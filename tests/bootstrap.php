<?php
/**
 * PHPUnit bootstrap for the WordPress integration test suite.
 *
 * Expects the WordPress test library. The quickest way to provide it is
 * `wp-env` (https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
 * or the `install-wp-tests.sh` script. Set WP_TESTS_DIR to its location.
 *
 * @package WP_Booking_Simple
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$_phpunit_polyfills = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL; // phpcs:ignore
	echo 'Install the WordPress test suite (see tests/README.md) and set WP_TESTS_DIR.' . PHP_EOL; // phpcs:ignore
	exit( 1 );
}

require_once "{$_tests_dir}/includes/functions.php";

/**
 * Load the plugin under test.
 */
function _manually_load_wpbsl_plugin() {
	require dirname( __DIR__ ) . '/wp-booking-simple.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_wpbsl_plugin' );

require "{$_tests_dir}/includes/bootstrap.php";
