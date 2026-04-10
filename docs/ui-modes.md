# UI Modes

## Pages Mode (Default)

Each page is a separate post editor. Works with ACF, CMB2, and other field frameworks.

### Setup

```php
$manager = Manager::create(
    'site_settings',
    array(
        'ui_mode' => 'pages',  // or omit (default)
    )
);
```

## Tabs Mode

Single page with tabs. Native metaboxes only (no ACF/CMB2).

### Setup

```php
$manager = Manager::create(
    'site_settings',
    array(
        'ui_mode'      => 'tabs',
        'tab_position' => 'top',               // 'top' or 'left'
        'integrations' => array(
            'acf' => array( 'enabled' => false ),  // Must disable
        ),
    )
);
```

**Tab Position:**
- `'top'` (default) - Horizontal tabs above content
- `'left'` - Vertical tabs in left sidebar

Note: If any integration is enabled, the plugin uses Pages mode automatically.

See [Tabbed UI](tabbed-ui.md) for complete documentation.
