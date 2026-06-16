<?php
/**
 * Plugin Name: WP booking Luca
 * Version: 1.15.0
 * Plugin URI: https://famiglia-desimoni.ch/
 * Description: A simple and modern booking system for WordPress with calendar management, email notifications, and price calculations.
 * Author: Famiglia De Simoni
 * Author URI: https://famiglia-desimoni.ch/
 * Requires at least: 5.0
 * Tested up to: 6.9
 *
 * Text Domain: wp-booking-system-luca
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
define( 'WP_BOOKING_SYSTEM_LUCA_VERSION', '1.15.0' );
define( 'WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin class files.
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-helpers.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-stats.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-database.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-admin.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-frontend.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-ajax.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-email.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-widget.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-block.php';
require_once WP_BOOKING_SYSTEM_LUCA_PLUGIN_DIR . 'includes/class-wp-booking-system-luca-elementor.php';

/**
 * Returns the main instance of WP_Booking_System_Luca to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WP_Booking_System_Luca
 */
function wp_booking_system_luca() {
	$instance = WP_Booking_System_Luca::instance( __FILE__, WP_BOOKING_SYSTEM_LUCA_VERSION );

	return $instance;
}

// Register activation hook.
register_activation_hook( __FILE__, array( 'WP_Booking_System_Luca', 'activate' ) );

// Initialize the plugin.
$wp_booking_system_luca = wp_booking_system_luca();

// Register deactivation hook after plugin is initialized.
register_deactivation_hook( __FILE__, array( $wp_booking_system_luca, 'deactivate' ) );
