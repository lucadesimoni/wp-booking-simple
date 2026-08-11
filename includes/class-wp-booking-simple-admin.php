<?php
/**
 * Admin class for managing bookings
 *
 * @package WP_Booking_Simple
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_Simple_Admin Class
 */
class WP_Booking_Simple_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_smtp_notice' ) );
		add_action( 'admin_post_wpbsl_export_csv', array( $this, 'export_csv' ) );
	}

	/**
	 * Stream the bookings (optionally restricted to a check-in date range) as
	 * a CSV download. Triggered from admin-post.php.
	 */
	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'wp-booking-simple' ) );
		}

		check_admin_referer( 'wpbsl_export_csv' );

		$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';

		$bookings = wp_booking_simple()->database->get_bookings();
		$bookings = WP_Booking_Simple_Helpers::filter_by_date_range( $bookings, $from, $to );

		$pay_statuses = WP_Booking_Simple_Helpers::payment_statuses();
		$pay_methods  = WP_Booking_Simple_Helpers::payment_methods();
		$currency     = get_option( 'wpbsl_currency', 'CHF' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bookings-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );

		fputcsv(
			$out,
			array(
				'ID', 'First name', 'Last name', 'Email', 'Phone', 'Check-in', 'Check-out', 'Nights',
				'Adults', 'Kids', 'Owner', 'Visitors welcome', 'Status',
				'Total price (' . $currency . ')', 'Amount paid (' . $currency . ')', 'Outstanding (' . $currency . ')',
				'Payment status', 'Payment method', 'Created at',
			)
		);

		foreach ( $bookings as $b ) {
			$method = isset( $b->payment_method ) ? $b->payment_method : '';
			$pstat  = isset( $b->payment_status ) ? $b->payment_status : 'unpaid';
			fputcsv(
				$out,
				array(
					$b->id,
					$b->first_name,
					$b->last_name,
					$b->email,
					$b->phone,
					$b->check_in,
					$b->check_out,
					WP_Booking_Simple_Helpers::calculate_nights( $b->check_in, $b->check_out ),
					$b->adults,
					$b->kids,
					isset( $b->owner ) ? $b->owner : '',
					( isset( $b->visitors_welcome ) && (int) $b->visitors_welcome ) ? 'Yes' : 'No',
					ucfirst( (string) $b->status ),
					number_format( (float) $b->total_price, 2, '.', '' ),
					number_format( (float) ( isset( $b->amount_paid ) ? $b->amount_paid : 0 ), 2, '.', '' ),
					number_format( WP_Booking_Simple_Helpers::amount_due( $b ), 2, '.', '' ),
					isset( $pay_statuses[ $pstat ] ) ? $pay_statuses[ $pstat ] : $pstat,
					( '' !== $method && isset( $pay_methods[ $method ] ) ) ? $pay_methods[ $method ] : '',
					isset( $b->created_at ) ? $b->created_at : '',
				)
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * Nudge the admin to set up reliable email delivery.
	 *
	 * Shown only on this plugin's own admin screens, and only while SMTP
	 * delivery is disabled, so it never nags across the whole dashboard.
	 */
	public function maybe_show_smtp_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || false === strpos( (string) $screen->id, 'wp-booking-simple' ) ) {
			return;
		}

		// Don't show it on the Settings screen itself — the guidance is already there.
		if ( false !== strpos( (string) $screen->id, 'wp-booking-simple-settings' ) ) {
			return;
		}

		if ( (int) get_option( 'wpbsl_smtp_enabled', 0 ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=wp-booking-simple-settings' );
		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'WP Booking Simple is using your server\'s default mailer, which can fail to deliver booking emails. For reliable delivery (e.g. through Gmail), enable SMTP in', 'wp-booking-simple' ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'wp-booking-simple' ); ?></a>.
			</p>
		</div>
		<?php
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Bookings', 'wp-booking-simple' ),
			__( 'WP Booking Simple', 'wp-booking-simple' ),
			'manage_options',
			'wp-booking-simple',
			array( $this, 'render_calendar_page' ),
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'wp-booking-simple',
			__( 'All Bookings', 'wp-booking-simple' ),
			__( 'All Bookings', 'wp-booking-simple' ),
			'manage_options',
			'wp-booking-simple-list',
			array( $this, 'render_list_page' )
		);

		add_submenu_page(
			'wp-booking-simple',
			__( 'Dashboard', 'wp-booking-simple' ),
			__( 'Dashboard', 'wp-booking-simple' ),
			'manage_options',
			'wp-booking-simple-dashboard',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'wp-booking-simple',
			__( 'Settings', 'wp-booking-simple' ),
			__( 'Settings', 'wp-booking-simple' ),
			'manage_options',
			'wp-booking-simple-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Our screen hooks are toplevel_page_wp-booking-simple and
		// wp-booking-simple_page_wp-booking-simple-{list,settings}; they all
		// share the "wp-booking-simple" stem.
		if ( strpos( $hook, 'wp-booking-simple' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'wp-booking-simple-admin',
			WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WP_BOOKING_SIMPLE_VERSION
		);

		wp_enqueue_script(
			'wp-booking-simple-admin',
			WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'fullcalendar' ),
			WP_BOOKING_SIMPLE_VERSION,
			true
		);

		wp_localize_script(
			'wp-booking-simple-admin',
			'wpbsAdmin',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'wp-booking-simple-admin' ),
				'currency'   => get_option( 'wpbsl_currency', 'CHF' ),
				'priceAdult' => floatval( get_option( 'wpbsl_price_adult', 50 ) ),
				'priceKid'   => floatval( get_option( 'wpbsl_price_kid', 25 ) ),
				'i18n'    => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this booking?', 'wp-booking-simple' ),
					'confirmCancel'  => __( 'Cancel this booking and email the guest?', 'wp-booking-simple' ),
					'genericError'   => __( 'An error occurred. Please try again.', 'wp-booking-simple' ),
					'saving'         => __( 'Saving…', 'wp-booking-simple' ),
					'sending'        => __( 'Sending…', 'wp-booking-simple' ),
					'noHistory'      => __( 'No changes recorded yet.', 'wp-booking-simple' ),
					'confirmInsertDe' => __( 'Replace the confirmation, cancellation and reminder email text with the German starter?', 'wp-booking-simple' ),
					'insertedDe'     => __( 'Inserted — remember to click Save Settings.', 'wp-booking-simple' ),
				),
			)
		);

		// Email template block builder (settings screen only).
		if ( false !== strpos( $hook, 'wp-booking-simple-settings' ) ) {
			wp_enqueue_script(
				'wp-booking-simple-template-builder',
				WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/js/admin-template-builder.js',
				array(),
				WP_BOOKING_SIMPLE_VERSION,
				true
			);

			wp_localize_script(
				'wp-booking-simple-template-builder',
				'wpbsBuilder',
				array(
					'mergeTags' => array(
						'{site_name}', '{guest_name}', '{first_name}', '{last_name}', '{guest_email}', '{guest_phone}',
						'{check_in}', '{check_out}', '{adults}', '{kids}', '{guests}', '{total_price}', '{status}',
						'{owner}', '{visitors_welcome}', '{payment_status}', '{payment_method}', '{amount_paid}', '{amount_due}',
						'{notes}', '{booking_details}', '{payment_info}', '{payment_account}', '{payment_bank}', '{payment_iban}', '{payment_twint}',
						'{manage_url}', '{manage_link}', '{admin_link}',
					),
					'i18n'      => array(
						'addBlock'      => __( 'Add block', 'wp-booking-simple' ),
						'text'          => __( 'Text', 'wp-booking-simple' ),
						'heading'       => __( 'Heading', 'wp-booking-simple' ),
						'details'       => __( 'Booking details', 'wp-booking-simple' ),
						'button'        => __( 'Button', 'wp-booking-simple' ),
						'image'         => __( 'Image', 'wp-booking-simple' ),
						'divider'       => __( 'Divider', 'wp-booking-simple' ),
						'remove'        => __( 'Remove', 'wp-booking-simple' ),
						'drag'          => __( 'Drag to reorder', 'wp-booking-simple' ),
						'label'         => __( 'Button label', 'wp-booking-simple' ),
						'url'           => __( 'Button URL (e.g. {manage_url})', 'wp-booking-simple' ),
						'imageUrl'      => __( 'Image URL', 'wp-booking-simple' ),
						'altText'       => __( 'Alt text', 'wp-booking-simple' ),
						'widthPx'       => __( 'Width (px, optional)', 'wp-booking-simple' ),
						'detailsNote'   => __( 'The styled booking-details box is inserted here.', 'wp-booking-simple' ),
						'dividerNote'   => __( 'A horizontal divider.', 'wp-booking-simple' ),
						'insertTag'     => __( 'Insert tag', 'wp-booking-simple' ),
						'empty'         => __( 'No blocks yet — add one to start building this email.', 'wp-booking-simple' ),
					),
				)
			);
		}

		// FullCalendar (bundled v6 global build; injects its own styles, no CDN).
		wp_enqueue_script(
			'fullcalendar',
			WP_BOOKING_SIMPLE_PLUGIN_URL . 'assets/vendor/fullcalendar/index.global.min.js',
			array(),
			'6.1.10',
			true
		);
	}

	/**
	 * Render calendar page.
	 */
	public function render_calendar_page() {
		$bookings = wp_booking_simple()->database->get_bookings();

		$today     = current_time( 'Y-m-d' );
		$counts    = array( 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0 );
		$upcoming  = 0;
		$next_in   = null;

		foreach ( $bookings as $b ) {
			if ( isset( $counts[ $b->status ] ) ) {
				$counts[ $b->status ]++;
			}
			if ( 'cancelled' !== $b->status && $b->check_in >= $today ) {
				$upcoming++;
				if ( null === $next_in || $b->check_in < $next_in ) {
					$next_in = $b->check_in;
				}
			}
		}

		$cards = array(
			array( __( 'Upcoming stays', 'wp-booking-simple' ), $upcoming, '#2271b1' ),
			array( __( 'Pending', 'wp-booking-simple' ), $counts['pending'], '#ff9800' ),
			array( __( 'Confirmed', 'wp-booking-simple' ), $counts['confirmed'], '#4caf50' ),
			array( __( 'Total bookings', 'wp-booking-simple' ), count( $bookings ), '#757575' ),
		);
		?>
		<div class="wrap wpbs-admin-wrap">
			<h1><?php esc_html_e( 'Booking Calendar', 'wp-booking-simple' ); ?></h1>

			<div class="wpbs-stat-cards">
				<?php foreach ( $cards as $card ) : ?>
					<div class="wpbs-stat-card" style="border-top:3px solid <?php echo esc_attr( $card[2] ); ?>;">
						<span class="wpbs-stat-number"><?php echo esc_html( $card[1] ); ?></span>
						<span class="wpbs-stat-label"><?php echo esc_html( $card[0] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $next_in ) : ?>
				<p class="description" style="margin:4px 0 12px;">
					<?php
					/* translators: %s: formatted date of the next check-in */
					printf( esc_html__( 'Next check-in: %s', 'wp-booking-simple' ), '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $next_in ) ) ) . '</strong>' );
					?>
				</p>
			<?php endif; ?>

			<div class="wpbs-calendar-legend">
				<span><span class="wpbs-legend-dot" style="background:#ff9800;"></span><?php esc_html_e( 'Pending', 'wp-booking-simple' ); ?></span>
				<span><span class="wpbs-legend-dot" style="background:#4caf50;"></span><?php esc_html_e( 'Confirmed', 'wp-booking-simple' ); ?></span>
				<span><span class="wpbs-legend-dot" style="background:#f44336;"></span><?php esc_html_e( 'Cancelled', 'wp-booking-simple' ); ?></span>
				<span class="description"><?php esc_html_e( 'Click an entry for details.', 'wp-booking-simple' ); ?></span>
			</div>

			<div id="wpbs-calendar" data-initial-date="<?php echo esc_attr( $next_in ? $next_in : $today ); ?>"></div>
		</div>
		<?php
		$this->render_booking_modal();
	}

	/**
	 * Render list page.
	 */
	public function render_list_page() {
		$bookings = wp_booking_simple()->database->get_bookings();
		?>
		<div class="wrap wpbs-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Bookings', 'wp-booking-simple' ); ?></h1>
			<?php
			$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=wpbsl_export_csv' ), 'wpbsl_export_csv' );
			?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'wp-booking-simple' ); ?></a>
			<hr class="wp-header-end" />
			<div class="wpbs-table-responsive">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Guest', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Email', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Check-in', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Check-out', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Guests', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Owner', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Price', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Payment', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wp-booking-simple' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-booking-simple' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $bookings ) ) : ?>
						<tr>
							<td colspan="11"><?php esc_html_e( 'No bookings found.', 'wp-booking-simple' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $bookings as $booking ) : ?>
							<tr>
								<td><?php echo esc_html( $booking->id ); ?></td>
								<td><?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?></td>
								<td><?php echo esc_html( $booking->email ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?></td>
								<td><?php echo esc_html( $booking->adults . ' ' . __( 'adults', 'wp-booking-simple' ) . ', ' . $booking->kids . ' ' . __( 'kids', 'wp-booking-simple' ) ); ?></td>
								<td><?php echo esc_html( ! empty( $booking->owner ) ? $booking->owner : '—' ); ?></td>
								<td><?php echo esc_html( number_format( $booking->total_price, 2 ) . ' ' . get_option( 'wpbsl_currency', 'CHF' ) ); ?></td>
								<?php
								$pay_key    = isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid';
								$pay_labels = WP_Booking_Simple_Helpers::payment_statuses();
								$cur        = get_option( 'wpbsl_currency', 'CHF' );
								?>
								<td>
									<span class="wpbs-pay wpbs-pay-<?php echo esc_attr( $pay_key ); ?>"><?php echo esc_html( isset( $pay_labels[ $pay_key ] ) ? $pay_labels[ $pay_key ] : ucfirst( $pay_key ) ); ?></span>
									<br /><small><?php echo esc_html( number_format( (float) ( isset( $booking->amount_paid ) ? $booking->amount_paid : 0 ), 0 ) . ' / ' . number_format( (float) $booking->total_price, 0 ) . ' ' . $cur ); ?></small>
								</td>
								<td>
									<span class="wpbs-status wpbs-status-<?php echo esc_attr( $booking->status ); ?>">
										<?php echo esc_html( ucfirst( $booking->status ) ); ?>
									</span>
								</td>
								<td>
									<a href="#" class="wpbs-view-booking" data-id="<?php echo esc_attr( $booking->id ); ?>">
										<?php esc_html_e( 'View / Edit', 'wp-booking-simple' ); ?>
									</a> |
									<?php if ( 'confirmed' !== $booking->status && 'cancelled' !== $booking->status ) : ?>
										<a href="#" class="wpbs-set-status" data-id="<?php echo esc_attr( $booking->id ); ?>" data-status="confirmed">
											<?php esc_html_e( 'Confirm', 'wp-booking-simple' ); ?>
										</a> |
									<?php endif; ?>
									<?php if ( 'cancelled' !== $booking->status ) : ?>
										<a href="#" class="wpbs-set-status" data-id="<?php echo esc_attr( $booking->id ); ?>" data-status="cancelled">
											<?php esc_html_e( 'Cancel', 'wp-booking-simple' ); ?>
										</a> |
									<?php endif; ?>
									<a href="#" class="wpbs-delete-booking" data-id="<?php echo esc_attr( $booking->id ); ?>">
										<?php esc_html_e( 'Delete', 'wp-booking-simple' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		<?php
		$this->render_booking_modal();
	}

	/**
	 * Render the booking view/edit modal (shared by the list and calendar
	 * screens). Fields are populated client-side via AJAX.
	 */
	public function render_booking_modal() {
		$owners   = WP_Booking_Simple_Helpers::parse_owners( get_option( 'wpbsl_owners', '' ) );
		$statuses = array(
			'pending'   => __( 'Pending', 'wp-booking-simple' ),
			'confirmed' => __( 'Confirmed', 'wp-booking-simple' ),
			'cancelled' => __( 'Cancelled', 'wp-booking-simple' ),
		);
		$cur = get_option( 'wpbsl_currency', 'CHF' );
		?>
		<div id="wpbs-modal" class="wpbs-modal" style="display:none;" aria-hidden="true">
			<div class="wpbs-modal-backdrop"></div>
			<div class="wpbs-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="wpbs-modal-title">
				<div class="wpbs-modal-head">
					<h2 id="wpbs-modal-title"><?php esc_html_e( 'Booking', 'wp-booking-simple' ); ?> <span id="wpbs-modal-id"></span></h2>
					<button type="button" class="wpbs-modal-close" aria-label="<?php esc_attr_e( 'Close', 'wp-booking-simple' ); ?>">&times;</button>
				</div>
				<div class="wpbs-modal-body">
					<form id="wpbs-edit-form" class="wpbs-edit-form">
						<input type="hidden" name="id" id="wpbs-f-id" value="" />

						<div class="wpbs-edit-grid">
							<label><?php esc_html_e( 'First name', 'wp-booking-simple' ); ?><input type="text" name="first_name" id="wpbs-f-first_name" /></label>
							<label><?php esc_html_e( 'Last name', 'wp-booking-simple' ); ?><input type="text" name="last_name" id="wpbs-f-last_name" /></label>
							<label><?php esc_html_e( 'Email', 'wp-booking-simple' ); ?><input type="email" name="email" id="wpbs-f-email" /></label>
							<label><?php esc_html_e( 'Phone', 'wp-booking-simple' ); ?><input type="text" name="phone" id="wpbs-f-phone" /></label>
							<label><?php esc_html_e( 'Check-in', 'wp-booking-simple' ); ?><input type="date" name="check_in" id="wpbs-f-check_in" /></label>
							<label><?php esc_html_e( 'Check-out', 'wp-booking-simple' ); ?><input type="date" name="check_out" id="wpbs-f-check_out" /></label>
							<label><?php esc_html_e( 'Adults', 'wp-booking-simple' ); ?><input type="number" min="1" name="adults" id="wpbs-f-adults" /></label>
							<label><?php esc_html_e( 'Kids', 'wp-booking-simple' ); ?><input type="number" min="0" name="kids" id="wpbs-f-kids" /></label>
							<label><?php esc_html_e( 'Owner', 'wp-booking-simple' ); ?>
								<select name="owner" id="wpbs-f-owner">
									<option value="">&mdash;</option>
									<?php foreach ( $owners as $o ) : ?>
										<option value="<?php echo esc_attr( $o ); ?>"><?php echo esc_html( $o ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label><?php esc_html_e( 'Visitors welcome', 'wp-booking-simple' ); ?>
								<select name="visitors_welcome" id="wpbs-f-visitors_welcome">
									<option value="0"><?php esc_html_e( 'No', 'wp-booking-simple' ); ?></option>
									<option value="1"><?php esc_html_e( 'Yes', 'wp-booking-simple' ); ?></option>
								</select>
							</label>
							<label><?php esc_html_e( 'Status', 'wp-booking-simple' ); ?>
								<select name="status" id="wpbs-f-status">
									<?php foreach ( $statuses as $k => $v ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="wpbs-price-field"><?php echo esc_html( sprintf( /* translators: %s currency */ __( 'Total price (%s)', 'wp-booking-simple' ), $cur ) ); ?>
								<span class="wpbs-price-row">
									<input type="number" step="0.01" min="0" name="total_price" id="wpbs-f-total_price" />
									<button type="button" class="button" id="wpbs-recalc-price"><?php esc_html_e( 'Recalc', 'wp-booking-simple' ); ?></button>
								</span>
							</label>
						</div>

						<fieldset class="wpbs-pay-fieldset">
							<legend><?php esc_html_e( 'Payment', 'wp-booking-simple' ); ?></legend>
							<div class="wpbs-edit-grid">
								<label><?php esc_html_e( 'Payment status', 'wp-booking-simple' ); ?>
									<select name="payment_status" id="wpbs-f-payment_status">
										<?php foreach ( WP_Booking_Simple_Helpers::payment_statuses() as $k => $v ) : ?>
											<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label><?php esc_html_e( 'Payment method', 'wp-booking-simple' ); ?>
									<select name="payment_method" id="wpbs-f-payment_method">
										<option value="">&mdash;</option>
										<?php foreach ( WP_Booking_Simple_Helpers::payment_methods() as $k => $v ) : ?>
											<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label><?php echo esc_html( sprintf( /* translators: %s currency */ __( 'Amount paid (%s)', 'wp-booking-simple' ), $cur ) ); ?><input type="number" step="0.01" min="0" name="amount_paid" id="wpbs-f-amount_paid" /></label>
							</div>
						</fieldset>

						<label class="wpbs-notes-field"><?php esc_html_e( 'Notes', 'wp-booking-simple' ); ?>
							<textarea name="notes" id="wpbs-f-notes" rows="3"></textarea>
						</label>

						<div class="wpbs-modal-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'wp-booking-simple' ); ?></button>
							<button type="button" class="button" id="wpbs-send-reminder"><?php esc_html_e( 'Send payment reminder', 'wp-booking-simple' ); ?></button>
							<span id="wpbs-edit-msg" class="wpbs-edit-msg"></span>
						</div>
					</form>

					<div class="wpbs-history">
						<h3><?php esc_html_e( 'Change history', 'wp-booking-simple' ); ?></h3>
						<div id="wpbs-history-list"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the insights dashboard.
	 */
	public function render_dashboard_page() {
		// Optional check-in date-range filter (read-only GET, no nonce needed).
		$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$bookings = wp_booking_simple()->database->get_bookings();
		$bookings = WP_Booking_Simple_Helpers::filter_by_date_range( $bookings, $from, $to );
		$stats    = WP_Booking_Simple_Stats::summarize( $bookings );
		$cur      = get_option( 'wpbsl_currency', 'CHF' );
		$t        = $stats['totals'];

		$export_args = array( 'action' => 'wpbsl_export_csv' );
		if ( '' !== $from ) {
			$export_args['from'] = $from;
		}
		if ( '' !== $to ) {
			$export_args['to'] = $to;
		}
		$export_url = wp_nonce_url( add_query_arg( $export_args, admin_url( 'admin-post.php' ) ), 'wpbsl_export_csv' );

		$money = function ( $v ) use ( $cur ) {
			return number_format( (float) $v, 2 ) . ' ' . $cur;
		};
		$pay_methods  = WP_Booking_Simple_Helpers::payment_methods();
		$max_owner    = 1;
		foreach ( $stats['by_owner'] as $row ) {
			$max_owner = max( $max_owner, (int) $row['nights'] );
		}
		$max_month = 1;
		foreach ( $stats['by_month'] as $count ) {
			$max_month = max( $max_month, (int) $count );
		}

		$cards = array(
			array( __( 'Bookings', 'wp-booking-simple' ), $t['bookings'], '#2271b1' ),
			array( __( 'Nights booked', 'wp-booking-simple' ), $t['nights'], '#3858e9' ),
			array( __( 'Guests', 'wp-booking-simple' ), $t['guests'], '#00a32a' ),
			array( __( 'Revenue', 'wp-booking-simple' ), $money( $t['revenue'] ), '#8B0000' ),
			array( __( 'Collected', 'wp-booking-simple' ), $money( $t['collected'] ), '#4caf50' ),
			array( __( 'Outstanding', 'wp-booking-simple' ), $money( $t['outstanding'] ), '#ff9800' ),
		);
		?>
		<div class="wrap wpbs-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Booking Dashboard', 'wp-booking-simple' ); ?></h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'wp-booking-simple' ); ?></a>
			<hr class="wp-header-end" />
			<p class="description"><?php esc_html_e( 'Insights across all non-cancelled bookings. Filter by check-in date below; the export respects the same range.', 'wp-booking-simple' ); ?></p>

			<form method="get" class="wpbs-dash-filter">
				<input type="hidden" name="page" value="wp-booking-simple-dashboard" />
				<label><?php esc_html_e( 'From', 'wp-booking-simple' ); ?>
					<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" />
				</label>
				<label><?php esc_html_e( 'To', 'wp-booking-simple' ); ?>
					<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" />
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'Apply', 'wp-booking-simple' ); ?></button>
				<?php if ( '' !== $from || '' !== $to ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-booking-simple-dashboard' ) ); ?>" class="button-link"><?php esc_html_e( 'Reset', 'wp-booking-simple' ); ?></a>
				<?php endif; ?>
			</form>

			<div class="wpbs-stat-cards">
				<?php foreach ( $cards as $c ) : ?>
					<div class="wpbs-stat-card" style="border-top:3px solid <?php echo esc_attr( $c[2] ); ?>;">
						<span class="wpbs-stat-number"><?php echo esc_html( $c[1] ); ?></span>
						<span class="wpbs-stat-label"><?php echo esc_html( $c[0] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="wpbs-dash-grid">
				<div class="wpbs-dash-panel">
					<h2><?php esc_html_e( 'Bookings per guest', 'wp-booking-simple' ); ?></h2>
					<?php if ( empty( $stats['by_guest'] ) ) : ?>
						<p class="description"><?php esc_html_e( 'No data yet.', 'wp-booking-simple' ); ?></p>
					<?php else : ?>
						<table class="widefat striped">
							<thead><tr>
								<th><?php esc_html_e( 'Guest', 'wp-booking-simple' ); ?></th>
								<th><?php esc_html_e( 'Bookings', 'wp-booking-simple' ); ?></th>
								<th><?php esc_html_e( 'Nights', 'wp-booking-simple' ); ?></th>
								<th><?php esc_html_e( 'Guests', 'wp-booking-simple' ); ?></th>
								<th><?php esc_html_e( 'Revenue', 'wp-booking-simple' ); ?></th>
							</tr></thead>
							<tbody>
							<?php foreach ( array_slice( $stats['by_guest'], 0, 15 ) as $g ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $g['name'] ? $g['name'] : $g['email'] ); ?></strong><?php echo $g['email'] ? '<br /><small>' . esc_html( $g['email'] ) . '</small>' : ''; ?></td>
									<td><?php echo esc_html( $g['bookings'] ); ?></td>
									<td><?php echo esc_html( $g['nights'] ); ?></td>
									<td><?php echo esc_html( $g['guests'] ); ?></td>
									<td><?php echo esc_html( $money( $g['revenue'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<div class="wpbs-dash-panel">
					<h2><?php esc_html_e( 'Owner usage', 'wp-booking-simple' ); ?></h2>
					<?php if ( empty( $stats['by_owner'] ) ) : ?>
						<p class="description"><?php esc_html_e( 'No data yet.', 'wp-booking-simple' ); ?></p>
					<?php else : ?>
						<?php foreach ( $stats['by_owner'] as $o ) : ?>
							<div class="wpbs-bar-row">
								<span class="wpbs-bar-label"><?php echo esc_html( $o['owner'] ? $o['owner'] : __( 'Unassigned', 'wp-booking-simple' ) ); ?></span>
								<span class="wpbs-bar-track"><span class="wpbs-bar-fill" style="width:<?php echo esc_attr( round( $o['nights'] / $max_owner * 100 ) ); ?>%;"></span></span>
								<span class="wpbs-bar-value">
									<?php
									/* translators: 1: nights, 2: bookings */
									echo esc_html( sprintf( __( '%1$d nights · %2$d bookings', 'wp-booking-simple' ), $o['nights'], $o['bookings'] ) );
									?>
								</span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

					<h2 style="margin-top:24px;"><?php esc_html_e( 'Payments by method', 'wp-booking-simple' ); ?></h2>
					<?php if ( empty( $stats['by_method'] ) ) : ?>
						<p class="description"><?php esc_html_e( 'No payments recorded yet.', 'wp-booking-simple' ); ?></p>
					<?php else : ?>
						<table class="widefat striped">
							<tbody>
							<?php foreach ( $stats['by_method'] as $m ) : ?>
								<tr>
									<td><?php echo esc_html( $m['method'] && isset( $pay_methods[ $m['method'] ] ) ? $pay_methods[ $m['method'] ] : __( 'Unspecified', 'wp-booking-simple' ) ); ?></td>
									<td><?php echo esc_html( $m['count'] ); ?>&times;</td>
									<td><strong><?php echo esc_html( $money( $m['amount'] ) ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<div class="wpbs-dash-panel">
					<h2><?php esc_html_e( 'Bookings by month (check-in)', 'wp-booking-simple' ); ?></h2>
					<?php if ( empty( $stats['by_month'] ) ) : ?>
						<p class="description"><?php esc_html_e( 'No data yet.', 'wp-booking-simple' ); ?></p>
					<?php else : ?>
						<?php foreach ( $stats['by_month'] as $month => $count ) : ?>
							<div class="wpbs-bar-row">
								<span class="wpbs-bar-label"><?php echo esc_html( date_i18n( 'M Y', strtotime( $month . '-01' ) ) ); ?></span>
								<span class="wpbs-bar-track"><span class="wpbs-bar-fill" style="width:<?php echo esc_attr( round( $count / $max_month * 100 ) ); ?>%;"></span></span>
								<span class="wpbs-bar-value"><?php echo esc_html( $count ); ?></span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

					<h2 style="margin-top:24px;"><?php esc_html_e( 'Other', 'wp-booking-simple' ); ?></h2>
					<ul class="wpbs-dash-list">
						<li><?php echo esc_html( sprintf( /* translators: %d count */ __( 'Adults: %d', 'wp-booking-simple' ), $t['adults'] ) ); ?></li>
						<li><?php echo esc_html( sprintf( /* translators: %d count */ __( 'Kids: %d', 'wp-booking-simple' ), $t['kids'] ) ); ?></li>
						<li><?php echo esc_html( sprintf( /* translators: %d count */ __( 'Bookings welcoming visitors: %d', 'wp-booking-simple' ), $t['visitors'] ) ); ?></li>
						<li><?php echo esc_html( sprintf( /* translators: %d count */ __( 'Cancelled bookings: %d', 'wp-booking-simple' ), $t['cancelled'] ) ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize a JSON string of email builder blocks into a safe, whitelisted
	 * JSON string (or '' when empty/invalid).
	 *
	 * @param string $raw JSON from the builder's hidden field.
	 * @return string
	 */
	private function sanitize_blocks_json( $raw ) {
		if ( '' === trim( (string) $raw ) ) {
			return '';
		}

		$blocks = json_decode( (string) $raw, true );

		if ( ! is_array( $blocks ) ) {
			return '';
		}

		$clean = array();

		foreach ( $blocks as $block ) {
			$type = isset( $block['type'] ) ? $block['type'] : '';

			switch ( $type ) {
				case 'heading':
				case 'text':
					$clean[] = array(
						'type' => $type,
						'text' => sanitize_textarea_field( isset( $block['text'] ) ? $block['text'] : '' ),
					);
					break;

				case 'details':
				case 'divider':
					$clean[] = array( 'type' => $type );
					break;

				case 'button':
					$url = trim( (string) ( isset( $block['url'] ) ? $block['url'] : '' ) );
					$url = ( '' !== $url && false !== strpos( $url, '{' ) ) ? sanitize_text_field( $url ) : esc_url_raw( $url );
					$clean[] = array(
						'type'  => 'button',
						'label' => sanitize_text_field( isset( $block['label'] ) ? $block['label'] : '' ),
						'url'   => $url,
					);
					break;

				case 'image':
					$clean[] = array(
						'type'  => 'image',
						'src'   => esc_url_raw( isset( $block['src'] ) ? $block['src'] : '' ),
						'alt'   => sanitize_text_field( isset( $block['alt'] ) ? $block['alt'] : '' ),
						'width' => absint( isset( $block['width'] ) ? $block['width'] : 0 ),
					);
					break;
			}
		}

		return $clean ? wp_json_encode( $clean ) : '';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( isset( $_POST['wpbsl_save_settings'] ) && check_admin_referer( 'wpbsl_settings' ) ) {
			// Validate and sanitize input.
			$price_adult              = isset( $_POST['wpbsl_price_adult'] ) ? floatval( $_POST['wpbsl_price_adult'] ) : 0;
			$price_kid                = isset( $_POST['wpbsl_price_kid'] ) ? floatval( $_POST['wpbsl_price_kid'] ) : 0;
			$currency                 = isset( $_POST['wpbsl_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_currency'] ) ) : 'CHF';
			$email_from               = isset( $_POST['wpbsl_email_from'] ) ? sanitize_email( wp_unslash( $_POST['wpbsl_email_from'] ) ) : '';
			$email_from_name          = isset( $_POST['wpbsl_email_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_email_from_name'] ) ) : '';
			$admin_notification_email = isset( $_POST['wpbsl_admin_notification_email'] ) ? sanitize_email( wp_unslash( $_POST['wpbsl_admin_notification_email'] ) ) : '';
			$chalet_capacity          = isset( $_POST['wpbsl_chalet_capacity'] ) ? absint( $_POST['wpbsl_chalet_capacity'] ) : 10;

			// Booking entry options.
			$min_nights       = isset( $_POST['wpbsl_min_nights'] ) ? max( 1, absint( $_POST['wpbsl_min_nights'] ) ) : 1;
			$max_nights       = isset( $_POST['wpbsl_max_nights'] ) ? absint( $_POST['wpbsl_max_nights'] ) : 0;
			$min_advance_days = isset( $_POST['wpbsl_min_advance_days'] ) ? absint( $_POST['wpbsl_min_advance_days'] ) : 0;
			$max_advance_days = isset( $_POST['wpbsl_max_advance_days'] ) ? absint( $_POST['wpbsl_max_advance_days'] ) : 0;
			$default_adults   = isset( $_POST['wpbsl_default_adults'] ) ? max( 1, absint( $_POST['wpbsl_default_adults'] ) ) : 2;
			$default_kids     = isset( $_POST['wpbsl_default_kids'] ) ? absint( $_POST['wpbsl_default_kids'] ) : 0;
			$require_phone    = isset( $_POST['wpbsl_require_phone'] ) ? 1 : 0;
			$show_notes       = isset( $_POST['wpbsl_show_notes'] ) ? 1 : 0;
			$auto_confirm     = isset( $_POST['wpbsl_auto_confirm'] ) ? 1 : 0;
			$show_owner       = isset( $_POST['wpbsl_show_owner'] ) ? 1 : 0;
			$owners           = isset( $_POST['wpbsl_owners'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpbsl_owners'] ) ) : '';
			$show_visitors    = isset( $_POST['wpbsl_show_visitors'] ) ? 1 : 0;

			// SMTP / email delivery options.
			$smtp_enabled    = isset( $_POST['wpbsl_smtp_enabled'] ) ? 1 : 0;
			$smtp_host       = isset( $_POST['wpbsl_smtp_host'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_smtp_host'] ) ) : '';
			$smtp_port       = isset( $_POST['wpbsl_smtp_port'] ) ? absint( $_POST['wpbsl_smtp_port'] ) : 587;
			$smtp_encryption = isset( $_POST['wpbsl_smtp_encryption'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_smtp_encryption'] ) ) : 'tls';
			$smtp_encryption = in_array( $smtp_encryption, array( 'none', 'ssl', 'tls' ), true ) ? $smtp_encryption : 'tls';
			$smtp_auth       = isset( $_POST['wpbsl_smtp_auth'] ) ? 1 : 0;
			$smtp_username   = isset( $_POST['wpbsl_smtp_username'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_smtp_username'] ) ) : '';
			// Password: keep the stored value when the field is left blank.
			$smtp_password_input = isset( $_POST['wpbsl_smtp_password'] ) ? trim( (string) wp_unslash( $_POST['wpbsl_smtp_password'] ) ) : '';
			$smtp_password       = '' === $smtp_password_input ? (string) get_option( 'wpbsl_smtp_password', '' ) : $smtp_password_input;

			// TWINT / Swiss QR-bill payment options.
			$qr_enabled = isset( $_POST['wpbsl_qr_enabled'] ) ? 1 : 0;
			$qr_name    = isset( $_POST['wpbsl_qr_creditor_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_qr_creditor_name'] ) ) : '';
			$qr_iban    = isset( $_POST['wpbsl_qr_creditor_iban'] ) ? WP_Booking_Simple_Helpers::normalize_iban( wp_unslash( $_POST['wpbsl_qr_creditor_iban'] ) ) : '';
			$qr_address = isset( $_POST['wpbsl_qr_creditor_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_qr_creditor_address'] ) ) : '';
			$qr_city    = isset( $_POST['wpbsl_qr_creditor_city'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_qr_creditor_city'] ) ) : '';
			$qr_country = isset( $_POST['wpbsl_qr_creditor_country'] ) ? strtoupper( substr( sanitize_text_field( wp_unslash( $_POST['wpbsl_qr_creditor_country'] ) ), 0, 2 ) ) : 'CH';
			$qr_bank    = isset( $_POST['wpbsl_qr_bank_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_qr_bank_name'] ) ) : '';
			$qr_paylink = isset( $_POST['wpbsl_qr_twint_paylink'] ) ? esc_url_raw( wp_unslash( $_POST['wpbsl_qr_twint_paylink'] ) ) : '';
			$qr_tlabel  = isset( $_POST['wpbsl_qr_twint_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wpbsl_qr_twint_label'] ) ) : '';

			// Email template options. Subjects are plain text; bodies allow safe HTML.
			// Saving a blank value resets that template to its built-in default.
			$template_fields = array(
				'wpbsl_email_confirmation_subject' => 'subject',
				'wpbsl_email_confirmation_body'    => 'body',
				'wpbsl_email_cancellation_subject' => 'subject',
				'wpbsl_email_cancellation_body'    => 'body',
				'wpbsl_email_reminder_subject'     => 'subject',
				'wpbsl_email_reminder_body'        => 'body',
				'wpbsl_email_admin_subject'        => 'subject',
				'wpbsl_email_admin_body'           => 'body',
			);
			$template_values = array();
			foreach ( $template_fields as $field_key => $field_type ) {
				if ( ! isset( $_POST[ $field_key ] ) ) {
					$template_values[ $field_key ] = '';
					continue;
				}
				$raw = wp_unslash( $_POST[ $field_key ] );
				$template_values[ $field_key ] = ( 'body' === $field_type ) ? wp_kses_post( $raw ) : sanitize_text_field( $raw );
			}

			// Visual builder blocks (JSON) per template.
			$block_values = array();
			foreach ( array( 'confirmation', 'cancellation', 'reminder', 'admin' ) as $slug ) {
				$block_key                   = 'wpbsl_email_' . $slug . '_blocks';
				$block_raw                   = isset( $_POST[ $block_key ] ) ? wp_unslash( $_POST[ $block_key ] ) : '';
				$block_values[ $block_key ]  = $this->sanitize_blocks_json( $block_raw );
			}

			// Validate emails.
			if ( ! is_email( $email_from ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid email from address.', 'wp-booking-simple' ) . '</p></div>';
			} elseif ( ! empty( $admin_notification_email ) && ! is_email( $admin_notification_email ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid admin notification email address.', 'wp-booking-simple' ) . '</p></div>';
			} elseif ( $max_nights > 0 && $max_nights < $min_nights ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Maximum nights cannot be less than minimum nights.', 'wp-booking-simple' ) . '</p></div>';
			} elseif ( $max_advance_days > 0 && $max_advance_days < $min_advance_days ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Maximum advance days cannot be less than minimum advance days.', 'wp-booking-simple' ) . '</p></div>';
			} elseif ( $default_adults + $default_kids > $chalet_capacity ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Default guests cannot exceed the chalet capacity.', 'wp-booking-simple' ) . '</p></div>';
			} elseif ( $smtp_enabled && '' === $smtp_host ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Please enter an SMTP host (e.g. smtp.gmail.com) to enable SMTP delivery.', 'wp-booking-simple' ) . '</p></div>';
			} elseif ( $qr_enabled && ! WP_Booking_Simple_Helpers::is_valid_ch_iban( $qr_iban ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Please enter a valid Swiss/Liechtenstein IBAN (CHâ¦ or LIâ¦) to enable TWINT / QR-bill payments.', 'wp-booking-simple' ) . '</p></div>';
			} else {
				update_option( 'wpbsl_price_adult', $price_adult );
				update_option( 'wpbsl_price_kid', $price_kid );
				update_option( 'wpbsl_currency', $currency );
				update_option( 'wpbsl_email_from', $email_from );
				update_option( 'wpbsl_email_from_name', $email_from_name );
				update_option( 'wpbsl_admin_notification_email', $admin_notification_email );
				update_option( 'wpbsl_chalet_capacity', $chalet_capacity );
				update_option( 'wpbsl_min_nights', $min_nights );
				update_option( 'wpbsl_max_nights', $max_nights );
				update_option( 'wpbsl_min_advance_days', $min_advance_days );
				update_option( 'wpbsl_max_advance_days', $max_advance_days );
				update_option( 'wpbsl_default_adults', $default_adults );
				update_option( 'wpbsl_default_kids', $default_kids );
				update_option( 'wpbsl_require_phone', $require_phone );
				update_option( 'wpbsl_show_notes', $show_notes );
				update_option( 'wpbsl_auto_confirm', $auto_confirm );
				update_option( 'wpbsl_show_owner', $show_owner );
				update_option( 'wpbsl_owners', $owners );
				update_option( 'wpbsl_show_visitors', $show_visitors );
				update_option( 'wpbsl_smtp_enabled', $smtp_enabled );
				update_option( 'wpbsl_smtp_host', $smtp_host );
				update_option( 'wpbsl_smtp_port', $smtp_port );
				update_option( 'wpbsl_smtp_encryption', $smtp_encryption );
				update_option( 'wpbsl_smtp_auth', $smtp_auth );
				update_option( 'wpbsl_smtp_username', $smtp_username );
				update_option( 'wpbsl_smtp_password', $smtp_password );
				update_option( 'wpbsl_qr_enabled', $qr_enabled );
				update_option( 'wpbsl_qr_creditor_name', $qr_name );
				update_option( 'wpbsl_qr_creditor_iban', $qr_iban );
				update_option( 'wpbsl_qr_creditor_address', $qr_address );
				update_option( 'wpbsl_qr_creditor_city', $qr_city );
				update_option( 'wpbsl_qr_creditor_country', $qr_country );
				update_option( 'wpbsl_qr_bank_name', $qr_bank );
				update_option( 'wpbsl_qr_twint_paylink', $qr_paylink );
				update_option( 'wpbsl_qr_twint_label', $qr_tlabel );
				foreach ( $template_values as $field_key => $field_value ) {
					update_option( $field_key, $field_value );
				}
				foreach ( $block_values as $field_key => $field_value ) {
					update_option( $field_key, $field_value );
				}
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wp-booking-simple' ) . '</p></div>';
			}
		}

		$price_adult     = get_option( 'wpbsl_price_adult', 50 );
		$price_kid       = get_option( 'wpbsl_price_kid', 25 );
		$currency        = get_option( 'wpbsl_currency', 'CHF' );
		$email_from      = get_option( 'wpbsl_email_from', get_option( 'admin_email' ) );
		$email_from_name = get_option( 'wpbsl_email_from_name', get_bloginfo( 'name' ) );

		// Which settings tab to show. Posted back on save so the same tab stays open.
		$tabs       = array(
			'general'   => __( 'General', 'wp-booking-simple' ),
			'rules'     => __( 'Booking Rules', 'wp-booking-simple' ),
			'form'      => __( 'Booking Form', 'wp-booking-simple' ),
			'email'     => __( 'Email Delivery', 'wp-booking-simple' ),
			'templates' => __( 'Email Templates', 'wp-booking-simple' ),
		);
		$active_tab = isset( $_POST['wpbsl_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['wpbsl_active_tab'] ) ) : 'general';
		$active     = isset( $tabs[ $active_tab ] ) ? $active_tab : 'general';
		?>
		<div class="wrap wpbs-admin-wrap wpbs-settings">
			<h1><?php esc_html_e( 'Booking Settings', 'wp-booking-simple' ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'wpbsl_settings' ); ?>
				<input type="hidden" name="wpbsl_active_tab" id="wpbsl_active_tab" value="<?php echo esc_attr( $active ); ?>" />

				<nav class="nav-tab-wrapper wpbs-settings-tabs">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<a href="#<?php echo esc_attr( $slug ); ?>" class="nav-tab<?php echo $active === $slug ? ' nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</nav>

				<div class="wpbs-tab-panel<?php echo 'general' === $active ? ' is-active' : ''; ?>" data-tab="general">
				<h2 class="title"><?php esc_html_e( 'Pricing & Notifications', 'wp-booking-simple' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wpbsl_price_adult"><?php esc_html_e( 'Price per Adult (per night)', 'wp-booking-simple' ); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" id="wpbsl_price_adult" name="wpbsl_price_adult" value="<?php echo esc_attr( $price_adult ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpbsl_price_kid"><?php esc_html_e( 'Price per Kid (per night)', 'wp-booking-simple' ); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" id="wpbsl_price_kid" name="wpbsl_price_kid" value="<?php echo esc_attr( $price_kid ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpbsl_currency"><?php esc_html_e( 'Currency', 'wp-booking-simple' ); ?></label>
						</th>
						<td>
							<input type="text" id="wpbsl_currency" name="wpbsl_currency" value="<?php echo esc_attr( $currency ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpbsl_email_from"><?php esc_html_e( 'Email From Address', 'wp-booking-simple' ); ?></label>
						</th>
						<td>
							<input type="email" id="wpbsl_email_from" name="wpbsl_email_from" value="<?php echo esc_attr( $email_from ); ?>" class="regular-text" />
						</td>
					</tr>
				<tr>
					<th scope="row">
						<label for="wpbsl_email_from_name"><?php esc_html_e( 'Email From Name', 'wp-booking-simple' ); ?></label>
					</th>
					<td>
						<input type="text" id="wpbsl_email_from_name" name="wpbsl_email_from_name" value="<?php echo esc_attr( $email_from_name ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpbsl_admin_notification_email"><?php esc_html_e( 'Admin Notification Email', 'wp-booking-simple' ); ?></label>
					</th>
					<td>
						<input type="email" id="wpbsl_admin_notification_email" name="wpbsl_admin_notification_email" value="<?php echo esc_attr( get_option( 'wpbsl_admin_notification_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Email address to receive notifications when new bookings are made.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpbsl_chalet_capacity"><?php esc_html_e( 'Chalet Maximum Capacity', 'wp-booking-simple' ); ?></label>
					</th>
					<td>
						<input type="number" id="wpbsl_chalet_capacity" name="wpbsl_chalet_capacity" value="<?php echo esc_attr( get_option( 'wpbsl_chalet_capacity', 10 ) ); ?>" min="1" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Maximum number of guests (adults + kids) that can be accommodated.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'TWINT / QR-bill Payments', 'wp-booking-simple' ); ?></h2>
			<p class="description" style="max-width:640px;">
				<?php esc_html_e( 'Show a Swiss QR-bill on the guest\'s booking-management page so they can pay the outstanding balance by scanning it with TWINT or any Swiss banking app. This is free — it only needs your IBAN, with no merchant account or transaction fees.', 'wp-booking-simple' ); ?>
			</p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable QR payments', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_qr_enabled" value="1" <?php checked( 1, (int) get_option( 'wpbsl_qr_enabled', 0 ) ); ?> />
							<?php esc_html_e( 'Offer TWINT / QR-bill payment on the manage-booking page.', 'wp-booking-simple' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_creditor_name"><?php esc_html_e( 'Account holder (name)', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_qr_creditor_name" name="wpbsl_qr_creditor_name" value="<?php echo esc_attr( get_option( 'wpbsl_qr_creditor_name', '' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_creditor_iban"><?php esc_html_e( 'IBAN', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="text" id="wpbsl_qr_creditor_iban" name="wpbsl_qr_creditor_iban" value="<?php echo esc_attr( get_option( 'wpbsl_qr_creditor_iban', '' ) ); ?>" class="regular-text" placeholder="CH93 0076 2011 6238 5295 7" />
						<p class="description"><?php esc_html_e( 'Your Swiss or Liechtenstein IBAN (CH… or LI…).', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_creditor_address"><?php esc_html_e( 'Address (street & number)', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_qr_creditor_address" name="wpbsl_qr_creditor_address" value="<?php echo esc_attr( get_option( 'wpbsl_qr_creditor_address', '' ) ); ?>" class="regular-text" placeholder="Musterstrasse 1" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_creditor_city"><?php esc_html_e( 'Address (postal code & town)', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_qr_creditor_city" name="wpbsl_qr_creditor_city" value="<?php echo esc_attr( get_option( 'wpbsl_qr_creditor_city', '' ) ); ?>" class="regular-text" placeholder="8000 Zürich" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_creditor_country"><?php esc_html_e( 'Country code', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_qr_creditor_country" name="wpbsl_qr_creditor_country" value="<?php echo esc_attr( get_option( 'wpbsl_qr_creditor_country', 'CH' ) ); ?>" class="small-text" maxlength="2" placeholder="CH" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_bank_name"><?php esc_html_e( 'Bank name (optional)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="text" id="wpbsl_qr_bank_name" name="wpbsl_qr_bank_name" value="<?php echo esc_attr( get_option( 'wpbsl_qr_bank_name', '' ) ); ?>" class="regular-text" placeholder="Raiffeisenbank Pilatus" />
						<p class="description"><?php esc_html_e( 'Shown in the payment details on the confirmation email and at checkout.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_twint_paylink"><?php esc_html_e( 'TWINT Pay link (optional)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="url" id="wpbsl_qr_twint_paylink" name="wpbsl_qr_twint_paylink" value="<?php echo esc_attr( get_option( 'wpbsl_qr_twint_paylink', '' ) ); ?>" class="regular-text" placeholder="https://go.twint.ch/..." />
						<p class="description"><?php esc_html_e( 'Your TWINT pay-link URL (from portal.twint.ch). Shown as a \'Pay with TWINT\' button alongside the QR code.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_qr_twint_label"><?php esc_html_e( 'TWINT link text (optional)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="text" id="wpbsl_qr_twint_label" name="wpbsl_qr_twint_label" value="<?php echo esc_attr( get_option( 'wpbsl_qr_twint_label', '' ) ); ?>" class="regular-text" placeholder="Twint-Paylink Chalet De Simoni" />
						<p class="description"><?php esc_html_e( 'The clickable text for the {payment_twint} email link. Defaults to "Pay with TWINT".', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
			</table>

			</div><!-- /general -->

			<div class="wpbs-tab-panel<?php echo 'rules' === $active ? ' is-active' : ''; ?>" data-tab="rules">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wpbsl_min_nights"><?php esc_html_e( 'Minimum Stay (nights)', 'wp-booking-simple' ); ?></label></th>
					<td><input type="number" id="wpbsl_min_nights" name="wpbsl_min_nights" value="<?php echo esc_attr( get_option( 'wpbsl_min_nights', 1 ) ); ?>" min="1" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_max_nights"><?php esc_html_e( 'Maximum Stay (nights)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="number" id="wpbsl_max_nights" name="wpbsl_max_nights" value="<?php echo esc_attr( get_option( 'wpbsl_max_nights', 0 ) ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'Set to 0 for no maximum.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_min_advance_days"><?php esc_html_e( 'Minimum Advance Notice (days)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="number" id="wpbsl_min_advance_days" name="wpbsl_min_advance_days" value="<?php echo esc_attr( get_option( 'wpbsl_min_advance_days', 0 ) ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'Earliest a guest may check in, in days from today. 0 = today allowed.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_max_advance_days"><?php esc_html_e( 'Booking Window (days ahead)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="number" id="wpbsl_max_advance_days" name="wpbsl_max_advance_days" value="<?php echo esc_attr( get_option( 'wpbsl_max_advance_days', 0 ) ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'How far ahead guests may book, in days. 0 = no limit.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'New Bookings', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_auto_confirm" value="1" <?php checked( 1, (int) get_option( 'wpbsl_auto_confirm', 0 ) ); ?> />
							<?php esc_html_e( 'Confirm new bookings automatically (skip the pending step).', 'wp-booking-simple' ); ?>
						</label>
					</td>
				</tr>
			</table>

			</div><!-- /rules -->

			<div class="wpbs-tab-panel<?php echo 'form' === $active ? ' is-active' : ''; ?>" data-tab="form">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wpbsl_default_adults"><?php esc_html_e( 'Default Adults', 'wp-booking-simple' ); ?></label></th>
					<td><input type="number" id="wpbsl_default_adults" name="wpbsl_default_adults" value="<?php echo esc_attr( get_option( 'wpbsl_default_adults', 2 ) ); ?>" min="1" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_default_kids"><?php esc_html_e( 'Default Kids', 'wp-booking-simple' ); ?></label></th>
					<td><input type="number" id="wpbsl_default_kids" name="wpbsl_default_kids" value="<?php echo esc_attr( get_option( 'wpbsl_default_kids', 0 ) ); ?>" min="0" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Phone Field', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_require_phone" value="1" <?php checked( 1, (int) get_option( 'wpbsl_require_phone', 0 ) ); ?> />
							<?php esc_html_e( 'Require the guest to provide a phone number.', 'wp-booking-simple' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notes Field', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_show_notes" value="1" <?php checked( 1, (int) get_option( 'wpbsl_show_notes', 1 ) ); ?> />
							<?php esc_html_e( 'Show the optional notes field on the booking form.', 'wp-booking-simple' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Owner Field', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_show_owner" value="1" <?php checked( 1, (int) get_option( 'wpbsl_show_owner', 1 ) ); ?> />
							<?php esc_html_e( 'Show an "Owner" dropdown on the booking form.', 'wp-booking-simple' ); ?>
						</label>
						<p style="margin-top:8px;">
							<label for="wpbsl_owners"><?php esc_html_e( 'Owner names (one per line):', 'wp-booking-simple' ); ?></label><br />
							<textarea id="wpbsl_owners" name="wpbsl_owners" rows="4" class="large-text code" placeholder="Alberto&#10;Luca"><?php echo esc_textarea( get_option( 'wpbsl_owners', '' ) ); ?></textarea>
						</p>
						<p class="description"><?php esc_html_e( 'The dropdown only appears when at least one name is listed. Available in emails as {owner}.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Visitors Field', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_show_visitors" value="1" <?php checked( 1, (int) get_option( 'wpbsl_show_visitors', 1 ) ); ?> />
							<?php esc_html_e( 'Show a "Visitors welcome?" yes/no field on the booking form.', 'wp-booking-simple' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Available in emails as {visitors_welcome}.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
			</table>

			</div><!-- /form -->

			<div class="wpbs-tab-panel<?php echo 'email' === $active ? ' is-active' : ''; ?>" data-tab="email">
			<h2 class="title"><?php esc_html_e( 'Email Delivery (SMTP)', 'wp-booking-simple' ); ?></h2>
			<p class="description" style="max-width:640px;">
				<?php esc_html_e( 'Booking confirmation and notification emails are sent automatically through WordPress. By default WordPress uses your server\'s mail, which is often unreliable. Enable SMTP below to send through a real mailbox such as Gmail / Google Workspace for dependable delivery.', 'wp-booking-simple' ); ?>
			</p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'SMTP Delivery', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_smtp_enabled" value="1" <?php checked( 1, (int) get_option( 'wpbsl_smtp_enabled', 0 ) ); ?> />
							<?php esc_html_e( 'Send emails through an external SMTP server.', 'wp-booking-simple' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'For Gmail: host smtp.gmail.com, port 587 (TLS), your full address as the username, and a Google "App Password" (not your normal password) as the password.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_host"><?php esc_html_e( 'SMTP Host', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_smtp_host" name="wpbsl_smtp_host" value="<?php echo esc_attr( get_option( 'wpbsl_smtp_host', '' ) ); ?>" class="regular-text" placeholder="smtp.gmail.com" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_port"><?php esc_html_e( 'SMTP Port', 'wp-booking-simple' ); ?></label></th>
					<td><input type="number" id="wpbsl_smtp_port" name="wpbsl_smtp_port" value="<?php echo esc_attr( get_option( 'wpbsl_smtp_port', 587 ) ); ?>" min="1" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_encryption"><?php esc_html_e( 'Encryption', 'wp-booking-simple' ); ?></label></th>
					<td>
						<?php $enc = get_option( 'wpbsl_smtp_encryption', 'tls' ); ?>
						<select id="wpbsl_smtp_encryption" name="wpbsl_smtp_encryption">
							<option value="tls" <?php selected( 'tls', $enc ); ?>><?php esc_html_e( 'TLS (recommended, port 587)', 'wp-booking-simple' ); ?></option>
							<option value="ssl" <?php selected( 'ssl', $enc ); ?>><?php esc_html_e( 'SSL (port 465)', 'wp-booking-simple' ); ?></option>
							<option value="none" <?php selected( 'none', $enc ); ?>><?php esc_html_e( 'None', 'wp-booking-simple' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Authentication', 'wp-booking-simple' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_smtp_auth" value="1" <?php checked( 1, (int) get_option( 'wpbsl_smtp_auth', 1 ) ); ?> />
							<?php esc_html_e( 'Use a username and password to authenticate (required for Gmail).', 'wp-booking-simple' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_username"><?php esc_html_e( 'SMTP Username', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_smtp_username" name="wpbsl_smtp_username" value="<?php echo esc_attr( get_option( 'wpbsl_smtp_username', '' ) ); ?>" class="regular-text" placeholder="you@gmail.com" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_password"><?php esc_html_e( 'SMTP Password', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="password" id="wpbsl_smtp_password" name="wpbsl_smtp_password" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo get_option( 'wpbsl_smtp_password', '' ) ? esc_attr__( '•••••••• (saved — leave blank to keep)', 'wp-booking-simple' ) : ''; ?>" />
						<p class="description"><?php esc_html_e( 'Stored in your database. Leave blank to keep the current password.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Send a Test Email', 'wp-booking-simple' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Save your settings first, then send a test email to confirm delivery works.', 'wp-booking-simple' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wpbsl_test_email_to"><?php esc_html_e( 'Send test to', 'wp-booking-simple' ); ?></label></th>
					<td>
						<input type="email" id="wpbsl_test_email_to" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text" />
						<button type="button" class="button button-secondary" id="wpbs-send-test-email" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp-booking-simple-admin' ) ); ?>"><?php esc_html_e( 'Send Test Email', 'wp-booking-simple' ); ?></button>
						<span id="wpbs-test-email-result" style="margin-left:10px;font-weight:600;"></span>
					</td>
				</tr>
			</table>
			</div><!-- /email -->

			<?php
			$email = wp_booking_simple()->email;
			// Effective value: saved text, or the built-in default when blank.
			$eff = function ( $key, $default ) {
				$v = (string) get_option( $key, '' );
				return '' !== trim( $v ) ? $v : $default;
			};
			?>
			<div class="wpbs-tab-panel<?php echo 'templates' === $active ? ' is-active' : ''; ?>" data-tab="templates">
			<p class="description" style="max-width:640px;">
				<?php esc_html_e( 'Customise the wording of the automatic emails. Clear a field and save to restore its default. You can use these merge tags, which are replaced with each booking\'s details:', 'wp-booking-simple' ); ?>
			</p>
			<p class="description" style="max-width:760px;">
				<code>{site_name}</code> <code>{guest_name}</code> <code>{first_name}</code> <code>{last_name}</code> <code>{guest_email}</code> <code>{guest_phone}</code> <code>{check_in}</code> <code>{check_out}</code> <code>{adults}</code> <code>{kids}</code> <code>{guests}</code> <code>{total_price}</code> <code>{status}</code> <code>{owner}</code> <code>{visitors_welcome}</code> <code>{payment_status}</code> <code>{payment_method}</code> <code>{amount_paid}</code> <code>{amount_due}</code> <code>{notes}</code> <code>{booking_details}</code> <code>{payment_info}</code> <code>{payment_account}</code> <code>{payment_bank}</code> <code>{payment_iban}</code> <code>{payment_twint}</code> <code>{manage_link}</code> <code>{manage_url}</code> <code>{admin_link}</code>
			</p>
			<?php
			$de_starters = array(
				'confirmation' => array(
					'subject' => 'Buchungsbestätigung – {site_name}',
					'body'    => "Liebe/r {guest_name}

vielen Dank für Ihre Buchung – wir freuen uns sehr auf Ihren Aufenthalt!

{booking_details}

{payment_info}

Ihre Buchung können Sie jederzeit über den folgenden Link ansehen oder anpassen:

{manage_link}

Herzliche Grüsse
{site_name}",
				),
				'cancellation' => array(
					'subject' => 'Buchung storniert – {site_name}',
					'body'    => "Liebe/r {guest_name}

Ihre Buchung wurde storniert. Falls dies ein Versehen war, melden Sie sich bitte bei uns.

Herzliche Grüsse
{site_name}",
				),
				'reminder'     => array(
					'subject' => 'Zahlungserinnerung – {site_name}',
					'body'    => "Liebe/r {guest_name}

eine freundliche Erinnerung an den offenen Betrag für Ihre Buchung.

{booking_details}

Bezahlter Betrag: {amount_paid}
Offener Betrag: {amount_due}

{payment_info}

Herzliche Grüsse
{site_name}",
				),
			);
			?>
			<p style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:12px 16px;max-width:760px;">
				<button type="button" class="button button-secondary" id="wpbs-insert-de" data-templates="<?php echo esc_attr( base64_encode( wp_json_encode( $de_starters ) ) ); ?>"><?php esc_html_e( 'Insert German starter templates', 'wp-booking-simple' ); ?></button>
				<span class="description" style="margin-left:8px;"><?php esc_html_e( 'Fills the guest confirmation, cancellation and payment-reminder emails with ready-made German text (used as the fallback body — you can still edit it). Then click Save Settings.', 'wp-booking-simple' ); ?></span>
				<span id="wpbs-insert-de-msg" style="margin-left:8px;font-weight:600;"></span>
			</p>
			<table class="form-table">
				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Guest Confirmation', 'wp-booking-simple' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_confirmation_subject"><?php esc_html_e( 'Subject', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_confirmation_subject" name="wpbsl_email_confirmation_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_confirmation_subject', $email->default_confirmation_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-simple' ); ?></th>
					<td>
						<input type="hidden" class="wpbs-builder-data" name="wpbsl_email_confirmation_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_confirmation_blocks', '' ) ); ?>" />
						<div class="wpbs-builder" data-slug="confirmation"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_confirmation_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_confirmation_body" name="wpbsl_email_confirmation_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_confirmation_body', $email->default_confirmation_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Guest Cancellation', 'wp-booking-simple' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_cancellation_subject"><?php esc_html_e( 'Subject', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_cancellation_subject" name="wpbsl_email_cancellation_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_cancellation_subject', $email->default_cancellation_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-simple' ); ?></th>
					<td>
						<input type="hidden" class="wpbs-builder-data" name="wpbsl_email_cancellation_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_cancellation_blocks', '' ) ); ?>" />
						<div class="wpbs-builder" data-slug="cancellation"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_cancellation_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_cancellation_body" name="wpbsl_email_cancellation_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_cancellation_body', $email->default_cancellation_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Payment Reminder', 'wp-booking-simple' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_reminder_subject"><?php esc_html_e( 'Subject', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_reminder_subject" name="wpbsl_email_reminder_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_reminder_subject', $email->default_reminder_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-simple' ); ?></th>
					<td>
						<input type="hidden" class="wpbs-builder-data" name="wpbsl_email_reminder_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_reminder_blocks', '' ) ); ?>" />
						<div class="wpbs-builder" data-slug="reminder"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_reminder_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_reminder_body" name="wpbsl_email_reminder_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_reminder_body', $email->default_reminder_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above. Send reminders from any booking via "View / Edit".', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Admin Notification', 'wp-booking-simple' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_admin_subject"><?php esc_html_e( 'Subject', 'wp-booking-simple' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_admin_subject" name="wpbsl_email_admin_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_admin_subject', $email->default_admin_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-simple' ); ?></th>
					<td>
						<input type="hidden" class="wpbs-builder-data" name="wpbsl_email_admin_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_admin_blocks', '' ) ); ?>" />
						<div class="wpbs-builder" data-slug="admin"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_admin_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-simple' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_admin_body" name="wpbsl_email_admin_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_admin_body', $email->default_admin_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above.', 'wp-booking-simple' ); ?></p>
					</td>
				</tr>
			</table>
			</div><!-- /templates -->

			<?php submit_button( __( 'Save Settings', 'wp-booking-simple' ), 'primary', 'wpbsl_save_settings' ); ?>
		</form>

		<script>
		( function () {
			var btn = document.getElementById( 'wpbs-send-test-email' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				var out = document.getElementById( 'wpbs-test-email-result' );
				var to = document.getElementById( 'wpbsl_test_email_to' ).value;
				btn.disabled = true;
				out.style.color = '#666';
				out.textContent = <?php echo wp_json_encode( __( 'Sending…', 'wp-booking-simple' ) ); ?>;
				var body = new URLSearchParams();
				body.append( 'action', 'wpbsl_send_test_email' );
				body.append( 'nonce', btn.getAttribute( 'data-nonce' ) );
				body.append( 'email', to );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						out.style.color = res.success ? '#0a7d28' : '#b32d2e';
						out.textContent = ( res.data && res.data.message ) ? res.data.message : '';
					} )
					.catch( function () {
						out.style.color = '#b32d2e';
						out.textContent = <?php echo wp_json_encode( __( 'Request failed. Please try again.', 'wp-booking-simple' ) ); ?>;
					} )
					.finally( function () { btn.disabled = false; } );
			} );
		} )();
		</script>
	</div>
	<?php
	}
}

