<?php
/**
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 * @package WP_Booking_System_Luca/Uninstall
 */

// If plugin is not being uninstalled, exit (do nothing).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete database table.
$table_name = $wpdb->prefix . 'wpbsl_bookings';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

$history_table = $wpdb->prefix . 'wpbsl_booking_history';
$wpdb->query( "DROP TABLE IF EXISTS {$history_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

// Remove the auto-created pages.
foreach ( array( 'wpbsl_booking_page_id', 'wpbsl_manage_page_id' ) as $page_option ) {
	$page_id = (int) get_option( $page_option, 0 );
	if ( $page_id ) {
		wp_delete_post( $page_id, true );
	}
}

// Delete options.
delete_option( 'wpbsl_price_adult' );
delete_option( 'wpbsl_price_kid' );
delete_option( 'wpbsl_currency' );
delete_option( 'wpbsl_email_from' );
delete_option( 'wpbsl_email_from_name' );
delete_option( 'wpbsl_admin_notification_email' );
delete_option( 'wpbsl_chalet_capacity' );
delete_option( 'wpbsl_booking_page_id' );
delete_option( 'wpbsl_manage_page_id' );
delete_option( 'wpbsl_min_nights' );
delete_option( 'wpbsl_max_nights' );
delete_option( 'wpbsl_min_advance_days' );
delete_option( 'wpbsl_max_advance_days' );
delete_option( 'wpbsl_default_adults' );
delete_option( 'wpbsl_default_kids' );
delete_option( 'wpbsl_require_phone' );
delete_option( 'wpbsl_show_notes' );
delete_option( 'wpbsl_auto_confirm' );
delete_option( 'wpbsl_smtp_enabled' );
delete_option( 'wpbsl_smtp_host' );
delete_option( 'wpbsl_smtp_port' );
delete_option( 'wpbsl_smtp_encryption' );
delete_option( 'wpbsl_smtp_auth' );
delete_option( 'wpbsl_smtp_username' );
delete_option( 'wpbsl_smtp_password' );
delete_option( 'wpbsl_email_confirmation_subject' );
delete_option( 'wpbsl_email_confirmation_body' );
delete_option( 'wpbsl_email_cancellation_subject' );
delete_option( 'wpbsl_email_cancellation_body' );
delete_option( 'wpbsl_email_admin_subject' );
delete_option( 'wpbsl_email_admin_body' );
delete_option( 'wpbsl_email_confirmation_blocks' );
delete_option( 'wpbsl_email_cancellation_blocks' );
delete_option( 'wpbsl_email_admin_blocks' );
delete_option( 'wpbsl_show_owner' );
delete_option( 'wpbsl_owners' );
delete_option( 'wpbsl_show_visitors' );
delete_option( 'wpbsl_db_version' );

// Clear any cached data.
wp_cache_flush();
