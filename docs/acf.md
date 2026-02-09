# ACF Integration

Using CodeSoup Options with Advanced Custom Fields. ACF is enabled by default.

## Setup

```php
use CodeSoup\Options\Manager;

$manager = Manager::create( 'theme_settings' );

$manager->register_pages(
	array(
		array( 'id' => 'general', 'title' => 'General', 'capability' => 'manage_options' ),
		array( 'id' => 'header', 'title' => 'Header', 'capability' => 'manage_options' ),
		array( 'id' => 'footer', 'title' => 'Footer', 'capability' => 'manage_options' ),
	)
);

$manager->init();
```

## Assigning Field Groups

1. Create or edit an ACF field group in WordPress admin
2. Under "Location Rules", add:
   - **Rule:** CodeSoup Options
   - **Operator:** is equal to
   - **Value:** Select your page ID (e.g., "general")
3. Add your fields
4. Save the field group

The field group will now appear on the selected options page.

## Data Storage

ACF integration uses **dual storage**:

- **Postmeta** - Individual fields stored for ACF compatibility
- **Post Content** - All fields serialized for fast bulk retrieval

This allows you to:
- Use ACF's `get_field()` functions
- Use Manager's `get_options()` for fast retrieval
- Maintain ACF field validation and formatting

## Retrieving Data

### Get All Options

```php
$manager = Manager::get( 'theme_settings' );
$options = $manager->get_options( 'general' );

$site_logo = $options['site_logo'] ?? '';
$site_tagline = $options['site_tagline'] ?? '';
```

### Get Single Field

```php
$manager = Manager::get( 'theme_settings' );

// Uses ACF's get_field() internally
$logo_id = $manager->get_option( 'general', 'site_logo' );

// With default value
$footer_text = $manager->get_option( 'footer', 'copyright', '© 2024' );
```

### In Templates

```php
$settings = Manager::get( 'theme_settings' );

// Display logo
$logo_id = $settings->get_option( 'header', 'logo' );
if ( $logo_id ) {
	echo wp_get_attachment_image( $logo_id, 'full' );
}

// Display social links
$facebook = $settings->get_option( 'social', 'facebook_url' );
if ( $facebook ) {
	printf( '<a href="%s">Facebook</a>', esc_url( $facebook ) );
}
```

## Combining with Native Metaboxes

You can add native metaboxes alongside ACF field groups:

```php
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Custom Settings',
		'path'  => __DIR__ . '/templates/custom.php',
	)
);
```

## Disabling ACF

To disable ACF for a specific instance:

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

## Saving Data

ACF handles saving automatically. You don't need to create save handlers.

## Troubleshooting

**Field groups not showing:**
- Verify ACF is installed and active
- Check location rules match your page ID exactly
- Clear WordPress object cache

**Options not saving:**
- Check user has required capability
- Review WordPress debug log for errors
- Verify ACF field group is published

## Complete Example

See `docs/examples/02-acf-integration.php` for a complete working example.

