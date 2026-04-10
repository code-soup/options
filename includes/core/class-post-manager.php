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
				'capability_type'     => 'post',
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'custom-fields', 'revisions' ),
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
	 * Maybe ensure pages exist
	 *
	 * @param \WP_Screen $screen Current screen.
	 * @return void
	 */
	public function maybe_ensure_pages_exist( \WP_Screen $screen ): void {
		$post_type = $this->manager->get_post_type();
		$config    = $this->manager->get_config();

		// Check for pages mode (post type screens)
		$is_pages_mode = $screen->post_type === $post_type || $screen->id === 'edit-' . $post_type;

		// Check for tabs mode (admin page screen)
		$is_tabs_mode = false;
		if ( 'tabs' === $config['ui']['mode'] ) {
			$admin_page   = $this->manager->get_admin_page();
			$is_tabs_mode = $admin_page && $screen->id === $admin_page->get_screen_id();
		}

		if ( $is_pages_mode || $is_tabs_mode ) {
			$this->ensure_pages_exist();
		}
	}

	/**
	 * Ensure all registered pages exist
	 *
	 * Uses batch query to check all pages at once for better performance.
	 *
	 * @return void
	 */
	public function ensure_pages_exist(): void {
		$pages = $this->manager->get_pages();

		if ( empty( $pages ) ) {
			return;
		}

		// Get all post names we need to check.
		$post_names = array();
		$pages_map  = array();
		foreach ( $pages as $page ) {
			$post_name               = $this->get_post_name( $page->id );
			$post_names[]            = $post_name;
			$pages_map[ $post_name ] = $page;
		}

		// Batch query to check which posts exist.
		$existing_posts = get_posts(
			array(
				'post_type'      => $this->manager->get_post_type(),
				'post_name__in'  => $post_names,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'names',
			)
		);

		// Create missing posts.
		$existing_names = array_filter(
			$existing_posts,
			function( $name ) {
				return is_string( $name ) || is_int( $name );
			}
		);
		$existing_names = array_flip( $existing_names );

		foreach ( $pages_map as $post_name => $page ) {
			if ( ! isset( $existing_names[ $post_name ] ) ) {
				$this->create_page( $page );
			}
		}
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

		// Check if post already exists.
		$existing = get_posts(
			array(
				'post_type'   => $post_type,
				'post_name'   => $post_name,
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);

		if ( array() !== $existing ) {
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
	 * @param string $page_id Page identifier.
	 * @return string
	 */
	public function get_post_name( string $page_id ): string {
		return $this->manager->get_config( 'prefix' ) . Manager::normalize_slug( $page_id );
	}

	/**
	 * Extract page ID from post name
	 *
	 * @param string $post_name Post name.
	 * @return string|null Page ID or null if prefix doesn't match.
	 */
	public function extract_page_id_from_post_name( string $post_name ): ?string {
		$prefix = $this->manager->get_config( 'prefix' );

		if ( strpos( $post_name, $prefix ) !== 0 ) {
			return null;
		}

		return str_replace( '_', '-', substr( $post_name, strlen( $prefix ) ) );
	}

	/**
	 * Get post by page ID
	 *
	 * @param string $page_id Page identifier.
	 * @return \WP_Post|null Post object or null if not found.
	 */
	public function get_post_by_page_id( string $page_id ): ?\WP_Post {
		$post_name = $this->get_post_name( $page_id );

		// Query for post by slug.
		$posts = get_posts(
			array(
				'post_type'      => $this->manager->get_post_type(),
				'post_name'      => $post_name,
				'post_status'    => 'any',
				'posts_per_page' => 1,
			)
		);

		return ! empty( $posts ) ? $posts[0] : null;
	}
}
