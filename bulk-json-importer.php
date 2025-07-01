<?php

/**
 * Plugin Name:       Bulk JSON Importer
 * Plugin URI:        https://github.com/cwahlfeldt/bulk-json-importer
 * Description:       Allows bulk importing of posts and custom post types from a JSON file with field mapping for standard, ACF, and custom fields. Converts text content to basic Gutenberg paragraph blocks.
 * Version:           0.1.1
 * Author:            Chris Wahlfeldt
 * Author URI:        https://cwahlfeldt.github.io/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bulk-json-importer
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

// Define plugin constants.
define('BJI_VERSION', '1.3.0');
define('BJI_PLUGIN_FILE', __FILE__);
define('BJI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BJI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BJI_PLUGIN_SLUG', 'bulk-json-importer');

// Autoload classes.
spl_autoload_register('bji_autoload');

/**
 * Autoload plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function bji_autoload($class_name)
{
	if (strpos($class_name, 'BJI_') !== 0) {
		return;
	}

	$class_file = str_replace('_', '-', strtolower($class_name));
	$class_path = BJI_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

	if (file_exists($class_path)) {
		require_once $class_path;
	}
}

// Initialize the plugin.
add_action('plugins_loaded', 'bji_init');

/**
 * Initialize the plugin.
 */
function bji_init()
{
	// Initialize main plugin class.
	BJI_Plugin::get_instance();
}

/**
 * Load plugin text domain for translations.
 */
function bji_load_textdomain()
{
	load_plugin_textdomain(
		'bulk-json-importer',
		false,
		dirname(plugin_basename(__FILE__)) . '/languages'
	);
}

// Load text domain early.
add_action('init', 'bji_load_textdomain');

// Activation and deactivation hooks.
register_activation_hook(__FILE__, array('BJI_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('BJI_Plugin', 'deactivate'));
