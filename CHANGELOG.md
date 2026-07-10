# Changelog

## [Unreleased]

### Fixed

- Asset URL generation for PHP-Scoper compatibility (standalone plugin + vendor package)
- Dual-mode URL resolution with automatic fallback

## [1.2.2] - 2026-07-06

### Added

- `hide_save_button` page configuration option

### Fixed

- Screen ID generation for top-level and submenu admin pages
- Screen matching logic in `Post_Manager::is_relevant_screen()`

## [1.1.1] - 2026-06-27

### Fixed

- Screen ID generation for top-level and submenu pages
- Screen matching logic in `Post_Manager::is_relevant_screen()`

## [1.1.0] - 2026-06-09

### Breaking Changes

- Configuration structure changed to nested groups (backward compatible)
- Cache system removed

### Added

- Tabbed UI mode
- Automatic capability management
- `Path_Helper` and `Security_Helper` utility classes
- `Admin_Notice` utility class
- `get_metabox_registry()` and `get_template_path()` methods
- `Post_Manager::regenerate_capabilities()` method
- `disable_branding` and `templates_dir` config options
- Backward compatibility layer
- Metabox page ID validation

### Changed

- Configuration refactored into groups (menu, ui, assets)
- Data retrieval simplified

### Removed

- Cache system
- `cache_duration` config option

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
