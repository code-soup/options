# Basic Migration Example

Changing post type and prefix from old configuration to new.

## Old Configuration

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'site_settings',
	array(
		'post_type' => 'old_options',
		'prefix'    => 'old_',
	)
);
```

## New Configuration

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'site_settings',
	array(
		'post_type' => 'new_options',
		'prefix'    => 'new_',
	)
);
```

## Migration Script

```php
use CodeSoup\Options\Migration;
use CodeSoup\Options\Manager;

// Initialize Manager with NEW configuration
$manager = Manager::create(
	'site_settings',
	array(
		'post_type' => 'new_options',
		'prefix'    => 'new_',
	)
);

$manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General Settings',
		'capability' => 'manage_options',
	)
);

$manager->init();

// Run migration with OLD configuration
$old_config = array(
	'post_type' => 'old_options',
	'prefix'    => 'old_',
);

$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'manage_options',
	),
);

$result = Migration::migrate( 'site_settings', $old_config, $new_pages );

// Check results
if ( $result['success'] ) {
	echo sprintf(
		'Migration complete: %d posts updated, %d post types changed, %d prefixes changed, %d capabilities synced',
		$result['posts_updated'],
		$result['post_type_changed'],
		$result['prefix_changed'],
		$result['capabilities_synced']
	);
} else {
	echo 'Migration failed: ' . $result['error'];
}

if ( ! empty( $result['errors'] ) ) {
	foreach ( $result['errors'] as $error ) {
		echo 'Error: ' . $error . "\n";
	}
}
```

## Verification

After migration, verify:

```php
// Check posts were renamed
$posts = get_posts(
	array(
		'post_type'      => 'new_options',
		'posts_per_page' => -1,
	)
);

foreach ( $posts as $post ) {
	echo sprintf( 'Post: %s (ID: %d)', $post->post_name, $post->ID ) . "\n";
}

// Check data integrity
$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'general' );
var_dump( $options ); // Should contain your data
```

