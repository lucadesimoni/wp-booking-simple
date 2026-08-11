<?php
/**
 * Plugin Name: WP Booking Simple
 * Version: 1.22.1
 * Plugin URI: https://famiglia-desimoni.ch/
 * Description: A simple and modern booking system for WordPress with calendar management, email notifications, and price calculations.
 * Author: Famiglia De Simoni
 * Author URI: https://famiglia-desimoni.ch/
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.2
 *
 * Text Domain: wp-booking-simple
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Famiglia De Simoni
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WP_BOOKING_SIMPLE_VERSION', '1.22.1' );
define( 'WP_BOOKING_SIMPLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_BOOKING_SIMPLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin class files.
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-helpers.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-stats.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-database.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-admin.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-frontend.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-ajax.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-email.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-widget.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-block.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-elementor.php';

/**
 * Returns the main instance of WP_Booking_Simple to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WP_Booking_Simple
 */
function wp_booking_simple() {
	$instance = WP_Booking_Simple::instance( __FILE__, WP_BOOKING_SIMPLE_VERSION );

	return $instance;
}

// Register activation hook.
register_activation_hook( __FILE__, array( 'WP_Booking_Simple', 'activate' ) );

// Initialize the plugin.
$wp_booking_simple = wp_booking_simple();

// Register deactivation hook after plugin is initialized.
register_deactivation_hook( __FILE__, array( $wp_booking_simple, 'deactivate' ) );
