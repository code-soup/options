# Saving Options Examples

## With ACF Integration

ACF handles saving automatically. No save handlers needed.

## With Native Metaboxes

Use `save_options()` method in a `save_post` hook:

```php
use CodeSoup\Options\Manager;

add_action( 'save_post', function( $post_id ) {
	$manager = Manager::get( 'site_settings' );

	// Only process your post type
	if ( get_post_type( $post_id ) !== $manager->get_config( 'post_type' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Verify user has permission
	if ( ! $manager->can_edit_page( $post_id ) ) {
		return;
	}

	// Check if fields exist
	if ( ! isset( $_POST['site_title'], $_POST['_wpnonce'] ) ) {
		return;
	}

	// CRITICAL: Always sanitize data
	$data = array(
		'site_title'       => sanitize_text_field( $_POST['site_title'] ?? '' ),
		'site_email'       => sanitize_email( $_POST['site_email'] ?? '' ),
		'site_description' => sanitize_textarea_field( $_POST['site_description'] ?? '' ),
		'site_url'         => sanitize_url( $_POST['site_url'] ?? '' ),
		'posts_per_page'   => absint( $_POST['posts_per_page'] ?? 10 ),
		'welcome_message'  => wp_kses_post( $_POST['welcome_message'] ?? '' ),
	);

	// Save - returns WP_Error on failure
	$result = $manager->save_options(
		array(
			'post_id' => $post_id,
			'nonce'   => $_POST['_wpnonce'],
			'data'    => $data,
		)
	);

	if ( is_wp_error( $result ) ) {
		error_log( 'Failed to save options: ' . $result->get_error_message() );
		
		// Show admin notice
		add_action( 'admin_notices', function() use ( $result ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $result->get_error_message() )
			);
		} );
	}
} );
```

## Sanitization Functions

- `sanitize_text_field()` - Single-line text
- `sanitize_textarea_field()` - Multi-line text
- `sanitize_email()` - Email addresses
- `sanitize_url()` - URLs
- `absint()` - Positive integers
- `wp_kses_post()` - HTML content

## Programmatic Save

```php
use CodeSoup\Options\Manager;

function update_site_settings( $data ) {
	$manager = Manager::get( 'site_settings' );

	// Get the post ID for the page
	$pages = $manager->get_pages();
	$general_page = null;
	foreach ( $pages as $page ) {
		if ( 'general' === $page->get_id() ) {
			$general_page = $page;
			break;
		}
	}

	if ( ! $general_page ) {
		return new WP_Error( 'page_not_found', 'General page not found' );
	}

	// Sanitize data
	$sanitized = array(
		'site_title' => sanitize_text_field( $data['site_title'] ?? '' ),
		'site_email' => sanitize_email( $data['site_email'] ?? '' ),
	);

	// Save
	return $manager->save_options(
		array(
			'post_id' => $general_page->get_post_id(),
			'nonce'   => wp_create_nonce( 'update-post_' . $general_page->get_post_id() ),
			'data'    => $sanitized,
		)
	);
}
```

