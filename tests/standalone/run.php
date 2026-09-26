<?php
/**
 * Self-contained test runner.
 *
 * Runs without PHPUnit or a WordPress install by stubbing the small slice of
 * the WordPress API the plugin touches at load time. It verifies:
 *
 *   1. The pure booking logic in WP_Booking_Simple_Helpers.
 *   2. That the whole plugin boots with no fatal errors and registers its
 *      shortcodes, blocks and AJAX handlers.
 *
 * Usage: php tests/standalone/run.php
 *
 * @package WP_Booking_Simple
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
function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['_wpbsl_test']['actions'][ $h ] = $cb; $GLOBALS['_wpbsl_test']['all_actions'][ $h ][ $p ][] = $cb; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) {}
function add_shortcode( $t, $cb ) { $GLOBALS['_wpbsl_test']['shortcodes'][ $t ] = $cb; }
function register_block_type( $name, $args = array() ) { $GLOBALS['_wpbsl_test']['blocks'][ $name ] = $args; return true; }
function is_admin() { return true; }
function load_plugin_textdomain() { return true; }
function __( $s, $d = null ) { return $s; }
function _n( $single, $plural, $number, $d = null ) { return 1 === (int) $number ? $single : $plural; }
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
require $plugin_dir . '/includes/class-wp-booking-simple-helpers.php';
require $plugin_dir . '/includes/class-wp-booking-simple-stats.php';

echo "\nHelpers: nights & pricing\n";
check_equals( 1, WP_Booking_Simple_Helpers::calculate_nights( '2026-06-01', '2026-06-02' ), 'one night between consecutive days' );
check_equals( 7, WP_Booking_Simple_Helpers::calculate_nights( '2026-06-01', '2026-06-08' ), 'seven nights for a week' );
check_equals( 1, WP_Booking_Simple_Helpers::calculate_nights( '2026-06-08', '2026-06-01' ), 'reversed dates floor to one night' );
check_equals( 1, WP_Booking_Simple_Helpers::calculate_nights( 'garbage', 'also-bad' ), 'invalid dates floor to one night' );

// 2 adults @50 + 1 kid @25 over 3 nights = (100 + 25) * 3 = 375.
check_equals( 375.0, WP_Booking_Simple_Helpers::calculate_price( '2026-06-01', '2026-06-04', 2, 1, 50, 25 ), '2 adults + 1 kid x 3 nights = 375.00' );
// 1 adult @120.5 over 2 nights = 241.0.
check_equals( 241.0, WP_Booking_Simple_Helpers::calculate_price( '2026-06-01', '2026-06-03', 1, 0, 120.5, 60 ), 'fractional nightly rate rounds correctly' );
check_equals( 0.0, WP_Booking_Simple_Helpers::calculate_price( '2026-06-01', '2026-06-04', 0, 0, 50, 25 ), 'zero guests cost nothing' );

echo "\nHelpers: date & range validation\n";
check( WP_Booking_Simple_Helpers::is_valid_date( '2026-06-11' ), 'valid Y-m-d date accepted' );
check( ! WP_Booking_Simple_Helpers::is_valid_date( '2026-13-40' ), 'impossible date rejected' );
check( ! WP_Booking_Simple_Helpers::is_valid_date( '11-06-2026' ), 'wrong format rejected' );
check( ! WP_Booking_Simple_Helpers::is_valid_date( '' ), 'empty date rejected' );
check( WP_Booking_Simple_Helpers::is_valid_range( '2026-06-01', '2026-06-05' ), 'check-out after check-in is a valid range' );
check( ! WP_Booking_Simple_Helpers::is_valid_range( '2026-06-05', '2026-06-05' ), 'same day is not a valid range' );
check( ! WP_Booking_Simple_Helpers::is_valid_range( '2026-06-05', '2026-06-01' ), 'check-out before check-in rejected' );

echo "\nHelpers: token, capacity & status\n";
check( WP_Booking_Simple_Helpers::is_valid_token( str_repeat( 'a', 64 ) ), '64-char hex token accepted' );
check( WP_Booking_Simple_Helpers::is_valid_token( bin2hex( random_bytes( 32 ) ) ), 'generated token shape accepted' );
check( ! WP_Booking_Simple_Helpers::is_valid_token( str_repeat( 'a', 63 ) ), 'too-short token rejected' );
check( ! WP_Booking_Simple_Helpers::is_valid_token( str_repeat( 'z', 64 ) ), 'non-hex token rejected' );
check( WP_Booking_Simple_Helpers::exceeds_capacity( 8, 3, 10 ), '11 guests exceed capacity of 10' );
check( ! WP_Booking_Simple_Helpers::exceeds_capacity( 6, 4, 10 ), 'exactly 10 guests are within capacity' );
check( WP_Booking_Simple_Helpers::is_valid_status( 'confirmed' ), 'confirmed is a valid status' );
check( ! WP_Booking_Simple_Helpers::is_valid_status( 'deleted' ), 'unknown status rejected' );

echo "\nHelpers: stay length & booking window (entry options)\n";
// Use a fixed reference date so the window tests are deterministic.
$ref = strtotime( '2026-06-01' );
check_equals( 9, WP_Booking_Simple_Helpers::days_until( '2026-06-10', $ref ), 'days_until counts whole days ahead' );
check_equals( -1, WP_Booking_Simple_Helpers::days_until( '2026-05-31', $ref ), 'days_until is negative for past dates' );
check( WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-04', 2, 7 ), '3 nights satisfies min 2 / max 7' );
check( ! WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-02', 2, 7 ), '1 night fails a 2-night minimum' );
check( ! WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-15', 2, 7 ), '14 nights fails a 7-night maximum' );
check( WP_Booking_Simple_Helpers::meets_stay_length( '2026-06-01', '2026-06-30', 2, 0 ), 'max 0 means no upper limit on nights' );
check( WP_Booking_Simple_Helpers::is_within_booking_window( '2026-06-08', 7, 365, $ref ), '7 days out meets a 7-day minimum notice' );
check( ! WP_Booking_Simple_Helpers::is_within_booking_window( '2026-06-03', 7, 365, $ref ), '2 days out fails a 7-day minimum notice' );
check( ! WP_Booking_Simple_Helpers::is_within_booking_window( '2027-06-01', 0, 90, $ref ), 'a year out fails a 90-day booking window' );
check( WP_Booking_Simple_Helpers::is_within_booking_window( '2026-12-01', 0, 0, $ref ), 'max 0 means no upper bound on the window' );

/* --------------------------------------------------------------------------
 * 2. Boot smoke test — load the full plugin and assert registrations.
 * ------------------------------------------------------------------------ */
echo "\nPlugin boot & registration\n";
require $plugin_dir . '/wp-booking-simple.php';
$instance = wp_booking_simple();

check( $instance instanceof WP_Booking_Simple, 'main instance constructed without fatal errors' );
check( $instance->database instanceof WP_Booking_Simple_Database, 'database subsystem initialised' );
check( $instance->frontend instanceof WP_Booking_Simple_Frontend, 'frontend subsystem initialised' );
check( $instance->email instanceof WP_Booking_Simple_Email, 'email subsystem initialised' );

$shortcodes = $GLOBALS['_wpbsl_test']['shortcodes'];
check( isset( $shortcodes['wp_booking_form_simple'] ), 'booking form shortcode registered' );
check( isset( $shortcodes['wp_booking_manage_simple'] ), 'manage booking shortcode registered' );
check( isset( $shortcodes['wp_booking_calendar_simple'] ), 'calendar shortcode registered' );

$actions = $GLOBALS['_wpbsl_test']['actions'];
foreach ( array( 'wp_ajax_wpbsl_submit_booking', 'wp_ajax_nopriv_wpbsl_submit_booking', 'wp_ajax_wpbsl_cancel_booking', 'wp_ajax_wpbsl_update_status' ) as $hook ) {
	check( isset( $actions[ $hook ] ), "AJAX handler hooked: {$hook}" );
}

// Blocks register on the WordPress `init` action; fire it to register them.
// Run every init callback in priority order (styles, translations, blocks).
$init_callbacks = isset( $GLOBALS['_wpbsl_test']['all_actions']['init'] ) ? $GLOBALS['_wpbsl_test']['all_actions']['init'] : array();
ksort( $init_callbacks );
foreach ( $init_callbacks as $callbacks ) {
	foreach ( $callbacks as $callback ) {
		call_user_func( $callback );
	}
}
$blocks = $GLOBALS['_wpbsl_test']['blocks'];
check( isset( $blocks['wp-booking-simple/calendar'] ), 'calendar block registered' );
check( isset( $blocks['wp-booking-simple/form'] ), 'booking form block registered' );

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

echo "\nGuest count wording (shared by emails and the manage page)\n";
$guests_of = function ( $adults, $kids ) {
	return WP_Booking_Simple_Helpers::guests_label( array( 'adults' => $adults, 'kids' => $kids ) );
};
check_equals( '2 adults, 1 kid', $guests_of( 2, 1 ), 'a single kid is not pluralised' );
check_equals( '1 adult', $guests_of( 1, 0 ), 'a single adult is not pluralised' );
check_equals( '2 adults, 3 kids', $guests_of( 2, 3 ), 'several kids are pluralised' );
check_equals( '4 adults', $guests_of( 4, 0 ), 'zero kids are left out entirely' );

echo "\nEmail: merge-tag replacement (customizable templates)\n";
$vars = array(
	'{guest_name}'  => 'Anna Rossi',
	'{check_in}'    => '01 Jul 2026',
	'{total_price}' => '375.00 CHF',
	'{site_name}'   => 'Chalet De Simoni',
);
$tpl = "Dear {guest_name}, your stay from {check_in} costs {total_price}. — {site_name}";
$out = WP_Booking_Simple_Email::replace_merge_tags( $tpl, $vars );
check_equals( 'Dear Anna Rossi, your stay from 01 Jul 2026 costs 375.00 CHF. — Chalet De Simoni', $out, 'all merge tags substituted' );
check( false === strpos( $out, '{' ), 'no unreplaced tags remain' );
check_equals( '', WP_Booking_Simple_Email::replace_merge_tags( '', $vars ), 'empty template yields empty string' );
check_equals( 'No tags here', WP_Booking_Simple_Email::replace_merge_tags( 'No tags here', $vars ), 'plain text passes through unchanged' );
// Unknown tags are left intact (so typos are visible rather than silently dropped).
check_equals( 'Hi {unknown}', WP_Booking_Simple_Email::replace_merge_tags( 'Hi {unknown}', $vars ), 'unknown tags are left untouched' );

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
check( false !== strpos( $ics, 'UID:wpbs-213@example.test' ), 'ICS UID embeds booking id and host' );
check( false !== strpos( $ics, '#213' ), 'ICS summary includes the booking number' );
check( false !== strpos( $ics, 'END:VCALENDAR' ), 'ICS terminates with VCALENDAR' );
check( substr_count( $ics, "\r\n" ) >= 14, 'ICS uses CRLF line endings throughout' );

echo "\nHelpers: owner list parsing\n";
$owners = WP_Booking_Simple_Helpers::parse_owners( "Alberto\nLuca, Anna\n\nLuca" );
check_equals( array( 'Alberto', 'Luca', 'Anna' ), $owners, 'owners split on newlines/commas, trimmed and de-duplicated' );
check_equals( array(), WP_Booking_Simple_Helpers::parse_owners( '   ' ), 'blank owners list yields empty array' );

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
$render = new ReflectionMethod( 'WP_Booking_Simple_Email', 'render_blocks' );
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

echo "\nHelpers: booking change diff\n";
$old_booking = (object) array(
	'first_name' => 'Anna', 'last_name' => 'Rossi', 'email' => 'a@x.com', 'phone' => '',
	'check_in' => '2026-08-03', 'check_out' => '2026-08-08', 'adults' => 2, 'kids' => 0,
	'owner' => 'Alberto', 'visitors_welcome' => 0, 'total_price' => 500.0, 'status' => 'pending',
	'payment_status' => 'unpaid', 'payment_method' => '', 'amount_paid' => 0.0, 'notes' => '',
);
$new_vals = array(
	'first_name' => 'Anna', 'last_name' => 'Rossi', 'email' => 'a@x.com', 'phone' => '',
	'check_in' => '2026-08-03', 'check_out' => '2026-08-10', 'adults' => 2, 'kids' => 0,
	'owner' => 'Luca', 'visitors_welcome' => 1, 'total_price' => 500.00, 'status' => 'confirmed',
	'payment_status' => 'paid', 'payment_method' => 'twint', 'amount_paid' => 500.0, 'notes' => '',
);
$diff = WP_Booking_Simple_Helpers::compute_changes( $old_booking, $new_vals );
check_equals( array( 'check_out', 'owner', 'visitors_welcome', 'status', 'payment_status', 'payment_method', 'amount_paid' ), array_keys( $diff ), 'compute_changes detects exactly the changed fields' );
check_equals( array( 'from' => 'Alberto', 'to' => 'Luca' ), $diff['owner'], 'owner change captures from/to' );
check( ! isset( $diff['total_price'] ), 'numeric 500.0 vs 500.00 is not a change' );
check( ! isset( $diff['first_name'] ), 'unchanged fields are excluded' );

echo "\nHelpers: Swiss IBAN validation\n";
check( WP_Booking_Simple_Helpers::is_valid_ch_iban( 'CH9300762011623852957' ), 'valid CH IBAN accepted' );
check( WP_Booking_Simple_Helpers::is_valid_ch_iban( 'CH93 0076 2011 6238 5295 7' ), 'spaced CH IBAN accepted' );
check( WP_Booking_Simple_Helpers::is_valid_ch_iban( 'LI21088100002324013AA' ), 'valid LI IBAN accepted' );
check( ! WP_Booking_Simple_Helpers::is_valid_ch_iban( 'CH9300762011623852958' ), 'wrong check digits rejected' );
check( ! WP_Booking_Simple_Helpers::is_valid_ch_iban( 'DE89370400440532013000' ), 'non-CH/LI IBAN rejected' );
check( ! WP_Booking_Simple_Helpers::is_valid_ch_iban( 'CH12' ), 'too-short IBAN rejected' );

echo "\nHelpers: Swiss QR-bill payload\n";
$qr = WP_Booking_Simple_Helpers::build_swiss_qr_payload( array(
	'iban'    => 'CH93 0076 2011 6238 5295 7',
	'name'    => 'Chalet Desimoni',
	'address' => 'Musterstrasse 1',
	'city'    => '8000 Zuerich',
	'amount'  => 250,
	'currency'=> 'CHF',
	'message' => 'Booking #12 Mark Bianchi',
) );
$rows = explode( "\n", $qr );
check_equals( 'SPC', $rows[0], 'payload starts with SPC' );
check_equals( '0200', $rows[1], 'version 0200' );
check_equals( 'CH9300762011623852957', $rows[3], 'IBAN normalised (spaces stripped)' );
check_equals( '250.00', $rows[18], 'amount formatted with 2 decimals' );
check_equals( 'CHF', $rows[19], 'currency present' );
check_equals( 'NON', $rows[27], 'reference type NON' );
check_equals( 'Booking #12 Mark Bianchi', $rows[29], 'unstructured message present' );
check_equals( 'EPD', $rows[30], 'payload ends with EPD trailer' );
check_equals( 31, count( $rows ), 'payload has the 31 Swiss QR elements' );

echo "\nHelpers: outstanding balance\n";
check_equals( 200.0, WP_Booking_Simple_Helpers::amount_due( (object) array( 'total_price' => 500, 'amount_paid' => 300 ) ), 'due = total - paid' );
check_equals( 0.0, WP_Booking_Simple_Helpers::amount_due( (object) array( 'total_price' => 500, 'amount_paid' => 500 ) ), 'fully paid is zero due' );
check_equals( 0.0, WP_Booking_Simple_Helpers::amount_due( (object) array( 'total_price' => 100, 'amount_paid' => 150 ) ), 'overpaid clamps to zero' );
check_equals( 80.0, WP_Booking_Simple_Helpers::amount_due( array( 'total_price' => 80 ) ), 'missing amount_paid treats as unpaid (array input)' );

echo "\nHelpers: date-range filter\n";
$range_set = array(
	(object) array( 'check_in' => '2026-07-15' ),
	(object) array( 'check_in' => '2026-08-01' ),
	(object) array( 'check_in' => '2026-09-30' ),
);
check_equals( 3, count( WP_Booking_Simple_Helpers::filter_by_date_range( $range_set, '', '' ) ), 'empty bounds return everything' );
check_equals( 1, count( WP_Booking_Simple_Helpers::filter_by_date_range( $range_set, '2026-08-01', '2026-08-31' ) ), 'August window keeps only the August check-in' );
check_equals( 2, count( WP_Booking_Simple_Helpers::filter_by_date_range( $range_set, '2026-08-01', '' ) ), 'open-ended from-bound is inclusive' );
check_equals( 2, count( WP_Booking_Simple_Helpers::filter_by_date_range( $range_set, '', '2026-08-01' ) ), 'open-ended to-bound is inclusive' );

echo "\nStats: dashboard aggregation\n";
$sample = array(
	(object) array( 'first_name' => 'Anna', 'last_name' => 'Rossi', 'email' => 'a@x.com', 'check_in' => '2026-08-03', 'check_out' => '2026-08-08', 'adults' => 2, 'kids' => 1, 'owner' => 'Alberto', 'visitors_welcome' => 1, 'total_price' => 600.0, 'amount_paid' => 600.0, 'payment_method' => 'bank', 'status' => 'confirmed' ),
	(object) array( 'first_name' => 'Anna', 'last_name' => 'Rossi', 'email' => 'a@x.com', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'adults' => 2, 'kids' => 0, 'owner' => 'Luca', 'visitors_welcome' => 0, 'total_price' => 200.0, 'amount_paid' => 100.0, 'payment_method' => 'twint', 'status' => 'pending' ),
	(object) array( 'first_name' => 'Bob', 'last_name' => 'Neri', 'email' => 'b@x.com', 'check_in' => '2026-09-10', 'check_out' => '2026-09-12', 'adults' => 4, 'kids' => 0, 'owner' => 'Alberto', 'visitors_welcome' => 0, 'total_price' => 400.0, 'amount_paid' => 0.0, 'payment_method' => '', 'status' => 'confirmed' ),
	(object) array( 'first_name' => 'Cara', 'last_name' => 'X', 'email' => 'c@x.com', 'check_in' => '2026-09-20', 'check_out' => '2026-09-25', 'adults' => 1, 'kids' => 0, 'owner' => '', 'visitors_welcome' => 0, 'total_price' => 250.0, 'amount_paid' => 0.0, 'payment_method' => '', 'status' => 'cancelled' ),
);
$s = WP_Booking_Simple_Stats::summarize( $sample );
check_equals( 3, $s['totals']['bookings'], 'cancelled bookings excluded from totals (3 of 4)' );
check_equals( 1, $s['totals']['cancelled'], 'cancelled tally counts the cancelled booking' );
check_equals( 9, $s['totals']['nights'], 'nights summed across non-cancelled (5+2+2)' );
check_equals( 9, $s['totals']['guests'], 'guests summed (3+2+4)' );
check_equals( 1200.0, $s['totals']['revenue'], 'revenue summed (600+200+400)' );
check_equals( 700.0, $s['totals']['collected'], 'collected summed (600+100)' );
check_equals( 500.0, $s['totals']['outstanding'], 'outstanding = revenue - collected' );
check_equals( 'a@x.com', $s['by_guest'][0]['email'], 'top guest by bookings is the repeat guest' );
check_equals( 2, $s['by_guest'][0]['bookings'], 'repeat guest has 2 bookings merged by email' );
check_equals( 'Alberto', $s['by_owner'][0]['owner'], 'Alberto leads owner usage by nights (5+2=7)' );
check_equals( 7, $s['by_owner'][0]['nights'], 'Alberto owner nights total' );
check_equals( 1, $s['totals']['visitors'], 'one booking welcomes visitors' );

echo "\nHelpers: status & payment maps\n";
check_equals( array( 'pending', 'confirmed', 'cancelled' ), WP_Booking_Simple_Helpers::allowed_statuses(), 'allowed booking statuses' );
$ps = WP_Booking_Simple_Helpers::payment_statuses();
check_equals( array( 'unpaid', 'partial', 'paid', 'refunded' ), array_keys( $ps ), 'payment statuses incl. refunded' );
$pm = WP_Booking_Simple_Helpers::payment_methods();
check_equals( array( 'bank', 'twint', 'bar' ), array_keys( $pm ), 'payment methods (bank/twint/cash)' );
check( WP_Booking_Simple_Helpers::is_valid_status( 'confirmed' ), 'confirmed is a valid status' );
check( ! WP_Booking_Simple_Helpers::is_valid_status( 'refunded' ), 'payment status is not a booking status' );

echo "\nHelpers: IBAN normalization\n";
check_equals( 'CH2080808007270588572', WP_Booking_Simple_Helpers::normalize_iban( ' ch20 8080 8007 2705 8857 2 ' ), 'normalize strips spaces and uppercases' );
check_equals( '', WP_Booking_Simple_Helpers::normalize_iban( '' ), 'empty IBAN normalizes to empty' );

echo "\nHelpers: tracked fields\n";
$tf = WP_Booking_Simple_Helpers::tracked_fields();
check( isset( $tf['payment_status'], $tf['total_price'], $tf['check_in'], $tf['status'] ), 'tracked fields include the key booking fields' );
check( is_string( $tf['payment_status'] ) && '' !== $tf['payment_status'], 'each tracked field has a human label' );

echo "\nHelpers: removable booking-form fields\n";
$saved_options = $GLOBALS['_wpbsl_test']['options'];

// Nothing configured: every optional field defaults to shown, except the owner
// dropdown, which also needs at least one configured name.
$GLOBALS['_wpbsl_test']['options'] = array();
$ff = WP_Booking_Simple_Helpers::form_fields();
check( true === $ff['last_name'], 'last name shown by default' );
check( true === $ff['phone'], 'phone shown by default' );
check( true === $ff['kids'], 'kids shown by default' );
check( true === $ff['notes'], 'notes shown by default' );
check( true === $ff['visitors'], 'visitors shown by default' );
check( false === $ff['owner'], 'owner hidden while no owners are configured' );

$GLOBALS['_wpbsl_test']['options']['wpbsl_owners'] = "Alberto\nLuca";
check( true === WP_Booking_Simple_Helpers::shows_field( 'owner' ), 'owner shown once names exist' );

// Switching fields off.
$GLOBALS['_wpbsl_test']['options']['wpbsl_show_last_name'] = 0;
$GLOBALS['_wpbsl_test']['options']['wpbsl_show_phone']     = 0;
$GLOBALS['_wpbsl_test']['options']['wpbsl_show_kids']      = 0;
$ff = WP_Booking_Simple_Helpers::form_fields();
check( false === $ff['last_name'], 'last name can be switched off' );
check( false === $ff['phone'], 'phone can be switched off' );
check( false === $ff['kids'], 'kids can be switched off' );
check( true === $ff['notes'], 'switching one field off leaves the others alone' );

// Mandatory fields are not part of the map and must never read as hidden.
check( true === WP_Booking_Simple_Helpers::shows_field( 'email' ), 'unknown/mandatory field reads as shown' );
check( true === WP_Booking_Simple_Helpers::shows_field( 'check_in' ), 'check-in is never hidden' );
check( false === WP_Booking_Simple_Helpers::shows_field( 'kids' ), 'shows_field agrees with the map' );

// With kids switched off nobody is charged the kid rate: 3 nights x 2 adults
// x 50 = 300, where the same stay with one kid would have been 375.
check_equals( 300, WP_Booking_Simple_Helpers::calculate_price( '2026-03-01', '2026-03-04', 2, 0, 50, 25 ), 'kids forced to zero bills adults only' );
check_equals( 375, WP_Booking_Simple_Helpers::calculate_price( '2026-03-01', '2026-03-04', 2, 1, 50, 25 ), 'the same stay with a kid costs more' );

$GLOBALS['_wpbsl_test']['options'] = $saved_options;

echo "\nHelpers: owners parsing edge cases\n";
check_equals( array( 'Alberto', 'Luca' ), WP_Booking_Simple_Helpers::parse_owners( "  Alberto \n\n Luca \n" ), 'owners trimmed and blank lines dropped' );
check_equals( array(), WP_Booking_Simple_Helpers::parse_owners( "\n \n" ), 'all-blank owners list yields empty array' );

echo "\nHelpers: capacity & range edge cases\n";
check( WP_Booking_Simple_Helpers::exceeds_capacity( 8, 3, 10 ), '11 guests exceeds capacity of 10' );
check( ! WP_Booking_Simple_Helpers::exceeds_capacity( 6, 4, 10 ), 'exactly at capacity is allowed' );
check( ! WP_Booking_Simple_Helpers::is_valid_range( '2026-08-05', '2026-08-05' ), 'same-day range is invalid (needs a night)' );
check( WP_Booking_Simple_Helpers::is_valid_range( '2026-08-05', '2026-08-06' ), 'one-night range is valid' );

/* --------------------------------------------------------------------------
 * Theme & builder integration (Astra, Spectra / Gutenberg, Elementor).
 * ------------------------------------------------------------------------ */
echo "\nTheme: colour sanitiser\n";
foreach ( array( '#8B0000', '#fff', 'rgb(1, 2, 3)', 'hsl(0 100% 27%)', 'var(--ast-global-color-0)', 'var(--wp--preset--color--primary)', 'var(--e-global-color-primary)' ) as $c ) {
	check_equals( $c, WP_Booking_Simple_Theme::sanitize_color( $c ), "accepts {$c}" );
}
foreach ( array( 'red;}body{', 'var(--x);}a{', 'var(--a, red)', 'url(javascript:1)', '#12' ) as $c ) {
	check_equals( '', WP_Booking_Simple_Theme::sanitize_color( $c ), "rejects {$c}" );
}

echo "\nTheme: colour scheme\n";
$saved_options = $GLOBALS['_wpbsl_test']['options'];
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) { return $value; }
}
unset( $GLOBALS['_wpbsl_test']['options']['wpbsl_color_scheme'] );
check_equals( '', WP_Booking_Simple_Theme::scheme_css(), 'plugin palette by default (no inline CSS)' );
$GLOBALS['_wpbsl_test']['options']['wpbsl_color_scheme'] = 'theme';
$scheme = WP_Booking_Simple_Theme::scheme_css();
check( 0 === strpos( $scheme, 'body{' ), 'theme scheme is declared on body (where Elementor kit colours resolve)' );
check( false !== strpos( $scheme, '--wpbs-primary:var(--ast-global-color-0, var(--e-global-color-primary, var(--wp--preset--color--primary, #8B0000)))' ), 'primary follows Astra, then Elementor, then block-theme presets' );
check( false !== strpos( $scheme, '--wpbs-button-bg:var(--wpbs-primary)' ), 'buttons re-derive from the theme primary' );
if ( ! function_exists( 'astra_get_option' ) ) {
	function astra_get_option( $key, $default = '' ) {
		$o = array(
			'button-radius-fields' => array( 'desktop' => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ), 'desktop-unit' => 'px' ),
			'button-bg-color'      => 'var(--ast-global-color-1)',
			'button-color'         => 'nope;}',
			'headings-font-family' => "'Lora', serif",
		);
		return isset( $o[ $key ] ) ? $o[ $key ] : $default;
	}
}
$scheme = WP_Booking_Simple_Theme::scheme_css();
check( false !== strpos( $scheme, '--wpbs-button-radius:20px 20px 20px 20px' ), 'Astra button radius is used' );
check( false !== strpos( $scheme, '--wpbs-button-bg:var(--ast-global-color-1)' ), 'Astra button colour is used' );
check( false === strpos( $scheme, 'nope' ), 'invalid Astra colour is ignored' );
check( false !== strpos( $scheme, "--wpbs-heading-font:'Lora', serif" ), 'Astra heading font is used' );
$GLOBALS['_wpbsl_test']['options'] = $saved_options;

echo "\nBlocks: editor integration\n";
$form_block = $GLOBALS['_wpbsl_test']['blocks']['wp-booking-simple/form'];
check_equals( 3, isset( $form_block['api_version'] ) ? $form_block['api_version'] : 0, 'blocks use block API v3 (iframed editor)' );
check_equals( 'wp-booking-simple-frontend', isset( $form_block['style'] ) ? $form_block['style'] : '', 'front-end styles load in the editor canvas' );
check( isset( $form_block['supports']['align'] ) && in_array( 'full', $form_block['supports']['align'], true ), 'wide/full alignment supported' );
check( ! empty( $form_block['supports']['spacing']['padding'] ), 'spacing supported' );
$legacy_block = $GLOBALS['_wpbsl_test']['blocks']['wp-booking-system/form'];
check( false === $legacy_block['supports']['inserter'] && ! empty( $legacy_block['supports']['align'] ), 'legacy alias hidden from the inserter but keeps the supports' );
$block_js = file_get_contents( $plugin_dir . '/assets/js/block.js' );
check( false !== strpos( $block_js, "registerBlockType('wp-booking-system/form'" ), 'legacy block names are registered in the editor' );
check( false !== strpos( $block_js, 'useBlockProps()' ), 'edit() uses useBlockProps' );
check( false !== strpos( $block_js, '__nextHasNoMarginBottom: true' ), 'controls use the WordPress 7.0 control styles' );
check( (bool) preg_match( '/Requires at least:\s*6\.5/', file_get_contents( $plugin_dir . '/wp-booking-simple.php' ) ), 'plugin header requires WordPress 6.5' );

echo "\nElementor: editor re-initialisation\n";
$frontend_js = file_get_contents( $plugin_dir . '/assets/js/frontend.js' );
check( false !== strpos( $frontend_js, "frontend/element_ready/' + name + '.default" ), 'widgets re-initialise after an Elementor re-render' );
check( false !== strpos( $frontend_js, "$(document).on('submit', '#wpbs-booking-form'" ), 'form submit handler is delegated' );
check( false !== strpos( $frontend_js, 'calendarEl.dataset.wpbsReady' ), 'calendar init is idempotent' );

/* --------------------------------------------------------------------------
 * WordPress.org directory readiness.
 * ------------------------------------------------------------------------ */
echo "\nSecurity: SMTP password encryption\n";
if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scheme = 'auth' ) { return $GLOBALS['_wpbsl_salt'] ?? 'test-salt-A'; }
}
$enc = WP_Booking_Simple_Helpers::encrypt_secret( 'p@ss "w0rd" ü' );
check( 0 === strpos( $enc, WP_Booking_Simple_Helpers::SECRET_PREFIX ), 'secret is stored with the encryption prefix' );
check( false === strpos( $enc, 'p@ss' ), 'plain text does not appear in the stored value' );
check_equals( 'p@ss "w0rd" ü', WP_Booking_Simple_Helpers::decrypt_secret( $enc ), 'round-trips, including quotes and UTF-8' );
check( $enc !== WP_Booking_Simple_Helpers::encrypt_secret( 'p@ss "w0rd" ü' ), 'a fresh IV each time (no identical ciphertexts)' );
check_equals( 'legacy-plain', WP_Booking_Simple_Helpers::decrypt_secret( 'legacy-plain' ), 'a password saved by an earlier version still works' );
check_equals( '', WP_Booking_Simple_Helpers::encrypt_secret( '' ), 'empty stays empty' );
$tampered = substr( $enc, 0, -4 ) . ( 'AAAA' === substr( $enc, -4 ) ? 'BBBB' : 'AAAA' );
check_equals( '', WP_Booking_Simple_Helpers::decrypt_secret( $tampered ), 'tampered ciphertext is rejected (GCM tag)' );
$GLOBALS['_wpbsl_salt'] = 'rotated-salt';
check_equals( '', WP_Booking_Simple_Helpers::decrypt_secret( $enc ), 'rotated salts make the old value unreadable (re-enter the password)' );
unset( $GLOBALS['_wpbsl_salt'] );

echo "\nDirectory: code and headers\n";
$php_src = array();
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin_dir . '/includes' ) ) as $f ) {
	if ( '.php' === substr( $f->getPathname(), -4 ) ) {
		$php_src[ $f->getPathname() ] = file_get_contents( $f->getPathname() );
	}
}
$php_src['main'] = file_get_contents( $plugin_dir . '/wp-booking-simple.php' );
$all_php = implode( "\n", $php_src );
check( ! preg_match( '/<script(?![^>]*application\/ld\+json)/i', $all_php ), 'no inline <script> tags' );
check( ! preg_match( '/(?<![\w>:$])date\(/', $all_php ), 'no date() (gmdate()/wp_date() instead)' );
// absint()/intval() need no unslashing (WPCS treats them as unslash-safe).
check( ! preg_match( '/(sanitize_\w+|esc_url_raw)\(\s*\$_(POST|GET|REQUEST)\[/', $all_php ), 'request data is unslashed before sanitising' );
check( ! preg_match( '/famiglia-desimoni|Raiffeisen|Alberto|De Simoni\b(?! ?\*)/i', preg_replace( '/^\s*\*\s*(Author|@author).*$/m', '', $all_php ) ), 'no family/site-specific strings outside the author credit' );
check( false === strpos( $all_php, "get_option( 'wpbsl_smtp_password'" ) || false !== strpos( $all_php, 'decrypt_secret( get_option( \'wpbsl_smtp_password\'' ), 'SMTP password is only read through decrypt_secret()' );
$main_hdr = $php_src['main'];
foreach ( array( 'Plugin Name', 'Version', 'Requires at least', 'Requires PHP', 'Author', 'License', 'License URI', 'Text Domain', 'Domain Path' ) as $h ) {
	check( (bool) preg_match( '/^\s*\*\s*' . preg_quote( $h, '/' ) . ':\s*\S/m', $main_hdr ), "plugin header: {$h}" );
}
$readme_src = file_get_contents( $plugin_dir . '/readme.txt' );
foreach ( array( 'Description', 'Installation', 'Frequently Asked Questions', 'External services', 'Changelog' ) as $sec ) {
	check( false !== strpos( $readme_src, "== {$sec} ==" ), "readme section: {$sec}" );
}

/* --------------------------------------------------------------------------
 * Summary.
 * ------------------------------------------------------------------------ */
echo "\n----------------------------------------\n";
$passed = $tests_run - $tests_failed;
echo "Ran {$tests_run} checks: {$passed} passed, {$tests_failed} failed.\n";

exit( $tests_failed > 0 ? 1 : 0 );
