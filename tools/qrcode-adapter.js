//---------------------------------------------------------------------
//
// WP booking Luca adapter (added by this plugin, not part of upstream).
//
// Exposes the library under the plugin-prefixed global `WPBSLQRCode` so it
// cannot collide with another plugin or theme shipping its own `qrcode`.
//
// Upstream takes the error-correction level as a letter ('L','M','Q','H').
// This adapter also accepts the numeric level used by the QR specification
// (and by the older standalone qrcode.js), so `new WPBSLQRCode( 0, 0 )`
// means "auto type number, level M" as the Swiss QR-bill requires.
//
//---------------------------------------------------------------------
(function (root) {
	'use strict';

	var LEVELS = { 0: 'M', 1: 'L', 2: 'H', 3: 'Q' };

	function WPBSLQRCode(typeNumber, errorCorrectionLevel) {
		var level = errorCorrectionLevel;

		if ('string' !== typeof level) {
			level = LEVELS[ level ];
		}

		if (!level) {
			level = 'M';
		}

		// `qrcode` is a factory, so this works with or without `new`.
		return qrcode(typeNumber || 0, level);
	}

	root.WPBSLQRCode = WPBSLQRCode;
}(typeof window !== 'undefined' ? window : this));
