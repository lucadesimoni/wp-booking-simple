<?php
/**
 * Stateless helper functions.
 *
 * Pure logic (pricing, validation, formatting) lives here so it can be
 * unit-tested without a running WordPress instance.
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Helpers Class
 */
class WP_Booking_System_Luca_Helpers {

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
}
