# Custom Tabbed UI Integration Plan

## Current State Analysis

The plugin currently uses WordPress's native post type list interface (`edit.php?post_type={post_type}`). Each registered page creates a separate post entry, and users navigate between pages via the standard WordPress post list.

**Current Flow:**

1. Manager registers custom post type with `show_ui => true`, `show_in_menu => false`
2. Admin menu points to `edit.php?post_type={post_type}`
3. Users see WordPress post list showing all option pages
4. Clicking a page opens the standard post editor with metaboxes

## Proposed Solution: Custom Admin Page with Tabs

Replace the post list interface with a custom admin page featuring horizontal or vertical tabs for navigation between option pages.

---

## Implementation Plan

### Phase 1: Core Infrastructure

#### 1.1 Tab Configuration

No changes needed to Page class. Tab configuration is handled at Manager level:

- Tab position is set via Manager config (`tab_position`)
- Tab order follows page registration order
- Tab labels use page titles
- No icons or custom ordering in MVP

#### 1.2 Create Custom Admin Page Handler

**File:** `includes/class-admin-page.php`

Responsibilities:

- Register custom admin page callback instead of `edit.php?post_type=...`
- Handle tab rendering and navigation
- Load appropriate page content based on active tab
- Manage URL parameters for tab state (`?page={slug}&tab={page_id}`)

Implement:

- Implement custom page, redirect edit.php?Post_type to this new page

#### 1.3 Update Manager Configuration

**File:** `includes/class-manager.php`

Add new config options:

```php
'ui_mode' => 'pages'    // 'pages' (default) or 'tabs'
'tab_position' => 'top' // 'top', 'left', 'right' (only applies when ui_mode = 'tabs')
```

---

### Phase 2: UI Components

#### 2.1 Create Tab Navigation Component

**File:** `includes/ui/class-tab-navigation.php`

Features:

- Render tab navigation based on position (top/left/right)
- Handle active state
- Accessibility (ARIA labels, keyboard navigation)

#### 2.2 Create Tab Content Renderer

**File:** `includes/ui/class-tab-content.php`

Features:

- Render metaboxes for active tab
- Maintain WordPress metabox API compatibility (native metaboxes only)
- Handle form submission
- Provide post object context to metabox templates

#### 2.3 Create Assets Handler

**Files:**

- `assets/css/admin-tabs.css`
- `assets/js/admin-tabs.js`

Features:

- Tab styling (horizontal/vertical layouts)
- Tab switching logic
- Form state preservation
- Unsaved changes warning

---

### Phase 3: Integration Points

#### 3.1 Modify Admin Menu Registration

**File:** `includes/class-manager.php` → `register_admin_menu()`

Change from:

```php
$menu_slug = 'edit.php?post_type=' . $this->config['post_type'];
```

To:

```php
$menu_slug = $this->config['ui_mode'] === 'tabs'
    ? 'codesoup-options-' . $this->instance_key
    : 'edit.php?post_type=' . $this->config['post_type'];
```

Register custom callback for tabs mode.

#### 3.2 Modify Post Type Registration

**File:** `includes/class-manager.php` → `register_post_type()`

When in tabs mode:

- Set `show_ui => false` to hide default UI
- Keep post type for data storage only
- Maintain all existing capabilities

#### 3.3 Update Metabox Registration

**File:** `includes/class-manager.php` → `register_metaboxes()`

Adapt to work with custom admin page:

- Detect current tab from URL parameter
- Register metaboxes for active tab only
- Use custom screen ID format: `codesoup-options-{instance_key}`
- Load appropriate post object for the active tab
- Pass post object to metabox callbacks via global `$post`

---

### Phase 4: Data Handling

#### 4.1 Form Submission Handler

**File:** `includes/class-form-handler.php`

Features:

- Process form submissions from tabbed interface
- Maintain compatibility with existing `save_options()` method
- Handle nonce verification
- Redirect back to active tab after save
- Display success/error notices

---

### Phase 5: Backward Compatibility

#### 5.1 Maintain Default Mode

Keep existing `edit.php` interface as default:

- `ui_mode => 'pages'` uses current behavior (default)
- `ui_mode => 'tabs'` enables new tabbed interface
- No breaking changes for existing implementations

#### 5.2 Migration Path

Provide smooth transition:

- Both modes access same data (posts)
- Can switch between modes without data loss
- Clear documentation on differences
- Native metaboxes work in both modes

---

### Phase 6: Enhanced Features

#### 6.1 Tab Layouts

Support multiple tab positions:

- **Top tabs** - Horizontal tabs above content (WordPress native style)
- **Left tabs** - Vertical sidebar tabs (ACF style)
- **Right tabs** - Vertical right-side tabs

#### 6.2 Advanced Tab Features (Future Enhancements)

- Tab icons and badges (counts, status indicators)
- Tab groups/sections
- Conditional tab visibility based on capabilities
- Sticky tabs during scroll
- Tab search/filter for many pages
- Responsive design for mobile devices
- AJAX tab switching
- ACF integration support

---

## File Structure

```
includes/
├── class-manager.php (modified)
├── class-page.php (no changes needed)
├── class-admin-page.php (new)
├── class-form-handler.php (new)
├── ui/
│   ├── class-tab-navigation.php (new)
│   ├── class-tab-content.php (new)
│   └── templates/
│       ├── admin-page-wrapper.php (new)
│       ├── tab-navigation-horizontal.php (new)
│       ├── tab-navigation-vertical.php (new)
│       └── tab-content.php (new)
assets/
├── css/
│   └── admin-tabs.css (new)
└── js/
    └── admin-tabs.js (new)
```

---

## Configuration Example

```php
$manager = Manager::create(
    'site_settings',
    array(
        'menu_label'   => 'Site Settings',
        'ui_mode'      => 'tabs',
        'tab_position' => 'left', // 'top', 'left', 'right'
        'integrations' => array(
            'acf' => array( 'enabled' => false ),
        ),
    )
);

$manager->register_page(
    array(
        'id'         => 'general',
        'title'      => 'General Settings',
        'capability' => 'manage_options',
    )
);

$manager->register_page(
    array(
        'id'         => 'advanced',
        'title'      => 'Advanced Settings',
        'capability' => 'manage_options',
    )
);

// Register native metaboxes
$manager->register_metabox(
    array(
        'page'  => 'general',
        'title' => 'Site Information',
        'path'  => __DIR__ . '/templates/site-info.php',
    )
);
```

---

## Key Considerations

### 1. **WordPress Standards**

- Follow WordPress coding standards
- Use WordPress UI patterns (nav-tab, nav-tab-active classes)
- Maintain accessibility standards (WCAG 2.1)

### 2. **Performance**

- Enqueue assets only on plugin pages
- Minimize DOM manipulation
- Efficient metabox rendering

### 3. **Security**

- Nonce verification for all form submissions
- Capability checks per tab
- Sanitize all inputs
- Escape all outputs

### 4. **Metabox Compatibility**

- Native metaboxes must render correctly
- Proper screen ID handling for `add_meta_box()`
- Post object context must be available to metabox templates
- Form nonce and submission handling

### 5. **User Experience**

- Preserve tab state in URL
- Warn on unsaved changes
- Keyboard navigation support

---

## Testing Strategy

1. **Unit Tests** - Test tab configuration, navigation logic
2. **Integration Tests** - Test with native metaboxes
3. **UI Tests** - Test all tab positions (top/left/right)
4. **Compatibility Tests** - Test with different WordPress versions
5. **Accessibility Tests** - Screen reader, keyboard navigation
6. **Form Submission Tests** - Test save functionality in tabs mode

---

## Estimated Implementation Order

1. [ ] Add ui_mode and tab_position config to Manager
2. [ ] Create Admin Page Handler (basic structure with redirect)
3. [ ] Create Tab Navigation Component (horizontal top tabs first)
4. [ ] Create Tab Content Renderer (metabox integration)
5. [ ] Create CSS/JS assets (basic styling and tab switching)
6. [ ] Implement Form Handler (save functionality with nonce)
7. [ ] Add vertical tab layouts (left/right positions)
8. [ ] Add accessibility features (ARIA, keyboard navigation)
9. [ ] Update documentation and examples
10. [ ] Testing and refinement

---

## Technical Notes

### Screen ID Strategy

When `ui_mode => 'tabs'`, the custom admin page will use a screen ID format:
- Format: `codesoup-options-{instance_key}`
- Example: `codesoup-options-site_settings`

Metaboxes will be registered using this screen ID instead of the post type.

### Post Object Context

Each tab will have an associated post object that is loaded and passed to metabox templates, maintaining compatibility with existing metabox code that expects `$post` to be available.

### Form Submission Flow

1. User edits fields in active tab
2. Clicks "Update" button
3. Form submits to custom handler
4. Handler validates nonce and capability
5. Handler saves data to appropriate post
6. Redirects back to same tab with success notice

---

## Notes

This plan provides a custom tabbed UI system for native metaboxes while maintaining backward compatibility with the existing pages mode. ACF integration and advanced features are deferred to future enhancements.
