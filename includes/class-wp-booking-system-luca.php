<?php
/**
 * Main plugin class
 *
 * @package WP_Booking_System_Luca
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WP_Booking_System_Luca Class
 */
class WP_Booking_System_Luca {

	/**
	 * The single instance of the class.
	 *
	 * @var WP_Booking_System_Luca
	 */
	protected static $_instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	public $file = '';

	/**
	 * Database instance.
	 *
	 * @var WP_Booking_System_Luca_Database
	 */
	public $database = null;

	/**
	 * Admin instance.
	 *
	 * @var WP_Booking_System_Luca_Admin
	 */
	public $admin = null;

	/**
	 * Frontend instance.
	 *
	 * @var WP_Booking_System_Luca_Frontend
	 */
	public $frontend = null;

	/**
	 * Email instance.
	 *
	 * @var WP_Booking_System_Luca_Email
	 */
	public $email = null;

	/**
	 * Main WP_Booking_System_Luca Instance.
	 *
	 * @param string $file Plugin file path.
	 * @param string $version Plugin version.
	 * @return WP_Booking_System_Luca
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 *
	 * @param string $file Plugin file path.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->version = $version;
		$this->file    = $file;

		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		// Initialize database.
		$this->database = new WP_Booking_System_Luca_Database();

		// Apply any schema changes for existing installs.
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_database' ) );

		// Initialize admin.
		if ( is_admin() ) {
			$this->admin = new WP_Booking_System_Luca_Admin();
		}

		// Initialize frontend.
		$this->frontend = new WP_Booking_System_Luca_Frontend();

		// Initialize AJAX.
		new WP_Booking_System_Luca_Ajax();

		// Initialize email.
		$this->email = new WP_Booking_System_Luca_Email();

		// Register widget.
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		// Initialize block editor support.
		new WP_Booking_System_Luca_Block();

		// Load plugin textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register widget.
	 */
	public function register_widget() {
		register_widget( 'WP_Booking_System_Luca_Widget' );
	}

	/**
	 * Run installation tasks on activation.
	 *
	 * Creates the database table, seeds default options and provisions the
	 * front-end pages so the booking form and magic-link management work
	 * with zero manual configuration.
	 */
	public static function activate() {
		$database = new WP_Booking_System_Luca_Database();
		$database->create_tables();

		self::seed_default_options();
		self::create_pages();

		// Ensure pretty permalinks for the manage page are flushed.
		flush_rewrite_rules();
	}

	/**
	 * Seed default options if they have never been set.
	 */
	private static function seed_default_options() {
		$defaults = array(
			'wpbsl_price_adult'              => 50,
			'wpbsl_price_kid'                => 25,
			'wpbsl_currency'                 => 'CHF',
			'wpbsl_email_from'               => get_option( 'admin_email' ),
			'wpbsl_email_from_name'          => get_bloginfo( 'name' ),
			'wpbsl_admin_notification_email' => get_option( 'admin_email' ),
			'wpbsl_chalet_capacity'          => 10,
			// Booking entry options.
			'wpbsl_min_nights'               => 1,
			'wpbsl_max_nights'               => 0,
			'wpbsl_min_advance_days'         => 0,
			'wpbsl_max_advance_days'         => 0,
			'wpbsl_default_adults'           => 2,
			'wpbsl_default_kids'             => 0,
			'wpbsl_require_phone'            => 0,
			'wpbsl_show_notes'               => 1,
			'wpbsl_auto_confirm'             => 0,
			// Extra booking-form fields.
			'wpbsl_show_owner'               => 1,
			'wpbsl_owners'                   => '',
			'wpbsl_show_visitors'            => 1,
			// Email delivery (SMTP).
			'wpbsl_smtp_enabled'             => 0,
			'wpbsl_smtp_host'                => '',
			'wpbsl_smtp_port'                => 587,
			'wpbsl_smtp_encryption'          => 'tls',
			'wpbsl_smtp_auth'                => 1,
			'wpbsl_smtp_username'            => '',
			'wpbsl_smtp_password'            => '',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key, false ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Create the booking and management pages if they do not yet exist.
	 *
	 * The page IDs are stored so emails and embeds always resolve to a real
	 * URL, even if the site administrator renames or moves the pages.
	 */
	public static function create_pages() {
		$pages = array(
			'wpbsl_booking_page_id' => array(
				'title'   => __( 'Book Now', 'wp-booking-system-luca' ),
				'content' => '[wp_booking_calendar_luca]' . "\n\n" . '[wp_booking_form_luca]',
			),
			'wpbsl_manage_page_id'  => array(
				'title'   => __( 'Manage Booking', 'wp-booking-system-luca' ),
				'content' => '[wp_booking_manage_luca]',
			),
		);

		foreach ( $pages as $option_key => $page ) {
			$existing_id = (int) get_option( $option_key, 0 );

			if ( $existing_id && 'page' === get_post_type( $existing_id ) && 'trash' !== get_post_status( $existing_id ) ) {
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => get_current_user_id(),
				)
			);

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( $option_key, (int) $page_id );
			}
		}
	}

	/**
	 * Run schema upgrades when the plugin version changes.
	 *
	 * dbDelta adds any new columns (e.g. owner, visitors_welcome) to existing
	 * booking tables without touching data.
	 */
	public function maybe_upgrade_database() {
		$installed = get_option( 'wpbsl_db_version', '' );

		if ( $installed === $this->version ) {
			return;
		}

		$this->database->create_tables();
		update_option( 'wpbsl_db_version', $this->version );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wp-booking-system-luca', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clean up if needed.
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', $this->file ) );
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( $this->file ) );
	}
}

