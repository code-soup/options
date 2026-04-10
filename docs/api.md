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

**Implementation Note:** Uses direct database updates (`$wpdb->update()`) instead of `wp_update_post()` to prevent infinite loops when called from `save_post` hooks.

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

// Get specific values via array access
$config = $manager->get_config();
$post_type  = $config['post_type'];
$menu_label = $config['menu']['label'];
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

**New in v1.1.0:** Configuration uses nested structure for better organization. See [Migration Guide](migration-v1.1.md) if upgrading from older versions.

```php
array(
	'post_type'      => 'cs_options',        // Custom post type name
	'prefix'         => 'cs_opt_',           // Post slug prefix
	'menu'           => array(
		'label'    => 'Options',             // Admin menu label
		'icon'     => 'dashicons-admin-generic', // Dashicon or URL
		'position' => 80,                    // Menu position (1-100)
		'parent'   => null,                  // Parent menu slug for submenu
	),
	'ui'             => array(
		'mode'         => 'pages',           // 'pages' or 'tabs'
		'tab_position' => 'top',             // 'top' or 'left' (tabs mode only)
		'templates_dir' => null,             // Custom templates directory path
	),
	'assets'         => array(
		'disable_styles'   => false,         // Disable plugin styles
		'disable_scripts'  => false,         // Disable plugin scripts
		'disable_branding' => false,         // Disable CodeSoup branding header
	),
	'revisions'      => true,                // Enable revision history
	'debug'          => false,               // Enable error logging (default: false)
	'integrations'   => array(               // Integration configuration
		'acf' => array(
			'enabled' => true,
			'class'   => 'CodeSoup\\Options\\Integrations\\ACF\\Init',
		),
	),
)
```

### `debug` - Error Logging

Controls whether the plugin logs errors, warnings, and info messages to the PHP error log.

**Behavior:**
- **`false`** (default): All logging is disabled. No messages will be logged
- **`true`**: Logging is enabled. Errors, warnings, and info messages will be logged via Logger class

**Examples:**

```php
Manager::create(
	'site_settings',
	array(
		'debug' => false,
	)
);
```

**Notes:**
- Debug messages are only logged when both `debug` is `true` AND `WP_DEBUG` is enabled
- When `debug` is `false`, no messages are logged regardless of log level
- Useful for production environments where you want to suppress all plugin logging

---

### `menu.parent` - Menu Placement

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
		'menu' => array(
			'label'  => 'Site Settings',
			'parent' => 'options-general.php',
		),
	)
);
// Result: Settings → Site Settings

// Submenu under Appearance
Manager::create(
	'theme_options',
	array(
		'menu' => array(
			'label'  => 'Theme Options',
			'parent' => 'themes.php',
		),
	)
);
// Result: Appearance → Theme Options

// Top-level menu (no parent)
Manager::create(
	'main_options',
	array(
		'menu' => array(
			'label'    => 'Main Options',
			'icon'     => 'dashicons-admin-settings',
			'position' => 50,
		),
	)
);
// Result: New top-level menu item in sidebar
```

**Notes:**
- When `menu.parent` is set, `menu.icon` and `menu.position` are ignored
- User must have capability for at least one page to see the menu
- See `docs/examples/submenu-usage.php` for complete examples

---

### `ui.mode` - UI Display Mode

Controls how options pages are displayed in WordPress admin.

**Values:**
- `'pages'` (default) - Each page is a separate WordPress admin page with list table
- `'tabs'` - All pages grouped under single admin page with tab navigation

**Examples:**

```php
// Pages mode (default)
Manager::create(
	'site_settings',
	array(
		'ui' => array(
			'mode' => 'pages',
		),
	)
);

// Tabs mode
Manager::create(
	'site_settings',
	array(
		'ui' => array(
			'mode'         => 'tabs',
			'tab_position' => 'top',
		),
		'integrations' => array(
			'acf' => array( 'enabled' => false ),  // Must disable for tabs mode
		),
	)
);
```

**Notes:**
- Tabs mode requires all integrations to be disabled
- Tabs mode works with native metaboxes only
- See `docs/tabbed-ui.md` for complete documentation

---

### `ui.tab_position` - Tab Layout

Controls tab placement in tabs mode. Only used when `ui.mode` is `'tabs'`.

**Values:**
- `'top'` (default) - Horizontal tabs above content
- `'left'` - Vertical tabs in left sidebar

**Examples:**

```php
// Horizontal tabs
Manager::create(
	'site_settings',
	array(
		'ui' => array(
			'mode'         => 'tabs',
			'tab_position' => 'top',
		),
);

// Vertical tabs
Manager::create(
	'site_settings',
	array(
		'ui' => array(
			'mode'         => 'tabs',
			'tab_position' => 'left',
		),
	)
);
```

**Notes:**
- Only applies when `ui.mode` is `'tabs'`
- Ignored in pages mode

---

### `assets.disable_styles` and `assets.disable_scripts` - Asset Control

Disable plugin CSS and JavaScript files.

**Examples:**

```php
Manager::create(
	'site_settings',
	array(
		'assets' => array(
			'disable_styles'  => true,  // No CSS
			'disable_scripts' => true,  // No JavaScript
		),
	)
);
```

**Notes:**
- Useful when providing custom styling
- Defaults to `false` (assets enabled)

---

### `disable_branding` - Branding Control

Disable CodeSoup branding header.

**Examples:**

```php
Manager::create(
	'site_settings',
	array(
		'disable_branding' => true,
	)
);
```

**Notes:**
- Removes CodeSoup logo and header from admin pages
- Defaults to `false` (branding shown)
- Header can still be customized via `codesoup_options_header_template` filter

---

### `templates_dir` - Custom Templates Directory

Override default template directory. Plugin will check this directory first before using built-in templates.

**Examples:**

```php
Manager::create(
	'site_settings',
	array(
		'templates_dir' => get_stylesheet_directory() . '/codesoup-templates',
	)
);
```

**Template Structure:**

Your custom templates directory should mirror the plugin's structure:

```
your-templates-dir/
├── header/
│   └── default.php
├── sidebar/
│   ├── banner-sidebar.php
│   └── advertising.php
├── tabs/
│   ├── wrapper.php
│   ├── navigation/
│   │   ├── horizontal.php
│   │   ├── vertical.php
│   │   └── mobile.php
│   └── content/
│       ├── index.php
│       ├── form.php
│       └── empty.php
└── metabox/
    └── actions.php
```

**Notes:**
- Only override templates you need to customize
- Plugin falls back to built-in templates for missing files
- Templates have access to same variables as built-in templates
- See `docs/customization.md` for template variables reference

---

## Page Configuration

```php
array(
	'id'          => 'general',           // Required: Unique page ID (sanitized with sanitize_key)
	'title'       => 'General Settings',  // Required: Page title
	'capability'  => 'manage_options',    // Required: User capability
	'description' => 'Site settings',     // Optional: Page description (stored in post_excerpt)
)
```

**Important - Page ID Matching:**
- Page `id` is sanitized using WordPress `sanitize_key()` function
- **The exact same ID must be used when registering metaboxes:** `register_metabox(['page' => 'general'])`
- **Recommended:** Use simple alphanumeric IDs: `'general'`, `'footer'`, `'advanced'`
- **Avoid:** Special characters, dashes may cause mismatches

**Description Field:**
- The `description` field is optional and stored in the WordPress `post_excerpt` field
- Useful for adding context or notes about the page's purpose
- Can be retrieved using standard WordPress functions like `get_post_field( 'post_excerpt', $post_id )`

---

## Metabox Configuration

```php
array(
	'page'     => 'general',                    // Required: Page ID (must match registered page ID exactly)
	'title'    => 'Custom Fields',              // Required: Metabox title
	'path'     => __DIR__ . '/template.php',    // Required: Template file path
	'context'  => 'normal',                     // Optional: normal, side, advanced
	'priority' => 'default',                    // Optional: high, core, default, low
	'order'    => 10,                           // Optional: Display order
	'class'    => 'custom-metabox-class',       // Optional: Custom CSS class(es) for postbox
	'args'     => array(),                      // Optional: Custom data for template
)
```

**Important - Page ID Must Match:**
- The `page` value **must exactly match** the page `id` used in `register_page()` or `register_pages()`
- Both are sanitized with `sanitize_key()` so they must be identical
- Example: If page ID is `'general'`, use `'page' => 'general'` in metabox
- **Mismatch = metabox won't display**

**Custom CSS Classes:**
- The `class` parameter is optional and adds custom CSS classes to the metabox postbox element
- Accepts string (space-separated) or array of class names
- Each class is sanitized using `sanitize_html_class()` for security
- Applied via WordPress `postbox_classes_{$post_type}_{$id}` filter
- Only applied if a non-empty value is provided
- Duplicates are automatically removed
- Useful for custom styling or JavaScript targeting

**Examples:**
```php
// Single class (string)
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Advanced Settings',
		'path'  => __DIR__ . '/templates/advanced.php',
		'class' => 'highlighted-metabox',
	)
);

// Multiple classes (space-separated string)
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Advanced Settings',
		'path'  => __DIR__ . '/templates/advanced.php',
		'class' => 'highlighted-metabox custom-border',
	)
);

// Multiple classes (array)
$manager->register_metabox(
	array(
		'page'  => 'general',
		'title' => 'Advanced Settings',
		'path'  => __DIR__ . '/templates/advanced.php',
		'class' => array( 'highlighted-metabox', 'custom-border', 'priority-high' ),
	)
);
```

---

## Template Variables

Available in metabox templates:

- `$post` - Current options post object
- `$args` - Custom arguments from metabox config

