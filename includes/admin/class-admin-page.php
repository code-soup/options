<?php
/**
 * Admin Page Handler
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Admin_Page class
 *
 * Handles custom admin page rendering for tabs mode.
 *
 * @since 1.2.0
 */
class Admin_Page {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Instance key
	 *
	 * @var string
	 */
	private string $instance_key;

	/**
	 * Current active tab
	 *
	 * @var string|null
	 */
	private ?string $active_tab = null;

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager      = $manager;
		$this->instance_key = $manager->get_instance_key();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Get page slug for admin menu
	 *
	 * @return string
	 */
	public function get_page_slug(): string {
		return sprintf(
			'codesoup-options-%s',
			$this->instance_key
		);
	}

	/**
	 * Get screen ID
	 *
	 * @return string
	 */
	public function get_screen_id(): string {
		$config = $this->manager->get_config();
		$page_slug = $this->get_page_slug();

		if ( ! empty( $config['menu']['parent'] ) ) {
			return $config['menu']['parent'] . '_page_' . $page_slug;
		}

		return $page_slug;
	}

	/**
	 * Get active tab ID
	 *
	 * @return string|null
	 */
	public function get_active_tab(): ?string {
		if ( null === $this->active_tab ) {
			$this->active_tab = $this->determine_active_tab();
		}

		return $this->active_tab;
	}

	/**
	 * Get active page object
	 *
	 * @return \CodeSoup\Options\Page|null
	 */
	public function get_active_page(): ?\CodeSoup\Options\Page {
		$active_tab = $this->get_active_tab();
		if ( ! $active_tab ) {
			return null;
		}

		$pages = $this->manager->get_pages();
		return $pages[ $active_tab ] ?? null;
	}

	/**
	 * Determine active tab from URL or default to first page
	 *
	 * @return string|null
	 */
	private function determine_active_tab(): ?string {
		$pages = $this->manager->get_pages();

		if ( empty( $pages ) ) {
			return null;
		}

		$tab = isset( $_GET['tab'] )
			? sanitize_key( $_GET['tab'] )
			: null;

		if ( $tab && isset( $pages[ $tab ] ) ) {
			$page = $pages[ $tab ];
			if ( current_user_can( $page->capability ) ) {
				return $tab;
			}
		}

		foreach ( $pages as $page ) {
			if ( current_user_can( $page->capability ) ) {
				return $page->id;
			}
		}

		return null;
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public function render(): void {
		$active_tab = $this->get_active_tab();

		if ( ! $active_tab ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'codesoup-options' )
			);
		}

		$tab_pages    = $this->manager->get_pages();
		$tab_position = $this->manager->get_config( 'tab_position' );

		require $this->manager->get_template_path( 'tabs/wrapper.php' );
	}

	/**
	 * Get tab URL
	 *
	 * @param string $tab_id Tab ID.
	 * @return string
	 */
	public function get_tab_url( string $tab_id ): string {
		return add_query_arg(
			array(
				'page' => $this->get_page_slug(),
				'tab'  => $tab_id,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Add body class
	 *
	 * @param string $classes Current body classes.
	 * @return string
	 */
	public function add_body_class( string $classes ): string {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( $page === $this->get_page_slug() ) {
			$classes .= ' codesoup-options-tabbed-ui';
		}

		return $classes;
	}

	/**
	 * Enqueue assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page !== $this->get_page_slug() ) {
			return;
		}

		$config = $this->manager->get_config();

		// Enqueue styles unless disabled
		if ( ! $config['assets']['disable_styles'] ) {
			wp_enqueue_style(
				'codesoup-options-tabs',
				Path_Helper::get_asset_url( 'css/admin-tabs.css' ),
				array(),
				'1.2.0'
			);
		}

		// Enqueue scripts unless disabled
		if ( ! $config['assets']['disable_scripts'] ) {
			wp_enqueue_script(
				'codesoup-options-tabs',
				Path_Helper::get_asset_url( 'js/admin-tabs.js' ),
				array( 'jquery' ),
				'1.2.0',
				true
			);
		}

		// Always enqueue WordPress core scripts
		wp_enqueue_script( 'postbox' );
		wp_enqueue_style( 'wp-admin' );
	}
}
