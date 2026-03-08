<?php
/**
 * CSV Importer for Glossary Terms
 *
 * @package Advgls_Glossary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Advgls_CSV_Importer {
    
    private static $instance = null;
    private $max_file_size = 2097152; // 2MB
    private $allowed_mime_types = array('text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel');
    private $batch_size = 50;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_advgls_preview_csv', array($this, 'ajax_preview_csv'));
        add_action('wp_ajax_advgls_import_csv', array($this, 'ajax_import_csv'));
        add_action('wp_ajax_advgls_download_template', array($this, 'ajax_download_template'));
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'advgls-import-csv' ) {
            wp_enqueue_style(
                'advgls-csv-import',
                ADVGLS_URL . 'css/advgls-csv-import.css',
                array(),
                ADVGLS_VERSION
            );
            wp_enqueue_script(
                'advgls-csv-import',
                ADVGLS_URL . 'js/advgls-csv-import.js',
                array('jquery'),
                ADVGLS_VERSION,
                true
            );
            wp_localize_script('advgls-csv-import', 'advglsCSV', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('advgls_csv_import'),
                'uploading' => __('Uploading...', 'smart-glossary'),
                'parsing' => __('Parsing CSV...', 'smart-glossary'),
                'importing' => __('Importing terms...', 'smart-glossary'),
                'complete' => __('Import complete!', 'smart-glossary'),
                'error' => __('An error occurred', 'smart-glossary'),
                'total_rows' => __('Total rows:', 'smart-glossary'),
                'valid_rows' => __('Valid rows:', 'smart-glossary'),
                'invalid_rows' => __('Invalid rows:', 'smart-glossary'),
                'row' => __('Row', 'smart-glossary'),
                'term' => __('Term', 'smart-glossary'),
                'definition' => __('Definition', 'smart-glossary'),
                'status' => __('Status', 'smart-glossary'),
                'valid' => __('Valid', 'smart-glossary'),
                'invalid' => __('Invalid', 'smart-glossary'),
                'showing_first_20' => __('Showing first 20 rows', 'smart-glossary'),
                'validation_errors' => __('Validation Errors', 'smart-glossary'),
                'no_file' => __('No file uploaded', 'smart-glossary'),
                'processing' => __('Processing', 'smart-glossary'),
                'imported' => __('Imported', 'smart-glossary'),
                'updated' => __('Updated', 'smart-glossary'),
                'skipped' => __('Skipped', 'smart-glossary'),
                'failed' => __('Failed', 'smart-glossary'),
                'import_summary' => __('Import Summary', 'smart-glossary'),
                'errors' => __('Errors', 'smart-glossary'),
                'error' => __('Error', 'smart-glossary'),
                'view_glossary' => __('View Glossary Terms', 'smart-glossary'),
                'glossary_url' => admin_url('edit.php?post_type=glossary'),
                'upload_and_preview' => __('Upload and Preview', 'smart-glossary'),
            ));
        }
    }
    
    /**
     * Render import page (static method for menu callback)
     */
    public static function render_page() {
        $instance = self::get_instance();
        $instance->render_import_page();
    }
    
    /**
     * Render import page
     */
    private function render_import_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'smart-glossary'));
        }
        ?>
        <div class="wrap advgls-csv-import-wrap">
            <h1><?php echo esc_html__('Import Glossary Terms from CSV', 'smart-glossary'); ?></h1>
            
            <div class="advgls-import-container">
                <!-- Upload Section -->
                <div class="advgls-upload-section card">
                    <h2><?php esc_html_e('Step 1: Upload CSV File', 'smart-glossary'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Upload a CSV file containing glossary terms and their definitions.', 'smart-glossary'); ?>
                        <a href="#" id="advgls-download-template" class="button-link"><?php esc_html_e('Download CSV Template', 'smart-glossary'); ?></a>
                    </p>
                    
                    <form id="advgls-csv-upload-form" method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('advgls_csv_upload', 'advgls_csv_upload_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="csv_file"><?php esc_html_e('CSV File', 'smart-glossary'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required />
                                    <p class="description">
                                        <?php
                                        printf(
                                            /* translators: %s: Maximum file size */
                                            esc_html__('Maximum file size: %s', 'smart-glossary'),
                                            esc_html( size_format($this->max_file_size) )
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="csv_delimiter"><?php esc_html_e('Delimiter', 'smart-glossary'); ?></label>
                                </th>
                                <td>
                                    <select id="csv_delimiter" name="csv_delimiter">
                                        <option value=","><?php esc_html_e('Comma (,)', 'smart-glossary'); ?></option>
                                        <option value=";"><?php esc_html_e('Semicolon (;)', 'smart-glossary'); ?></option>
                                        <option value="\t"><?php esc_html_e('Tab', 'smart-glossary'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="advgls-upload-btn">
                                <?php esc_html_e('Upload and Preview', 'smart-glossary'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Preview Section -->
                <div class="advgls-preview-section card" id="advgls-preview-section" style="display: none;">
                    <h2><?php esc_html_e('Step 2: Preview and Configure', 'smart-glossary'); ?></h2>
                    <div id="advgls-preview-content"></div>
                    
                    <div class="advgls-import-options">
                        <h3><?php esc_html_e('Import Options', 'smart-glossary'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="duplicate_action"><?php esc_html_e('Duplicate Handling', 'smart-glossary'); ?></label>
                                </th>
                                <td>
                                    <select id="duplicate_action" name="duplicate_action">
                                        <option value="skip"><?php esc_html_e('Skip duplicates', 'smart-glossary'); ?></option>
                                        <option value="update"><?php esc_html_e('Update duplicates', 'smart-glossary'); ?></option>
                                        <option value="create"><?php esc_html_e('Create anyway', 'smart-glossary'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('How to handle terms that already exist.', 'smart-glossary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="import_status"><?php esc_html_e('Post Status', 'smart-glossary'); ?></label>
                                </th>
                                <td>
                                    <select id="import_status" name="import_status">
                                        <option value="publish"><?php esc_html_e('Published', 'smart-glossary'); ?></option>
                                        <option value="draft"><?php esc_html_e('Draft', 'smart-glossary'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="button" class="button button-primary" id="advgls-start-import-btn">
                                <?php esc_html_e('Start Import', 'smart-glossary'); ?>
                            </button>
                        </p>
                    </div>
                </div>
                
                <!-- Progress Section -->
                <div class="advgls-progress-section card" id="advgls-progress-section" style="display: none;">
                    <h2><?php esc_html_e('Step 3: Import Progress', 'smart-glossary'); ?></h2>
                    <div class="advgls-progress-bar">
                        <div class="advgls-progress-fill" id="advgls-progress-fill"></div>
                    </div>
                    <p class="advgls-progress-text" id="advgls-progress-text"></p>
                </div>
                
                <!-- Results Section -->
                <div class="advgls-results-section card" id="advgls-results-section" style="display: none;">
                    <h2><?php esc_html_e('Import Results', 'smart-glossary'); ?></h2>
                    <div id="advgls-results-content"></div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="advgls-instructions card">
                <h2><?php esc_html_e('CSV Format Instructions', 'smart-glossary'); ?></h2>
                <p><?php esc_html_e('Your CSV file should have the following structure:', 'smart-glossary'); ?></p>
                <ul>
                    <li><strong><?php esc_html_e('Required columns:', 'smart-glossary'); ?></strong>
                        <ul>
                            <li><code>term</code> <?php esc_html_e('or', 'smart-glossary'); ?> <code>title</code> - <?php esc_html_e('The glossary term name', 'smart-glossary'); ?></li>
                            <li><code>definition</code> <?php esc_html_e('or', 'smart-glossary'); ?> <code>description</code> - <?php esc_html_e('The tooltip definition', 'smart-glossary'); ?></li>
                        </ul>
                    </li>
                    <li><strong><?php esc_html_e('Optional columns:', 'smart-glossary'); ?></strong>
                        <ul>
                            <li><code>slug</code> - <?php esc_html_e('Custom post slug (auto-generated if not provided)', 'smart-glossary'); ?></li>
                            <li><code>status</code> - <?php esc_html_e('Post status: publish or draft (default: publish)', 'smart-glossary'); ?></li>
                        </ul>
                    </li>
                </ul>
                <p><strong><?php esc_html_e('Example CSV:', 'smart-glossary'); ?></strong></p>
                <pre><code>term,definition
"API","Application Programming Interface - a set of protocols"
"CSS","Cascading Style Sheets - used for styling web pages"
"HTML","HyperText Markup Language - the standard markup language"</code></pre>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle file upload
     */
    private function handle_file_upload() {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('File upload failed.', 'smart-glossary'));
        }
        
        $file = $_FILES['csv_file'];
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            return new WP_Error('file_too_large', __('File is too large.', 'smart-glossary'));
        }
        
        // Check file type
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file_type['ext'], array('csv', 'txt'))) {
            return new WP_Error('invalid_file_type', __('Invalid file type. Please upload a CSV file.', 'smart-glossary'));
        }
        
        // Use WordPress upload handler
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error']);
        }
        
        return $upload['file'];
    }
    
    /**
     * Parse CSV file
     */
    private function parse_csv_file($file_path, $delimiter = ',') {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('CSV file not found.', 'smart-glossary'));
        }
        
        $data = array();
        $headers = array();
        $row_number = 0;
        
        // Open file
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return new WP_Error('file_open_error', __('Could not open CSV file.', 'smart-glossary'));
        }
        
        // Detect encoding and remove BOM if present
        $content = file_get_contents($file_path);
        
        // Remove UTF-8 BOM if present
        if (substr($content, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
            $content = substr($content, 3);
            file_put_contents($file_path, $content);
            // Reopen file after removing BOM
            fclose($handle);
            $handle = fopen($file_path, 'r');
        }
        
        $encoding = mb_detect_encoding($content, 'UTF-8, ISO-8859-1, Windows-1252', true);
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            file_put_contents($file_path, $content);
            // Reopen file after encoding conversion
            fclose($handle);
            $handle = fopen($file_path, 'r');
            // Skip BOM again if encoding conversion added it
            if (substr($content, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
                fseek($handle, 3);
            }
        }
        
        // Read headers
        $header_row = fgetcsv($handle, 0, $delimiter);
        if ($header_row === false) {
            fclose($handle);
            return new WP_Error('invalid_csv', __('Invalid CSV format.', 'smart-glossary'));
        }
        
        // Normalize headers - remove any BOM characters and trim
        $headers = array_map(function($header) {
            // Remove BOM and other invisible characters
            $header = preg_replace('/\x{FEFF}/u', '', $header);
            return trim($header);
        }, $header_row);
        $headers = array_map('strtolower', $headers);
        
        // Validate required columns
        $has_term = in_array('term', $headers) || in_array('title', $headers);
        $has_definition = in_array('definition', $headers) || in_array('description', $headers);
        
        if (!$has_term || !$has_definition) {
            fclose($handle);
            return new WP_Error('missing_columns', __('CSV must contain "term" (or "title") and "definition" (or "description") columns.', 'smart-glossary'));
        }
        
        // Read data rows
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row_number++;
            
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows
            }
            
            $row_data = array_combine($headers, $row);
            $row_data['_row_number'] = $row_number;
            $data[] = $row_data;
        }
        
        fclose($handle);
        
        return array(
            'headers' => $headers,
            'data' => $data,
            'total_rows' => $row_number
        );
    }
    
    /**
     * Validate CSV data
     */
    private function validate_csv_data($data) {
        $errors = array();
        $validated = array();
        
        foreach ($data as $row) {
            $row_errors = array();
            
            // Get term name
            $term = isset($row['term']) ? trim($row['term']) : (isset($row['title']) ? trim($row['title']) : '');
            if (empty($term)) {
                $row_errors[] = __('Term name is required.', 'smart-glossary');
            } elseif (strlen($term) > 200) {
                $row_errors[] = __('Term name is too long (max 200 characters).', 'smart-glossary');
            }
            
            // Get definition
            $definition = isset($row['definition']) ? trim($row['definition']) : (isset($row['description']) ? trim($row['description']) : '');
            if (empty($definition)) {
                $row_errors[] = __('Definition is required.', 'smart-glossary');
            }
            
            $row['_valid'] = empty($row_errors);
            $row['_errors'] = $row_errors;
            $row['_term'] = $term;
            $row['_definition'] = $definition;
            
            $validated[] = $row;
            
            if (!empty($row_errors)) {
                $errors[] = array(
                    'row' => $row['_row_number'],
                    'errors' => $row_errors
                );
            }
        }
        
        return array(
            'data' => $validated,
            'errors' => $errors
        );
    }
    
    /**
     * Check for duplicate terms
     */
    private function check_duplicates($term_name) {
        // Get all glossary terms and find exact title match
        $all_terms = get_posts(array(
            'post_type' => 'glossary',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($all_terms as $term) {
            if (strcasecmp($term->post_title, $term_name) === 0) {
                return $term->ID;
            }
        }
        
        return false;
    }
    
    /**
     * AJAX: Preview CSV
     */
    public function ajax_preview_csv() {
        check_ajax_referer('advgls_csv_import', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'smart-glossary'));
        }
        
        // Verify nonce for upload
        if (!isset($_POST['advgls_csv_upload_nonce']) || !wp_verify_nonce($_POST['advgls_csv_upload_nonce'], 'advgls_csv_upload')) {
            wp_send_json_error(__('Security check failed.', 'smart-glossary'));
        }
        
        $delimiter = isset($_POST['csv_delimiter']) ? sanitize_text_field($_POST['csv_delimiter']) : ',';
        
        // Handle file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No file uploaded or upload error occurred.', 'smart-glossary'));
        }
        
        $file_path = $this->handle_file_upload();
        if (is_wp_error($file_path)) {
            wp_send_json_error($file_path->get_error_message());
        }
        
        // Parse CSV
        $parsed = $this->parse_csv_file($file_path, $delimiter);
        if (is_wp_error($parsed)) {
            // Clean up uploaded file
            wp_delete_file($file_path);
            wp_send_json_error($parsed->get_error_message());
        }
        
        // Validate data
        $validated = $this->validate_csv_data($parsed['data']);
        
        // Store file path in transient for import
        $upload_key = 'advgls_csv_' . wp_generate_password(12, false);
        set_transient($upload_key, $file_path, HOUR_IN_SECONDS);
        set_transient($upload_key . '_delimiter', $delimiter, HOUR_IN_SECONDS);
        
        // Prepare preview data
        $preview_data = array_slice($validated['data'], 0, 20); // Show first 20 rows
        
        wp_send_json_success(array(
            'upload_key' => $upload_key,
            'total_rows' => count($validated['data']),
            'valid_rows' => count(array_filter($validated['data'], function($row) { return $row['_valid']; })),
            'invalid_rows' => count($validated['errors']),
            'preview' => $preview_data,
            'errors' => $validated['errors'],
            'headers' => $parsed['headers']
        ));
    }
    
    /**
     * AJAX: Import CSV
     */
    public function ajax_import_csv() {
        check_ajax_referer('advgls_csv_import', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'smart-glossary'));
        }
        
        $upload_key = isset($_POST['upload_key']) ? sanitize_text_field($_POST['upload_key']) : '';
        $duplicate_action = isset($_POST['duplicate_action']) ? sanitize_text_field($_POST['duplicate_action']) : 'skip';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish';
        $batch = isset($_POST['batch']) ? absint($_POST['batch']) : 0;
        
        // Get file path from transient
        $file_path = get_transient($upload_key);
        $delimiter = get_transient($upload_key . '_delimiter');
        
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(__('CSV file not found. Please upload again.', 'smart-glossary'));
        }
        
        // Parse CSV (if first batch)
        if ($batch === 0) {
            $parsed = $this->parse_csv_file($file_path, $delimiter);
            if (is_wp_error($parsed)) {
                wp_send_json_error($parsed->get_error_message());
            }
            
            $validated = $this->validate_csv_data($parsed['data']);
            set_transient($upload_key . '_data', $validated['data'], HOUR_IN_SECONDS);
            set_transient($upload_key . '_results', array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => array()), HOUR_IN_SECONDS);
        }
        
        // Get data from transient
        $data = get_transient($upload_key . '_data');
        $results = get_transient($upload_key . '_results');
        
        if (!$data) {
            wp_send_json_error(__('Import data not found.', 'smart-glossary'));
        }
        
        // Process batch
        $offset = $batch * $this->batch_size;
        $batch_data = array_slice($data, $offset, $this->batch_size);
        
        if (empty($batch_data)) {
            // Import complete - clean up
            $file_path = get_transient($upload_key);
            if ($file_path && file_exists($file_path)) {
                wp_delete_file($file_path);// Delete temporary file
            }
            
            delete_transient($upload_key);
            delete_transient($upload_key . '_delimiter');
            delete_transient($upload_key . '_data');
            delete_transient($upload_key . '_results');
            
            // Clear cache
            delete_transient('advgls_terms');
            
            wp_send_json_success(array(
                'complete' => true,
                'results' => $results
            ));
        }
        
        // Process each row in batch
        foreach ($batch_data as $row) {
            if (!$row['_valid']) {
                $results['failed']++;
                $results['errors'][] = array(
                    'row' => $row['_row_number'],
                    'term' => $row['_term'],
                    'error' => implode(', ', $row['_errors'])
                );
                continue;
            }
            
            $term_name = $row['_term'];
            $definition = $row['_definition'];
            $slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
            $row_status = isset($row['status']) ? sanitize_text_field($row['status']) : $status;
            
            // Check for duplicates
            $existing_id = $this->check_duplicates($term_name);
            
            if ($existing_id && $duplicate_action === 'skip') {
                $results['skipped']++;
                continue;
            }
            
            // Prepare post data
            $post_data = array(
                'post_title' => sanitize_text_field($term_name),
                'post_type' => 'glossary',
                'post_status' => in_array($row_status, array('publish', 'draft')) ? $row_status : 'publish',
            );
            
            if (!empty($slug)) {
                $post_data['post_name'] = $slug;
            }
            
            if ($existing_id && $duplicate_action === 'update') {
                $post_data['ID'] = $existing_id;
                $post_id = wp_update_post($post_data);
                if (!is_wp_error($post_id)) {
                    update_post_meta($post_id, '_glossary_description', sanitize_textarea_field($definition));
                    $results['updated']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = array(
                        'row' => $row['_row_number'],
                        'term' => $term_name,
                        'error' => $post_id->get_error_message()
                    );
                }
            } else {
                $post_id = wp_insert_post($post_data);
                if (!is_wp_error($post_id) && $post_id > 0) {
                    update_post_meta($post_id, '_glossary_description', sanitize_textarea_field($definition));
                    $results['imported']++;
                } else {
                    $results['failed']++;
                    $error_msg = is_wp_error($post_id) ? $post_id->get_error_message() : __('Failed to create post.', 'smart-glossary');
                    $results['errors'][] = array(
                        'row' => $row['_row_number'],
                        'term' => $term_name,
                        'error' => $error_msg
                    );
                }
            }
        }
        
        // Save results
        set_transient($upload_key . '_results', $results, HOUR_IN_SECONDS);
        
        // Clear cache after batch
        delete_transient('advgls_terms');
        
        wp_send_json_success(array(
            'complete' => false,
            'batch' => $batch + 1,
            'processed' => $offset + count($batch_data),
            'total' => count($data),
            'results' => $results
        ));
    }
    
    /**
     * AJAX: Download CSV template
     */
    public function ajax_download_template() {
        check_ajax_referer('advgls_csv_import', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'smart-glossary'));
        }
        
        $filename = 'glossary-import-template.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Headers - write directly without BOM to avoid parsing issues
        fputcsv($output, array('term', 'definition'), ',');
        
        // Example rows
        fputcsv($output, array('API', 'Application Programming Interface - a set of protocols for building software'), ',');
        fputcsv($output, array('CSS', 'Cascading Style Sheets - used for styling web pages'), ',');
        fputcsv($output, array('HTML', 'HyperText Markup Language - the standard markup language for web pages'), ',');
        
        fclose($output);
        exit;
    }
}

// Initialize
Advgls_CSV_Importer::get_instance();
