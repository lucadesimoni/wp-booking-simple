<?php
/**
 * Theme integration: shared styles, the color scheme, and color handling
 * for the block and Elementor controls.
 *
 * The front-end stylesheet is driven by --wpbs-* custom properties whose
 * defaults are the plugin's own dark-red palette. With the "Theme colors"
 * scheme (Settings > Booking Form) they are re-pointed at the active theme:
 * Astra's global palette and Customizer buttons, Elementor's global colors
 * and fonts, or a block theme's presets, in that order.
 *
 * @package WP_Booking_Simple
 * @since 1.23.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_Simple_Theme Class
 */
class WP_Booking_Simple_Theme {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Registered on init (not wp_enqueue_scripts) so blocks can name the
		// handle and the block-editor iframe can load it.
		add_action( 'init', array( $this, 'register_styles' ), 5 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_canvas_styles' ) );
	}

	/**
	 * Register the front-end stylesheets, with the color scheme attached.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style( 'flatpickr', WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.css', array(), '4.6.13' );
		wp_register_style(
			'wp-booking-simple-frontend',
			WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/css/frontend.css',
			array( 'flatpickr' ),
			WP_BOOKING_SIMPLE_VERSION
		);

		$css = self::scheme_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'wp-booking-simple-frontend', $css );
		}
	}

	/**
	 * Load the booking styles into the block editor canvas, so the block
	 * previews look like the front end. `enqueue_block_assets` also fires on
	 * the front end, where the renderers enqueue the styles themselves.
	 *
	 * @return void
	 */
	public function enqueue_editor_canvas_styles() {
		if ( is_admin() ) {
			wp_enqueue_style( 'wp-booking-simple-frontend' );
		}
	}

	/**
	 * Whether the booking UI follows the theme's colors.
	 *
	 * @return bool
	 */
	public static function uses_theme_colors() {
		return 'theme' === get_option( 'wpbsl_color_scheme', 'plugin' );
	}

	/**
	 * Accept a CSS color from an editor color picker, or return ''.
	 *
	 * Theme palettes hand out CSS variables rather than hex values - Astra's
	 * global palette is `var(--ast-global-color-0)`, block themes use
	 * `var(--wp--preset--color--primary)`, Elementor globals are
	 * `var(--e-global-color-primary)` - so those are allowed alongside hex,
	 * rgb() and hsl(). A palette color then keeps following the theme.
	 *
	 * @param string $color Raw color value.
	 * @return string
	 */
	public static function sanitize_color( $color ) {
		$color = trim( (string) $color );
		if ( '' === $color ) {
			return '';
		}
		if ( preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^(?:rgb|hsl)a?\(\s*[0-9.,%\s\/deg]+\)$/i', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^var\(\s*--[a-z0-9_-]+\s*\)$/i', $color ) ) {
			return $color;
		}
		return '';
	}

	/**
	 * Read an Astra Customizer setting, or $default when Astra is not active.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	private static function astra_option( $key, $default = '' ) {
		if ( ! function_exists( 'astra_get_option' ) ) {
			return $default;
		}
		$value = astra_get_option( $key, $default );
		return null === $value ? $default : $value;
	}

	/**
	 * Astra's button corner radius (Astra 4 four-corner setting, or the
	 * legacy single value), or '' when unset.
	 *
	 * @return string
	 */
	private static function astra_button_radius() {
		$fields = self::astra_option( 'button-radius-fields' );
		if ( is_array( $fields ) && isset( $fields['desktop'] ) && is_array( $fields['desktop'] ) ) {
			$unit    = isset( $fields['desktop-unit'] ) && in_array( $fields['desktop-unit'], array( 'px', 'em', 'rem', '%' ), true ) ? $fields['desktop-unit'] : 'px';
			$corners = array();
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				if ( ! isset( $fields['desktop'][ $side ] ) || ! is_numeric( $fields['desktop'][ $side ] ) ) {
					return '';
				}
				$corners[] = ( 0 + $fields['desktop'][ $side ] ) . $unit;
			}
			return implode( ' ', $corners );
		}
		$legacy = self::astra_option( 'button-radius' );
		return is_numeric( $legacy ) ? ( 0 + $legacy ) . 'px' : '';
	}

	/**
	 * The heading font stack set in Astra, or '' when it inherits.
	 *
	 * @return string
	 */
	private static function astra_heading_font() {
		$font = self::astra_option( 'headings-font-family' );
		if ( ! is_string( $font ) || '' === trim( $font ) || 'inherit' === $font ) {
			return '';
		}
		return preg_replace( '/[^\w\s,\'"\-]/', '', $font );
	}

	/**
	 * Custom properties for the "Theme colors" scheme ('' for the plugin's
	 * own palette).
	 *
	 * Declared on `body`, not `:root`: Elementor puts its global colors on
	 * the kit class of the body element, so they only resolve from there.
	 *
	 * @return string
	 */
	public static function scheme_css() {
		if ( ! self::uses_theme_colors() ) {
			return '';
		}

		$vars = array(
			'--wpbs-primary'         => 'var(--ast-global-color-0, var(--e-global-color-primary, var(--wp--preset--color--primary, #8B0000)))',
			'--wpbs-primary-dark'    => 'var(--ast-global-color-1, color-mix(in srgb, var(--wpbs-primary) 80%, #000))',
			'--wpbs-booked'          => 'var(--wpbs-primary)',
			'--wpbs-button-bg'       => 'var(--wpbs-primary)',
			'--wpbs-button-bg-hover' => 'var(--wpbs-primary-dark)',
			'--wpbs-heading-color'   => 'var(--ast-global-color-2, var(--wp--preset--color--contrast, inherit))',
			'--wpbs-heading-font'    => 'var(--e-global-typography-primary-font-family, inherit)',
		);

		// Astra's Customizer button settings, when set.
		$astra = array(
			'--wpbs-button-bg'       => self::sanitize_color( self::astra_option( 'button-bg-color' ) ),
			'--wpbs-button-bg-hover' => self::sanitize_color( self::astra_option( 'button-bg-h-color' ) ),
			'--wpbs-button-color'    => self::sanitize_color( self::astra_option( 'button-color' ) ),
			'--wpbs-button-radius'   => self::astra_button_radius(),
			'--wpbs-heading-font'    => self::astra_heading_font(),
		);
		foreach ( $astra as $prop => $value ) {
			if ( '' !== $value ) {
				$vars[ $prop ] = $value;
			}
		}

		/**
		 * Filter the custom properties of the "Theme colors" scheme.
		 *
		 * @param array $vars Custom property => CSS value.
		 */
		$vars = (array) apply_filters( 'wpbsl_theme_scheme_vars', $vars );

		$css = '';
		foreach ( $vars as $prop => $value ) {
			$css .= $prop . ':' . $value . ';';
		}
		return 'body{' . $css . '}';
	}
}
