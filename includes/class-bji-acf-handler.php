<?php
/**
 * ACF field handling functionality
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Advanced Custom Fields integration.
 */
class BJI_ACF_Handler {

	/**
	 * Check if ACF is active.
	 *
	 * @return bool True if ACF is active.
	 */
	public function is_active() {
		return class_exists( 'ACF' );
	}

	/**
	 * Get ACF fields for a post type.
	 *
	 * @param string $post_type The post type slug.
	 * @return array Array of ACF field objects.
	 */
	public function get_fields_for_post_type( $post_type ) {
		if ( ! $this->is_active() ) {
			return array();
		}

		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		$acf_fields = array();
		$field_groups = acf_get_field_groups( array( 'post_type' => $post_type ) );

		if ( empty( $field_groups ) ) {
			return array();
		}

		foreach ( $field_groups as $group ) {
			if ( ! isset( $group['key'] ) ) {
				continue;
			}

			$fields_in_group = acf_get_fields( $group['key'] );
			
			if ( empty( $fields_in_group ) ) {
				continue;
			}

			foreach ( $fields_in_group as $field ) {
				if ( ! isset( $field['key'] ) ) {
					continue;
				}
				
				$acf_fields[ $field['key'] ] = $field;
			}
		}

		return $acf_fields;
	}

	/**
	 * Update ACF field value for a post.
	 *
	 * @param string $field_key The ACF field key.
	 * @param mixed  $value     The field value.
	 * @param int    $post_id   The post ID.
	 * @return bool True on success, false on failure.
	 */
	public function update_field( $field_key, $value, $post_id ) {
		if ( ! $this->is_active() || ! function_exists( 'update_field' ) ) {
			return false;
		}

		return update_field( $field_key, $value, $post_id );
	}

	/**
	 * Get field object by key.
	 *
	 * @param string $field_key The field key.
	 * @param int    $post_id   The post ID.
	 * @return array|false Field object or false on failure.
	 */
	public function get_field_object( $field_key, $post_id ) {
		if ( ! $this->is_active() || ! function_exists( 'get_field_object' ) ) {
			return false;
		}

		return get_field_object( $field_key, $post_id, false );
	}
}