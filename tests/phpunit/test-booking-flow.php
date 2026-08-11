<?php
/**
 * Integration tests for the booking lifecycle.
 *
 * Runs against a real (test) WordPress database, exercising the database
 * layer, availability logic, magic-link page provisioning and email sending.
 *
 * @package WP_Booking_Simple
 */

class Test_WPBSL_Booking_Flow extends WP_UnitTestCase {

	/**
	 * @var WP_Booking_Simple_Database
	 */
	protected $db;

	public function set_up() {
		parent::set_up();
		$this->db = wp_booking_simple()->database;
		$this->db->create_tables();
	}

	public function test_insert_and_fetch_booking() {
		$id = $this->db->insert_booking(
			array(
				'first_name'  => 'Ada',
				'last_name'   => 'Lovelace',
				'email'       => 'ada@example.com',
				'check_in'    => '2026-07-01',
				'check_out'   => '2026-07-05',
				'adults'      => 2,
				'kids'        => 1,
				'total_price' => 375.00,
			)
		);

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		$booking = $this->db->get_booking( $id );
		$this->assertSame( 'Ada', $booking->first_name );
		$this->assertSame( 'pending', $booking->status );
		$this->assertTrue( WP_Booking_Simple_Helpers::is_valid_token( $booking->booking_token ) );

		// Token lookup powers the magic link.
		$by_token = $this->db->get_booking_by_token( $booking->booking_token );
		$this->assertEquals( $id, $by_token->id );
	}

	public function test_availability_blocks_overlaps() {
		$this->db->insert_booking(
			array(
				'first_name'  => 'Grace',
				'last_name'   => 'Hopper',
				'email'       => 'grace@example.com',
				'check_in'    => '2026-08-10',
				'check_out'   => '2026-08-15',
				'adults'      => 2,
				'total_price' => 500.00,
				'status'      => 'confirmed',
			)
		);

		$this->assertFalse( $this->db->is_available( '2026-08-12', '2026-08-14' ), 'overlap inside an existing booking is unavailable' );
		$this->assertTrue( $this->db->is_available( '2026-08-15', '2026-08-18' ), 'a stay starting on a checkout day is available' );
		$this->assertTrue( $this->db->is_available( '2026-09-01', '2026-09-05' ), 'a clear range is available' );
	}

	public function test_cancelled_booking_frees_dates() {
		$id = $this->db->insert_booking(
			array(
				'first_name'  => 'Alan',
				'last_name'   => 'Turing',
				'email'       => 'alan@example.com',
				'check_in'    => '2026-10-01',
				'check_out'   => '2026-10-05',
				'adults'      => 1,
				'total_price' => 200.00,
			)
		);

		$this->assertFalse( $this->db->is_available( '2026-10-02', '2026-10-04' ) );

		$this->db->update_booking( $id, array( 'status' => 'cancelled' ) );
		$this->assertTrue( $this->db->is_available( '2026-10-02', '2026-10-04' ), 'cancelled bookings no longer block dates' );
	}

	public function test_activation_creates_pages() {
		WP_Booking_Simple::create_pages();

		$booking_page = (int) get_option( 'wpbsl_booking_page_id' );
		$manage_page  = (int) get_option( 'wpbsl_manage_page_id' );

		$this->assertGreaterThan( 0, $booking_page );
		$this->assertGreaterThan( 0, $manage_page );
		$this->assertSame( 'publish', get_post_status( $booking_page ) );
		$this->assertStringContainsString( '[wp_booking_form_simple]', get_post( $booking_page )->post_content );
		$this->assertStringContainsString( '[wp_booking_manage_simple]', get_post( $manage_page )->post_content );

		// Re-running must not create duplicates.
		WP_Booking_Simple::create_pages();
		$this->assertSame( $manage_page, (int) get_option( 'wpbsl_manage_page_id' ) );
	}

	public function test_confirmation_email_is_sent() {
		$id      = $this->db->insert_booking(
			array(
				'first_name'  => 'Edsger',
				'last_name'   => 'Dijkstra',
				'email'       => 'edsger@example.com',
				'check_in'    => '2026-11-01',
				'check_out'   => '2026-11-03',
				'adults'      => 2,
				'total_price' => 200.00,
			)
		);
		$booking = $this->db->get_booking( $id );

		$mailer = tests_retrieve_phpmailer_instance();
		reset_phpmailer_instance();

		$sent = wp_booking_simple()->email->send_booking_confirmation( $booking );
		$this->assertTrue( $sent );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertSame( 'edsger@example.com', $mailer->get_recent_email()->to[0][0] );
	}
}
