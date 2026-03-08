<?php
/**
 * Back-compat shim.
 *
 * This file intentionally does NOT contain a WordPress plugin header, to avoid
 * WordPress listing multiple plugins from the same folder.
 *
 * @package Smart_Glossary
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/smart-glossary.php';

