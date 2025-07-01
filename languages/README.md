# Translation Files

This directory contains translation files for the Bulk JSON Importer plugin.

## Available Languages

- **English (Default)**: Built into the plugin code
- **Spanish (Spain)**: `bulk-json-importer-es_ES.po` / `bulk-json-importer-es_ES.mo`

## For Translators

### Creating a New Translation

1. **Copy the template**: Start with `bulk-json-importer.pot`
2. **Rename the file**: Use the format `bulk-json-importer-{locale}.po`
   - Example: `bulk-json-importer-fr_FR.po` for French (France)
   - Example: `bulk-json-importer-de_DE.po` for German (Germany)
3. **Update the header**: Change the language information in the file header
4. **Translate strings**: Replace `msgstr ""` with your translations
5. **Compile**: Use `msgfmt` to create the `.mo` file

### WordPress Locale Codes

Common locale codes:
- `es_ES` - Spanish (Spain)
- `fr_FR` - French (France)
- `de_DE` - German (Germany)
- `it_IT` - Italian (Italy)
- `pt_BR` - Portuguese (Brazil)
- `ja` - Japanese
- `zh_CN` - Chinese (Simplified)
- `ru_RU` - Russian

### Compiling Translations

To compile a `.po` file to a `.mo` file:

```bash
msgfmt bulk-json-importer-{locale}.po -o bulk-json-importer-{locale}.mo
```

### Translation Tools

Recommended tools for translation:
- **Poedit**: User-friendly GUI for translation
- **WordPress.org GlotPress**: Online translation platform
- **Loco Translate**: WordPress plugin for in-admin translation

### File Structure

```
languages/
├── bulk-json-importer.pot          # Template file (for translators)
├── bulk-json-importer-es_ES.po     # Spanish source file
├── bulk-json-importer-es_ES.mo     # Spanish compiled file
└── README.md                       # This file
```

### Contributing Translations

If you create a translation for this plugin, please consider:

1. **Testing**: Test the translation in a WordPress environment
2. **Quality**: Ensure translations are accurate and culturally appropriate
3. **Completeness**: Translate all strings, not just some
4. **Sharing**: Submit translations back to the plugin authors

### String Categories

The plugin includes these types of translatable strings:

- **UI Labels**: Form labels, button text, page titles
- **Messages**: Success messages, error messages, warnings
- **Help Text**: Descriptions, instructions, tooltips
- **Validation**: Error messages for form validation

### Context Information

Some strings include context comments to help translators:
- **File references**: Where the string appears in the code
- **Placeholders**: Variables like `%s` and `%d` that will be replaced
- **Plurals**: Strings that change based on quantity

### Testing Translations

To test your translation:

1. Place the `.mo` file in the `languages/` directory
2. Change your WordPress site language to your locale
3. Navigate through the plugin interface
4. Verify all strings are translated correctly
5. Test with different data scenarios

## Technical Notes

- The plugin uses the text domain `bulk-json-importer`
- Translations are loaded on the `init` action hook
- JavaScript strings are localized via `wp_localize_script`
- The plugin follows WordPress internationalization best practices

## Questions?

For translation questions or to submit new translations, please:
- Check the plugin documentation
- Contact the plugin authors
- Use the WordPress.org plugin support forums