<?php
/**
 * AJAX class for handling frontend and admin requests
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Ajax Class
 */
class WP_Booking_System_Luca_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Frontend AJAX.
		add_action( 'wp_ajax_wpbsl_check_availability', array( $this, 'check_availability' ) );
		add_action( 'wp_ajax_nopriv_wpbsl_check_availability', array( $this, 'check_availability' ) );
		add_action( 'wp_ajax_wpbsl_calculate_price', array( $this, 'calculate_price' ) );
		add_action( 'wp_ajax_nopriv_wpbsl_calculate_price', array( $this, 'calculate_price' ) );
		add_action( 'wp_ajax_wpbsl_submit_booking', array( $this, 'submit_booking' ) );
		add_action( 'wp_ajax_nopriv_wpbsl_submit_booking', array( $this, 'submit_booking' ) );
		add_action( 'wp_ajax_wpbsl_cancel_booking', array( $this, 'cancel_booking' ) );
		add_action( 'wp_ajax_nopriv_wpbsl_cancel_booking', array( $this, 'cancel_booking' ) );

		// Admin AJAX.
		add_action( 'wp_ajax_wpbsl_get_bookings', array( $this, 'get_bookings' ) );
		add_action( 'wp_ajax_wpbsl_get_booking', array( $this, 'get_booking' ) );
		add_action( 'wp_ajax_wpbsl_delete_booking', array( $this, 'delete_booking' ) );
		add_action( 'wp_ajax_wpbsl_update_status', array( $this, 'update_status' ) );
		add_action( 'wp_ajax_wpbsl_update_booking', array( $this, 'update_booking' ) );
		add_action( 'wp_ajax_wpbsl_send_test_email', array( $this, 'send_test_email' ) );

		// Calendar availability (frontend).
		add_action( 'wp_ajax_wpbsl_get_calendar_availability', array( $this, 'get_calendar_availability' ) );
		add_action( 'wp_ajax_nopriv_wpbsl_get_calendar_availability', array( $this, 'get_calendar_availability' ) );
	}

	/**
	 * Check availability.
	 */
	public function check_availability() {
		check_ajax_referer( 'wp-booking-system-luca-frontend', 'nonce' );

		$check_in  = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';

		if ( empty( $check_in ) || empty( $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select both dates.', 'wp-booking-system-luca' ) ) );
		}

		// Validate date format and range.
		if ( ! WP_Booking_System_Luca_Helpers::is_valid_range( $check_in, $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a valid date range (check-out after check-in).', 'wp-booking-system-luca' ) ) );
		}

		$available = wp_booking_system_luca()->database->is_available( $check_in, $check_out );

		wp_send_json_success( array( 'available' => $available ) );
	}

	/**
	 * Calculate price.
	 */
	public function calculate_price() {
		check_ajax_referer( 'wp-booking-system-luca-frontend', 'nonce' );

		$check_in  = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';
		$adults    = isset( $_POST['adults'] ) ? absint( $_POST['adults'] ) : 1;
		$kids      = isset( $_POST['kids'] ) ? absint( $_POST['kids'] ) : 0;

		if ( empty( $check_in ) || empty( $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select both dates.', 'wp-booking-system-luca' ) ) );
		}

		// Validate date format and range.
		if ( ! WP_Booking_System_Luca_Helpers::is_valid_range( $check_in, $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a valid date range (check-out after check-in).', 'wp-booking-system-luca' ) ) );
		}

		// Validate capacity.
		$max_capacity = absint( get_option( 'wpbsl_chalet_capacity', 10 ) );

		if ( WP_Booking_System_Luca_Helpers::exceeds_capacity( $adults, $kids, $max_capacity ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: Maximum capacity */
						__( 'The chalet can accommodate a maximum of %d guests. Please reduce the number of guests.', 'wp-booking-system-luca' ),
						$max_capacity
					),
				)
			);
		}

		// Enforce booking rules (stay length and booking window).
		$rule_error = $this->validate_booking_rules( $check_in, $check_out );
		if ( '' !== $rule_error ) {
			wp_send_json_error( array( 'message' => $rule_error ) );
		}

		$price = $this->calculate_booking_price( $check_in, $check_out, $adults, $kids );
		$currency = get_option( 'wpbsl_currency', 'CHF' );

		wp_send_json_success(
			array(
				'price'    => $price,
				'currency' => $currency,
				'formatted' => number_format_i18n( $price, 2 ) . ' ' . esc_html( $currency ),
			)
		);
	}

	/**
	 * Submit booking.
	 */
	public function submit_booking() {
		check_ajax_referer( 'wp-booking-system-luca-frontend', 'nonce' );

		$data = array(
			'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'email'      => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'check_in'   => isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '',
			'check_out'  => isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '',
			'adults'     => isset( $_POST['adults'] ) ? absint( $_POST['adults'] ) : 1,
			'kids'       => isset( $_POST['kids'] ) ? absint( $_POST['kids'] ) : 0,
			'notes'      => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
		);

		// Optional extra fields. Owner must be one of the configured names.
		$owner          = isset( $_POST['owner'] ) ? sanitize_text_field( wp_unslash( $_POST['owner'] ) ) : '';
		$allowed_owners = WP_Booking_System_Luca_Helpers::parse_owners( get_option( 'wpbsl_owners', '' ) );
		$data['owner']  = in_array( $owner, $allowed_owners, true ) ? $owner : '';
		$data['visitors_welcome'] = ( isset( $_POST['visitors_welcome'] ) && '1' === (string) $_POST['visitors_welcome'] ) ? 1 : 0;

		// Validate required fields.
		if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['email'] ) || empty( $data['check_in'] ) || empty( $data['check_out'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'wp-booking-system-luca' ) ) );
		}

		// Validate email format.
		if ( ! is_email( $data['email'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-booking-system-luca' ) ) );
		}

		// Validate date format and range.
		if ( ! WP_Booking_System_Luca_Helpers::is_valid_range( $data['check_in'], $data['check_out'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Check-out date must be after check-in date.', 'wp-booking-system-luca' ) ) );
		}

		// Validate guest counts.
		if ( $data['adults'] < 1 ) {
			wp_send_json_error( array( 'message' => __( 'At least one adult is required.', 'wp-booking-system-luca' ) ) );
		}

		// Validate capacity.
		$max_capacity = absint( get_option( 'wpbsl_chalet_capacity', 10 ) );

		if ( WP_Booking_System_Luca_Helpers::exceeds_capacity( $data['adults'], $data['kids'], $max_capacity ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: Maximum capacity */
						__( 'The chalet can accommodate a maximum of %d guests. Please reduce the number of guests.', 'wp-booking-system-luca' ),
						$max_capacity
					),
				)
			);
		}

		// Require a phone number when configured to do so.
		if ( (int) get_option( 'wpbsl_require_phone', 0 ) && empty( $data['phone'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide a phone number.', 'wp-booking-system-luca' ) ) );
		}

		// Enforce booking rules (stay length and booking window).
		$rule_error = $this->validate_booking_rules( $data['check_in'], $data['check_out'] );
		if ( '' !== $rule_error ) {
			wp_send_json_error( array( 'message' => $rule_error ) );
		}

		// Check availability.
		if ( ! wp_booking_system_luca()->database->is_available( $data['check_in'], $data['check_out'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Selected dates are not available.', 'wp-booking-system-luca' ) ) );
		}

		// Calculate price.
		$data['total_price'] = $this->calculate_booking_price( $data['check_in'], $data['check_out'], $data['adults'], $data['kids'] );

		// Auto-confirm new bookings when enabled.
		$data['status'] = (int) get_option( 'wpbsl_auto_confirm', 0 ) ? 'confirmed' : 'pending';

		// Insert booking.
		$booking_id = wp_booking_system_luca()->database->insert_booking( $data );

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create booking. Please try again.', 'wp-booking-system-luca' ) ) );
		}

		// Get booking with token.
		$booking = wp_booking_system_luca()->database->get_booking( $booking_id );

		// Send email.
		wp_booking_system_luca()->email->send_booking_confirmation( $booking );

		wp_send_json_success(
			array(
				'message' => __( 'Booking submitted successfully! Check your email for confirmation.', 'wp-booking-system-luca' ),
				'booking_id' => $booking_id,
			)
		);
	}

	/**
	 * Cancel booking.
	 */
	public function cancel_booking() {
		check_ajax_referer( 'wp-booking-system-luca-frontend', 'nonce' );

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( empty( $token ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking token.', 'wp-booking-system-luca' ) ) );
		}

		// Validate token format (64 character hex string).
		if ( ! WP_Booking_System_Luca_Helpers::is_valid_token( $token ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking token format.', 'wp-booking-system-luca' ) ) );
		}

		$booking = wp_booking_system_luca()->database->get_booking_by_token( $token );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found.', 'wp-booking-system-luca' ) ) );
		}

		// Update status to cancelled.
		$result = wp_booking_system_luca()->database->update_booking( $booking->id, array( 'status' => 'cancelled' ) );

		if ( $result ) {
			if ( 'cancelled' !== $booking->status ) {
				wp_booking_system_luca()->database->insert_history(
					$booking->id,
					array( 'status' => array( 'from' => (string) $booking->status, 'to' => 'cancelled' ) ),
					__( 'Guest', 'wp-booking-system-luca' )
				);
			}
			// Send cancellation email.
			wp_booking_system_luca()->email->send_booking_cancellation( $booking );
			wp_send_json_success( array( 'message' => __( 'Booking cancelled successfully.', 'wp-booking-system-luca' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to cancel booking.', 'wp-booking-system-luca' ) ) );
		}
	}

	/**
	 * Get bookings for calendar (admin).
	 */
	public function get_bookings() {
		check_ajax_referer( 'wp-booking-system-luca-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-booking-system-luca' ) ) );
		}

		$start = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : '';
		$end   = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : '';

		$bookings = wp_booking_system_luca()->database->get_bookings_for_calendar( $start, $end );

		$events = array();
		foreach ( $bookings as $booking ) {
			$guests = (int) $booking->adults + (int) $booking->kids;
			$name   = trim( $booking->first_name . ' ' . $booking->last_name );

			$events[] = array(
				'id'            => $booking->id,
				'title'         => $name . ' (' . $guests . ')',
				'start'         => $booking->check_in,
				'end'           => date( 'Y-m-d', strtotime( $booking->check_out . ' +1 day' ) ),
				'color'         => $this->get_status_color( $booking->status ),
				'extendedProps' => array(
					'status'  => ucfirst( $booking->status ),
					'guests'  => $guests,
					'owner'   => isset( $booking->owner ) ? $booking->owner : '',
					'checkIn' => $booking->check_in,
					'checkOut' => $booking->check_out,
				),
			);
		}

		wp_send_json_success( $events );
	}

	/**
	 * Get single booking (admin).
	 */
	public function get_booking() {
		check_ajax_referer( 'wp-booking-system-luca-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-booking-system-luca' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking ID.', 'wp-booking-system-luca' ) ) );
		}

		$booking = wp_booking_system_luca()->database->get_booking( $id );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found.', 'wp-booking-system-luca' ) ) );
		}

		wp_send_json_success(
			array(
				'booking' => $booking,
				'history' => $this->format_history( wp_booking_system_luca()->database->get_history( $id ) ),
			)
		);
	}

	/**
	 * Update an entire booking from the admin editor, recording the change
	 * history. All editable fields are tracked.
	 */
	public function update_booking() {
		check_ajax_referer( 'wp-booking-system-luca-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-booking-system-luca' ) ) );
		}

		$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$booking = $id ? wp_booking_system_luca()->database->get_booking( $id ) : null;

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found.', 'wp-booking-system-luca' ) ) );
		}

		$post = wp_unslash( $_POST );

		$check_in  = isset( $post['check_in'] ) ? sanitize_text_field( $post['check_in'] ) : $booking->check_in;
		$check_out = isset( $post['check_out'] ) ? sanitize_text_field( $post['check_out'] ) : $booking->check_out;

		if ( ! WP_Booking_System_Luca_Helpers::is_valid_range( $check_in, $check_out ) ) {
			wp_send_json_error( array( 'message' => __( 'Check-out must be after check-in.', 'wp-booking-system-luca' ) ) );
		}

		// Admins may override booking rules, but never double-book the chalet.
		if ( ! wp_booking_system_luca()->database->is_available( $check_in, $check_out, $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Those dates overlap another booking.', 'wp-booking-system-luca' ) ) );
		}

		$email = isset( $post['email'] ) ? sanitize_email( $post['email'] ) : $booking->email;
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-booking-system-luca' ) ) );
		}

		$status         = isset( $post['status'] ) ? sanitize_text_field( $post['status'] ) : $booking->status;
		$payment_status = isset( $post['payment_status'] ) ? sanitize_text_field( $post['payment_status'] ) : 'unpaid';
		$payment_method = isset( $post['payment_method'] ) ? sanitize_text_field( $post['payment_method'] ) : '';
		$owner          = isset( $post['owner'] ) ? sanitize_text_field( $post['owner'] ) : '';
		$allowed_owners = WP_Booking_System_Luca_Helpers::parse_owners( get_option( 'wpbsl_owners', '' ) );

		$new = array(
			'first_name'       => isset( $post['first_name'] ) ? sanitize_text_field( $post['first_name'] ) : $booking->first_name,
			'last_name'        => isset( $post['last_name'] ) ? sanitize_text_field( $post['last_name'] ) : $booking->last_name,
			'email'            => $email,
			'phone'            => isset( $post['phone'] ) ? sanitize_text_field( $post['phone'] ) : $booking->phone,
			'check_in'         => $check_in,
			'check_out'        => $check_out,
			'adults'           => isset( $post['adults'] ) ? max( 1, absint( $post['adults'] ) ) : (int) $booking->adults,
			'kids'             => isset( $post['kids'] ) ? absint( $post['kids'] ) : (int) $booking->kids,
			'owner'            => in_array( $owner, $allowed_owners, true ) ? $owner : '',
			'visitors_welcome' => ( isset( $post['visitors_welcome'] ) && '1' === (string) $post['visitors_welcome'] ) ? 1 : 0,
			'total_price'      => isset( $post['total_price'] ) ? max( 0, floatval( $post['total_price'] ) ) : (float) $booking->total_price,
			'status'           => WP_Booking_System_Luca_Helpers::is_valid_status( $status ) ? $status : $booking->status,
			'payment_status'   => array_key_exists( $payment_status, WP_Booking_System_Luca_Helpers::payment_statuses() ) ? $payment_status : 'unpaid',
			'payment_method'   => array_key_exists( $payment_method, WP_Booking_System_Luca_Helpers::payment_methods() ) ? $payment_method : '',
			'amount_paid'      => isset( $post['amount_paid'] ) ? max( 0, floatval( $post['amount_paid'] ) ) : (float) $booking->amount_paid,
			'notes'            => isset( $post['notes'] ) ? sanitize_textarea_field( $post['notes'] ) : $booking->notes,
		);

		$changes = WP_Booking_System_Luca_Helpers::compute_changes( $booking, $new );

		wp_booking_system_luca()->database->update_booking( $id, $new );

		if ( ! empty( $changes ) ) {
			wp_booking_system_luca()->database->insert_history( $id, $changes, $this->current_actor() );
		}

		// Email the guest if an admin edit cancelled the booking.
		if ( 'cancelled' === $new['status'] && 'cancelled' !== $booking->status ) {
			wp_booking_system_luca()->email->send_booking_cancellation( wp_booking_system_luca()->database->get_booking( $id ) );
		}

		wp_send_json_success(
			array(
				'message' => empty( $changes ) ? __( 'No changes to save.', 'wp-booking-system-luca' ) : __( 'Booking updated.', 'wp-booking-system-luca' ),
				'booking' => wp_booking_system_luca()->database->get_booking( $id ),
				'history' => $this->format_history( wp_booking_system_luca()->database->get_history( $id ) ),
			)
		);
	}

	/**
	 * Current actor label for history entries.
	 *
	 * @return string
	 */
	private function current_actor() {
		$user = wp_get_current_user();
		return ( $user && $user->exists() ) ? $user->display_name : __( 'System', 'wp-booking-system-luca' );
	}

	/**
	 * Turn raw history rows into display-ready data for the editor.
	 *
	 * @param array $rows History rows.
	 * @return array
	 */
	private function format_history( $rows ) {
		$labels = WP_Booking_System_Luca_Helpers::tracked_fields();
		$out    = array();

		foreach ( (array) $rows as $row ) {
			$changes = json_decode( $row->changes, true );
			if ( ! is_array( $changes ) ) {
				continue;
			}

			$items = array();
			foreach ( $changes as $field => $pair ) {
				$items[] = array(
					'label' => isset( $labels[ $field ] ) ? $labels[ $field ] : $field,
					'from'  => $this->format_field_value( $field, isset( $pair['from'] ) ? $pair['from'] : '' ),
					'to'    => $this->format_field_value( $field, isset( $pair['to'] ) ? $pair['to'] : '' ),
				);
			}

			$out[] = array(
				'changed_at' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->changed_at ) ),
				'changed_by' => $row->changed_by ? $row->changed_by : __( 'System', 'wp-booking-system-luca' ),
				'items'      => $items,
			);
		}

		return $out;
	}

	/**
	 * Human-format a single field value for the history view.
	 *
	 * @param string $field Field name.
	 * @param string $value Raw value.
	 * @return string
	 */
	private function format_field_value( $field, $value ) {
		$currency = get_option( 'wpbsl_currency', 'CHF' );

		switch ( $field ) {
			case 'status':
				return ucfirst( (string) $value );
			case 'payment_status':
				$map = WP_Booking_System_Luca_Helpers::payment_statuses();
				return isset( $map[ $value ] ) ? $map[ $value ] : (string) $value;
			case 'payment_method':
				$map = WP_Booking_System_Luca_Helpers::payment_methods();
				return isset( $map[ $value ] ) ? $map[ $value ] : '—';
			case 'visitors_welcome':
				return $value ? __( 'Yes', 'wp-booking-system-luca' ) : __( 'No', 'wp-booking-system-luca' );
			case 'total_price':
			case 'amount_paid':
				return number_format( (float) $value, 2 ) . ' ' . $currency;
			case 'check_in':
			case 'check_out':
				return $value ? date_i18n( get_option( 'date_format' ), strtotime( $value ) ) : '—';
			default:
				return ( '' === (string) $value ) ? '—' : (string) $value;
		}
	}

	/**
	 * Delete booking (admin).
	 */
	public function delete_booking() {
		check_ajax_referer( 'wp-booking-system-luca-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-booking-system-luca' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking ID.', 'wp-booking-system-luca' ) ) );
		}

		$result = wp_booking_system_luca()->database->delete_booking( $id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Booking deleted successfully.', 'wp-booking-system-luca' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete booking.', 'wp-booking-system-luca' ) ) );
		}
	}

	/**
	 * Validate a requested stay against the configured booking rules.
	 *
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @return string Error message, or empty string when the stay is allowed.
	 */
	private function validate_booking_rules( $check_in, $check_out ) {
		$min_nights       = max( 1, absint( get_option( 'wpbsl_min_nights', 1 ) ) );
		$max_nights       = absint( get_option( 'wpbsl_max_nights', 0 ) );
		$min_advance_days = absint( get_option( 'wpbsl_min_advance_days', 0 ) );
		$max_advance_days = absint( get_option( 'wpbsl_max_advance_days', 0 ) );

		$nights = WP_Booking_System_Luca_Helpers::calculate_nights( $check_in, $check_out );

		if ( $nights < $min_nights ) {
			return sprintf(
				/* translators: %d: Minimum number of nights */
				_n( 'A minimum stay of %d night is required.', 'A minimum stay of %d nights is required.', $min_nights, 'wp-booking-system-luca' ),
				$min_nights
			);
		}

		if ( $max_nights > 0 && $nights > $max_nights ) {
			return sprintf(
				/* translators: %d: Maximum number of nights */
				_n( 'The maximum stay is %d night.', 'The maximum stay is %d nights.', $max_nights, 'wp-booking-system-luca' ),
				$max_nights
			);
		}

		if ( ! WP_Booking_System_Luca_Helpers::is_within_booking_window( $check_in, $min_advance_days, $max_advance_days ) ) {
			if ( $min_advance_days > 0 && WP_Booking_System_Luca_Helpers::days_until( $check_in ) < $min_advance_days ) {
				return sprintf(
					/* translators: %d: Minimum advance days */
					_n( 'Bookings must be made at least %d day in advance.', 'Bookings must be made at least %d days in advance.', $min_advance_days, 'wp-booking-system-luca' ),
					$min_advance_days
				);
			}

			return sprintf(
				/* translators: %d: Maximum advance days */
				__( 'Bookings can only be made up to %d days ahead.', 'wp-booking-system-luca' ),
				$max_advance_days
			);
		}

		return '';
	}

	/**
	 * Calculate booking price using the configured nightly rates.
	 *
	 * @param string $check_in Check-in date.
	 * @param string $check_out Check-out date.
	 * @param int    $adults Number of adults.
	 * @param int    $kids Number of kids.
	 * @return float
	 */
	private function calculate_booking_price( $check_in, $check_out, $adults, $kids ) {
		return WP_Booking_System_Luca_Helpers::calculate_price(
			$check_in,
			$check_out,
			$adults,
			$kids,
			floatval( get_option( 'wpbsl_price_adult', 50 ) ),
			floatval( get_option( 'wpbsl_price_kid', 25 ) )
		);
	}

	/**
	 * Update a booking status (admin).
	 */
	public function update_status() {
		check_ajax_referer( 'wp-booking-system-luca-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-booking-system-luca' ) ) );
		}

		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $id || ! WP_Booking_System_Luca_Helpers::is_valid_status( $status ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wp-booking-system-luca' ) ) );
		}

		$booking = wp_booking_system_luca()->database->get_booking( $id );

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => __( 'Booking not found.', 'wp-booking-system-luca' ) ) );
		}

		$result = wp_booking_system_luca()->database->update_booking( $id, array( 'status' => $status ) );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to update booking status.', 'wp-booking-system-luca' ) ) );
		}

		if ( $status !== $booking->status ) {
			wp_booking_system_luca()->database->insert_history(
				$id,
				array( 'status' => array( 'from' => (string) $booking->status, 'to' => $status ) ),
				$this->current_actor()
			);
		}

		// Notify the guest when their booking is cancelled by an admin.
		if ( 'cancelled' === $status && 'cancelled' !== $booking->status ) {
			wp_booking_system_luca()->email->send_booking_cancellation( $booking );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Booking status updated.', 'wp-booking-system-luca' ),
				'status'  => $status,
			)
		);
	}

	/**
	 * Send a test email so the admin can verify delivery (e.g. Gmail SMTP).
	 */
	public function send_test_email() {
		check_ajax_referer( 'wp-booking-system-luca-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'wp-booking-system-luca' ) ) );
		}

		$to = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : get_option( 'admin_email' );

		$result = wp_booking_system_luca()->email->send_test_email( $to );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		}

		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	/**
	 * Get calendar availability for frontend widget.
	 */
	public function get_calendar_availability() {
		check_ajax_referer( 'wp-booking-system-luca-frontend', 'nonce' );

		$start = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : '';
		$end   = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : '';

		if ( empty( $start ) || empty( $end ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date range.', 'wp-booking-system-luca' ) ) );
		}

		$bookings = wp_booking_system_luca()->database->get_bookings_for_calendar( $start, $end );

		$events = array();
		foreach ( $bookings as $booking ) {
			$events[] = array(
				'id'    => $booking->id,
				'title' => __( 'Booked', 'wp-booking-system-luca' ),
				'start' => $booking->check_in,
				'end'   => date( 'Y-m-d', strtotime( $booking->check_out . ' +1 day' ) ),
				'display' => 'background',
				'backgroundColor' => '#8B0000',
				'borderColor' => '#8B0000',
			);
		}

		wp_send_json_success( $events );
	}

	/**
	 * Get status color for calendar.
	 *
	 * @param string $status Booking status.
	 * @return string
	 */
	private function get_status_color( $status ) {
		$colors = array(
			'pending'  => '#ff9800',
			'confirmed' => '#4caf50',
			'cancelled' => '#f44336',
		);

		return isset( $colors[ $status ] ) ? $colors[ $status ] : '#757575';
	}
}

