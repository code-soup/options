# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2026-04-10

### Breaking Changes

- **Configuration structure changed to nested groups** - Old flat structure still supported via backward compatibility layer
  - `menu_label` → `menu.label`
  - `menu_icon` → `menu.icon`
  - `menu_position` → `menu.position`
  - `parent_menu` → `menu.parent`
  - `ui_mode` → `ui.mode`
  - `tab_position` → `ui.tab_position`
  - `templates_dir` → `ui.templates_dir`
  - `disable_styles` → `assets.disable_styles`
  - `disable_scripts` → `assets.disable_scripts`
  - `disable_branding` → `assets.disable_branding`

### Added

- Added tabbed UI mode for organizing options pages
- Added `Admin_Notice` utility class for rendering admin notices
- Added `disable_branding` config option to remove CodeSoup header
- Added `templates_dir` config option to override template directory
- Added `get_template_path()` method to Manager for custom template loading
- Added backward compatibility layer for old flat configuration structure

### Changed

- Refactored configuration structure into logical groups (menu, ui, assets)
- Refactored admin notice rendering to use `Admin_Notice` utility
- Updated all documentation to reflect new nested configuration structure

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
