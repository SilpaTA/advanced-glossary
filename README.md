# Advanced Glossary Plugin - Installation Guide

## Plugin Structure

The plugin has the following folder structure in your WordPress plugins directory (`wp-content/plugins/`):

```
advanced-glossary/
├── advanced-glossary.php (main plugin file)
├── css/
│   └── glossary-style.css
├── inc/
│   ├── class-glossary-settings.php
│   └── class-glossary-shortcode-generator.php
├── js/
│   ├── glossary-admin.js
│   ├── glossary-block.js
│   ├── glossary-format.js
│   ├── glossary-script.js
│   ├── glossary-shortcode-generator.js
│   └── glossary-tinymce.js
└── README.md
```

## Installation Steps

### Step 1: Create Plugin Folder
1. Navigate to `wp-content/plugins/` in your WordPress installation
2. Create a new folder named `advanced-glossary`

### Step 2: Add Plugin Files
1. Create `advanced-glossary.php` and paste the main plugin code
2. Create a `css` folder and add `glossary-style.css`
3. Create an `inc` folder and add the class files:
   - `class-glossary-settings.php` (settings page functionality)
   - `class-glossary-shortcode-generator.php` (shortcode generator)
4. Create a `js` folder and add all JavaScript files:
   - `glossary-script.js` (frontend tooltip functionality)
   - `glossary-block.js` (Gutenberg block)
   - `glossary-format.js` (Gutenberg format toolbar)
   - `glossary-tinymce.js` (Classic editor button)
   - `glossary-admin.js` (admin functionality)
   - `glossary-shortcode-generator.js` (shortcode generator UI)

### Step 3: Activate Plugin
1. Go to WordPress Admin Dashboard
2. Navigate to **Plugins** → **Installed Plugins**
3. Find "Advanced Glossary" and click **Activate**

## Usage Guide

### Creating Glossary Terms

1. In WordPress admin, you'll see a new **Glossary** menu item
2. Click **Add New Term**
3. Enter the **term title** (the word you want to define)
4. Add detailed content in the main editor (optional - for full glossary page)
5. In the **Glossary Definition** meta box, add a short definition (this appears in the tooltip)
6. Click **Publish**

### Adding Glossary Terms to Content

#### Method 1: Gutenberg Block Editor - Glossary Block
1. Edit any post or page
2. Click the **+** button to add a block
3. Search for **"Glossary Term"**
4. Select the block
5. Choose a glossary term from the dropdown
6. Optionally customize the display text
7. The term will appear with hover functionality

#### Method 2: Gutenberg Block Editor - Format Toolbar
1. Edit any post or page in the block editor
2. Select the text you want to convert to a glossary term
3. Click the **dropdown arrow** in the formatting toolbar
4. Select **"Glossary Term"** from the format options
5. Choose a glossary term from the dropdown
6. The selected text will be converted to a glossary link

#### Method 3: Classic Editor
1. Edit any post or page
2. Look for the **book icon** in the toolbar
3. Click it to open the glossary term selector
4. Select a term from the dropdown
5. Optionally add custom display text
6. Click **Insert**

#### Method 4: Shortcode
You can manually insert glossary terms using shortcodes:

**Basic usage:**
```
[glossary term="Your Term Name"]
```

**With custom text:**
```
[glossary term="Your Term Name"]Click here for definition[/glossary]
```

**Using term ID:**
```
[glossary id="123"]
```

**Shortcode Generator (in Classic Editor):**
1. Click the **"G" icon** in the editor toolbar
2. Select a term from the dropdown
3. Optionally add custom display text
4. Click **Insert Shortcode**
5. The shortcode will be inserted at your cursor position

#### Method 5: Automatic Linking
The plugin can automatically link glossary terms throughout your content:
- Any word matching a glossary term title will be automatically linked
- Only the first occurrence of each term per page is linked by default
- Hover over any linked term to see the definition tooltip
- **This feature can be enabled/disabled** in plugin settings

### Plugin Settings

Navigate to **Glossary → Settings** to configure:

#### Auto-Linking Options
- **Enable Auto-Linking**: Toggle automatic linking of glossary terms in content
- **Link First Occurrence Only**: Choose whether to link only the first occurrence or all occurrences
- **Post Types**: Select which post types should have auto-linking enabled (Posts, Pages, Custom Post Types)

#### Display Options
- **Tooltip Animation**: Choose animation style for tooltips
- **Tooltip Position**: Set default tooltip position (top, bottom, left, right)
- **Custom CSS**: Add custom CSS to style glossary terms and tooltips

#### Advanced Options
- **Case Sensitive Matching**: Enable/disable case-sensitive term matching
- **Exclude Words**: Add words that should never be linked automatically
- **Priority Terms**: Set which terms should take precedence when multiple matches exist

### Customization

#### Styling the Tooltip
Edit `css/glossary-style.css` to customize:
- Tooltip colors and fonts
- Border styles
- Animation effects
- Positioning

#### Changing Link Appearance
Modify the `.glossary-term` CSS class to change:
- Text color
- Underline style
- Hover effects

Or use the **Custom CSS** option in **Glossary → Settings** to add your own styles without editing files.

## Features

✅ **Custom Post Type**: Dedicated glossary terms management  
✅ **Hover Tooltips**: Beautiful popups with definitions  
✅ **Gutenberg Block**: Native block editor support  
✅ **Gutenberg Format**: Inline text formatting toolbar option  
✅ **Classic Editor**: TinyMCE button for classic editor  
✅ **Shortcode Support**: Manual term insertion via shortcodes  
✅ **Shortcode Generator**: Visual shortcode builder in classic editor  
✅ **Auto-linking**: Automatically link terms in content (can be disabled)  
✅ **Flexible Settings**: Control auto-linking behavior and appearance  
✅ **AJAX Loading**: Fast, dynamic tooltip loading  
✅ **Responsive**: Mobile-friendly tooltips  
✅ **SEO Friendly**: Proper HTML structure and links  
✅ **Multiple Insert Methods**: Block, format, button, shortcode, or automatic

## Advanced Features

### Manual Term Insertion in HTML
You can manually add glossary terms in HTML mode:
```html
<span class="glossary-term" data-term-id="123">Your Term</span>
```

### Disable Auto-linking for Specific Posts
You can disable auto-linking for individual posts using custom fields or by disabling it globally in settings.

### Custom Styling
- Use the **Custom CSS** field in **Glossary → Settings**
- Add custom CSS to your theme's `style.css`
- Use the WordPress Customizer for live preview

### Developer Hooks

**Filters:**
```php
// Modify auto-linked content
apply_filters('glossary_auto_link_content', $content);

// Customize tooltip HTML
apply_filters('glossary_tooltip_html', $html, $term_id);

// Control which terms are auto-linked
apply_filters('glossary_auto_link_terms', $terms);
```

**Actions:**
```php
// Run code after term is saved
do_action('glossary_term_saved', $post_id);

// Modify settings before saving
do_action('glossary_settings_update', $settings);
```

## Troubleshooting

### Tooltips Not Appearing
- Check that JavaScript is enabled
- Ensure jQuery is loaded
- Check browser console for errors
- Clear your cache and try again

### Terms Not Auto-linking
- Check if auto-linking is **enabled** in **Glossary → Settings**
- Make sure the term title exactly matches the word in content
- Verify that the post type is enabled for auto-linking in settings
- Check if case-sensitive matching is affecting results

### Gutenberg Block Not Showing
- Ensure you're using WordPress 5.0 or higher
- Try regenerating your permalinks (Settings → Permalinks → Save)
- Clear block editor cache

### Shortcodes Not Working
- Verify the term slug or ID is correct
- Check that the glossary term is published
- Ensure shortcode syntax is correct: `[glossary term="slug"]` or `[glossary id="123"]`

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Modern browser with JavaScript enabled

## Support

For issues or feature requests, please contact your developer or modify the plugin as needed.

## Changelog

### Version 1.0.0
- Initial release
- Custom post type for glossary terms
- Hover tooltip functionality
- Gutenberg block support
- Gutenberg format toolbar option
- Classic editor button
- Shortcode support with generator
- Automatic term linking with enable/disable option
- Settings page for configuration
- AJAX-powered definitions
- Flexible auto-linking controls
- Custom CSS options
- Multiple insertion methods