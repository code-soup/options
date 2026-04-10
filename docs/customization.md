# Customization Guide

You can customize templates using filters or by overriding the entire templates directory.

## Method 1: Custom Templates Directory

Override the entire templates directory to use your own template files.

```php
Manager::create(
	'site_settings',
	array(
		'templates_dir' => get_stylesheet_directory() . '/codesoup-templates',
	)
);
```

**Template Structure:**

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
- Only create templates you want to customize
- Plugin falls back to built-in templates for missing files
- Copy from `includes/templates/` as starting point

---

## Method 2: Filters (Individual Templates)

Override individual templates using filters.

### Header

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

Variables available in your template: `$logo_url`, and access to config via `$this->manager->get_config()`

## Sidebar (Tabs Mode Only)

```php
add_filter( 'codesoup_options_sidebar_template', function( $template_path, $instance_key ) {
    return __DIR__ . '/templates/sidebar.php';
}, 10, 2 );
```

Variables available in your template: `$this` (Admin_Page instance)



## Styling

### Disable Default Assets

```php
$manager = Manager::create(
    'my_settings',
    array(
        'assets' => array(
            'disable_styles'  => true,
            'disable_scripts' => true,
        ),
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
