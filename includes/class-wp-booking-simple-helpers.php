<?php
/**
 * Stateless helper functions.
 *
 * Pure logic (pricing, validation, formatting) lives here so it can be
 * unit-tested without a running WordPress instance.
 *
 * @package WP_Booking_Simple
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_Simple_Helpers Class
 */
class WP_Booking_Simple_Helpers {

	/**
	 * Normalise an IBAN: strip spaces, uppercase.
	 *
	 * @param string $iban Raw IBAN.
	 * @return string
	 */
	public static function normalize_iban( $iban ) {
		return strtoupper( preg_replace( '/\s+/', '', (string) $iban ) );
	}

	/**
	 * Whether a string is a structurally valid Swiss/Liechtenstein IBAN
	 * (CH/LI, 21 chars, ISO 7064 mod-97 == 1). Swiss QR-bills require one.
	 *
	 * @param string $iban Raw IBAN.
	 * @return bool
	 */
	public static function is_valid_ch_iban( $iban ) {
		$iban = self::normalize_iban( $iban );
		if ( ! preg_match( '/^(CH|LI)[0-9]{2}[0-9A-Z]{17}$/', $iban ) ) {
			return false;
		}
		// Move the first 4 chars to the end, convert letters to numbers, mod 97.
		$rearranged = substr( $iban, 4 ) . substr( $iban, 0, 4 );
		$digits     = '';
		$len        = strlen( $rearranged );
		for ( $i = 0; $i < $len; $i++ ) {
			$ch       = $rearranged[ $i ];
			$digits  .= ctype_alpha( $ch ) ? (string) ( ord( $ch ) - 55 ) : $ch;
		}
		// Mod 97 over a long numeric string, in chunks to avoid overflow.
		$remainder = '';
		$dlen      = strlen( $digits );
		for ( $i = 0; $i < $dlen; $i += 7 ) {
			$remainder = (string) ( (int) ( $remainder . substr( $digits, $i, 7 ) ) % 97 );
		}
		return 1 === (int) $remainder;
	}

	/**
	 * Build the Swiss QR Code (QR-bill, Swiss Payment Standards 2.0) payload
	 * string. The guest scans the resulting QR with TWINT or any Swiss banking
	 * app to pay — no merchant account or fees required beyond a normal IBAN.
	 *
	 * @param array $args {
	 *     @type string $iban     Creditor IBAN (CH/LI).
	 *     @type string $name     Creditor name.
	 *     @type string $address  Creditor address line 1 (street + number).
	 *     @type string $city     Creditor address line 2 (postal code + town).
	 *     @type string $country  ISO country code (default CH).
	 *     @type float  $amount   Amount.
	 *     @type string $currency Currency (CHF/EUR).
	 *     @type string $message  Unstructured message (e.g. booking reference).
	 * }
	 * @return string Newline-separated Swiss QR payload.
	 */
	public static function build_swiss_qr_payload( $args ) {
		$defaults = array(
			'iban'     => '',
			'name'     => '',
			'address'  => '',
			'city'     => '',
			'country'  => 'CH',
			'amount'   => 0,
			'currency' => 'CHF',
			'message'  => '',
		);
		$a = array_merge( $defaults, $args );

		$clip = function ( $value, $max ) {
			$value = trim( preg_replace( '/[\r\n]+/', ' ', (string) $value ) );
			return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
		};

		$lines = array(
			'SPC',                                   // QRType.
			'0200',                                  // Version.
			'1',                                     // Coding type.
			self::normalize_iban( $a['iban'] ),      // Account (IBAN).
			'K',                                     // Creditor address type: combined.
			$clip( $a['name'], 70 ),                 // Creditor name.
			$clip( $a['address'], 70 ),              // Address line 1.
			$clip( $a['city'], 70 ),                 // Address line 2.
			'',                                      // Postal code (empty for combined).
			'',                                      // Town (empty for combined).
			strtoupper( substr( (string) $a['country'], 0, 2 ) ), // Country.
			'', '', '', '', '', '', '',              // Ultimate creditor (unused).
			number_format( (float) $a['amount'], 2, '.', '' ),    // Amount.
			strtoupper( substr( (string) $a['currency'], 0, 3 ) ), // Currency.
			'', '', '', '', '', '', '',              // Ultimate debtor (unused).
			'NON',                                   // Reference type.
			'',                                      // Reference (empty for NON).
			$clip( $a['message'], 140 ),             // Unstructured message.
			'EPD',                                   // Trailer.
		);

		return implode( "\n", $lines );
	}

	/**
	 * Allowed payment statuses.
	 *
	 * @return array
	 */
	public static function payment_statuses() {
		return array(
			'unpaid'   => __( 'Unpaid', 'wp-booking-simple' ),
			'partial'  => __( 'Partially paid', 'wp-booking-simple' ),
			'paid'     => __( 'Paid', 'wp-booking-simple' ),
			'refunded' => __( 'Refunded', 'wp-booking-simple' ),
		);
	}

	/**
	 * Allowed payment methods ('' = none recorded).
	 *
	 * @return array
	 */
	public static function payment_methods() {
		return array(
			'bank'  => __( 'Bank', 'wp-booking-simple' ),
			'twint' => __( 'TWINT', 'wp-booking-simple' ),
			'bar'   => __( 'Cash (Bar)', 'wp-booking-simple' ),
		);
	}

	/**
	 * Booking fields that can be edited from the admin and are tracked in the
	 * change history, mapped to their human label.
	 *
	 * @return array
	 */
	public static function tracked_fields() {
		return array(
			'first_name'       => __( 'First name', 'wp-booking-simple' ),
			'last_name'        => __( 'Last name', 'wp-booking-simple' ),
			'email'            => __( 'Email', 'wp-booking-simple' ),
			'phone'            => __( 'Phone', 'wp-booking-simple' ),
			'check_in'         => __( 'Check-in', 'wp-booking-simple' ),
			'check_out'        => __( 'Check-out', 'wp-booking-simple' ),
			'adults'           => __( 'Adults', 'wp-booking-simple' ),
			'kids'             => __( 'Kids', 'wp-booking-simple' ),
			'owner'            => __( 'Owner', 'wp-booking-simple' ),
			'visitors_welcome' => __( 'Visitors welcome', 'wp-booking-simple' ),
			'total_price'      => __( 'Total price', 'wp-booking-simple' ),
			'status'           => __( 'Status', 'wp-booking-simple' ),
			'payment_status'   => __( 'Payment status', 'wp-booking-simple' ),
			'payment_method'   => __( 'Payment method', 'wp-booking-simple' ),
			'amount_paid'      => __( 'Amount paid', 'wp-booking-simple' ),
			'notes'            => __( 'Notes', 'wp-booking-simple' ),
		);
	}

	/**
	 * Compute the differences between an existing booking and a set of
	 * proposed new values, limited to the tracked fields.
	 *
	 * @param object|array $old Current booking.
	 * @param array        $new Proposed values keyed by field.
	 * @return array Map of field => array( 'from' => string, 'to' => string ).
	 */
	public static function compute_changes( $old, $new ) {
		$old     = (array) $old;
		$numeric = array( 'total_price', 'amount_paid', 'adults', 'kids', 'visitors_welcome' );
		$changes = array();

		foreach ( self::tracked_fields() as $field => $label ) {
			if ( ! array_key_exists( $field, $new ) ) {
				continue;
			}

			$from = array_key_exists( $field, $old ) ? $old[ $field ] : '';
			$to   = $new[ $field ];

			if ( in_array( $field, $numeric, true ) ) {
				if ( (float) $from === (float) $to ) {
					continue;
				}
			} elseif ( (string) $from === (string) $to ) {
				continue;
			}

			$changes[ $field ] = array(
				'from' => (string) $from,
				'to'   => (string) $to,
			);
		}

		return $changes;
	}

	/**
	 * Outstanding balance for a booking (total price minus amount paid),
	 * never negative.
	 *
	 * @param object|array $booking Booking.
	 * @return float
	 */
	public static function amount_due( $booking ) {
		$booking = (object) $booking;
		$total   = isset( $booking->total_price ) ? (float) $booking->total_price : 0.0;
		$paid    = isset( $booking->amount_paid ) ? (float) $booking->amount_paid : 0.0;

		return max( 0.0, round( $total - $paid, 2 ) );
	}

	/**
	 * Filter a list of bookings to those whose check-in date falls within an
	 * inclusive [from, to] range. Empty bounds are ignored. Dates are
	 * compared as Y-m-d strings (lexicographic order matches chronological).
	 *
	 * @param array  $bookings Bookings.
	 * @param string $from     Lower bound (Y-m-d) or ''.
	 * @param string $to       Upper bound (Y-m-d) or ''.
	 * @return array
	 */
	public static function filter_by_date_range( $bookings, $from, $to ) {
		if ( '' === (string) $from && '' === (string) $to ) {
			return $bookings;
		}

		$out = array();
		foreach ( $bookings as $b ) {
			$ci = is_object( $b ) ? $b->check_in : $b['check_in'];
			if ( '' !== (string) $from && $ci < $from ) {
				continue;
			}
			if ( '' !== (string) $to && $ci > $to ) {
				continue;
			}
			$out[] = $b;
		}

		return $out;
	}

	/**
	 * Number of nights between two dates (minimum 1).
	 *
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @return int
	 */
	public static function calculate_nights( $check_in, $check_out ) {
		$in  = strtotime( $check_in );
		$out = strtotime( $check_out );

		if ( false === $in || false === $out || $out <= $in ) {
			return 1;
		}

		return (int) max( 1, floor( ( $out - $in ) / DAY_IN_SECONDS ) );
	}

	/**
	 * Calculate the total price of a stay.
	 *
	 * @param string $check_in    Check-in date (Y-m-d).
	 * @param string $check_out   Check-out date (Y-m-d).
	 * @param int    $adults      Number of adults.
	 * @param int    $kids        Number of kids.
	 * @param float  $price_adult Nightly price per adult.
	 * @param float  $price_kid   Nightly price per kid.
	 * @return float
	 */
	public static function calculate_price( $check_in, $check_out, $adults, $kids, $price_adult, $price_kid ) {
		$nights = self::calculate_nights( $check_in, $check_out );
		$adults = max( 0, (int) $adults );
		$kids   = max( 0, (int) $kids );

		$total = ( $adults * (float) $price_adult + $kids * (float) $price_kid ) * $nights;

		return round( (float) $total, 2 );
	}

	/**
	 * Validate a date string in Y-m-d format.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_valid_date( $date ) {
		if ( ! is_string( $date ) || '' === $date ) {
			return false;
		}

		$d = DateTime::createFromFormat( 'Y-m-d', $date );

		return $d && $d->format( 'Y-m-d' ) === $date;
	}

	/**
	 * Validate a booking token (64-character hex string).
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	public static function is_valid_token( $token ) {
		return is_string( $token ) && (bool) preg_match( '/^[a-f0-9]{64}$/i', $token );
	}

	/**
	 * Whether the requested party exceeds the chalet capacity.
	 *
	 * @param int $adults       Number of adults.
	 * @param int $kids         Number of kids.
	 * @param int $max_capacity Maximum allowed guests.
	 * @return bool
	 */
	public static function exceeds_capacity( $adults, $kids, $max_capacity ) {
		return ( (int) $adults + (int) $kids ) > (int) $max_capacity;
	}

	/**
	 * Whether the requested check-out is after the check-in.
	 *
	 * @param string $check_in  Check-in date (Y-m-d).
	 * @param string $check_out Check-out date (Y-m-d).
	 * @return bool
	 */
	public static function is_valid_range( $check_in, $check_out ) {
		if ( ! self::is_valid_date( $check_in ) || ! self::is_valid_date( $check_out ) ) {
			return false;
		}

		return strtotime( $check_out ) > strtotime( $check_in );
	}

	/**
	 * Number of whole days from a reference date until a given date.
	 *
	 * @param string   $date         Target date (Y-m-d).
	 * @param int|null $reference_ts Reference timestamp (defaults to today). Useful for tests.
	 * @return int|null Days ahead (negative if in the past), or null for an invalid date.
	 */
	public static function days_until( $date, $reference_ts = null ) {
		if ( ! self::is_valid_date( $date ) ) {
			return null;
		}

		$reference_ts  = is_null( $reference_ts ) ? time() : $reference_ts;
		$ref_midnight  = strtotime( gmdate( 'Y-m-d', $reference_ts ) );
		$date_midnight = strtotime( $date );

		return (int) floor( ( $date_midnight - $ref_midnight ) / DAY_IN_SECONDS );
	}

	/**
	 * Whether a check-in date falls inside the allowed booking window.
	 *
	 * @param string   $check_in         Check-in date (Y-m-d).
	 * @param int      $min_advance_days Earliest allowed (days from today). 0 = today allowed.
	 * @param int      $max_advance_days Latest allowed (days from today). 0 = no upper limit.
	 * @param int|null $reference_ts     Reference timestamp (defaults to today). Useful for tests.
	 * @return bool
	 */
	public static function is_within_booking_window( $check_in, $min_advance_days, $max_advance_days, $reference_ts = null ) {
		$days = self::days_until( $check_in, $reference_ts );

		if ( is_null( $days ) ) {
			return false;
		}

		if ( $days < (int) $min_advance_days ) {
			return false;
		}

		if ( (int) $max_advance_days > 0 && $days > (int) $max_advance_days ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether a stay's length satisfies the configured min/max nights.
	 *
	 * @param string $check_in   Check-in date (Y-m-d).
	 * @param string $check_out  Check-out date (Y-m-d).
	 * @param int    $min_nights Minimum nights (treated as at least 1).
	 * @param int    $max_nights Maximum nights. 0 = no upper limit.
	 * @return bool
	 */
	public static function meets_stay_length( $check_in, $check_out, $min_nights, $max_nights ) {
		$nights = self::calculate_nights( $check_in, $check_out );

		if ( $nights < max( 1, (int) $min_nights ) ) {
			return false;
		}

		if ( (int) $max_nights > 0 && $nights > (int) $max_nights ) {
			return false;
		}

		return true;
	}

	/**
	 * Parse a configured owners list (newline or comma separated) into a
	 * clean, de-duplicated array of names.
	 *
	 * @param string $raw Raw option value.
	 * @return string[]
	 */
	public static function parse_owners( $raw ) {
		$parts = preg_split( '/[\r\n,]+/', (string) $raw );
		$names = array();

		foreach ( (array) $parts as $part ) {
			$part = trim( $part );
			if ( '' !== $part && ! in_array( $part, $names, true ) ) {
				$names[] = $part;
			}
		}

		return $names;
	}

	/**
	 * Allowed booking statuses.
	 *
	 * @return string[]
	 */
	public static function allowed_statuses() {
		return array( 'pending', 'confirmed', 'cancelled' );
	}

	/**
	 * Whether a status string is valid.
	 *
	 * @param string $status Status.
	 * @return bool
	 */
	public static function is_valid_status( $status ) {
		return in_array( $status, self::allowed_statuses(), true );
	}

	/**
	 * Human-readable guest count, e.g. "2 adults, 1 kid" or just "1 adult".
	 *
	 * Pluralised properly, and kids are left out entirely at zero: with the
	 * kids field switched off every booking would otherwise read ", 0 kids".
	 *
	 * Lives here so the booking emails and the guest's manage page cannot
	 * drift apart - they each used to build this string themselves, and both
	 * got the plural wrong.
	 *
	 * @param object|array $booking Booking row.
	 * @return string
	 */
	public static function guests_label( $booking ) {
		$booking = (object) $booking;
		$adults  = isset( $booking->adults ) ? (int) $booking->adults : 0;
		$kids    = isset( $booking->kids ) ? (int) $booking->kids : 0;

		$label = sprintf(
			/* translators: %d: number of adults */
			_n( '%d adult', '%d adults', $adults, 'wp-booking-simple' ),
			$adults
		);

		if ( $kids > 0 ) {
			$label .= ', ' . sprintf(
				/* translators: %d: number of kids */
				_n( '%d kid', '%d kids', $kids, 'wp-booking-simple' ),
				$kids
			);
		}

		return $label;
	}

	/**
	 * The booking-form fields a site owner can switch off, and whether each one
	 * is currently switched on.
	 *
	 * Check-in, check-out, adults, first name and email are not listed: the
	 * booking cannot be priced, held or confirmed by email without them.
	 *
	 * Both the rendered form and the AJAX validation read this single map, so a
	 * field can never be hidden in the form while still being enforced on
	 * submit (or, worse, accepted from a hand-crafted request).
	 *
	 * The owner dropdown additionally needs at least one configured name -
	 * showing an empty dropdown would just be a dead control.
	 *
	 * @return array Field key => bool.
	 */
	public static function form_fields() {
		return array(
			'last_name' => (bool) get_option( 'wpbsl_show_last_name', 1 ),
			'phone'     => (bool) get_option( 'wpbsl_show_phone', 1 ),
			'kids'      => (bool) get_option( 'wpbsl_show_kids', 1 ),
			'owner'     => (bool) get_option( 'wpbsl_show_owner', 1 )
				&& array() !== self::parse_owners( get_option( 'wpbsl_owners', '' ) ),
			'visitors'  => (bool) get_option( 'wpbsl_show_visitors', 1 ),
			'notes'     => (bool) get_option( 'wpbsl_show_notes', 1 ),
		);
	}

	/**
	 * Whether one booking-form field is switched on.
	 *
	 * Unknown keys report true, so the mandatory fields that are not part of
	 * the map are never treated as hidden.
	 *
	 * @param string $field Field key.
	 * @return bool
	 */
	public static function shows_field( $field ) {
		$fields = self::form_fields();

		return isset( $fields[ $field ] ) ? $fields[ $field ] : true;
	}

	/**
	 * Prefix marking a value encrypted by encrypt_secret().
	 */
	const SECRET_PREFIX = 'wpbsl-enc:v1:';

	/**
	 * Key for encrypt_secret(), derived from the site's secret salts
	 * (AUTH_KEY / AUTH_SALT in wp-config.php), so it never sits in the
	 * database next to the data it protects.
	 *
	 * @return string 32-byte binary key.
	 */
	private static function secret_key() {
		return hash( 'sha256', wp_salt( 'auth' ) . '|wp-booking-simple', true );
	}

	/**
	 * Encrypt a secret (the SMTP password) for storage in the options table.
	 *
	 * Uses AES-256-GCM when OpenSSL is available; otherwise the value is
	 * stored as before, so SMTP keeps working on hosts without OpenSSL.
	 * Changing the wp-config.php salts makes stored secrets unreadable; the
	 * password then has to be entered again.
	 *
	 * @param string $plain Plain-text secret.
	 * @return string
	 */
	public static function encrypt_secret( $plain ) {
		$plain = (string) $plain;
		if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) || ! in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true ) ) {
			return $plain;
		}
		$iv     = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $plain, 'aes-256-gcm', self::secret_key(), OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $cipher ) {
			return $plain;
		}
		return self::SECRET_PREFIX . base64_encode( $iv . $tag . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary ciphertext stored as text.
	}

	/**
	 * Decrypt a value stored by encrypt_secret(). Values saved by earlier
	 * versions (plain text, no prefix) are returned unchanged.
	 *
	 * @param string $stored Stored value.
	 * @return string Plain text, or '' when it cannot be decrypted.
	 */
	public static function decrypt_secret( $stored ) {
		$stored = (string) $stored;
		if ( 0 !== strpos( $stored, self::SECRET_PREFIX ) ) {
			return $stored;
		}
		$raw = base64_decode( substr( $stored, strlen( self::SECRET_PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- see encrypt_secret().
		if ( false === $raw || strlen( $raw ) < 29 || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$plain = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', self::secret_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
		return false === $plain ? '' : $plain;
	}

	/**
	 * Run a front-end renderer so that a bug in it can never take the page
	 * down: any error or exception is caught, partial output is discarded,
	 * the problem is reported, and an empty string is returned (a short note
	 * for users who can edit, so they know something needs attention).
	 *
	 * @param string   $context  What was being rendered (for the report).
	 * @param callable $callback Renderer returning HTML.
	 * @param array    $args     Arguments for the renderer.
	 * @return string
	 */
	public static function safe_render( $context, $callback, $args = array() ) {
		$level = ob_get_level();
		ob_start();
		try {
			$html = (string) call_user_func_array( $callback, $args );
			// Anything the renderer echoed instead of returning stays with it.
			return (string) ob_get_clean() . $html;
		} catch ( \Throwable $e ) {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			self::report_error( $context, $e );
			if ( function_exists( 'current_user_can' ) && current_user_can( 'edit_posts' ) ) {
				return '<p class="wpbs-render-error">' . esc_html__( 'WP Booking Simple could not display this element. Details are in the PHP error log (with WP_DEBUG enabled).', 'wp-booking-simple' ) . '</p>';
			}
			return '';
		}
	}

	/**
	 * Report a caught error: to the PHP error log when WP_DEBUG is on, and to
	 * the `wpbsl_render_error` action for monitoring plugins.
	 *
	 * @param string     $context Where it happened.
	 * @param \Throwable $e       The error.
	 * @return void
	 */
	public static function report_error( $context, $e ) {
		$message = sprintf( 'WP Booking Simple: %s failed: %s in %s:%d', $context, $e->getMessage(), $e->getFile(), $e->getLine() );
		if ( function_exists( 'wp_trigger_error' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_trigger_error( '', $message, E_USER_WARNING );
		}
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpbsl_render_error', $context, $e );
		}
	}
}
