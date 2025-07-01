=== Bulk JSON Importer ===
Contributors: cwahlfeldt
Tags: import, json, bulk, posts, acf, custom fields, gutenberg
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import posts and custom post types from JSON files with intelligent field mapping for WordPress fields, ACF, and custom meta.

== Description ==

The Bulk JSON Importer streamlines the process of importing large amounts of content into WordPress from JSON data sources. Whether you're migrating from another platform, importing data from APIs, or bulk-creating content, this plugin provides a user-friendly interface with advanced mapping capabilities.

= Key Features =

* **Multi-format Support**: Import any public post type (posts, pages, custom post types)
* **Intelligent Field Mapping**: Visual interface for mapping JSON keys to WordPress fields
* **ACF Integration**: Full support for Advanced Custom Fields with automatic field detection
* **Gutenberg Ready**: Automatically converts text content to Gutenberg paragraph blocks
* **Custom Meta Fields**: Support for standard WordPress custom fields (post meta)
* **Secure Processing**: Built-in security checks and data validation
* **Error Handling**: Comprehensive error reporting and import logging
* **Performance Optimized**: Efficient processing for large datasets

= What It Does =

This plugin takes a JSON file containing an array of objects and creates WordPress posts from that data. Each object in the JSON array becomes a new post, with the ability to map JSON properties to:

* **Standard WordPress Fields**: Title, content, excerpt, status, date, etc.
* **ACF Fields**: Any Advanced Custom Fields assigned to the target post type
* **Custom Meta**: Standard WordPress post meta fields

= Supported JSON Format =

`[
  {
    "title": "Your Post Title",
    "content": "Your post content here",
    "excerpt": "Post excerpt",
    "status": "publish",
    "custom_field": "Custom value",
    "acf_field": "ACF value"
  },
  {
    "title": "Another Post",
    "content": "More content"
  }
]`

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Users need `manage_options` capability (typically Administrators)
* Optional: Advanced Custom Fields plugin for ACF field mapping

== Installation ==

= From WordPress Admin (Recommended) =

1. Download the plugin zip file
2. Go to your WordPress admin dashboard
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the downloaded zip file and click **Install Now**
6. Click **Activate Plugin**

= Manual Installation =

1. Download and extract the plugin files
2. Upload the `bulk-json-importer` folder to `/wp-content/plugins/`
3. Go to **Plugins** in your WordPress admin
4. Find "Bulk JSON Importer" and click **Activate**

== Usage ==

= Step 1: Access the Importer =

1. Go to **Tools > Bulk JSON Importer** in your WordPress admin
2. You'll see the upload interface

= Step 2: Upload Your JSON File =

1. Click **Choose File** and select your JSON file
2. Select the target **Post Type** from the dropdown
3. Click **Upload and Proceed to Mapping**

= Step 3: Map Your Fields =

The mapping interface shows three sections:

**Standard Fields**
Map JSON keys to WordPress core fields:
* **Title** (Required): The post title
* **Content**: Post content (converted to Gutenberg blocks)
* **Excerpt**: Post excerpt
* **Status**: publish, draft, private, etc.
* **Date**: Publication date (YYYY-MM-DD HH:MM:SS format)

**ACF Fields (if ACF is active)**
* Automatically detects ACF field groups assigned to your post type
* Shows field name, type, and description
* Supports most ACF field types (text, textarea, number, select, etc.)

**Custom Meta Fields**
* Add unlimited custom field mappings
* Specify JSON key and WordPress meta key
* Use for non-ACF post metadata

= Step 4: Process Import =

1. Review your field mappings
2. Click **Process Import**
3. View the results page with import statistics and any warnings/errors

== Frequently Asked Questions ==

= What JSON format is supported? =

The plugin expects a JSON file containing an array of objects. Each object represents one post to be imported. The objects can have any properties, which you'll map to WordPress fields during the import process.

= Does this work with custom post types? =

Yes! The plugin works with any public post type registered in WordPress, including custom post types created by themes or other plugins.

= Can I import ACF (Advanced Custom Fields) data? =

Absolutely! If you have the ACF plugin installed, the importer will automatically detect all ACF field groups assigned to your target post type and allow you to map JSON data to those fields.

= What happens if the import fails? =

The plugin provides detailed error reporting. If an import fails, you'll see exactly which items failed and why. Successfully imported items are not rolled back, so you can fix issues and re-run the import for failed items.

= Is there a file size limit? =

The plugin can handle reasonably large files, but very large files may be limited by PHP settings like `upload_max_filesize` and `max_execution_time`. We recommend keeping files under 10MB for optimal performance.

= Can I import images and media? =

Currently, the plugin imports text-based data. For images, you would need to upload them separately and reference them by attachment ID in your JSON data for ACF image fields.

= Does it work with Gutenberg blocks? =

Yes! Text content is automatically converted to Gutenberg paragraph blocks. Each line in your content becomes a separate paragraph block.

== Screenshots ==

1. Upload interface - Select your JSON file and target post type
2. Field mapping interface - Map JSON keys to WordPress fields
3. ACF integration - Automatic detection of ACF fields
4. Import results - Detailed reporting of import success and any errors

== Changelog ==

= 0.1.1 =
* Initial release
* Core import functionality
* ACF integration
* Custom meta field support
* Gutenberg block conversion
* Security enhancements

== Upgrade Notice ==

= 0.1.1 =
Initial release of the Bulk JSON Importer plugin.

== Support ==

= What We Support =

* Plugin installation and basic configuration
* JSON format questions and examples
* Field mapping guidance
* ACF integration questions
* Bug reports and feature requests

= What We Don't Support =

* Custom JSON parsing for non-standard formats
* Server configuration and hosting issues
* Custom WordPress or theme modifications
* Data migration consulting services
* Third-party plugin conflicts

= Getting Help =

1. Check the plugin documentation and FAQ
2. Visit the WordPress plugin support forums
3. Report bugs on the plugin's GitHub repository
4. Contact support for professional assistance

= Reporting Bugs =

When reporting issues, please include:
* WordPress version
* PHP version
* Plugin version
* JSON file structure (sample)
* Error messages
* Steps to reproduce

== Privacy ==

This plugin does not collect, store, or transmit any personal data. All JSON file processing happens locally on your server during the import process. Uploaded files are processed and then removed from temporary storage.