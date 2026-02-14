<?php
/**
 * Example 2: ACF Integration
 *
 * This example shows how to use CodeSoup Options with Advanced Custom Fields.
 * ACF integration is enabled by default, so you just need to create field groups
 * and assign them using the "CodeSoup Options" location rule.
 *
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;

// Create a manager instance (ACF integration is enabled by default).
$manager = Manager::create(
	'acf_settings',
	array(
		'menu_label'    => 'Theme Settings',
		'menu_icon'     => 'dashicons-admin-appearance',
		'menu_position' => 60,
	)
);

// Register options pages.
$manager->register_pages(
	array(
		array(
			'id'          => 'general',
			'title'       => 'General',
			'capability'  => 'manage_options',
			'description' => 'General theme settings',
		),
		array(
			'id'          => 'header',
			'title'       => 'Header',
			'capability'  => 'manage_options',
			'description' => 'Header configuration',
		),
		array(
			'id'          => 'footer',
			'title'       => 'Footer',
			'capability'  => 'manage_options',
			'description' => 'Footer configuration',
		),
		array(
			'id'          => 'social',
			'title'       => 'Social Media',
			'capability'  => 'manage_options',
			'description' => 'Social media links',
		),
	)
);

// Initialize the manager.
$manager->init();

/**
 * How to assign ACF field groups:
 *
 * 1. Create or edit an ACF field group in WordPress admin
 * 2. In the Location Rules section, select:
 *    - "Show this field group if" → "CodeSoup Options" → "is equal to" → Select page ID
 *    - For example: "CodeSoup Options" → "is equal to" → "general"
 * 3. Add your fields to the field group
 * 4. The field group will now appear on the selected options page
 */

// ============================================================================
// Retrieving Options
// ============================================================================

// Get all options for a page (returns unserialized array from post_content).
$manager         = Manager::get( 'acf_settings' );
$general_options = $manager->get_options( 'general' );

// Access values from the array.
$site_logo    = $general_options['site_logo'] ?? '';
$site_tagline = $general_options['site_tagline'] ?? '';

// Or get a single field value directly from postmeta (uses ACF's get_field()).
$site_logo = $manager->get_option( 'general', 'site_logo' );

// With default value.
$footer_text = $manager->get_option( 'footer', 'copyright_text', '© 2024 My Site' );

// ============================================================================
// Using in Templates
// ============================================================================

/**
 * In your theme templates (header.php, footer.php, etc.):
 */

// Get the manager instance.
$settings = Manager::get( 'acf_settings' );

// Display logo.
$logo_id = $settings->get_option( 'header', 'logo' );
if ( $logo_id ) {
	echo wp_get_attachment_image( $logo_id, 'full' );
}

// Display social links.
$social_links = $settings->get_options( 'social' );
if ( ! empty( $social_links['facebook'] ) ) {
	printf(
		'<a href="%s" target="_blank">Facebook</a>',
		esc_url( $social_links['facebook'] )
	);
}

// Display footer copyright.
$copyright = $settings->get_option( 'footer', 'copyright_text', '© ' . gmdate( 'Y' ) );
echo esc_html( $copyright );

// ============================================================================
// Combining ACF with Custom Metaboxes
// ============================================================================

// You can also add custom metaboxes alongside ACF field groups.
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Custom Settings',
		'path'  => __DIR__ . '/templates/custom-settings.php',
	)
);

/**
 * Data Storage:
 *
 * ACF integration uses dual storage:
 * - Individual fields are stored in postmeta (for ACF compatibility)
 * - All fields are also serialized and stored in post_content (for fast retrieval)
 *
 * This allows you to:
 * - Use ACF's get_field() functions
 * - Use Manager's get_options() for fast bulk retrieval
 * - Maintain ACF field validation and formatting
 */

/**
 * Disabling ACF Integration:
 *
 * If you want to disable ACF for a specific instance:
 */
$manager = Manager::create(
	'no_acf',
	array(
		'integrations' => array(
			'acf' => array(
				'enabled' => false,
			),
		),
	)
);
