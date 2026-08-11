<?php
/**
 * Unit tests for WP_Booking_Simple_Helpers.
 *
 * These run under the WordPress PHPUnit harness but exercise only pure logic,
 * so they double as fast regression tests for pricing and validation.
 *
 * @package WP_Booking_Simple
 */

class Test_WPBSL_Helpers extends WP_UnitTestCase {

	public function test_calculate_nights() {
		$this->assertSame( 1, WP_Booking_Simple_Helpers::calculate_nights( '2026-06-01', '2026-06-02' ) );
		$this->assertSame( 7, WP_Booking_Simple_Helpers::calculate_nights( '2026-06-01', '2026-06-08' ) );
		$this->assertSame( 1, WP_Booking_Simple_Helpers::calculate_nights( '2026-06-08', '2026-06-01' ) );
	}

	public function test_calculate_price() {
		$this->assertEqualsWithDelta( 375.0, WP_Booking_Simple_Helpers::calculate_price( '2026-06-01', '2026-06-04', 2, 1, 50, 25 ), 0.001 );
		$this->assertEqualsWithDelta( 241.0, WP_Booking_Simple_Helpers::calculate_price( '2026-06-01', '2026-06-03', 1, 0, 120.5, 60 ), 0.001 );
		$this->assertEqualsWithDelta( 0.0, WP_Booking_Simple_Helpers::calculate_price( '2026-06-01', '2026-06-04', 0, 0, 50, 25 ), 0.001 );
	}

	public function test_is_valid_date() {
		$this->assertTrue( WP_Booking_Simple_Helpers::is_valid_date( '2026-06-11' ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_date( '2026-13-40' ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_date( '11-06-2026' ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_date( '' ) );
	}

	public function test_is_valid_range() {
		$this->assertTrue( WP_Booking_Simple_Helpers::is_valid_range( '2026-06-01', '2026-06-05' ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_range( '2026-06-05', '2026-06-05' ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_range( '2026-06-05', '2026-06-01' ) );
	}

	public function test_is_valid_token() {
		$this->assertTrue( WP_Booking_Simple_Helpers::is_valid_token( str_repeat( 'a', 64 ) ) );
		$this->assertTrue( WP_Booking_Simple_Helpers::is_valid_token( bin2hex( random_bytes( 32 ) ) ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_token( str_repeat( 'a', 63 ) ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_token( str_repeat( 'z', 64 ) ) );
	}

	public function test_exceeds_capacity() {
		$this->assertTrue( WP_Booking_Simple_Helpers::exceeds_capacity( 8, 3, 10 ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::exceeds_capacity( 6, 4, 10 ) );
	}

	public function test_is_valid_status() {
		$this->assertTrue( WP_Booking_Simple_Helpers::is_valid_status( 'confirmed' ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_valid_status( 'deleted' ) );
	}

	public function test_days_until() {
		$ref = strtotime( '2026-06-01' );
		$this->assertSame( 9, WP_Booking_Simple_Helpers::days_until( '2026-06-10', $ref ) );
		$this->assertSame( -1, WP_Booking_Simple_Helpers::days_until( '2026-05-31', $ref ) );
		$this->assertNull( WP_Booking_Simple_Helpers::days_until( 'not-a-date', $ref ) );
	}

	public function test_meets_stay_length() {
		$this->assertTrue( WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-04', 2, 7 ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-02', 2, 7 ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-15', 2, 7 ) );
		$this->assertTrue( WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-30', 2, 0 ) );
	}

	public function test_is_within_booking_window() {
		$ref = strtotime( '2026-06-01' );
		$this->assertTrue( WP_Booking_Simple_Helpers::is_within_booking_window( '2026-06-08', 7, 365, $ref ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_within_booking_window( '2026-06-03', 7, 365, $ref ) );
		$this->assertFalse( WP_Booking_Simple_Helpers::is_within_booking_window( '2027-06-01', 0, 90, $ref ) );
		$this->assertTrue( WP_Booking_Simple_Helpers::is_within_booking_window( '2026-12-01', 0, 0, $ref ) );
	}
}
