# ACF Integration Example

Basic setup with Advanced Custom Fields integration (enabled by default).

## Setup Code

```php
use CodeSoup\Options\Manager;

$manager = Manager::create( 'theme_settings' );

$manager->register_pages(
	array(
		array( 'id' => 'general', 'title' => 'General', 'capability' => 'manage_options', 'description' => 'General site settings' ),
		array( 'id' => 'header', 'title' => 'Header', 'capability' => 'manage_options', 'description' => 'Header configuration' ),
		array( 'id' => 'footer', 'title' => 'Footer', 'capability' => 'manage_options', 'description' => 'Footer settings' ),
	)
);

$manager->init();
```

## Assigning ACF Field Groups

1. Create or edit an ACF field group in WordPress admin
2. Under "Location Rules", add:
   - **Rule:** CodeSoup Options
   - **Operator:** is equal to
   - **Value:** Select your page ID (e.g., "general")
3. Add your fields
4. Save the field group

## Data Storage

ACF integration uses dual storage:
- **Postmeta** - Individual fields stored for ACF compatibility
- **Post Content** - All fields serialized for fast bulk retrieval

This allows you to:
- Use ACF's `get_field()` functions
- Use Manager's `get_options()` for fast retrieval
- Maintain ACF field validation and formatting

## Combining with Native Metaboxes

```php
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Custom Settings',
		'path'  => __DIR__ . '/templates/custom.php',
		'class' => 'custom-settings-box',
	)
);
```

## Disabling ACF

```php
$manager = Manager::create(
	'instance_key',
	array(
		'integrations' => array(
			'acf' => array( 'enabled' => false ),
		),
	)
);
```

