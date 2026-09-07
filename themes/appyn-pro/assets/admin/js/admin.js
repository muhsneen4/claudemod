/**
 * Appyn Pro - options panel behaviour.
 */
(function ($) {
	'use strict';

	var data = window.APX_ADMIN || {};
	var i18n = data.i18n || {};

	/* Colour pickers -------------------------------------------------- */

	function initColors(scope) {
		$(scope).find('.apx-color').each(function () {
			if ($(this).hasClass('wp-color-picker')) { return; }
			$(this).wpColorPicker({ palettes: true });
		});
	}

	/* Range and number stay in sync ----------------------------------- */

	function initSliders(scope) {
		$(scope).find('.apx-slider').each(function () {
			var $row = $(this);
			var $range = $row.find('input[type="range"]');
			var $number = $row.find('input[type="number"]');

			// Only the number input is submitted, so the range is display only.
			$range.removeAttr('name');

			$range.on('input change', function () { $number.val($range.val()); });
			$number.on('input change', function () { $range.val($number.val()); });
		});

		$(scope).find('.apx-field--colora').each(function () {
			var $row = $(this);
			var $range = $row.find('input[type="range"]');
			var $out = $row.find('output');

			$range.on('input change', function () { $out.text($range.val()); });
		});
	}

	/* Media library --------------------------------------------------- */

	function initMedia(scope) {
		$(scope).on('click', '.apx-image__pick', function (e) {
			e.preventDefault();

			var $wrap = $(this).closest('.apx-image');
			var frame = wp.media({
				title: 'Select image',
				multiple: false,
				library: { type: 'image' }
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();

				$wrap.find('.apx-image__input').val(attachment.url).trigger('change');
				$wrap.find('.apx-image__preview').html('<img src="' + attachment.url + '" alt="">');
			});

			frame.open();
		});

		$(scope).on('click', '.apx-image__clear', function (e) {
			e.preventDefault();

			var $wrap = $(this).closest('.apx-image');
			$wrap.find('.apx-image__input').val('').trigger('change');
			$wrap.find('.apx-image__preview').empty();
		});
	}

	/* Icon picker ----------------------------------------------------- */

	function openIconModal($input) {
		var icons = data.icons || [];
		var $modal = $(
			'<div class="apx-iconmodal"><div class="apx-iconmodal__box">' +
			'<div class="apx-iconmodal__head"><input type="search" placeholder="' + (i18n.search || 'Search') + '"><button type="button" class="button apx-iconmodal__close">&times;</button></div>' +
			'<div class="apx-iconmodal__grid"></div></div></div>'
		);

		var $grid = $modal.find('.apx-iconmodal__grid');

		function paint(filter) {
			$grid.empty();

			icons.forEach(function (icon) {
				if (filter && icon.indexOf(filter) === -1) { return; }
				$grid.append('<button type="button" data-icon="' + icon + '" title="' + icon + '"><i class="' + icon + '"></i></button>');
			});
		}

		paint('');

		$modal.find('input').on('input', function () { paint($(this).val().toLowerCase()); });

		$modal.on('click', '.apx-iconmodal__grid button', function () {
			var icon = $(this).data('icon');

			$input.val(icon).trigger('change');
			$input.closest('.apx-iconpick').find('.apx-iconpick__btn i').attr('class', icon);
			$modal.remove();
		});

		$modal.on('click', '.apx-iconmodal__close', function () { $modal.remove(); });
		$modal.on('click', function (e) { if (e.target === $modal[0]) { $modal.remove(); } });

		$('body').append($modal);
	}

	function initIcons(scope) {
		$(scope).on('click', '.apx-iconpick__btn', function (e) {
			e.preventDefault();
			openIconModal($(this).closest('.apx-iconpick').find('.apx-iconpick__input'));
		});

		$(scope).on('change', '.apx-iconpick__input', function () {
			$(this).closest('.apx-iconpick').find('.apx-iconpick__btn i').attr('class', $(this).val());
		});
	}

	/* Repeaters ------------------------------------------------------- */

	function initRepeaters(scope) {
		$(scope).find('.apx-rep').each(function () {
			var $rep = $(this);
			var $rows = $rep.find('.apx-rep__rows').first();

			if ($rows.data('apx-ready')) { return; }
			$rows.data('apx-ready', true);

			if ($.fn.sortable) {
				$rows.sortable({
					handle: '.apx-rep__handle',
					axis: 'y',
					update: reindex
				});
			}

			function reindex() {
				$rows.children('.apx-rep__row').each(function (index) {
					$(this).find('input, select, textarea').each(function () {
						var name = $(this).attr('name');
						if (!name) { return; }
						$(this).attr('name', name.replace(/\[(\d+|__i__)\]/, '[' + index + ']'));
					});
				});
			}

			$rep.on('click', '.apx-rep__add', function (e) {
				e.preventDefault();

				var template = $rep.find('.apx-rep__tpl').html();
				var index = $rows.children('.apx-rep__row').length;
				var $row = $(template.replace(/__i__/g, index));

				$rows.append($row);
				$row.addClass('is-open');

				initColors($row);
				initSliders($row);
				$row.find('.apx-color').each(function () { $(this).wpColorPicker(); });
			});

			$rep.on('click', '.apx-rep__del', function (e) {
				e.preventDefault();

				if (!window.confirm(i18n.confirmDelete || 'Remove?')) { return; }

				$(this).closest('.apx-rep__row').remove();
				reindex();
			});

			$rep.on('click', '.apx-rep__toggle, .apx-rep__title', function (e) {
				e.preventDefault();
				$(this).closest('.apx-rep__row').toggleClass('is-open');
			});

			$rep.on('input', 'input[type="text"]', function () {
				var $row = $(this).closest('.apx-rep__row');
				var name = $(this).attr('name') || '';

				if (/\[(label|name|title)\]$/.test(name)) {
					$row.find('.apx-rep__title').first().text($(this).val() || (i18n.row || 'Item'));
				}
			});
		});
	}

	/* Code editors ---------------------------------------------------- */

	function initCode() {
		if (!data.codeEditor || !window.wp || !wp.codeEditor) { return; }

		$('.apx-code').each(function () {
			if ($(this).data('apx-code')) { return; }
			$(this).data('apx-code', true);

			var settings = $.extend(true, {}, data.codeEditor);
			var isJs = /js_/.test($(this).attr('id') || '');

			if (isJs) {
				settings.codemirror = $.extend({}, settings.codemirror, { mode: 'javascript' });
			}

			wp.codeEditor.initialize(this, settings);
		});
	}

	/* Confirm the reset ----------------------------------------------- */

	function initReset() {
		$('.apx-reset').on('submit', function (e) {
			if (!window.confirm(i18n.confirmReset || 'Reset?')) { e.preventDefault(); }
		});
	}

	$(function () {
		initColors(document);
		initSliders(document);
		initMedia(document);
		initIcons(document);
		initRepeaters(document);
		initCode();
		initReset();
	});
})(jQuery);
