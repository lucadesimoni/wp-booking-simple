<?php
/**
 * Self-contained test runner.
 *
 * Runs without PHPUnit or a WordPress install by stubbing the small slice of
 * the WordPress API the plugin touches at load time. It verifies:
 *
 *   1. The pure booking logic in WP_Booking_System_Luca_Helpers.
 *   2. That the whole plugin boots with no fatal errors and registers its
 *      shortcodes, blocks and AJAX handlers.
 *
 * Usage: php tests/standalone/run.php
 *
 * @package WP_Booking_System_Luca
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

$plugin_dir = dirname( __DIR__, 2 );

/* --------------------------------------------------------------------------
 * Tiny assertion framework.
 * ------------------------------------------------------------------------ */
$tests_run    = 0;
$tests_failed = 0;

/**
 * Assert a condition is true.
 *
 * @param bool   $condition Condition.
 * @param string $message   Description.
 */
function check( $condition, $message ) {
	global $tests_run, $tests_failed;
	$tests_run++;
	if ( $condition ) {
		echo "  \033[32mPASS\033[0m  {$message}\n";
	} else {
		$tests_failed++;
		echo "  \033[31mFAIL\033[0m  {$message}\n";
	}
}

/**
 * Assert two values are equal (loose, with type-aware float compare).
 *
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 * @param string $message  Description.
 */
function check_equals( $expected, $actual, $message ) {
	$ok = is_float( $expected ) || is_float( $actual )
		? abs( (float) $expected - (float) $actual ) < 0.00001
		: $expected === $actual;
	check( $ok, $message . ( $ok ? '' : ' (expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ')' ) );
}

/* --------------------------------------------------------------------------
 * Minimal WordPress stubs needed to load the plugin.
 * ------------------------------------------------------------------------ */
define( 'ABSPATH', $plugin_dir . '/' );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['_wpbsl_test'] = array(
	'shortcodes' => array(),
	'actions'    => array(),
	'blocks'     => array(),
	'options'    => array(),
);

function plugin_dir_path( $f ) { return rtrim( dirname( $f ), '/' ) . '/'; }
function plugin_dir_url( $f ) { return 'http://example.test/wp-content/plugins/' . basename( dirname( $f ) ) . '/'; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
function plugins_url( $p = '', $f = '' ) { return 'http://example.test' . $p; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function register_activation_hook( $f, $cb ) {}
function register_deactivation_hook( $f, $cb ) {}
function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['_wpbsl_test']['actions'][ $h ] = $cb; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) {}
function add_shortcode( $t, $cb ) { $GLOBALS['_wpbsl_test']['shortcodes'][ $t ] = $cb; }
function register_block_type( $name, $args = array() ) { $GLOBALS['_wpbsl_test']['blocks'][ $name ] = $args; return true; }
function is_admin() { return true; }
function load_plugin_textdomain() { return true; }
function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return $s; }
function esc_html_e( $s, $d = null ) { echo $s; }
function esc_attr( $s ) { return $s; }
function esc_url( $s ) { return $s; }
function register_widget( $c ) {}
function wp_register_script( $h, $s = '', $d = array(), $v = false, $f = false ) {}
function wp_register_style( $h, $s = '', $d = array(), $v = false ) {}
function wp_enqueue_script( $h, $s = '', $d = array(), $v = false, $f = false ) {}
function wp_enqueue_style( $h, $s = '', $d = array(), $v = false ) {}
function wp_localize_script( $h, $o, $l ) {}
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['_wpbsl_test']['options'] ) ? $GLOBALS['_wpbsl_test']['options'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['_wpbsl_test']['options'][ $k ] = $v; return true; }
function add_option( $k, $v ) { $GLOBALS['_wpbsl_test']['options'][ $k ] = $v; return true; }
function wp_create_nonce( $a = -1 ) { return 'nonce'; }
function admin_url( $p = '' ) { return 'http://example.test/wp-admin/' . $p; }
function get_bloginfo( $k = '' ) { return 'Test Site'; }
function wp_json_encode( $d ) { return json_encode( $d ); }
function shortcode_atts( $defaults, $atts ) { return array_merge( $defaults, (array) $atts ); }
function home_url( $p = '' ) { return 'http://example.test' . $p; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function get_locale() { return 'en_US'; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function absint( $n ) { return abs( (int) $n ); }
function wpautop( $s ) { return '<p>' . str_replace( "\n\n", '</p><p>', (string) $s ) . '</p>'; }
function date_i18n( $f, $ts ) { return gmdate( $f, $ts ); }
function add_query_arg( $k, $v, $url ) { return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $k . '=' . $v; }
function get_post_status( $id ) { return false; }

class WP_Widget {
	public $id_base;
	public $id;
	public function __construct( $id_base = '', $name = '', $opts = array() ) { $this->id_base = strtolower( $id_base ); }
	public function get_field_id( $f ) { return $f; }
	public function get_field_name( $f ) { return $f; }
}

class wpdb_stub {
	public $prefix = 'wp_';
	public $insert_id = 0;
	public function get_charset_collate() { return ''; }
	public function prepare( $q, ...$a ) { return $q; }
	public function query( $q ) { return true; }
	public function insert( $t, $d, $f = null ) { $this->insert_id = 123; return 1; }
	public function update( $t, $d, $w, $df = null, $wf = null ) { return 1; }
	public function delete( $t, $w, $wf = null ) { return 1; }
	public function get_row( $q ) { return null; }
	public function get_results( $q ) { return array(); }
	public function get_var( $q ) { return 0; }
}
$GLOBALS['wpdb'] = new wpdb_stub();

/* --------------------------------------------------------------------------
 * 1. Helper unit tests (pure logic — the heart of the booking system).
 * ------------------------------------------------------------------------ */
require $plugin_dir . '/includes/class-wp-booking-system-luca-helpers.php';

echo "\nHelpers: nights & pricing\n";
check_equals( 1, WP_Booking_System_Luca_Helpers::calculate_nights( '2026-06-01', '2026-06-02' ), 'one night between consecutive days' );
check_equals( 7, WP_Booking_System_Luca_Helpers::calculate_nights( '2026-06-01', '2026-06-08' ), 'seven nights for a week' );
check_equals( 1, WP_Booking_System_Luca_Helpers::calculate_nights( '2026-06-08', '2026-06-01' ), 'reversed dates floor to one night' );
check_equals( 1, WP_Booking_System_Luca_Helpers::calculate_nights( 'garbage', 'also-bad' ), 'invalid dates floor to one night' );

// 2 adults @50 + 1 kid @25 over 3 nights = (100 + 25) * 3 = 375.
check_equals( 375.0, WP_Booking_System_Luca_Helpers::calculate_price( '2026-06-01', '2026-06-04', 2, 1, 50, 25 ), '2 adults + 1 kid x 3 nights = 375.00' );
// 1 adult @120.5 over 2 nights = 241.0.
check_equals( 241.0, WP_Booking_System_Luca_Helpers::calculate_price( '2026-06-01', '2026-06-03', 1, 0, 120.5, 60 ), 'fractional nightly rate rounds correctly' );
check_equals( 0.0, WP_Booking_System_Luca_Helpers::calculate_price( '2026-06-01', '2026-06-04', 0, 0, 50, 25 ), 'zero guests cost nothing' );

echo "\nHelpers: date & range validation\n";
check( WP_Booking_System_Luca_Helpers::is_valid_date( '2026-06-11' ), 'valid Y-m-d date accepted' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_date( '2026-13-40' ), 'impossible date rejected' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_date( '11-06-2026' ), 'wrong format rejected' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_date( '' ), 'empty date rejected' );
check( WP_Booking_System_Luca_Helpers::is_valid_range( '2026-06-01', '2026-06-05' ), 'check-out after check-in is a valid range' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_range( '2026-06-05', '2026-06-05' ), 'same day is not a valid range' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_range( '2026-06-05', '2026-06-01' ), 'check-out before check-in rejected' );

echo "\nHelpers: token, capacity & status\n";
check( WP_Booking_System_Luca_Helpers::is_valid_token( str_repeat( 'a', 64 ) ), '64-char hex token accepted' );
check( WP_Booking_System_Luca_Helpers::is_valid_token( bin2hex( random_bytes( 32 ) ) ), 'generated token shape accepted' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_token( str_repeat( 'a', 63 ) ), 'too-short token rejected' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_token( str_repeat( 'z', 64 ) ), 'non-hex token rejected' );
check( WP_Booking_System_Luca_Helpers::exceeds_capacity( 8, 3, 10 ), '11 guests exceed capacity of 10' );
check( ! WP_Booking_System_Luca_Helpers::exceeds_capacity( 6, 4, 10 ), 'exactly 10 guests are within capacity' );
check( WP_Booking_System_Luca_Helpers::is_valid_status( 'confirmed' ), 'confirmed is a valid status' );
check( ! WP_Booking_System_Luca_Helpers::is_valid_status( 'deleted' ), 'unknown status rejected' );

echo "\nHelpers: stay length & booking window (entry options)\n";
// Use a fixed reference date so the window tests are deterministic.
$ref = strtotime( '2026-06-01' );
check_equals( 9, WP_Booking_System_Luca_Helpers::days_until( '2026-06-10', $ref ), 'days_until counts whole days ahead' );
check_equals( -1, WP_Booking_System_Luca_Helpers::days_until( '2026-05-31', $ref ), 'days_until is negative for past dates' );
check( WP_Booking_System_Luca_Helpers::meets_stay_length( '2026-06-01', '2026-06-04', 2, 7 ), '3 nights satisfies min 2 / max 7' );
check( ! WP_Booking_System_Luca_Helpers::meets_stay_length( '2026-06-01', '2026-06-02', 2, 7 ), '1 night fails a 2-night minimum' );
check( ! WP_Booking_System_Luca_Helpers::meets_stay_length( '2026-06-01', '2026-06-15', 2, 7 ), '14 nights fails a 7-night maximum' );
check( WP_Booking_System_Luca_Helpers::meets_stay_length( '2026-06-01', '2026-06-30', 2, 0 ), 'max 0 means no upper limit on nights' );
check( WP_Booking_System_Luca_Helpers::is_within_booking_window( '2026-06-08', 7, 365, $ref ), '7 days out meets a 7-day minimum notice' );
check( ! WP_Booking_System_Luca_Helpers::is_within_booking_window( '2026-06-03', 7, 365, $ref ), '2 days out fails a 7-day minimum notice' );
check( ! WP_Booking_System_Luca_Helpers::is_within_booking_window( '2027-06-01', 0, 90, $ref ), 'a year out fails a 90-day booking window' );
check( WP_Booking_System_Luca_Helpers::is_within_booking_window( '2026-12-01', 0, 0, $ref ), 'max 0 means no upper bound on the window' );

/* --------------------------------------------------------------------------
 * 2. Boot smoke test — load the full plugin and assert registrations.
 * ------------------------------------------------------------------------ */
echo "\nPlugin boot & registration\n";
require $plugin_dir . '/wp-booking-system.php';
$instance = wp_booking_system_luca();

check( $instance instanceof WP_Booking_System_Luca, 'main instance constructed without fatal errors' );
check( $instance->database instanceof WP_Booking_System_Luca_Database, 'database subsystem initialised' );
check( $instance->frontend instanceof WP_Booking_System_Luca_Frontend, 'frontend subsystem initialised' );
check( $instance->email instanceof WP_Booking_System_Luca_Email, 'email subsystem initialised' );

$shortcodes = $GLOBALS['_wpbsl_test']['shortcodes'];
check( isset( $shortcodes['wp_booking_form_luca'] ), 'booking form shortcode registered' );
check( isset( $shortcodes['wp_booking_manage_luca'] ), 'manage booking shortcode registered' );
check( isset( $shortcodes['wp_booking_calendar_luca'] ), 'calendar shortcode registered' );

$actions = $GLOBALS['_wpbsl_test']['actions'];
foreach ( array( 'wp_ajax_wpbsl_submit_booking', 'wp_ajax_nopriv_wpbsl_submit_booking', 'wp_ajax_wpbsl_cancel_booking', 'wp_ajax_wpbsl_update_status' ) as $hook ) {
	check( isset( $actions[ $hook ] ), "AJAX handler hooked: {$hook}" );
}

// Blocks register on the WordPress `init` action; fire it to register them.
if ( isset( $actions['init'] ) ) {
	call_user_func( $actions['init'] );
}
$blocks = $GLOBALS['_wpbsl_test']['blocks'];
check( isset( $blocks['wp-booking-system/calendar'] ), 'calendar block registered' );
check( isset( $blocks['wp-booking-system/form'] ), 'booking form block registered' );

check( isset( $actions['phpmailer_init'] ), 'phpmailer_init hooked for SMTP support' );
check( isset( $actions['wp_ajax_wpbsl_send_test_email'] ), 'test-email AJAX handler hooked' );

/* --------------------------------------------------------------------------
 * 3. SMTP configuration logic (PHPMailer wiring).
 * ------------------------------------------------------------------------ */
echo "\nEmail: SMTP / PHPMailer configuration\n";

// Minimal PHPMailer test double exposing the bits configure_phpmailer touches.
class WPBSL_FakePHPMailer {
	public $Host = '';
	public $Port = 25;
	public $SMTPAuth = false;
	public $SMTPSecure = '';
	public $SMTPAutoTLS = true;
	public $Username = '';
	public $Password = '';
	public $is_smtp = false;
	public function isSMTP() { $this->is_smtp = true; }
}

$email = $instance->email;

// Disabled: PHPMailer must be left untouched (default mail transport).
$GLOBALS['_wpbsl_test']['options']['wpbsl_smtp_enabled'] = 0;
$pm = new WPBSL_FakePHPMailer();
$email->configure_phpmailer( $pm );
check( false === $pm->is_smtp, 'SMTP disabled leaves PHPMailer on default transport' );

// Enabled but no host: still must not switch to SMTP (avoids broken sends).
$GLOBALS['_wpbsl_test']['options']['wpbsl_smtp_enabled'] = 1;
$GLOBALS['_wpbsl_test']['options']['wpbsl_smtp_host'] = '';
$pm = new WPBSL_FakePHPMailer();
$email->configure_phpmailer( $pm );
check( false === $pm->is_smtp, 'SMTP enabled without a host falls back safely' );

// Enabled + Gmail TLS config: PHPMailer wired correctly.
$GLOBALS['_wpbsl_test']['options'] = array_merge(
	$GLOBALS['_wpbsl_test']['options'],
	array(
		'wpbsl_smtp_enabled'    => 1,
		'wpbsl_smtp_host'       => 'smtp.gmail.com',
		'wpbsl_smtp_port'       => 587,
		'wpbsl_smtp_encryption' => 'tls',
		'wpbsl_smtp_auth'       => 1,
		'wpbsl_smtp_username'   => 'host@gmail.com',
		'wpbsl_smtp_password'   => 'app-password',
	)
);
$pm = new WPBSL_FakePHPMailer();
$email->configure_phpmailer( $pm );
check( true === $pm->is_smtp, 'Gmail config switches PHPMailer to SMTP' );
check_equals( 'smtp.gmail.com', $pm->Host, 'SMTP host applied' );
check_equals( 587, $pm->Port, 'SMTP port applied' );
check_equals( 'tls', $pm->SMTPSecure, 'TLS encryption applied' );
check( true === $pm->SMTPAuth, 'SMTP auth enabled' );
check_equals( 'host@gmail.com', $pm->Username, 'SMTP username applied' );
check_equals( 'app-password', $pm->Password, 'SMTP password applied' );

// SSL on 465.
$GLOBALS['_wpbsl_test']['options']['wpbsl_smtp_encryption'] = 'ssl';
$GLOBALS['_wpbsl_test']['options']['wpbsl_smtp_port'] = 465;
$pm = new WPBSL_FakePHPMailer();
$email->configure_phpmailer( $pm );
check_equals( 'ssl', $pm->SMTPSecure, 'SSL encryption applied' );

// Encryption "none" disables auto-TLS and secure transport.
$GLOBALS['_wpbsl_test']['options']['wpbsl_smtp_encryption'] = 'none';
$pm = new WPBSL_FakePHPMailer();
$email->configure_phpmailer( $pm );
check_equals( '', $pm->SMTPSecure, 'encryption "none" clears SMTPSecure' );
check( false === $pm->SMTPAutoTLS, 'encryption "none" disables SMTPAutoTLS' );

echo "\nEmail: merge-tag replacement (customizable templates)\n";
$vars = array(
	'{guest_name}'  => 'Anna Rossi',
	'{check_in}'    => '01 Jul 2026',
	'{total_price}' => '375.00 CHF',
	'{site_name}'   => 'Chalet De Simoni',
);
$tpl = "Dear {guest_name}, your stay from {check_in} costs {total_price}. — {site_name}";
$out = WP_Booking_System_Luca_Email::replace_merge_tags( $tpl, $vars );
check_equals( 'Dear Anna Rossi, your stay from 01 Jul 2026 costs 375.00 CHF. — Chalet De Simoni', $out, 'all merge tags substituted' );
check( false === strpos( $out, '{' ), 'no unreplaced tags remain' );
check_equals( '', WP_Booking_System_Luca_Email::replace_merge_tags( '', $vars ), 'empty template yields empty string' );
check_equals( 'No tags here', WP_Booking_System_Luca_Email::replace_merge_tags( 'No tags here', $vars ), 'plain text passes through unchanged' );
// Unknown tags are left intact (so typos are visible rather than silently dropped).
check_equals( 'Hi {unknown}', WP_Booking_System_Luca_Email::replace_merge_tags( 'Hi {unknown}', $vars ), 'unknown tags are left untouched' );

// Defaults are non-empty and reference the key tags they rely on.
$email_obj = $instance->email;
check( false !== strpos( $email_obj->default_confirmation_body(), '{manage_link}' ), 'confirmation default references {manage_link}' );
check( false !== strpos( $email_obj->default_confirmation_body(), '{booking_details}' ), 'confirmation default references {booking_details}' );
check( false !== strpos( $email_obj->default_admin_body(), '{admin_link}' ), 'admin default references {admin_link}' );
check( false !== strpos( $email_obj->default_confirmation_subject(), '{site_name}' ), 'confirmation subject default references {site_name}' );

echo "\nEmail: ICS calendar attachment\n";
$ics_booking = (object) array(
	'id'         => 213,
	'first_name' => 'Luca',
	'last_name'  => 'De Simoni',
	'check_in'   => '2026-08-03',
	'check_out'  => '2026-08-08',
	'adults'     => 4,
	'kids'       => 0,
);
$ics = $email_obj->generate_ics( $ics_booking );
check( 0 === strpos( $ics, "BEGIN:VCALENDAR\r\n" ), 'ICS starts with VCALENDAR + CRLF' );
check( false !== strpos( $ics, 'DTSTART;VALUE=DATE:20260803' ), 'ICS DTSTART is the check-in date' );
check( false !== strpos( $ics, 'DTEND;VALUE=DATE:20260808' ), 'ICS DTEND is the check-out date' );
check( false !== strpos( $ics, 'UID:wpbsl-213@example.test' ), 'ICS UID embeds booking id and host' );
check( false !== strpos( $ics, '#213' ), 'ICS summary includes the booking number' );
check( false !== strpos( $ics, 'END:VCALENDAR' ), 'ICS terminates with VCALENDAR' );
check( substr_count( $ics, "\r\n" ) >= 14, 'ICS uses CRLF line endings throughout' );

echo "\nHelpers: owner list parsing\n";
$owners = WP_Booking_System_Luca_Helpers::parse_owners( "Alberto\nLuca, Anna\n\nLuca" );
check_equals( array( 'Alberto', 'Luca', 'Anna' ), $owners, 'owners split on newlines/commas, trimmed and de-duplicated' );
check_equals( array(), WP_Booking_System_Luca_Helpers::parse_owners( '   ' ), 'blank owners list yields empty array' );

echo "\nEmail: visual block builder rendering\n";
$block_booking = (object) array(
	'id'         => 5,
	'first_name' => 'Anna',
	'last_name'  => 'Rossi',
	'email'      => 'anna@example.com',
	'phone'      => '',
	'check_in'   => '2026-08-03',
	'check_out'  => '2026-08-08',
	'adults'     => 4,
	'kids'       => 0,
	'total_price'=> 375.0,
	'status'     => 'confirmed',
	'owner'      => 'Alberto',
	'visitors_welcome' => 1,
	'notes'      => '',
	'booking_token' => str_repeat( 'a', 64 ),
);
$render = new ReflectionMethod( 'WP_Booking_System_Luca_Email', 'render_blocks' );
$render->setAccessible( true );
$blocks = array(
	array( 'type' => 'heading', 'text' => 'Hallo {first_name}' ),
	array( 'type' => 'text', 'text' => 'Owner is {owner}.' ),
	array( 'type' => 'details' ),
	array( 'type' => 'button', 'label' => 'Manage', 'url' => '{manage_url}' ),
	array( 'type' => 'divider' ),
	array( 'type' => 'image', 'src' => 'https://example.test/qr.png', 'alt' => 'QR', 'width' => 200 ),
);
$out = $render->invoke( $email_obj, $blocks, $block_booking );
check( false !== strpos( $out, '<h2' ) && false !== strpos( $out, 'Hallo Anna' ), 'heading block renders with merged {first_name}' );
check( false !== strpos( $out, 'Owner is Alberto.' ), 'text block renders with merged {owner}' );
check( false !== strpos( $out, 'booking-details' ), 'details block injects the booking-details box' );
check( false !== strpos( $out, '<a href="http' ) && false !== strpos( $out, '>Manage<' ), 'button block renders an anchor with {manage_url}' );
check( false !== strpos( $out, '<hr' ), 'divider block renders an <hr>' );
check( false !== strpos( $out, '<img src="https://example.test/qr.png"' ) && false !== strpos( $out, 'width="200"' ), 'image block renders an <img> with width' );

/* --------------------------------------------------------------------------
 * Summary.
 * ------------------------------------------------------------------------ */
echo "\n----------------------------------------\n";
$passed = $tests_run - $tests_failed;
echo "Ran {$tests_run} checks: {$passed} passed, {$tests_failed} failed.\n";

exit( $tests_failed > 0 ? 1 : 0 );
