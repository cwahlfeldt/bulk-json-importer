# Bulk JSON Importer

A powerful WordPress plugin that allows you to bulk import posts and custom post types from JSON files with intelligent field mapping for standard WordPress fields, Advanced Custom Fields (ACF), and custom meta fields.

## Description

The Bulk JSON Importer streamlines the process of importing large amounts of content into WordPress from JSON data sources. Whether you're migrating from another platform, importing data from APIs, or bulk-creating content, this plugin provides a user-friendly interface with advanced mapping capabilities.

### Key Features

- **Multi-format Support**: Import any public post type (posts, pages, custom post types)
- **Intelligent Field Mapping**: Visual interface for mapping JSON keys to WordPress fields
- **ACF Integration**: Full support for Advanced Custom Fields with automatic field detection
- **Gutenberg Ready**: Automatically converts text content to Gutenberg paragraph blocks
- **Custom Meta Fields**: Support for standard WordPress custom fields (post meta)
- **Secure Processing**: Built-in security checks and data validation
- **Error Handling**: Comprehensive error reporting and import logging
- **Performance Optimized**: Efficient processing for large datasets

### What It Does

This plugin takes a JSON file containing an array of objects and creates WordPress posts from that data. Each object in the JSON array becomes a new post, with the ability to map JSON properties to:

- **Standard WordPress Fields**: Title, content, excerpt, status, date, etc.
- **ACF Fields**: Any Advanced Custom Fields assigned to the target post type
- **Custom Meta**: Standard WordPress post meta fields

### Supported JSON Format

```json
[
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
]
```

## Installation

### From WordPress Admin (Recommended)

1. Download the plugin zip file
2. Go to your WordPress admin dashboard
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the downloaded zip file and click **Install Now**
6. Click **Activate Plugin**

### Manual Installation

1. Download and extract the plugin files
2. Upload the `bulk-json-importer` folder to `/wp-content/plugins/`
3. Go to **Plugins** in your WordPress admin
4. Find "Bulk JSON Importer" and click **Activate**

### Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Permissions**: Users need `manage_options` capability (typically Administrators)
- **Optional**: Advanced Custom Fields plugin for ACF field mapping

## Usage

### Step 1: Access the Importer

1. Go to **Tools > Bulk JSON Importer** in your WordPress admin
2. You'll see the upload interface

### Step 2: Upload Your JSON File

1. Click **Choose File** and select your JSON file
2. Select the target **Post Type** from the dropdown
3. Click **Upload and Proceed to Mapping**

### Step 3: Map Your Fields

The mapping interface shows three sections:

#### Standard Fields
Map JSON keys to WordPress core fields:
- **Title** (Required): The post title
- **Content**: Post content (converted to Gutenberg blocks)
- **Excerpt**: Post excerpt
- **Status**: publish, draft, private, etc.
- **Date**: Publication date (YYYY-MM-DD HH:MM:SS format)

#### ACF Fields (if ACF is active)
- Automatically detects ACF field groups assigned to your post type
- Shows field name, type, and description
- Supports most ACF field types (text, textarea, number, select, etc.)

#### Custom Meta Fields
- Add unlimited custom field mappings
- Specify JSON key and WordPress meta key
- Use for non-ACF post metadata

### Step 4: Process Import

1. Review your field mappings
2. Click **Process Import**
3. View the results page with import statistics and any warnings/errors

## Advanced Usage

### JSON Data Preparation

For best results, ensure your JSON data is:

- **Valid JSON**: Use a JSON validator to check syntax
- **UTF-8 Encoded**: Prevents character encoding issues
- **Consistent Structure**: All objects should have similar properties
- **Reasonable Size**: Large files may require server timeout adjustments

### ACF Field Support

The plugin supports most ACF field types:

- **Basic Fields**: Text, Textarea, Number, Email, URL, Password
- **Content Fields**: Wysiwyg Editor, Oembed, Image, File, Gallery
- **Choice Fields**: Select, Checkbox, Radio Button, True/False
- **Relational Fields**: Link, Post Object, Page Link, Relationship, Taxonomy, User
- **jQuery Fields**: Google Map, Date Picker, Color Picker
- **Layout Fields**: Message, Accordion, Tab, Group

**Note**: Complex fields (Repeater, Gallery, Relationship) require data in ACF's expected format.

### Custom Field Examples

```json
{
  "post_title": "Sample Post",
  "post_content": "This is the content",
  "custom_price": "29.99",
  "custom_category": "electronics",
  "acf_field_key": "value"
}
```

### Performance Tips

- **File Size**: Keep JSON files under 10MB for optimal performance
- **Batch Processing**: For very large datasets, split into multiple files
- **Server Resources**: Increase PHP memory limit and execution time if needed
- **Testing**: Always test with a small subset first

## Troubleshooting

### Common Issues

**File Upload Errors**
- Check file size limits in PHP settings
- Ensure JSON file is valid and properly formatted
- Verify file permissions

**Import Fails or Times Out**
- Increase PHP memory limit (`memory_limit = 256M`)
- Increase execution time (`max_execution_time = 300`)
- Reduce file size or split into smaller files

**Fields Not Mapping Correctly**
- Check JSON key names match exactly (case-sensitive)
- Verify ACF field groups are assigned to the post type
- Ensure custom field names are valid (alphanumeric and underscores)

**Posts Not Created**
- Verify user has sufficient permissions
- Check that post title is mapped (required field)
- Review error messages in the import log

### Debug Mode

To enable detailed logging, add this to your `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Check `/wp-content/debug.log` for detailed error information.

## Support

### What We Support

- ✅ Plugin installation and basic configuration
- ✅ JSON format questions and examples
- ✅ Field mapping guidance
- ✅ ACF integration questions
- ✅ Bug reports and feature requests

### What We Don't Support

- ❌ Custom JSON parsing for non-standard formats
- ❌ Server configuration and hosting issues
- ❌ Custom WordPress or theme modifications
- ❌ Data migration consulting services
- ❌ Third-party plugin conflicts

### Getting Help

1. **Documentation**: Check this README and plugin comments
2. **GitHub Issues**: Report bugs at [repository URL]
3. **WordPress Forums**: Community support available
4. **Professional Support**: Contact [support email] for premium assistance

### Reporting Bugs

When reporting issues, please include:

- WordPress version
- PHP version
- Plugin version
- JSON file structure (sample)
- Error messages
- Steps to reproduce

## Changelog

### Version 0.1.1
- Initial release
- Core import functionality
- ACF integration
- Custom meta field support
- Gutenberg block conversion
- Security enhancements

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Internationalization

The plugin is fully internationalized and ready for translation.

### Available Languages

- **English**: Default language (built-in)
- **Spanish (Spain)**: Complete translation included

### Adding Translations

To translate the plugin to your language:

1. Use the template file `/languages/bulk-json-importer.pot`
2. Create a new `.po` file for your locale (e.g., `bulk-json-importer-fr_FR.po`)
3. Translate all strings using tools like Poedit
4. Compile to `.mo` file using `msgfmt`
5. Place both files in the `/languages/` directory

For detailed translation instructions, see `/languages/README.md`.

### Changing Language

To use the plugin in Spanish or another language:

1. Go to **Settings > General** in WordPress admin
2. Change **Site Language** to your preferred language
3. The plugin interface will automatically display in that language

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Translation Contributions

We especially welcome translation contributions:
- Complete translations to new languages
- Improvements to existing translations
- Cultural adaptations and localizations

## Credits

- **Author**: Chris Wahlfeldt
- **Contributors**: [List contributors]
- **Libraries**: Built with WordPress standards and best practices

---

**Note**: This plugin is provided as-is. Always backup your database before importing large amounts of data.