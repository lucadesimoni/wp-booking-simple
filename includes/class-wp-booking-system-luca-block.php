<?php
/**
 * Gutenberg Block Class for Booking Calendar
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Block Class
 */
class WP_Booking_System_Luca_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		// `block_categories_all` is WP 5.8+; `block_categories` covers older installs.
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 1 );
		add_filter( 'block_categories', array( $this, 'register_block_category' ), 10, 1 );
	}

	/**
	 * Add a dedicated "WP booking Luca" category to the block inserter so the
	 * blocks are easy to find (works in Gutenberg and Spectra alike).
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	public function register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'wp-booking-luca' === $category['slug'] ) {
				return $categories;
			}
		}

		return array_merge(
			array(
				array(
					'slug'  => 'wp-booking-luca',
					'title' => __( 'WP booking Luca', 'wp-booking-system-luca' ),
					'icon'  => 'calendar-alt',
				),
			),
			$categories
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
			'wp-booking-system-luca-block',
			WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			WP_BOOKING_SYSTEM_LUCA_VERSION,
			true
		);

		// Booking calendar block.
		register_block_type(
			'wp-booking-system/calendar',
			array(
				'title'           => __( 'Booking Calendar', 'wp-booking-system-luca' ),
				'description'     => __( 'Show a monthly availability calendar.', 'wp-booking-system-luca' ),
				'category'        => 'wp-booking-luca',
				'icon'            => 'calendar-alt',
				'keywords'        => array( 'booking', 'calendar', 'availability', 'chalet' ),
				'editor_script'   => 'wp-booking-system-luca-block',
				'render_callback' => array( $this, 'render_calendar_block' ),
				'attributes'      => array(
					'title' => array(
						'type'    => 'string',
						'default' => __( 'Booking Calendar', 'wp-booking-system-luca' ),
					),
				),
			)
		);

		// Booking form block.
		register_block_type(
			'wp-booking-system/form',
			array(
				'title'           => __( 'Booking Form', 'wp-booking-system-luca' ),
				'description'     => __( 'Show the booking form with live price and availability.', 'wp-booking-system-luca' ),
				'category'        => 'wp-booking-luca',
				'icon'            => 'calendar',
				'keywords'        => array( 'booking', 'reservation', 'form', 'chalet' ),
				'editor_script'   => 'wp-booking-system-luca-block',
				'render_callback' => array( $this, 'render_form_block' ),
				'attributes'      => array(
					'title' => array(
						'type'    => 'string',
						'default' => __( 'Book Your Stay', 'wp-booking-system-luca' ),
					),
				),
			)
		);
	}

	/**
	 * Render the calendar block on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_calendar_block( $attributes ) {
		$title = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Booking Calendar', 'wp-booking-system-luca' );

		return wp_booking_system_luca()->frontend->render_booking_calendar( array( 'title' => $title ) );
	}

	/**
	 * Render the booking form block on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_form_block( $attributes ) {
		$title = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Book Your Stay', 'wp-booking-system-luca' );

		return wp_booking_system_luca()->frontend->render_booking_form( array( 'title' => $title ) );
	}
}
