<?php
/**
 * Metabox Registry
 *
 * Manages metabox registration for options pages.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Metabox_Registry class
 *
 * Handles metabox registration in both pages and tabs modes.
 *
 * @since 1.1.0
 */
class Metabox_Registry {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Registered metaboxes
	 *
	 * @var array<Metabox>
	 */
	private array $metaboxes = array();

	/**
	 * Whether metaboxes have been sorted
	 *
	 * @var bool
	 */
	private bool $metaboxes_sorted = false;

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register a metabox
	 *
	 * @param Metabox $metabox Metabox object.
	 * @return void
	 */
	public function register_metabox( Metabox $metabox ): void {
		$this->metaboxes[]      = $metabox;
		$this->metaboxes_sorted = false;
	}

	/**
	 * Register multiple metaboxes
	 *
	 * @param array<Metabox> $metaboxes Array of metabox objects.
	 * @return void
	 */
	public function register_metaboxes_batch( array $metaboxes ): void {
		foreach ( $metaboxes as $metabox ) {
			if ( $metabox instanceof Metabox ) {
				$this->metaboxes[] = $metabox;
			}
		}
		$this->metaboxes_sorted = false;
	}

	/**
	 * Get all registered metaboxes
	 *
	 * @return array<Metabox>
	 */
	public function get_metaboxes(): array {
		return $this->metaboxes;
	}

	/**
	 * Sort metaboxes by order
	 *
	 * @return void
	 */
	private function sort_metaboxes(): void {
		if ( $this->metaboxes_sorted ) {
			return;
		}

		usort(
			$this->metaboxes,
			function ( $a, $b ) {
				return $a->order <=> $b->order;
			}
		);

		$this->metaboxes_sorted = true;
	}

	/**
	 * Register metaboxes hook callback
	 *
	 * @return void
	 */
	public function register(): void {
		$config = $this->manager->get_config();

		if ( 'tabs' === $config['ui']['mode'] ) {
			$this->register_tabs_mode();
		} else {
			$this->register_pages_mode();
		}
	}

	/**
	 * Register metaboxes for pages mode
	 *
	 * @return void
	 */
	private function register_pages_mode(): void {
		$screen = Path_Helper::get_current_screen();
		$config = $this->manager->get_config();

		if ( ! $screen || $screen->post_type !== $config['post_type'] ) {
			return;
		}

		// Remove default submitdiv.
		remove_meta_box(
			'submitdiv',
			$config['post_type'],
			'side'
		);

		// Add custom actions metabox.
		$actions_metabox = new Metabox(
			array(
				'page'     => 'all',
				'title'    => __( 'Actions', 'codesoup-options' ),
				'path'     => dirname( __DIR__ ) . '/templates/metabox/actions.php',
				'context'  => 'side',
				'priority' => 'high',
				'args'     => array(
					'manager' => $this->manager,
				),
			)
		);

		$actions_metabox->register(
			sprintf( '%s_actions', $this->manager->get_instance_key() ),
			$config['post_type']
		);

		if ( empty( $this->metaboxes ) ) {
			return;
		}

		global $post;

		if ( ! $post ) {
			return;
		}

		$post_manager = $this->manager->get_post_manager();
		$page_id      = $post_manager->extract_page_id_from_post_name( $post->post_name );

		if ( ! $page_id ) {
			return;
		}

		$this->sort_metaboxes();

		foreach ( $this->metaboxes as $metabox ) {
			if ( 'all' !== $metabox->page && $metabox->page !== $page_id ) {
				continue;
			}

			$metabox_args         = $metabox->args ?? array();
			$metabox_args['post'] = $post;

			$metabox->register(
				sprintf( '%s_%s', $this->manager->get_instance_key(), $metabox->page ),
				$config['post_type'],
				$metabox_args
			);
		}
	}

	/**
	 * Register metaboxes for tabs mode
	 *
	 * @return void
	 */
	private function register_tabs_mode(): void {
		$admin_page = $this->manager->get_admin_page();

		if ( ! $admin_page ) {
			return;
		}

		$screen = Path_Helper::get_current_screen();

		if ( ! $screen || $screen->id !== $admin_page->get_screen_id() ) {
			return;
		}

		if ( empty( $this->metaboxes ) ) {
			return;
		}

		$active_page = $admin_page->get_active_page();

		if ( ! $active_page ) {
			return;
		}

		$this->sort_metaboxes();

		foreach ( $this->metaboxes as $metabox ) {
			if ( 'all' !== $metabox->page && $metabox->page !== $active_page->id ) {
				continue;
			}

			$metabox_args          = $metabox->args ?? array();
			$metabox_args['page']  = $active_page;
			$metabox_args['__tab'] = $active_page->id;

			$metabox->register(
				sprintf( '%s_%s', $this->manager->get_instance_key(), $metabox->page ),
				$admin_page->get_screen_id(),
				$metabox_args
			);
		}
	}
}
