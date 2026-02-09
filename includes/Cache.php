<?php
/**
 * Cache class for managing WordPress object cache and transients
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

/**
 * Cache class
 *
 * Handles all caching operations for the Options plugin.
 * Uses WordPress object cache with transient fallback.
 *
 * @since 2.0.0
 */
class Cache {

	/**
	 * Instance key for cache namespacing
	 *
	 * @var string
	 */
	private string $instance_key;

	/**
	 * Cache group name for WordPress object cache
	 *
	 * @var string
	 */
	private string $cache_group;

	/**
	 * Cache duration in seconds
	 *
	 * @var int
	 */
	private int $cache_duration;

	/**
	 * Cache of created page IDs to avoid repeated existence checks
	 *
	 * @var array
	 */
	private array $page_cache = array();

	/**
	 * Constructor
	 *
	 * @param string $instance_key Instance identifier.
	 * @param string $cache_group Cache group name.
	 * @param int    $cache_duration Cache duration in seconds.
	 */
	public function __construct( string $instance_key, string $cache_group, int $cache_duration ) {
		$this->instance_key   = $instance_key;
		$this->cache_group    = $cache_group;
		$this->cache_duration = $cache_duration;
	}

	/**
	 * Get cache key for a page
	 *
	 * WordPress has a 172 character limit for option names (used by transients).
	 * We use 'cs_opt_' prefix (7 chars) to keep keys short.
	 *
	 * @param string $page_id Page identifier.
	 * @return string
	 * @throws \LengthException If cache key exceeds safe length.
	 */
	public function get_key( string $page_id ): string {
		$key = 'cs_opt_' . $this->instance_key . '_' . $page_id;

		// WordPress transient keys are stored as options with '_transient_' prefix (11 chars).
		// Total limit is 191 chars (MySQL utf8mb4 index limit), minus 11 = 180 chars for key.
		// We use 172 to be safe.
		if ( strlen( $key ) > 172 ) {
			throw new \LengthException(
				sprintf(
					__( 'Cache key too long (%1$d chars): %2$s', 'codesoup-options' ),
					strlen( $key ),
					$key
				)
			);
		}

		return $key;
	}

	/**
	 * Get data from cache
	 *
	 * @param string $cache_key Cache key.
	 * @return mixed|false Cached data or false if not found.
	 */
	public function get( string $cache_key ) {
		$cached = wp_cache_get( $cache_key, $this->cache_group );
		if ( $cached !== false ) {
			return $cached;
		}

		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			wp_cache_set( $cache_key, $cached, $this->cache_group );
			return $cached;
		}

		return false;
	}

	/**
	 * Set data in cache
	 *
	 * @param string $cache_key Cache key.
	 * @param mixed  $data Data to cache.
	 * @return void
	 * @throws \InvalidArgumentException If data is not serializable.
	 */
	public function set( string $cache_key, $data ): void {
		// Validate data is serializable (resources cannot be serialized).
		if ( is_resource( $data ) ) {
			throw new \InvalidArgumentException(
				__( 'Cannot cache resource type data.', 'codesoup-options' )
			);
		}

		// Test serialization to catch objects that don't support it.
		$test = @serialize( $data );
		if ( false === $test && 'b:0;' !== $test ) {
			throw new \InvalidArgumentException(
				__( 'Data cannot be serialized for caching.', 'codesoup-options' )
			);
		}

		wp_cache_set( $cache_key, $data, $this->cache_group );
		set_transient( $cache_key, $data, $this->cache_duration );
	}

	/**
	 * Delete data from cache
	 *
	 * @param string $cache_key Cache key.
	 * @return void
	 */
	public function delete( string $cache_key ): void {
		wp_cache_delete( $cache_key, $this->cache_group );
		delete_transient( $cache_key );
	}

	/**
	 * Get page ID from page cache
	 *
	 * @param string $page_id Page identifier.
	 * @return int|false Page post ID or false if not cached.
	 */
	public function get_page_id( string $page_id ) {
		return $this->page_cache[ $page_id ] ?? false;
	}

	/**
	 * Set page ID in page cache
	 *
	 * @param string $page_id Page identifier.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function set_page_id( string $page_id, int $post_id ): void {
		$this->page_cache[ $page_id ] = $post_id;
	}

	/**
	 * Clear page ID from page cache
	 *
	 * @param string $page_id Page identifier.
	 * @return void
	 */
	public function clear_page_id( string $page_id ): void {
		unset( $this->page_cache[ $page_id ] );
	}

	/**
	 * Clear all cached data for this instance
	 *
	 * Clears both object cache and transients. Used when destroying an instance.
	 *
	 * @return void
	 */
	public function clear_all(): void {
		// Clear in-memory page cache.
		$this->page_cache = array();

		// WordPress doesn't provide a way to clear all keys in a group,
		// so we can only clear the group from object cache.
		// Transients will expire naturally based on cache_duration.
		wp_cache_flush_group( $this->cache_group );
	}

	/**
	 * Get cache group
	 *
	 * @return string
	 */
	public function get_group(): string {
		return $this->cache_group;
	}
}

