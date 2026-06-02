# Sanitization Reference

## Overview

This document describes the consistent sanitization methods used throughout the CodeSoup Options plugin for post types, prefixes, and post names.

## Format Convention

**Configuration (stored in options/config):**
- **Post Types:** Use **underscores** (e.g., `site_settings_options`)
- **Prefixes:** Use **underscores** (e.g., `site_settings_options`)

**Post Names (wp_posts.post_name):**
- **Format:** `{prefix-with-dashes}-{page-id}`
- **Uses dashes only** (e.g., `site-settings-options-general`)
- Prefix converted from underscores to dashes
- Page ID normalized to dashes
- Fully controlled by our package - WordPress-friendly format

## Core Sanitization Methods

### 1. `Manager::normalize_slug( string $slug ): string`

**Purpose:** Normalize slugs for page IDs (post_name field) to use dashes.

**Usage:**
- Normalizing page IDs for post names
- Any slug that should use dashes

**Process:**
1. Convert to lowercase via `sanitize_title()`
2. Remove consecutive dashes and underscores, replace with single dash
3. Trim leading/trailing dashes

**Example:**
```php
$slug = Manager::normalize_slug( 'my_page__id' );
// Result: 'my-page-id'
```

### 2. `Manager::sanitize_post_type_value( string $value ): string` (private)

**Purpose:** Sanitize post_type and prefix configuration values to use underscores.

**Usage:**
- In Manager constructor for `$config['post_type']`
- In Manager constructor for `$config['prefix']`

**Process:**
1. Convert to lowercase
2. Remove invalid characters (keep only a-z, 0-9, dashes, underscores)
3. Replace dashes with underscores
4. Remove consecutive underscores
5. Trim leading/trailing underscores

**Example:**
```php
$value = self::sanitize_post_type_value( 'My-Post--Type!' );
// Result: 'my_post_type'
```

### 3. `Post_Manager::get_post_name( string $page_id ): string`

**Purpose:** Generate complete post name from page ID using the configured prefix.

**Usage:**
- Creating posts for pages
- Querying posts by page ID
- ACF location rules
- Debug output
- **ANYWHERE** you need the full post name

**Format:** `{prefix-with-dashes}-{page-id}`
- Prefix converted from underscores to dashes
- Page ID normalized to dashes
- Both parts combined with dash separator
- Final result uses dashes only (WordPress-friendly)

**Process:**
1. Get prefix from config (has underscores: `site_settings_options`)
2. Convert prefix to dashes (`site-settings-options`)
3. Normalize page ID to dashes
4. Combine with dash separator: `prefix-page-id`
5. Normalize complete post name (removes consecutive dashes)

**Example:**
```php
// Config prefix: 'site_settings_options', Page: 'general'
$post_name = $post_manager->get_post_name( 'general' );
// Result: 'site-settings-options-general'

// Config prefix: 'site_settings_options', Page: 'advanced_settings'
$post_name = $post_manager->get_post_name( 'advanced_settings' );
// Result: 'site-settings-options-advanced-settings'

// Config prefix: 'my_theme_options', Page: 'header-config'
$post_name = $post_manager->get_post_name( 'header-config' );
// Result: 'my-theme-options-header-config'
```

## Post Type Generation

**Location:** `Manager::__construct()` (lines 460-470)

**Default Generation:**
```php
$normalized_key    = self::sanitize_post_type_value( $instance_key );
$default_post_type = self::sanitize_post_type_value( $normalized_key . '_options' );
```

**Examples:**
- Instance: `'site_settings'` → Post Type: `'site_settings_options'`
- Instance: `'my-theme'` → Post Type: `'my_theme_options'`
- Instance: `'MY___OPTIONS'` → Post Type: `'my_options_options'`

## Prefix Generation

**Location:** `Manager::__construct()` (lines 460-470)

**Default Generation:**
```php
$normalized_key  = self::sanitize_post_type_value( $instance_key );
$default_prefix  = self::sanitize_post_type_value( $normalized_key . '_options' );
```

**Examples:**
- Instance: `'site_settings'` → Prefix: `'site_settings_options'`
- Instance: `'my-theme'` → Prefix: `'my_theme_options'`
- Instance: `'MY___OPTIONS'` → Prefix: `'my_options_options'`

## Post Name Generation

**Always use:** `Post_Manager::get_post_name( $page_id )`

**Format:** `{prefix-with-dashes}-{page-id}`

**Examples:**
- Config Prefix: `'site_settings_options'`, Page: `'general'` → Post Name: `'site-settings-options-general'`
- Config Prefix: `'my_theme_options'`, Page: `'header_settings'` → Post Name: `'my-theme-options-header-settings'`
- Config Prefix: `'site_settings_options'`, Page: `'advanced-config'` → Post Name: `'site-settings-options-advanced-config'`

## Where to Use These Methods

### ✅ ALWAYS Use `Post_Manager::get_post_name()`

1. **includes/core/class-post-manager.php**
   - `create_page()` - Creating new posts
   - `get_post_by_page_id()` - Querying posts
   - `ensure_pages_exist()` - Batch page creation

2. **includes/integrations/acf/class-location.php**
   - `get_values()` - ACF location rules

3. **includes/class-manager.php**
   - `debug()` - Debug output

4. **docs/examples/03-custom-integration.php**
   - `register_metaboxes()` - CMB2 integration

### ✅ ALWAYS Use `Manager::normalize_slug()`

1. **Migration operations**
   - Normalizing old prefix values
   - Normalizing old post_type values
   - Building new post names during migration

2. **Page ID normalization**
   - Before concatenating with prefix

### ❌ NEVER Do This

```php
// ❌ WRONG - Direct concatenation
$post_name = $config['prefix'] . $page->id;

// ❌ WRONG - Manual normalization
$post_name = str_replace( '_', '-', $prefix . $page_id );

// ❌ WRONG - Using prefix with underscores in post_name
$post_name = $config['prefix'] . '_' . $page->id;

// ✅ CORRECT - Always use Post_Manager
$post_name = $post_manager->get_post_name( $page->id );
```

## Summary

| What | Method | Separator | Storage |
|------|--------|-----------|---------|
| Post Type | `sanitize_post_type_value()` | Underscores | Config/DB |
| Prefix | `sanitize_post_type_value()` | Underscores | Config/DB |
| Page ID | `normalize_slug()` | Dashes | Internal |
| Post Name | `Post_Manager::get_post_name()` | All dashes | wp_posts.post_name |

## Format Examples

| Component | Input | Output | Format |
|-----------|-------|--------|--------|
| Post Type (config) | `'site-settings'` | `'site_settings_options'` | Underscores |
| Prefix (config) | `'my___theme'` | `'my_theme_options'` | Underscores |
| Page ID | `'advanced_settings'` | `'advanced-settings'` | Dashes |
| Post Name | prefix=`'site_settings_options'`, page=`'general'` | `'site-settings-options-general'` | All dashes |
| Post Name | prefix=`'my_theme_options'`, page=`'header_config'` | `'my-theme-options-header-config'` | All dashes |

## Benefits

1. **WordPress Convention** - Post types use underscores (stored in config)
2. **SEO-Friendly** - Post names use dashes (WordPress slug best practice)
3. **Full Control** - We control post_name generation completely
4. **Consistency** - All post names use dashes, all config uses underscores
5. **No Duplicates** - Each page gets a unique post name
6. **No Consecutive Separators** - All values are properly cleaned
7. **Maintainability** - Single source of truth for name generation
8. **Debugging** - Easy to trace where post names come from
9. **URL-Friendly** - Dashes in post names work well in URLs
