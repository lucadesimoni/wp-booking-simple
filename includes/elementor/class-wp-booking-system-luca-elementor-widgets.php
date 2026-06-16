<?php
/**
 * Elementor widget classes for WP booking Luca.
 *
 * This file is only required from within the
 * `elementor/widgets/register` hook, so \Elementor\Widget_Base is guaranteed
 * to exist when these classes are declared.
 *
 * @package WP_Booking_System_Luca
 * @since 1.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Booking Form Elementor widget.
 */
class WP_Booking_System_Luca_Elementor_Form_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wpbsl-booking-form';
	}

	/**
	 * Widget title shown in the panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Booking Form', 'wp-booking-system-luca' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * Panel category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'wp-booking-luca' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'booking', 'reservation', 'form', 'chalet', 'calendar' );
	}

	/**
	 * Register the widget's controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'wpbsl_content',
			array(
				'label' => __( 'Booking Form', 'wp-booking-system-luca' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'wpbsl_title',
			array(
				'label'   => __( 'Title', 'wp-booking-system-luca' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Book Your Stay', 'wp-booking-system-luca' ),
			)
		);

		$this->add_control(
			'wpbsl_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'Pricing, fields and booking rules are configured under WP booking Luca → Settings.', 'wp-booking-system-luca' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$title    = isset( $settings['wpbsl_title'] ) ? $settings['wpbsl_title'] : '';

		// Output is pre-built, escaped markup from the frontend renderer.
		echo wp_booking_system_luca()->frontend->render_booking_form( array( 'title' => $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Booking Calendar Elementor widget.
 */
class WP_Booking_System_Luca_Elementor_Calendar_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wpbsl-booking-calendar';
	}

	/**
	 * Widget title shown in the panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Booking Calendar', 'wp-booking-system-luca' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}

	/**
	 * Panel category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'wp-booking-luca' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'booking', 'calendar', 'availability', 'chalet' );
	}

	/**
	 * Register the widget's controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'wpbsl_content',
			array(
				'label' => __( 'Booking Calendar', 'wp-booking-system-luca' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'wpbsl_title',
			array(
				'label'   => __( 'Title', 'wp-booking-system-luca' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Booking Calendar', 'wp-booking-system-luca' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$title    = isset( $settings['wpbsl_title'] ) ? $settings['wpbsl_title'] : '';

		// Output is pre-built, escaped markup from the frontend renderer.
		echo wp_booking_system_luca()->frontend->render_booking_calendar( array( 'title' => $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
