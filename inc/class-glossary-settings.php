<?php
/**
 * Glossary Settings Page
 *
 * @package Advanced_Glossary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Glossary_Settings {
    
    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    /**
     * Register Settings
     */
    public static function register_settings() {
        register_setting('glossary_settings_group', 'advanced_glossary_auto_link');
    }
    
    /**
     * Render Settings Page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>Glossary Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('glossary_settings_group');
                do_settings_sections('glossary-settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Automatic Linking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="advanced_glossary_auto_link" value="1" <?php checked(1, get_option('advanced_glossary_auto_link', 1)); ?> />
                                Automatically link glossary terms in post content
                            </label>
                            <p class="description">When enabled, glossary terms will be automatically detected and linked in your post and page content.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize settings
Glossary_Settings::init();