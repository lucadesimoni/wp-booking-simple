<?php
/**
 * Booking insights / aggregation.
 *
 * Pure aggregation logic kept separate so it can be unit-tested without a
 * running WordPress instance.
 *
 * @package WP_Booking_System_Luca
 * @since 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Stats Class
 */
class WP_Booking_System_Luca_Stats {

	/**
	 * Summarise a set of bookings into dashboard metrics.
	 *
	 * Cancelled bookings are excluded from usage/revenue metrics but still
	 * counted in the status breakdown and the cancelled tally.
	 *
	 * @param array $bookings Array of booking objects/arrays.
	 * @return array
	 */
	public static function summarize( $bookings ) {
		$totals = array(
			'bookings'    => 0,
			'nights'      => 0,
			'adults'      => 0,
			'kids'        => 0,
			'guests'      => 0,
			'revenue'     => 0.0,
			'collected'   => 0.0,
			'outstanding' => 0.0,
			'cancelled'   => 0,
			'visitors'    => 0,
		);

		$by_guest  = array();
		$by_owner  = array();
		$by_method = array();
		$by_status = array();
		$by_month  = array();

		foreach ( $bookings as $b ) {
			$b      = (object) $b;
			$status = isset( $b->status ) ? $b->status : 'pending';

			// Status breakdown counts everything.
			$by_status[ $status ] = isset( $by_status[ $status ] ) ? $by_status[ $status ] + 1 : 1;

			if ( 'cancelled' === $status ) {
				$totals['cancelled']++;
				continue;
			}

			$nights = WP_Booking_System_Luca_Helpers::calculate_nights( $b->check_in, $b->check_out );
			$adults = (int) $b->adults;
			$kids   = (int) $b->kids;
			$guests = $adults + $kids;
			$price  = (float) $b->total_price;
			$paid   = isset( $b->amount_paid ) ? (float) $b->amount_paid : 0.0;

			$totals['bookings']++;
			$totals['nights']    += $nights;
			$totals['adults']    += $adults;
			$totals['kids']      += $kids;
			$totals['guests']    += $guests;
			$totals['revenue']   += $price;
			$totals['collected'] += $paid;
			if ( ! empty( $b->visitors_welcome ) ) {
				$totals['visitors']++;
			}

			// Per guest (keyed by email when present, else name).
			$name = trim( $b->first_name . ' ' . $b->last_name );
			$key  = strtolower( trim( ! empty( $b->email ) ? $b->email : $name ) );
			if ( ! isset( $by_guest[ $key ] ) ) {
				$by_guest[ $key ] = array(
					'name'     => $name,
					'email'    => isset( $b->email ) ? $b->email : '',
					'bookings' => 0,
					'nights'   => 0,
					'guests'   => 0,
					'revenue'  => 0.0,
				);
			}
			$by_guest[ $key ]['bookings']++;
			$by_guest[ $key ]['nights']  += $nights;
			$by_guest[ $key ]['guests']  += $guests;
			$by_guest[ $key ]['revenue'] += $price;

			// Per owner.
			$owner = isset( $b->owner ) && '' !== trim( (string) $b->owner ) ? $b->owner : '__none__';
			if ( ! isset( $by_owner[ $owner ] ) ) {
				$by_owner[ $owner ] = array(
					'owner'    => '__none__' === $owner ? '' : $b->owner,
					'bookings' => 0,
					'nights'   => 0,
				);
			}
			$by_owner[ $owner ]['bookings']++;
			$by_owner[ $owner ]['nights'] += $nights;

			// Payment method breakdown (by collected amount).
			if ( $paid > 0 ) {
				$method = isset( $b->payment_method ) && '' !== trim( (string) $b->payment_method ) ? $b->payment_method : '__none__';
				if ( ! isset( $by_method[ $method ] ) ) {
					$by_method[ $method ] = array(
						'method' => '__none__' === $method ? '' : $b->payment_method,
						'count'  => 0,
						'amount' => 0.0,
					);
				}
				$by_method[ $method ]['count']++;
				$by_method[ $method ]['amount'] += $paid;
			}

			// Per check-in month.
			$month = substr( (string) $b->check_in, 0, 7 );
			if ( '' !== $month ) {
				$by_month[ $month ] = isset( $by_month[ $month ] ) ? $by_month[ $month ] + 1 : 1;
			}
		}

		$totals['outstanding'] = $totals['revenue'] - $totals['collected'];

		// Sort: guests/owners by bookings desc, months chronologically.
		$by_guest = array_values( $by_guest );
		usort(
			$by_guest,
			function ( $a, $z ) {
				return $z['bookings'] <=> $a['bookings'];
			}
		);

		$by_owner = array_values( $by_owner );
		usort(
			$by_owner,
			function ( $a, $z ) {
				return $z['nights'] <=> $a['nights'];
			}
		);

		$by_method = array_values( $by_method );
		ksort( $by_month );

		return array(
			'totals'    => $totals,
			'by_guest'  => $by_guest,
			'by_owner'  => $by_owner,
			'by_method' => $by_method,
			'by_status' => $by_status,
			'by_month'  => $by_month,
		);
	}
}
