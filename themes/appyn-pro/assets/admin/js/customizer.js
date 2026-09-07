/**
 * Appyn Pro - customizer live preview.
 *
 * Rewrites the design tokens in place, so colour and size changes show up
 * without reloading the preview frame.
 */
(function (api) {
	'use strict';

	var map = window.APX_PREVIEW || {};
	var styleEl = null;

	function sheet() {
		if (!styleEl) {
			styleEl = document.createElement('style');
			styleEl.id = 'apx-preview-tokens';
			document.head.appendChild(styleEl);
		}

		return styleEl;
	}

	var overrides = { root: {}, dark: {} };

	function paint() {
		var css = ':root{';

		Object.keys(overrides.root).forEach(function (name) {
			css += name + ':' + overrides.root[name] + ';';
		});

		css += '}html[data-apx-theme="dark"]{';

		Object.keys(overrides.dark).forEach(function (name) {
			css += name + ':' + overrides.dark[name] + ';';
		});

		css += '}';

		sheet().textContent = css;
	}

	Object.keys(map).forEach(function (setting) {
		var conf = map[setting];

		api(setting, function (value) {
			value.bind(function (next) {
				if (conf.type === 'font') {
					next = '"' + next + '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
				} else if (conf.unit) {
					next = next + conf.unit;
				}

				overrides[conf.scope][conf.var] = next;
				paint();
			});
		});
	});
})(wp.customize);
