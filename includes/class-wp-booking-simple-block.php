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
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			WP_BOOKING_SIMPLE_VERSION,
			true
		);

		// Shared by both blocks: API v3 (required by the iframed editor), the
		// front-end stylesheet (so previews in the editor canvas are styled),
		// and the layout tools of the block editor and Spectra.
		$shared = array(
			'api_version'   => 3,
			'style'         => 'wp-booking-simple-frontend',
			'editor_script' => 'wp-booking-simple-block',
			'supports'      => array(
				'html'    => false,
				'align'   => array( 'wide', 'full' ),
				'anchor'  => true,
				'spacing' => array(
					'margin'  => true,
					'padding' => true,
				),
			),
		);

		// Booking calendar block.
		$calendar_args = array(
			'title'           => __( 'Booking Calendar', 'wp-booking-simple' ),
			'description'     => __( 'Show a monthly availability calendar.', 'wp-booking-simple' ),
			'category'        => 'wp-booking-simple',
			'icon'            => 'calendar-alt',
			'keywords'        => array( 'booking', 'calendar', 'availability', 'chalet' ),
			'render_callback' => array( $this, 'render_calendar_block' ),
			'attributes'      => array(
				'anchor'      => array( 'type' => 'string' ),
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
			'render_callback' => array( $this, 'render_form_block' ),
			'attributes'      => array(
				'anchor'        => array( 'type' => 'string' ),
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

		$calendar_args = array_merge( $shared, $calendar_args );
		$form_args     = array_merge( $shared, $form_args );

		register_block_type( 'wp-booking-simple/calendar', $calendar_args );
		register_block_type( 'wp-booking-simple/form', $form_args );

		// Legacy names, kept renderable for content saved before the rename but
		// hidden from the inserter so each block appears only once when adding.
		$calendar_args['supports']['inserter'] = false;
		$form_args['supports']['inserter']     = false;

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

		return $this->wrap_with_styles( $html, $rules, $attributes );
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

		return $this->wrap_with_styles( $html, $rules, $attributes );
	}

	/**
	 * Validate a CSS colour. Palette variables such as
	 * `var(--ast-global-color-0)` (Astra) are accepted as well - that is what
	 * the block colour pickers hand out on Astra and block themes.
	 *
	 * @param string $color Raw colour value.
	 * @return string
	 */
	private function sanitize_css_color( $color ) {
		return WP_Booking_Simple_Theme::sanitize_color( $color );
	}

	/**
	 * Wrap rendered block HTML in a uniquely-scoped container carrying the
	 * block supports (alignment, spacing, anchor, custom class) and prepend a
	 * <style> block so per-block colour choices apply without leaking to other
	 * instances. Selectors are scoped under the unique wrapper class.
	 *
	 * @param string $html       Rendered HTML.
	 * @param array  $rules      CSS rule strings (without the wrapper prefix).
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	private function wrap_with_styles( $html, $rules, $attributes = array() ) {
		$id    = wp_unique_id( 'wpbs-block-' );
		$style = '';
		foreach ( $rules as $rule ) {
			// Scope every selector of a comma-separated list, not just the first.
			list( $selectors, $body ) = explode( '{', $rule, 2 );
			$scoped = array();
			foreach ( explode( ',', $selectors ) as $selector ) {
				$scoped[] = '.' . $id . ' ' . trim( $selector );
			}
			$style .= implode( ',', $scoped ) . '{' . $body;
		}

		$extra = array( 'class' => 'wpbs-block ' . $id );
		if ( ! empty( $attributes['anchor'] ) ) {
			$extra['id'] = sanitize_html_class( $attributes['anchor'] );
		}

		// Outside a block render (e.g. called directly) there are no supports.
		if ( function_exists( 'get_block_wrapper_attributes' ) && class_exists( 'WP_Block_Supports' ) && ! empty( WP_Block_Supports::$block_to_render ) ) {
			$wrapper = get_block_wrapper_attributes( $extra );
		} else {
			$wrapper = 'class="' . esc_attr( $extra['class'] ) . '"' . ( isset( $extra['id'] ) ? ' id="' . esc_attr( $extra['id'] ) . '"' : '' );
		}

		return ( '' !== $style ? '<style>' . $style . '</style>' : '' ) . '<div ' . $wrapper . '>' . $html . '</div>';
	}
}
