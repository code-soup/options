---
name: codesoup-options-setup
description: Set up and configure CodeSoup Options plugin for WordPress. Create options pages using ACF (Advanced Custom Fields), native metaboxes, or custom integrations (CMB2, MetaBox.io, Carbon Fields). Configure menu placement, post types, capabilities, and integrations. Use when setting up WordPress options pages, integrating with ACF or field frameworks, creating custom admin pages, configuring admin menu placement, or managing options with revision history.
license: GPL-3.0-or-later
metadata:
  author: code-soup
  version: "1.0.0"
  package: codesoup/options
---

# CodeSoup Options Setup

Set up WordPress options pages using custom post types with ACF integration, native metaboxes, or custom field frameworks.

## Examples

Complete working examples are available in the `examples/` directory:

- [ACF Integration](examples/acf-integration.md) - Setup with Advanced Custom Fields
- [Native Metaboxes](examples/native-metaboxes.md) - Full control without frameworks
- [Custom Integration](examples/custom-integration.md) - CMB2, MetaBox.io, Carbon Fields
- [Menu Placement](examples/menu-placement.md) - Configure admin menu location

## When to Use This Skill

- Setting up WordPress options/settings pages
- Integrating with Advanced Custom Fields (ACF)
- Creating custom admin pages without ACF
- Integrating with CMB2, MetaBox.io, or Carbon Fields
- Configuring WordPress admin menu placement
- Managing options with revision history and post locking

## Requirements

- PHP >= 7.2
- WordPress >= 6.0
- Optional: ACF, CMB2, MetaBox.io, or Carbon Fields

## Installation

### Via Composer

```bash
composer require codesoup/options
```

### As WordPress Plugin

1. Download and extract to `wp-content/plugins/codesoup-options`
2. Activate the plugin
3. Add configuration to your theme or plugin

## Setup Methods

### Method 1: ACF Integration (Recommended)

ACF integration is enabled by default. No additional configuration needed.

**Key Features:**
- Dual storage (postmeta + post content) for compatibility and performance
- Use ACF's `get_field()` functions or Manager's `get_options()` for fast retrieval
- Maintain ACF field validation and formatting
- Can be combined with native metaboxes

**See:** [ACF Integration Example](examples/acf-integration.md) for complete setup code and field group assignment.

### Method 2: Native Metaboxes (No Framework)

Use when you want full control over HTML fields without any framework.

**Key Features:**
- Full control over HTML and field rendering
- No framework dependencies
- You handle field templates and data sanitization
- Requires save_post hook for saving data

**Important:** You are responsible for sanitizing all input data. Use appropriate sanitization functions (sanitize_text_field, sanitize_email, etc.)

**See:** [Native Metaboxes Example](examples/native-metaboxes.md) for complete setup, field templates, and save handlers.

**Error Handling:**

`save_options()` returns `WP_Error` with code `'save_options_failed'` when:
- Post ID is missing or invalid
- Nonce is missing or invalid
- Data is missing or not an array
- Post doesn't exist
- Post is not the correct post type
- Nonce verification fails
- User lacks permission to edit the post
- Post update fails

### Method 3: Custom Integration (CMB2, MetaBox.io, Carbon Fields)

Create custom integrations for any field framework.

**See:** [Custom Integration Example](examples/custom-integration.md) for complete CMB2 integration implementation including IntegrationInterface, availability checking, hook registration, and best practices.

## Configuration Options

**Available configuration options:**
- `post_type` - Custom post type name
- `prefix` - Post slug prefix
- `menu_label` - Admin menu label
- `menu_icon` - Dashicon or URL
- `menu_position` - Menu position (1-100)
- `parent_menu` - Parent menu slug for submenu
- `revisions` - Enable revision history
- `cache_duration` - Cache duration in seconds
- `debug` - Enable error logging
- `integrations` - Integration configuration

**See:** [Menu Placement Example](examples/menu-placement.md) for top-level menus, submenus, and common parent menu values.

### Debug Mode

- **`false`** (default): All logging disabled
- **`true`**: Errors, warnings, and info messages logged to error_log
- Debug messages only logged when both `debug` is `true` AND `WP_DEBUG` is enabled

## Page Configuration

**Required fields:**
- `id` - Unique page ID
- `title` - Page title
- `capability` - User capability

**Optional fields:**
- `description` - Page description (stored in post_excerpt)

## Metabox Configuration

**Required fields:**
- `page` - Page ID
- `title` - Metabox title
- `path` - Template file path

**Optional fields:**
- `context` - normal, side, advanced
- `priority` - high, core, default, low
- `order` - Display order
- `class` - Custom CSS class for postbox
- `args` - Custom data for template

**Template Variables:** `$post` (current options post object), `$args` (custom arguments from metabox config)

## Troubleshooting

**ACF field groups not showing:**
- Verify ACF is installed and active
- Check location rules match your page ID exactly
- Clear WordPress object cache

**Options not saving:**
- Check user has required capability
- Review WordPress debug log for errors
- Verify ACF field group is published (for ACF)
- Verify nonce is present (for native metaboxes)

**Integration not loading:**
- Check `is_available()` returns true
- Verify required plugin/framework is installed
- Check admin notices for error messages

## Complete Examples

All complete working examples with full code are available in the `examples/` directory:

- [ACF Integration](examples/acf-integration.md) - Basic ACF setup with field groups
- [Native Metaboxes](examples/native-metaboxes.md) - Full control without frameworks
- [Custom Integration](examples/custom-integration.md) - CMB2 integration example
- [Menu Placement](examples/menu-placement.md) - Top-level and submenu configurations

## Why Custom Post Types?

- **Revision History** - Track changes over time
- **Post Locking** - Prevent concurrent edits
- **Better Organization** - Multiple option pages with capability control
- **Built-in ACF Integration** - Works out of the box with Advanced Custom Fields
- **Extensible** - Can be extended to use with CMB2, MetaBox.io, Carbon Fields, or native metaboxes


