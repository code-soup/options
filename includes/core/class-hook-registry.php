<?php
/**
 * Hook Registry
 *
 * Manages WordPress hook registration for options pages.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Hook_Registry class
 *
 * Centralized WordPress hook registration and callbacks for Manager.
 *
 * @since 1.1.0
 */
class Hook_Registry {

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
	 * Register all WordPress hooks
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'redirect_default_post_list' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'show_creation_errors' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'disable_title_edit' ) );
		add_action( 'admin_head', array( $this, 'remove_help_tabs' ) );
		add_action( 'admin_head', array( $this, 'remove_screen_options' ) );
		add_filter( 'parent_file', array( $this, 'set_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'set_submenu_file' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'prevent_title_update' ), 10, 2 );

		// Register metaboxes
		$metabox_registry = $this->manager->get_metabox_registry();
		add_action( 'add_meta_boxes', array( $metabox_registry, 'register' ) );
	}

	/**
	 * Redirect default post list to custom pages list
	 *
	 * @return void
	 */
	public function redirect_default_post_list(): void {
		global $pagenow;

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';

		if ( $this->manager->get_post_type() !== $post_type ) {
			return;
		}

		$redirect_url = add_query_arg(
			array( 'page' => $this->manager->get_menu_slug() ),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		$pages = $this->manager->get_pages();

		if ( empty( $pages ) ) {
			return;
		}

		// Get minimum capability from all pages.
		$min_capability = 'manage_options';
		foreach ( $pages as $page ) {
			if ( current_user_can( $page->capability ) ) {
				$min_capability = $page->capability;
				break;
			}
		}

		$menu_slug     = $this->manager->get_menu_slug();
		$menu_callback = array( $this, 'handle_menu_redirect' );
		$config        = $this->manager->get_config();

		if ( ! empty( $config['menu']['parent'] ) ) {
			add_submenu_page(
				$config['menu']['parent'],
				$config['menu']['label'],
				$config['menu']['label'],
				$min_capability,
				$menu_slug,
				$menu_callback
			);
		} else {
			add_menu_page(
				$config['menu']['label'],
				$config['menu']['label'],
				$min_capability,
				$menu_slug,
				$menu_callback,
				$config['menu']['icon'],
				$config['menu']['position']
			);
		}
	}

	/**
	 * Handle menu callback
	 *
	 * @return void
	 */
	public function handle_menu_redirect(): void {
		$pages      = $this->manager->get_pages();
		$admin_page = $this->manager->get_admin_page();
		$list_page  = $this->manager->get_pages_list_page();

		if ( $admin_page ) {
			$admin_page->render();
			return;
		}

		if ( $list_page ) {
			$list_page->render();
			return;
		}

		wp_die( esc_html__( 'No pages configured.', 'codesoup-options' ) );
	}

	/**
	 * Show admin notices for page creation errors
	 *
	 * @return void
	 */
	public function show_creation_errors(): void {
		$errors = $this->manager->get_creation_errors();

		if ( empty( $errors ) ) {
			return;
		}

		foreach ( $errors as $error ) {
			Admin_Notice::error( $error, false );
		}
	}

	/**
	 * Disable title editing for options pages
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function disable_title_edit( string $hook ): void {
		if ( 'post.php' !== $hook ) {
			return;
		}

		$screen = Path_Helper::get_current_screen();
		if ( ! $screen || $screen->post_type !== $this->manager->get_post_type() ) {
			return;
		}

		wp_add_inline_style(
			'wp-admin',
			'#post-body-content #title { pointer-events: none; opacity: 0.6; }'
		);
	}

	/**
	 * Remove help tabs from admin screen
	 *
	 * @return void
	 */
	public function remove_help_tabs(): void {
		$screen = Path_Helper::get_current_screen();

		if ( ! $screen || $screen->post_type !== $this->manager->get_post_type() ) {
			return;
		}

		$screen->remove_help_tabs();
	}

	/**
	 * Remove screen options from admin screen
	 *
	 * @return void
	 */
	public function remove_screen_options(): void {
		$screen = Path_Helper::get_current_screen();

		if ( ! $screen || $screen->post_type !== $this->manager->get_post_type() ) {
			return;
		}

		add_filter( 'screen_options_show_screen', '__return_false' );
	}

	/**
	 * Prevent title update on options pages
	 *
	 * @param array $data    An array of slashed, sanitized, and processed post data.
	 * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @return array Modified post data with original title restored.
	 */
	public function prevent_title_update( array $data, array $postarr ): array {
		if ( ! isset( $postarr['ID'] ) || ! isset( $data['post_type'] ) ) {
			return $data;
		}

		if ( $data['post_type'] !== $this->manager->get_post_type() ) {
			return $data;
		}

		$original_post = get_post( $postarr['ID'] );
		if ( $original_post ) {
			$data['post_title'] = $original_post->post_title;
		}

		return $data;
	}

	/**
	 * Set parent file for submenu highlighting
	 *
	 * @param string $parent_file Parent file.
	 * @return string
	 */
	public function set_parent_file( string $parent_file ): string {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return $parent_file;
		}

		$screen = Path_Helper::get_current_screen();
		if ( ! $screen || $screen->post_type !== $this->manager->get_post_type() ) {
			return $parent_file;
		}

		return $this->manager->get_menu_slug();
	}

	/**
	 * Set submenu file for submenu highlighting
	 *
	 * @param string|null $submenu_file Submenu file.
	 * @return string|null
	 */
	public function set_submenu_file( ?string $submenu_file ): ?string {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return $submenu_file;
		}

		$screen = Path_Helper::get_current_screen();
		if ( ! $screen || $screen->post_type !== $this->manager->get_post_type() ) {
			return $submenu_file;
		}

		return $this->manager->get_menu_slug();
	}
}
