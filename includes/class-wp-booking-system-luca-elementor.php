<?php
/**
 * Elementor integration: registers native "Booking Form" and "Booking
 * Calendar" widgets so they appear in the Elementor editor panel.
 *
 * All hooks below only fire when Elementor is active, so this class is inert
 * on sites without Elementor.
 *
 * @package WP_Booking_System_Luca
 * @since 1.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Elementor Class
 */
class WP_Booking_System_Luca_Elementor {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		// Make the booking styles/scripts available inside the Elementor editor preview.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_assets' ) );
	}

	/**
	 * Add a dedicated "WP booking Luca" category to the Elementor panel.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'wp-booking-luca',
			array(
				'title' => __( 'WP booking Luca', 'wp-booking-system-luca' ),
				'icon'  => 'eicon-calendar',
			)
		);
	}

	/**
	 * Register the widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/elementor/class-wp-booking-system-luca-elementor-widgets.php';

		$form     = new WP_Booking_System_Luca_Elementor_Form_Widget();
		$calendar = new WP_Booking_System_Luca_Elementor_Calendar_Widget();

		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $form );
			$widgets_manager->register( $calendar );
		} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			// Elementor < 3.5 fallback.
			$widgets_manager->register_widget_type( $form );
			$widgets_manager->register_widget_type( $calendar );
		}
	}

	/**
	 * Ensure the booking assets load in the Elementor editor preview so the
	 * widgets render with their styling while editing.
	 *
	 * @return void
	 */
	public function enqueue_preview_assets() {
		if ( isset( wp_booking_system_luca()->frontend ) ) {
			wp_booking_system_luca()->frontend->enqueue_assets();
		}
	}
}
