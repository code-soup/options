<?php
/**
 * Admin Page Handler
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * AdminPage class
 *
 * Handles custom admin page rendering for tabs mode.
 *
 * @since 1.2.0
 */
class AdminPage {

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

		add_action(
			'admin_enqueue_scripts',
			array(
				$this,
				'enqueue_assets',
			)
		);
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
		return sprintf(
			'codesoup-options-%s',
			$this->instance_key
		);
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

		$pages        = $this->manager->get_pages();
		$tab_position = $this->manager->get_config( 'tab_position' );

		require __DIR__ . '/ui/templates/admin-page-wrapper.php';
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
	 * Enqueue assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->get_page_slug() ) {
			return;
		}

		$plugin_dir_url = plugin_dir_url( dirname( __FILE__ ) );

		wp_enqueue_style(
			'codesoup-options-tabs',
			$plugin_dir_url . 'assets/css/admin-tabs.css',
			array(),
			'1.2.0'
		);

		wp_enqueue_script(
			'codesoup-options-tabs',
			$plugin_dir_url . 'assets/js/admin-tabs.js',
			array( 'jquery' ),
			'1.2.0',
			true
		);

		wp_enqueue_script( 'postbox' );
		wp_enqueue_style( 'wp-admin' );
	}
}

