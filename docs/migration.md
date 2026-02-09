# Migration Guide

This guide explains how to migrate your CodeSoup Options configuration when you need to change the post type, prefix, or other settings.

## When to Migrate

You need to migrate when:
- Changing the `post_type` configuration
- Changing the `prefix` configuration
- Updating page capabilities in existing installations

## Migration Process

### Step 1: Backup Your Data

**CRITICAL:** Always backup your database before running migrations.

```bash
# Using WP-CLI
wp db export backup-before-migration.sql

# Or use your hosting provider's backup tools
```

### Step 2: Prepare Migration Code

The `Migration` class handles renaming posts and syncing capabilities. You need to:
1. Keep your old configuration values
2. Update your code with new configuration
3. Run the migration once

### Example: Changing Post Type and Prefix

**Old configuration:**
```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'site_settings',
	array(
		'post_type' => 'old_options',
		'prefix'    => 'old_',
	)
);
```

**New configuration:**
```php
use CodeSoup\Options\Manager;

$manager = Manager::create(
	'site_settings',
	array(
		'post_type' => 'new_options',
		'prefix'    => 'new_',
	)
);
```

**Migration script:**
```php
use CodeSoup\Options\Migration;
use CodeSoup\Options\Manager;

// Initialize Manager with NEW configuration.
$manager = Manager::create(
	'site_settings',
	array(
		'post_type' => 'new_options',
		'prefix'    => 'new_',
	)
);

$manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General Settings',
		'capability' => 'manage_options',
	)
);

$manager->init();

// Run migration with OLD configuration.
$old_config = array(
	'post_type' => 'old_options',
	'prefix'    => 'old_',
);

$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'manage_options',
	),
);

$result = Migration::migrate( 'site_settings', $old_config, $new_pages );

// Check results.
if ( $result['success'] ) {
	echo sprintf(
		'Migration complete: %d posts updated, %d post types changed, %d prefixes changed, %d capabilities synced',
		$result['posts_updated'],
		$result['post_type_changed'],
		$result['prefix_changed'],
		$result['capabilities_synced']
	);
} else {
	echo 'Migration failed: ' . $result['error'];
}

if ( ! empty( $result['errors'] ) ) {
	foreach ( $result['errors'] as $error ) {
		echo 'Error: ' . $error . "\n";
	}
}
```

### Step 3: Run Migration

**Option A: WP-CLI (Recommended)**

Create a WP-CLI command file:

```php
// wp-content/plugins/your-plugin/cli/migrate-options.php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

WP_CLI::add_command(
	'options migrate',
	function( $args, $assoc_args ) {
		// Your migration code here (from Step 2).

		WP_CLI::success( 'Migration completed' );
	}
);
```

Run it:
```bash
wp options migrate
```

**Option B: Temporary Admin Page**

Create a one-time admin page to run the migration (remove after use).

**Option C: Direct Script**

Run migration script directly via PHP CLI or browser (secure it properly).

### Step 5: Clean Up

After successful migration:

1. **Remove migration code** - Delete temporary migration scripts
2. **Clear all caches:**
```php
wp_cache_flush();
delete_option( '_transient_timeout_*' );
delete_option( '_transient_*' );
```
3. **Update documentation** - Document the new configuration for your team

## Common Migration Scenarios

### Scenario 1: Changing Only the Prefix

```php
$old_config = array(
	'prefix' => 'old_prefix_',
);

$result = Migration::migrate( 'site_settings', $old_config );
```

### Scenario 2: Changing Only the Post Type

```php
$old_config = array(
	'post_type' => 'old_post_type',
);

$result = Migration::migrate( 'site_settings', $old_config );
```

### Scenario 3: Syncing Capabilities Only

If you only changed capabilities in your code:

```php
$old_config = array(); // Empty - no post_type or prefix change.

$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'edit_posts', // Changed from manage_options.
	),
	array(
		'id'         => 'advanced',
		'capability' => 'manage_options',
	),
);

$result = Migration::migrate( 'site_settings', $old_config, $new_pages );
```

### Scenario 4: Migrating Multiple Instances

```php
$instances = array(
	array(
		'key'        => 'site_settings',
		'old_config' => array( 'prefix' => 'old_' ),
	),
	array(
		'key'        => 'theme_options',
		'old_config' => array( 'post_type' => 'old_theme_opts' ),
	),
);

foreach ( $instances as $instance ) {
	$result = Migration::migrate( $instance['key'], $instance['old_config'] );
	// Handle result...
}
```

## Troubleshooting

### Migration Returns "No posts found to migrate"

**Cause:** The old post_type doesn't exist or has no posts.

**Solution:**
1. Verify old post_type exists: `wp post list --post_type=old_options`
2. Check if posts were already migrated
3. Verify you're using the correct old configuration

### Migration Returns "Instance not found"

**Cause:** Manager instance not initialized before migration.

**Solution:** Always call `Manager::create()` and `init()` before running migration.

### Post Names Not Updating

**Cause:** Duplicate post_name constraint in database.

**Solution:**
1. Check for existing posts with the new name
2. Manually rename conflicting posts
3. Re-run migration

### Capabilities Not Syncing

**Cause:** Page ID doesn't match between old posts and new configuration.

**Solution:**
1. Verify page IDs in `$new_pages` match the suffix in post_name
2. Example: If post_name is `prefix_general`, page ID should be `general`

### ACF Fields Not Showing After Migration

**Cause:** ACF field groups still assigned to old post type.

**Solution:**
1. Go to ACF → Field Groups
2. Edit each field group
3. Update location rules to use new post type
4. Or use ACF's location rule "CodeSoup Options" → select your page ID

## Migration Return Values

The `Migration::migrate()` method returns an array:

```php
array(
	'success'              => true,              // Overall success status.
	'posts_updated'        => 5,                 // Total posts modified.
	'post_type_changed'    => 5,                 // Posts with post_type updated.
	'prefix_changed'       => 5,                 // Posts with post_name updated.
	'capabilities_synced'  => 3,                 // Posts with capability meta updated.
	'errors'               => array(),           // Array of error messages.
	'error'                => 'Error message',   // Single error (if success=false).
)
```

## Best Practices

1. **Test in staging first** - Never run migrations directly in production
2. **Backup before migrating** - Always have a database backup
3. **Run once** - Migrations should be one-time operations
4. **Verify results** - Check data integrity after migration
5. **Document changes** - Keep a record of what was migrated and when
6. **Clear caches** - Flush all caches after migration
7. **Remove migration code** - Delete temporary migration scripts after use

## Advanced: Custom Migration Logic

If you need custom migration logic beyond what the Migration class provides, you can:

```php
global $wpdb;

// Get all posts.
$posts = get_posts(
	array(
		'post_type'      => 'old_options',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	)
);

foreach ( $posts as $post ) {
	// Custom logic here.
	// Example: Transform post_content data.
	$options = maybe_unserialize( $post->post_content );

	// Modify data structure.
	if ( isset( $options['old_key'] ) ) {
		$options['new_key'] = $options['old_key'];
		unset( $options['old_key'] );
	}

	// Save back.
	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => maybe_serialize( $options ),
		)
	);

	// Clear cache.
	$manager = Manager::get( 'site_settings' );
	$manager->invalidate_cache( $post->ID );
}
```

## Need Help?

If you encounter issues during migration:

1. Check the error log: `wp-content/debug.log` (if `WP_DEBUG_LOG` is enabled)
2. Review the migration return array for specific errors
3. Verify your old and new configurations are correct
4. Test with a single post first before migrating all posts

### Step 4: Verify Migration

After migration, verify:

1. **Check posts were renamed:**
```php
$posts = get_posts(
	array(
		'post_type'      => 'new_options',
		'posts_per_page' => -1,
	)
);

foreach ( $posts as $post ) {
	echo sprintf( 'Post: %s (ID: %d)', $post->post_name, $post->ID ) . "\n";
}
```

2. **Check data integrity:**
```php
$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'general' );
var_dump( $options ); // Should contain your data.
```

3. **Test in admin:**
   - Navigate to your options pages
   - Verify all data displays correctly
   - Save a test change

