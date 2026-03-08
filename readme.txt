=== Smart Glossary for WordPress ===
Contributors: silpata
Tags: glossary, tooltip, definition, terms, dictionary, hover, gutenberg, shortcode
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create glossary terms with hover tooltips. Add glossary terms as custom post type and display definitions on hover.

== Description ==

Smart Glossary for WordPress is a powerful plugin that allows you to create and manage glossary terms with hover tooltips. Perfect for educational sites, documentation, technical blogs, and any website that needs to explain terminology.

= Key Features =

* **Custom Post Type**: Dedicated glossary terms management interface
* **Hover Tooltips**: Beautiful popups with definitions that appear on hover
* **Gutenberg Block**: Native block editor support with dedicated Glossary Term block
* **Gutenberg Format**: Inline text formatting toolbar option for quick term insertion
* **Classic Editor**: TinyMCE button for classic editor users
* **Shortcode Support**: Manual term insertion via shortcodes `[advgls_glossary term="name"]` or `[glossary term="name"]`
* **Shortcode Generator**: Visual shortcode builder in admin panel
* **Auto-linking**: Automatically link terms in content (can be disabled)
* **Flexible Settings**: Control auto-linking behavior and appearance
* **AJAX Loading**: Fast, dynamic tooltip loading
* **Responsive**: Mobile-friendly tooltips
* **SEO Friendly**: Proper HTML structure and links

= Multiple Insertion Methods =

1. **Gutenberg Block**: Add a Glossary Term block from the block inserter
2. **Gutenberg Format Toolbar**: Select text and apply Glossary Term format
3. **Classic Editor Button**: Use the book icon in TinyMCE toolbar
4. **Shortcode**: Manually insert `[advgls_glossary term="Term Name"]` or `[glossary term="Term Name"]`
5. **Auto-linking**: Automatically link matching terms (optional)

= How It Works =

1. Create glossary terms in the WordPress admin (Glossary → Add New Term)
2. Add a short definition that appears in the tooltip
3. Insert terms into your content using any of the available methods
4. Visitors hover over linked terms to see definitions instantly

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Smart Glossary for WordPress"
4. Click "Install Now"
5. Click "Activate"

= Manual Installation =

1. Upload the `smart-glossary` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Glossary → Add New Term to create your first term

== Frequently Asked Questions ==

= How do I create a glossary term? =

Go to Glossary → Add New Term in your WordPress admin. Enter the term title and add a short definition in the Glossary Definition meta box.

= Can I disable automatic linking? =

Yes, go to Glossary → Settings and uncheck "Enable Automatic Linking".

= Can I use custom text for glossary terms? =

Yes, when using shortcodes: `[advgls_glossary term="Term Name"]Custom Text[/advgls_glossary]`

= Does it work with Gutenberg? =

Yes, the plugin includes a dedicated Gutenberg block and format toolbar option.

= Does it work with the Classic Editor? =

Yes, a TinyMCE button is available for classic editor users.

= Are tooltips mobile-friendly? =

Yes, tooltips are responsive and work on all devices.

= Can I style the tooltips? =

Yes, you can add custom CSS in Glossary → Settings or modify the CSS files.

== Screenshots ==

1. Glossary term creation interface
2. Gutenberg block editor integration
3. Hover tooltip on frontend
4. Settings page
5. Shortcode generator

== Changelog ==

= 1.0.1 =
* Initial release
* Custom post type for glossary terms
* Hover tooltip functionality
* Gutenberg block support
* Gutenberg format toolbar option
* Classic editor button
* Shortcode support with generator
* Automatic term linking with enable/disable option
* Settings page for configuration
* AJAX-powered definitions
* Flexible auto-linking controls
* Custom CSS options
* Multiple insertion methods
* WordPress.org compliance: distinctive plugin name, prefixed AJAX actions, prefixed shortcode, security improvements

== Upgrade Notice ==

= 1.0.1 =
Initial release of Smart Glossary for WordPress.
