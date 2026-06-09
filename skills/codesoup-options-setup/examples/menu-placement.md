# Menu Placement Examples

Configure where your options pages appear in the WordPress admin menu.

## Top-Level Menu (Default)

```php
use CodeSoup\Options\Manager;

Manager::create(
	'main_options',
	array(
		'menu_label'    => 'Main Options',
		'menu_icon'     => 'dashicons-admin-settings',
		'menu_position' => 50,
	)
);
```

## Submenu Under Settings

```php
use CodeSoup\Options\Manager;

Manager::create(
	'site_settings',
	array(
		'menu_label'  => 'Site Settings',
		'parent_menu' => 'options-general.php',
	)
);
```

## Submenu Under Appearance

```php
use CodeSoup\Options\Manager;

Manager::create(
	'theme_options',
	array(
		'menu_label'  => 'Theme Options',
		'parent_menu' => 'themes.php',
	)
);
```

## Common Parent Menu Values

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

## Notes

- When `parent_menu` is set, `menu_icon` and `menu_position` are ignored
- User must have capability for at least one page to see the menu

