=== CodeSoup Options ===
Contributors: codesoup
Tags: options, settings, custom post type, acf, metabox, admin
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Framework-agnostic WordPress options manager using custom post types with built-in ACF support.

== Description ==

CodeSoup Options stores site settings in custom post types instead of the `wp_options` table. It supports multiple instances, per-page capabilities, native metaboxes, and integrations such as Advanced Custom Fields.

**Features**

* Pages mode and tabbed admin UI
* Automatic capability management for administrators
* Built-in ACF integration (optional)
* Extensible integration layer for other field frameworks
* Per-page option to hide the save button (read-only pages)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/codesoup-options`, or install via Composer in your theme/plugin.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Register pages and metaboxes from your theme or plugin using `CodeSoup\Options\Manager`.

See the [GitHub documentation](https://github.com/code-soup/codesoup-options) for configuration examples.

== Frequently Asked Questions ==

= Does this replace the Options API? =

No. It is an alternative storage model using custom post types for structured, capability-controlled option pages.

= Is ACF required? =

No. ACF integration is enabled by default but can be disabled in configuration.

== Changelog ==

= 1.3.0 =
* Asset URL fixes for standalone and Composer vendor installs
* Tabs mode save button and `hide_save_button` page option improvements

= 1.2.2 =
* `hide_save_button` page configuration
* Screen ID fixes for admin pages

See [CHANGELOG.md](https://github.com/code-soup/codesoup-options/blob/master/CHANGELOG.md) for full history.

== Upgrade Notice ==

= 1.3.0 =
Recommended update for tabbed UI save behavior and asset loading in Composer or scoped installs.
