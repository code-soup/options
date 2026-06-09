# Syncing Capabilities Only

Update page capabilities without changing post type or prefix.

## Migration Code

```php
use CodeSoup\Options\Migration;

$old_config = array(); // Empty - no post_type or prefix change

$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'edit_posts', // Changed from manage_options
	),
	array(
		'id'         => 'advanced',
		'capability' => 'manage_options',
	),
);

$result = Migration::migrate( 'site_settings', $old_config, $new_pages );
```

## Complete Example

```php
use CodeSoup\Options\Migration;
use CodeSoup\Options\Manager;

// Initialize Manager with NEW capabilities
$manager = Manager::create( 'site_settings' );

$manager->register_pages(
	array(
		array(
			'id'         => 'general',
			'title'      => 'General Settings',
			'capability' => 'edit_posts', // Changed capability
		),
		array(
			'id'         => 'advanced',
			'title'      => 'Advanced Settings',
			'capability' => 'manage_options',
		),
	)
);

$manager->init();

// Sync capabilities
$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'edit_posts',
	),
	array(
		'id'         => 'advanced',
		'capability' => 'manage_options',
	),
);

$result = Migration::migrate( 'site_settings', array(), $new_pages );

if ( $result['success'] ) {
	echo "Capabilities synced!\n";
	echo "Capabilities synced: {$result['capabilities_synced']}\n";
}
```

