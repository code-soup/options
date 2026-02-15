<?php
/**
 * Example 1: Basic Usage - Options Pages Without Any Integration
 *
 * This example shows how to use CodeSoup Options without any field framework.
 * You can use native WordPress metaboxes or custom HTML templates.
 *
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;

// Create a manager instance with ACF integration disabled.
$manager = Manager::create(
	'basic_settings',
	array(
		'menu_label'    => 'Site Settings',
		'menu_icon'     => 'dashicons-admin-settings',
		'menu_position' => 50,
		'integrations'  => array(
			'acf' => array(
				'enabled' => false, // Disable ACF integration.
			),
		),
	)
);

// Register options pages.
$manager->register_pages(
	array(
		array(
			'id'          => 'general',
			'title'       => 'General Settings',
			'capability'  => 'manage_options',
			'description' => 'General site configuration and information',
		),
		array(
			'id'          => 'footer',
			'title'       => 'Footer Settings',
			'capability'  => 'manage_options',
			'description' => 'Footer content and scripts',
		),
	)
);

// Register custom metabox with HTML template.
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Site Information',
		'path'  => __DIR__ . '/templates/site-info-metabox.php',
	)
);

$manager->register_metabox(
	array(
		'page'  => 'footer',
		'title' => 'Footer Content',
		'path'  => __DIR__ . '/templates/footer-metabox.php',
	)
);

// Initialize the manager.
$manager->init();

// ============================================================================
// Save Handler for Native Metaboxes
// ============================================================================

// Hook into save_post to save your custom field data.
add_action(
	'save_post',
	function ( $post_id ) {
		$manager = Manager::get( 'basic_settings' );

		// Only process our custom post type.
		if ( get_post_type( $post_id ) !== $manager->get_config( 'post_type' ) ) {
			return;
		}

		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify user has permission to edit this page.
		if ( ! $manager->can_edit_page( $post_id ) ) {
			return;
		}

		// Check if our fields and nonce are present.
		if ( ! isset( $_POST['site_title'], $_POST['_wpnonce'] ) ) {
			return;
		}

		// Sanitize your data.
		$data = array(
			'site_title'       => isset( $_POST['site_title'] )
				? sanitize_text_field( $_POST['site_title'] )
				: '',
			'site_description' => isset( $_POST['site_description'] )
				? sanitize_textarea_field( $_POST['site_description'] )
				: '',
			'site_email'       => isset( $_POST['site_email'] )
				? sanitize_email( $_POST['site_email'] )
				: '',
		);

		// Save using Manager::save_options() - data is serialized to post_content.
		// Returns WP_Error on failure.
		$result = $manager->save_options(
			array(
				'post_id' => $post_id,
				'nonce'   => $_POST['_wpnonce'],
				'data'    => $data,
			)
		);

		if ( is_wp_error( $result ) ) {
			// Log error or show admin notice.
			error_log( 'Failed to save options: ' . $result->get_error_message() );
		}
	},
	10,
	1
);

// ============================================================================
// Retrieve Options
// ============================================================================

// Retrieve options anywhere in your code.
$manager = Manager::get( 'basic_settings' );
$options = $manager->get_options( 'general' );

// Access individual values.
$site_title = $options['site_title'] ?? '';
$site_email = $options['site_email'] ?? '';
