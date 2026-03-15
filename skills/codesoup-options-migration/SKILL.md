---
name: codesoup-options-migration
description: Migrate CodeSoup Options configuration when changing post_type, prefix, or capabilities. Handle database migrations, rename posts, sync capabilities, backup data, troubleshoot migration issues. Use when changing post_type configuration, changing prefix configuration, updating page capabilities in existing installations, or migrating from old to new configuration.
license: GPL-3.0-or-later
metadata:
  author: code-soup
  version: "1.0.0"
  package: codesoup/options
---

# CodeSoup Options Migration

Migrate CodeSoup Options configuration when changing post type, prefix, or capabilities.

## Examples

Complete working examples are available in the `examples/` directory:

- [Basic Migration](examples/basic-migration.md) - Change post_type and prefix
- [Prefix Only](examples/prefix-only.md) - Change prefix only
- [Capabilities Sync](examples/capabilities-sync.md) - Update capabilities only
- [WP-CLI Migration](examples/wpcli-migration.md) - Run via WP-CLI (recommended)

## When to Use This Skill

- Changing the `post_type` configuration
- Changing the `prefix` configuration
- Updating page capabilities in existing installations
- Migrating from old configuration to new configuration

## Migration Process

### Step 1: Backup Your Data

**CRITICAL:** Always backup your database before running migrations using WP-CLI (`wp db export`) or your hosting provider's backup tools.

### Step 2: Prepare Migration Code

The `Migration` class handles renaming posts and syncing capabilities.

**Requirements:**
1. Keep your old configuration values
2. Update your code with new configuration
3. Run the migration once

**See Examples:**
- [Basic Migration](examples/basic-migration.md) - Complete example changing post_type and prefix
- [Prefix Only](examples/prefix-only.md) - Change prefix only
- [Capabilities Sync](examples/capabilities-sync.md) - Update capabilities only
- [WP-CLI Migration](examples/wpcli-migration.md) - Recommended approach using WP-CLI

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
		// Your migration code here (from Step 2)

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

### Step 4: Verify Migration

After migration, verify:

**1. Check posts were renamed:**

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

**2. Check data integrity:**

```php
$manager = Manager::get( 'site_settings' );
$options = $manager->get_options( 'general' );
var_dump( $options ); // Should contain your data
```

**3. Test in admin:**
- Navigate to your options pages
- Verify all data displays correctly
- Save a test change

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
use CodeSoup\Options\Migration;

$old_config = array(
	'prefix' => 'old_prefix_',
);

$result = Migration::migrate( 'site_settings', $old_config );
```

### Scenario 2: Changing Only the Post Type

```php
use CodeSoup\Options\Migration;

$old_config = array(
	'post_type' => 'old_post_type',
);

$result = Migration::migrate( 'site_settings', $old_config );
```

### Scenario 3: Syncing Capabilities Only

If you only changed capabilities in your code:

```php
use CodeSoup\Options\Migration;

$old_config = array(); // Empty - no post_type or prefix change

$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'edit_posts', // Changed from manage_options
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
use CodeSoup\Options\Migration;

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

	if ( $result['success'] ) {
		echo "Migrated {$instance['key']}: {$result['posts_updated']} posts updated\n";
	} else {
		echo "Failed to migrate {$instance['key']}: {$result['error']}\n";
	}
}
```

## Migration Return Values

The `Migration::migrate()` method returns an array:

```php
array(
	'success'              => true,              // Overall success status
	'posts_updated'        => 5,                 // Total posts modified
	'post_type_changed'    => 5,                 // Posts with post_type updated
	'prefix_changed'       => 5,                 // Posts with post_name updated
	'capabilities_synced'  => 3,                 // Posts with capability meta updated
	'errors'               => array(),           // Array of error messages
	'error'                => 'Error message',   // Single error (if success=false)
)
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

```php
// WRONG - Manager not initialized
$result = Migration::migrate( 'site_settings', $old_config );

// CORRECT - Manager initialized first
$manager = Manager::create( 'site_settings', $new_config );
$manager->register_pages( /* ... */ );
$manager->init();
$result = Migration::migrate( 'site_settings', $old_config );
```

### Post Names Not Updating

**Cause:** Duplicate post_name constraint in database.

**Solution:**
1. Check for existing posts with the new name
2. Manually rename conflicting posts
3. Re-run migration

```php
// Check for conflicts
global $wpdb;
$conflicts = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT ID, post_name FROM {$wpdb->posts} WHERE post_name LIKE %s",
		'new_prefix_%'
	)
);
```

### Capabilities Not Syncing

**Cause:** Page ID doesn't match between old posts and new configuration.

**Solution:**
1. Verify page IDs in `$new_pages` match the suffix in post_name
2. Example: If post_name is `prefix_general`, page ID should be `general`

```php
// Check post names
$posts = get_posts( array( 'post_type' => 'new_options', 'posts_per_page' => -1 ) );
foreach ( $posts as $post ) {
	// Extract page ID from post_name
	$page_id = str_replace( 'new_prefix_', '', $post->post_name );
	echo "Post: {$post->post_name}, Page ID: {$page_id}\n";
}
```

### ACF Fields Not Showing After Migration

**Cause:** ACF field groups still assigned to old post type.

**Solution:**
1. Go to ACF → Field Groups
2. Edit each field group
3. Update location rules to use new post type
4. Or use ACF's location rule "CodeSoup Options" → select your page ID

### Data Lost After Migration

**Cause:** Migration script error or incorrect configuration.

**Solution:**
1. Restore from backup immediately
2. Review migration script for errors
3. Test migration on staging environment first
4. Verify old_config matches actual old configuration

## Best Practices

1. **Test in staging first** - Never run migrations directly in production
2. **Backup before migrating** - Always have a database backup
3. **Run once** - Migrations should be one-time operations
4. **Verify results** - Check data integrity after migration
5. **Document changes** - Keep a record of what was migrated and when
6. **Clear caches** - Flush all caches after migration
7. **Remove migration code** - Delete temporary migration scripts after use

## Advanced: Custom Migration Logic

If you need custom migration logic beyond what the Migration class provides:

```php
global $wpdb;

// Get all posts
$posts = get_posts(
	array(
		'post_type'      => 'old_options',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	)
);

foreach ( $posts as $post ) {
	// Custom logic here
	// Example: Transform post_content data
	$options = maybe_unserialize( $post->post_content );

	// Modify data structure
	if ( isset( $options['old_key'] ) ) {
		$options['new_key'] = $options['old_key'];
		unset( $options['old_key'] );
	}

	// Save back
	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => maybe_serialize( $options ),
		)
	);

	// Clear cache
	$manager = Manager::get( 'site_settings' );
	wp_cache_delete( 'options_' . $post->ID, 'codesoup_options' );
}
```

### Example: Migrating Data Structure

```php
use CodeSoup\Options\Manager;

function migrate_data_structure() {
	$manager = Manager::get( 'site_settings' );
	$config = $manager->get_config();

	// Get all option posts
	$posts = get_posts(
		array(
			'post_type'      => $config['post_type'],
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	foreach ( $posts as $post ) {
		$options = maybe_unserialize( $post->post_content );

		if ( ! is_array( $options ) ) {
			continue;
		}

		// Example: Rename keys
		$migrations = array(
			'old_logo'  => 'site_logo',
			'old_email' => 'contact_email',
		);

		foreach ( $migrations as $old_key => $new_key ) {
			if ( isset( $options[ $old_key ] ) ) {
				$options[ $new_key ] = $options[ $old_key ];
				unset( $options[ $old_key ] );
			}
		}

		// Save updated data
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => maybe_serialize( $options ),
			)
		);

		// Clear cache
		wp_cache_delete( 'options_' . $post->ID, 'codesoup_options' );
	}

	return true;
}
```

### Example: Migrating ACF Field Names

```php
function migrate_acf_field_names() {
	$manager = Manager::get( 'site_settings' );
	$config = $manager->get_config();

	// Get all option posts
	$posts = get_posts(
		array(
			'post_type'      => $config['post_type'],
			'posts_per_page' => -1,
		)
	);

	// Field name mappings
	$field_mappings = array(
		'old_field_name' => 'new_field_name',
		'legacy_logo'    => 'site_logo',
	);

	foreach ( $posts as $post ) {
		foreach ( $field_mappings as $old_name => $new_name ) {
			// Get old value
			$value = get_post_meta( $post->ID, $old_name, true );

			if ( $value ) {
				// Save to new field name
				update_post_meta( $post->ID, $new_name, $value );

				// Optionally delete old field
				delete_post_meta( $post->ID, $old_name );
			}
		}

		// Update post_content as well
		$options = maybe_unserialize( $post->post_content );
		if ( is_array( $options ) ) {
			foreach ( $field_mappings as $old_name => $new_name ) {
				if ( isset( $options[ $old_name ] ) ) {
					$options[ $new_name ] = $options[ $old_name ];
					unset( $options[ $old_name ] );
				}
			}

			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => maybe_serialize( $options ),
				)
			);
		}
	}

	return true;
}
```

## Complete Migration Example

Here's a complete example showing all steps:

```php
<?php
/**
 * Complete Migration Example
 *
 * This script migrates from old configuration to new configuration.
 * Run once via WP-CLI, admin page, or direct script.
 */

use CodeSoup\Options\Migration;
use CodeSoup\Options\Manager;

// Step 1: Backup (do this manually before running script)
// wp db export backup-before-migration.sql

// Step 2: Initialize Manager with NEW configuration
$manager = Manager::create(
	'site_settings',
	array(
		'post_type'    => 'site_options',
		'prefix'       => 'site_opt_',
		'menu_label'   => 'Site Settings',
		'debug'        => true, // Enable logging during migration
	)
);

// Register pages with NEW configuration
$manager->register_pages(
	array(
		array(
			'id'         => 'general',
			'title'      => 'General Settings',
			'capability' => 'manage_options',
		),
		array(
			'id'         => 'advanced',
			'title'      => 'Advanced Settings',
			'capability' => 'manage_options',
		),
	)
);

$manager->init();

// Step 3: Run migration with OLD configuration
$old_config = array(
	'post_type' => 'old_options',
	'prefix'    => 'old_',
);

$new_pages = array(
	array(
		'id'         => 'general',
		'capability' => 'manage_options',
	),
	array(
		'id'         => 'advanced',
		'capability' => 'manage_options',
	),
);

echo "Starting migration...\n";

$result = Migration::migrate( 'site_settings', $old_config, $new_pages );

// Step 4: Check results
if ( $result['success'] ) {
	echo "✓ Migration successful!\n";
	echo sprintf(
		"  - Posts updated: %d\n",
		$result['posts_updated']
	);
	echo sprintf(
		"  - Post types changed: %d\n",
		$result['post_type_changed']
	);
	echo sprintf(
		"  - Prefixes changed: %d\n",
		$result['prefix_changed']
	);
	echo sprintf(
		"  - Capabilities synced: %d\n",
		$result['capabilities_synced']
	);
} else {
	echo "✗ Migration failed!\n";
	echo "  Error: " . $result['error'] . "\n";
}

if ( ! empty( $result['errors'] ) ) {
	echo "\nErrors encountered:\n";
	foreach ( $result['errors'] as $error ) {
		echo "  - " . $error . "\n";
	}
}

// Step 5: Verify migration
echo "\nVerifying migration...\n";

$posts = get_posts(
	array(
		'post_type'      => 'site_options',
		'posts_per_page' => -1,
	)
);

echo sprintf( "Found %d posts with new post_type\n", count( $posts ) );

foreach ( $posts as $post ) {
	echo sprintf(
		"  - %s (ID: %d)\n",
		$post->post_name,
		$post->ID
	);
}

// Check data integrity
$options = $manager->get_options( 'general' );
echo "\nGeneral options data:\n";
print_r( $options );

// Step 6: Clear caches
wp_cache_flush();
echo "\n✓ Caches cleared\n";

echo "\nMigration complete! Please verify data in WordPress admin.\n";
echo "Remember to:\n";
echo "  1. Test all options pages\n";
echo "  2. Save a test change\n";
echo "  3. Update ACF field group location rules (if using ACF)\n";
echo "  4. Remove this migration script\n";
```

## Need Help?

If you encounter issues during migration:

1. Check the error log: `wp-content/debug.log` (if `WP_DEBUG_LOG` is enabled)
2. Review the migration return array for specific errors
3. Verify your old and new configurations are correct
4. Test with a single post first before migrating all posts
5. Always restore from backup if something goes wrong


