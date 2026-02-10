<?php
/**
 * Example 4: Debug Configuration
 *
 * This example shows how to control error logging using the debug flag.
 * By default, debug is set to false, which prevents all logging to error_log.
 *
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;

/**
 * Production Configuration (Default)
 *
 * With debug disabled, no errors, warnings, or info messages
 * will be written to the PHP error log.
 */
$manager = Manager::create(
	'production_settings',
	array(
		'menu_label' => 'Production Settings',
		'debug'      => false,
	)
);

$manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General',
		'capability' => 'manage_options',
	)
);

$manager->init();

/**
 * Development Configuration
 *
 * With debug enabled, errors, warnings, and info messages
 * will be logged to the PHP error log.
 */
$dev_manager = Manager::create(
	'dev_settings',
	array(
		'menu_label' => 'Dev Settings',
		'debug'      => true,
	)
);

$dev_manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General',
		'capability' => 'manage_options',
	)
);

$dev_manager->init();

/**
 * Conditional Debug Based on Environment
 *
 * Enable debug logging only in development environments.
 */
$conditional_manager = Manager::create(
	'conditional_settings',
	array(
		'menu_label' => 'Conditional Settings',
		'debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
	)
);

$conditional_manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General',
		'capability' => 'manage_options',
	)
);

$conditional_manager->init();

/**
 * What Gets Logged
 *
 * When debug is enabled, the following messages are logged:
 *
 * - ERROR: Critical issues like failed post creation, integration errors
 * - WARNING: Non-critical issues like missing integration classes, config warnings
 * - INFO: Informational messages like integration availability
 * - DEBUG: Debug messages (only when WP_DEBUG is also enabled)
 *
 * Log format: [instance_key] [LEVEL] message
 * Example: [production_settings] [ERROR] Failed to create page "general"
 */

/**
 * Disabling ACF Integration to Prevent Notices
 *
 * If you see ACF-related notices in your error log and want to disable them,
 * you can disable the ACF integration entirely:
 */
$no_acf_manager = Manager::create(
	'no_acf_settings',
	array(
		'menu_label'   => 'No ACF Settings',
		'debug'        => false,
		'integrations' => array(
			'acf' => array(
				'enabled' => false,
			),
		),
	)
);

$no_acf_manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General',
		'capability' => 'manage_options',
	)
);

$no_acf_manager->init();

/**
 * Best Practices
 *
 * 1. Keep debug disabled in production to reduce error log noise
 * 2. Enable debug during development to catch issues early
 * 3. Use conditional debug based on WP_DEBUG or environment variables
 * 4. Disable unused integrations to prevent unnecessary checks and notices
 */

