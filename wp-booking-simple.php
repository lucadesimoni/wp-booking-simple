<?php
/**
 * Plugin Name: WP Booking Simple
 * Version: 1.25.0
 * Plugin URI: https://github.com/lucadesimoni/wp-booking-simple
 * Description: A simple and modern booking system for WordPress with calendar management, email notifications, and price calculations.
 * Author: Outthinkx Club
 * Author URI: https://github.com/lucadesimoni
 * Requires at least: 6.5
 * Tested up to: 7.1
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: wp-booking-simple
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Outthinkx Club
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Never break the site: if the plugin cannot run safely here, show an admin
 * notice and stop instead of causing a fatal error.
 */
$wpbsl_load_problem = '';
if ( defined( 'WP_BOOKING_SIMPLE_VERSION' ) || class_exists( 'WP_Booking_Simple', false ) ) {
	// Two copies active (e.g. an old folder left behind after an update):
	// loading this one too would redeclare every class.
	$wpbsl_load_problem = 'duplicate';
} elseif ( version_compare( PHP_VERSION, '7.2', '<' ) ) {
	$wpbsl_load_problem = 'php';
} elseif ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '6.5', '<' ) ) {
	$wpbsl_load_problem = 'wp';
}

if ( '' !== $wpbsl_load_problem ) {
	add_action(
		'admin_notices',
		function () use ( $wpbsl_load_problem ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$messages = array(
				'duplicate' => __( 'WP Booking Simple is active twice (two copies of the plugin are installed). Only the first copy is running; deactivate and delete the other one.', 'wp-booking-simple' ),
				/* translators: %s: PHP version. */
				'php'       => sprintf( __( 'WP Booking Simple needs PHP 7.2 or later (this site runs %s) and has not been loaded.', 'wp-booking-simple' ), PHP_VERSION ),
				/* translators: %s: WordPress version. */
				'wp'        => sprintf( __( 'WP Booking Simple needs WordPress 6.5 or later (this site runs %s) and has not been loaded.', 'wp-booking-simple' ), $GLOBALS['wp_version'] ),
			);
			echo '<div class="notice notice-error"><p>' . esc_html( $messages[ $wpbsl_load_problem ] ) . '</p></div>';
		}
	);
	return;
}

// Define plugin constants.
define( 'WP_BOOKING_SIMPLE_VERSION', '1.25.0' );
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
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-theme.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-block.php';
require_once WP_BOOKING_SIMPLE_PLUGIN_DIR . 'includes/class-wp-booking-simple-elementor.php';

if ( ! function_exists( 'wp_booking_simple' ) ) {
	/**
	 * Returns the main instance of WP_Booking_Simple to prevent the need to use globals.
	 *
	 * Declared conditionally so a second copy of the plugin cannot redeclare it.
	 *
	 * @since  1.0.0
	 * @return object WP_Booking_Simple
	 */
	function wp_booking_simple() {
		$instance = WP_Booking_Simple::instance( __FILE__, WP_BOOKING_SIMPLE_VERSION );

		return $instance;
	}
}

// Register activation hook.
register_activation_hook( __FILE__, array( 'WP_Booking_Simple', 'activate' ) );

// Initialize the plugin. A failure while setting up is reported as an admin
// notice instead of taking the site down.
try {
	$wp_booking_simple = wp_booking_simple();
} catch ( \Throwable $wpbsl_boot_error ) {
	WP_Booking_Simple_Helpers::report_error( 'startup', $wpbsl_boot_error );
	add_action(
		'admin_notices',
		function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'WP Booking Simple could not start and has been paused for this request; the rest of the site is unaffected. With WP_DEBUG enabled, the PHP error log shows why.', 'wp-booking-simple' ) . '</p></div>';
			}
		}
	);
	return;
}

// Register deactivation hook after plugin is initialized.
register_deactivation_hook( __FILE__, array( $wp_booking_simple, 'deactivate' ) );
