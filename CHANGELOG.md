# Changelog

All notable changes to this project will be documented in this file.

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
