# Migration Guide: v1.0 to v1.1

## Overview

Version 1.1.0 introduces a new nested configuration structure for better organization. The old flat structure is still fully supported through a backward compatibility layer.

## Breaking Changes

### Configuration Structure

Configuration options are now organized into logical groups:

**Old (v1.0):**
```php
Manager::create(
	'site_settings',
	array(
		'menu_label'       => 'Settings',
		'menu_icon'        => 'dashicons-admin-settings',
		'menu_position'    => 50,
		'parent_menu'      => null,
		'ui_mode'          => 'pages',
		'tab_position'     => 'top',
		'templates_dir'    => null,
		'disable_styles'   => false,
		'disable_scripts'  => false,
		'disable_branding' => false,
	)
);
```

**New (v1.1):**
```php
Manager::create(
	'site_settings',
	array(
		'menu'   => array(
			'label'    => 'Settings',
			'icon'     => 'dashicons-admin-settings',
			'position' => 50,
			'parent'   => null,
		),
		'ui'     => array(
			'mode'          => 'pages',
			'tab_position'  => 'top',
			'templates_dir' => null,
		),
		'assets' => array(
			'disable_styles'  => false,
			'disable_scripts' => false,
			'disable_branding' => false,
		),
	)
);
```

## Do You Need to Migrate?

**Yes, you should migrate.** While the old flat configuration structure continues to work through a backward compatibility layer, you will see deprecation warnings in the WordPress admin until you migrate.

**Deprecation Warning:**

If you use old configuration keys, you'll see this admin notice:

```
CodeSoup Options (your_instance): Deprecated configuration detected: menu_label, ui_mode, disable_styles.
Please migrate to nested structure. View Migration Guide
```

**Reasons to migrate:**
- Remove deprecation warnings
- Better code organization
- Improved readability
- Future compatibility
- The backward compatibility layer may be removed in v2.0

## Migration Mapping

| Old Key | New Key |
|---------|---------|
| `menu_label` | `menu.label` |
| `menu_icon` | `menu.icon` |
| `menu_position` | `menu.position` |
| `parent_menu` | `menu.parent` |
| `ui_mode` | `ui.mode` |
| `tab_position` | `ui.tab_position` |
| `templates_dir` | `ui.templates_dir` |
| `disable_styles` | `assets.disable_styles` |
| `disable_scripts` | `assets.disable_scripts` |
| `disable_branding` | `assets.disable_branding` |

Unchanged keys:
- `post_type`
- `prefix`
- `revisions`
- `debug`
- `integrations`

Removed keys:
- `cache_duration` - Cache system removed from plugin (v1.2.0+)

## Accessing Config Values

The `get_config()` method supports both old deprecated keys and direct array access:

```php
$manager = Manager::get( 'site_settings' );

// Old keys still work (backward compatibility)
$label = $manager->get_config( 'menu_label' );

// Direct array access (recommended)
$config = $manager->get_config();
$label = $config['menu']['label'];
```

## Migration Steps

### 1. Update Configuration

Replace old flat config with new nested structure:

```php
// Before
$manager = Manager::create(
	'site_settings',
	array(
		'menu_label'    => 'Settings',
		'ui_mode'       => 'tabs',
		'disable_styles' => true,
	)
);

// After
$manager = Manager::create(
	'site_settings',
	array(
		'menu'   => array(
			'label' => 'Settings',
		),
		'ui'     => array(
			'mode' => 'tabs',
		),
		'assets' => array(
			'disable_styles' => true,
		),
	)
);
```

### 2. Update get_config() Calls (Optional)

Update code that accesses config values to use direct array access:

```php
// Before
$ui_mode = $manager->get_config( 'ui_mode' );

// After (recommended)
$config = $manager->get_config();
$ui_mode = $config['ui']['mode'];
```

**Note:** This step is optional as old deprecated keys still work.

### 3. Test Your Implementation

After migrating:

1. Verify admin menu displays correctly
2. Check UI mode (pages/tabs) works as expected
3. Confirm asset loading (CSS/JS)
4. Test custom templates if using `templates_dir`
5. Verify branding settings

## Backward Compatibility

The backward compatibility layer is provided to ease migration, but deprecated keys will trigger admin warnings.

**Timeline:**
- **v1.1.x** - Backward compatibility with deprecation warnings
- **v2.0.0** - Backward compatibility layer may be removed

We strongly recommend migrating to avoid issues with future updates.

## Questions?

- Review the updated [API documentation](api.md)
- Check [configuration examples](../README.md#configuration)
- See [Skills documentation](../skills/codesoup-options-setup/SKILL.md)
