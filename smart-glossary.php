<?php
/**
 * Plugin Name: Smart Glossary for WordPress
 * Plugin URI: https://wordpress.org/plugins/smart-glossary
 * Description: Create glossary terms with hover tooltips. Add glossary terms as custom post type and display definitions on hover.
 * Version: 1.0.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Silpa T A
 * Author URI: https://profiles.wordpress.org/shilpaashokan94/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-glossary
 * Domain Path: /languages
 *
 * @package Smart_Glossary
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin file path (used for i18n loading).
 */
define( 'ADVGLS_MAIN_FILE', __FILE__ );

// Define plugin constants (advgls prefix for avoiding collisions).
define( 'ADVGLS_VERSION', '1.0.1' );
define( 'ADVGLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADVGLS_URL', plugin_dir_url( __FILE__ ) );

require_once ADVGLS_PATH . 'inc/class-advgls-glossary.php';

/**
 * Activation hook callback.
 *
 * @return void
 */
function advgls_activate() {
	Advgls_Glossary::get_instance()->activate();
}

/**
 * Deactivation hook callback.
 *
 * @return void
 */
function advgls_deactivate() {
	Advgls_Glossary::get_instance()->deactivate();
}

register_activation_hook( __FILE__, 'advgls_activate' );
register_deactivation_hook( __FILE__, 'advgls_deactivate' );

// Initialize plugin.
Advgls_Glossary::get_instance();

