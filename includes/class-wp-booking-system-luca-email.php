<?php
/**
 * Email class for sending booking notifications
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Booking_System_Luca_Email Class
 */
class WP_Booking_System_Luca_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Apply the configured "From" address/name to every email WordPress sends
		// from this site (so even the test email and any fallback path use it).
		add_filter( 'wp_mail_from', array( $this, 'filter_mail_from' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ) );

		// Route mail through an external SMTP server (e.g. Gmail) when configured.
		add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );
	}

	/**
	 * Filter the global "From" email address.
	 *
	 * @param string $from Default from address.
	 * @return string
	 */
	public function filter_mail_from( $from ) {
		$configured = get_option( 'wpbsl_email_from', '' );

		return ( $configured && is_email( $configured ) ) ? $configured : $from;
	}

	/**
	 * Filter the global "From" name.
	 *
	 * @param string $name Default from name.
	 * @return string
	 */
	public function filter_mail_from_name( $name ) {
		$configured = get_option( 'wpbsl_email_from_name', '' );

		return $configured ? $configured : $name;
	}

	/**
	 * Configure PHPMailer to send through an external SMTP server.
	 *
	 * Hooked to `phpmailer_init`. When SMTP delivery is enabled in Settings,
	 * this reroutes WordPress mail (including this plugin's notifications)
	 * through the configured server — e.g. Gmail / Google Workspace.
	 *
	 * @param object $phpmailer PHPMailer instance (passed by reference by WP).
	 * @return void
	 */
	public function configure_phpmailer( $phpmailer ) {
		if ( ! (int) get_option( 'wpbsl_smtp_enabled', 0 ) ) {
			return;
		}

		$host = trim( (string) get_option( 'wpbsl_smtp_host', '' ) );

		if ( '' === $host ) {
			// Misconfigured: fall back to the default mail transport rather than failing.
			return;
		}

		$encryption = get_option( 'wpbsl_smtp_encryption', 'tls' );

		$phpmailer->isSMTP();
		$phpmailer->Host        = $host;
		$phpmailer->Port        = (int) get_option( 'wpbsl_smtp_port', 587 );
		$phpmailer->SMTPAuth    = (bool) (int) get_option( 'wpbsl_smtp_auth', 1 );
		$phpmailer->SMTPSecure  = in_array( $encryption, array( 'ssl', 'tls' ), true ) ? $encryption : '';
		$phpmailer->SMTPAutoTLS = ( 'none' !== $encryption );

		if ( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = (string) get_option( 'wpbsl_smtp_username', '' );
			$phpmailer->Password = (string) get_option( 'wpbsl_smtp_password', '' );
		}
	}

	/**
	 * Send a test email to verify the current delivery configuration.
	 *
	 * @param string $to Recipient address.
	 * @return array { @type bool $success, @type string $message }
	 */
	public function send_test_email( $to ) {
		$to = sanitize_email( $to );

		if ( ! is_email( $to ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid email address to send the test to.', 'wp-booking-system-luca' ),
			);
		}

		// Capture any PHPMailer error so we can surface it to the admin.
		$error_holder = new stdClass();
		$error_holder->message = '';
		$capture = function ( $wp_error ) use ( $error_holder ) {
			$error_holder->message = $wp_error->get_error_message();
		};
		add_action( 'wp_mail_failed', $capture );

		$subject = sprintf(
			/* translators: %s: Site name */
			__( '[%s] Test email from WP booking Luca', 'wp-booking-system-luca' ),
			get_bloginfo( 'name' )
		);

		$smtp_on = (int) get_option( 'wpbsl_smtp_enabled', 0 );
		$message = sprintf(
			/* translators: %s: delivery method description */
			__( 'This is a test email from WP booking Luca. If you received it, your booking notifications are being delivered correctly (%s).', 'wp-booking-system-luca' ),
			$smtp_on ? sprintf( __( 'via SMTP host %s', 'wp-booking-system-luca' ), get_option( 'wpbsl_smtp_host', '' ) ) : __( 'via the default WordPress mailer', 'wp-booking-system-luca' )
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $subject, '<p>' . esc_html( $message ) . '</p>', $headers );

		remove_action( 'wp_mail_failed', $capture );

		if ( $sent ) {
			return array(
				'success' => true,
				/* translators: %s: recipient email address */
				'message' => sprintf( __( 'Test email sent to %s. Please check the inbox (and spam folder).', 'wp-booking-system-luca' ), $to ),
			);
		}

		return array(
			'success' => false,
			'message' => $error_holder->message
				? sprintf( __( 'Sending failed: %s', 'wp-booking-system-luca' ), $error_holder->message )
				: __( 'Sending failed. Check your SMTP settings or install an SMTP plugin.', 'wp-booking-system-luca' ),
		);
	}

	/**
	 * Build the magic-link URL a guest uses to manage their booking.
	 *
	 * Resolves to the page created on activation; falls back to a
	 * conventional slug if that page is missing.
	 *
	 * @param string $token Booking token.
	 * @return string
	 */
	private function get_manage_url( $token ) {
		$manage_page_id = (int) get_option( 'wpbsl_manage_page_id', 0 );

		if ( $manage_page_id && 'publish' === get_post_status( $manage_page_id ) ) {
			$base = get_permalink( $manage_page_id );
		} else {
			$base = home_url( '/booking-manage/' );
		}

		return add_query_arg( 'token', rawurlencode( $token ), $base );
	}

	/**
	 * Send booking confirmation email.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	public function send_booking_confirmation( $booking ) {
		$to      = $booking->email;
		$subject = $this->render_subject( 'wpbsl_email_confirmation_subject', $this->default_confirmation_subject(), $booking );
		$body    = $this->compose_body( 'confirmation', $this->default_confirmation_body(), $booking );
		$message = $this->wrap_email( __( 'Booking Confirmation', 'wp-booking-system-luca' ), $body );

		$attachments = $this->build_ics_attachment( $booking );
		$result      = wp_mail( $to, $subject, $message, $this->mail_headers(), $attachments );
		$this->cleanup_attachments( $attachments );

		// Send admin notification.
		$this->send_admin_notification( $booking );

		return $result;
	}

	/**
	 * Send admin notification email for new booking.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	public function send_admin_notification( $booking ) {
		$admin_email = get_option( 'wpbsl_admin_notification_email', get_option( 'admin_email' ) );

		if ( empty( $admin_email ) || ! is_email( $admin_email ) ) {
			return false;
		}

		$to      = $admin_email;
		$subject = $this->render_subject( 'wpbsl_email_admin_subject', $this->default_admin_subject(), $booking );
		$body    = $this->compose_body( 'admin', $this->default_admin_body(), $booking );
		$message = $this->wrap_email( __( 'New Booking Received', 'wp-booking-system-luca' ), $body );

		$attachments = $this->build_ics_attachment( $booking );
		$result      = wp_mail( $to, $subject, $message, $this->mail_headers(), $attachments );
		$this->cleanup_attachments( $attachments );

		return $result;
	}

	/**
	 * Standard headers for every email this plugin sends.
	 *
	 * @return string[]
	 */
	private function mail_headers() {
		return array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_option( 'wpbsl_email_from_name', get_bloginfo( 'name' ) ) . ' <' . get_option( 'wpbsl_email_from', get_option( 'admin_email' ) ) . '>',
		);
	}

	/**
	 * Read a saved template, falling back to the built-in default when blank.
	 *
	 * An empty saved value therefore acts as "reset to default".
	 *
	 * @param string $option_key Option name.
	 * @param string $default    Built-in default.
	 * @return string
	 */
	private function get_template_part( $option_key, $default ) {
		$value = (string) get_option( $option_key, '' );

		return '' !== trim( $value ) ? $value : $default;
	}

	/**
	 * Replace {merge_tags} in a string. Pure and side-effect free.
	 *
	 * @param string $text Template text.
	 * @param array  $vars Map of tag => replacement.
	 * @return string
	 */
	public static function replace_merge_tags( $text, array $vars ) {
		return str_replace( array_keys( $vars ), array_values( $vars ), (string) $text );
	}

	/**
	 * Build the merge-tag map for a booking. Text values are escaped so
	 * guest-supplied data cannot inject markup into the email.
	 *
	 * @param object $booking Booking object.
	 * @return array
	 */
	/**
	 * Human label for a payment status key.
	 *
	 * @param string $key Status key.
	 * @return string
	 */
	private function payment_status_label( $key ) {
		$map = WP_Booking_System_Luca_Helpers::payment_statuses();
		return isset( $map[ $key ] ) ? $map[ $key ] : ucfirst( (string) $key );
	}

	/**
	 * Human label for a payment method key ('' = none).
	 *
	 * @param string $key Method key.
	 * @return string
	 */
	private function payment_method_label( $key ) {
		$map = WP_Booking_System_Luca_Helpers::payment_methods();
		return isset( $map[ $key ] ) ? $map[ $key ] : '—';
	}

	/**
	 * Configured creditor IBAN, grouped in fours (e.g. "CH20 8080 …"), or ''.
	 *
	 * @return string
	 */
	private function formatted_iban() {
		$iban = WP_Booking_System_Luca_Helpers::normalize_iban( get_option( 'wpbsl_qr_creditor_iban', '' ) );
		return '' === $iban ? '' : trim( chunk_split( $iban, 4, ' ' ) );
	}

	/**
	 * The TWINT pay link as an HTML anchor (using the configured label, or a
	 * default), or '' when no pay link is set.
	 *
	 * @return string
	 */
	private function twint_paylink_html() {
		$url = (string) get_option( 'wpbsl_qr_twint_paylink', '' );
		if ( '' === trim( $url ) ) {
			return '';
		}
		$label = trim( (string) get_option( 'wpbsl_qr_twint_label', '' ) );
		if ( '' === $label ) {
			$label = __( 'Pay with TWINT', 'wp-booking-system-luca' );
		}
		return '<a href="' . esc_url( $url ) . '" style="color:#8B0000; font-weight:bold;">' . esc_html( $label ) . '</a>';
	}

	private function get_merge_vars( $booking ) {
		$currency   = get_option( 'wpbsl_currency', 'CHF' );
		$date_fmt   = get_option( 'date_format' );
		$manage_url = $this->get_manage_url( $booking->booking_token );
		$admin_url  = admin_url( 'admin.php?page=wp-booking-system-list' );

		$guests = sprintf(
			/* translators: 1: number of adults, 2: number of kids */
			__( '%1$d adults, %2$d kids', 'wp-booking-system-luca' ),
			(int) $booking->adults,
			(int) $booking->kids
		);

		return array(
			'{site_name}'       => esc_html( get_bloginfo( 'name' ) ),
			'{first_name}'      => esc_html( $booking->first_name ),
			'{last_name}'       => esc_html( $booking->last_name ),
			'{guest_name}'      => esc_html( trim( $booking->first_name . ' ' . $booking->last_name ) ),
			'{guest_email}'     => esc_html( $booking->email ),
			'{guest_phone}'     => esc_html( $booking->phone ? $booking->phone : __( 'N/A', 'wp-booking-system-luca' ) ),
			'{check_in}'        => esc_html( date_i18n( $date_fmt, strtotime( $booking->check_in ) ) ),
			'{check_out}'       => esc_html( date_i18n( $date_fmt, strtotime( $booking->check_out ) ) ),
			'{adults}'          => (int) $booking->adults,
			'{kids}'            => (int) $booking->kids,
			'{guests}'          => esc_html( $guests ),
			'{total_price}'     => esc_html( number_format( (float) $booking->total_price, 2 ) . ' ' . $currency ),
			'{status}'          => esc_html( ucfirst( (string) $booking->status ) ),
			'{owner}'           => esc_html( isset( $booking->owner ) ? (string) $booking->owner : '' ),
			'{visitors_welcome}' => esc_html( ( isset( $booking->visitors_welcome ) && (int) $booking->visitors_welcome ) ? __( 'Yes', 'wp-booking-system-luca' ) : __( 'No', 'wp-booking-system-luca' ) ),
			'{payment_status}'  => esc_html( $this->payment_status_label( isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid' ) ),
			'{payment_method}'  => esc_html( $this->payment_method_label( isset( $booking->payment_method ) ? $booking->payment_method : '' ) ),
			'{amount_paid}'     => esc_html( number_format( isset( $booking->amount_paid ) ? (float) $booking->amount_paid : 0, 2 ) . ' ' . $currency ),
			'{amount_due}'      => esc_html( number_format( WP_Booking_System_Luca_Helpers::amount_due( $booking ), 2 ) . ' ' . $currency ),
			'{notes}'           => esc_html( (string) $booking->notes ),
			'{payment_account}' => esc_html( (string) get_option( 'wpbsl_qr_creditor_name', '' ) ),
			'{payment_bank}'    => esc_html( (string) get_option( 'wpbsl_qr_bank_name', '' ) ),
			'{payment_iban}'    => esc_html( $this->formatted_iban() ),
			'{payment_twint}'   => $this->twint_paylink_html(),
			'{payment_twint_url}' => esc_url( (string) get_option( 'wpbsl_qr_twint_paylink', '' ) ),
			'{manage_url}'      => esc_url( $manage_url ),
			'{manage_link}'     => '<a href="' . esc_url( $manage_url ) . '" class="button" style="display:inline-block; padding:12px 24px; background-color:#8B0000; color:#ffffff; text-decoration:none; border-radius:4px; margin-top:15px;">' . esc_html__( 'Manage Booking', 'wp-booking-system-luca' ) . '</a>',
			'{admin_link}'      => '<a href="' . esc_url( $admin_url ) . '" class="button" style="display:inline-block; padding:12px 24px; background-color:#8B0000; color:#ffffff; text-decoration:none; border-radius:4px; margin-top:15px;">' . esc_html__( 'View Booking', 'wp-booking-system-luca' ) . '</a>',
			'{booking_details}' => $this->render_booking_details( $booking ),
			'{payment_info}'    => $this->render_payment_info( $booking ),
		);
	}

	/**
	 * Styled "booking details" box used by the {booking_details} tag.
	 *
	 * @param object $booking Booking object.
	 * @return string
	 */
	private function render_booking_details( $booking ) {
		$currency = get_option( 'wpbsl_currency', 'CHF' );
		$date_fmt = get_option( 'date_format' );

		ob_start();
		?>
		<div class="booking-details" style="background-color:#f7f7f7; color:#333333; padding:15px; margin:15px 0; border-left:4px solid #8B0000;">
			<h3 style="color:#333333; margin-top:0;"><?php esc_html_e( 'Booking Details', 'wp-booking-system-luca' ); ?></h3>
			<p><strong><?php esc_html_e( 'Check-in:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( date_i18n( $date_fmt, strtotime( $booking->check_in ) ) ); ?></p>
			<p><strong><?php esc_html_e( 'Check-out:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( date_i18n( $date_fmt, strtotime( $booking->check_out ) ) ); ?></p>
			<p><strong><?php esc_html_e( 'Guests:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $booking->adults . ' ' . __( 'adults', 'wp-booking-system-luca' ) . ', ' . $booking->kids . ' ' . __( 'kids', 'wp-booking-system-luca' ) ); ?></p>
			<p><strong><?php esc_html_e( 'Total Price:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( number_format( (float) $booking->total_price, 2 ) . ' ' . $currency ); ?></p>
			<?php if ( ! empty( $booking->notes ) ) : ?>
				<p><strong><?php esc_html_e( 'Notes:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $booking->notes ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the payment context (bank details, amount due, reference, QR
	 * payload and TWINT pay link) for a booking, or null when QR/TWINT payment
	 * is not applicable (disabled, no valid IBAN, nothing due, or
	 * cancelled/refunded). Reused by the email, the manage page and checkout.
	 *
	 * @param object $booking Booking object.
	 * @return array|null
	 */
	public function payment_context( $booking ) {
		if ( ! (int) get_option( 'wpbsl_qr_enabled', 0 ) ) {
			return null;
		}

		$iban = WP_Booking_System_Luca_Helpers::normalize_iban( get_option( 'wpbsl_qr_creditor_iban', '' ) );
		if ( ! WP_Booking_System_Luca_Helpers::is_valid_ch_iban( $iban ) ) {
			return null;
		}

		$pstatus = isset( $booking->payment_status ) ? $booking->payment_status : 'unpaid';
		if ( 'refunded' === $pstatus || ( isset( $booking->status ) && 'cancelled' === $booking->status ) ) {
			return null;
		}

		$due = WP_Booking_System_Luca_Helpers::amount_due( $booking );
		if ( $due <= 0 ) {
			return null;
		}

		$cur = strtoupper( (string) get_option( 'wpbsl_currency', 'CHF' ) );
		$cur = in_array( $cur, array( 'CHF', 'EUR' ), true ) ? $cur : 'CHF';
		$ref = trim( sprintf( 'Booking #%d %s %s', (int) $booking->id, $booking->first_name, $booking->last_name ) );

		$payload = WP_Booking_System_Luca_Helpers::build_swiss_qr_payload(
			array(
				'iban'     => $iban,
				'name'     => get_option( 'wpbsl_qr_creditor_name', '' ),
				'address'  => get_option( 'wpbsl_qr_creditor_address', '' ),
				'city'     => get_option( 'wpbsl_qr_creditor_city', '' ),
				'country'  => get_option( 'wpbsl_qr_creditor_country', 'CH' ),
				'amount'   => $due,
				'currency' => $cur,
				'message'  => $ref,
			)
		);

		return array(
			'name'       => (string) get_option( 'wpbsl_qr_creditor_name', '' ),
			'bank'       => (string) get_option( 'wpbsl_qr_bank_name', '' ),
			'iban'       => trim( chunk_split( $iban, 4, ' ' ) ),
			'amount'     => number_format( $due, 2 ) . ' ' . $cur,
			'reference'  => $ref,
			'paylink'    => (string) get_option( 'wpbsl_qr_twint_paylink', '' ),
			'qr_payload' => base64_encode( $payload ),
			'manage_url' => $this->get_manage_url( $booking->booking_token ),
		);
	}

	/**
	 * Render the {payment_info} block for emails. Empty when not applicable.
	 *
	 * @param object $booking Booking object.
	 * @return string
	 */
	private function render_payment_info( $booking ) {
		$ctx = $this->payment_context( $booking );
		if ( ! $ctx ) {
			return '';
		}

		ob_start();
		?>
		<div class="payment-info" style="background-color:#f7f7f7; color:#333333; padding:15px; margin:15px 0; border-left:4px solid #8B0000;">
			<h3 style="color:#333333; margin-top:0;"><?php esc_html_e( 'Payment Details', 'wp-booking-system-luca' ); ?></h3>
			<p style="margin:8px 0;">
				<?php if ( '' !== $ctx['name'] ) : ?><?php echo esc_html( $ctx['name'] ); ?><br /><?php endif; ?>
				<?php if ( '' !== $ctx['bank'] ) : ?><?php echo esc_html( $ctx['bank'] ); ?><br /><?php endif; ?>
				<strong><?php esc_html_e( 'IBAN:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $ctx['iban'] ); ?>
			</p>
			<p style="margin:8px 0;">
				<strong><?php esc_html_e( 'Amount:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $ctx['amount'] ); ?><br />
				<strong><?php esc_html_e( 'Reference:', 'wp-booking-system-luca' ); ?></strong> <?php echo esc_html( $ctx['reference'] ); ?>
			</p>
			<?php if ( '' !== $ctx['paylink'] ) : ?>
				<p style="margin:10px 0 0;"><?php esc_html_e( 'Or pay by TWINT:', 'wp-booking-system-luca' ); ?> <a href="<?php echo esc_url( $ctx['paylink'] ); ?>" style="color:#8B0000; font-weight:bold;"><?php esc_html_e( 'Pay with TWINT', 'wp-booking-system-luca' ); ?></a></p>
			<?php endif; ?>
			<p style="margin:10px 0 0; font-size:13px; color:#666666;"><?php esc_html_e( 'You can also scan the Swiss QR code on your booking page.', 'wp-booking-system-luca' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resolve a subject line: merge tags applied, HTML stripped.
	 *
	 * @param string $option_key Option name.
	 * @param string $default    Default subject.
	 * @param object $booking    Booking object.
	 * @return string
	 */
	private function render_subject( $option_key, $default, $booking ) {
		$raw = $this->get_template_part( $option_key, $default );

		return wp_strip_all_tags( self::replace_merge_tags( $raw, $this->get_merge_vars( $booking ) ) );
	}

	/**
	 * Resolve a body: merge tags applied, then paragraph formatting.
	 *
	 * @param string $option_key Option name.
	 * @param string $default    Default body.
	 * @param object $booking    Booking object.
	 * @return string
	 */
	private function get_template_body( $option_key, $default, $booking ) {
		$raw = $this->get_template_part( $option_key, $default );

		return wpautop( self::replace_merge_tags( $raw, $this->get_merge_vars( $booking ) ) );
	}

	/**
	 * Build an email body, preferring the visual block builder when one has
	 * been configured for this template, otherwise the plain-text template.
	 *
	 * @param string $slug         Template slug: confirmation|cancellation|admin.
	 * @param string $default_body Default plain-text body.
	 * @param object $booking      Booking object.
	 * @return string
	 */
	private function compose_body( $slug, $default_body, $booking ) {
		$blocks = json_decode( (string) get_option( 'wpbsl_email_' . $slug . '_blocks', '' ), true );

		if ( is_array( $blocks ) && ! empty( $blocks ) ) {
			return $this->render_blocks( $blocks, $booking );
		}

		return $this->get_template_body( 'wpbsl_email_' . $slug . '_body', $default_body, $booking );
	}

	/**
	 * Render an ordered list of content blocks (from the drag-and-drop
	 * builder) into email HTML, resolving merge tags per block.
	 *
	 * @param array  $blocks  Block definitions.
	 * @param object $booking Booking object.
	 * @return string
	 */
	private function render_blocks( $blocks, $booking ) {
		$vars   = $this->get_merge_vars( $booking );
		$button = 'display:inline-block; padding:12px 24px; background-color:#8B0000; color:#ffffff; text-decoration:none; border-radius:4px; margin-top:15px;';
		$html   = '';

		foreach ( $blocks as $block ) {
			$type = isset( $block['type'] ) ? $block['type'] : '';

			switch ( $type ) {
				case 'heading':
					$text  = self::replace_merge_tags( esc_html( isset( $block['text'] ) ? $block['text'] : '' ), $vars );
					$html .= '<h2 style="color:#333333; margin:0 0 12px;">' . $text . '</h2>';
					break;

				case 'text':
					$text  = self::replace_merge_tags( wpautop( esc_html( isset( $block['text'] ) ? $block['text'] : '' ) ), $vars );
					$html .= $text;
					break;

				case 'details':
					$html .= $vars['{booking_details}'];
					break;

				case 'button':
					$label = self::replace_merge_tags( esc_html( isset( $block['label'] ) ? $block['label'] : '' ), $vars );
					$url   = esc_url( self::replace_merge_tags( isset( $block['url'] ) ? $block['url'] : '', $vars ) );
					$html .= '<p><a href="' . $url . '" style="' . esc_attr( $button ) . '">' . $label . '</a></p>';
					break;

				case 'image':
					$src = esc_url( isset( $block['src'] ) ? $block['src'] : '' );
					if ( $src ) {
						$alt   = esc_attr( isset( $block['alt'] ) ? $block['alt'] : '' );
						$width = isset( $block['width'] ) ? absint( $block['width'] ) : 0;
						$wattr = $width ? ' width="' . $width . '" style="max-width:100%;height:auto;"' : ' style="max-width:100%;height:auto;"';
						$html .= '<p><img src="' . $src . '" alt="' . $alt . '"' . $wattr . ' /></p>';
					}
					break;

				case 'divider':
					$html .= '<hr style="border:none;border-top:1px solid #dddddd;margin:20px 0;" />';
					break;
			}
		}

		return $html;
	}

	/**
	 * Wrap a body in the branded HTML email shell.
	 *
	 * @param string $heading Header-bar heading.
	 * @param string $body    Body HTML.
	 * @return string
	 */
	private function wrap_email( $heading, $body ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( str_replace( '_', '-', get_locale() ) ); ?>">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta name="color-scheme" content="light only">
			<meta name="supported-color-schemes" content="light only">
			<style>
				:root { color-scheme: light only; supported-color-schemes: light only; }
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #eeeeee; margin: 0; padding: 0; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.header { background-color: #8B0000; color: #ffffff; padding: 20px; text-align: center; }
				.header h1 { color: #ffffff; margin: 0; }
				.content { background-color: #ffffff; color: #333333; padding: 20px; }
				.booking-details { background-color: #f7f7f7; color: #333333; padding: 15px; margin: 15px 0; border-left: 4px solid #8B0000; }
				.booking-details p { margin: 8px 0; }
				.button { display: inline-block; padding: 12px 24px; background-color: #8B0000; color: #ffffff !important; text-decoration: none; border-radius: 4px; margin-top: 15px; }
				.footer { text-align: center; padding: 20px; color: #666666; font-size: 12px; }
			</style>
		</head>
		<body style="background-color:#eeeeee; color:#333333;">
			<div class="container">
				<div class="header" style="background-color:#8B0000;">
					<h1 style="color:#ffffff; margin:0;"><?php echo esc_html( $heading ); ?></h1>
				</div>
				<div class="content" style="background-color:#ffffff; color:#333333;">
					<?php echo wp_kses_post( $body ); ?>
				</div>
				<div class="footer">
					<p><?php echo esc_html( get_bloginfo( 'name' ) ); ?> | <?php echo esc_url( home_url() ); ?></p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Default guest-confirmation subject.
	 *
	 * @return string
	 */
	public function default_confirmation_subject() {
		return __( 'Booking Confirmation - {site_name}', 'wp-booking-system-luca' );
	}

	/**
	 * Default guest-confirmation body.
	 *
	 * @return string
	 */
	public function default_confirmation_body() {
		return __(
			"Dear {guest_name},\n\nThank you for your booking! We are pleased to confirm your reservation.\n\n{booking_details}\n\n{payment_info}\n\nYou can manage or cancel your booking using the link below:\n\n{manage_link}\n\nWe look forward to welcoming you!\n\nBest regards,\n{site_name}",
			'wp-booking-system-luca'
		);
	}

	/**
	 * Default guest-cancellation subject.
	 *
	 * @return string
	 */
	public function default_cancellation_subject() {
		return __( 'Booking Cancelled - {site_name}', 'wp-booking-system-luca' );
	}

	/**
	 * Default guest-cancellation body.
	 *
	 * @return string
	 */
	public function default_cancellation_body() {
		return __(
			"Dear {guest_name},\n\nYour booking has been cancelled as requested.\n\nWe hope to welcome you in the future!\n\nBest regards,\n{site_name}",
			'wp-booking-system-luca'
		);
	}

	/**
	 * Default payment-reminder subject.
	 *
	 * @return string
	 */
	public function default_reminder_subject() {
		return __( 'Payment reminder - {site_name}', 'wp-booking-system-luca' );
	}

	/**
	 * Default payment-reminder body.
	 *
	 * @return string
	 */
	public function default_reminder_body() {
		return __(
			"Dear {guest_name},\n\nThis is a friendly reminder about the balance for your booking.\n\n{booking_details}\n\nAmount paid: {amount_paid}\nOutstanding balance: {amount_due}\n\nYou can review your booking using the link below:\n\n{manage_link}\n\nThank you,\n{site_name}",
			'wp-booking-system-luca'
		);
	}

	/**
	 * Default admin-notification subject.
	 *
	 * @return string
	 */
	public function default_admin_subject() {
		return __( 'New Booking Received - {site_name}', 'wp-booking-system-luca' );
	}

	/**
	 * Default admin-notification body.
	 *
	 * @return string
	 */
	public function default_admin_body() {
		return __(
			"A new booking has been submitted.\n\nGuest: {guest_name}\nEmail: {guest_email}\nPhone: {guest_phone}\nOwner: {owner}\nVisitors welcome: {visitors_welcome}\nStatus: {status}\n\n{booking_details}\n\n{admin_link}",
			'wp-booking-system-luca'
		);
	}

	/**
	 * Escape a value for inclusion in an iCalendar (.ics) text field.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function ics_escape( $value ) {
		$value = (string) $value;
		$value = str_replace( array( "\\", ';', ',' ), array( '\\\\', '\\;', '\\,' ), $value );
		$value = str_replace( array( "\r\n", "\n", "\r" ), '\\n', $value );

		return $value;
	}

	/**
	 * Build an iCalendar (.ics) document for a booking, as an all-day VEVENT
	 * spanning check-in (inclusive) to check-out (exclusive).
	 *
	 * @param object $booking Booking object.
	 * @return string
	 */
	public function generate_ics( $booking ) {
		$host  = wp_parse_url( home_url(), PHP_URL_HOST );
		$host  = $host ? $host : 'localhost';
		$id    = isset( $booking->id ) ? (int) $booking->id : 0;
		$site  = get_bloginfo( 'name' );
		$start = gmdate( 'Ymd', strtotime( $booking->check_in ) );
		$end   = gmdate( 'Ymd', strtotime( $booking->check_out ) );

		$summary = sprintf(
			/* translators: 1: site name, 2: booking id */
			__( '%1$s - Booking #%2$d', 'wp-booking-system-luca' ),
			$site,
			$id
		);

		$description = sprintf(
			/* translators: 1: guest name, 2: number of guests */
			__( 'Booking for %1$s (%2$d guests).', 'wp-booking-system-luca' ),
			trim( $booking->first_name . ' ' . $booking->last_name ),
			(int) $booking->adults + (int) $booking->kids
		);

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//WP booking Luca//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:wpbsl-' . $id . '@' . $host,
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART;VALUE=DATE:' . $start,
			'DTEND;VALUE=DATE:' . $end,
			'SUMMARY:' . self::ics_escape( $summary ),
			'DESCRIPTION:' . self::ics_escape( $description ),
			'STATUS:CONFIRMED',
			'END:VEVENT',
			'END:VCALENDAR',
		);

		return implode( "\r\n", $lines ) . "\r\n";
	}

	/**
	 * Write the booking's .ics to a temp file and return it as a wp_mail
	 * attachments array. The file is named booking-{id}.ics.
	 *
	 * @param object $booking Booking object.
	 * @return string[] Attachment paths (empty on failure).
	 */
	private function build_ics_attachment( $booking ) {
		if ( empty( $booking->check_in ) || empty( $booking->check_out ) ) {
			return array();
		}

		$id   = isset( $booking->id ) ? (int) $booking->id : 0;
		$path = trailingslashit( get_temp_dir() ) . 'booking-' . ( $id ? $id : 'new' ) . '.ics';

		if ( false === file_put_contents( $path, $this->generate_ics( $booking ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return array();
		}

		return array( $path );
	}

	/**
	 * Remove temporary attachment files after sending.
	 *
	 * @param string[] $attachments Attachment paths.
	 * @return void
	 */
	private function cleanup_attachments( $attachments ) {
		foreach ( (array) $attachments as $path ) {
			if ( $path && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
	}

	/**
	 * Send booking cancellation email.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	public function send_booking_cancellation( $booking ) {
		$to      = $booking->email;
		$subject = $this->render_subject( 'wpbsl_email_cancellation_subject', $this->default_cancellation_subject(), $booking );
		$body    = $this->compose_body( 'cancellation', $this->default_cancellation_body(), $booking );
		$message = $this->wrap_email( __( 'Booking Cancelled', 'wp-booking-system-luca' ), $body );

		return wp_mail( $to, $subject, $message, $this->mail_headers() );
	}

	/**
	 * Send a payment-reminder email to the guest.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	public function send_payment_reminder( $booking ) {
		$to      = $booking->email;
		$subject = $this->render_subject( 'wpbsl_email_reminder_subject', $this->default_reminder_subject(), $booking );
		$body    = $this->compose_body( 'reminder', $this->default_reminder_body(), $booking );
		$message = $this->wrap_email( __( 'Payment Reminder', 'wp-booking-system-luca' ), $body );

		return wp_mail( $to, $subject, $message, $this->mail_headers() );
	}

}

