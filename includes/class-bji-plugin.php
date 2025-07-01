<?php
/**
 * Main plugin class
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class BJI_Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var BJI_Plugin
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @var BJI_Admin
	 */
	public $admin;

	/**
	 * Utils instance.
	 *
	 * @var BJI_Utils
	 */
	public $utils;

	/**
	 * ACF Handler instance.
	 *
	 * @var BJI_ACF_Handler
	 */
	public $acf_handler;

	/**
	 * File Handler instance.
	 *
	 * @var BJI_File_Handler
	 */
	public $file_handler;

	/**
	 * Import Processor instance.
	 *
	 * @var BJI_Import_Processor
	 */
	public $import_processor;

	/**
	 * Get plugin instance.
	 *
	 * @return BJI_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		// Initialize utility classes.
		$this->utils           = new BJI_Utils();
		$this->acf_handler     = new BJI_ACF_Handler();
		$this->file_handler    = new BJI_File_Handler();
		$this->import_processor = new BJI_Import_Processor();

		// Initialize admin interface.
		if ( is_admin() ) {
			$this->admin = new BJI_Admin();
		}

		// Load plugin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( 'tools_page_' . BJI_PLUGIN_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'bji-admin',
			BJI_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			BJI_VERSION,
			true
		);

		wp_enqueue_style(
			'bji-admin',
			BJI_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BJI_VERSION
		);

		wp_localize_script(
			'bji-admin',
			'bjiAdmin',
			array(
				'nonce'       => wp_create_nonce( 'bji_admin_nonce' ),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'strings'     => array(
					'removeRow'      => __( 'Remove', 'bulk-json-importer' ),
					'enterMetaKey'   => __( 'Enter meta key', 'bulk-json-importer' ),
					'doNotMap'       => __( '-- Do Not Map --', 'bulk-json-importer' ),
				),
			)
		);
	}

	/**
	 * Plugin activation hook.
	 */
	public static function activate() {
		// Create any necessary database tables or options.
		add_option( 'bji_version', BJI_VERSION );
		
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function deactivate() {
		// Clean up transients on deactivation.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bji_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bji_%'" );
		
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}