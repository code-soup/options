# Customization Guide

You can customize the header, sidebar, and styling.

## Header

### Custom Logo

```php
add_filter( 'codesoup_options_header_logo', function( $logo_url, $instance_key ) {
    return get_stylesheet_directory_uri() . '/images/logo.png';
}, 10, 2 );
```

### Custom Template

```php
add_filter( 'codesoup_options_header_template', function( $template_path, $instance_key ) {
    return get_stylesheet_directory() . '/templates/header.php';
}, 10, 2 );
```

Variables available in your template: `$logo_url`, `$menu_label`

## Sidebar (Tabs Mode Only)

```php
add_filter( 'codesoup_options_sidebar_template', function( $template_path, $instance_key ) {
    return __DIR__ . '/templates/sidebar.php';
}, 10, 2 );
```

Variables available in your template: `$this` (AdminPage instance)



## Styling

### Disable Default Assets

```php
$manager = Manager::create(
    'my_settings',
    array(
        'disable_styles'  => true,
        'disable_scripts' => true,
    )
);
```

### Add Custom CSS

```php
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_style(
        'my-options-css',
        get_stylesheet_directory_uri() . '/css/options.css'
    );
} );
```
