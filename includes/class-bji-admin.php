<?php
/**
 * Admin functionality
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class for handling the admin interface.
 */
class BJI_Admin {

	/**
	 * Required capability for using the importer.
	 */
	const REQUIRED_CAPABILITY = 'manage_options';

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'bji_import_action';

	/**
	 * Nonce name.
	 */
	const NONCE_NAME = 'bji_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'Bulk JSON Importer', 'bulk-json-importer' ),
			__( 'Bulk JSON Importer', 'bulk-json-importer' ),
			self::REQUIRED_CAPABILITY,
			BJI_PLUGIN_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'bulk-json-importer' ) );
		}

		// Debug: Check what's being posted
		if ( ! empty( $_POST ) ) {
			error_log( 'BJI Debug - POST data: ' . print_r( $_POST, true ) );
			error_log( 'BJI Debug - FILES data: ' . print_r( $_FILES, true ) );
		}

		// Debug: Check if classes exist
		error_log( 'BJI Debug - BJI_Plugin exists: ' . ( class_exists( 'BJI_Plugin' ) ? 'yes' : 'no' ) );
		error_log( 'BJI Debug - BJI_Utils exists: ' . ( class_exists( 'BJI_Utils' ) ? 'yes' : 'no' ) );
		error_log( 'BJI Debug - BJI_File_Handler exists: ' . ( class_exists( 'BJI_File_Handler' ) ? 'yes' : 'no' ) );

		// Handle form submissions.
		// Check for step 2 (process import) or presence of import button name
		if ( ( isset( $_POST['step'] ) && $_POST['step'] === '2' ) || 
			 isset( $_POST['bji_process_import'] ) ) {
			error_log( 'BJI Debug - Processing import' );
			$this->handle_process_import();
			return;
		}

		// Check for step 1 (upload) or presence of upload button name
		if ( ( isset( $_POST['step'] ) && $_POST['step'] === '1' ) || 
			 ( isset( $_POST['bji_upload_json'] ) && isset( $_FILES['bji_json_file'] ) ) ) {
			error_log( 'BJI Debug - Upload detected, processing...' );
			$this->handle_upload_and_show_mapping();
			return;
		}

		error_log( 'BJI Debug - Showing upload form' );
		// Show upload form.
		$this->render_upload_form();
	}

	/**
	 * Render the upload form.
	 */
	private function render_upload_form() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );

		include BJI_PLUGIN_DIR . 'includes/admin/upload-form.php';
	}

	/**
	 * Handle file upload and show mapping interface.
	 */
	private function handle_upload_and_show_mapping() {
		error_log( 'BJI Debug - handle_upload_and_show_mapping called' );
		
		// Security checks.
		if ( ! $this->verify_nonce() ) {
			error_log( 'BJI Debug - Nonce verification failed' );
			BJI_Plugin::get_instance()->utils->add_admin_notice(
				__( 'Security check failed. Please try again.', 'bulk-json-importer' ),
				'error'
			);
			$this->render_upload_form();
			return;
		}

		error_log( 'BJI Debug - Nonce verification passed' );

		$file_handler = BJI_Plugin::get_instance()->file_handler;
		$result = $file_handler->process_uploaded_file();

		if ( is_wp_error( $result ) ) {
			error_log( 'BJI Debug - File processing error: ' . $result->get_error_message() );
			BJI_Plugin::get_instance()->utils->add_admin_notice(
				$result->get_error_message(),
				'error'
			);
			$this->render_upload_form();
			return;
		}

		error_log( 'BJI Debug - File processing successful, rendering mapping form' );
		$this->render_mapping_form( $result );
	}

	/**
	 * Render the mapping form.
	 *
	 * @param array $data The processed file data.
	 */
	private function render_mapping_form( $data ) {
		$json_keys     = $data['json_keys'];
		$post_type     = $data['post_type'];
		$transient_key = $data['transient_key'];
		$item_count    = $data['item_count'];
		$file_name     = $data['file_name'];

		$post_type_object = get_post_type_object( $post_type );
		$post_type_label  = $post_type_object ? $post_type_object->labels->singular_name : $post_type;

		$acf_fields = BJI_Plugin::get_instance()->acf_handler->get_fields_for_post_type( $post_type );

		include BJI_PLUGIN_DIR . 'includes/admin/mapping-form.php';
	}

	/**
	 * Handle the import process.
	 */
	private function handle_process_import() {
		// Security checks.
		if ( ! $this->verify_nonce() ) {
			BJI_Plugin::get_instance()->utils->add_admin_notice(
				__( 'Security check failed. Please start over.', 'bulk-json-importer' ),
				'error'
			);
			$this->render_upload_form();
			return;
		}

		$processor = BJI_Plugin::get_instance()->import_processor;
		$result = $processor->process_import();

		if ( is_wp_error( $result ) ) {
			BJI_Plugin::get_instance()->utils->add_admin_notice(
				$result->get_error_message(),
				'error'
			);
			$this->render_upload_form();
			return;
		}

		$this->render_results_page( $result );
	}

	/**
	 * Render the results page.
	 *
	 * @param array $result The import results.
	 */
	private function render_results_page( $result ) {
		include BJI_PLUGIN_DIR . 'includes/admin/results-page.php';
	}

	/**
	 * Show admin notices.
	 */
	public function show_admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'tools_page_' . BJI_PLUGIN_SLUG !== $screen->id ) {
			return;
		}

		BJI_Plugin::get_instance()->utils->show_admin_notices();
	}

	/**
	 * Verify nonce.
	 *
	 * @return bool
	 */
	private function verify_nonce() {
		$nonce_exists = isset( $_POST[ self::NONCE_NAME ] );
		$nonce_valid = $nonce_exists && wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION );
		
		error_log( 'BJI Debug - Nonce exists: ' . ( $nonce_exists ? 'yes' : 'no' ) );
		error_log( 'BJI Debug - Nonce valid: ' . ( $nonce_valid ? 'yes' : 'no' ) );
		error_log( 'BJI Debug - Expected nonce name: ' . self::NONCE_NAME );
		error_log( 'BJI Debug - Expected nonce action: ' . self::NONCE_ACTION );
		
		return $nonce_valid;
	}
}