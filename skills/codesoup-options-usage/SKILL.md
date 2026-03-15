---
name: codesoup-options-usage
description: Retrieve and save WordPress options using CodeSoup Options Manager API. Get single options, bulk options, use in templates. Complete API reference for Manager methods including create, get, save_options, get_options, get_option, register_page, register_pages, register_metabox, can_edit_page, get_config, init. Use when retrieving options in themes or plugins, saving options programmatically, accessing Manager instances, working with options in templates, or understanding the Manager API.
license: GPL-3.0-or-later
metadata:
  author: code-soup
  version: "1.0.0"
  package: codesoup/options
---

# CodeSoup Options Usage

Retrieve and save WordPress options using the CodeSoup Options Manager API.

## Examples

Complete working examples are available in the `examples/` directory:

- [Retrieving Options](examples/retrieving-options.md) - Get single and bulk options
- [Saving Options](examples/saving-options.md) - Save with native metaboxes

## When to Use This Skill

- Retrieving options in WordPress themes or plugins
- Saving options programmatically
- Accessing Manager instances
- Working with options in templates
- Understanding the Manager API

## Retrieving Options

### Get All Options for a Page

Returns all options as an array from post_content (fast bulk retrieval). Use `get_options( $page_id )` to retrieve all options at once.

### Get Single Option

Uses ACF's `get_field()` if ACF is enabled, otherwise retrieves from post_content. Use `get_option( $page_id, $field_name, $default )` for single values.

### Using in Templates

Options can be retrieved in header.php, footer.php, or any template file using `Manager::get()` to access the Manager instance.

**See:** [Retrieving Options Example](examples/retrieving-options.md) for complete code examples including bulk retrieval, single options, template usage, helper functions, and conditional display patterns.

## Saving Options

### With ACF Integration

ACF handles saving automatically. No save handlers needed.

### With Native Metaboxes

Use `save_options()` method in a `save_post` hook. Always sanitize data before saving using appropriate sanitization functions (sanitize_text_field, sanitize_email, sanitize_url, absint, wp_kses_post).

**See:** [Saving Options Example](examples/saving-options.md) for complete save_post hook implementation, data sanitization, error handling, and programmatic saving.

## Manager API Reference

### Static Methods

#### `Manager::create( string $key, array $config = [] ): Manager`

Create a new manager instance.

**Parameters:**
- `$key` - Unique instance identifier
- `$config` - Configuration array

**Returns:** Manager instance

**Throws:** `InvalidArgumentException` if instance already exists

**Example:**

```php
$manager = Manager::create(
	'theme_settings',
	array(
		'menu_label'    => 'Theme Settings',
		'menu_icon'     => 'dashicons-admin-appearance',
		'menu_position' => 60,
	)
);
```

---

#### `Manager::get( string $key ): ?Manager`

Retrieve an existing manager instance.

**Parameters:**
- `$key` - Instance identifier

**Returns:** Manager instance or null if not found

**Example:**

```php
$manager = Manager::get( 'theme_settings' );
if ( $manager ) {
	$options = $manager->get_options( 'general' );
}
```

---

#### `Manager::save_options( array $args ): bool|WP_Error`

Save options data for native metaboxes. Data is serialized and stored in post_content.

**Parameters:**
- `$args['post_id']` - Post ID
- `$args['nonce']` - Nonce value for verification
- `$args['data']` - Data array to save (must be sanitized)

**Returns:** True on success, WP_Error on failure

**Error Codes:** Returns `WP_Error` with code `'save_options_failed'` when:
- Post ID is missing or invalid
- Nonce is missing or invalid
- Data is missing or not an array
- Post doesn't exist
- Post is not the correct post type
- Nonce verification fails
- User lacks permission to edit the post
- Post update fails
- Cache key exceeds maximum length (172 characters)

**Example:**

```php
$result = $manager->save_options(
	array(
		'post_id' => $post_id,
		'nonce'   => $_POST['_wpnonce'],
		'data'    => $sanitized_data,
	)
);

if ( is_wp_error( $result ) ) {
	error_log( 'Save failed: ' . $result->get_error_message() );
}
```

---

### Instance Methods

#### `register_page( array $page_config ): void`

Register a single options page.

**Parameters:**
- `$page_config['id']` - Required: Unique page ID
- `$page_config['title']` - Required: Page title
- `$page_config['capability']` - Required: User capability
- `$page_config['description']` - Optional: Page description (stored in post_excerpt)

**Throws:** `InvalidArgumentException` if validation fails

**Example:**

```php
$manager->register_page(
	array(
		'id'          => 'general',
		'title'       => 'General Settings',
		'capability'  => 'manage_options',
		'description' => 'General site settings',
	)
);
```

---

#### `register_pages( array $pages ): void`

Register multiple options pages.

**Parameters:**
- `$pages` - Array of page configuration arrays

**Example:**

```php
$manager->register_pages(
	array(
		array( 'id' => 'general', 'title' => 'General', 'capability' => 'manage_options' ),
		array( 'id' => 'header', 'title' => 'Header', 'capability' => 'manage_options' ),
		array( 'id' => 'footer', 'title' => 'Footer', 'capability' => 'manage_options' ),
	)
);
```

---

#### `register_metabox( array $metabox_config ): void`

Register a custom metabox.

**Parameters:**
- `$metabox_config['page']` - Required: Page ID
- `$metabox_config['title']` - Required: Metabox title
- `$metabox_config['path']` - Required: Template file path
- `$metabox_config['context']` - Optional: normal, side, advanced (default: normal)
- `$metabox_config['priority']` - Optional: high, core, default, low (default: default)
- `$metabox_config['order']` - Optional: Display order (default: 10)
- `$metabox_config['class']` - Optional: Custom CSS class for postbox
- `$metabox_config['args']` - Optional: Custom data for template

**Throws:** `InvalidArgumentException` if validation fails

**Example:**

```php
$manager->register_metabox(
	array(
		'page'     => 'general',
		'title'    => 'Site Information',
		'path'     => __DIR__ . '/templates/site-info.php',
		'context'  => 'normal',
		'priority' => 'high',
		'order'    => 5,
		'class'    => 'highlighted-metabox',
		'args'     => array( 'custom_data' => 'value' ),
	)
);
```

---

#### `get_options( string $page_id ): array`

Get all options for a page from post_content.

**Parameters:**
- `$page_id` - Page identifier

**Returns:** Array of all options

**Example:**

```php
$options = $manager->get_options( 'general' );
$site_title = $options['site_title'] ?? '';
$site_email = $options['site_email'] ?? '';
```

---

#### `get_option( string $page_id, string $key, mixed $default = null ): mixed`

Get a single option value. Uses ACF's `get_field()` if ACF is enabled, otherwise retrieves from post_content.

**Parameters:**
- `$page_id` - Page identifier
- `$key` - Option key
- `$default` - Default value if not found

**Returns:** Option value or default

**Example:**

```php
// Without default
$logo_id = $manager->get_option( 'header', 'logo' );

// With default
$copyright = $manager->get_option( 'footer', 'copyright', '© 2024' );
```

---

#### `get_config( ?string $key = null ): mixed`

Get the manager configuration.

**Parameters:**
- `$key` - Optional config key to retrieve

**Returns:** Full config array if no key provided, specific value if key provided, null if key not found

**Examples:**

```php
// Get full config
$config = $manager->get_config();

// Get specific value
$post_type = $manager->get_config( 'post_type' );
$menu_label = $manager->get_config( 'menu_label' );
$debug = $manager->get_config( 'debug' );
```

---

#### `get_pages(): array`

Get all registered pages.

**Returns:** Array of Page objects

**Example:**

```php
$pages = $manager->get_pages();
foreach ( $pages as $page ) {
	echo $page->get_id() . ': ' . $page->get_title();
}
```

---

#### `can_edit_page( int $post_id ): bool`

Check if current user can edit a specific page.

**Parameters:**
- `$post_id` - Post ID

**Returns:** True if user has required capability, false otherwise

**Example:**

```php
if ( $manager->can_edit_page( $post_id ) ) {
	// User can edit this page
	$manager->save_options( /* ... */ );
}
```

---

#### `get_page_capability( int $post_id ): ?string`

Get the required capability for a specific page.

**Parameters:**
- `$post_id` - Post ID

**Returns:** Capability string or null if not found

**Example:**

```php
$capability = $manager->get_page_capability( $post_id );
if ( current_user_can( $capability ) ) {
	// User has required capability
}
```

---

#### `init(): void`

Initialize the manager and register all WordPress hooks. Must be called after configuration.

**Example:**

```php
$manager = Manager::create( 'theme_settings' );
$manager->register_pages( /* ... */ );
$manager->init(); // Must call this
```

---

#### `destroy(): void`

Destroy the manager instance and clean up resources.

**Example:**

```php
$manager = Manager::get( 'theme_settings' );
$manager->destroy();
```

---

## Common Usage Patterns

### Pattern 1: Simple Retrieval

```php
use CodeSoup\Options\Manager;

// Get manager instance
$settings = Manager::get( 'theme_settings' );

// Get single value
$logo = $settings->get_option( 'header', 'logo' );

// Use in template
if ( $logo ) {
	echo wp_get_attachment_image( $logo, 'full' );
}
```

### Pattern 2: Bulk Retrieval

```php
use CodeSoup\Options\Manager;

// Get all options at once (faster for multiple values)
$settings = Manager::get( 'theme_settings' );
$footer_options = $settings->get_options( 'footer' );

// Access multiple values
$copyright = $footer_options['copyright'] ?? '';
$address = $footer_options['address'] ?? '';
$phone = $footer_options['phone'] ?? '';
```

### Pattern 3: Conditional Display

```php
use CodeSoup\Options\Manager;

$settings = Manager::get( 'theme_settings' );
$social = $settings->get_options( 'social' );

// Display social links if they exist
$links = array(
	'facebook'  => $social['facebook'] ?? '',
	'twitter'   => $social['twitter'] ?? '',
	'instagram' => $social['instagram'] ?? '',
);

foreach ( $links as $platform => $url ) {
	if ( $url ) {
		printf(
			'<a href="%s" class="social-%s">%s</a>',
			esc_url( $url ),
			esc_attr( $platform ),
			esc_html( ucfirst( $platform ) )
		);
	}
}
```

### Pattern 4: Helper Functions

```php
use CodeSoup\Options\Manager;

/**
 * Get theme option
 */
function mytheme_get_option( $page, $key, $default = null ) {
	$manager = Manager::get( 'theme_settings' );
	return $manager ? $manager->get_option( $page, $key, $default ) : $default;
}

// Usage in templates
$logo = mytheme_get_option( 'header', 'logo' );
$copyright = mytheme_get_option( 'footer', 'copyright', '© ' . gmdate( 'Y' ) );
```

### Pattern 5: Programmatic Save

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

## Data Storage

### ACF Integration

When ACF is enabled, data is stored in two places:

1. **Postmeta** - Individual fields stored for ACF compatibility
2. **Post Content** - All fields serialized for fast bulk retrieval

This dual storage allows:
- Using ACF's `get_field()` functions
- Using Manager's `get_options()` for fast retrieval
- Maintaining ACF field validation and formatting

### Native Metaboxes

Data is stored only in `post_content` as a serialized array:

```php
// Stored in post_content
array(
	'site_title' => 'My Site',
	'site_email' => 'admin@example.com',
	'site_url'   => 'https://example.com',
)
```

**Important:**
- Don't use `update_post_meta()` - Use `Manager::save_options()` instead
- Data is cached for performance (default: 1 hour)
- Cache is automatically invalidated on save

## Performance Tips

1. **Use bulk retrieval** when accessing multiple options:
   ```php
   // Good - One query
   $options = $manager->get_options( 'general' );
   $title = $options['site_title'];
   $email = $options['site_email'];

   // Less efficient - Multiple queries (with ACF)
   $title = $manager->get_option( 'general', 'site_title' );
   $email = $manager->get_option( 'general', 'site_email' );
   ```

2. **Cache manager instances** in your code:
   ```php
   // Good - Get once
   $manager = Manager::get( 'theme_settings' );
   $logo = $manager->get_option( 'header', 'logo' );
   $menu = $manager->get_option( 'header', 'menu' );

   // Less efficient - Get multiple times
   $logo = Manager::get( 'theme_settings' )->get_option( 'header', 'logo' );
   $menu = Manager::get( 'theme_settings' )->get_option( 'header', 'menu' );
   ```

3. **Adjust cache duration** for your needs:
   ```php
   Manager::create(
   	'theme_settings',
   	array(
   		'cache_duration' => DAY_IN_SECONDS, // Cache for 24 hours
   	)
   );
   ```

## Troubleshooting

**Options returning empty:**
- Verify manager instance exists: `Manager::get( 'key' )` returns non-null
- Check page ID matches registered page
- Verify data was saved (check post_content in database)
- Clear WordPress object cache

**ACF fields not retrieving:**
- Verify ACF is installed and active
- Check field groups are assigned to correct page
- Verify field names match exactly

**Save not working:**
- Check `save_options()` return value for WP_Error
- Verify nonce is valid
- Check user has required capability
- Review error_log for messages (enable debug mode)

**Performance issues:**
- Use `get_options()` for bulk retrieval instead of multiple `get_option()` calls
- Increase cache duration if options don't change frequently
- Check for excessive Manager::get() calls in loops


