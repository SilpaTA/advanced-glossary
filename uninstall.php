<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Advgls_Glossary
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('advgls_auto_link');

// Delete transients
delete_transient('advgls_terms');

// Optionally, delete all glossary terms
// Uncomment the following lines if you want to delete all glossary terms on uninstall
/*
$glossary_terms = get_posts(array(
    'post_type' => 'glossary',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

foreach ($glossary_terms as $term) {
    wp_delete_post($term->ID, true);
}
*/
