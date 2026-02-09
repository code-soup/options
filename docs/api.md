# API Reference

Complete reference for all Manager methods and configuration options.

## Manager Methods

### Static Methods

#### `Manager::create( string $key, array $config = [] ): Manager`

Create a new manager instance.

**Parameters:**
- `$key` - Unique instance identifier
- `$config` - Configuration array (see Configuration Options)

**Returns:** Manager instance

**Throws:** `InvalidArgumentException` if instance already exists

---

#### `Manager::get( string $key ): ?Manager`

Retrieve an existing manager instance.

**Parameters:**
- `$key` - Instance identifier

**Returns:** Manager instance or null if not found

---

#### `Manager::save_options( int $post_id, array $data ): bool`

Save options data for native metaboxes. Data is serialized and stored in post_content.

**Parameters:**
- `$post_id` - Post ID
- `$data` - Data array to save

**Returns:** True on success

**Throws:** `InvalidArgumentException` if post doesn't exist or update fails

---

### Instance Methods

#### `register_page( array $page_config ): void`

Register a single options page.

**Parameters:**
- `$page_config` - Page configuration array (see Page Configuration)

**Throws:** `InvalidArgumentException` if validation fails

---

#### `register_pages( array $pages ): void`

Register multiple options pages.

**Parameters:**
- `$pages` - Array of page configuration arrays

---

#### `register_metabox( array $metabox_config ): void`

Register a custom metabox.

**Parameters:**
- `$metabox_config` - Metabox configuration array (see Metabox Configuration)

**Throws:** `InvalidArgumentException` if validation fails

---

#### `get_options( string $page_id ): array`

Get all options for a page from post_content.

**Parameters:**
- `$page_id` - Page identifier

**Returns:** Array of all options

---

#### `get_option( string $page_id, string $key, mixed $default = null ): mixed`

Get a single option value. Uses ACF's `get_field()` if ACF is enabled, otherwise retrieves from post_content.

**Parameters:**
- `$page_id` - Page identifier
- `$key` - Option key
- `$default` - Default value if not found

**Returns:** Option value or default

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
```

---

#### `get_pages(): array`

Get all registered pages.

**Returns:** Array of Page objects

---

#### `can_edit_page( int $post_id ): bool`

Check if current user can edit a specific page.

**Parameters:**
- `$post_id` - Post ID

**Returns:** True if user has required capability, false otherwise

---

#### `get_page_capability( int $post_id ): ?string`

Get the required capability for a specific page.

**Parameters:**
- `$post_id` - Post ID

**Returns:** Capability string or null if not found

---

#### `init(): void`

Initialize the manager and register all WordPress hooks. Must be called after configuration.

---

#### `destroy(): void`

Destroy the manager instance and clean up resources.

---

## Configuration Options

```php
array(
	'post_type'      => 'cs_options',               // Custom post type name
	'prefix'         => 'cs_opt_',                  // Post slug prefix
	'menu_label'     => 'Options',                  // Admin menu label
	'menu_icon'      => 'dashicons-admin-generic',  // Dashicon or URL
	'menu_position'  => 80,                         // Menu position (1-100)
	'parent_menu'    => null,                       // Parent menu slug for submenu
	'revisions'      => true,                       // Enable revision history
	'cache_duration' => HOUR_IN_SECONDS,            // Cache duration in seconds
	'integrations'   => array(                      // Integration configuration
		'acf' => array(
			'enabled' => true,
			'class'   => 'CodeSoup\\Options\\Integrations\\ACF\\Init',
		),
	),
)
```

### `parent_menu` - Menu Placement

Controls where your options pages appear in the WordPress admin menu.

**Behavior:**
- **`null` or empty** (default): Creates a top-level menu in the admin sidebar
- **Set to parent slug**: Creates a submenu under an existing WordPress menu

**Common parent menu values:**

```php
// WordPress Core Menus
'options-general.php'       // Settings
'tools.php'                 // Tools
'themes.php'                // Appearance
'plugins.php'               // Plugins
'users.php'                 // Users
'upload.php'                // Media
'edit.php'                  // Posts
'edit.php?post_type=page'   // Pages

// WooCommerce (if installed)
'woocommerce'

// Custom plugin menus
'your-custom-menu-slug'
```

**Examples:**

```php
// Submenu under Settings
Manager::create(
	'site_settings',
	array(
		'menu_label'  => 'Site Settings',
		'parent_menu' => 'options-general.php',
	)
);
// Result: Settings → Site Settings

// Submenu under Appearance
Manager::create(
	'theme_options',
	array(
		'menu_label'  => 'Theme Options',
		'parent_menu' => 'themes.php',
	)
);
// Result: Appearance → Theme Options

// Top-level menu (no parent_menu)
Manager::create(
	'main_options',
	array(
		'menu_label'    => 'Main Options',
		'menu_icon'     => 'dashicons-admin-settings',
		'menu_position' => 50,
	)
);
// Result: New top-level menu item in sidebar
```

**Notes:**
- When `parent_menu` is set, `menu_icon` and `menu_position` are ignored
- User must have capability for at least one page to see the menu
- See `docs/examples/submenu-usage.php` for complete examples

---

## Page Configuration

```php
array(
	'id'          => 'general',           // Required: Unique page ID
	'title'       => 'General Settings',  // Required: Page title
	'capability'  => 'manage_options',    // Required: User capability
	'description' => 'Site settings',     // Optional: Page description
)
```

---

## Metabox Configuration

```php
array(
	'page'     => 'general',                    // Required: Page ID
	'title'    => 'Custom Fields',              // Required: Metabox title
	'path'     => __DIR__ . '/template.php',    // Required: Template file path
	'context'  => 'normal',                     // Optional: normal, side, advanced
	'priority' => 'default',                    // Optional: high, core, default, low
	'order'    => 10,                           // Optional: Display order
	'args'     => array(),                      // Optional: Custom data for template
)
```

---

## Template Variables

Available in metabox templates:

- `$post` - Current options post object
- `$args` - Custom arguments from metabox config

