# File Structure

## Directory Organization

```
includes/
├── class-autoloader.php          # Main autoloader with directory mapping
├── class-manager.php             # Core manager class
│
├── core/                          # Core functionality
│   ├── class-cache.php           # Caching system
│   ├── class-logger.php          # Logging system
│   ├── class-page.php            # Page entity
│   └── class-metabox.php         # Metabox entity
│
├── admin/                         # Admin UI classes
│   ├── class-admin-header.php    # Custom header
│   ├── class-admin-page.php      # Tabs mode admin page
│   ├── class-form-handler.php    # Form submission handler
│   ├── class-pages-list-page.php # Pages mode list page
│   └── class-pages-list-table.php # WP_List_Table implementation
│
├── utilities/                     # Utility classes
│   └── class-migration.php       # Migration helper
│
├── integrations/                  # Field framework integrations
│   ├── integration-interface.php
│   └── acf/
│       ├── class-init.php
│       └── class-location.php
│
└── templates/                     # All template files
    ├── header/
    │   └── default.php           # Default header template
    ├── sidebar/
    │   ├── advertising.php       # Advertising sidebar
    │   └── banner-sidebar.php    # Sidebar wrapper
    ├── tabs/
    │   ├── wrapper.php           # Main tab wrapper
    │   ├── content.php           # Tab content
    │   ├── navigation-horizontal.php
    │   └── navigation-vertical.php
    └── metabox/
        └── actions.php           # Actions metabox template
```

## Template Paths

### Header
- Default: `includes/templates/header/default.php`
- Filter: `codesoup_options_header_template`

### Sidebar
- Default: `includes/templates/sidebar/advertising.php`
- Filter: `codesoup_options_sidebar_template`

### Tabs
- Wrapper: `includes/templates/tabs/wrapper.php`
- Content: `includes/templates/tabs/content.php`
- Navigation: `includes/templates/tabs/navigation-*.php`

### Metabox
- Actions: `includes/templates/metabox/actions.php`

## Autoloader

The autoloader maps classes to subdirectories:

```php
Cache            → includes/core/class-cache.php
AdminPage        → includes/admin/class-admin-page.php
Migration        → includes/utilities/class-migration.php
Integrations\ACF → includes/integrations/acf/...
```

## Examples

Example files are in `docs/examples/` for reference only.
