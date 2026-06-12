<?php
/**
 * Admin class for managing bookings
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Admin Class
 */
class WP_Booking_System_Luca_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_smtp_notice' ) );
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

		if ( ! $screen || false === strpos( (string) $screen->id, 'wp-booking-system' ) ) {
			return;
		}

		// Don't show it on the Settings screen itself — the guidance is already there.
		if ( false !== strpos( (string) $screen->id, 'wp-booking-system-settings' ) ) {
			return;
		}

		if ( (int) get_option( 'wpbsl_smtp_enabled', 0 ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=wp-booking-system-settings' );
		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'WP booking Luca is using your server\'s default mailer, which can fail to deliver booking emails. For reliable delivery (e.g. through Gmail), enable SMTP in', 'wp-booking-system-luca' ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'wp-booking-system-luca' ); ?></a>.
			</p>
		</div>
		<?php
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Bookings', 'wp-booking-system-luca' ),
			__( 'WP booking Luca', 'wp-booking-system-luca' ),
			'manage_options',
			'wp-booking-system-luca',
			array( $this, 'render_calendar_page' ),
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'wp-booking-system-luca',
			__( 'All Bookings', 'wp-booking-system-luca' ),
			__( 'All Bookings', 'wp-booking-system-luca' ),
			'manage_options',
			'wp-booking-system-list',
			array( $this, 'render_list_page' )
		);

		add_submenu_page(
			'wp-booking-system-luca',
			__( 'Settings', 'wp-booking-system-luca' ),
			__( 'Settings', 'wp-booking-system-luca' ),
			'manage_options',
			'wp-booking-system-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Our screen hooks are toplevel_page_wp-booking-system-luca and
		// wp-booking-luca_page_wp-booking-system-{list,settings}; they all
		// share the "wp-booking-system" stem.
		if ( strpos( $hook, 'wp-booking-system' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'wp-booking-system-luca-admin',
			WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WP_BOOKING_SYSTEM_LUCA_VERSION
		);

		wp_enqueue_script(
			'wp-booking-system-luca-admin',
			WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'fullcalendar' ),
			WP_BOOKING_SYSTEM_LUCA_VERSION,
			true
		);

		wp_localize_script(
			'wp-booking-system-luca-admin',
			'wpbslAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wp-booking-system-luca-admin' ),
				'currency' => get_option( 'wpbsl_currency', 'CHF' ),
				'i18n'    => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this booking?', 'wp-booking-system-luca' ),
					'confirmCancel'  => __( 'Cancel this booking and email the guest?', 'wp-booking-system-luca' ),
					'genericError'   => __( 'An error occurred. Please try again.', 'wp-booking-system-luca' ),
				),
			)
		);

		// Email template block builder (settings screen only).
		if ( false !== strpos( $hook, 'wp-booking-system-settings' ) ) {
			wp_enqueue_script(
				'wp-booking-system-luca-template-builder',
				WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/js/admin-template-builder.js',
				array(),
				WP_BOOKING_SYSTEM_LUCA_VERSION,
				true
			);

			wp_localize_script(
				'wp-booking-system-luca-template-builder',
				'wpbslBuilder',
				array(
					'mergeTags' => array(
						'{site_name}', '{guest_name}', '{first_name}', '{last_name}', '{guest_email}', '{guest_phone}',
						'{check_in}', '{check_out}', '{adults}', '{kids}', '{guests}', '{total_price}', '{status}',
						'{owner}', '{visitors_welcome}', '{notes}', '{booking_details}', '{manage_url}', '{manage_link}', '{admin_link}',
					),
					'i18n'      => array(
						'addBlock'      => __( 'Add block', 'wp-booking-system-luca' ),
						'text'          => __( 'Text', 'wp-booking-system-luca' ),
						'heading'       => __( 'Heading', 'wp-booking-system-luca' ),
						'details'       => __( 'Booking details', 'wp-booking-system-luca' ),
						'button'        => __( 'Button', 'wp-booking-system-luca' ),
						'image'         => __( 'Image', 'wp-booking-system-luca' ),
						'divider'       => __( 'Divider', 'wp-booking-system-luca' ),
						'remove'        => __( 'Remove', 'wp-booking-system-luca' ),
						'drag'          => __( 'Drag to reorder', 'wp-booking-system-luca' ),
						'label'         => __( 'Button label', 'wp-booking-system-luca' ),
						'url'           => __( 'Button URL (e.g. {manage_url})', 'wp-booking-system-luca' ),
						'imageUrl'      => __( 'Image URL', 'wp-booking-system-luca' ),
						'altText'       => __( 'Alt text', 'wp-booking-system-luca' ),
						'widthPx'       => __( 'Width (px, optional)', 'wp-booking-system-luca' ),
						'detailsNote'   => __( 'The styled booking-details box is inserted here.', 'wp-booking-system-luca' ),
						'dividerNote'   => __( 'A horizontal divider.', 'wp-booking-system-luca' ),
						'insertTag'     => __( 'Insert tag', 'wp-booking-system-luca' ),
						'empty'         => __( 'No blocks yet — add one to start building this email.', 'wp-booking-system-luca' ),
					),
				)
			);
		}

		// FullCalendar (bundled v6 global build; injects its own styles, no CDN).
		wp_enqueue_script(
			'fullcalendar',
			WP_BOOKING_SYSTEM_LUCA_PLUGIN_URL . 'assets/vendor/fullcalendar/index.global.min.js',
			array(),
			'6.1.10',
			true
		);
	}

	/**
	 * Render calendar page.
	 */
	public function render_calendar_page() {
		$bookings = wp_booking_system_luca()->database->get_bookings();

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
			array( __( 'Upcoming stays', 'wp-booking-system-luca' ), $upcoming, '#2271b1' ),
			array( __( 'Pending', 'wp-booking-system-luca' ), $counts['pending'], '#ff9800' ),
			array( __( 'Confirmed', 'wp-booking-system-luca' ), $counts['confirmed'], '#4caf50' ),
			array( __( 'Total bookings', 'wp-booking-system-luca' ), count( $bookings ), '#757575' ),
		);
		?>
		<div class="wrap wpbs-admin-wrap">
			<h1><?php esc_html_e( 'Booking Calendar', 'wp-booking-system-luca' ); ?></h1>

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
					printf( esc_html__( 'Next check-in: %s', 'wp-booking-system-luca' ), '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $next_in ) ) ) . '</strong>' );
					?>
				</p>
			<?php endif; ?>

			<div class="wpbs-calendar-legend">
				<span><span class="wpbs-legend-dot" style="background:#ff9800;"></span><?php esc_html_e( 'Pending', 'wp-booking-system-luca' ); ?></span>
				<span><span class="wpbs-legend-dot" style="background:#4caf50;"></span><?php esc_html_e( 'Confirmed', 'wp-booking-system-luca' ); ?></span>
				<span><span class="wpbs-legend-dot" style="background:#f44336;"></span><?php esc_html_e( 'Cancelled', 'wp-booking-system-luca' ); ?></span>
				<span class="description"><?php esc_html_e( 'Click an entry for details.', 'wp-booking-system-luca' ); ?></span>
			</div>

			<div id="wpbs-calendar" data-initial-date="<?php echo esc_attr( $next_in ? $next_in : $today ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Render list page.
	 */
	public function render_list_page() {
		$bookings = wp_booking_system_luca()->database->get_bookings();
		?>
		<div class="wrap wpbs-admin-wrap">
			<h1><?php esc_html_e( 'All Bookings', 'wp-booking-system-luca' ); ?></h1>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Guest', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Email', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Check-in', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Check-out', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Guests', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Owner', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Price', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wp-booking-system-luca' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-booking-system-luca' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $bookings ) ) : ?>
						<tr>
							<td colspan="10"><?php esc_html_e( 'No bookings found.', 'wp-booking-system-luca' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $bookings as $booking ) : ?>
							<tr>
								<td><?php echo esc_html( $booking->id ); ?></td>
								<td><?php echo esc_html( $booking->first_name . ' ' . $booking->last_name ); ?></td>
								<td><?php echo esc_html( $booking->email ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?></td>
								<td><?php echo esc_html( $booking->adults . ' ' . __( 'adults', 'wp-booking-system-luca' ) . ', ' . $booking->kids . ' ' . __( 'kids', 'wp-booking-system-luca' ) ); ?></td>
								<td><?php echo esc_html( ! empty( $booking->owner ) ? $booking->owner : '—' ); ?></td>
								<td><?php echo esc_html( number_format( $booking->total_price, 2 ) . ' ' . get_option( 'wpbsl_currency', 'CHF' ) ); ?></td>
								<td>
									<span class="wpbs-status wpbs-status-<?php echo esc_attr( $booking->status ); ?>">
										<?php echo esc_html( ucfirst( $booking->status ) ); ?>
									</span>
								</td>
								<td>
									<a href="#" class="wpbs-view-booking" data-id="<?php echo esc_attr( $booking->id ); ?>">
										<?php esc_html_e( 'View', 'wp-booking-system-luca' ); ?>
									</a> |
									<?php if ( 'confirmed' !== $booking->status && 'cancelled' !== $booking->status ) : ?>
										<a href="#" class="wpbs-set-status" data-id="<?php echo esc_attr( $booking->id ); ?>" data-status="confirmed">
											<?php esc_html_e( 'Confirm', 'wp-booking-system-luca' ); ?>
										</a> |
									<?php endif; ?>
									<?php if ( 'cancelled' !== $booking->status ) : ?>
										<a href="#" class="wpbs-set-status" data-id="<?php echo esc_attr( $booking->id ); ?>" data-status="cancelled">
											<?php esc_html_e( 'Cancel', 'wp-booking-system-luca' ); ?>
										</a> |
									<?php endif; ?>
									<a href="#" class="wpbs-delete-booking" data-id="<?php echo esc_attr( $booking->id ); ?>">
										<?php esc_html_e( 'Delete', 'wp-booking-system-luca' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
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

			// Email template options. Subjects are plain text; bodies allow safe HTML.
			// Saving a blank value resets that template to its built-in default.
			$template_fields = array(
				'wpbsl_email_confirmation_subject' => 'subject',
				'wpbsl_email_confirmation_body'    => 'body',
				'wpbsl_email_cancellation_subject' => 'subject',
				'wpbsl_email_cancellation_body'    => 'body',
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
			foreach ( array( 'confirmation', 'cancellation', 'admin' ) as $slug ) {
				$block_key                   = 'wpbsl_email_' . $slug . '_blocks';
				$block_raw                   = isset( $_POST[ $block_key ] ) ? wp_unslash( $_POST[ $block_key ] ) : '';
				$block_values[ $block_key ]  = $this->sanitize_blocks_json( $block_raw );
			}

			// Validate emails.
			if ( ! is_email( $email_from ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid email from address.', 'wp-booking-system-luca' ) . '</p></div>';
			} elseif ( ! empty( $admin_notification_email ) && ! is_email( $admin_notification_email ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid admin notification email address.', 'wp-booking-system-luca' ) . '</p></div>';
			} elseif ( $max_nights > 0 && $max_nights < $min_nights ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Maximum nights cannot be less than minimum nights.', 'wp-booking-system-luca' ) . '</p></div>';
			} elseif ( $max_advance_days > 0 && $max_advance_days < $min_advance_days ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Maximum advance days cannot be less than minimum advance days.', 'wp-booking-system-luca' ) . '</p></div>';
			} elseif ( $default_adults + $default_kids > $chalet_capacity ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Default guests cannot exceed the chalet capacity.', 'wp-booking-system-luca' ) . '</p></div>';
			} elseif ( $smtp_enabled && '' === $smtp_host ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Please enter an SMTP host (e.g. smtp.gmail.com) to enable SMTP delivery.', 'wp-booking-system-luca' ) . '</p></div>';
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
				foreach ( $template_values as $field_key => $field_value ) {
					update_option( $field_key, $field_value );
				}
				foreach ( $block_values as $field_key => $field_value ) {
					update_option( $field_key, $field_value );
				}
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wp-booking-system-luca' ) . '</p></div>';
			}
		}

		$price_adult     = get_option( 'wpbsl_price_adult', 50 );
		$price_kid       = get_option( 'wpbsl_price_kid', 25 );
		$currency        = get_option( 'wpbsl_currency', 'CHF' );
		$email_from      = get_option( 'wpbsl_email_from', get_option( 'admin_email' ) );
		$email_from_name = get_option( 'wpbsl_email_from_name', get_bloginfo( 'name' ) );
		?>
		<div class="wrap wpbs-admin-wrap">
			<h1><?php esc_html_e( 'Booking Settings', 'wp-booking-system-luca' ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'wpbsl_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wpbsl_price_adult"><?php esc_html_e( 'Price per Adult (per night)', 'wp-booking-system-luca' ); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" id="wpbsl_price_adult" name="wpbsl_price_adult" value="<?php echo esc_attr( $price_adult ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpbsl_price_kid"><?php esc_html_e( 'Price per Kid (per night)', 'wp-booking-system-luca' ); ?></label>
						</th>
						<td>
							<input type="number" step="0.01" id="wpbsl_price_kid" name="wpbsl_price_kid" value="<?php echo esc_attr( $price_kid ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpbsl_currency"><?php esc_html_e( 'Currency', 'wp-booking-system-luca' ); ?></label>
						</th>
						<td>
							<input type="text" id="wpbsl_currency" name="wpbsl_currency" value="<?php echo esc_attr( $currency ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpbsl_email_from"><?php esc_html_e( 'Email From Address', 'wp-booking-system-luca' ); ?></label>
						</th>
						<td>
							<input type="email" id="wpbsl_email_from" name="wpbsl_email_from" value="<?php echo esc_attr( $email_from ); ?>" class="regular-text" />
						</td>
					</tr>
				<tr>
					<th scope="row">
						<label for="wpbsl_email_from_name"><?php esc_html_e( 'Email From Name', 'wp-booking-system-luca' ); ?></label>
					</th>
					<td>
						<input type="text" id="wpbsl_email_from_name" name="wpbsl_email_from_name" value="<?php echo esc_attr( $email_from_name ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpbsl_admin_notification_email"><?php esc_html_e( 'Admin Notification Email', 'wp-booking-system-luca' ); ?></label>
					</th>
					<td>
						<input type="email" id="wpbsl_admin_notification_email" name="wpbsl_admin_notification_email" value="<?php echo esc_attr( get_option( 'wpbsl_admin_notification_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Email address to receive notifications when new bookings are made.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpbsl_chalet_capacity"><?php esc_html_e( 'Chalet Maximum Capacity', 'wp-booking-system-luca' ); ?></label>
					</th>
					<td>
						<input type="number" id="wpbsl_chalet_capacity" name="wpbsl_chalet_capacity" value="<?php echo esc_attr( get_option( 'wpbsl_chalet_capacity', 10 ) ); ?>" min="1" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Maximum number of guests (adults + kids) that can be accommodated.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Booking Rules', 'wp-booking-system-luca' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wpbsl_min_nights"><?php esc_html_e( 'Minimum Stay (nights)', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="number" id="wpbsl_min_nights" name="wpbsl_min_nights" value="<?php echo esc_attr( get_option( 'wpbsl_min_nights', 1 ) ); ?>" min="1" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_max_nights"><?php esc_html_e( 'Maximum Stay (nights)', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<input type="number" id="wpbsl_max_nights" name="wpbsl_max_nights" value="<?php echo esc_attr( get_option( 'wpbsl_max_nights', 0 ) ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'Set to 0 for no maximum.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_min_advance_days"><?php esc_html_e( 'Minimum Advance Notice (days)', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<input type="number" id="wpbsl_min_advance_days" name="wpbsl_min_advance_days" value="<?php echo esc_attr( get_option( 'wpbsl_min_advance_days', 0 ) ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'Earliest a guest may check in, in days from today. 0 = today allowed.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_max_advance_days"><?php esc_html_e( 'Booking Window (days ahead)', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<input type="number" id="wpbsl_max_advance_days" name="wpbsl_max_advance_days" value="<?php echo esc_attr( get_option( 'wpbsl_max_advance_days', 0 ) ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'How far ahead guests may book, in days. 0 = no limit.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'New Bookings', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_auto_confirm" value="1" <?php checked( 1, (int) get_option( 'wpbsl_auto_confirm', 0 ) ); ?> />
							<?php esc_html_e( 'Confirm new bookings automatically (skip the pending step).', 'wp-booking-system-luca' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Booking Form', 'wp-booking-system-luca' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wpbsl_default_adults"><?php esc_html_e( 'Default Adults', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="number" id="wpbsl_default_adults" name="wpbsl_default_adults" value="<?php echo esc_attr( get_option( 'wpbsl_default_adults', 2 ) ); ?>" min="1" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_default_kids"><?php esc_html_e( 'Default Kids', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="number" id="wpbsl_default_kids" name="wpbsl_default_kids" value="<?php echo esc_attr( get_option( 'wpbsl_default_kids', 0 ) ); ?>" min="0" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Phone Field', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_require_phone" value="1" <?php checked( 1, (int) get_option( 'wpbsl_require_phone', 0 ) ); ?> />
							<?php esc_html_e( 'Require the guest to provide a phone number.', 'wp-booking-system-luca' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notes Field', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_show_notes" value="1" <?php checked( 1, (int) get_option( 'wpbsl_show_notes', 1 ) ); ?> />
							<?php esc_html_e( 'Show the optional notes field on the booking form.', 'wp-booking-system-luca' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Owner Field', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_show_owner" value="1" <?php checked( 1, (int) get_option( 'wpbsl_show_owner', 1 ) ); ?> />
							<?php esc_html_e( 'Show an "Owner" dropdown on the booking form.', 'wp-booking-system-luca' ); ?>
						</label>
						<p style="margin-top:8px;">
							<label for="wpbsl_owners"><?php esc_html_e( 'Owner names (one per line):', 'wp-booking-system-luca' ); ?></label><br />
							<textarea id="wpbsl_owners" name="wpbsl_owners" rows="4" class="large-text code" placeholder="Alberto&#10;Luca"><?php echo esc_textarea( get_option( 'wpbsl_owners', '' ) ); ?></textarea>
						</p>
						<p class="description"><?php esc_html_e( 'The dropdown only appears when at least one name is listed. Available in emails as {owner}.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Visitors Field', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_show_visitors" value="1" <?php checked( 1, (int) get_option( 'wpbsl_show_visitors', 1 ) ); ?> />
							<?php esc_html_e( 'Show a "Visitors welcome?" yes/no field on the booking form.', 'wp-booking-system-luca' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Available in emails as {visitors_welcome}.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Email Delivery (SMTP)', 'wp-booking-system-luca' ); ?></h2>
			<p class="description" style="max-width:640px;">
				<?php esc_html_e( 'Booking confirmation and notification emails are sent automatically through WordPress. By default WordPress uses your server\'s mail, which is often unreliable. Enable SMTP below to send through a real mailbox such as Gmail / Google Workspace for dependable delivery.', 'wp-booking-system-luca' ); ?>
			</p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'SMTP Delivery', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_smtp_enabled" value="1" <?php checked( 1, (int) get_option( 'wpbsl_smtp_enabled', 0 ) ); ?> />
							<?php esc_html_e( 'Send emails through an external SMTP server.', 'wp-booking-system-luca' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'For Gmail: host smtp.gmail.com, port 587 (TLS), your full address as the username, and a Google "App Password" (not your normal password) as the password.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_host"><?php esc_html_e( 'SMTP Host', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="text" id="wpbsl_smtp_host" name="wpbsl_smtp_host" value="<?php echo esc_attr( get_option( 'wpbsl_smtp_host', '' ) ); ?>" class="regular-text" placeholder="smtp.gmail.com" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_port"><?php esc_html_e( 'SMTP Port', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="number" id="wpbsl_smtp_port" name="wpbsl_smtp_port" value="<?php echo esc_attr( get_option( 'wpbsl_smtp_port', 587 ) ); ?>" min="1" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_encryption"><?php esc_html_e( 'Encryption', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<?php $enc = get_option( 'wpbsl_smtp_encryption', 'tls' ); ?>
						<select id="wpbsl_smtp_encryption" name="wpbsl_smtp_encryption">
							<option value="tls" <?php selected( 'tls', $enc ); ?>><?php esc_html_e( 'TLS (recommended, port 587)', 'wp-booking-system-luca' ); ?></option>
							<option value="ssl" <?php selected( 'ssl', $enc ); ?>><?php esc_html_e( 'SSL (port 465)', 'wp-booking-system-luca' ); ?></option>
							<option value="none" <?php selected( 'none', $enc ); ?>><?php esc_html_e( 'None', 'wp-booking-system-luca' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Authentication', 'wp-booking-system-luca' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpbsl_smtp_auth" value="1" <?php checked( 1, (int) get_option( 'wpbsl_smtp_auth', 1 ) ); ?> />
							<?php esc_html_e( 'Use a username and password to authenticate (required for Gmail).', 'wp-booking-system-luca' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_username"><?php esc_html_e( 'SMTP Username', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="text" id="wpbsl_smtp_username" name="wpbsl_smtp_username" value="<?php echo esc_attr( get_option( 'wpbsl_smtp_username', '' ) ); ?>" class="regular-text" placeholder="you@gmail.com" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_smtp_password"><?php esc_html_e( 'SMTP Password', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<input type="password" id="wpbsl_smtp_password" name="wpbsl_smtp_password" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo get_option( 'wpbsl_smtp_password', '' ) ? esc_attr__( '•••••••• (saved — leave blank to keep)', 'wp-booking-system-luca' ) : ''; ?>" />
						<p class="description"><?php esc_html_e( 'Stored in your database. Leave blank to keep the current password.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
			</table>

			<?php
			$email = wp_booking_system_luca()->email;
			// Effective value: saved text, or the built-in default when blank.
			$eff = function ( $key, $default ) {
				$v = (string) get_option( $key, '' );
				return '' !== trim( $v ) ? $v : $default;
			};
			?>
			<h2 class="title"><?php esc_html_e( 'Email Templates', 'wp-booking-system-luca' ); ?></h2>
			<p class="description" style="max-width:640px;">
				<?php esc_html_e( 'Customise the wording of the automatic emails. Clear a field and save to restore its default. You can use these merge tags, which are replaced with each booking\'s details:', 'wp-booking-system-luca' ); ?>
			</p>
			<p class="description" style="max-width:760px;">
				<code>{site_name}</code> <code>{guest_name}</code> <code>{first_name}</code> <code>{last_name}</code> <code>{guest_email}</code> <code>{guest_phone}</code> <code>{check_in}</code> <code>{check_out}</code> <code>{adults}</code> <code>{kids}</code> <code>{guests}</code> <code>{total_price}</code> <code>{status}</code> <code>{owner}</code> <code>{visitors_welcome}</code> <code>{notes}</code> <code>{booking_details}</code> <code>{manage_link}</code> <code>{manage_url}</code> <code>{admin_link}</code>
			</p>
			<table class="form-table">
				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Guest Confirmation', 'wp-booking-system-luca' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_confirmation_subject"><?php esc_html_e( 'Subject', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_confirmation_subject" name="wpbsl_email_confirmation_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_confirmation_subject', $email->default_confirmation_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-system-luca' ); ?></th>
					<td>
						<input type="hidden" class="wpbsl-builder-data" name="wpbsl_email_confirmation_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_confirmation_blocks', '' ) ); ?>" />
						<div class="wpbsl-builder" data-slug="confirmation"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_confirmation_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_confirmation_body" name="wpbsl_email_confirmation_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_confirmation_body', $email->default_confirmation_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Guest Cancellation', 'wp-booking-system-luca' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_cancellation_subject"><?php esc_html_e( 'Subject', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_cancellation_subject" name="wpbsl_email_cancellation_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_cancellation_subject', $email->default_cancellation_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-system-luca' ); ?></th>
					<td>
						<input type="hidden" class="wpbsl-builder-data" name="wpbsl_email_cancellation_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_cancellation_blocks', '' ) ); ?>" />
						<div class="wpbsl-builder" data-slug="cancellation"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_cancellation_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_cancellation_body" name="wpbsl_email_cancellation_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_cancellation_body', $email->default_cancellation_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row" colspan="2" style="padding-bottom:0;"><h3 style="margin:0;"><?php esc_html_e( 'Admin Notification', 'wp-booking-system-luca' ); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_admin_subject"><?php esc_html_e( 'Subject', 'wp-booking-system-luca' ); ?></label></th>
					<td><input type="text" id="wpbsl_email_admin_subject" name="wpbsl_email_admin_subject" value="<?php echo esc_attr( $eff( 'wpbsl_email_admin_subject', $email->default_admin_subject() ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content', 'wp-booking-system-luca' ); ?></th>
					<td>
						<input type="hidden" class="wpbsl-builder-data" name="wpbsl_email_admin_blocks" value="<?php echo esc_attr( get_option( 'wpbsl_email_admin_blocks', '' ) ); ?>" />
						<div class="wpbsl-builder" data-slug="admin"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wpbsl_email_admin_body"><?php esc_html_e( 'Body (fallback)', 'wp-booking-system-luca' ); ?></label></th>
					<td>
						<textarea id="wpbsl_email_admin_body" name="wpbsl_email_admin_body" rows="6" class="large-text code"><?php echo esc_textarea( $eff( 'wpbsl_email_admin_body', $email->default_admin_body() ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when no content blocks are added above.', 'wp-booking-system-luca' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Settings', 'wp-booking-system-luca' ), 'primary', 'wpbsl_save_settings' ); ?>
		</form>

		<h2 class="title"><?php esc_html_e( 'Send a Test Email', 'wp-booking-system-luca' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Save your settings first, then send a test email to confirm delivery works.', 'wp-booking-system-luca' ); ?></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="wpbsl_test_email_to"><?php esc_html_e( 'Send test to', 'wp-booking-system-luca' ); ?></label></th>
				<td>
					<input type="email" id="wpbsl_test_email_to" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text" />
					<button type="button" class="button button-secondary" id="wpbsl-send-test-email" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp-booking-system-luca-admin' ) ); ?>"><?php esc_html_e( 'Send Test Email', 'wp-booking-system-luca' ); ?></button>
					<span id="wpbsl-test-email-result" style="margin-left:10px;font-weight:600;"></span>
				</td>
			</tr>
		</table>
		<script>
		( function () {
			var btn = document.getElementById( 'wpbsl-send-test-email' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				var out = document.getElementById( 'wpbsl-test-email-result' );
				var to = document.getElementById( 'wpbsl_test_email_to' ).value;
				btn.disabled = true;
				out.style.color = '#666';
				out.textContent = <?php echo wp_json_encode( __( 'Sending…', 'wp-booking-system-luca' ) ); ?>;
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
						out.textContent = <?php echo wp_json_encode( __( 'Request failed. Please try again.', 'wp-booking-system-luca' ) ); ?>;
					} )
					.finally( function () { btn.disabled = false; } );
			} );
		} )();
		</script>
	</div>
	<?php
	}
}

