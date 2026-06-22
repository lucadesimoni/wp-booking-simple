<?php
/**
 * Frontend class for booking form
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Frontend Class
 */
class WP_Booking_System_Luca_Frontend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'wp_booking_form_luca', array( $this, 'render_booking_form' ) );
		add_shortcode( 'wp_booking_manage_luca', array( $this, 'render_booking_manage' ) );
		add_shortcode( 'wp_booking_calendar_luca', array( $this, 'render_booking_calendar' ) );
	}

	/**
	 * Register assets, and enqueue them only on pages that need them.
	 *
	 * Registering up front means the inline calendar scripts always have the
	 * localized data available, while conditional enqueuing keeps the rest of
	 * the site lean and fast.
	 */
	public function enqueue_scripts() {
		// Third-party libraries bundled with the plugin (no external CDN, so the
		// date picker and calendar work even behind a strict CSP or offline).
		wp_register_style( 'flatpickr', WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.css', array(), '4.6.13' );
		wp_register_script( 'flatpickr', WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.js', array(), '4.6.13', true );
		wp_register_script( 'fullcalendar', WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/vendor/fullcalendar/index.global.min.js', array(), '6.1.10', true );
		wp_register_script( 'wpbsl-qrcode', WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/vendor/qrcode/qrcode.js', array(), WP_BOOKING_SYSTEM_LUCA_VERSION, true );

		// Plugin assets.
		wp_register_style(
			'wp-booking-system-luca-frontend',
			WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/css/frontend.css',
			array( 'flatpickr' ),
			WP_BOOKING_SYSTEM_LUCA_VERSION
		);

		wp_register_script(
			'wp-booking-system-luca-frontend',
			WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery', 'flatpickr', 'fullcalendar' ),
			WP_BOOKING_SYSTEM_LUCA_VERSION,
			true
		);

		wp_localize_script(
			'wp-booking-system-luca-frontend',
			'wpbslFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp-booking-system-luca-frontend' ),
				'config'  => array(
					'minNights'      => max( 1, absint( get_option( 'wpbsl_min_nights', 1 ) ) ),
					'maxNights'      => absint( get_option( 'wpbsl_max_nights', 0 ) ),
					'minAdvanceDays' => absint( get_option( 'wpbsl_min_advance_days', 0 ) ),
					'maxAdvanceDays' => absint( get_option( 'wpbsl_max_advance_days', 0 ) ),
				),
				'unavailableDates' => $this->get_unavailable_dates(),
				'i18n'    => array(
					'checking'      => __( 'Checking availability...', 'wp-booking-system-luca' ),
					'available'     => __( 'Available', 'wp-booking-system-luca' ),
					'unavailable'   => __( 'Unavailable', 'wp-booking-system-luca' ),
					'tipAvailable'  => __( 'Available — click to select your dates', 'wp-booking-system-luca' ),
					'tipBooked'     => __( 'Already booked', 'wp-booking-system-luca' ),
					'tipPast'       => __( 'This date has already passed', 'wp-booking-system-luca' ),
					'selectDates'   => __( 'Please select check-in and check-out dates', 'wp-booking-system-luca' ),
					'invalidDates'  => __( 'Check-out date must be after check-in date', 'wp-booking-system-luca' ),
					'calculating'   => __( 'Calculating price...', 'wp-booking-system-luca' ),
					'submitting'    => __( 'Submitting...', 'wp-booking-system-luca' ),
					'submittedNote' => __( 'A confirmation email with a link to manage your booking is on its way. If you don\'t see it shortly, please check your spam folder.', 'wp-booking-system-luca' ),
					'bookAnother'   => __( 'Book another stay', 'wp-booking-system-luca' ),
					'confirmCancel' => __( 'Are you sure you want to cancel this booking?', 'wp-booking-system-luca' ),
					'cancelled'     => __( 'Cancelled', 'wp-booking-system-luca' ),
				),
			)
		);

		if ( $this->should_enqueue_assets() ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Enqueue the (already registered) booking assets.
	 *
	 * Safe to call multiple times and at render time: WordPress de-dupes
	 * enqueues, and because the scripts are footer scripts this still works
	 * when called from inside a shortcode/block rendered by a page builder
	 * (Elementor, Divi, WPBakery, etc.) where the content is not in
	 * `post_content` and head-time detection cannot see it. This is what makes
	 * the form/calendar embeddable on any existing page.
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'wp-booking-system-luca-frontend' );
		wp_enqueue_script( 'wp-booking-system-luca-frontend' );
	}

	/**
	 * Decide whether the current request needs the booking assets.
	 *
	 * @return bool
	 */
	private function should_enqueue_assets() {
		// The booking calendar widget can appear in any sidebar.
		if ( is_active_widget( false, false, 'wp_booking_system_luca_widget', true ) ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();

			if ( $post instanceof WP_Post ) {
				$shortcodes = array( 'wp_booking_form_luca', 'wp_booking_manage_luca', 'wp_booking_calendar_luca' );
				foreach ( $shortcodes as $shortcode ) {
					if ( has_shortcode( $post->post_content, $shortcode ) ) {
						return true;
					}
				}

				if ( has_block( 'wp-booking-system/calendar', $post ) || has_block( 'wp-booking-system/form', $post ) ) {
					return true;
				}
			}
		}

		/**
		 * Allow themes/plugins to force-load the booking assets (e.g. when the
		 * form is rendered outside of post content).
		 *
		 * @param bool $enqueue Whether to enqueue the assets.
		 */
		return (bool) apply_filters( 'wpbsl_enqueue_assets', false );
	}

	/**
	 * Render booking form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_form( $atts = array() ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'title' => __( 'Book Your Stay', 'wp-booking-system-luca' ),
			),
			$atts,
			'wp_booking_form_luca'
		);

		$default_adults = max( 1, absint( get_option( 'wpbsl_default_adults', 2 ) ) );
		$default_kids   = absint( get_option( 'wpbsl_default_kids', 0 ) );
		$require_phone  = (int) get_option( 'wpbsl_require_phone', 0 );
		$show_notes     = (int) get_option( 'wpbsl_show_notes', 1 );
		$show_visitors  = (int) get_option( 'wpbsl_show_visitors', 1 );
		$owners         = WP_Booking_System_Luca_Helpers::parse_owners( get_option( 'wpbsl_owners', '' ) );
		$show_owner     = (int) get_option( 'wpbsl_show_owner', 1 ) && ! empty( $owners );

		ob_start();
		?>
		<div class="wpbs-booking-form-wrapper">
			<h2 class="wpbs-form-title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<form id="wpbs-booking-form" class="wpbs-booking-form">
				<div class="wpbs-form-row">
					<div class="wpbs-form-group">
						<label for="wpbs-check-in"><?php esc_html_e( 'Check-in', 'wp-booking-system-luca' ); ?></label>
						<input type="text" id="wpbs-check-in" name="check_in" class="wpbs-date-input" placeholder="<?php esc_attr_e( 'Select a date', 'wp-booking-system-luca' ); ?>" required readonly />
					</div>
					<div class="wpbs-form-group">
						<label for="wpbs-check-out"><?php esc_html_e( 'Check-out', 'wp-booking-system-luca' ); ?></label>
						<input type="text" id="wpbs-check-out" name="check_out" class="wpbs-date-input" placeholder="<?php esc_attr_e( 'Select a date', 'wp-booking-system-luca' ); ?>" required readonly />
					</div>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group">
						<label for="wpbs-adults"><?php esc_html_e( 'Adults', 'wp-booking-system-luca' ); ?></label>
						<input type="number" id="wpbs-adults" name="adults" min="1" value="<?php echo esc_attr( $default_adults ); ?>" required />
					</div>
					<div class="wpbs-form-group">
						<label for="wpbs-kids"><?php esc_html_e( 'Kids', 'wp-booking-system-luca' ); ?></label>
						<input type="number" id="wpbs-kids" name="kids" min="0" value="<?php echo esc_attr( $default_kids ); ?>" required />
					</div>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-first-name"><?php esc_html_e( 'First Name', 'wp-booking-system-luca' ); ?></label>
						<input type="text" id="wpbs-first-name" name="first_name" required />
					</div>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-last-name"><?php esc_html_e( 'Last Name', 'wp-booking-system-luca' ); ?></label>
						<input type="text" id="wpbs-last-name" name="last_name" required />
					</div>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-email"><?php esc_html_e( 'Email', 'wp-booking-system-luca' ); ?></label>
						<input type="email" id="wpbs-email" name="email" required />
					</div>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-phone"><?php esc_html_e( 'Phone', 'wp-booking-system-luca' ); ?></label>
						<input type="tel" id="wpbs-phone" name="phone" <?php echo $require_phone ? 'required' : ''; ?> />
					</div>
				</div>

				<?php if ( $show_owner ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-owner"><?php esc_html_e( 'Owner', 'wp-booking-system-luca' ); ?></label>
						<select id="wpbs-owner" name="owner">
							<option value="">&mdash;</option>
							<?php foreach ( $owners as $owner_name ) : ?>
								<option value="<?php echo esc_attr( $owner_name ); ?>"><?php echo esc_html( $owner_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $show_visitors ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-visitors-welcome"><?php esc_html_e( 'Visitors welcome?', 'wp-booking-system-luca' ); ?></label>
						<select id="wpbs-visitors-welcome" name="visitors_welcome">
							<option value="0"><?php esc_html_e( 'No', 'wp-booking-system-luca' ); ?></option>
							<option value="1"><?php esc_html_e( 'Yes', 'wp-booking-system-luca' ); ?></option>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $show_notes ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-notes"><?php esc_html_e( 'Notes', 'wp-booking-system-luca' ); ?></label>
						<textarea id="wpbs-notes" name="notes" rows="4"></textarea>
					</div>
				</div>
				<?php endif; ?>

				<div class="wpbs-price-summary" id="wpbs-price-summary" style="display: none;">
					<div class="wpbs-price-row">
						<span class="wpbs-price-label"><?php esc_html_e( 'Total Price:', 'wp-booking-system-luca' ); ?></span>
						<span class="wpbs-price-value" id="wpbs-total-price"></span>
					</div>
				</div>

				<div class="wpbs-form-messages" id="wpbs-form-messages"></div>

				<button type="submit" class="wpbs-submit-button">
					<?php esc_html_e( 'Book Now', 'wp-booking-system-luca' ); ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render booking management page.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_manage( $atts = array() ) {
		$this->enqueue_assets();

		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( empty( $token ) || ! WP_Booking_System_Luca_Helpers::is_valid_token( $token ) ) {
			return '<div class="wpbs-booking-manage-wrapper"><p>' . esc_html__( 'Invalid or missing booking link.', 'wp-booking-system-luca' ) . '</p></div>';
		}

		$booking = wp_booking_system_luca()->database->get_booking_by_token( $token );

		if ( ! $booking ) {
			return '<p>' . esc_html__( 'Booking not found.', 'wp-booking-system-luca' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wpbs-booking-manage-wrapper">
			<h2><?php esc_html_e( 'Manage Your Booking', 'wp-booking-system-luca' ); ?></h2>
			<div class="wpbs-booking-details">
				<p><strong><?php esc_html_e( 'Guest:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?></p>
				<p><strong><?php esc_html_e( 'Check-in:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) ); ?></p>
				<p><strong><?php esc_html_e( 'Check-out:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?></p>
				<p><strong><?php esc_html_e( 'Guests:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $booking->adults . ' ' . __( 'adults', 'wp-booking-system-luca' ) . ', ' . $booking->kids . ' ' . __( 'kids', 'wp-booking-system-luca' ) ); ?></p>
				<p><strong><?php esc_html_e( 'Total Price:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( number_format( $booking->total_price, 2 ) . ' ' . get_option( 'wpbsl_currency', 'CHF' ) ); ?></p>
				<p><strong><?php esc_html_e( 'Status:', 'wp-booking-system-luca' ); ?></strong> <span class="wpbs-status wpbs-status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span></p>
			</div>

			<?php
			$qr_iban = WP_Booking_System_Luca_Helpers::normalize_iban( get_option( 'wpbsl_qr_creditor_iban', '' ) );
			$qr_due  = WP_Booking_System_Luca_Helpers::amount_due( $booking );
			$qr_cur  = strtoupper( (string) get_option( 'wpbsl_currency', 'CHF' ) );
			$qr_cur  = in_array( $qr_cur, array( 'CHF', 'EUR' ), true ) ? $qr_cur : 'CHF';

			$qr_pstatus = isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid';
			if ( (int) get_option( 'wpbsl_qr_enabled', 0 ) && 'cancelled' !== $booking->status && 'refunded' !== $qr_pstatus && $qr_due > 0 && WP_Booking_System_Luca_Helpers::is_valid_ch_iban( $qr_iban ) ) :
				wp_enqueue_script( 'wpbsl-qrcode' );
				$qr_ref     = trim( sprintf( 'Booking #%d %s %s', (int) $booking->id, $booking->first_name, $booking->last_name ) );
				$qr_payload = WP_Booking_System_Luca_Helpers::build_swiss_qr_payload(
					array(
						'iban'     => $qr_iban,
						'name'     => get_option( 'wpbsl_qr_creditor_name', '' ),
						'address'  => get_option( 'wpbsl_qr_creditor_address', '' ),
						'city'     => get_option( 'wpbsl_qr_creditor_city', '' ),
						'country'  => get_option( 'wpbsl_qr_creditor_country', 'CH' ),
						'amount'   => $qr_due,
						'currency' => $qr_cur,
						'message'  => $qr_ref,
					)
				);
				?>
				<div class="wpbs-qr-pay">
					<h3><?php esc_html_e( 'Pay with TWINT', 'wp-booking-system-luca' ); ?></h3>
					<p><?php esc_html_e( 'Scan this QR code with TWINT or your banking app to pay the outstanding balance.', 'wp-booking-system-luca' ); ?></p>
					<div id="wpbs-qr" class="wpbs-qr" data-payload="<?php echo esc_attr( base64_encode( $qr_payload ) ); ?>" aria-label="<?php esc_attr_e( 'Swiss QR payment code', 'wp-booking-system-luca' ); ?>"></div>
					<ul class="wpbs-qr-info">
						<li><strong><?php esc_html_e( 'Amount:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( number_format( $qr_due, 2 ) . ' ' . $qr_cur ); ?></li>
						<li><strong><?php esc_html_e( 'IBAN:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( trim( chunk_split( $qr_iban, 4, ' ' ) ) ); ?></li>
						<li><strong><?php esc_html_e( 'Reference:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $qr_ref ); ?></li>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $booking->status !== 'cancelled' ) : ?>
				<div class="wpbs-booking-actions">
					<button type="button" class="wpbs-cancel-booking" data-token="<?php echo esc_attr( $token ); ?>">
						<?php esc_html_e( 'Cancel Booking', 'wp-booking-system-luca' ); ?>
					</button>
				</div>
			<?php endif; ?>

			<div class="wpbs-form-messages" id="wpbs-manage-messages"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Dates (Y-m-d) that are already taken by a non-cancelled booking, with
	 * each booked night marked unavailable (check-out day stays selectable as
	 * a new check-in). Used to disable dates in the picker and calendar.
	 *
	 * @return array
	 */
	private function get_unavailable_dates() {
		$bookings = wp_booking_system_luca()->database->get_bookings( array( 'status' => '' ) );

		$dates = array();
		foreach ( $bookings as $booking ) {
			if ( 'cancelled' === $booking->status ) {
				continue;
			}
			$current = new DateTime( $booking->check_in );
			$end     = new DateTime( $booking->check_out );
			while ( $current < $end ) {
				$dates[] = $current->format( 'Y-m-d' );
				$current->modify( '+1 day' );
			}
		}

		return array_values( array_unique( $dates ) );
	}

	/**
	 * Render booking calendar shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_calendar( $atts = array() ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			array(
				'title' => __( 'Booking Calendar', 'wp-booking-system-luca' ),
			),
			$atts,
			'wp_booking_calendar_luca'
		);

		// Get unavailable dates.
		$unavailable_dates = $this->get_unavailable_dates();

		ob_start();
		?>
		<div class="wpbs-calendar-shortcode-wrapper">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h3 class="wpbs-calendar-title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>
			<div id="wpbs-calendar-shortcode" class="wpbs-calendar-shortcode"></div>
			<div class="wpbs-calendar-legend">
				<span class="wpbs-legend-item">
					<span class="wpbs-legend-available"></span>
					<?php esc_html_e( 'Available', 'wp-booking-system-luca' ); ?>
				</span>
				<span class="wpbs-legend-item">
					<span class="wpbs-legend-booked"></span>
					<?php esc_html_e( 'Booked', 'wp-booking-system-luca' ); ?>
				</span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

