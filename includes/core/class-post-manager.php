<?php
/**
 * Post Manager
 *
 * Handles WordPress post operations for options pages.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Post_Manager class
 *
 * Manages WordPress post creation, retrieval, and lifecycle for options pages.
 *
 * @since 1.1.0
 */
class Post_Manager {

	/**
	 * Option key prefix for post type capabilities
	 */
	private const OPTION_PREFIX_CAPS = 'codesoup_options_caps_%s';

	/**
	 * Option key prefix for page capabilities
	 */
	private const OPTION_PREFIX_PAGE_CAPS = 'codesoup_options_page_caps_%s';

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register custom post type
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$post_type = $this->manager->get_post_type();
		$config    = $this->manager->get_config();

		if ( post_type_exists( $post_type ) ) {
			return;
		}

		$plural   = $post_type;
		$singular = rtrim( $post_type, 's' );

		register_post_type(
			$post_type,
			array(
				'labels'              => array(
					'name'          => $config['menu']['label'],
					'singular_name' => $config['menu']['label'],
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => array(
					$singular,
					$plural
				),
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => array(
					'custom-fields',
					'title',
				),
				'show_in_rest'        => false,
				'delete_with_user'    => false,
				'can_export'          => true,
				'exclude_from_search' => true,
			)
		);

		if ( $config['revisions'] ) {
			add_post_type_support( $post_type, 'revisions' );
		}

		// Ensure admin role has capabilities for this post type.
		$this->maybe_add_capabilities_to_admin( $singular, $plural );
	}

	/**
	 * Add post type capabilities to administrator role
	 *
	 * Uses option to track if capabilities have been added for this post type.
	 * Option is cleared via flush_rewrite_rules_codesoup_options action.
	 *
	 * @param string $singular Singular capability type.
	 * @param string $plural Plural capability type.
	 * @return void
	 */
	private function maybe_add_capabilities_to_admin( string $singular, string $plural ): void {
		$post_type  = $this->manager->get_post_type();
		$option_key = sprintf( self::OPTION_PREFIX_CAPS, $post_type );

		// List of capabilities WordPress generates for custom post types.
		$capabilities = array(
			sprintf( 'edit_%s', $singular ),
			sprintf( 'read_%s', $singular ),
			sprintf( 'delete_%s', $singular ),
			sprintf( 'edit_%s', $plural ),
			sprintf( 'edit_others_%s', $plural ),
			sprintf( 'publish_%s', $plural ),
			sprintf( 'read_private_%s', $plural ),
			sprintf( 'delete_%s', $plural ),
			sprintf( 'delete_private_%s', $plural ),
			sprintf( 'delete_published_%s', $plural ),
			sprintf( 'delete_others_%s', $plural ),
			sprintf( 'edit_private_%s', $plural ),
			sprintf( 'edit_published_%s', $plural ),
		);

		$this->add_capabilities_to_role(
			'administrator',
			$capabilities,
			$option_key,
			sprintf( 'post type: %s', $post_type )
		);
	}

	/**
	 * Add custom page capabilities to administrator role
	 *
	 * Collects unique capabilities from registered pages and adds them to admin role.
	 * Uses option to track if capabilities have been added for this instance.
	 *
	 * @param array $pages Array of Page objects.
	 * @return void
	 */
	private function maybe_add_page_capabilities_to_admin( array $pages ): void {
		$instance_key = $this->manager->get_instance_key();
		$option_key   = sprintf( self::OPTION_PREFIX_PAGE_CAPS, $instance_key );

		// Collect unique capabilities from all pages.
		$capabilities = array();

		foreach ( $pages as $page ) {
			$capabilities[ $page->capability ] = true;
		}

		if ( empty( $capabilities ) ) {
			// Store flag even if no capabilities to prevent re-checking.
			if ( false === get_option( $option_key, false ) ) {
				update_option( $option_key, 1, false );
			}
			return;
		}

		$this->add_capabilities_to_role(
			'administrator',
			array_keys( $capabilities ),
			$option_key,
			sprintf( 'instance: %s (%s)', $instance_key, implode( ', ', array_keys( $capabilities ) ) )
		);
	}

	/**
	 * Add capabilities to role with validation
	 *
	 * @param string $role_name Role name.
	 * @param array  $capabilities Array of capability strings.
	 * @param string $option_key Option key to track if caps added.
	 * @param string $log_context Context string for logging.
	 * @return void
	 */
	private function add_capabilities_to_role( string $role_name, array $capabilities, string $option_key, string $log_context ): void {
		$caps_added = get_option( $option_key, false );

		if ( false !== $caps_added ) {
			return;
		}

		$role = get_role( $role_name );

		if ( ! $role ) {
			return;
		}

		$added_count = 0;

		foreach ( $capabilities as $cap ) {
			// Validate capability format (alphanumeric and underscores only).
			if ( ! preg_match( '/^[a-z0-9_]+$/i', $cap ) ) {
				$this->manager->get_logger()->warning(
					sprintf(
						'Invalid capability format "%s" - skipping',
						$cap
					)
				);
				continue;
			}

			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
				++$added_count;
			}
		}

		// Store flag to prevent re-adding capabilities.
		update_option( $option_key, 1, false );

		if ( $added_count > 0 ) {
			$this->manager->get_logger()->info(
				sprintf(
					'Added %d capabilities to %s role for %s',
					$added_count,
					$role_name,
					$log_context
				)
			);
		}
	}

	/**
	 * Check if we're on a relevant screen where pages should be created
	 *
	 * @param \WP_Screen $screen Current screen.
	 * @return bool
	 */
	private function is_relevant_screen( \WP_Screen $screen ): bool {
		$post_type = $this->manager->get_post_type();
		$config    = $this->manager->get_config();

		// Check for post type screens (edit list and single post edit)
		if (
			$screen->post_type === $post_type
			|| $screen->id === 'edit-' . $post_type
		) {
			return true;
		}

		// Check for tabs mode admin page
		if ( 'tabs' === $config['ui']['mode'] )
		{
			$admin_page = $this->manager->get_admin_page();

			if ( $admin_page
				&& $screen->id === $admin_page->get_screen_id()
			) {
				return true;
			}
		}

		// Check for pages mode admin page
		if ( 'pages' === $config['ui']['mode'] )
		{
			$pages_list_page = $this->manager->get_pages_list_page();

			if (
				$pages_list_page
				&& $screen->id === $pages_list_page->get_screen_id()
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Maybe ensure pages exist
	 *
	 * @param \WP_Screen $screen Current screen.
	 * @return void
	 */
	public function maybe_ensure_pages_exist( \WP_Screen $screen ): void {
		if ( $this->is_relevant_screen( $screen ) ) {
			$this->ensure_pages_exist();
		}
	}

	/**
	 * Ensure all registered pages exist
	 *
	 * @return void
	 */
	public function ensure_pages_exist(): void {
		$pages = $this->manager->get_pages();

		if ( empty( $pages ) ) {
			return;
		}

		// Add custom page capabilities to administrator role.
		$this->maybe_add_page_capabilities_to_admin( $pages );

		// create_page() already checks if page exists
		foreach ( $pages as $page ) {
			$this->create_page( $page );
		}
	}

	/**
	 * Get post by post_name and post_type using direct SQL
	 *
	 * @param string $post_name Post slug/name.
	 * @param string $post_type Post type.
	 * @return \WP_Post|null Post object if found, null otherwise.
	 */
	private function get_post_by_name_and_type( string $post_name, string $post_type ): ?\WP_Post {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT ID
			FROM {$wpdb->posts}
			WHERE post_name = %s
			AND post_type = %s
			AND post_status IN ('publish')
			LIMIT 1",
			$post_name,
			$post_type
		);

		$post_id = $wpdb->get_var( $query );

		if ( ! $post_id ) {
			return null;
		}

		return get_post( $post_id );
	}

	/**
	 * Create a new page post
	 *
	 * @param Page $page Page object.
	 * @return void
	 */
	public function create_page( Page $page ): void {
		$post_name = $this->get_post_name( $page->id );
		$post_type = $this->manager->get_post_type();

		// Check if post already exists using direct SQL
		$existing = $this->get_post_by_name_and_type( $post_name, $post_type );

		if ( $existing ) {
			return;
		}

		// Create the post.
		$post_id = wp_insert_post(
			array(
				'post_type'    => $post_type,
				'post_title'   => $page->title,
				'post_name'    => $post_name,
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->manager->get_logger()->error(
				sprintf(
					'Failed to create page "%s": %s',
					$page->id,
					$post_id->get_error_message()
				)
			);
			return;
		}

		// Store capability in post meta.
		update_post_meta( $post_id, Manager::META_KEY_CAPABILITY, $page->capability );

		$this->manager->get_logger()->info(
			sprintf(
				'Created page "%s" with post ID %d',
				$page->id,
				$post_id
			)
		);
	}

	/**
	 * Get post name from page ID
	 *
	 * Format: {prefix-with-dashes}-{page-id-with-dashes}
	 * - Prefix converted to dashes (from underscore format in config)
	 * - Page ID uses dashes (normalized slug)
	 * - Separator between prefix and page is dash
	 * - Post names are fully controlled by this package and can use any format
	 *
	 * @param string $page_id Page identifier.
	 * @return string
	 */
	public function get_post_name( string $page_id ): string {
		$prefix = $this->manager->get_config( 'prefix' );

		// Convert prefix from underscores to dashes for post_name.
		// Config uses underscores, but post_name uses dashes.
		$prefix_with_dashes = str_replace( '_', '-', $prefix );

		// Normalize page ID to use dashes.
		$normalized_page = Manager::normalize_slug( $page_id );

		// Combine both parts with dash separator.
		// Final format: prefix-page-id (e.g., 'site-settings-options-general')
		$post_name = $prefix_with_dashes . '-' . $normalized_page;

		// Normalize the complete post name to remove any consecutive dashes.
		return Manager::normalize_slug( $post_name );
	}

	/**
	 * Extract page ID from post name
	 *
	 * Extracts the page ID from a post name in format: {prefix-with-dashes}-{page-id}
	 * Converts underscore prefix from config to dashes to match post_name format.
	 *
	 * @param string $post_name Post name.
	 * @return string|null Page ID or null if prefix doesn't match.
	 */
	public function extract_page_id_from_post_name( string $post_name ): ?string {
		$prefix = $this->manager->get_config( 'prefix' );

		// Convert prefix from underscores to dashes to match post_name format.
		$prefix_with_dashes = str_replace( '_', '-', $prefix );

		// Add dash separator to match the format created by get_post_name().
		$prefix_with_separator = $prefix_with_dashes . '-';

		if ( strpos( $post_name, $prefix_with_separator ) !== 0 ) {
			return null;
		}

		// Return the page ID part (everything after prefix-).
		return substr( $post_name, strlen( $prefix_with_separator ) );
	}

	/**
	 * Get post by page ID
	 *
	 * @param string $page_id Page identifier.
	 * @return \WP_Post|null Post object or null if not found.
	 */
	public function get_post_by_page_id( string $page_id ): ?\WP_Post {
		$post_name = $this->get_post_name( $page_id );
		$post_type = $this->manager->get_post_type();

		// Check if post already exists using direct SQL
		$page = $this->get_post_by_name_and_type( $post_name, $post_type );

		return ! empty( $page ) ? $page : null;
	}

	/**
	 * Clear capability flags for a specific post type
	 *
	 * Should be called when flush_rewrite_rules is triggered.
	 *
	 * @param string $post_type Post type identifier.
	 * @return void
	 */
	public static function clear_capability_flag( string $post_type ): void {
		$option_key = sprintf( self::OPTION_PREFIX_CAPS, $post_type );
		delete_option( $option_key );
	}

	/**
	 * Clear page capability flags for a specific instance
	 *
	 * @param string $instance_key Instance identifier.
	 * @return void
	 */
	public static function clear_page_capability_flag( string $instance_key ): void {
		$option_key = sprintf( self::OPTION_PREFIX_PAGE_CAPS, $instance_key );
		delete_option( $option_key );
	}

	/**
	 * Clear all capability flags for CodeSoup Options
	 *
	 * Removes all capability tracking options (both post type and page capabilities).
	 * Call this to force capability regeneration on next init.
	 *
	 * @return void
	 */
	public static function clear_all_capability_flags(): void {
		global $wpdb;

		$patterns = array(
			'codesoup_options_caps_%',
			'codesoup_options_page_caps_%',
		);

		foreach ( $patterns as $pattern ) {
			// Validate pattern contains only expected characters.
			if ( ! preg_match( '/^[a-z_]+%$/', $pattern ) ) {
				continue;
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			);
		}
	}

	/**
	 * Force capability regeneration
	 *
	 * Clears all capability flags to force re-check on next init.
	 *
	 * Usage:
	 * ```php
	 * CodeSoup\Options\Post_Manager::regenerate_capabilities();
	 * ```
	 *
	 * @return void
	 */
	public static function regenerate_capabilities(): void {
		self::clear_all_capability_flags();
	}
}
