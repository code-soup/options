# Skills audit (2026-10-27)

Internal record of accuracy review against `includes/` and `docs/` on **master**.

## Issues corrected in PR (skills-audit-fixes)

| Area | Problem | Fix |
|------|---------|-----|
| `Page` API | Examples used `get_id()`, `get_title()`, `get_post_id()` | Use public properties `$page->id`, `$page->title`; resolve posts via `Manager::get_post_by_page_id()` |
| `Manager::destroy()` | Documented as instance method | `Manager::destroy( string $instance_key )` static |
| `get_option()` | Described as reading `post_content` without ACF | Clarified: uses active integration (default ACF `get_field()`); native metaboxes should use `get_options()` |
| Config defaults | `revisions` default `true`, `menu.position` `80` | Match code: `revisions` default `false`, `position` `99` |
| Debug logging | All levels required `WP_DEBUG` | Only `Logger::debug()` requires `WP_DEBUG`; error/warning/info need `debug => true` |
| Migration cleanup | `delete_option( '_transient_*' )` wildcards | Replaced with accurate guidance (`wp_cache_flush()`, optional transient helpers) |
| WP-CLI example | `use` imports inside closure (invalid PHP) | Fully qualified class names at file scope |
| CMB2 example | `post_name` used raw `prefix + id` | Note to use `Post_Manager` post_name rules (prefix, dashes) |
| README structure | Listed non-existent `tabs/content.php` | Updated to match repository layout |
| Frontmatter | Setup/usage skills missing metadata block | Aligned with migration skill format + version bump |

## Verified accurate (no change)

- `save_options( array $args )` signature and `WP_Error` code `save_options_failed`
- `Migration::migrate( $instance_key, $old_config, $new_pages )` signature
- `Post_Manager::regenerate_capabilities()` static method
- `IntegrationInterface` method list
- ACF dual storage description
- Nested vs flat config deprecation mapping (points to `migration-v1.1.md`)

## Follow-up (see suggested-skills PR)

- Dedicated tabbed UI / troubleshooting skills
- Re-audit after **1.3.0** merges (`hide_save_button`, `get_page()`, path helper)
