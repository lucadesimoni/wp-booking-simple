<?php
/**
 * Database class for handling booking data
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Database Class
 */
class WP_Booking_System_Luca_Database {

	/**
	 * Table name for bookings.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'wpbsl_bookings';
	}

	/**
	 * Create database tables.
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			booking_token varchar(64) NOT NULL,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(255) NOT NULL,
			phone varchar(50) DEFAULT NULL,
			check_in date NOT NULL,
			check_out date NOT NULL,
			adults int(11) NOT NULL DEFAULT 1,
			kids int(11) NOT NULL DEFAULT 0,
			owner varchar(100) DEFAULT NULL,
			visitors_welcome tinyint(1) NOT NULL DEFAULT 0,
			total_price decimal(10,2) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY booking_token (booking_token),
			KEY check_in (check_in),
			KEY check_out (check_out),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$this->ensure_columns();
	}

	/**
	 * Add columns introduced after the initial schema if they are missing.
	 *
	 * dbDelta does not reliably alter existing tables on every database
	 * backend (notably SQLite), so we add the newer columns explicitly.
	 */
	private function ensure_columns() {
		global $wpdb;

		$existing = $wpdb->get_col( "DESCRIBE {$this->table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		if ( empty( $existing ) ) {
			return;
		}

		$columns = array(
			'owner'            => 'varchar(100) DEFAULT NULL',
			'visitors_welcome' => "tinyint(1) NOT NULL DEFAULT 0",
		);

		foreach ( $columns as $name => $definition ) {
			if ( ! in_array( $name, $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table_name} ADD COLUMN {$name} {$definition}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			}
		}
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Insert a new booking.
	 *
	 * @param array $data Booking data.
	 * @return int|false Booking ID on success, false on failure.
	 */
	public function insert_booking( $data ) {
		global $wpdb;

		$defaults = array(
			'booking_token' => $this->generate_token(),
			'first_name'     => '',
			'last_name'      => '',
			'email'          => '',
			'phone'          => '',
			'check_in'       => '',
			'check_out'      => '',
			'adults'         => 1,
			'kids'           => 0,
			'owner'          => '',
			'visitors_welcome' => 0,
			'total_price'    => 0,
			'status'         => 'pending',
			'notes'          => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// Keep the value order aligned with the format specifiers below.
		$data = array(
			'booking_token'    => $data['booking_token'],
			'first_name'       => $data['first_name'],
			'last_name'        => $data['last_name'],
			'email'            => $data['email'],
			'phone'            => $data['phone'],
			'check_in'         => $data['check_in'],
			'check_out'        => $data['check_out'],
			'adults'           => $data['adults'],
			'kids'             => $data['kids'],
			'owner'            => $data['owner'],
			'visitors_welcome' => $data['visitors_welcome'],
			'total_price'      => $data['total_price'],
			'status'           => $data['status'],
			'notes'            => $data['notes'],
		);

		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%f', '%s', '%s' )
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get booking by ID.
	 *
	 * @param int $id Booking ID.
	 * @return object|null
	 */
	public function get_booking( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
	}

	/**
	 * Get booking by token.
	 *
	 * @param string $token Booking token.
	 * @return object|null
	 */
	public function get_booking_by_token( $token ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE booking_token = %s", $token ) );
	}

	/**
	 * Update booking.
	 *
	 * @param int   $id Booking ID.
	 * @param array $data Booking data.
	 * @return bool
	 */
	public function update_booking( $id, $data ) {
		global $wpdb;

		// Define format for each field.
		$formats = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, array( 'adults', 'kids' ), true ) ) {
				$formats[ $key ] = '%d';
			} elseif ( in_array( $key, array( 'total_price' ), true ) ) {
				$formats[ $key ] = '%f';
			} else {
				$formats[ $key ] = '%s';
			}
		}

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete booking.
	 *
	 * @param int $id Booking ID.
	 * @return bool
	 */
	public function delete_booking( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
	}

	/**
	 * Get all bookings.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_bookings( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => '',
			'date_from' => '',
			'date_to'   => '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
			'limit'     => -1,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );

		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( "status = %s", $args['status'] );
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[] = $wpdb->prepare( "check_in >= %s", $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[] = $wpdb->prepare( "check_out <= %s", $args['date_to'] );
		}

		$where_clause = implode( ' AND ', $where );
		$orderby      = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$limit        = $args['limit'] > 0 ? $wpdb->prepare( "LIMIT %d", $args['limit'] ) : '';

		$sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} {$limit}";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get bookings for calendar view.
	 *
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date End date (Y-m-d).
	 * @return array
	 */
	public function get_bookings_for_calendar( $start_date, $end_date ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			WHERE status != 'cancelled' 
			AND (
				(check_in <= %s AND check_out >= %s) OR
				(check_in BETWEEN %s AND %s) OR
				(check_out BETWEEN %s AND %s)
			)
			ORDER BY check_in ASC",
			$end_date,
			$start_date,
			$start_date,
			$end_date,
			$start_date,
			$end_date
		);

		return $wpdb->get_results( $sql );
	}

	/**
	 * Check if dates are available.
	 *
	 * @param string $check_in Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @param int    $exclude_id Booking ID to exclude.
	 * @return bool
	 */
	public function is_available( $check_in, $check_out, $exclude_id = 0 ) {
		global $wpdb;

		$exclude = $exclude_id > 0 ? $wpdb->prepare( "AND id != %d", $exclude_id ) : '';

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name} 
			WHERE status != 'cancelled' 
			AND (
				(check_in < %s AND check_out > %s) OR
				(check_in < %s AND check_out > %s) OR
				(check_in >= %s AND check_out <= %s)
			)
			{$exclude}",
			$check_out,
			$check_in,
			$check_in,
			$check_out,
			$check_in,
			$check_out
		);

		$count = $wpdb->get_var( $sql );

		return $count == 0;
	}

	/**
	 * Generate unique booking token.
	 *
	 * @return string
	 */
	private function generate_token() {
		return bin2hex( random_bytes( 32 ) );
	}
}

