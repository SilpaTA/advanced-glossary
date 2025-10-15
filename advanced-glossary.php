<?php
/**
 * Plugin Name: Advanced Glossary
 * Description: Create glossary terms with hover tooltips. Add glossary terms as custom post type and display definitions on hover.
 * Version: 1.0.0
 * Author: Silpa T A
 * License: GPL v2 or later
 * Text Domain: advanced-glossary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADVANCED_GLOSSARY_VERSION', '1.0.0');
define('ADVANCED_GLOSSARY_PATH', plugin_dir_path(__FILE__));
define('ADVANCED_GLOSSARY_URL', plugin_dir_url(__FILE__));

class Advanced_Glossary {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Load dependencies
        $this->load_dependencies();
        
        // Register custom post type
        add_action('init', array($this, 'register_glossary_post_type'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_glossary_meta_boxes'));
        add_action('save_post', array($this, 'save_glossary_meta'));
        
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
        
        // Auto-link glossary terms in content
        add_filter('the_content', array($this, 'auto_link_glossary_terms'), 999);
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register shortcode
        add_shortcode('glossary', array($this, 'glossary_shortcode_handler'));
        add_filter('the_content', array($this, 'process_glossary_terms'), 10);


        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ADVANCED_GLOSSARY_PATH . 'inc/class-glossary-settings.php';
        require_once ADVANCED_GLOSSARY_PATH . 'inc/class-glossary-shortcode-generator.php';
    }
    
    /**
     * Register Glossary Custom Post Type
     */
    public function register_glossary_post_type() {
        $labels = array(
            'name'                  => 'Glossary Terms',
            'singular_name'         => 'Glossary Term',
            'menu_name'             => 'Glossary',
            'add_new'               => 'Add New Term',
            'add_new_item'          => 'Add New Glossary Term',
            'edit_item'             => 'Edit Glossary Term',
            'new_item'              => 'New Glossary Term',
            'view_item'             => 'View Glossary Term',
            'search_items'          => 'Search Glossary',
            'not_found'             => 'No glossary terms found',
            'not_found_in_trash'    => 'No glossary terms found in trash',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,  
            'show_ui'               => true,   
            'has_archive'           => false,  
            'publicly_queryable'    => false,  
            'show_in_menu'          => false,
            'show_in_rest'          => true,
            'menu_icon'             => 'dashicons-book',
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
            'Glossary Definition',
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
            <label for="glossary_description"><strong>Short Definition (for tooltip):</strong></label><br>
            <textarea id="glossary_description" name="glossary_description" rows="4" style="width:100%;"><?php echo esc_textarea($description); ?></textarea>
            <span class="description">This brief definition will appear in the hover tooltip.</span>
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
            'glossary-block',
            ADVANCED_GLOSSARY_URL . 'js/glossary-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
            ADVANCED_GLOSSARY_VERSION,
            true
        );
        
        register_block_type('advanced-glossary/term', array(
            'editor_script' => 'glossary-block',
        ));
    }
    
    /**
     * Enqueue Frontend Assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style('glossary-style', ADVANCED_GLOSSARY_URL . 'css/glossary-style.css', array(), ADVANCED_GLOSSARY_VERSION);
        wp_enqueue_script('glossary-script', ADVANCED_GLOSSARY_URL . 'js/glossary-script.js', array('jquery'), ADVANCED_GLOSSARY_VERSION, true);
        
        wp_localize_script('glossary-script', 'glossaryAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glossary_nonce')
        ));
    }
    
    /**
     * Enqueue Admin Assets
     */
    public function enqueue_admin_assets($hook) {
        // Enqueue on post edit pages
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('glossary-admin', ADVANCED_GLOSSARY_URL . 'js/glossary-admin.js', array('jquery'), ADVANCED_GLOSSARY_VERSION, true);
            wp_localize_script('glossary-admin', 'glossaryAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('glossary_nonce')
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
        $plugin_array['glossary_button'] = ADVANCED_GLOSSARY_URL . 'js/glossary-tinymce.js';
        return $plugin_array;
    }
    
    public function register_tinymce_button($buttons) {
        array_push($buttons, 'glossary_button');
        return $buttons;
    }
    
    /**
     * AJAX: Get Glossary Terms
     */
    public function ajax_get_glossary_terms() {
        check_ajax_referer('glossary_nonce', 'nonce');
        
        $args = array(
            'post_type' => 'glossary',
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
        
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        
        if (!$term_id) {
            wp_send_json_error('Invalid term ID');
        }
        
        $term = get_post($term_id);
        
        if (!$term || $term->post_type !== 'glossary') {
            wp_send_json_error('Term not found');
        }
        
        $description = get_post_meta($term_id, '_glossary_description', true);
        wp_send_json_success(array(
            'title' => $term->post_title,
            'description' => $description ? $description : 'No description available.',
            'link' => get_permalink($term_id),
        ));
    }

    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'glossary-format',
            ADVANCED_GLOSSARY_URL . 'js/glossary-format.js',
            array('wp-rich-text', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch', 'wp-data'),
            ADVANCED_GLOSSARY_VERSION,
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
        $auto_link_enabled = get_option('advanced_glossary_auto_link', 1);
        if (!$auto_link_enabled) {
            return $content;
        }

        $args = array(
            'post_type' => 'glossary',
            'posts_per_page' => -1,
        );

        $glossary_terms = get_posts($args);

        foreach ($glossary_terms as $term) {
            $word = preg_quote($term->post_title, '/');
            $pattern = '/\b(' . $word . ')\b/i';
            $replacement = '<span class="glossary-term" data-term-id="' . $term->ID . '">$1</span>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        // Main menu pointing directly to glossary terms
        add_menu_page(
            'Glossary',
            'Glossary',
            'manage_options',
            'edit.php?post_type=glossary',
            '',
            'dashicons-book',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'edit.php?post_type=glossary',
            'Glossary Settings',
            'Settings',
            'manage_options',
            'glossary-settings',
            array('Glossary_Settings', 'render_page')
        );
        
        // Shortcode Generator submenu
        add_submenu_page(
            'edit.php?post_type=glossary',
            'Shortcode Generator',
            'Shortcode Generator',
            'manage_options',
            'glossary-shortcode-generator',
            array('Glossary_Shortcode_Generator', 'render_page')
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

        $term = $atts['term'];
        $id = $atts['id'];

        if ($id) {
            $post = get_post($id);
            if ($post) {
                $term = $post->post_title;
            }
        } else {
            $query = new WP_Query(array(
                'post_type' => 'glossary',
                'title' => $term,
                'posts_per_page' => 1
            ));

            $post = $query->have_posts() ? $query->next_post() : null;
        }

        if (!$post) {
            return $content ? $content : esc_html($term);
        }

        return '<span class="glossary-term" data-term-id="' . esc_attr($post->ID) . '">' . ($content ? esc_html($content) : esc_html($term)) . '</span>';
    }
    public function process_glossary_terms($content) {
    // Match spans with glossary-term class and data-term attribute from Gutenberg
    $pattern = '/<span class="glossary-term" data-term="([^"]+)">([^<]+)<\/span>/i';
    
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
                
                return '<span class="glossary-term" data-term-id="' . esc_attr($post_id) . '">' . esc_html($text) . '</span>';
            }
        } else {
            return '<span class="glossary-term" data-term-id="' . esc_attr($post->ID) . '">' . esc_html($text) . '</span>';
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
    }
    
    /**
     * Deactivation Hook
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize plugin
Advanced_Glossary::get_instance();