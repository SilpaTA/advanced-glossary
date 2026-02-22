# CSV Import Feature - Implementation Plan

## Overview
Add functionality to import glossary terms from CSV files, allowing bulk creation of terms with their definitions.

## Feature Requirements

### 1. User Interface
- **Location**: New submenu under Glossary menu: "Import from CSV"
- **Page Slug**: `advgls-import-csv`
- **Access Control**: `manage_options` capability
- **Components**:
  - File upload form
  - CSV format instructions/example
  - Preview table (before import)
  - Import progress indicator
  - Results summary (success/failed counts)
  - Error log display

### 2. CSV File Format

#### Required Columns
- `term` or `title` - The glossary term name (required)
- `definition` or `description` - The tooltip definition (required)

#### Optional Columns
- `slug` - Custom post slug (auto-generated if not provided)
- `status` - Post status (default: 'publish')
- `id` - Existing term ID for updates (if updating existing terms)

#### CSV Format Options
- **Delimiter**: Comma (`,`), semicolon (`;`), or tab
- **Encoding**: UTF-8 (auto-detect)
- **Header Row**: First row contains column names
- **Quotes**: Support quoted fields with commas

#### Example CSV:
```csv
term,definition
"API","Application Programming Interface - a set of protocols for building software"
"CSS","Cascading Style Sheets - used for styling web pages"
"HTML","HyperText Markup Language - the standard markup language for web pages"
```

### 3. Technical Implementation

#### File Structure
```
inc/
  └── class-advgls-csv-importer.php  (New class)
js/
  └── advgls-csv-import.js           (New JavaScript for AJAX import)
css/
  └── advgls-csv-import.css         (Optional styling)
```

#### Class: `Advgls_CSV_Importer`

**Methods:**
1. `init()` - Initialize hooks
2. `enqueue_assets($hook)` - Load CSS/JS on import page
3. `render_page()` - Display import interface
4. `handle_file_upload()` - Process uploaded CSV file
5. `parse_csv_file($file_path)` - Parse CSV into array
6. `validate_csv_data($data)` - Validate CSV structure and data
7. `preview_import($data)` - Show preview before import
8. `process_import($data, $options)` - Execute import
9. `import_term($row, $options)` - Import single term
10. `ajax_import_csv()` - AJAX handler for import
11. `ajax_preview_csv()` - AJAX handler for preview
12. `get_csv_template()` - Generate/download CSV template

**Properties:**
- `$max_file_size` - Maximum upload size (default: 2MB)
- `$allowed_mime_types` - Allowed file types
- `$batch_size` - Number of terms to process per batch

#### Import Process Flow

1. **Upload Phase**
   - User uploads CSV file
   - Validate file type and size
   - Store temporarily in WordPress uploads directory
   - Return file path or error

2. **Preview Phase**
   - Parse CSV file
   - Validate data structure
   - Display preview table with:
     - Row numbers
     - Term names
     - Definitions (truncated)
     - Validation status (valid/invalid)
     - Duplicate detection
   - Show import options:
     - Skip duplicates / Update duplicates
     - Post status (publish/draft)
     - Batch size

3. **Import Phase** (AJAX-based for large files)
   - Process in batches (e.g., 50 terms per batch)
   - For each row:
     - Validate term name and definition
     - Check for duplicates (if option selected)
     - Create/update post:
       ```php
       $post_data = array(
           'post_title' => sanitize_text_field($term),
           'post_type' => 'glossary',
           'post_status' => $status,
           'post_name' => $slug // if provided
       );
       $post_id = wp_insert_post($post_data);
       update_post_meta($post_id, '_glossary_description', sanitize_textarea_field($definition));
       ```
     - Clear cache after each batch
   - Return progress updates via AJAX
   - Display results summary

4. **Results Phase**
   - Show summary:
     - Total rows processed
     - Successfully imported
     - Failed imports
     - Skipped (duplicates)
   - Display error log for failed rows
   - Option to download error report

### 4. Security Considerations

- **File Upload Security**:
  - Verify file type (MIME type check)
  - Limit file size
  - Sanitize file name
  - Store in secure location
  - Delete temporary files after import

- **Data Validation**:
  - Sanitize all input (`sanitize_text_field`, `sanitize_textarea_field`)
  - Validate term names (non-empty, reasonable length)
  - Validate definitions (non-empty)
  - Check user capabilities

- **Nonce Verification**:
  - Add nonce to upload form
  - Verify nonce in AJAX handlers

- **CSV Injection Prevention**:
  - Strip dangerous characters
  - Validate cell content
  - Escape special characters

### 5. Error Handling

#### Validation Errors
- Missing required columns
- Empty term names
- Empty definitions
- Invalid file format
- File too large
- Encoding issues

#### Import Errors
- Duplicate term names (if skip duplicates enabled)
- Database errors
- Memory limit exceeded (for large files)
- Timeout issues

#### Error Reporting
- Per-row error messages
- Error log file (optional)
- Display errors in UI
- Export error report as CSV

### 6. Duplicate Handling

**Options:**
1. **Skip Duplicates** (default)
   - Check if term exists by title
   - Skip row if duplicate found
   - Log skipped rows

2. **Update Duplicates**
   - Find existing term by title
   - Update definition if different
   - Log updated rows

3. **Create Anyway**
   - Allow duplicate term names
   - WordPress will auto-append numbers to slugs

### 7. Performance Considerations

- **Batch Processing**: Process in chunks (50-100 rows per batch)
- **AJAX Progress**: Show progress bar and status updates
- **Memory Management**: 
  - Use `wp_suspend_cache_addition()` for large imports
  - Clear object cache periodically
- **Timeout Handling**: 
  - Increase execution time for large files
  - Use background processing (WP-Cron) for very large files (optional)

### 8. User Experience Features

- **CSV Template Download**: Provide downloadable template CSV
- **Drag & Drop Upload**: Modern file upload interface
- **Real-time Preview**: Show preview immediately after upload
- **Progress Indicator**: Visual progress bar during import
- **Success/Error Notifications**: Clear feedback messages
- **Import History**: Log recent imports (optional)
- **Undo Import**: Ability to delete imported terms (optional)

### 9. Integration Points

#### Hooks to Add
```php
// Before import starts
do_action('advgls_before_csv_import', $data, $options);

// After each term is imported
do_action('advgls_after_term_imported', $post_id, $row_data);

// After import completes
do_action('advgls_after_csv_import', $results);

// Filter to modify term data before import
apply_filters('advgls_csv_term_data', $term_data, $row);

// Filter to modify import options
apply_filters('advgls_csv_import_options', $options);
```

### 10. Testing Checklist

- [ ] Upload valid CSV file
- [ ] Upload invalid file types (reject)
- [ ] Upload oversized file (reject)
- [ ] CSV with missing columns (error)
- [ ] CSV with empty required fields (error)
- [ ] CSV with special characters (handle correctly)
- [ ] CSV with duplicate terms (handle per option)
- [ ] Large CSV file (1000+ rows) - batch processing
- [ ] CSV with different delimiters
- [ ] CSV with quoted fields containing commas
- [ ] Import with "skip duplicates" option
- [ ] Import with "update duplicates" option
- [ ] Preview functionality
- [ ] Error reporting
- [ ] Cache clearing after import
- [ ] Permission checks (non-admin users)

### 11. Implementation Steps

#### Phase 1: Basic Structure
1. Create `class-advgls-csv-importer.php`
2. Add submenu page in main plugin file
3. Create basic upload form
4. Implement file upload handling

#### Phase 2: CSV Parsing
1. Implement CSV parser (use `fgetcsv()` or `str_getcsv()`)
2. Handle different delimiters
3. Detect encoding
4. Validate CSV structure

#### Phase 3: Preview & Validation
1. Build preview table
2. Implement data validation
3. Show validation errors
4. Duplicate detection

#### Phase 4: Import Functionality
1. Implement batch processing
2. Create AJAX handlers
3. Add progress indicator
4. Implement term creation/update

#### Phase 5: Error Handling & Polish
1. Comprehensive error handling
2. Results summary
3. Error log export
4. UI/UX improvements
5. Internationalization

#### Phase 6: Testing & Documentation
1. Unit testing
2. Integration testing
3. User documentation
4. Update README

### 12. Code Structure Example

```php
class Advgls_CSV_Importer {
    private $max_file_size = 2097152; // 2MB
    private $allowed_mime_types = array('text/csv', 'text/plain', 'application/csv');
    private $batch_size = 50;
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_ajax_advgls_preview_csv', array(__CLASS__, 'ajax_preview_csv'));
        add_action('wp_ajax_advgls_import_csv', array(__CLASS__, 'ajax_import_csv'));
    }
    
    // ... implementation methods
}
```

### 13. Dependencies

- **WordPress Functions**:
  - `wp_handle_upload()` - File upload handling
  - `wp_insert_post()` - Create glossary terms
  - `update_post_meta()` - Save definitions
  - `get_posts()` - Check for duplicates
  - `wp_send_json_success()` / `wp_send_json_error()` - AJAX responses

- **PHP Functions**:
  - `fgetcsv()` - Parse CSV
  - `mb_detect_encoding()` - Detect encoding
  - `sanitize_text_field()` - Sanitize input

### 14. Future Enhancements (Optional)

- Export functionality (CSV export of existing terms)
- Scheduled imports (via WP-Cron)
- Import from URL
- Support for additional fields (categories, tags, custom meta)
- Import history and rollback
- Mapping interface for custom CSV formats
- Support for Excel files (.xlsx)

## Estimated Development Time

- Basic import: 8-12 hours
- Preview & validation: 4-6 hours
- Error handling & polish: 4-6 hours
- Testing & documentation: 4-6 hours
- **Total: 20-30 hours**

## Notes

- Follow WordPress coding standards
- Ensure all strings are translatable
- Use proper nonces and capability checks
- Test with various CSV formats and edge cases
- Consider performance for large imports (1000+ rows)
- Provide clear user feedback at each step
