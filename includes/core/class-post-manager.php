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
		$screen_id = str_replace('toplevel_page_', '', $screen->id);

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
				&& $screen_id === $admin_page->get_screen_id()
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
				&& $screen_id === $pages_list_page->get_screen_id()
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

		error_log('pages'. print_r($pages, true));

		if ( empty( $pages ) ) {
			return;
		}

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

		error_log( print_r($existing, true));

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
}
