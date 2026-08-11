<?php
/**
 * Gutenberg Block Class for Booking Calendar
 *
 * @package WP_Booking_Simple
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_Simple_Block Class
 */
class WP_Booking_Simple_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );

		// `block_categories_all` is WP 5.8+; `block_categories` covers older
		// installs. Only register the legacy filter when the modern one is
		// unavailable — attaching to the deprecated hook on WP 5.8+ makes core
		// emit a `_deprecated_hook` notice, and would add the category twice.
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 1 );

		if ( ! function_exists( 'get_default_block_categories' ) ) {
			add_filter( 'block_categories', array( $this, 'register_block_category' ), 10, 1 );
		}
	}

	/**
	 * Add a dedicated "WP Booking Simple" category to the block inserter so the
	 * blocks are easy to find (works in Gutenberg and Spectra alike).
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	public function register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'wp-booking-simple' === $category['slug'] ) {
				return $categories;
			}
		}

		return array_merge(
			array(
				array(
					'slug'  => 'wp-booking-simple',
					'title' => __( 'WP Booking Simple', 'wp-booking-simple' ),
					'icon'  => 'calendar-alt',
				),
			),
			$categories
		);
	}

	/**
	 * Every block name this plugin answers to, current and legacy.
	 *
	 * The `wp-booking-system/*` names are what the blocks were registered under
	 * before the plugin was renamed to WP Booking Simple. A post stores the
	 * block name in its content, so those names stay registered as aliases -
	 * otherwise every page already using a block would show
	 * "Your site doesn't include support for this block".
	 *
	 * @return array Block name => render method.
	 */
	public static function get_block_names() {
		return array(
			'wp-booking-simple/calendar' => 'render_calendar_block',
			'wp-booking-simple/form'     => 'render_form_block',
			// Legacy aliases (pre-rename).
			'wp-booking-system/calendar' => 'render_calendar_block',
			'wp-booking-system/form'     => 'render_form_block',
		);
	}

	/**
	 * Register Gutenberg block.
	 */
	public function register_block() {
		// Check if Gutenberg is active.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Register block script.
		wp_register_script(
			'wp-booking-simple-block',
			WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			WP_BOOKING_SIMPLE_VERSION,
			true
		);

		// Booking calendar block.
		$calendar_args = array(
			'title'           => __( 'Booking Calendar', 'wp-booking-simple' ),
			'description'     => __( 'Show a monthly availability calendar.', 'wp-booking-simple' ),
			'category'        => 'wp-booking-simple',
			'icon'            => 'calendar-alt',
			'keywords'        => array( 'booking', 'calendar', 'availability', 'chalet' ),
			'editor_script'   => 'wp-booking-simple-block',
			'render_callback' => array( $this, 'render_calendar_block' ),
			'attributes'      => array(
				'title'       => array(
					'type'    => 'string',
					'default' => __( 'Booking Calendar', 'wp-booking-simple' ),
				),
				'accentColor' => array( 'type' => 'string', 'default' => '' ),
				'bookedColor' => array( 'type' => 'string', 'default' => '' ),
			),
		);

		// Booking form block.
		$form_args = array(
			'title'           => __( 'Booking Form', 'wp-booking-simple' ),
			'description'     => __( 'Show the booking form with live price and availability.', 'wp-booking-simple' ),
			'category'        => 'wp-booking-simple',
			'icon'            => 'calendar',
			'keywords'        => array( 'booking', 'reservation', 'form', 'chalet' ),
			'editor_script'   => 'wp-booking-simple-block',
			'render_callback' => array( $this, 'render_form_block' ),
			'attributes'      => array(
				'title'         => array(
					'type'    => 'string',
					'default' => __( 'Book Your Stay', 'wp-booking-simple' ),
				),
				'accentColor'   => array( 'type' => 'string', 'default' => '' ),
				'buttonBg'      => array( 'type' => 'string', 'default' => '' ),
				'buttonColor'   => array( 'type' => 'string', 'default' => '' ),
				'buttonHoverBg' => array( 'type' => 'string', 'default' => '' ),
			),
		);

		register_block_type( 'wp-booking-simple/calendar', $calendar_args );
		register_block_type( 'wp-booking-simple/form', $form_args );

		// Legacy names, kept renderable for content saved before the rename but
		// hidden from the inserter so each block appears only once when adding.
		$calendar_args['supports'] = array( 'inserter' => false );
		$form_args['supports']     = array( 'inserter' => false );

		register_block_type( 'wp-booking-system/calendar', $calendar_args );
		register_block_type( 'wp-booking-system/form', $form_args );
	}

	/**
	 * Render the calendar block on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_calendar_block( $attributes ) {
		$title = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Booking Calendar', 'wp-booking-simple' );
		$html  = wp_booking_simple()->frontend->render_booking_calendar( array( 'title' => $title ) );

		$accent = $this->sanitize_css_color( isset( $attributes['accentColor'] ) ? $attributes['accentColor'] : '' );
		$booked = $this->sanitize_css_color( isset( $attributes['bookedColor'] ) ? $attributes['bookedColor'] : '' );

		$rules = array();
		if ( '' !== $accent ) {
			$rules[] = '.wpbs-calendar-shortcode .fc-button{background-color:' . $accent . ';border-color:' . $accent . '}';
			$rules[] = '.wpbs-calendar-shortcode .fc-button:hover{background-color:' . $accent . ';border-color:' . $accent . '}';
			$rules[] = '.wpbs-calendar-shortcode .fc-col-header-cell-cushion{color:' . $accent . ' !important}';
			$rules[] = '.wpbs-calendar-shortcode .fc-day-today .fc-daygrid-day-number{color:' . $accent . ' !important}';
			$rules[] = '.wpbs-calendar-shortcode .wpbs-selected-edge{background-color:' . $accent . ' !important}';
		}
		if ( '' !== $booked ) {
			$rules[] = '.wpbs-calendar-shortcode .wpbs-unavailable-date{background-color:' . $booked . ' !important}';
			$rules[] = '.wpbs-legend-booked{background-color:' . $booked . '}';
		}

		return $this->wrap_with_styles( $html, $rules );
	}

	/**
	 * Render the booking form block on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_form_block( $attributes ) {
		$title = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Book Your Stay', 'wp-booking-simple' );
		$html  = wp_booking_simple()->frontend->render_booking_form( array( 'title' => $title ) );

		$accent     = $this->sanitize_css_color( isset( $attributes['accentColor'] ) ? $attributes['accentColor'] : '' );
		$btn_bg     = $this->sanitize_css_color( isset( $attributes['buttonBg'] ) ? $attributes['buttonBg'] : '' );
		$btn_color  = $this->sanitize_css_color( isset( $attributes['buttonColor'] ) ? $attributes['buttonColor'] : '' );
		$btn_hover  = $this->sanitize_css_color( isset( $attributes['buttonHoverBg'] ) ? $attributes['buttonHoverBg'] : '' );

		$rules = array();
		if ( '' !== $accent ) {
			$rules[] = '.wpbs-price-value{color:' . $accent . '}';
			$rules[] = '.wpbs-price-summary{border-left-color:' . $accent . '}';
			$rules[] = '.wpbs-booking-form input:focus,.wpbs-booking-form select:focus,.wpbs-booking-form textarea:focus{border-color:' . $accent . '}';
		}
		if ( '' !== $btn_bg ) {
			$rules[] = '.wpbs-booking-form .wpbs-submit-button{background-color:' . $btn_bg . '}';
		}
		if ( '' !== $btn_color ) {
			$rules[] = '.wpbs-booking-form .wpbs-submit-button{color:' . $btn_color . '}';
		}
		if ( '' !== $btn_hover ) {
			$rules[] = '.wpbs-booking-form .wpbs-submit-button:hover{background-color:' . $btn_hover . '}';
		}

		return $this->wrap_with_styles( $html, $rules );
	}

	/**
	 * Validate a CSS colour string (hex or rgb/rgba); returns '' if invalid.
	 *
	 * @param string $color Raw colour value.
	 * @return string
	 */
	private function sanitize_css_color( $color ) {
		$color = trim( (string) $color );
		if ( '' === $color ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^rgba?\(\s*[0-9.,%\s]+\)$/', $color ) ) {
			return $color;
		}
		return '';
	}

	/**
	 * Wrap rendered block HTML in a uniquely-scoped container and prepend a
	 * <style> block so per-block colour choices apply without leaking to other
	 * instances. Selectors are scoped under the unique wrapper class.
	 *
	 * @param string $html  Rendered HTML.
	 * @param array  $rules CSS rule strings (without the wrapper prefix).
	 * @return string
	 */
	private function wrap_with_styles( $html, $rules ) {
		if ( empty( $rules ) ) {
			return $html;
		}

		$id    = wp_unique_id( 'wpbs-block-' );
		$style = '';
		foreach ( $rules as $rule ) {
			$style .= '.' . $id . ' ' . $rule;
		}

		return '<style>' . $style . '</style><div class="wpbs-block ' . esc_attr( $id ) . '">' . $html . '</div>';
	}
}
