<?php
/**
 * Import processing functionality
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the import processing.
 */
class BJI_Import_Processor {

	/**
	 * Process the import.
	 *
	 * @return array|WP_Error Import results or error.
	 */
	public function process_import() {
		// Validate input data.
		$validation_result = $this->validate_import_data();
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		$transient_key = sanitize_text_field( wp_unslash( $_POST['bji_transient_key'] ) );
		$post_type = sanitize_key( $_POST['bji_post_type'] );
		$mapping = isset( $_POST['mapping'] ) && is_array( $_POST['mapping'] ) 
			? BJI_Plugin::get_instance()->utils->sanitize_mapping_array( $_POST['mapping'] ) 
			: array();

		// Retrieve data from transient.
		$transient_data = get_transient( $transient_key );
		if ( false === $transient_data || ! is_array( $transient_data ) || ! isset( $transient_data['data'], $transient_data['post_type'] ) ) {
			return new WP_Error( 'expired_data', __( 'Import data expired or was invalid. Please start over.', 'bulk-json-importer' ) );
		}

		// Verify post type matches.
		if ( $transient_data['post_type'] !== $post_type ) {
			delete_transient( $transient_key );
			return new WP_Error( 'post_type_mismatch', __( 'Post type mismatch between steps. Please start over.', 'bulk-json-importer' ) );
		}

		$items_to_import = $transient_data['data'];
		$original_file_name = $transient_data['file_name'] ?? 'unknown file';
		delete_transient( $transient_key );

		// Process the import.
		return $this->process_items( $items_to_import, $post_type, $mapping, $original_file_name );
	}

	/**
	 * Validate import data.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function validate_import_data() {
		$required_fields = array( 'bji_transient_key', 'bji_post_type', 'mapping' );
		
		foreach ( $required_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				return new WP_Error( 
					'missing_data', 
					__( 'Missing required data (transient key, post type, or mapping info). Please start over.', 'bulk-json-importer' )
				);
			}
		}

		return true;
	}

	/**
	 * Process items for import.
	 *
	 * @param array  $items_to_import   Array of items to import.
	 * @param string $post_type         Post type.
	 * @param array  $mapping           Field mapping.
	 * @param string $original_file_name Original file name.
	 * @return array Import results.
	 */
	private function process_items( $items_to_import, $post_type, $mapping, $original_file_name ) {
		$imported_count = 0;
		$skipped_count = 0;
		$error_messages = array();
		$start_time = microtime( true );

		$acf_handler = BJI_Plugin::get_instance()->acf_handler;
		$utils = BJI_Plugin::get_instance()->utils;

		// Performance optimizations.
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		set_time_limit( 0 );

		foreach ( $items_to_import as $index => $item ) {
			if ( ! is_array( $item ) ) {
				$skipped_count++;
				$error_messages[] = sprintf( 
					__( 'Item #%d: Skipped - Invalid data format (expected object/array).', 'bulk-json-importer' ), 
					$index + 1 
				);
				continue;
			}

			$result = $this->process_single_item( $item, $index, $post_type, $mapping, $acf_handler, $utils );
			
			if ( is_wp_error( $result ) ) {
				$skipped_count++;
				$error_messages[] = $result->get_error_message();
			} else {
				$imported_count++;
				if ( ! empty( $result['warnings'] ) ) {
					$error_messages = array_merge( $error_messages, $result['warnings'] );
				}
			}
		}

		// Re-enable counting.
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		$end_time = microtime( true );
		$duration = round( $end_time - $start_time, 2 );

		return array(
			'imported_count'     => $imported_count,
			'skipped_count'      => $skipped_count,
			'error_messages'     => $error_messages,
			'duration'           => $duration,
			'original_file_name' => $original_file_name,
			'total_items'        => count( $items_to_import ),
		);
	}

	/**
	 * Process a single item.
	 *
	 * @param array                $item        The item data.
	 * @param int                  $index       The item index.
	 * @param string               $post_type   The post type.
	 * @param array                $mapping     The field mapping.
	 * @param BJI_ACF_Handler      $acf_handler ACF handler instance.
	 * @param BJI_Utils            $utils       Utils instance.
	 * @return array|WP_Error Result array or error.
	 */
	private function process_single_item( $item, $index, $post_type, $mapping, $acf_handler, $utils ) {
		$warnings = array();
		
		// Prepare post data.
		$post_data = array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'meta_input'  => array(),
		);

		$acf_fields_to_update = array();

		// Map standard fields.
		$mapped_title = $this->map_standard_fields( $item, $mapping, $post_data, $utils, $index, $warnings );
		
		if ( ! $mapped_title ) {
			return new WP_Error( 
				'missing_title', 
				sprintf( __( 'Item #%d: Skipped - Missing required field mapping or value for: Title (post_title).', 'bulk-json-importer' ), $index + 1 )
			);
		}

		// Map custom fields.
		$this->map_custom_fields( $item, $mapping, $post_data );

		// Prepare ACF fields.
		$this->prepare_acf_fields( $item, $mapping, $acf_fields_to_update, $acf_handler );

		// Insert post.
		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 
				'post_insert_failed', 
				sprintf( 
					__( 'Item #%d: Failed to create post - %s', 'bulk-json-importer' ), 
					$index + 1, 
					$post_id->get_error_message() 
				)
			);
		}

		// Update ACF fields.
		$this->update_acf_fields( $acf_fields_to_update, $post_id, $acf_handler, $index, $warnings );

		return array( 'post_id' => $post_id, 'warnings' => $warnings );
	}

	/**
	 * Map standard WordPress fields.
	 *
	 * @param array     $item      The item data.
	 * @param array     $mapping   The field mapping.
	 * @param array     $post_data The post data array (by reference).
	 * @param BJI_Utils $utils     Utils instance.
	 * @param int       $index     Item index.
	 * @param array     $warnings  Warnings array (by reference).
	 * @return bool Whether title was mapped.
	 */
	private function map_standard_fields( $item, $mapping, &$post_data, $utils, $index, &$warnings ) {
		$mapped_title = false;
		
		if ( ! isset( $mapping['standard'] ) || ! is_array( $mapping['standard'] ) ) {
			return $mapped_title;
		}

		foreach ( $mapping['standard'] as $wp_key => $json_key ) {
			if ( empty( $json_key ) || ! isset( $item[ $json_key ] ) ) {
				continue;
			}

			$value = $item[ $json_key ];

			switch ( $wp_key ) {
				case 'post_title':
					$post_data['post_title'] = sanitize_text_field( $value );
					$mapped_title = true;
					break;

				case 'post_content':
					$post_data['post_content'] = $utils->convert_to_blocks( $value );
					break;

				case 'post_excerpt':
					$post_data['post_excerpt'] = wp_kses_post( $value );
					break;

				case 'post_status':
					$this->map_post_status( $value, $post_data, $index, $warnings );
					break;

				case 'post_date':
					$this->map_post_date( $value, $post_data, $index, $warnings );
					break;

				default:
					$post_data[ $wp_key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $mapped_title;
	}

	/**
	 * Map post status.
	 *
	 * @param string $value     The status value.
	 * @param array  $post_data The post data array (by reference).
	 * @param int    $index     Item index.
	 * @param array  $warnings  Warnings array (by reference).
	 */
	private function map_post_status( $value, &$post_data, $index, &$warnings ) {
		$allowed_statuses = get_post_stati();
		$sanitized_status = sanitize_key( $value );
		
		if ( array_key_exists( $sanitized_status, $allowed_statuses ) ) {
			$post_data['post_status'] = $sanitized_status;
		} else {
			$warnings[] = sprintf( 
				__( 'Item #%d: Notice - Invalid status "%s" provided for post_status, using default "publish".', 'bulk-json-importer' ), 
				$index + 1, 
				esc_html( $value ) 
			);
		}
	}

	/**
	 * Map post date.
	 *
	 * @param string $value     The date value.
	 * @param array  $post_data The post data array (by reference).
	 * @param int    $index     Item index.
	 * @param array  $warnings  Warnings array (by reference).
	 */
	private function map_post_date( $value, &$post_data, $index, &$warnings ) {
		$timestamp = strtotime( $value );
		
		if ( $timestamp ) {
			$post_data['post_date'] = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' );
			$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $timestamp );
		} else {
			$warnings[] = sprintf( 
				__( 'Item #%d: Notice - Could not parse date "%s" for post_date, using current time.', 'bulk-json-importer' ), 
				$index + 1, 
				esc_html( $value ) 
			);
		}
	}

	/**
	 * Map custom fields.
	 *
	 * @param array $item      The item data.
	 * @param array $mapping   The field mapping.
	 * @param array $post_data The post data array (by reference).
	 */
	private function map_custom_fields( $item, $mapping, &$post_data ) {
		if ( ! isset( $mapping['custom'] ) || ! is_array( $mapping['custom'] ) ) {
			return;
		}

		foreach ( $mapping['custom'] as $custom_map ) {
			if ( ! isset( $custom_map['json_key'], $custom_map['meta_key'] ) || 
				 ! is_string( $custom_map['json_key'] ) || 
				 ! is_string( $custom_map['meta_key'] ) || 
				 '' === $custom_map['json_key'] || 
				 '' === $custom_map['meta_key'] ) {
				continue;
			}

			$json_key = $custom_map['json_key'];
			$meta_key = $custom_map['meta_key'];

			if ( isset( $item[ $json_key ] ) ) {
				$post_data['meta_input'][ $meta_key ] = $item[ $json_key ];
			}
		}
	}

	/**
	 * Prepare ACF fields for update.
	 *
	 * @param array           $item                 The item data.
	 * @param array           $mapping              The field mapping.
	 * @param array           $acf_fields_to_update ACF fields array (by reference).
	 * @param BJI_ACF_Handler $acf_handler          ACF handler instance.
	 */
	private function prepare_acf_fields( $item, $mapping, &$acf_fields_to_update, $acf_handler ) {
		if ( ! $acf_handler->is_active() || ! isset( $mapping['acf'] ) || ! is_array( $mapping['acf'] ) ) {
			return;
		}

		foreach ( $mapping['acf'] as $acf_field_key => $json_key ) {
			if ( ! is_string( $json_key ) || '' === $json_key || ! isset( $item[ $json_key ] ) ) {
				continue;
			}

			$acf_fields_to_update[ $acf_field_key ] = $item[ $json_key ];
		}
	}

	/**
	 * Update ACF fields.
	 *
	 * @param array           $acf_fields_to_update ACF fields to update.
	 * @param int             $post_id              Post ID.
	 * @param BJI_ACF_Handler $acf_handler          ACF handler instance.
	 * @param int             $index                Item index.
	 * @param array           $warnings             Warnings array (by reference).
	 */
	private function update_acf_fields( $acf_fields_to_update, $post_id, $acf_handler, $index, &$warnings ) {
		if ( empty( $acf_fields_to_update ) ) {
			return;
		}

		foreach ( $acf_fields_to_update as $field_key => $value ) {
			$update_result = $acf_handler->update_field( $field_key, $value, $post_id );

			if ( false === $update_result ) {
				$acf_field_object = $acf_handler->get_field_object( $field_key, $post_id );
				$field_label = $acf_field_object ? $acf_field_object['label'] : $field_key;
				
				$warnings[] = sprintf( 
					__( 'Item #%d (Post ID %d): Notice - ACF update potentially failed for field "%s". Check data format in JSON.', 'bulk-json-importer' ), 
					$index + 1, 
					$post_id, 
					esc_html( $field_label ) 
				);
			}
		}
	}
}