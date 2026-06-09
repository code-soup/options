# Changing Only the Prefix

Migrate when only changing the prefix, keeping the same post type.

## Migration Code

```php
use CodeSoup\Options\Migration;

$old_config = array(
	'prefix' => 'old_prefix_',
);

$result = Migration::migrate( 'site_settings', $old_config );
```

## Complete Example

```php
use CodeSoup\Options\Migration;
use CodeSoup\Options\Manager;

// Initialize Manager with NEW prefix
$manager = Manager::create(
	'site_settings',
	array(
		'prefix' => 'new_prefix_',
	)
);

$manager->register_pages(
	array(
		array( 'id' => 'general', 'title' => 'General', 'capability' => 'manage_options' ),
		array( 'id' => 'advanced', 'title' => 'Advanced', 'capability' => 'manage_options' ),
	)
);

$manager->init();

// Run migration with OLD prefix
$old_config = array(
	'prefix' => 'old_prefix_',
);

$result = Migration::migrate( 'site_settings', $old_config );

if ( $result['success'] ) {
	echo "Prefix migration complete!\n";
	echo "Posts updated: {$result['posts_updated']}\n";
	echo "Prefixes changed: {$result['prefix_changed']}\n";
}
```

