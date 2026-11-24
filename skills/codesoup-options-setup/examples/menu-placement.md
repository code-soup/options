# Menu Placement Examples

Configure where your options pages appear in the WordPress admin menu.

## Top-Level Menu (Default)

```php
use CodeSoup\Options\Manager;

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
```

## Submenu Under Settings

```php
use CodeSoup\Options\Manager;

Manager::create(
	'site_settings',
	array(
		'menu' => array(
			'label'  => 'Site Settings',
			'parent' => 'options-general.php',
		),
	)
);
```

## Submenu Under Appearance

```php
use CodeSoup\Options\Manager;

Manager::create(
	'theme_options',
	array(
		'menu' => array(
			'label'  => 'Theme Options',
			'parent' => 'themes.php',
		),
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

- When `menu.parent` is set, `menu.icon` and `menu.position` are ignored
- User must have capability for at least one page to see the menu
- Flat keys `menu_label` / `parent_menu` still work but are deprecated (see `docs/migration-v1.1.md`)
