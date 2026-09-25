<?php
/**
 * Frontend class for booking form
 *
 * @package WP_Booking_Simple
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_Simple_Frontend Class
 */
class WP_Booking_Simple_Frontend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		foreach ( self::get_shortcodes() as $shortcode => $method ) {
			add_shortcode( $shortcode, array( $this, $method ) );
		}
	}

	/**
	 * Map every supported shortcode tag to its renderer.
	 *
	 * The `_luca` tags are what the plugin registered before it was renamed to
	 * WP Booking Simple. They stay registered as aliases so pages that already
	 * embed them - including the "Book Now" and "Manage Booking" pages created
	 * by earlier versions - keep rendering instead of printing the raw tag.
	 *
	 * @return array Shortcode tag => method name.
	 */
	public static function get_shortcodes() {
		return array(
			'wp_booking_form_simple'     => 'render_booking_form',
			'wp_booking_manage_simple'   => 'render_booking_manage',
			'wp_booking_calendar_simple' => 'render_booking_calendar',
			// Legacy aliases (pre-rename).
			'wp_booking_form_luca'       => 'render_booking_form',
			'wp_booking_manage_luca'     => 'render_booking_manage',
			'wp_booking_calendar_luca'   => 'render_booking_calendar',
		);
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
		wp_register_script( 'flatpickr', WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.js', array(), '4.6.13', true );
		wp_register_script( 'fullcalendar', WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/vendor/fullcalendar/index.global.min.js', array(), '6.1.10', true );
		wp_register_script( 'wpbs-qrcode', WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/vendor/qrcode/qrcode.js', array(), WP_BOOKING_SIMPLE_VERSION, true );

		// Plugin assets. The stylesheets are registered on init by
		// WP_Booking_Simple_Theme, so the block editor can load them too.
		wp_register_script(
			'wp-booking-simple-frontend',
			WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery', 'flatpickr', 'fullcalendar' ),
			WP_BOOKING_SIMPLE_VERSION,
			true
		);

		wp_localize_script(
			'wp-booking-simple-frontend',
			'wpbsFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp-booking-simple-frontend' ),
				'config'  => array(
					'minNights'      => max( 1, absint( get_option( 'wpbsl_min_nights', 1 ) ) ),
					'maxNights'      => absint( get_option( 'wpbsl_max_nights', 0 ) ),
					'minAdvanceDays' => absint( get_option( 'wpbsl_min_advance_days', 0 ) ),
					'maxAdvanceDays' => absint( get_option( 'wpbsl_max_advance_days', 0 ) ),
				),
				'unavailableDates' => $this->get_unavailable_dates(),
				'i18n'    => array(
					'checking'      => __( 'Checking availability...', 'wp-booking-simple' ),
					'available'     => __( 'Available', 'wp-booking-simple' ),
					'unavailable'   => __( 'Unavailable', 'wp-booking-simple' ),
					'tipAvailable'  => __( 'Available — click to select your dates', 'wp-booking-simple' ),
					'tipBooked'     => __( 'Already booked', 'wp-booking-simple' ),
					'tipPast'       => __( 'This date has already passed', 'wp-booking-simple' ),
					'selectDates'   => __( 'Please select check-in and check-out dates', 'wp-booking-simple' ),
					'invalidDates'  => __( 'Check-out date must be after check-in date', 'wp-booking-simple' ),
					'calculating'   => __( 'Calculating price...', 'wp-booking-simple' ),
					'submitting'    => __( 'Submitting...', 'wp-booking-simple' ),
					'submittedNote' => __( 'A confirmation email with a link to manage your booking is on its way. If you don\'t see it shortly, please check your spam folder.', 'wp-booking-simple' ),
					'bookAnother'   => __( 'Book another stay', 'wp-booking-simple' ),
					'payTitle'      => __( 'Pay now', 'wp-booking-simple' ),
					'payIntro'      => __( 'Scan the QR code with TWINT or your banking app to pay.', 'wp-booking-simple' ),
					'payWithTwint'  => __( 'Pay with TWINT', 'wp-booking-simple' ),
					'labelAmount'   => __( 'Amount:', 'wp-booking-simple' ),
					'labelIban'     => __( 'IBAN:', 'wp-booking-simple' ),
					'labelReference' => __( 'Reference:', 'wp-booking-simple' ),
					'confirmCancel' => __( 'Are you sure you want to cancel this booking?', 'wp-booking-simple' ),
					'cancelled'     => __( 'Cancelled', 'wp-booking-simple' ),
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
		wp_enqueue_style( 'wp-booking-simple-frontend' );
		wp_enqueue_script( 'wp-booking-simple-frontend' );
	}

	/**
	 * Decide whether the current request needs the booking assets.
	 *
	 * @return bool
	 */
	private function should_enqueue_assets() {
		// The booking calendar widget can appear in any sidebar.
		if ( is_active_widget( false, false, 'wp_booking_simple_widget', true ) ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();

			if ( $post instanceof WP_Post ) {
				// Includes the legacy `_luca` aliases, so pages embedding the
				// pre-rename tags still get the styles and scripts.
				foreach ( array_keys( self::get_shortcodes() ) as $shortcode ) {
					if ( has_shortcode( $post->post_content, $shortcode ) ) {
						return true;
					}
				}

				foreach ( WP_Booking_Simple_Block::get_block_names() as $block ) {
					if ( has_block( $block, $post ) ) {
						return true;
					}
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

		// When TWINT/QR payment is configured, load the QR generator so the
		// post-booking confirmation can offer payment immediately at checkout.
		if ( (int) get_option( 'wpbsl_qr_enabled', 0 ) && WP_Booking_Simple_Helpers::is_valid_ch_iban( WP_Booking_Simple_Helpers::normalize_iban( get_option( 'wpbsl_qr_creditor_iban', '' ) ) ) ) {
			wp_enqueue_script( 'wpbs-qrcode' );
		}

		$atts = shortcode_atts(
			array(
				'title' => __( 'Book Your Stay', 'wp-booking-simple' ),
			),
			$atts,
			'wp_booking_form_simple'
		);

		$default_adults = max( 1, absint( get_option( 'wpbsl_default_adults', 2 ) ) );
		$default_kids   = absint( get_option( 'wpbsl_default_kids', 0 ) );
		$owners         = WP_Booking_Simple_Helpers::parse_owners( get_option( 'wpbsl_owners', '' ) );

		// Which optional fields this site shows. The AJAX handler reads the same
		// map, so hiding a field here also stops it being required on submit.
		$fields         = WP_Booking_Simple_Helpers::form_fields();
		$show_last_name = $fields['last_name'];
		$show_phone     = $fields['phone'];
		$show_kids      = $fields['kids'];
		$show_owner     = $fields['owner'];
		$show_visitors  = $fields['visitors'];
		$show_notes     = $fields['notes'];

		// Phone can only be mandatory while it is actually on the form.
		$require_phone = $show_phone && (int) get_option( 'wpbsl_require_phone', 0 );

		ob_start();
		?>
		<div class="wpbs-booking-form-wrapper">
			<h2 class="wpbs-form-title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<form id="wpbs-booking-form" class="wpbs-booking-form">
				<div class="wpbs-form-row">
					<div class="wpbs-form-group">
						<label for="wpbs-check-in"><?php esc_html_e( 'Check-in', 'wp-booking-simple' ); ?></label>
						<input type="text" id="wpbs-check-in" name="check_in" class="wpbs-date-input" placeholder="<?php esc_attr_e( 'Select a date', 'wp-booking-simple' ); ?>" required readonly />
					</div>
					<div class="wpbs-form-group">
						<label for="wpbs-check-out"><?php esc_html_e( 'Check-out', 'wp-booking-simple' ); ?></label>
						<input type="text" id="wpbs-check-out" name="check_out" class="wpbs-date-input" placeholder="<?php esc_attr_e( 'Select a date', 'wp-booking-simple' ); ?>" required readonly />
					</div>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group<?php echo $show_kids ? '' : ' wpbs-form-group-full'; ?>">
						<label for="wpbs-adults"><?php esc_html_e( 'Adults', 'wp-booking-simple' ); ?></label>
						<input type="number" id="wpbs-adults" name="adults" min="1" value="<?php echo esc_attr( $default_adults ); ?>" required />
					</div>
					<?php if ( $show_kids ) : ?>
					<div class="wpbs-form-group">
						<label for="wpbs-kids"><?php esc_html_e( 'Kids', 'wp-booking-simple' ); ?></label>
						<input type="number" id="wpbs-kids" name="kids" min="0" value="<?php echo esc_attr( $default_kids ); ?>" required />
					</div>
					<?php endif; ?>
				</div>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-first-name"><?php esc_html_e( 'First Name', 'wp-booking-simple' ); ?></label>
						<input type="text" id="wpbs-first-name" name="first_name" required />
					</div>
				</div>

				<?php if ( $show_last_name ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-last-name"><?php esc_html_e( 'Last Name', 'wp-booking-simple' ); ?></label>
						<input type="text" id="wpbs-last-name" name="last_name" required />
					</div>
				</div>
				<?php endif; ?>

				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-email"><?php esc_html_e( 'Email', 'wp-booking-simple' ); ?></label>
						<input type="email" id="wpbs-email" name="email" required />
					</div>
				</div>

				<?php if ( $show_phone ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-phone"><?php esc_html_e( 'Phone', 'wp-booking-simple' ); ?></label>
						<input type="tel" id="wpbs-phone" name="phone" <?php echo $require_phone ? 'required' : ''; ?> />
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $show_owner ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-owner"><?php esc_html_e( 'Owner', 'wp-booking-simple' ); ?></label>
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
						<label for="wpbs-visitors-welcome"><?php esc_html_e( 'Visitors welcome?', 'wp-booking-simple' ); ?></label>
						<select id="wpbs-visitors-welcome" name="visitors_welcome">
							<option value="0"><?php esc_html_e( 'No', 'wp-booking-simple' ); ?></option>
							<option value="1"><?php esc_html_e( 'Yes', 'wp-booking-simple' ); ?></option>
						</select>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $show_notes ) : ?>
				<div class="wpbs-form-row">
					<div class="wpbs-form-group wpbs-form-group-full">
						<label for="wpbs-notes"><?php esc_html_e( 'Notes', 'wp-booking-simple' ); ?></label>
						<textarea id="wpbs-notes" name="notes" rows="4"></textarea>
					</div>
				</div>
				<?php endif; ?>

				<div class="wpbs-price-summary" id="wpbs-price-summary" style="display: none;">
					<div class="wpbs-price-row">
						<span class="wpbs-price-label"><?php esc_html_e( 'Total Price:', 'wp-booking-simple' ); ?></span>
						<span class="wpbs-price-value" id="wpbs-total-price"></span>
					</div>
				</div>

				<div class="wpbs-form-messages" id="wpbs-form-messages"></div>

				<button type="submit" class="wpbs-submit-button">
					<?php esc_html_e( 'Book Now', 'wp-booking-simple' ); ?>
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

		if ( empty( $token ) || ! WP_Booking_Simple_Helpers::is_valid_token( $token ) ) {
			return '<div class="wpbs-booking-manage-wrapper"><p>' . esc_html__( 'Invalid or missing booking link.', 'wp-booking-simple' ) . '</p></div>';
		}

		$booking = wp_booking_simple()->database->get_booking_by_token( $token );

		if ( ! $booking ) {
			return '<p>' . esc_html__( 'Booking not found.', 'wp-booking-simple' ) . '</p>';
		}

		$currency       = get_option( 'wpbsl_currency', 'CHF' );
		$nights         = WP_Booking_Simple_Helpers::calculate_nights( $booking->check_in, $booking->check_out );
		$pay_labels     = WP_Booking_Simple_Helpers::payment_statuses();
		$pstatus        = isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid';
		$due            = WP_Booking_Simple_Helpers::amount_due( $booking );
		$status_message = array(
			'pending'   => __( 'Your booking is awaiting confirmation', 'wp-booking-simple' ),
			'confirmed' => __( 'Your booking is confirmed', 'wp-booking-simple' ),
			'cancelled' => __( 'This booking has been cancelled', 'wp-booking-simple' ),
		);
		$status_icon    = array( 'pending' => '⏳', 'confirmed' => '✓', 'cancelled' => '✕' );
		$bstatus        = isset( $status_message[ $booking->status ] ) ? $booking->status : 'pending';

		ob_start();
		?>
		<div class="wpbs-booking-manage-wrapper">
			<h2><?php esc_html_e( 'Manage Your Booking', 'wp-booking-simple' ); ?></h2>

			<div class="wpbs-manage-banner wpbs-manage-banner-<?php echo esc_attr( $bstatus ); ?>">
				<span class="wpbs-manage-banner-text"><?php echo esc_html( $status_icon[ $bstatus ] . ' ' . $status_message[ $bstatus ] ); ?></span>
				<span class="wpbs-manage-ref"><?php echo esc_html( sprintf( /* translators: %d: booking id */ __( 'Booking #%d', 'wp-booking-simple' ), (int) $booking->id ) ); ?></span>
			</div>

			<div class="wpbs-booking-details">
				<p><strong><?php esc_html_e( 'Guest:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?></p>
				<p><strong><?php esc_html_e( 'Check-in:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) ); ?></p>
				<p><strong><?php esc_html_e( 'Check-out:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?>
					<span class="wpbs-nights">(<?php echo esc_html( sprintf( _n( '%d night', '%d nights', $nights, 'wp-booking-simple' ), $nights ) ); ?>)</span></p>
				<p><strong><?php esc_html_e( 'Guests:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( WP_Booking_Simple_Helpers::guests_label( $booking ) ); ?></p>
				<p><strong><?php esc_html_e( 'Total Price:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( number_format( $booking->total_price, 2 ) . ' ' . $currency ); ?></p>
				<p><strong><?php esc_html_e( 'Payment:', 'wp-booking-simple' ); ?></strong>
					<span class="wpbs-pay wpbs-pay-<?php echo esc_attr( $pstatus ); ?>"><?php echo esc_html( isset( $pay_labels[ $pstatus ] ) ? $pay_labels[ $pstatus ] : ucfirst( $pstatus ) ); ?></span>
					<?php if ( $due > 0 && 'cancelled' !== $booking->status ) : ?>
						<span class="wpbs-outstanding"><?php echo esc_html( sprintf( /* translators: %s: amount */ __( '%s outstanding', 'wp-booking-simple' ), number_format( $due, 2 ) . ' ' . $currency ) ); ?></span>
					<?php endif; ?>
				</p>
			</div>

			<?php
			$pay = wp_booking_simple()->email->payment_context( $booking );
			if ( $pay ) :
				wp_enqueue_script( 'wpbs-qrcode' );
				?>
				<div class="wpbs-qr-pay">
					<h3><?php esc_html_e( 'Pay with TWINT', 'wp-booking-simple' ); ?></h3>
					<p><?php esc_html_e( 'Scan this QR code with TWINT or your banking app to pay the outstanding balance.', 'wp-booking-simple' ); ?></p>
					<div id="wpbs-qr" class="wpbs-qr" data-payload="<?php echo esc_attr( $pay['qr_payload'] ); ?>" aria-label="<?php esc_attr_e( 'Swiss QR payment code', 'wp-booking-simple' ); ?>"></div>
					<ul class="wpbs-qr-info">
						<li><strong><?php esc_html_e( 'Amount:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( $pay['amount'] ); ?></li>
						<?php if ( '' !== $pay['name'] ) : ?><li><?php echo esc_html( $pay['name'] ); ?></li><?php endif; ?>
						<?php if ( '' !== $pay['bank'] ) : ?><li><?php echo esc_html( $pay['bank'] ); ?></li><?php endif; ?>
						<li><strong><?php esc_html_e( 'IBAN:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( $pay['iban'] ); ?></li>
						<li><strong><?php esc_html_e( 'Reference:', 'wp-booking-simple' ); ?></strong> <?php echo esc_html( $pay['reference'] ); ?></li>
					</ul>
					<?php if ( '' !== $pay['paylink'] ) : ?>
						<a class="wpbs-twint-btn" href="<?php echo esc_url( $pay['paylink'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Pay with TWINT', 'wp-booking-simple' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $booking->status !== 'cancelled' ) : ?>
				<div class="wpbs-booking-actions">
					<button type="button" class="wpbs-cancel-booking" data-token="<?php echo esc_attr( $token ); ?>">
						<?php esc_html_e( 'Cancel Booking', 'wp-booking-simple' ); ?>
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
		$bookings = wp_booking_simple()->database->get_bookings( array( 'status' => '' ) );

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
				'title' => __( 'Booking Calendar', 'wp-booking-simple' ),
			),
			$atts,
			'wp_booking_calendar_simple'
		);

		$cal_id = wp_unique_id( 'wpbs-calendar-' );

		ob_start();
		?>
		<div class="wpbs-calendar-shortcode-wrapper">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h3 class="wpbs-calendar-title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>
			<div id="<?php echo esc_attr( $cal_id ); ?>" class="wpbs-calendar-shortcode"></div>
			<div class="wpbs-calendar-legend">
				<span class="wpbs-legend-item">
					<span class="wpbs-legend-available"></span>
					<?php esc_html_e( 'Available', 'wp-booking-simple' ); ?>
				</span>
				<span class="wpbs-legend-item">
					<span class="wpbs-legend-booked"></span>
					<?php esc_html_e( 'Booked', 'wp-booking-simple' ); ?>
				</span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

