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
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.2
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
if ( class_exists( 'CodeSoup\Options\Autoloader' ) )
{
	return;
}

// Load Composer autoloader if available.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

( function () {		
	$autoloader = new CodeSoup\Options\Autoloader( dirname( __FILE__ ) );
	$autoloader->register();
} )();