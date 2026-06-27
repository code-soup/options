# Changelog

All notable changes to this project will be documented in this file.

## [1.1.1] - 2026-06-27

### Fixed

- Fixed screen ID generation for WordPress admin pages to correctly handle both top-level and submenu pages
- Fixed screen matching logic in `Post_Manager::is_relevant_screen()` to work with updated screen IDs

## [1.1.0] - 2026-06-09

### Breaking Changes

- **Configuration structure changed to nested groups** - Old flat structure deprecated but supported via backward compatibility layer
- **Cache system removed** - Plugin no longer implements internal caching, options read directly from database

### Added

- Tabbed UI mode for organizing options pages
- Automatic capability management - adds post type and custom page capabilities to administrator role
- `Path_Helper` and `Security_Helper` utility classes
- `Admin_Notice` utility class
- `get_metabox_registry()` and `get_template_path()` methods to Manager class
- `Post_Manager::regenerate_capabilities()` method to force capability refresh
- `disable_branding` and `templates_dir` config options
- Backward compatibility layer for old configuration structure
- Validation for metabox page ID matching

### Changed

- Refactored configuration into logical groups (menu, ui, assets)
- Simplified data retrieval without internal cache layer

### Removed

- Cache system (`includes/core/class-cache.php`)
- `cache_duration` configuration option

### Migration

See [docs/migration-v1.1.md](docs/migration-v1.1.md) for upgrade guide. Existing code will continue to work but will show deprecation warnings until migrated.

## [1.0.1] - 2026-03-01

### Fixed

- Fixed infinite loop issue in `Manager::save_options()` by replacing `wp_update_post()` with direct database update
- Added `clean_post_cache()` call to ensure fresh data after save
- Updated documentation to clarify infinite loop prevention

### Added

- Custom CSS class support for metaboxes via `class` parameter
- Support for multiple CSS classes (accepts string or array)
- Automatic sanitization and deduplication of CSS classes
- Disabled bulk actions dropdown in WordPress admin list view
- Disabled bulk select checkboxes in WordPress admin list view
- Disabled months/dates filter dropdown in WordPress admin list view
- Made options page title field read-only in WordPress admin edit screen
- Added server-side protection to prevent title updates even if UI is bypassed

### Removed

- Removed `Manager::denormalize_slug()` method (was a no-op that just returned the slug unchanged)

## [1.0.0] - 2026-02-15

Initial release.

### Features

- Framework-agnostic WordPress options manager using custom post types
- Built-in ACF integration (enabled by default)
- Support for custom integrations (CMB2, MetaBox.io, Carbon Fields, native metaboxes)
- Capability-based access control per page
- Object caching with automatic invalidation
- Revision history support
- Submenu and top-level menu placement options
- Comprehensive documentation and examples
