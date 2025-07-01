<?php
/**
 * Utility functions for Bulk JSON Importer
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility class containing helper functions.
 */
class BJI_Utils {

	/**
	 * Transient prefix for storing import data.
	 */
	const TRANSIENT_PREFIX = 'bji_import_data_';

	/**
	 * Add an admin notice.
	 *
	 * @param string $message The message text.
	 * @param string $type    The notice type ('success', 'warning', 'error', 'info').
	 */
	public function add_admin_notice( $message, $type = 'info' ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$transient_key = 'bji_admin_notices_' . $user_id;
		$notices = get_transient( $transient_key );
		
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		
		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
		
		set_transient( $transient_key, $notices, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Show admin notices.
	 */
	public function show_admin_notices() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$transient_key = 'bji_admin_notices_' . $user_id;
		$notices = get_transient( $transient_key );

		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			if ( ! is_array( $notice ) || ! isset( $notice['message'], $notice['type'] ) ) {
				continue;
			}

			$allowed_types = array( 'error', 'warning', 'success', 'info' );
			$type = in_array( $notice['type'], $allowed_types ) ? $notice['type'] : 'info';

			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				wp_kses_post( $notice['message'] )
			);
		}
		
		delete_transient( $transient_key );
	}

	/**
	 * Sanitize mapping array recursively.
	 *
	 * @param array $array The input array.
	 * @return array The sanitized array.
	 */
	public function sanitize_mapping_array( $array ) {
		if ( ! is_array( $array ) ) {
			return array();
		}

		$sanitized = array();
		
		foreach ( $array as $key => $value ) {
			$sanitized_key = is_numeric( $key ) ? absint( $key ) : sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $sanitized_key ] = $this->sanitize_mapping_array( $value );
			} else {
				$sanitized[ $sanitized_key ] = $this->sanitize_mapping_value( $sanitized_key, $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a mapping value based on its key.
	 *
	 * @param string $key   The key name.
	 * @param mixed  $value The value to sanitize.
	 * @return mixed The sanitized value.
	 */
	private function sanitize_mapping_value( $key, $value ) {
		if ( 'meta_key' === $key ) {
			return sanitize_text_field( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $value ) );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		if ( is_numeric( $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Get upload error message.
	 *
	 * @param int $error_code The error code.
	 * @return string The error message.
	 */
	public function get_upload_error_message( $error_code ) {
		$messages = array(
			UPLOAD_ERR_OK         => __( 'No error, file uploaded successfully.', 'bulk-json-importer' ),
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'bulk-json-importer' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'bulk-json-importer' ),
			UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.', 'bulk-json-importer' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'bulk-json-importer' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder on the server.', 'bulk-json-importer' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk on the server.', 'bulk-json-importer' ),
			UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', 'bulk-json-importer' ),
		);

		return isset( $messages[ $error_code ] ) 
			? $messages[ $error_code ] 
			: __( 'Unknown upload error occurred.', 'bulk-json-importer' );
	}

	/**
	 * Generate a transient key for the current user.
	 *
	 * @return string The transient key.
	 */
	public function generate_transient_key() {
		return self::TRANSIENT_PREFIX . get_current_user_id() . '_' . wp_create_nonce( 'bji_transient' );
	}

	/**
	 * Get standard WordPress fields for mapping.
	 *
	 * @return array Array of field keys and labels.
	 */
	public function get_standard_fields() {
		return array(
			'post_title'   => __( 'Title', 'bulk-json-importer' ) . ' (' . __( 'Required', 'bulk-json-importer' ) . ')',
			'post_content' => __( 'Content', 'bulk-json-importer' ) . ' (' . __( 'Converted to Paragraph Blocks', 'bulk-json-importer' ) . ')',
			'post_excerpt' => __( 'Excerpt', 'bulk-json-importer' ),
			'post_status'  => __( 'Status (e.g., publish, draft)', 'bulk-json-importer' ),
			'post_date'    => __( 'Date (YYYY-MM-DD HH:MM:SS)', 'bulk-json-importer' ),
		);
	}

	/**
	 * Convert content to Gutenberg blocks.
	 *
	 * @param string $content The content to convert.
	 * @return string The block content.
	 */
	public function convert_to_blocks( $content ) {
		$sanitized_content = wp_kses_post( $content );
		$lines = preg_split( '/\R/', $sanitized_content );
		$block_content = '';

		if ( ! empty( $lines ) ) {
			foreach ( $lines as $line ) {
				$trimmed_line = trim( $line );
				if ( ! empty( $trimmed_line ) ) {
					$block_content .= "\n<!-- wp:paragraph --><p>" . $trimmed_line . "</p><!-- /wp:paragraph -->\n\n";
				}
			}
		}

		return $block_content;
	}
}