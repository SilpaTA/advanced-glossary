<?php
/**
 * Glossary Shortcode Generator Page
 *
 * @package Advanced_Glossary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Glossary_Shortcode_Generator {
    
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
        if (isset($_GET['page']) && $_GET['page'] === 'glossary-shortcode-generator') {
            wp_enqueue_script(
                'glossary-shortcode-generator',
                ADVANCED_GLOSSARY_URL . 'js/glossary-shortcode-generator.js',
                array('jquery'),
                ADVANCED_GLOSSARY_VERSION,
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
            <h1>Glossary Shortcode Generator</h1>
            
            <div class="card" style="max-width: 800px;">
                <h2>Generate Glossary Shortcode</h2>
                <p>Use this tool to generate shortcodes for your glossary terms. You can then copy and paste the shortcode into your posts or pages.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="glossary_term_select">Select a Glossary Term:</label>
                        </th>
                        <td>
                            <select id="glossary_term_select" style="min-width: 300px;">
                                <option value="">-- Select a term --</option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo esc_attr($term->post_title); ?>" data-id="<?php echo esc_attr($term->ID); ?>">
                                        <?php echo esc_html($term->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($terms)): ?>
                                <p class="description" style="color: #d63638;">
                                    No glossary terms found. Please <a href="<?php echo admin_url('post-new.php?post_type=glossary'); ?>">create some terms</a> first.
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <button type="button" id="generate_shortcode" class="button button-primary" <?php echo empty($terms) ? 'disabled' : ''; ?>>
                                Generate Shortcode
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="shortcode_output">Generated Shortcode:</label>
                        </th>
                        <td>
                            <input type="text" id="shortcode_output" readonly style="width: 100%; max-width: 500px;" placeholder="Select a term and click Generate">
                            <button type="button" id="copy_shortcode" class="button" style="margin-left: 10px;" disabled>
                                Copy to Clipboard
                            </button>
                            <p class="description">Use this shortcode in your posts or pages to link to a glossary term with hover tooltip.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Shortcode Usage Examples</h2>
                <p><strong>Basic usage (with term name):</strong></p>
                <code>[glossary term="Your Term Name"]</code>
                <p class="description">This will display the term name as a link with hover tooltip.</p>
                
                <p style="margin-top: 15px;"><strong>With custom text:</strong></p>
                <code>[glossary term="Your Term Name"]Click here for definition[/glossary]</code>
                <p class="description">This will display "Click here for definition" as the link text.</p>
                
                <p style="margin-top: 15px;"><strong>Using term ID:</strong></p>
                <code>[glossary id="123"]</code>
                <p class="description">Use the term ID instead of the name for better reliability.</p>
            </div>
        </div>
        <?php
    }
}

// Initialize shortcode generator
Glossary_Shortcode_Generator::init();