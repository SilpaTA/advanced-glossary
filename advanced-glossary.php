<?php
/**
 * Plugin Name: Advanced Glossary
 * Plugin URI: https://wordpress.org/plugins/advanced-glossary
 * Description: Create glossary terms with hover tooltips. Add glossary terms as custom post type and display definitions on hover.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Silpa T A
 * Author URI: https://profiles.wordpress.org/shilpaashokan94/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-glossary
 * Domain Path: /languages
 * Network: false
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADVGLS_VERSION', '1.0.1');
define('ADVGLS_PATH', plugin_dir_path(__FILE__));
define('ADVGLS_URL', plugin_dir_url(__FILE__));

class Advgls_Glossary {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Load plugin textdomain for internationalization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Load dependencies
        $this->load_dependencies();
        
        // Register custom post type
        add_action('init', array($this, 'register_glossary_post_type'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_glossary_meta_boxes'));
        add_action('save_post', array($this, 'save_glossary_meta'));
        
        // Clear cache when glossary terms are saved or deleted
        add_action('save_post_glossary', array($this, 'clear_glossary_cache'));
        add_action('delete_post', array($this, 'clear_glossary_cache_on_delete'));
        
        // Add Gutenberg block
        add_action('init', array($this, 'register_glossary_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add Classic Editor button
        add_action('admin_head', array($this, 'add_classic_editor_button'));
        
        // AJAX handlers
        add_action('wp_ajax_get_glossary_terms', array($this, 'ajax_get_glossary_terms'));
        add_action('wp_ajax_get_glossary_definition', array($this, 'ajax_get_glossary_definition'));
        add_action('wp_ajax_nopriv_get_glossary_definition', array($this, 'ajax_get_glossary_definition'));
        
        // Auto-link glossary terms in content (runs after process_glossary_terms)
        add_filter('the_content', array($this, 'auto_link_glossary_terms'), 15);
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register shortcode
        add_shortcode('glossary', array($this, 'glossary_shortcode_handler'));
        // Process glossary terms from Gutenberg (runs before auto-linking)
        add_filter('the_content', array($this, 'process_glossary_terms'), 5);


        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashicons'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'advanced-glossary',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ADVGLS_PATH . 'inc/class-glossary-settings.php';
        require_once ADVGLS_PATH . 'inc/class-glossary-shortcode-generator.php';
        require_once ADVGLS_PATH . 'inc/class-advgls-csv-importer.php';
    }
    
    /**
     * Register Glossary Custom Post Type
     */
    public function register_glossary_post_type() {
        $labels = array(
            'name'                  => __('Glossary Terms', 'advanced-glossary'),
            'singular_name'         => __('Glossary Term', 'advanced-glossary'),
            'menu_name'             => __('Glossary', 'advanced-glossary'),
            'add_new'               => __('Add New Term', 'advanced-glossary'),
            'add_new_item'          => __('Add New Glossary Term', 'advanced-glossary'),
            'edit_item'             => __('Edit Glossary Term', 'advanced-glossary'),
            'new_item'              => __('New Glossary Term', 'advanced-glossary'),
            'view_item'             => __('View Glossary Term', 'advanced-glossary'),
            'search_items'          => __('Search Glossary', 'advanced-glossary'),
            'not_found'             => __('No glossary terms found', 'advanced-glossary'),
            'not_found_in_trash'    => __('No glossary terms found in trash', 'advanced-glossary'),
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,  
            'show_ui'               => true,   
            'has_archive'           => false,  
            'publicly_queryable'    => false,  
            'show_in_menu'          => false,
            'show_in_rest'          => true,
            'menu_icon'             => 'dashicons-testimonial',
            'supports'              => array('title'),
            'rewrite'               => array('slug' => 'glossary'),
        );
        
        register_post_type('glossary', $args);
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_glossary_meta_boxes() {
        add_meta_box(
            'glossary_description',
            __('Glossary Definition', 'advanced-glossary'),
            array($this, 'render_glossary_meta_box'),
            'glossary',
            'normal',
            'high'
        );
    }
    
    /**
     * Render Meta Box
     */
    public function render_glossary_meta_box($post) {
        wp_nonce_field('glossary_meta_box', 'glossary_meta_box_nonce');
        $description = get_post_meta($post->ID, '_glossary_description', true);
        ?>
        <p>
            <label for="glossary_description"><strong><?php esc_html_e('Short Definition (for tooltip):', 'advanced-glossary'); ?></strong></label><br>
            <textarea id="glossary_description" name="glossary_description" rows="4" style="width:100%;"><?php echo esc_textarea($description); ?></textarea>
            <span class="description"><?php esc_html_e('This brief definition will appear in the hover tooltip.', 'advanced-glossary'); ?></span>
        </p>
        <?php
    }
    
    /**
     * Save Meta Box Data
     */
    public function save_glossary_meta($post_id) {
        if (!isset($_POST['glossary_meta_box_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['glossary_meta_box_nonce'], 'glossary_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['glossary_description'])) {
            update_post_meta($post_id, '_glossary_description', sanitize_textarea_field($_POST['glossary_description']));
        }
    }
    
    /**
     * Register Gutenberg Block
     */
    public function register_glossary_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        wp_register_script(
            'advgls-block',
            ADVGLS_URL . 'js/glossary-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
            ADVGLS_VERSION,
            true
        );
        
        register_block_type('advgls/term', array(
            'editor_script' => 'advgls-block',
        ));
    }
    
    /**
     * Enqueue Frontend Assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style('advgls-style', ADVGLS_URL . 'css/glossary-style.css', array(), ADVGLS_VERSION);
        wp_enqueue_script('advgls-script', ADVGLS_URL . 'js/glossary-script.js', array('jquery'), ADVGLS_VERSION, true);
        
        wp_localize_script('advgls-script', 'glossaryAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glossary_nonce'),
            'loading_text' => __('Loading...', 'advanced-glossary'),
            'error_text' => __('Error loading definition', 'advanced-glossary')
        ));
    }
    public function enqueue_dashicons() {
        wp_enqueue_style('dashicons');
    }
  
    /**
     * Enqueue Admin Assets
     */
    public function enqueue_admin_assets($hook) {
        // Enqueue admin styles for all glossary admin pages
        if (strpos($hook, 'glossary') !== false || strpos($hook, 'advgls') !== false) {
            wp_enqueue_style('advgls-admin', ADVGLS_URL . 'css/advgls-admin.css', array(), ADVGLS_VERSION);
        }
        
        // Enqueue on post edit pages
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_style('advgls-admin-style', ADVGLS_URL . 'css/glossary-editor.css', array(), ADVGLS_VERSION);
            wp_enqueue_script('advgls-admin', ADVGLS_URL . 'js/glossary-admin.js', array('jquery'), ADVGLS_VERSION, true);
            wp_localize_script('advgls-admin', 'glossaryAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('glossary_nonce'),
                'select_text' => __('Please select some text in the editor to link to a glossary term.', 'advanced-glossary'),
                'load_error' => __('Failed to load glossary terms', 'advanced-glossary'),
                'error_loading' => __('Error loading glossary terms', 'advanced-glossary'),
                'no_terms' => __('No glossary terms available. Please create some glossary terms first.', 'advanced-glossary'),
                'select_term' => __('Please select a glossary term', 'advanced-glossary'),
                'term_not_found' => __('Selected term not found', 'advanced-glossary')
            ));
        }
    }
    
    /**
     * Add Classic Editor Button
     */
    public function add_classic_editor_button() {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }
        
        if (get_user_option('rich_editing') !== 'true') {
            return;
        }
        
        add_filter('mce_external_plugins', array($this, 'add_tinymce_plugin'));
        add_filter('mce_buttons', array($this, 'register_tinymce_button'));
    }
    
    public function add_tinymce_plugin($plugin_array) {
        $plugin_array['advgls_button'] = ADVGLS_URL . 'js/glossary-tinymce.js';
        return $plugin_array;
    }
    
    public function register_tinymce_button($buttons) {
        array_push($buttons, 'advgls_button');
        return $buttons;
    }
    
    /**
     * AJAX: Get Glossary Terms
     */
    public function ajax_get_glossary_terms() {
        check_ajax_referer('glossary_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'advanced-glossary'));
            return;
        }
        
        $args = array(
            'post_type' => 'glossary',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        $terms = get_posts($args);
        $result = array();
        
        foreach ($terms as $term) {
            $result[] = array(
                'id' => $term->ID,
                'title' => $term->post_title,
            );
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get Glossary Definition
     */
    public function ajax_get_glossary_definition() {
        check_ajax_referer('glossary_nonce', 'nonce');
        
        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        
        if (!$term_id) {
            wp_send_json_error(__('Invalid term ID', 'advanced-glossary'));
            return;
        }
        
        $term = get_post($term_id);
        
        if (!$term || $term->post_type !== 'glossary' || $term->post_status !== 'publish') {
            wp_send_json_error(__('Term not found', 'advanced-glossary'));
            return;
        }
        
        $description = get_post_meta($term_id, '_glossary_description', true);
        $permalink = get_permalink($term_id);
        
        wp_send_json_success(array(
            'title' => sanitize_text_field($term->post_title),
            'description' => $description ? wp_kses_post($description) : __('No description available.', 'advanced-glossary'),
            'link' => $permalink ? esc_url($permalink) : '',
        ));
    }

    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'advgls-format',
            ADVGLS_URL . 'js/glossary-format.js',
            array('wp-rich-text', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch', 'wp-data'),
            ADVGLS_VERSION,
            true
        );
        
    }
    /**
     * Auto-link glossary terms in content
     */
    public function auto_link_glossary_terms($content) {
        if (!is_singular() || is_admin()) {
            return $content;
        }

        // Check if auto-linking is enabled
        $auto_link_enabled = get_option('advgls_auto_link', 1);
        if (!$auto_link_enabled) {
            return $content;
        }

        // Skip if content already contains glossary terms (avoid double processing)
        if (strpos($content, 'advgls-term') !== false) {
            return $content;
        }

        // Get cached glossary terms
        $cache_key = 'advgls_terms';
        $glossary_terms = get_transient($cache_key);
        
        if (false === $glossary_terms) {
            $args = array(
                'post_type' => 'glossary',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            );

            $glossary_terms = get_posts($args);
            // Cache for 1 hour
            set_transient($cache_key, $glossary_terms, HOUR_IN_SECONDS);
        }

        if (empty($glossary_terms)) {
            return $content;
        }

        // Sort terms by length (longest first) to match longer terms first
        usort($glossary_terms, function($a, $b) {
            return strlen($b->post_title) - strlen($a->post_title);
        });

        // Process content by splitting into text and HTML parts
        $processed_content = '';
        $offset = 0;
        $content_length = strlen($content);
        
        // Find all HTML tags and glossary spans
        preg_match_all('/(<[^>]+>)/', $content, $tag_matches, PREG_OFFSET_CAPTURE);
        
        $text_segments = array();
        $last_pos = 0;
        
        foreach ($tag_matches[0] as $tag_match) {
            $tag_pos = $tag_match[1];
            $tag = $tag_match[0];
            
            // Extract text before this tag
            if ($tag_pos > $last_pos) {
                $text_segments[] = array(
                    'type' => 'text',
                    'content' => substr($content, $last_pos, $tag_pos - $last_pos),
                    'start' => $last_pos,
                    'end' => $tag_pos
                );
            }
            
            // Add the tag
            $text_segments[] = array(
                'type' => 'tag',
                'content' => $tag,
                'start' => $tag_pos,
                'end' => $tag_pos + strlen($tag)
            );
            
            $last_pos = $tag_pos + strlen($tag);
        }
        
        // Add remaining text
        if ($last_pos < $content_length) {
            $text_segments[] = array(
                'type' => 'text',
                'content' => substr($content, $last_pos),
                'start' => $last_pos,
                'end' => $content_length
            );
        }
        
        // Process each text segment
        foreach ($text_segments as $segment) {
            if ($segment['type'] === 'tag') {
                $processed_content .= $segment['content'];
            } else {
                $text = $segment['content'];
                
                // Skip if already contains glossary terms
                if (strpos($text, 'advgls-term') !== false) {
                    $processed_content .= $text;
                    continue;
                }
                
                // Try to match each glossary term
                foreach ($glossary_terms as $term) {
                    $word = $term->post_title;
                    if (empty($word)) {
                        continue;
                    }
                    
                    $word_escaped = preg_quote($word, '/');
                    $pattern = '/\b(' . $word_escaped . ')\b/i';
                    
                    if (preg_match($pattern, $text)) {
                        $text = preg_replace($pattern, '<span class="advgls-term" data-term-id="' . esc_attr($term->ID) . '">$1</span>', $text, 1);
                        // Only link first occurrence per text segment
                        break;
                    }
                }
                
                $processed_content .= $text;
            }
        }

        return $processed_content;
    }
    
    /**
     * Add Admin Menu
     */

    public function add_admin_menu() {
           
        add_menu_page(
            __('Glossary', 'advanced-glossary'),
            __('Glossary', 'advanced-glossary'),
            'manage_options',
            'edit.php?post_type=glossary',
            '',
            'dashicons-testimonial',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'edit.php?post_type=glossary',
            __('Glossary Settings', 'advanced-glossary'),
            __('Settings', 'advanced-glossary'),
            'manage_options',
            'advgls-settings',
            array('Advgls_Settings', 'render_page')
        );
        
        // Shortcode Generator submenu
        add_submenu_page(
            'edit.php?post_type=glossary',
            __('Shortcode Generator', 'advanced-glossary'),
            __('Shortcode Generator', 'advanced-glossary'),
            'manage_options',
            'advgls-shortcode-generator',
            array('Advgls_Shortcode_Generator', 'render_page')
        );
        
        // CSV Import submenu
        add_submenu_page(
            'edit.php?post_type=glossary',
            __('Import from CSV', 'advanced-glossary'),
            __('Import from CSV', 'advanced-glossary'),
            'manage_options',
            'advgls-import-csv',
            array('Advgls_CSV_Importer', 'render_page')
        );
    }
    
    /**
     * Shortcode Handler
     */
    public function glossary_shortcode_handler($atts, $content = null) {
        $atts = shortcode_atts(array(
            'term' => '',
            'id' => ''
        ), $atts);

        $term = sanitize_text_field($atts['term']);
        $id = isset($atts['id']) ? absint($atts['id']) : 0;

        $post = null;

        if ($id) {
            $post = get_post($id);
            if (!$post || $post->post_type !== 'glossary' || $post->post_status !== 'publish') {
                $post = null;
            }
        } elseif ($term) {
            // Try to find by post_name (slug) first
            $term_slug = sanitize_title($term);
            $post = get_page_by_path($term_slug, OBJECT, 'glossary');
            
            // If not found by slug or not published, search by exact title match
            if (!$post || $post->post_status !== 'publish') {
                // Get all published glossary terms and find exact title match
                $all_terms = get_posts(array(
                    'post_type' => 'glossary',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                foreach ($all_terms as $glossary_term) {
                    if (strcasecmp($glossary_term->post_title, $term) === 0) {
                        $post = $glossary_term;
                        break;
                    }
                }
            }
        }

        if (!$post) {
            return $content ? do_shortcode($content) : esc_html($term);
        }

        $display_text = $content ? do_shortcode($content) : esc_html($post->post_title);
        return '<span class="advgls-term" data-term-id="' . esc_attr($post->ID) . '">' . $display_text . '</span>';
    }
    public function process_glossary_terms($content) {
        // Match spans with advgls-term class and data-term attribute from Gutenberg
        $pattern = '/<span class="advgls-term" data-term="([^"]+)">([^<]+)<\/span>/i';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $term_name = $matches[1];
            $text = $matches[2];
            $term_slug = sanitize_title($term_name);
            
            // Try to find the glossary post by slug first
            $post = get_page_by_path($term_slug, OBJECT, 'glossary');
            
            // If not found by slug, try searching by title
            if (!$post) {
                $args = array(
                    'post_type' => 'glossary',
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    's' => $term_name // Use search instead
                );
                
                $query = new WP_Query($args);
                
                if ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    wp_reset_postdata();
                    
                    return '<span class="advgls-term" data-term-id="' . esc_attr($post_id) . '">' . esc_html($text) . '</span>';
                }
            } else {
                return '<span class="advgls-term" data-term-id="' . esc_attr($post->ID) . '">' . esc_html($text) . '</span>';
            }
            
            // If no glossary term found, return the original text without the span
            return esc_html($text);
            }, $content);
            
        return $content;
    }
    /**
     * Activation Hook
     */
    public function activate() {
        $this->register_glossary_post_type();
        flush_rewrite_rules();
        // Clear any cached glossary terms
        delete_transient('advgls_terms');
    }
    
    /**
     * Clear glossary cache
     */
    public function clear_glossary_cache() {
        delete_transient('advgls_terms');
    }
    
    /**
     * Clear cache when glossary term is deleted
     */
    public function clear_glossary_cache_on_delete($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'glossary') {
            $this->clear_glossary_cache();
        }
    }
    
    /**
     * Deactivation Hook
     */
    public function deactivate() {
        flush_rewrite_rules();
        // Clear cached glossary terms
        $this->clear_glossary_cache();
    }
 
}

// Initialize plugin
Advgls_Glossary::get_instance();