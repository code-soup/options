# Filters and Hooks

## Filters

### `codesoup_options_header_logo`

Change the header logo.

```php
add_filter( 'codesoup_options_header_logo', function( $logo_url, $instance_key ) {
    return get_stylesheet_directory_uri() . '/images/logo.png';
}, 10, 2 );
```

### `codesoup_options_header_template`

Replace the header template.

```php
add_filter( 'codesoup_options_header_template', function( $template_path, $instance_key ) {
    return get_stylesheet_directory() . '/templates/header.php';
}, 10, 2 );
```

Template variables: `$logo_url`, `$menu_label`

### `codesoup_options_sidebar_template`

Replace the sidebar template (tabs mode).

```php
add_filter( 'codesoup_options_sidebar_template', function( $template_path, $instance_key ) {
    return __DIR__ . '/templates/sidebar.php';
}, 10, 2 );
```

Template variables: `$this` (AdminPage instance)

### `codesoup_options_sidebar_ads`

Customize sidebar ads.

```php
add_filter( 'codesoup_options_sidebar_ads', function( $ads, $instance_key ) {
    return array(
        array(
            'type'  => 'text',
            'title' => 'Links',
            'items' => array(
                array( 'label' => 'Documentation', 'link' => 'https://...' ),
            ),
        ),
    );
}, 10, 2 );
```
