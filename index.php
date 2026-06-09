<?php
/**
 * Plugin bootstrap file (optional).
 *
 * This file is only needed if you want to use this package as a standalone WordPress plugin.
 * When using as a Composer package, you don't need this file - just use the Manager class directly.
 *
 * @package CodeSoup\Options
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

/**
 * Plugin Name:       CodeSoup Options
 * Plugin URI:        https://www.codesoup.co
 * Description:       Framework-agnostic WordPress options manager using custom post types to store data. Supports multiple instances, ACF, custom metaboxes, and extensible integrations.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Code Soup
 * Author URI:        https://www.codesoup.co
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:       codesoup-options
 * Domain Path:       /languages
 */


/**
 * Prevent in case multiple instances of package exist.
 */
if ( class_exists( 'CodeSoup\Options\Autoloader' ) ) {
	return;
}

// Load Composer autoloader if available.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

( function () {
	$autoloader = new CodeSoup\Options\Autoloader( __DIR__ );
	$autoloader->register();
} )();

/**
 * Clear capability flags when permalinks are flushed
 *
 * This ensures capabilities are re-checked and added if needed
 * after permalink structure changes or plugin reactivation.
 */

// Run late to ensure all other plugins have loaded.
define( 'CODESOUP_OPTIONS_CAPABILITY_CHECK_PRIORITY', 9999 );

add_action(
	'init',
	function () {
		// Register hook to clear capability flags.
		// This runs after flush_rewrite_rules() is called.
		add_action(
			'wp_loaded',
			function () {
				static $checked = false;

				if ( $checked ) {
					return;
				}

				$checked = true;

				// Check if a flush marker exists.
				$flush_marker = get_option( 'codesoup_options_flush_caps', false );

				if ( $flush_marker ) {
					CodeSoup\Options\Post_Manager::clear_all_capability_flags();
					delete_option( 'codesoup_options_flush_caps' );
				}
			},
			CODESOUP_OPTIONS_CAPABILITY_CHECK_PRIORITY
		);
	}
);
