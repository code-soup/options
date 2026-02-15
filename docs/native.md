# Native Metaboxes

Using CodeSoup Options without any field framework. You create your own HTML fields and handle saving.

## Setup

Disable ACF integration and register your metaboxes:

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'site_settings',
	array(
		'menu_label'   => 'Site Settings',
		'integrations' => array(
			'acf' => array( 'enabled' => false ),
		),
	)
);

$manager->register_page(
	array(
		'id'          => 'general',
		'title'       => 'General Settings',
		'capability'  => 'manage_options',
		'description' => 'General site configuration options',
	)
);

$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Site Information',
		'path'  => __DIR__ . '/templates/site-info.php',
	)
);

$manager->init();
```

## Creating Fields

Create a template file with your HTML fields:

**templates/site-info.php:**

```php
<?php
use CodeSoup\Options\Manager;

// $post is available - the current options post
$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'general' );

$site_title = $options['site_title'] ?? '';
$site_email = $options['site_email'] ?? '';
?>

<table class="form-table">
	<tr>
		<th><label for="site_title">Site Title</label></th>
		<td>
			<input type="text" id="site_title" name="site_title"
			       value="<?php echo esc_attr( $site_title ); ?>" class="regular-text" />
		</td>
	</tr>
	<tr>
		<th><label for="site_email">Contact Email</label></th>
		<td>
			<input type="email" id="site_email" name="site_email"
			       value="<?php echo esc_attr( $site_email ); ?>" class="regular-text" />
		</td>
	</tr>
</table>
```

## Saving Data

Hook into `save_post` and use `Manager::save_options()`:

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

	// Verify user has permission to edit this page
	if ( ! $manager->can_edit_page( $post_id ) ) {
		return;
	}

	// Check if fields and nonce exist
	if ( ! isset( $_POST['site_title'], $_POST['_wpnonce'] ) ) {
		return;
	}

	// IMPORTANT: Always sanitize your data before saving
	// Never pass unsanitized $_POST data directly
	$data = array(
		'site_title' => sanitize_text_field( $_POST['site_title'] ),
		'site_email' => sanitize_email( $_POST['site_email'] ),
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
	}
} );
```

## Data Sanitization

**CRITICAL:** You are responsible for sanitizing all input data before calling `save_options()`. The framework provides the storage mechanism, but you must ensure data is safe.

### Sanitization Best Practices

1. **Never pass unsanitized `$_POST` data** - Always sanitize first
2. **Use appropriate sanitization functions** for each field type:
   - `sanitize_text_field()` - For single-line text
   - `sanitize_textarea_field()` - For multi-line text
   - `sanitize_email()` - For email addresses
   - `sanitize_url()` - For URLs
   - `absint()` - For positive integers
   - `wp_kses_post()` - For HTML content
3. **Validate data types** - Ensure data matches expected format
4. **Use nonces** - Always include nonce verification (handled by `save_options()`)

### Example with Multiple Field Types

```php
$data = array(
	'site_title'       => sanitize_text_field( $_POST['site_title'] ?? '' ),
	'site_email'       => sanitize_email( $_POST['site_email'] ?? '' ),
	'site_description' => sanitize_textarea_field( $_POST['site_description'] ?? '' ),
	'site_url'         => sanitize_url( $_POST['site_url'] ?? '' ),
	'posts_per_page'   => absint( $_POST['posts_per_page'] ?? 10 ),
	'welcome_message'  => wp_kses_post( $_POST['welcome_message'] ?? '' ),
);
```

## Error Handling

The `save_options()` method returns `WP_Error` on failure. Always check the return value.

### Errors Returned

`save_options()` returns `WP_Error` with code `'save_options_failed'` when:

- Post ID is missing or invalid
- Nonce is missing or invalid
- Data is missing or not an array
- Post doesn't exist
- Post is not the correct post type
- Nonce verification fails
- User lacks permission to edit the post
- Post update fails
- Cache key exceeds maximum length (172 characters)

### Example with Error Handling and Admin Notices

```php
add_action( 'save_post', function( $post_id ) {
	$manager = Manager::get( 'site_settings' );

	if ( get_post_type( $post_id ) !== $manager->get_config( 'post_type' ) ) {
		return;
	}

	if ( ! isset( $_POST['my_fields'], $_POST['_wpnonce'] ) ) {
		return;
	}

	// Sanitize all input
	$sanitized_data = array(
		'site_title' => sanitize_text_field( $_POST['my_fields']['site_title'] ?? '' ),
		'site_email' => sanitize_email( $_POST['my_fields']['site_email'] ?? '' ),
	);

	$result = $manager->save_options(
		array(
			'post_id' => $post_id,
			'nonce'   => $_POST['_wpnonce'],
			'data'    => $sanitized_data,
		)
	);

	if ( is_wp_error( $result ) ) {
		// Log error
		error_log( 'Options save failed: ' . $result->get_error_message() );

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

## Retrieving Data

Data is stored in `post_content` as a serialized array:

```php
$manager = Manager::get( 'site_settings' );

// Get all options for a page
$options = $manager->get_options( 'general' );
$site_title = $options['site_title'] ?? '';

// Or get single value
$site_email = $manager->get_option( 'general', 'site_email', 'default@example.com' );
```

## Important Notes

- **You must create your own fields** - No automatic field generation
- **You must sanitize input** - Always sanitize before saving (see Data Sanitization section)
- **Data is serialized** - Stored in `post_content`, not postmeta
- **Use Manager::save_options()** - Don't use `update_post_meta()`
- **Check for WP_Error** - save_options() returns WP_Error on failure
- **Security is your responsibility** - The framework provides tools, you must use them correctly

## Complete Example

See `docs/examples/01-basic-usage.php` for a complete working example.

