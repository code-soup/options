<?php
/**
 * Migration class for handling configuration and data migrations
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

/**
 * Migration class
 */
class Migration {

	/**
	 * Migrate instance configuration and data
	 *
	 * Handles:
	 * - Changing post_type or prefix (renames posts)
	 * - Syncing capabilities from code to existing posts
	 *
	 * @param string $instance_key Instance identifier.
	 * @param array  $old_config Old configuration with 'post_type' and 'prefix'.
	 * @param array  $new_pages Array of new page definitions with updated capabilities.
	 * @return array Migration results with counts.
	 */
	public static function migrate( string $instance_key, array $old_config, array $new_pages = array() ): array {
		global $wpdb;

		if ( empty( $instance_key ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Instance key is required', 'codesoup-options' ),
			);
		}

		$instance = Manager::get( sanitize_key( $instance_key ) );
		if ( ! $instance ) {
			return array(
				'success' => false,
				'error'   => __( 'Instance not found', 'codesoup-options' ),
			);
		}

		if ( ! is_array( $old_config ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Old config must be an array', 'codesoup-options' ),
			);
		}

		if ( ! is_array( $new_pages ) ) {
			return array(
				'success' => false,
				'error'   => __( 'New pages must be an array', 'codesoup-options' ),
			);
		}

		$new_config    = $instance->get_config();
		$old_post_type = isset( $old_config['post_type'] ) ? sanitize_key( $old_config['post_type'] ) : null;
		$old_prefix    = isset( $old_config['prefix'] ) ? sanitize_key( $old_config['prefix'] ) : null;
		$new_post_type = $new_config['post_type'];
		$new_prefix    = $new_config['prefix'];

		$results = array(
			'success'              => true,
			'posts_updated'        => 0,
			'post_type_changed'    => 0,
			'prefix_changed'       => 0,
			'capabilities_synced'  => 0,
			'errors'               => array(),
		);

		// Get all posts with old post_type.
		$posts = get_posts(
			array(
				'post_type'      => $old_post_type ?? $new_post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		if ( empty( $posts ) ) {
			$results['error'] = __( 'No posts found to migrate', 'codesoup-options' );
			return $results;
		}

		// Build capability map from new pages.
		$capability_map = array();
		foreach ( $new_pages as $page_args ) {
			if ( ! is_array( $page_args ) ) {
				continue;
			}

			if ( isset( $page_args['id'] ) && isset( $page_args['capability'] ) ) {
				$sanitized_id         = sanitize_key( $page_args['id'] );
				$sanitized_capability = sanitize_key( $page_args['capability'] );

				if ( ! empty( $sanitized_id ) && ! empty( $sanitized_capability ) ) {
					$capability_map[ $sanitized_id ] = $sanitized_capability;
				}
			}
		}

		foreach ( $posts as $post ) {
			$updated = false;
			$post_id = $post->ID;

			// Update post_type if changed.
			if ( $old_post_type && $old_post_type !== $new_post_type ) {
				$result = set_post_type( $post_id, $new_post_type );
				if ( false === $result ) {
					$results['errors'][] = sprintf(
						__( 'Failed to update post type for post ID %d', 'codesoup-options' ),
						$post_id
					);
				} else {
					$results['post_type_changed']++;
					$updated = true;
				}
			}

			// Update post_name (prefix) if changed.
			if ( $old_prefix && $old_prefix !== $new_prefix ) {
				$old_name = $post->post_name;
				if ( strpos( $old_name, $old_prefix ) === 0 ) {
					$page_id  = substr( $old_name, strlen( $old_prefix ) );
					$new_name = $new_prefix . $page_id;

					$result = wp_update_post(
						array(
							'ID'        => $post_id,
							'post_name' => $new_name,
						),
						true
					);

					if ( is_wp_error( $result ) ) {
						$results['errors'][] = sprintf(
							__( 'Failed to update post name for post ID %1$d: %2$s', 'codesoup-options' ),
							$post_id,
							$result->get_error_message()
						);
					} else {
						$results['prefix_changed']++;
						$updated = true;

						// Clear cache with old and new keys.
						$cache         = $instance->get_cache();
						$old_cache_key = $cache->get_key( $page_id );
						$cache->delete( $old_cache_key );

						// Clear new cache key.
						$new_cache_key = $cache->get_key( $page_id );
						$cache->delete( $new_cache_key );

						// Clear post_id cache.
						$post_id_cache_key = $cache->get_key( 'post_id_' . $page_id );
						$cache->delete( $post_id_cache_key );

						// Clear instance page cache.
						$cache->clear_page_id( $page_id );
					}
				}
			}

			// Sync capability if provided in new_pages.
			if ( ! empty( $capability_map ) ) {
				$post_name = get_post_field( 'post_name', $post_id );
				$prefix    = $new_prefix;

				if ( strpos( $post_name, $prefix ) === 0 ) {
					$page_id = substr( $post_name, strlen( $prefix ) );

					if ( isset( $capability_map[ $page_id ] ) ) {
						$new_capability = $capability_map[ $page_id ];
						$old_capability = get_post_meta( $post_id, Manager::META_KEY_CAPABILITY, true );

						if ( $old_capability !== $new_capability ) {
							update_post_meta( $post_id, Manager::META_KEY_CAPABILITY, $new_capability );
							$results['capabilities_synced']++;
							$updated = true;
						}
					}
				}
			}

			if ( $updated ) {
				$results['posts_updated']++;
			}
		}

		return $results;
	}
}

