<?php
/**
 * Upload form template
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Bulk Post Importer from JSON - Step 1: Upload', 'bulk-json-importer' ); ?></h1>
	<p><?php esc_html_e( 'Upload a JSON file containing an array of objects. Each object will represent a post.', 'bulk-json-importer' ); ?></p>

	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'tools.php?page=' . BJI_PLUGIN_SLUG ) ); ?>">
		<?php wp_nonce_field( BJI_Admin::NONCE_ACTION, BJI_Admin::NONCE_NAME ); ?>
		<input type="hidden" name="step" value="1">

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="bji_json_file"><?php esc_html_e( 'JSON File', 'bulk-json-importer' ); ?></label>
				</th>
				<td>
					<input type="file" id="bji_json_file" name="bji_json_file" accept=".json,application/json" required />
					<p class="description"><?php esc_html_e( 'Must be a valid JSON file containing an array of objects.', 'bulk-json-importer' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="bji_post_type"><?php esc_html_e( 'Target Post Type', 'bulk-json-importer' ); ?></label>
				</th>
				<td>
					<select id="bji_post_type" name="bji_post_type" required>
						<?php foreach ( $post_types as $post_type ) : ?>
							<option value="<?php echo esc_attr( $post_type->name ); ?>">
								<?php echo esc_html( $post_type->labels->singular_name . ' (' . $post_type->name . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the post type you want to create.', 'bulk-json-importer' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Upload and Proceed to Mapping', 'bulk-json-importer' ), 'primary', 'bji_upload_json' ); ?>
	</form>
</div>