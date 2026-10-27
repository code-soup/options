# Suggested additional Agent Skills

**Status:** Proposal for review (not installed by default)  
**Context:** Complements the three existing skills after the [skills accuracy audit](AUDIT.md). Several topics are only partially covered in `codesoup-options-setup` / `usage`.

---

## Recommended new skills

### 1. `codesoup-options-tabbed-ui`

**Why:** Tabs mode has distinct behavior (custom admin screen, `Form_Handler`, per-tab posts, navigation URLs) that is easy to misconfigure. Setup skill mentions tabs briefly; agents still confuse tabs vs pages mode.

**When to use (description):**
- Configuring `ui.mode => tabs`
- Horizontal vs vertical tab layout
- Saving in tabs mode (`admin-post.php`, nonces, `page_id` hidden field)
- Combining native metaboxes with tab UI

**Should include:**
- Screen ID format (`toplevel_page_*` vs submenu)
- One post per registered page ID (`get_post_by_page_id`)
- When ACF is viable in tabs vs pages mode
- After **1.3.0**: `hide_save_button` per tab page (UI + server rules)

**Examples directory:**
- `examples/minimal-tabs-native.md`
- `examples/vertical-tabs.md`

**Priority:** High

---

### 2. `codesoup-options-troubleshooting`

**Why:** Support-style questions (empty options, save loops, capabilities, wrong screen) are scattered across setup/usage troubleshooting sections. A single diagnostic skill reduces hallucinated APIs.

**When to use:**
- Options empty or not saving
- User cannot see admin menu
- ACF location rules not matching
- Capability / role issues
- Debug mode and `Manager::debug()`

**Should include:**
- Checklist: instance registered + `init()` called
- Verify post exists for page ID (`get_post_by_page_id`)
- `get_option()` vs `get_options()` (ACF vs native)
- `Post_Manager::regenerate_capabilities()`
- Pointer to `docs/api.md` filters (header/sidebar templates)

**Examples:**
- `examples/diagnose-empty-options.md`
- `examples/capability-menu-missing.md`

**Priority:** High

---

### 3. `codesoup-options-composer-package`

**Why:** Many installs use Composer (plain or PHP-Scoper). Path/asset URL behavior and bootstrap differ from standalone plugin zip. Post-1.3.0 `Path_Helper` logic is easy to get wrong in agent answers.

**When to use:**
- `composer require codesoup/options`
- Bundling inside a theme/plugin
- Scoped / prefixed vendor directories
- Missing CSS/JS or broken logo URLs

**Should include:**
- No `index.php` required when using Manager directly
- Autoloader entry via `includes/class-autoloader.php`
- How `plugins_url()` resolution depends on install path
- Symlinked `vendor` layouts

**Priority:** Medium (increases after 1.3.0 release)

---

## Optional / lower priority

| Skill | Purpose |
|-------|---------|
| `codesoup-options-filters-customization` | Header/sidebar filters, `templates_dir`, branding flags — today in `docs/customization.md` only |
| `codesoup-options-acf-advanced` | Location rules, field group patterns, dual storage edge cases — split from setup to keep setup shorter |
| `codesoup-options-release-maintainer` | Version bumps (`readme.txt`, headers, `Plugin_Metadata`), changelog, skill version metadata |

---

## Skills to extend (instead of new files)

If you prefer fewer skills, merge into existing:

| Topic | Extend |
|-------|--------|
| Tabbed UI | `codesoup-options-setup` + one new example file |
| Troubleshooting | `codesoup-options-usage` appendix |
| Composer / paths | `codesoup-options-setup` installation section |

---

## Implementation order

1. **`codesoup-options-troubleshooting`** — highest agent error rate (wrong getters, missing `init()`).
2. **`codesoup-options-tabbed-ui`** — especially before promoting tabs mode in docs.
3. **`codesoup-options-composer-package`** — after 1.3.0 path fixes land on `master`.

Each new skill should follow the same frontmatter pattern as `codesoup-options-migration` and bump `metadata.version` when content changes.

---

## Registration

After adding skills, update:

- `skills/README.md` — list + Skillshare install line
- `skills/SKILLSHARE.md` — if install examples name specific skills
- Root `README.md` — optional one-line pointer

```bash
skillshare install code-soup/codesoup-options -s codesoup-options-troubleshooting,codesoup-options-tabbed-ui
```
