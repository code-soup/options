# WP-CLI Migration Example

Running migrations via WP-CLI (recommended approach).

## Create WP-CLI Command File

```php
// wp-content/plugins/your-plugin/cli/migrate-options.php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

WP_CLI::add_command(
	'options migrate',
	function( $args, $assoc_args ) {
		use CodeSoup\Options\Migration;
		use CodeSoup\Options\Manager;

		WP_CLI::log( 'Starting migration...' );

		// Initialize Manager with NEW configuration
		$manager = Manager::create(
			'site_settings',
			array(
				'post_type' => 'site_options',
				'prefix'    => 'site_opt_',
				'debug'     => true,
			)
		);

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

		// Run migration with OLD configuration
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

		$result = Migration::migrate( 'site_settings', $old_config, $new_pages );

		if ( $result['success'] ) {
			WP_CLI::success( 'Migration completed!' );
			WP_CLI::log( sprintf( 'Posts updated: %d', $result['posts_updated'] ) );
			WP_CLI::log( sprintf( 'Post types changed: %d', $result['post_type_changed'] ) );
			WP_CLI::log( sprintf( 'Prefixes changed: %d', $result['prefix_changed'] ) );
			WP_CLI::log( sprintf( 'Capabilities synced: %d', $result['capabilities_synced'] ) );
		} else {
			WP_CLI::error( 'Migration failed: ' . $result['error'] );
		}

		if ( ! empty( $result['errors'] ) ) {
			WP_CLI::warning( 'Errors encountered:' );
			foreach ( $result['errors'] as $error ) {
				WP_CLI::log( '  - ' . $error );
			}
		}

		// Clear caches
		wp_cache_flush();
		WP_CLI::success( 'Caches cleared' );
	}
);
```

## Load Command in Plugin

```php
// In your main plugin file
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/cli/migrate-options.php';
}
```

## Run Migration

```bash
# Backup database first
wp db export backup-before-migration.sql

# Run migration
wp options migrate

# Verify results
wp post list --post_type=site_options
```

