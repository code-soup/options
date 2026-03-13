# Tabbed UI Mode

The plugin supports two UI modes for displaying options pages:

- **Pages Mode** (default) - Uses WordPress's native post list interface
- **Tabs Mode** - Custom tabbed interface with horizontal or vertical tab navigation

## Configuration

Enable tabbed UI mode by setting `ui_mode` to `'tabs'` in your Manager configuration:

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
    'site_settings',
    array(
        'menu_label'   => 'Site Settings',
        'ui_mode'      => 'tabs',        // Enable tabbed UI
        'tab_position' => 'left',        // 'top', 'left', or 'right'
        'integrations' => array(
            'acf' => array( 'enabled' => false ),
        ),
    )
);
```

## Tab Positions

### Top Tabs (Horizontal)

```php
'tab_position' => 'top'
```

Displays tabs horizontally above the content area, similar to WordPress's native tab interface.

### Left Tabs (Vertical Sidebar)

```php
'tab_position' => 'left'
```

Displays tabs in a vertical sidebar on the left side, similar to ACF's options page style.

### Right Tabs (Vertical Sidebar)

```php
'tab_position' => 'right'
```

Displays tabs in a vertical sidebar on the right side.

## Features

### Tab Navigation

- **Keyboard Navigation**: Use arrow keys to navigate between tabs
- **Accessibility**: Full ARIA support for screen readers
- **Active State**: Current tab is clearly highlighted

### Form Handling

- **Auto-save Warning**: Warns users about unsaved changes when switching tabs
- **Success Messages**: Displays confirmation after saving
- **Nonce Security**: All form submissions are protected with WordPress nonces

### Metabox Support

- Works with native WordPress metaboxes
- Maintains standard metabox contexts (normal, side, advanced)
- Supports all metabox features (drag-and-drop, collapse, etc.)

## Complete Example

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
    'site_settings',
    array(
        'menu_label'   => 'Site Settings',
        'ui_mode'      => 'tabs',
        'tab_position' => 'left',
        'integrations' => array(
            'acf' => array( 'enabled' => false ),
        ),
    )
);

// Register pages (these become tabs)
$manager->register_pages(
    array(
        array(
            'id'          => 'general',
            'title'       => 'General Settings',
            'capability'  => 'manage_options',
            'description' => 'General site configuration',
        ),
        array(
            'id'          => 'advanced',
            'title'       => 'Advanced Settings',
            'capability'  => 'manage_options',
            'description' => 'Advanced options',
        ),
    )
);

// Register metaboxes
$manager->register_metabox(
    array(
        'page'  => 'general',
        'title' => 'Site Information',
        'path'  => __DIR__ . '/templates/site-info.php',
    )
);

$manager->init();
```

## Switching Between Modes

You can switch between `pages` and `tabs` mode at any time without data loss. Both modes use the same underlying data storage (WordPress posts).

```php
// Pages mode (default)
'ui_mode' => 'pages'

// Tabs mode
'ui_mode' => 'tabs'
```

## Styling

The tabbed UI uses WordPress's native styling where possible and includes custom CSS for tab-specific elements. The interface is responsive and adapts to different screen sizes.

### Custom Styling

You can add custom CSS to further customize the appearance:

```php
add_action('admin_enqueue_scripts', function() {
    wp_add_inline_style('codesoup-options-tabs', '
        .codesoup-options-vertical-tabs {
            background: #f0f0f1;
        }
    ');
});
```

## Limitations

- ACF integration is not currently supported in tabs mode
- Tabs mode requires JavaScript to be enabled
- Tab order follows page registration order (no custom ordering)

## See Also

- [Native Metaboxes](native.md)
- [API Reference](api.md)
- [Examples](examples/)

