# Tabbed UI Mode

You can display your options pages in two different ways:

- **Pages Mode** (default) - Each page gets its own editor, shown in a list table
- **Tabs Mode** - Everything on one admin page with tabs to switch between sections

## A Note About Tabs Mode

Tabs mode works with native WordPress metaboxes. If you're using a field framework like ACF, CMB2, MetaBox.io, or Carbon Fields, the plugin will use Pages Mode instead. This happens because these frameworks expect to work with individual post editors.

If you'd like to use Tabs mode:
1. Disable all field framework integrations in your config
2. Use native WordPress metaboxes (register them with `register_metabox()`)

## Setting Up Tabs Mode

To use tabs mode, set `ui_mode` to `'tabs'` and disable integrations:

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
    'site_settings',
    array(
        'menu_label'   => 'Site Settings',
        'ui_mode'      => 'tabs',        // Enable tabbed UI (requires no integrations)
        'integrations' => array(
            'acf' => array( 'enabled' => false ),  // Required for tabs mode
        ),
    )
);
```

## How It Looks

Tabs mode gives you a 3-column layout:

```
┌────────┬──────────────────┬─────────┐
│  Tabs  │   Main Content   │ Sidebar │
│ 200px  │    (flexible)    │  240px  │
└────────┴──────────────────┴─────────┘
```

- **Left Column (200px)**: Vertical tab navigation
- **Middle Column (flexible)**: Main content area with metaboxes
- **Right Column (240px)**: Customizable sidebar (advertising, links, etc.)

### On Mobile

On smaller screens (782px or less), everything stacks vertically:

```
┌─────────────────────────────────────┐
│              Tabs                   │
├─────────────────────────────────────┤
│          Main Content               │
├─────────────────────────────────────┤
│            Sidebar                  │
└─────────────────────────────────────┘
```

## Customizing the Header

You can add your own logo and change the header template if you'd like:

```php
// Custom logo
add_filter( 'codesoup_options_header_logo', function( $logo_url, $instance_key ) {
    if ( 'site_settings' !== $instance_key ) {
        return $logo_url;
    }
    return get_stylesheet_directory_uri() . '/images/logo.png';
}, 10, 2 );

// Custom header template
add_filter( 'codesoup_options_header_template', function( $template_path, $instance_key ) {
    if ( 'site_settings' !== $instance_key ) {
        return $template_path;
    }
    return get_stylesheet_directory() . '/templates/admin-header.php';
}, 10, 2 );
```

## Customizing the Sidebar

The sidebar on the right (240px wide) can show whatever you want:

```php
add_filter( 'codesoup_options_sidebar_template', function( $template_path, $instance_key ) {
    if ( 'site_settings' !== $instance_key ) {
        return $template_path;
    }

    // Use the built-in advertising template
    return WP_PLUGIN_DIR . '/codesoup-options/includes/ui/templates/sidebar-advertising.php';

    // Or use your custom template
    // return __DIR__ . '/templates/custom-sidebar.php';
}, 10, 2 );
```

### Advertising Sidebar

The plugin includes a ready-to-use advertising sidebar template. Customize the ads:

```php
add_filter( 'codesoup_options_sidebar_ads', function( $ads, $instance_key ) {
    if ( 'site_settings' !== $instance_key ) {
        return $ads;
    }

    return array(
        // Text widget with links
        array(
            'type'  => 'text',
            'title' => '⭐ Premium Version',
            'items' => array(
                array( 'label' => 'Unlock Features', 'link' => 'https://example.com' ),
                array( 'label' => 'Get Support', 'link' => 'https://example.com/support' ),
            ),
        ),
        // Banner ad
        array(
            'type'        => 'banner',
            'image'       => '/path/to/banner.jpg',
            'link'        => 'https://example.com',
            'title'       => 'Special Offer',
            'description' => 'Limited time discount',
        ),
    );
}, 10, 2 );
```

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

- **Native metaboxes only**: Tabs mode requires native WordPress metaboxes
- **Contexts**: Supports normal and advanced contexts (side context not displayed)
- **Postbox features**: Supports collapse/expand functionality
- **Single column layout**: Content area uses full width (no side column)

## Complete Example

```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
    'site_settings',
    array(
        'menu_label'   => 'Site Settings',
        'ui_mode'      => 'tabs',
        'integrations' => array(
            'acf' => array( 'enabled' => false ),  // Required for tabs mode
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

