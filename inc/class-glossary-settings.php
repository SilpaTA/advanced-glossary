<?php
/**
 * Glossary Settings Page
 *
 * @package Advgls_Glossary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Advgls_Settings {
    
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
        register_setting('advgls_settings_group', 'advgls_auto_link');
    }
    
    /**
     * Render Settings Page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Glossary Settings', 'advanced-glossary'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('advgls_settings_group');
                do_settings_sections('advgls_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Enable Automatic Linking', 'advanced-glossary'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="advgls_auto_link" value="1" <?php checked(1, get_option('advgls_auto_link', 1)); ?> />
                                <?php esc_html_e('Automatically link glossary terms in post content', 'advanced-glossary'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, glossary terms will be automatically detected and linked in your post and page content.', 'advanced-glossary'); ?></p>
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
Advgls_Settings::init();