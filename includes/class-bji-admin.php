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


		// Handle form submissions.
		// Check for step 2 (process import) or presence of import button name
		if ( ( isset( $_POST['step'] ) && $_POST['step'] === '2' ) || 
			 isset( $_POST['bji_process_import'] ) ) {
			$this->handle_process_import();
			return;
		}

		// Check for step 1 (upload) or presence of upload button name
		if ( ( isset( $_POST['step'] ) && $_POST['step'] === '1' ) || 
			 ( isset( $_POST['bji_upload_json'] ) && isset( $_FILES['bji_json_file'] ) ) ) {
			$this->handle_upload_and_show_mapping();
			return;
		}
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
		// Security checks.
		if ( ! $this->verify_nonce() ) {
			BJI_Plugin::get_instance()->utils->add_admin_notice(
				__( 'Security check failed. Please try again.', 'bulk-json-importer' ),
				'error'
			);
			$this->render_upload_form();
			return;
		}

		$file_handler = BJI_Plugin::get_instance()->file_handler;
		$result = $file_handler->process_uploaded_file();

		if ( is_wp_error( $result ) ) {
			BJI_Plugin::get_instance()->utils->add_admin_notice(
				$result->get_error_message(),
				'error'
			);
			$this->render_upload_form();
			return;
		}

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
		return isset( $_POST[ self::NONCE_NAME ] ) && 
			   wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION );
	}
}