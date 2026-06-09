<?php
/**
 * Admin Header
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Admin_Header class
 *
 * Renders custom branded header for options pages.
 *
 * @since 1.3.0
 */
class Admin_Header {

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
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'in_admin_header', array( $this, 'render' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Check if we should render the header
	 *
	 * @return bool
	 */
	private function should_render(): bool {
		$screen = Path_Helper::get_current_screen();

		if ( null === $screen ) {
			return false;
		}

		$page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$config = $this->manager->get_config();

		// Check for tabs mode
		if ( 'tabs' === $config['ui']['mode'] ) {
			$admin_page = $this->manager->get_admin_page();
			if ( $admin_page && $page === $admin_page->get_page_slug() ) {
				return true;
			}
		}

		// Check for pages mode
		if ( 'pages' === $config['ui']['mode'] ) {
			$pages_list_page = $this->manager->get_pages_list_page();
			if ( $pages_list_page && $page === $pages_list_page->get_page_slug() ) {
				return true;
			}

			// Also show on individual post edit pages
			if ( $screen->post_type === $this->manager->get_post_type() && 'post' === $screen->base ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render header
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		// Get base URL (works for both plugin and composer package)
		$base_path = dirname( dirname( __DIR__ ) );
		$base_url  = str_replace( ABSPATH, home_url( '/' ), $base_path );

		// Allow custom logo URL via filter
		$logo_url = apply_filters(
			'codesoup_options_header_logo',
			$base_url . '/assets/media/code-soup-logo.jpg',
			$this->manager->get_instance_key()
		);

		$config     = $this->manager->get_config();
		$menu_label = $config['menu']['label'];

		// Get active page for tabs mode
		$active_page = null;
		if ( 'tabs' === $config['ui']['mode'] ) {
			$admin_page = $this->manager->get_admin_page();
			if ( $admin_page ) {
				$active_tab  = $admin_page->get_active_tab();
				$pages       = $this->manager->get_pages();
				$active_page = $pages[ $active_tab ] ?? null;
			}
		}

		// Allow custom header template via filter
		$template_path = apply_filters(
			'codesoup_options_header_template',
			$this->manager->get_template_path( 'header/default.php' ),
			$this->manager->get_instance_key()
		);

		// Validate template exists
		if ( file_exists( $template_path ) ) {
			require $template_path;
		}
	}

	/**
	 * Enqueue header styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( string $hook ): void {
		if ( ! $this->should_render() ) {
			return;
		}

		// Don't enqueue if styles are disabled
		$config = $this->manager->get_config();
		if ( $config['assets']['disable_styles'] ) {
			return;
		}

		wp_enqueue_style(
			'codesoup-options-header',
			Path_Helper::get_asset_url( 'css/admin-header.css' ),
			array(),
			'1.3.0'
		);
	}
}
