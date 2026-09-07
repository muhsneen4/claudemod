/**
 * Appyn Pro - front-end behaviour.
 *
 * Plain JavaScript, no dependencies. Every timing, distance and switch comes
 * from APX_CONFIG, which the theme options panel fills in.
 */
(function () {
	'use strict';

	var cfg = window.APX_CONFIG || {};
	var doc = document;
	var html = doc.documentElement;

	function on(el, type, fn) {
		if (el) { el.addEventListener(type, fn, false); }
	}

	function all(selector, root) {
		return Array.prototype.slice.call((root || doc).querySelectorAll(selector));
	}

	function isPhone() {
		return window.matchMedia('(max-width: 767px)').matches;
	}

	function motionOff() {
		if (!cfg.animations) { return true; }
		if (cfg.respectMotion && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return true; }
		if (cfg.disableMobile && isPhone()) { return true; }
		return false;
	}

	/* ------------------------------------------------------------- *
	 * Preloader
	 * ------------------------------------------------------------- */

	function preloader() {
		var el = doc.getElementById('apx-preloader');
		if (!el) { return; }

		var minTime = Math.max(0, cfg.preloader || 0);
		var start = Date.now();

		function hide() {
			var wait = Math.max(0, minTime - (Date.now() - start));
			window.setTimeout(function () {
				el.classList.add('is-done');
				window.setTimeout(function () {
					if (el.parentNode) { el.parentNode.removeChild(el); }
				}, 400);
			}, wait);
		}

		if (doc.readyState === 'complete') { hide(); } else { on(window, 'load', hide); }
	}

	/* ------------------------------------------------------------- *
	 * Dark mode
	 * ------------------------------------------------------------- */

	function darkMode() {
		if (!cfg.darkEnabled) { return; }

		function apply(mode) {
			html.setAttribute('data-apx-theme', mode);

			try {
				localStorage.setItem('apx_theme', mode);
				localStorage.setItem('px_light_dark_option', mode === 'dark' ? 1 : 0);
			} catch (e) {}

			// Keep the parent theme's own dark stylesheet in step.
			var parentSheet = doc.getElementById('css-dark-theme');
			if (parentSheet) {
				if (mode === 'dark') {
					parentSheet.removeAttribute('media');
				} else {
					parentSheet.setAttribute('media', 'max-width: 1px');
				}
			}

			var parentBtn = doc.getElementById('button_light_dark');
			if (parentBtn) { parentBtn.classList.toggle('active', mode === 'dark'); }
		}

		all('[data-apx-dark]').forEach(function (btn) {
			on(btn, 'click', function () {
				apply(html.getAttribute('data-apx-theme') === 'dark' ? 'light' : 'dark');
			});
		});

		if (cfg.darkDefault === 'system' && window.matchMedia) {
			var query = window.matchMedia('(prefers-color-scheme: dark)');
			var listener = function (event) {
				var stored = null;
				try { stored = localStorage.getItem('apx_theme'); } catch (e) {}
				if (!stored) { apply(event.matches ? 'dark' : 'light'); }
			};

			if (query.addEventListener) { query.addEventListener('change', listener); }
			else if (query.addListener) { query.addListener(listener); }
		}
	}

	/* ------------------------------------------------------------- *
	 * Header: sticky, drawer, search
	 * ------------------------------------------------------------- */

	function header() {
		var head = doc.querySelector('.apx-header');
		var spacer = doc.querySelector('.apx-header-spacer');

		if (head && cfg.sticky) {
			var trigger = head.offsetTop + 40;

			var onScroll = function () {
				var stuck = window.pageYOffset > trigger;
				head.classList.toggle('is-sticky', stuck);
				if (spacer) { spacer.classList.toggle('is-on', stuck); }
			};

			on(window, 'scroll', onScroll);
			onScroll();
		}

		var burger = doc.querySelector('.apx-burger');
		var drawer = doc.querySelector('.apx-drawer');

		if (burger && drawer) {
			var toggle = function (open) {
				drawer.classList.toggle('is-open', open);
				burger.classList.toggle('is-open', open);
				burger.setAttribute('aria-expanded', open ? 'true' : 'false');
				doc.body.style.overflow = open ? 'hidden' : '';
			};

			on(burger, 'click', function () { toggle(!drawer.classList.contains('is-open')); });
			on(drawer.querySelector('.apx-drawer__veil'), 'click', function () { toggle(false); });
			on(doc, 'keyup', function (e) { if (e.key === 'Escape') { toggle(false); } });
		}

		var searchBtn = doc.querySelector('[data-apx-search]');
		var searchBox = doc.querySelector('.apx-search');

		if (searchBtn && searchBox) {
			on(searchBtn, 'click', function () {
				searchBox.classList.toggle('is-open');
				var input = searchBox.querySelector('input');
				if (input && searchBox.classList.contains('is-open')) { input.focus(); }
			});
		}
	}

	/* ------------------------------------------------------------- *
	 * Hero slider
	 * ------------------------------------------------------------- */

	function hero() {
		var root = doc.querySelector('[data-apx-hero]');
		if (!root) { return; }

		var slides = all('.apx-hero__slide', root);
		var dots = all('.apx-hero__dot', root);
		if (slides.length < 2) { return; }

		var index = 0;
		var timer = null;

		function go(next) {
			if (cfg.heroLoop) {
				index = (next + slides.length) % slides.length;
			} else {
				index = Math.max(0, Math.min(slides.length - 1, next));
			}

			slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === index); });
			dots.forEach(function (dot, i) { dot.classList.toggle('is-active', i === index); });
		}

		function play() {
			if (!cfg.heroAutoplay) { return; }
			stop();
			timer = window.setInterval(function () { go(index + 1); }, cfg.heroAutoplay);
		}

		function stop() {
			if (timer) { window.clearInterval(timer); timer = null; }
		}

		on(root.querySelector('.apx-hero__arrow--prev'), 'click', function () { go(index - 1); play(); });
		on(root.querySelector('.apx-hero__arrow--next'), 'click', function () { go(index + 1); play(); });

		dots.forEach(function (dot) {
			on(dot, 'click', function () { go(parseInt(dot.getAttribute('data-index'), 10) || 0); play(); });
		});

		if (cfg.heroPause) {
			on(root, 'mouseenter', stop);
			on(root, 'mouseleave', play);
		}

		// Touch swipe.
		var startX = null;

		on(root, 'touchstart', function (e) { startX = e.touches[0].clientX; });
		on(root, 'touchend', function (e) {
			if (startX === null) { return; }
			var delta = e.changedTouches[0].clientX - startX;
			if (Math.abs(delta) > 40) { go(delta < 0 ? index + 1 : index - 1); play(); }
			startX = null;
		});

		play();
	}

	/* ------------------------------------------------------------- *
	 * Category strip arrows
	 * ------------------------------------------------------------- */

	function categories() {
		var list = doc.querySelector('[data-apx-cats]');
		if (!list) { return; }

		var wrap = list.parentNode;

		function scrollBy(amount) {
			list.scrollBy({ left: amount, behavior: motionOff() ? 'auto' : 'smooth' });
		}

		on(wrap.querySelector('.apx-cats__arrow--prev'), 'click', function () { scrollBy(-260); });
		on(wrap.querySelector('.apx-cats__arrow--next'), 'click', function () { scrollBy(260); });
	}

	/* ------------------------------------------------------------- *
	 * Scroll animations
	 * ------------------------------------------------------------- */

	function scrollAnimations() {
		if (motionOff() || cfg.scrollAnim === 'none' || !('IntersectionObserver' in window)) { return; }

		var targets = all('.baps .bav, .bloque-blog, .apx-news, .apx-cats__item, .section .title-section, .apx-infobar');

		if (!targets.length) { return; }

		targets.forEach(function (el, i) {
			if (el.hasAttribute('data-apx-anim')) { return; }
			el.setAttribute('data-apx-anim', cfg.scrollAnim);
			el.style.setProperty('--apx-delay', ((i % 12) * parseFloat(getComputedStyle(html).getPropertyValue('--apx-stagger') || 0.05)) + 's');
		});

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-in');
					if (cfg.scrollOnce) { observer.unobserve(entry.target); }
				} else if (!cfg.scrollOnce) {
					entry.target.classList.remove('is-in');
				}
			});
		}, { rootMargin: '0px 0px -' + (cfg.scrollOffset || 0) + 'px 0px', threshold: 0.05 });

		targets.forEach(function (el) { observer.observe(el); });
	}

	/* ------------------------------------------------------------- *
	 * Click feedback
	 * ------------------------------------------------------------- */

	function ripples() {
		if (cfg.clickFeedback !== 'ripple' || motionOff()) { return; }

		on(doc, 'click', function (e) {
			var target = e.target.closest('.buttond, .apx-hero__btn, .apx-cats__item a, .apx-dlbtn, .apx-btt');
			if (!target) { return; }

			var rect = target.getBoundingClientRect();
			var size = Math.max(rect.width, rect.height);
			var span = doc.createElement('span');

			span.className = 'apx-ripple';
			span.style.width = span.style.height = size + 'px';
			span.style.left = (e.clientX - rect.left - size / 2) + 'px';
			span.style.top = (e.clientY - rect.top - size / 2) + 'px';

			if (getComputedStyle(target).position === 'static') { target.style.position = 'relative'; }

			target.appendChild(span);
			window.setTimeout(function () {
				if (span.parentNode) { span.parentNode.removeChild(span); }
			}, 620);
		});
	}

	/* ------------------------------------------------------------- *
	 * Tilt and parallax
	 * ------------------------------------------------------------- */

	function tilt() {
		if (motionOff() || isPhone()) { return; }

		var strength = parseFloat(cfg.tilt) || 0;
		var nodes = strength ? all('.baps .bav, .apx-news') : [];

		if (cfg.detailTilt) {
			nodes = nodes.concat(all('.app-icb .bloque-imagen'));
			if (!strength) { strength = 8; }
		}

		if (!nodes.length || !strength) { return; }

		nodes.forEach(function (node) {
			on(node, 'mousemove', function (e) {
				var rect = node.getBoundingClientRect();
				var px = (e.clientX - rect.left) / rect.width - 0.5;
				var py = (e.clientY - rect.top) / rect.height - 0.5;

				node.style.transform = 'perspective(700px) rotateY(' + (px * strength) + 'deg) rotateX(' + (-py * strength) + 'deg)';
			});

			on(node, 'mouseleave', function () { node.style.transform = ''; });
		});
	}

	function parallax() {
		var amount = parseFloat(cfg.parallax) || 0;
		var banner = doc.querySelector('.apx-detail-banner--parallax');
		var nodes = all('[data-apx-parallax]');

		if (banner) { nodes.push(banner); }
		if (!nodes.length || motionOff()) { return; }
		if (!amount) { amount = 0.25; }

		var tick = function () {
			var y = window.pageYOffset;
			nodes.forEach(function (node) {
				node.style.transform = 'translate3d(0,' + (y * amount) + 'px,0) scale(1.1)';
			});
		};

		on(window, 'scroll', function () { window.requestAnimationFrame(tick); });
		tick();
	}

	/* ------------------------------------------------------------- *
	 * Back to top
	 * ------------------------------------------------------------- */

	function backToTop() {
		var btn = doc.getElementById('apx-backtotop');
		if (!btn) { return; }

		var trigger = cfg.bttTrigger || 400;

		var onScroll = function () {
			btn.classList.toggle('is-visible', window.pageYOffset > trigger);
		};

		on(window, 'scroll', onScroll);
		on(btn, 'click', function () {
			window.scrollTo({ top: 0, behavior: motionOff() ? 'auto' : 'smooth' });
		});

		onScroll();
	}

	/* ------------------------------------------------------------- *
	 * Download button states
	 * ------------------------------------------------------------- */

	function confetti(target) {
		var rect = target.getBoundingClientRect();
		var colors = ['#4CAF50', '#FF9800', '#2196F3', '#9C27B0', '#F44336'];

		for (var i = 0; i < 18; i++) {
			var piece = doc.createElement('span');
			piece.className = 'apx-confetti';
			piece.style.left = (rect.left + rect.width / 2) + 'px';
			piece.style.top = rect.top + 'px';
			piece.style.background = colors[i % colors.length];
			piece.style.setProperty('--cx', (Math.random() * 240 - 120) + 'px');
			piece.style.animationDelay = (Math.random() * 0.2) + 's';
			doc.body.appendChild(piece);

			window.setTimeout(function (node) {
				return function () { if (node.parentNode) { node.parentNode.removeChild(node); } };
			}(piece), 1400);
		}
	}

	function downloadButton() {
		all('a.downloadAPK, .apx-dlbtn').forEach(function (btn) {
			on(btn, 'click', function () {
				if (btn.classList.contains('is-loading') || btn.classList.contains('is-success')) { return; }

				var original = btn.innerHTML;
				btn.classList.add('is-loading');

				if (cfg.loadingStyle === 'spinner') {
					btn.innerHTML = '<span class="apx-dlbtn__spinner"></span>';
				} else if (cfg.loadingStyle === 'dots') {
					btn.innerHTML = '<span class="apx-dlbtn__dots"><i></i><i></i><i></i></span>';
				} else if (cfg.loadingStyle === 'progress') {
					btn.innerHTML = original + '<span class="apx-dlbtn__progress"></span>';
					var bar = btn.querySelector('.apx-dlbtn__progress');
					var pct = 0;
					var move = window.setInterval(function () {
						pct = Math.min(100, pct + 12);
						bar.style.width = pct + '%';
						if (pct >= 100) { window.clearInterval(move); }
					}, 90);
				}

				window.setTimeout(function () {
					btn.classList.remove('is-loading');
					btn.classList.add('is-success');
					btn.innerHTML = '<i class="fas fa-check"></i> ' + (cfg.downloadText || '');

					if (cfg.downloadAnim === 'confetti' && !motionOff()) { confetti(btn); }

					window.setTimeout(function () {
						btn.classList.remove('is-success');
						btn.innerHTML = original;
					}, 2600);
				}, 900);
			});
		});
	}

	/* ------------------------------------------------------------- *
	 * Boot
	 * ------------------------------------------------------------- */

	function boot() {
		preloader();
		darkMode();
		header();
		hero();
		categories();
		scrollAnimations();
		ripples();
		tilt();
		parallax();
		backToTop();
		downloadButton();
	}

	if (doc.readyState === 'loading') {
		on(doc, 'DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
