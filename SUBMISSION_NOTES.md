# WordPress.org Submission Notes for Smart Glossary for WordPress

## Pre-Submission Checklist

Before submitting to WordPress.org, complete these steps:

### 1. Request New Slug Reservation

Reply to the WordPress Plugin Review Team email requesting reservation of the new slug: **smart-glossary**

### 2. Package the Plugin for Upload

**Important:** The plugin folder and main file must match the reserved slug.

1. Create a new folder named `smart-glossary`
2. Copy ALL files from this plugin into the new folder **EXCEPT** `advanced-glossary.php`
3. Ensure the `smart-glossary` folder contains:
   - `smart-glossary.php` (main file - required)
   - `readme.txt`
   - `uninstall.php`
   - `inc/` folder
   - `js/` folder
   - `css/` folder
5. **Do NOT include** in the zip:
   - `advanced-glossary.php` (old main file - only used when folder is "advanced-glossary")
   - `assets/` folder if present (icons, banners - upload via SVN after approval)
   - `SUBMISSION_NOTES.md`, `README.md`, `CSV_IMPORT_PLAN.md`, `COMPLIANCE_CHECKLIST.md` (optional)
   - `node_modules`, `.git`, or development files

### 3. Plugin Assets (Icons, Banners)

WordPress.org directory assets (icons, banners, screenshots) should **NOT** be in the plugin zip. Upload them separately via SVN after the plugin is approved. See: [How Your Plugin Assets Work](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#how-your-plugin-assets-work)

### 4. Upload

1. Zip the `smart-glossary` folder
2. Upload via the "Add your plugin" page on WordPress.org
3. You may see a text domain warning - that's fine until the slug is reserved

## Changes Made for WordPress.org Compliance

- **Plugin Name:** Changed from "Advanced Glossary" to "Smart Glossary for WordPress"
- **Slug:** Changed from "advanced-glossary" to "smart-glossary"
- **Text Domain:** Updated to "smart-glossary" throughout
- **AJAX Actions:** Prefixed with `advgls_` (e.g., `advgls_get_glossary_terms`, `advgls_get_glossary_definition`)
- **Shortcode:** Added prefixed `[advgls_glossary]` (kept `[glossary]` for backward compatibility)
- **Meta Box:** Prefixed nonce and ID with `advgls_`
- **Security:** Added `wp_unslash()` and `sanitize_text_field()` for `$_POST`/`$_GET`, `load_plugin_textdomain()` for i18n
- **No assets folder** in plugin zip (upload via SVN after approval)
