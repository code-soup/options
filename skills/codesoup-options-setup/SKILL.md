---
name: CodeSoup Options Setup
description: Set up and configure CodeSoup Options plugin for WordPress. Create options pages using ACF (Advanced Custom Fields), native metaboxes, or custom integrations (CMB2, MetaBox.io, Carbon Fields). Configure menu placement, post types, capabilities, and integrations.
---

# CodeSoup Options Setup

Set up WordPress options pages using custom post types with ACF integration, native metaboxes, or custom field frameworks.

## When to Use This Skill

- Setting up WordPress options/settings pages
- Integrating with Advanced Custom Fields (ACF)
- Creating custom admin pages without ACF
- Integrating with CMB2, MetaBox.io, or Carbon Fields
- Configuring WordPress admin menu placement
- Managing options with revision history and post locking

## Requirements

- PHP >= 7.2
- WordPress >= 6.0
- Optional: ACF, CMB2, MetaBox.io, or Carbon Fields

## Installation

### Via Composer

```bash
composer require codesoup/options
```

### As WordPress Plugin

1. Download and extract to `wp-content/plugins/codesoup-options`
2. Activate the plugin
3. Add configuration to your theme or plugin

## Setup Methods

### Method 1: ACF Integration (Recommended)

ACF integration is enabled by default. No additional configuration needed.

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

**Assigning ACF Field Groups:**

1. Create or edit an ACF field group in WordPress admin
2. Under "Location Rules", add:
   - **Rule:** CodeSoup Options
   - **Operator:** is equal to
   - **Value:** Select your page ID (e.g., "general")
3. Add your fields
4. Save the field group

**Data Storage:**

ACF integration uses dual storage:
- **Postmeta** - Individual fields stored for ACF compatibility
- **Post Content** - All fields serialized for fast bulk retrieval

This allows you to:
- Use ACF's `get_field()` functions
- Use Manager's `get_options()` for fast retrieval
- Maintain ACF field validation and formatting

**Combining with Native Metaboxes:**

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

**Disabling ACF:**

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

### Method 2: Native Metaboxes (No Framework)

Use when you want full control over HTML fields without any framework.

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

**Creating Field Template (templates/site-info.php):**

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

**Saving Data with Native Metaboxes:**

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

**Data Sanitization (CRITICAL):**

You are responsible for sanitizing all input data before calling `save_options()`. Use appropriate sanitization functions:

- `sanitize_text_field()` - For single-line text
- `sanitize_textarea_field()` - For multi-line text
- `sanitize_email()` - For email addresses
- `sanitize_url()` - For URLs
- `absint()` - For positive integers
- `wp_kses_post()` - For HTML content

**Error Handling:**

`save_options()` returns `WP_Error` with code `'save_options_failed'` when:
- Post ID is missing or invalid
- Nonce is missing or invalid
- Data is missing or not an array
- Post doesn't exist
- Post is not the correct post type
- Nonce verification fails
- User lacks permission to edit the post
- Post update fails

### Method 3: Custom Integration (CMB2, MetaBox.io, Carbon Fields)

Create custom integrations for any field framework.

**Integration Interface:**

All integrations must implement `IntegrationInterface`:

```php
namespace CodeSoup\Options\Integrations;

use CodeSoup\Options\Manager;

interface IntegrationInterface {
	public function __construct( Manager $manager );
	public static function is_available(): bool;
	public function register_hooks(): void;
	public static function get_name(): string;
}
```

**Example: CMB2 Integration**

```php
namespace MyPlugin\Integrations;

use CodeSoup\Options\Integrations\IntegrationInterface;
use CodeSoup\Options\Manager;

class CMB2 implements IntegrationInterface {

	private Manager $manager;

	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	public static function is_available(): bool {
		return class_exists( 'CMB2' );
	}

	public function register_hooks(): void {
		if ( ! self::is_available() ) {
			add_action( 'admin_notices', array( $this, 'show_missing_notice' ) );
			return;
		}
		add_action( 'cmb2_admin_init', array( $this, 'register_metaboxes' ) );
	}

	public static function get_name(): string {
		return 'CMB2';
	}

	public function show_missing_notice(): void {
		echo '<div class="notice notice-error"><p>CMB2 plugin is required but not installed.</p></div>';
	}

	public function register_metaboxes(): void {
		$pages = $this->manager->get_pages();
		$config = $this->manager->get_config();

		foreach ( $pages as $page ) {
			$cmb = new_cmb2_box(
				array(
					'id'           => $config['prefix'] . $page->get_id(),
					'title'        => $page->get_title(),
					'object_types' => array( $config['post_type'] ),
					'show_on'      => array(
						'key'   => 'post_name',
						'value' => $config['prefix'] . $page->get_id(),
					),
				)
			);

			// Add fields based on page ID
			if ( 'general' === $page->get_id() ) {
				$cmb->add_field(
					array(
						'name' => 'Site Title',
						'id'   => 'site_title',
						'type' => 'text',
					)
				);

				$cmb->add_field(
					array(
						'name' => 'Site Logo',
						'id'   => 'site_logo',
						'type' => 'file',
					)
				);
			}
		}
	}
}
```

**Registering Custom Integration:**

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'instance_key',
	array(
		'integrations' => array(
			// Disable ACF
			'acf'  => array(
				'enabled' => false,
			),
			// Enable custom CMB2 integration
			'cmb2' => array(
				'enabled' => true,
				'class'   => 'MyPlugin\\Integrations\\CMB2',
			),
		),
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
```

**Integration Lifecycle:**

1. **Construction** - Integration receives Manager instance
2. **Availability Check** - `is_available()` determines if integration can run
3. **Hook Registration** - `register_hooks()` sets up WordPress hooks
4. **Execution** - Integration handles its own field rendering and saving

**Best Practices:**

- Check availability - Verify required plugins/classes exist
- Fail gracefully - Return false from `is_available()` if requirements not met
- Use Manager instance - Access Manager config via `$this->manager->get_config()`
- Handle saving - Integration should save its own data
- Log errors - Use `$this->manager->get_logger()` for debugging

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
	'debug'          => false,                      // Enable error logging
	'integrations'   => array(                      // Integration configuration
		'acf' => array(
			'enabled' => true,
			'class'   => 'CodeSoup\\Options\\Integrations\\ACF\\Init',
		),
	),
)
```

### Menu Placement

**Top-level menu (default):**

```php
Manager::create(
	'main_options',
	array(
		'menu_label'    => 'Main Options',
		'menu_icon'     => 'dashicons-admin-settings',
		'menu_position' => 50,
	)
);
```

**Submenu under existing WordPress menu:**

```php
// Under Settings
Manager::create(
	'site_settings',
	array(
		'menu_label'  => 'Site Settings',
		'parent_menu' => 'options-general.php',
	)
);

// Under Appearance
Manager::create(
	'theme_options',
	array(
		'menu_label'  => 'Theme Options',
		'parent_menu' => 'themes.php',
	)
);
```

**Common parent menu values:**

```php
'options-general.php'       // Settings
'tools.php'                 // Tools
'themes.php'                // Appearance
'plugins.php'               // Plugins
'users.php'                 // Users
'upload.php'                // Media
'edit.php'                  // Posts
'edit.php?post_type=page'   // Pages
'woocommerce'               // WooCommerce (if installed)
```

**Notes:**
- When `parent_menu` is set, `menu_icon` and `menu_position` are ignored
- User must have capability for at least one page to see the menu

### Debug Mode

Controls error logging to PHP error log:

```php
Manager::create(
	'site_settings',
	array(
		'debug' => true,  // Enable logging
	)
);
```

- **`false`** (default): All logging disabled
- **`true`**: Errors, warnings, and info messages logged to error_log
- Debug messages only logged when both `debug` is `true` AND `WP_DEBUG` is enabled

### Disable Styles and Scripts

```php
Manager::create(
	'site_settings',
	array(
		'disable_styles'  => true,
		'disable_scripts' => true,
	)
);
```

Default: `false`

### UI Mode

```php
Manager::create(
	'site_settings',
	array(
		'ui_mode' => 'pages',  // or 'tabs'
	)
);
```

- **`'pages'`** (default) - Works with ACF/CMB2, each page is separate
- **`'tabs'`** - Native metaboxes only, everything on one page

If integrations are enabled, uses pages mode automatically.

See `docs/ui-modes.md` for details.

## Page Configuration

```php
array(
	'id'          => 'general',           // Required: Unique page ID
	'title'       => 'General Settings',  // Required: Page title
	'capability'  => 'manage_options',    // Required: User capability
	'description' => 'Site settings',     // Optional: Page description (stored in post_excerpt)
)
```

## Metabox Configuration

```php
array(
	'page'     => 'general',                    // Required: Page ID
	'title'    => 'Custom Fields',              // Required: Metabox title
	'path'     => __DIR__ . '/template.php',    // Required: Template file path
	'context'  => 'normal',                     // Optional: normal, side, advanced
	'priority' => 'default',                    // Optional: high, core, default, low
	'order'    => 10,                           // Optional: Display order
	'class'    => 'custom-metabox-class',       // Optional: Custom CSS class for postbox
	'args'     => array(),                      // Optional: Custom data for template
)
```

**Template Variables:**

Available in metabox templates:
- `$post` - Current options post object
- `$args` - Custom arguments from metabox config

## Troubleshooting

**ACF field groups not showing:**
- Verify ACF is installed and active
- Check location rules match your page ID exactly
- Clear WordPress object cache

**Options not saving:**
- Check user has required capability
- Review WordPress debug log for errors
- Verify ACF field group is published (for ACF)
- Verify nonce is present (for native metaboxes)

**Integration not loading:**
- Check `is_available()` returns true
- Verify required plugin/framework is installed
- Check admin notices for error messages

## Complete Examples

### Example 1: Basic ACF Setup

```php
use CodeSoup\Options\Manager;

$manager = Manager::create( 'acf_settings' );

$manager->register_pages(
	array(
		array( 'id' => 'general', 'title' => 'General', 'capability' => 'manage_options' ),
		array( 'id' => 'header', 'title' => 'Header', 'capability' => 'manage_options' ),
		array( 'id' => 'footer', 'title' => 'Footer', 'capability' => 'manage_options' ),
	)
);

$manager->init();

// Then create ACF field groups and assign them using "CodeSoup Options" location rule
```

### Example 2: Native Metaboxes

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'basic_settings',
	array(
		'menu_label'    => 'Site Settings',
		'menu_icon'     => 'dashicons-admin-settings',
		'integrations'  => array(
			'acf' => array( 'enabled' => false ),
		),
	)
);

$manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General Settings',
		'capability' => 'manage_options',
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

// Add save_post hook to handle saving (see "Saving Data with Native Metaboxes" above)
```

### Example 3: Submenu Placement

```php
use CodeSoup\Options\Manager;

// Under Settings menu
$settings_manager = Manager::create(
	'site_settings',
	array(
		'menu_label'  => 'Site Settings',
		'parent_menu' => 'options-general.php',
	)
);

$settings_manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General Settings',
		'capability' => 'manage_options',
	)
);

$settings_manager->init();
```

## Why Custom Post Types?

- **Revision History** - Track changes over time
- **Post Locking** - Prevent concurrent edits
- **Better Organization** - Multiple option pages with capability control
- **Built-in ACF Integration** - Works out of the box with Advanced Custom Fields
- **Extensible** - Can be extended to use with CMB2, MetaBox.io, Carbon Fields, or native metaboxes


