<?php

/**
 * Plugin Name:       Bulk Post Importer from JSON (with ACF & Gutenberg Support)
 * Plugin URI:        https://example.com/bulk-post-importer
 * Description:       Allows bulk importing of posts from a JSON file with field mapping for standard, ACF, and custom fields. Converts content to basic Gutenberg paragraph blocks.
 * Version:           1.2.0
 * Author:            Your Name / Gemini AI
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bulk-json-importer
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

// --- Constants ---
define('BJI_PLUGIN_SLUG', 'bulk-json-importer');
define('BJI_NONCE_ACTION', 'bji_import_action');
define('BJI_NONCE_NAME', 'bji_nonce');
define('BJI_TRANSIENT_PREFIX', 'bji_import_data_');
define('BJI_REQUIRED_CAPABILITY', 'manage_options'); // Or use 'import' capability after adding it via filter

// --- Helper: Check if ACF is active ---
/**
 * Checks if Advanced Custom Fields plugin is active.
 *
 * @return bool True if ACF (Pro or Free) is active, false otherwise.
 */
function bji_is_acf_active()
{
	// Check for class existence which covers both free and pro versions and different load orders
	return class_exists('ACF');
	// Alternatively, check for a specific function: return function_exists('get_field');
}

/**
 * Retrieves ACF fields associated with a given post type.
 *
 * @param string $post_type The post type slug.
 * @return array An array of ACF field objects, keyed by field key. Returns empty array if ACF is not active or no fields found.
 */
function bji_get_acf_fields_for_post_type($post_type)
{
	if (! bji_is_acf_active()) {
		return [];
	}

	$acf_fields = [];
	// Ensure ACF functions are available before calling them
	if (function_exists('acf_get_field_groups') && function_exists('acf_get_fields')) {
		$field_groups = acf_get_field_groups(['post_type' => $post_type]);

		if (! empty($field_groups)) {
			foreach ($field_groups as $group) {
				// Check if group key exists, defensive coding
				if (! isset($group['key'])) continue;

				$fields_in_group = acf_get_fields($group['key']);
				if (! empty($fields_in_group)) {
					foreach ($fields_in_group as $field) {
						// Ensure field key exists
						if (! isset($field['key'])) continue;
						// Use field key for reliability
						$acf_fields[$field['key']] = $field;
					}
				}
			}
		}
	}

	// Optional: Filter out complex types if you don't want to map them yet
	// $supported_types = ['text', 'textarea', 'number', ...];
	// $acf_fields = array_filter($acf_fields, function($field) use ($supported_types) {
	//     return isset($field['type']) && in_array($field['type'], $supported_types);
	// });

	return $acf_fields;
}


/**
 * Adds the importer page to the Tools menu.
 */
function bji_add_admin_menu()
{
	add_management_page(
		__('Bulk JSON Importer', 'bulk-json-importer'),
		__('Bulk JSON Importer', 'bulk-json-importer'),
		BJI_REQUIRED_CAPABILITY,
		BJI_PLUGIN_SLUG,
		'bji_render_admin_page'
	);
}
add_action('admin_menu', 'bji_add_admin_menu');

/**
 * Renders the admin page content.
 * Handles the multi-step import process.
 */
function bji_render_admin_page()
{
	if (! current_user_can(BJI_REQUIRED_CAPABILITY)) {
		wp_die(__('You do not have sufficient permissions to access this page.', 'bulk-json-importer'));
	}

	// Check if we are processing an import (Step 3)
	if (isset($_POST['bji_process_import'])) {
		bji_handle_process_import();
		return; // Processing function handles output
	}

	// Check if we are showing the mapping screen (Step 2)
	if (isset($_POST['bji_upload_json']) && isset($_FILES['bji_json_file'])) {
		bji_handle_upload_and_show_mapping();
		return; // Mapping function handles output
	}

	// Otherwise, show the initial upload form (Step 1)
	bji_render_upload_form();
}

/**
 * Renders the initial file upload form (Step 1).
 */
function bji_render_upload_form()
{
?>
	<div class="wrap">
		<h1><?php esc_html_e('Bulk Post Importer from JSON - Step 1: Upload', 'bulk-json-importer'); ?></h1>
		<p><?php esc_html_e('Upload a JSON file containing an array of objects. Each object will represent a post.', 'bulk-json-importer'); ?></p>

		<?php bji_show_admin_notices(); // Display notices from previous actions 
		?>

		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('tools.php?page=' . BJI_PLUGIN_SLUG)); ?>">
			<?php wp_nonce_field(BJI_NONCE_ACTION, BJI_NONCE_NAME); ?>
			<input type="hidden" name="step" value="1">

			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="bji_json_file"><?php esc_html_e('JSON File', 'bulk-json-importer'); ?></label>
					</th>
					<td>
						<input type="file" id="bji_json_file" name="bji_json_file" accept=".json,application/json" required />
						<p class="description"><?php esc_html_e('Must be a valid JSON file containing an array of objects.', 'bulk-json-importer'); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="bji_post_type"><?php esc_html_e('Target Post Type', 'bulk-json-importer'); ?></label>
					</th>
					<td>
						<select id="bji_post_type" name="bji_post_type" required>
							<?php
							$post_types = get_post_types(array('public' => true), 'objects');
							// Exclude attachments or other unwanted types if necessary
							unset($post_types['attachment']);

							foreach ($post_types as $post_type) {
								printf(
									'<option value="%1$s">%2$s (%1$s)</option>',
									esc_attr($post_type->name),
									esc_html($post_type->labels->singular_name)
								);
							}
							?>
						</select>
						<p class="description"><?php esc_html_e('Select the post type you want to create.', 'bulk-json-importer'); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(__('Upload and Proceed to Mapping', 'bulk-json-importer'), 'primary', 'bji_upload_json'); ?>
		</form>
	</div>
<?php
}

/**
 * Handles the file upload, validation, and displays the mapping interface (Step 2).
 */
function bji_handle_upload_and_show_mapping()
{
	// 1. Security Checks
	if (! isset($_POST[BJI_NONCE_NAME]) || ! wp_verify_nonce(sanitize_key($_POST[BJI_NONCE_NAME]), BJI_NONCE_ACTION)) {
		bji_admin_notice(__('Security check failed. Please try again.', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}

	if (! current_user_can(BJI_REQUIRED_CAPABILITY)) {
		// Use wp_die for permission failures after nonce check
		wp_die(__('You do not have sufficient permissions to perform this action.', 'bulk-json-importer'), 403);
	}

	// 2. File Upload Validation
	if (! isset($_FILES['bji_json_file']) || empty($_FILES['bji_json_file']['tmp_name']) || $_FILES['bji_json_file']['error'] !== UPLOAD_ERR_OK) {
		$error_code = $_FILES['bji_json_file']['error'] ?? UPLOAD_ERR_NO_FILE;
		$error_message = bji_get_upload_error_message($error_code);
		bji_admin_notice(sprintf(__('File upload error: %s', 'bulk-json-importer'), $error_message), 'error');
		bji_render_upload_form();
		return;
	}

	$file_tmp_path = sanitize_text_field(wp_unslash($_FILES['bji_json_file']['tmp_name']));
	$file_type = mime_content_type($file_tmp_path); // More reliable MIME check
	$file_name = sanitize_file_name($_FILES['bji_json_file']['name']);

	// Basic MIME type check combined with extension check
	if ($file_type !== 'application/json' && ! str_ends_with(strtolower($file_name), '.json')) {
		bji_admin_notice(__('Invalid file type. Please upload a .json file (detected type: %s).', 'bulk-json-importer') . esc_html($file_type), 'error');
		bji_render_upload_form();
		return;
	}

	// 3. Read and Decode JSON
	$json_content = file_get_contents($file_tmp_path);
	if ($json_content === false) {
		bji_admin_notice(__('Could not read the uploaded file.', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}

	// Remove BOM if present (can interfere with json_decode)
	$json_content = preg_replace('/^\xEF\xBB\xBF/', '', $json_content);

	$data = json_decode($json_content, true); // Decode as associative array

	// Check for JSON decoding errors
	if (json_last_error() !== JSON_ERROR_NONE) {
		bji_admin_notice(sprintf(__('JSON Decode Error: %s. Please ensure the file is valid UTF-8 encoded JSON.', 'bulk-json-importer'), json_last_error_msg()), 'error');
		// Don't unlink temp file here, WordPress handles cleanup
		bji_render_upload_form();
		return;
	}

	// Check if it's an array of objects (or at least an array)
	if (! is_array($data)) {
		bji_admin_notice(__('JSON file structure error: Root element must be an array [...].', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}
	if (empty($data)) {
		bji_admin_notice(__('The JSON file appears to contain an empty array.', 'bulk-json-importer'), 'warning');
		bji_render_upload_form();
		return;
	}
	if (! is_array(reset($data))) {
		bji_admin_notice(__('JSON file structure error: The array should contain objects {...}.', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}


	// 4. Get Post Type and JSON Keys
	$selected_post_type = isset($_POST['bji_post_type']) ? sanitize_key($_POST['bji_post_type']) : 'post';
	if (! post_type_exists($selected_post_type)) {
		bji_admin_notice(__('Invalid post type selected.', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}

	// Get keys from the first item to suggest mapping
	$first_item = reset($data);
	$json_keys = array_keys($first_item);

	// 5. Store Data Temporarily for the next step
	$transient_key = BJI_TRANSIENT_PREFIX . get_current_user_id() . '_' . wp_create_nonce('bji_transient');
	$transient_data = [
		'data' => $data, // Store the whole data array
		'post_type' => $selected_post_type,
		'file_name' => $file_name // Store original filename for reference
	];
	// Store for 1 hour, should be enough time for mapping
	set_transient($transient_key, $transient_data, HOUR_IN_SECONDS);

	// 6. Render Mapping Form (Step 2) - Pass ACF fields if available
	bji_render_mapping_form($json_keys, $selected_post_type, $transient_key, count($data), $file_name);
}


/**
 * Renders the field mapping form (Step 2).
 *
 * @param array  $json_keys Array of keys found in the first JSON object.
 * @param string $post_type The selected target post type slug.
 * @param string $transient_key The key for the transient holding the data.
 * @param int    $item_count The total number of items found in the JSON.
 * @param string $file_name The original name of the uploaded file.
 */
function bji_render_mapping_form($json_keys, $post_type, $transient_key, $item_count, $file_name)
{
	$post_type_object = get_post_type_object($post_type);
	$post_type_label = $post_type_object ? $post_type_object->labels->singular_name : $post_type;

	// Standard WP fields
	$wp_fields = [
		'post_title'   => __('Title', 'bulk-json-importer') . ' (Required)',
		'post_content' => __('Content', 'bulk-json-importer') . ' (Converted to Paragraph Blocks)',
		'post_excerpt' => __('Excerpt', 'bulk-json-importer'),
		'post_status'  => __('Status (e.g., publish, draft)', 'bulk-json-importer'),
		'post_date'    => __('Date (YYYY-MM-DD HH:MM:SS)', 'bulk-json-importer'),
		// 'post_name'    => __( 'Slug', 'bulk-json-importer' ), // Optional: Add if slug mapping needed
		// 'post_author'  => __( 'Author ID', 'bulk-json-importer' ), // Optional: Add if author mapping needed
	];

	// Prepare JSON key options dropdown HTML
	$json_key_options = '<option value="">' . __('-- Do Not Map --', 'bulk-json-importer') . '</option>';
	foreach ($json_keys as $key) {
		$json_key_options .= sprintf(
			'<option value="%1$s">%1$s</option>',
			esc_attr($key)
		);
	}

	// Get ACF fields for this post type
	$acf_fields_for_post_type = bji_get_acf_fields_for_post_type($post_type);

?>
	<div class="wrap">
		<h1><?php esc_html_e('Bulk Post Importer - Step 2: Field Mapping', 'bulk-json-importer'); ?></h1>
		<p><?php printf(esc_html__('File: %s', 'bulk-json-importer'), '<strong>' . esc_html($file_name) . '</strong>'); ?></p>
		<p>
			<?php
			printf(
				esc_html__('Found %1$d items to import as "%2$s" posts. Please map the JSON keys (left) to the corresponding WordPress fields (right).', 'bulk-json-importer'),
				absint($item_count),
				esc_html($post_type_label)
			);
			?>
		</p>
		<p><?php esc_html_e('Unmapped fields will be ignored or set to default values (e.g., status defaults to "publish", date to current time).', 'bulk-json-importer'); ?></p>

		<form method="post" action="<?php echo esc_url(admin_url('tools.php?page=' . BJI_PLUGIN_SLUG)); ?>">
			<?php wp_nonce_field(BJI_NONCE_ACTION, BJI_NONCE_NAME); ?>
			<input type="hidden" name="bji_transient_key" value="<?php echo esc_attr($transient_key); ?>" />
			<input type="hidden" name="bji_post_type" value="<?php echo esc_attr($post_type); ?>" />
			<input type="hidden" name="item_count" value="<?php echo esc_attr($item_count); // Pass item count for potential future use 
															?>">
			<input type="hidden" name="step" value="2">

			<h2><?php esc_html_e('Standard Fields', 'bulk-json-importer'); ?></h2>
			<table class="form-table bji-mapping-table">
				<thead>
					<tr>
						<th><?php esc_html_e('JSON Key', 'bulk-json-importer'); ?></th>
						<th><?php esc_html_e('WordPress Field', 'bulk-json-importer'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($wp_fields as $wp_key => $wp_label) : ?>
						<tr valign="top">
							<th scope="row">
								<select name="mapping[standard][<?php echo esc_attr($wp_key); ?>]">
									<?php
									// Try to auto-select based on common names (case-insensitive check)
									$selected_attr = '';
									foreach ($json_keys as $json_key) {
										if (strcasecmp($wp_key, $json_key) === 0) {
											$selected_attr = 'selected="selected"';
											echo str_replace('value="' . esc_attr($json_key) . '"', 'value="' . esc_attr($json_key) . '" ' . $selected_attr, $json_key_options); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											break;
										}
									}
									if (empty($selected_attr)) {
										echo $json_key_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</select>
							</th>
							<td>
								<label><?php echo wp_kses_post($wp_label); // Allow potential formatting in label 
										?></label>
								<?php if ($wp_key === 'post_title'): ?>
									<p class="description"><?php esc_html_e('Mapping a title is highly recommended.', 'bulk-json-importer'); ?></p>
								<?php endif; ?>
								<?php if ($wp_key === 'post_content'): ?>
									<p class="description"><?php esc_html_e('Newline characters in source will create separate Paragraph blocks.', 'bulk-json-importer'); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<hr>

			<?php // ACF Fields Section 
			?>
			<?php if (bji_is_acf_active()) : ?>
				<h2><?php esc_html_e('Advanced Custom Fields (ACF)', 'bulk-json-importer'); ?></h2>
				<?php if (! empty($acf_fields_for_post_type)) : ?>
					<p><?php esc_html_e('Map JSON keys to available ACF fields for this post type.', 'bulk-json-importer'); ?></p>
					<p class="description"><?php esc_html_e('Note: Complex fields like Repeaters, Galleries, Relationships, Files, or Images require the JSON data to be in the specific format ACF expects (e.g., Attachment IDs for images/files, Post IDs for relationships, structured arrays for repeaters). Basic text/number/choice fields are handled directly.', 'bulk-json-importer'); ?></p>
					<table class="form-table bji-mapping-table">
						<thead>
							<tr>
								<th><?php esc_html_e('JSON Key', 'bulk-json-importer'); ?></th>
								<th><?php esc_html_e('ACF Field', 'bulk-json-importer'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($acf_fields_for_post_type as $field_key => $field) : ?>
								<?php
								// Defensive check for necessary field properties
								if (!isset($field['key'], $field['name'], $field['label'], $field['type'])) continue;
								?>
								<tr valign="top">
									<th scope="row">
										<select name="mapping[acf][<?php echo esc_attr($field_key); ?>]"> <?php // Use field KEY 
																											?>
											<?php
											// Try to auto-select if JSON key matches ACF field NAME (case-insensitive)
											$selected_attr = '';
											foreach ($json_keys as $json_key) {
												if (strcasecmp($field['name'], $json_key) === 0) {
													$selected_attr = 'selected="selected"';
													echo str_replace('value="' . esc_attr($json_key) . '"', 'value="' . esc_attr($json_key) . '" ' . $selected_attr, $json_key_options); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													break;
												}
											}
											if (empty($selected_attr)) {
												echo $json_key_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											}
											?>
										</select>
									</th>
									<td>
										<label title="Key: <?php echo esc_attr($field_key); ?> | Name: <?php echo esc_attr($field['name']); ?> | Type: <?php echo esc_attr($field['type']); ?>">
											<?php echo esc_html($field['label']); ?>
											(<code><?php echo esc_html($field['name']); ?></code> - <i><?php echo esc_html($field['type']); ?></i>)
										</label>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e('No ACF field groups were found assigned to this post type.', 'bulk-json-importer'); ?></p>
				<?php endif; ?>
				<hr>
			<?php endif; // End if ACF active 
			?>


			<h2><?php esc_html_e('Other Custom Fields (Non-ACF Post Meta)', 'bulk-json-importer'); ?></h2>
			<p><?php esc_html_e('Map JSON keys to standard WordPress custom field names (meta keys). Use this for meta fields NOT managed by ACF.', 'bulk-json-importer'); ?></p>
			<table class="form-table bji-mapping-table" id="bji-custom-fields-table">
				<thead>
					<tr>
						<th><?php esc_html_e('JSON Key', 'bulk-json-importer'); ?></th>
						<th><?php esc_html_e('Custom Field Name (Meta Key)', 'bulk-json-importer'); ?></th>
						<th><?php esc_html_e('Action', 'bulk-json-importer'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php // Add one row initially for user convenience 
					?>
					<tr valign="top" class="bji-custom-field-row">
						<td>
							<select name="mapping[custom][0][json_key]">
								<?php echo $json_key_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during generation 
								?>
							</select>
						</td>
						<td>
							<input type="text" name="mapping[custom][0][meta_key]" placeholder="<?php esc_attr_e('Enter meta key', 'bulk-json-importer'); ?>" />
						</td>
						<td>
							<button type="button" class="button bji-remove-row"><?php esc_html_e('Remove', 'bulk-json-importer'); ?></button>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3">
							<button type="button" id="bji-add-custom-field" class="button"><?php esc_html_e('Add Another Custom Field Mapping', 'bulk-json-importer'); ?></button>
						</td>
					</tr>
				</tfoot>
			</table>

			<?php submit_button(__('Process Import', 'bulk-json-importer'), 'primary', 'bji_process_import'); ?>
		</form>

		<?php // JS and CSS for Custom Fields table 
		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const customFieldsTable = document.getElementById('bji-custom-fields-table');
				if (customFieldsTable) {
					const tableBody = customFieldsTable.querySelector('tbody');
					const addRowButton = document.getElementById('bji-add-custom-field');
					let rowIndex = tableBody ? tableBody.rows.length : 0; // Start index for next row

					// Add Row Button Click
					if (addRowButton && tableBody) {
						addRowButton.addEventListener('click', function() {
							const newRow = tableBody.insertRow();
							newRow.classList.add('bji-custom-field-row');
							newRow.setAttribute('valign', 'top');

							const cell1 = newRow.insertCell();
							const cell2 = newRow.insertCell();
							const cell3 = newRow.insertCell();

							// IMPORTANT: Update the name attribute index correctly
							// Use template literals for cleaner HTML generation
							cell1.innerHTML = `<select name="mapping[custom][${rowIndex}][json_key]"><?php echo $json_key_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
																										?></select>`;
							cell2.innerHTML = `<input type="text" name="mapping[custom][${rowIndex}][meta_key]" placeholder="<?php esc_attr_e('Enter meta key', 'bulk-json-importer'); ?>" />`;
							cell3.innerHTML = `<button type="button" class="button bji-remove-row"><?php esc_html_e('Remove', 'bulk-json-importer'); ?></button>`;

							rowIndex++;
						});
					}

					// Remove Row (using event delegation on the table body)
					if (tableBody) {
						tableBody.addEventListener('click', function(e) {
							if (e.target && e.target.classList.contains('bji-remove-row')) {
								const row = e.target.closest('tr.bji-custom-field-row');
								if (row) {
									row.remove();
									// Note: Row indices are not updated, but PHP handles non-sequential keys fine on submit.
								}
							}
						});
					}
				}
			});
		</script>
		<style>
			.bji-mapping-table th,
			.bji-mapping-table td {
				padding: 8px;
				vertical-align: top;
			}

			.bji-mapping-table select,
			.bji-mapping-table input[type="text"] {
				min-width: 200px;
				width: 95%;
				max-width: 400px;
			}

			.bji-mapping-table label {
				display: block;
				font-weight: normal;
			}

			.bji-mapping-table label code,
			.bji-mapping-table label i {
				font-size: 0.9em;
				color: #555;
			}

			.bji-mapping-table .bji-remove-row {
				margin-left: 5px;
				vertical-align: middle;
			}

			.bji-mapping-table p.description {
				font-size: 0.85em;
				font-style: italic;
				margin-top: 4px;
			}

			hr {
				border: 0;
				border-top: 1px solid #ddd;
				margin: 20px 0;
			}
		</style>

	</div>
<?php
}


/**
 * Handles the actual post import processing (Step 3).
 * Includes Gutenberg block conversion and ACF field updates.
 */
function bji_handle_process_import()
{
	// 1. Security and Input Checks
	if (! isset($_POST[BJI_NONCE_NAME]) || ! wp_verify_nonce(sanitize_key($_POST[BJI_NONCE_NAME]), BJI_NONCE_ACTION)) {
		bji_admin_notice(__('Security check failed. Please start over.', 'bulk-json-importer'), 'error');
		bji_render_upload_form(); // Show step 1 again
		return;
	}

	if (! current_user_can(BJI_REQUIRED_CAPABILITY)) {
		wp_die(__('You do not have sufficient permissions to perform this action.', 'bulk-json-importer'), 403);
	}

	if (! isset($_POST['bji_transient_key'], $_POST['bji_post_type'], $_POST['mapping'])) {
		bji_admin_notice(__('Missing required data (transient key, post type, or mapping info). Please start over.', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}

	$transient_key = sanitize_text_field(wp_unslash($_POST['bji_transient_key']));
	$post_type     = sanitize_key($_POST['bji_post_type']);
	// Sanitize the mapping array recursively
	$mapping       = isset($_POST['mapping']) && is_array($_POST['mapping']) ? bji_sanitize_mapping_array($_POST['mapping']) : [];

	// 2. Retrieve Data from Transient
	$transient_data = get_transient($transient_key);
	if (false === $transient_data || ! is_array($transient_data) || ! isset($transient_data['data'], $transient_data['post_type'])) {
		bji_admin_notice(__('Import data expired or was invalid. Please start over.', 'bulk-json-importer'), 'error');
		bji_render_upload_form();
		return;
	}

	// Verify post type matches the one stored in transient
	if ($transient_data['post_type'] !== $post_type) {
		bji_admin_notice(__('Post type mismatch between steps. Please start over.', 'bulk-json-importer'), 'error');
		delete_transient($transient_key); // Clean up invalid transient
		bji_render_upload_form();
		return;
	}

	$items_to_import = $transient_data['data'];
	$original_file_name = $transient_data['file_name'] ?? 'unknown file';
	delete_transient($transient_key); // Crucial: Delete transient after retrieving data

	// 3. Process Each Item
	$imported_count = 0;
	$skipped_count = 0;
	$error_messages = [];
	$total_items = count($items_to_import);
	$start_time = microtime(true);

	$is_acf_active = bji_is_acf_active(); // Check once if ACF is active
	$can_update_acf = $is_acf_active && function_exists('update_field'); // Check specific function needed

	// Performance: Disable term/comment counting during bulk import
	wp_defer_term_counting(true);
	wp_defer_comment_counting(true);
	set_time_limit(0); // Try to prevent timeouts, may not work in safe mode

	foreach ($items_to_import as $index => $item) {
		if (! is_array($item)) {
			$skipped_count++;
			$error_messages[] = sprintf(__('Item #%d: Skipped - Invalid data format (expected object/array).', 'bulk-json-importer'), $index + 1);
			continue;
		}

		$post_data = [
			'post_type'   => $post_type,
			'post_status' => 'publish', // Default status
			'post_author' => get_current_user_id(), // Default author
			'meta_input'  => [], // Initialize meta_input array
			// 'tax_input' => [], // Initialize tax_input if taxonomy mapping is added later
		];
		$acf_fields_to_update = []; // For ACF fields updated after insert

		// --- Map Standard Fields ---
		$mapped_title = false;
		if (isset($mapping['standard']) && is_array($mapping['standard'])) {
			foreach ($mapping['standard'] as $wp_key => $json_key) {
				// Skip if no JSON key is mapped for this WP field
				if (empty($json_key)) continue;

				// Check if the mapped JSON key exists in the current item
				if (isset($item[$json_key])) {
					$value = $item[$json_key];

					// Assign and sanitize based on the WP field key
					switch ($wp_key) {
						case 'post_title':
							$post_data['post_title'] = sanitize_text_field($value);
							$mapped_title = true;
							break;

						case 'post_content':
							// Convert content to Gutenberg paragraph blocks
							$sanitized_content = wp_kses_post($value); // Sanitize before processing
							$lines = preg_split('/\R/', $sanitized_content); // Split by newline characters
							$block_content = '';
							if (! empty($lines)) {
								foreach ($lines as $line) {
									$trimmed_line = trim($line);
									if (! empty($trimmed_line)) {
										// Construct block syntax
										$block_content .= "\n";
										$block_content .= "<!-- wp:paragraph --><p>" . $trimmed_line . "</p><!-- /wp:paragraph -->\n";
										$block_content .= "\n\n";
									}
								}
							}
							$post_data['post_content'] = $block_content;
							break;

						case 'post_excerpt':
							$post_data['post_excerpt'] = wp_kses_post($value); // Excerpt allows some HTML
							break;

						case 'post_status':
							$allowed_statuses = get_post_stati();
							$sanitized_status = sanitize_key($value);
							if (array_key_exists($sanitized_status, $allowed_statuses)) {
								$post_data['post_status'] = $sanitized_status;
							} else {
								$error_messages[] = sprintf(__('Item #%d: Notice - Invalid status "%s" provided for post_status, using default "publish".', 'bulk-json-importer'), $index + 1, esc_html($value));
							}
							break;

						case 'post_date':
							// Attempt to parse date. Handles 'YYYY-MM-DD HH:MM:SS' and others strtotime understands.
							$timestamp = strtotime($value);
							if ($timestamp) {
								// Use wp_insert_post's handling by providing date in WP timezone format
								$post_data['post_date'] = get_date_from_gmt(date('Y-m-d H:i:s', $timestamp), 'Y-m-d H:i:s');
								$post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $timestamp);
							} else {
								$error_messages[] = sprintf(__('Item #%d: Notice - Could not parse date "%s" for post_date, using current time.', 'bulk-json-importer'), $index + 1, esc_html($value));
							}
							break;

						case 'post_name': // Optional Slug
							$post_data['post_name'] = sanitize_title($value);
							break;

						case 'post_author': // Optional Author ID
							$author_id = absint($value);
							if (get_user_by('ID', $author_id)) {
								$post_data['post_author'] = $author_id;
							} else {
								$error_messages[] = sprintf(__('Item #%d: Notice - Invalid user ID "%s" provided for post_author, using current user.', 'bulk-json-importer'), $index + 1, esc_html($value));
							}
							break;

						default:
							// Handle any other standard fields if added later (sanitize as text)
							$post_data[$wp_key] = sanitize_text_field($value);
							break;
					}
				}
			}
		}

		// A title is practically required by WordPress
		if (empty($post_data['post_title'])) {
			$skipped_count++;
			$error_messages[] = sprintf(__('Item #%d: Skipped - Missing required field mapping or value for: Title (post_title).', 'bulk-json-importer'), $index + 1);
			continue; // Skip this item entirely
		}

		// --- Prepare Non-ACF Custom Fields (for meta_input) ---
		if (isset($mapping['custom']) && is_array($mapping['custom'])) {
			foreach ($mapping['custom'] as $custom_map) {
				// Ensure both keys exist and are non-empty strings from sanitized data
				if (isset($custom_map['json_key'], $custom_map['meta_key']) && is_string($custom_map['json_key']) && is_string($custom_map['meta_key']) && $custom_map['json_key'] !== '' && $custom_map['meta_key'] !== '') {
					$json_key = $custom_map['json_key'];
					$meta_key = $custom_map['meta_key']; // Key already sanitized

					// Check if the source JSON key exists for this item
					if (isset($item[$json_key])) {
						// Add to meta_input. Value sanitization happens via update_post_meta hooks later.
						// `meta_input` handles arrays/objects (serialized) automatically.
						$post_data['meta_input'][$meta_key] = $item[$json_key];
					}
				}
			}
		}
		// No need to check if meta_input is empty, wp_insert_post handles it


		// --- Prepare ACF Fields (for update_field after insert) ---
		if ($can_update_acf && isset($mapping['acf']) && is_array($mapping['acf'])) {
			foreach ($mapping['acf'] as $acf_field_key => $json_key) { // $acf_field_key is the 'field_xxxxx...' identifier
				// Check if JSON key is mapped and exists in item
				if (is_string($json_key) && $json_key !== '' && isset($item[$json_key])) {
					// Store field key and raw value. `update_field` will handle type conversion/sanitization.
					// Add complex data transformations here if needed before passing to update_field
					$acf_fields_to_update[$acf_field_key] = $item[$json_key];
				}
			}
		}

		// --- Insert Post ---
		// This handles saving standard fields and non-ACF meta via 'meta_input'
		$post_id = wp_insert_post($post_data, true); // Set second param to true to return WP_Error on failure

		// Check for errors during post insertion
		if (is_wp_error($post_id)) {
			$skipped_count++;
			$error_messages[] = sprintf(
				__('Item #%d: Failed to create post - %s', 'bulk-json-importer'),
				$index + 1,
				$post_id->get_error_message() // Get the specific error message
			);
			continue; // Skip ACF update if post creation failed
		}

		// --- Update ACF Fields (if any were prepared) ---
		if ($can_update_acf && ! empty($acf_fields_to_update)) {
			foreach ($acf_fields_to_update as $field_key => $value) {
				// Use update_field() - it handles data based on field type settings.
				// $field_key is the 'field_xxxxx...' identifier.
				$update_result = update_field($field_key, $value, $post_id);

				// Optional: Log if ACF update fails silently (update_field doesn't throw errors well)
				if ($update_result === false) {
					$acf_field_object = get_field_object($field_key, $post_id, false); // Get field details for logging
					$field_label = $acf_field_object ? $acf_field_object['label'] : $field_key;
					$error_messages[] = sprintf(__('Item #%d (Post ID %d): Notice - ACF update potentially failed for field "%s". Check data format in JSON.', 'bulk-json-importer'), $index + 1, $post_id, esc_html($field_label));
				}
			}
		}

		// --- Success ---
		$imported_count++;
		// Optional: Action hook after successful import of one item
		// do_action('bji_after_post_imported', $post_id, $item, $post_data);

		// Optional: Clear caches periodically for very long imports to reduce memory footprint
		// if ($index % 100 === 0) {
		//    wp_cache_flush();
		// }

	} // End foreach loop processing items

	// Re-enable term and comment counting
	wp_defer_term_counting(false);
	wp_defer_comment_counting(false);

	$end_time = microtime(true);
	$duration = round($end_time - $start_time, 2);

	// 4. Display Final Results Page
?>
	<div class="wrap">
		<h1><?php esc_html_e('Bulk Post Importer - Import Results', 'bulk-json-importer'); ?></h1>

		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					esc_html__('Import process completed for file %1$s in %2$s seconds. %3$d posts imported successfully.', 'bulk-json-importer'),
					'<strong>' . esc_html($original_file_name) . '</strong>',
					esc_html($duration),
					absint($imported_count)
				);
				?>
			</p>
		</div>

		<?php if ($skipped_count > 0) : ?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						esc_html(_n('%d item was skipped.', '%d items were skipped.', $skipped_count, 'bulk-json-importer')),
						absint($skipped_count)
					);
					?>
					<?php if (!empty($error_messages)) : ?>
						<br><?php esc_html_e('See details below.', 'bulk-json-importer'); ?>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<?php // Display detailed error/notice log 
		?>
		<?php if (! empty($error_messages)) : ?>
			<h2><?php esc_html_e('Import Log (Errors & Notices)', 'bulk-json-importer'); ?></h2>
			<div style="border: 1px solid #ccd0d4; background: #fff; padding: 10px 15px; max-height: 400px; overflow-y: auto;">
				<ul style="list-style: disc; margin-left: 20px;">
					<?php foreach ($error_messages as $message) : ?>
						<li><?php echo wp_kses_post($message); // Use wp_kses_post for safety but allow some HTML like code/strong 
							?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<p style="margin-top: 20px;">
			<a href="<?php echo esc_url(admin_url('tools.php?page=' . BJI_PLUGIN_SLUG)); ?>" class="button button-primary">
				<?php esc_html_e('Import Another File', 'bulk-json-importer'); ?>
			</a>
		</p>

	</div>
<?php
}


// --- Helper Functions ---

/**
 * Get a user-friendly message for a PHP file upload error code.
 *
 * @param int $error_code The error code from $_FILES['key']['error'].
 * @return string The error message.
 */
function bji_get_upload_error_message($error_code)
{
	switch ($error_code) {
		case UPLOAD_ERR_OK: // Should not happen here, but include for completeness
			return __('No error, file uploaded successfully.', 'bulk-json-importer'); // Should not be called with OK
		case UPLOAD_ERR_INI_SIZE:
			return __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'bulk-json-importer');
		case UPLOAD_ERR_FORM_SIZE:
			return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'bulk-json-importer');
		case UPLOAD_ERR_PARTIAL:
			return __('The uploaded file was only partially uploaded.', 'bulk-json-importer');
		case UPLOAD_ERR_NO_FILE:
			return __('No file was uploaded.', 'bulk-json-importer');
		case UPLOAD_ERR_NO_TMP_DIR:
			return __('Missing a temporary folder on the server.', 'bulk-json-importer');
		case UPLOAD_ERR_CANT_WRITE:
			return __('Failed to write file to disk on the server.', 'bulk-json-importer');
		case UPLOAD_ERR_EXTENSION:
			return __('A PHP extension stopped the file upload.', 'bulk-json-importer');
		default:
			return __('Unknown upload error occurred.', 'bulk-json-importer');
	}
}

/**
 * Stores an admin notice message in a transient to be displayed on the next admin load for the user.
 *
 * @param string $message The message text.
 * @param string $type    The notice type ('success', 'warning', 'error', 'info'). Default 'info'.
 */
function bji_admin_notice($message, $type = 'info')
{
	$user_id = get_current_user_id();
	if (!$user_id) return; // Cannot store notice if no user context

	$transient_key = 'bji_admin_notices_' . $user_id;
	$notices = get_transient($transient_key);
	if (! is_array($notices)) {
		$notices = [];
	}
	$notices[] = [
		'message' => $message,
		'type'    => $type,
	];
	// Store for 5 minutes - should be plenty for a page reload
	set_transient($transient_key, $notices, 5 * MINUTE_IN_SECONDS);
}

/**
 * Displays stored admin notices for the current user and clears them. Hooked to 'admin_notices'.
 */
function bji_show_admin_notices_action()
{
	$user_id = get_current_user_id();
	if (!$user_id) return;

	// Only show on our plugin page to avoid cluttering other admin pages
	$screen = get_current_screen();
	if (!$screen || $screen->id !== 'tools_page_' . BJI_PLUGIN_SLUG) {
		return;
	}

	bji_show_admin_notices(); // Call the display logic
}
add_action('admin_notices', 'bji_show_admin_notices_action');


/**
 * Displays stored admin notices and clears them. (Called by action or directly in page render)
 */
function bji_show_admin_notices()
{
	$user_id = get_current_user_id();
	if (!$user_id) return;

	$transient_key = 'bji_admin_notices_' . $user_id;
	$notices = get_transient($transient_key);

	if (! empty($notices) && is_array($notices)) {
		foreach ($notices as $notice) {
			if (is_array($notice) && isset($notice['message'], $notice['type'])) {
				// Ensure type is one of the allowed values
				$allowed_types = ['error', 'warning', 'success', 'info'];
				$type = in_array($notice['type'], $allowed_types) ? $notice['type'] : 'info';

				printf(
					'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr($type),
					wp_kses_post($notice['message']) // Allow basic HTML in notices
				);
			}
		}
		delete_transient($transient_key); // Clear notices after displaying
	}
}

/**
 * Recursively sanitizes the mapping array from POST data.
 * Keys are sanitized using sanitize_key (or absint for numeric).
 * Values are sanitized using sanitize_text_field.
 * Special handling for 'meta_key' to allow more characters.
 *
 * @param array $array The input array (e.g., $_POST['mapping']). Must be an array.
 * @return array The sanitized array.
 */
function bji_sanitize_mapping_array($array)
{
	$sanitized_array = [];
	if (! is_array($array)) {
		// If input is not an array, return an empty array to prevent errors
		return $sanitized_array;
	}

	foreach ($array as $key => $value) {
		// Sanitize the key first
		$sanitized_key = is_numeric($key) ? absint($key) : sanitize_key($key);

		if (is_array($value)) {
			// If the value is an array, recurse
			$sanitized_array[$sanitized_key] = bji_sanitize_mapping_array($value);
		} else {
			// Sanitize the value based on the key it's associated with (or generically)
			$sanitized_value = '';
			if ($sanitized_key === 'meta_key') {
				// Allow underscores, hyphens, letters, numbers for meta keys. Remove others.
				$sanitized_value = sanitize_text_field(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $value));
			} elseif (is_string($value)) {
				// Sanitize other string values as text fields
				$sanitized_value = sanitize_text_field($value);
			} elseif (is_numeric($value)) {
				// Keep numeric values as they are (PHP handles types)
				$sanitized_value = $value;
			} // Add more specific sanitization if needed for certain keys

			// Assign the sanitized value to the sanitized key
			$sanitized_array[$sanitized_key] = $sanitized_value;
		}
	}
	return $sanitized_array;
}

// --- Optional Activation/Deactivation Hooks ---

// function bji_activate() { /* Setup tasks */ }
// register_activation_hook( __FILE__, 'bji_activate' );

// function bji_deactivate() { /* Cleanup tasks */ }
// register_deactivation_hook( __FILE__, 'bji_deactivate' );

?>