<?php
/**
 * File handling functionality
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles file upload and processing.
 */
class BJI_File_Handler {

	/**
	 * Process uploaded JSON file.
	 *
	 * @return array|WP_Error Processed file data or error.
	 */
	public function process_uploaded_file() {
		// Validate file upload.
		$validation_result = $this->validate_file_upload();
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Process the file.
		$file_path = sanitize_text_field( wp_unslash( $_FILES['bji_json_file']['tmp_name'] ) );
		$file_name = sanitize_file_name( $_FILES['bji_json_file']['name'] );
		$post_type = isset( $_POST['bji_post_type'] ) ? sanitize_key( $_POST['bji_post_type'] ) : 'post';

		// Validate post type.
		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error( 'invalid_post_type', __( 'Invalid post type selected.', 'bulk-json-importer' ) );
		}

		// Read and decode JSON.
		$data = $this->read_and_decode_json( $file_path );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Get keys from first item.
		$first_item = reset( $data );
		$json_keys = array_keys( $first_item );

		// Store data in transient.
		$transient_key = BJI_Plugin::get_instance()->utils->generate_transient_key();
		$transient_data = array(
			'data'      => $data,
			'post_type' => $post_type,
			'file_name' => $file_name,
		);
		
		set_transient( $transient_key, $transient_data, HOUR_IN_SECONDS );

		return array(
			'json_keys'     => $json_keys,
			'post_type'     => $post_type,
			'transient_key' => $transient_key,
			'item_count'    => count( $data ),
			'file_name'     => $file_name,
		);
	}

	/**
	 * Validate file upload.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function validate_file_upload() {
		if ( ! isset( $_FILES['bji_json_file'] ) || empty( $_FILES['bji_json_file']['tmp_name'] ) ) {
			return new WP_Error( 'no_file', __( 'No file was uploaded.', 'bulk-json-importer' ) );
		}

		if ( UPLOAD_ERR_OK !== $_FILES['bji_json_file']['error'] ) {
			$error_code = $_FILES['bji_json_file']['error'];
			$error_message = BJI_Plugin::get_instance()->utils->get_upload_error_message( $error_code );
			return new WP_Error( 'upload_error', sprintf( __( 'File upload error: %s', 'bulk-json-importer' ), $error_message ) );
		}

		$file_tmp_path = sanitize_text_field( wp_unslash( $_FILES['bji_json_file']['tmp_name'] ) );
		$file_name = sanitize_file_name( $_FILES['bji_json_file']['name'] );

		// Check file type.
		$file_type = mime_content_type( $file_tmp_path );
		if ( 'application/json' !== $file_type && ! str_ends_with( strtolower( $file_name ), '.json' ) ) {
			return new WP_Error( 
				'invalid_file_type', 
				sprintf( __( 'Invalid file type. Please upload a .json file (detected type: %s).', 'bulk-json-importer' ), esc_html( $file_type ) )
			);
		}

		return true;
	}

	/**
	 * Read and decode JSON file.
	 *
	 * @param string $file_path The file path.
	 * @return array|WP_Error Decoded data or error.
	 */
	private function read_and_decode_json( $file_path ) {
		$json_content = file_get_contents( $file_path );
		if ( false === $json_content ) {
			return new WP_Error( 'file_read_error', __( 'Could not read the uploaded file.', 'bulk-json-importer' ) );
		}

		// Remove BOM if present.
		$json_content = preg_replace( '/^\xEF\xBB\xBF/', '', $json_content );

		$data = json_decode( $json_content, true );

		// Check for JSON decoding errors.
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 
				'json_decode_error', 
				sprintf( __( 'JSON Decode Error: %s. Please ensure the file is valid UTF-8 encoded JSON.', 'bulk-json-importer' ), json_last_error_msg() )
			);
		}

		// Validate JSON structure.
		$validation_result = $this->validate_json_structure( $data );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		return $data;
	}

	/**
	 * Validate JSON structure.
	 *
	 * @param mixed $data The decoded JSON data.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function validate_json_structure( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_structure', __( 'JSON file structure error: Root element must be an array [...].', 'bulk-json-importer' ) );
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'empty_array', __( 'The JSON file appears to contain an empty array.', 'bulk-json-importer' ) );
		}

		if ( ! is_array( reset( $data ) ) ) {
			return new WP_Error( 'invalid_items', __( 'JSON file structure error: The array should contain objects {...}.', 'bulk-json-importer' ) );
		}

		return true;
	}
}