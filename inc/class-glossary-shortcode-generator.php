<?php
/**
 * Glossary Shortcode Generator Page
 *
 * @package Advgls_Glossary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Advgls_Shortcode_Generator {
    
    /**
     * Initialize shortcode generator
     */
    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    /**
     * Enqueue Assets
     */
    public static function enqueue_assets($hook) {
        if (isset($_GET['page']) && $_GET['page'] === 'advgls-shortcode-generator') {
            wp_enqueue_script(
                'advgls-shortcode-generator',
                ADVGLS_URL . 'js/glossary-shortcode-generator.js',
                array('jquery'),
                ADVGLS_VERSION,
                true
            );
        }
    }
    
    /**
     * Render Shortcode Generator Page
     */
    public static function render_page() {
        $terms = get_posts(array(
            'post_type' => 'glossary',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Glossary Shortcode Generator', 'advanced-glossary'); ?></h1>
            
            <div class="card" style="max-width: 800px;">
                <h2><?php esc_html_e('Generate Glossary Shortcode', 'advanced-glossary'); ?></h2>
                <p><?php esc_html_e('Use this tool to generate shortcodes for your glossary terms. You can then copy and paste the shortcode into your posts or pages.', 'advanced-glossary'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="glossary_term_select"><?php esc_html_e('Select a Glossary Term:', 'advanced-glossary'); ?></label>
                        </th>
                        <td>
                            <select id="glossary_term_select" style="min-width: 300px;">
                                <option value=""><?php esc_html_e('-- Select a term --', 'advanced-glossary'); ?></option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo esc_attr($term->post_title); ?>" data-id="<?php echo esc_attr($term->ID); ?>">
                                        <?php echo esc_html($term->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($terms)): ?>
                                <p class="description" style="color: #d63638;">
                                    <?php
                                    printf(
                                        /* translators: %s: Link to create new glossary term */
                                        esc_html__('No glossary terms found. Please %s first.', 'advanced-glossary'),
                                        '<a href="' . esc_url(admin_url('post-new.php?post_type=glossary')) . '">' . esc_html__('create some terms', 'advanced-glossary') . '</a>'
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <button type="button" id="generate_shortcode" class="button button-primary" <?php echo empty($terms) ? 'disabled' : ''; ?>>
                                <?php esc_html_e('Generate Shortcode', 'advanced-glossary'); ?>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="shortcode_output"><?php esc_html_e('Generated Shortcode:', 'advanced-glossary'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="shortcode_output" readonly style="width: 100%; max-width: 500px;" placeholder="<?php esc_attr_e('Select a term and click Generate', 'advanced-glossary'); ?>">
                            <button type="button" id="copy_shortcode" class="button" style="margin-left: 10px;" disabled>
                                <?php esc_html_e('Copy to Clipboard', 'advanced-glossary'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Use this shortcode in your posts or pages to link to a glossary term with hover tooltip.', 'advanced-glossary'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php esc_html_e('Shortcode Usage Examples', 'advanced-glossary'); ?></h2>
                <p><strong><?php esc_html_e('Basic usage (with term name):', 'advanced-glossary'); ?></strong></p>
                <code>[glossary term="Your Term Name"]</code>
                <p class="description"><?php esc_html_e('This will display the term name as a link with hover tooltip.', 'advanced-glossary'); ?></p>
                
                <p style="margin-top: 15px;"><strong><?php esc_html_e('With custom text:', 'advanced-glossary'); ?></strong></p>
                <code>[glossary term="Your Term Name"]Click here for definition[/glossary]</code>
                <p class="description"><?php esc_html_e('This will display "Click here for definition" as the link text.', 'advanced-glossary'); ?></p>
                
                <p style="margin-top: 15px;"><strong><?php esc_html_e('Using term ID:', 'advanced-glossary'); ?></strong></p>
                <code>[glossary id="123"]</code>
                <p class="description"><?php esc_html_e('Use the term ID instead of the name for better reliability.', 'advanced-glossary'); ?></p>
            </div>
        </div>
        <?php
    }
}

// Initialize shortcode generator
Advgls_Shortcode_Generator::init();