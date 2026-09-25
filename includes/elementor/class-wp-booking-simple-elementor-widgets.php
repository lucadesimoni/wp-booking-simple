<?php
/**
 * Elementor widget classes for WP Booking Simple.
 *
 * This file is only required from within the
 * `elementor/widgets/register` hook, so \Elementor\Widget_Base is guaranteed
 * to exist when these classes are declared.
 *
 * @package WP_Booking_Simple
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
class WP_Booking_Simple_Elementor_Form_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wpbs-booking-form';
	}

	/**
	 * Widget title shown in the panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Booking Form', 'wp-booking-simple' );
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
		return array( 'wp-booking-simple' );
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
				'label' => __( 'Booking Form', 'wp-booking-simple' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'wpbsl_title',
			array(
				'label'   => __( 'Title', 'wp-booking-simple' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Book Your Stay', 'wp-booking-simple' ),
			)
		);

		$this->add_control(
			'wpbsl_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'Pricing, fields and booking rules are configured under WP Booking Simple → Settings.', 'wp-booking-simple' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();

		// Style.
		$this->start_controls_section(
			'wpbsl_style',
			array(
				'label' => __( 'Style', 'wp-booking-simple' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'wpbsl_accent',
			array(
				'label'     => __( 'Accent color', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpbs-price-value'                  => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpbs-price-summary'               => 'border-left-color: {{VALUE}};',
					'{{WRAPPER}} .wpbs-booking-form input:focus'    => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .wpbs-booking-form select:focus'   => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .wpbs-booking-form textarea:focus' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_button_bg',
			array(
				'label'     => __( 'Button background', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpbs-booking-form .wpbs-submit-button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_button_color',
			array(
				'label'     => __( 'Button text color', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpbs-booking-form .wpbs-submit-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_button_hover_bg',
			array(
				'label'     => __( 'Button hover background', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpbs-booking-form .wpbs-submit-button:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_button_font_size',
			array(
				'label'      => __( 'Button font size', 'wp-booking-simple' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 10, 'max' => 40 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wpbs-booking-form .wpbs-submit-button' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_button_radius',
			array(
				'label'      => __( 'Button corner radius', 'wp-booking-simple' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 40 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wpbs-booking-form .wpbs-submit-button' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_title_color',
			array(
				'label'     => __( 'Title color', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .wpbs-form-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'wpbsl_title_typography',
				'label'    => __( 'Title typography', 'wp-booking-simple' ),
				'selector' => '{{WRAPPER}} .wpbs-form-title',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'wpbsl_button_typography',
				'label'    => __( 'Button typography', 'wp-booking-simple' ),
				'selector' => '{{WRAPPER}} .wpbs-booking-form .wpbs-submit-button',
			)
		);

		$this->add_control(
			'wpbsl_form_background',
			array(
				'label'     => __( 'Form background', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .wpbs-booking-form-wrapper' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'wpbsl_form_padding',
			array(
				'label'      => __( 'Form padding', 'wp-booking-simple' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wpbs-booking-form-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'wpbsl_form_shadow',
				'label'    => __( 'Form shadow', 'wp-booking-simple' ),
				'selector' => '{{WRAPPER}} .wpbs-booking-form-wrapper',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Load the booking styles and scripts wherever the widget is used,
	 * including Elementor popups, templates and the editor preview.
	 *
	 * @return array
	 */
	public function get_style_depends(): array {
		return array( 'wp-booking-simple-frontend' );
	}

	/**
	 * Scripts the widget needs.
	 *
	 * @return array
	 */
	public function get_script_depends(): array {
		return array( 'wp-booking-simple-frontend' );
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
		echo wp_booking_simple()->frontend->render_booking_form( array( 'title' => $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Booking Calendar Elementor widget.
 */
class WP_Booking_Simple_Elementor_Calendar_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wpbs-booking-calendar';
	}

	/**
	 * Widget title shown in the panel.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Booking Calendar', 'wp-booking-simple' );
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
		return array( 'wp-booking-simple' );
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
				'label' => __( 'Booking Calendar', 'wp-booking-simple' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'wpbsl_title',
			array(
				'label'   => __( 'Title', 'wp-booking-simple' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Booking Calendar', 'wp-booking-simple' ),
			)
		);

		$this->end_controls_section();

		// Style.
		$this->start_controls_section(
			'wpbsl_style',
			array(
				'label' => __( 'Style', 'wp-booking-simple' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'wpbsl_accent',
			array(
				'label'     => __( 'Accent color', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpbs-calendar-shortcode .fc-button'                            => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
					'{{WRAPPER}} .wpbs-calendar-shortcode .fc-button:hover'                      => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
					'{{WRAPPER}} .wpbs-calendar-shortcode .fc-col-header-cell-cushion'           => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .wpbs-calendar-shortcode .fc-day-today .fc-daygrid-day-number'  => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .wpbs-calendar-shortcode .wpbs-selected-edge'                   => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'wpbsl_booked_color',
			array(
				'label'     => __( 'Booked color', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpbs-calendar-shortcode .wpbs-unavailable-date' => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .wpbs-legend-booked'                             => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_cell_height',
			array(
				'label'      => __( 'Day cell height', 'wp-booking-simple' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 32, 'max' => 120 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wpbs-calendar-shortcode .fc-daygrid-day-frame' => 'min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'wpbsl_title_color',
			array(
				'label'     => __( 'Title color', 'wp-booking-simple' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .wpbs-calendar-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'wpbsl_title_typography',
				'label'    => __( 'Title typography', 'wp-booking-simple' ),
				'selector' => '{{WRAPPER}} .wpbs-calendar-title',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Load the booking styles and scripts wherever the widget is used,
	 * including Elementor popups, templates and the editor preview.
	 *
	 * @return array
	 */
	public function get_style_depends(): array {
		return array( 'wp-booking-simple-frontend' );
	}

	/**
	 * Scripts the widget needs.
	 *
	 * @return array
	 */
	public function get_script_depends(): array {
		return array( 'wp-booking-simple-frontend' );
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
		echo wp_booking_simple()->frontend->render_booking_calendar( array( 'title' => $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
