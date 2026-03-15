# Native Metaboxes Example

Using CodeSoup Options without any field framework. Full control over HTML fields.

## Setup Code

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
		'class' => 'site-info-metabox',
	)
);

$manager->init();
```

## Field Template (templates/site-info.php)

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

## Sanitization Functions

You are responsible for sanitizing all input data:

- `sanitize_text_field()` - For single-line text
- `sanitize_textarea_field()` - For multi-line text
- `sanitize_email()` - For email addresses
- `sanitize_url()` - For URLs
- `absint()` - For positive integers
- `wp_kses_post()` - For HTML content

