<?php
/**
 * Example 3: Custom Integration
 *
 * This example shows how to create a custom integration for any field framework
 * (CMB2, MetaBox.io, Carbon Fields, etc.) or your own custom solution.
 *
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;
use CodeSoup\Options\Integrations\IntegrationInterface;

/**
 * Example integration for CMB2
 *
 * This demonstrates the integration pattern. You can adapt this for any framework.
 */
class CMB2Integration implements IntegrationInterface {
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
	 * Check if CMB2 is available
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return defined( 'CMB2_LOADED' ) && CMB2_LOADED;
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! self::is_available() ) {
			add_action( 'admin_notices', array( $this, 'show_missing_notice' ) );
			return;
		}

		add_action( 'cmb2_admin_init', array( $this, 'register_metaboxes' ) );
	}

	/**
	 * Get integration name
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'CMB2';
	}

	/**
	 * Show admin notice when CMB2 is missing
	 *
	 * @return void
	 */
	public function show_missing_notice(): void {
		printf(
			'<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
			esc_html__( 'Options Manager', 'codesoup-options' ),
			esc_html__( 'CMB2 plugin is required. Please install and activate CMB2.', 'codesoup-options' )
		);
	}

	/**
	 * Register CMB2 metaboxes for all pages
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		$config = $this->manager->get_config();
		$pages  = $this->manager->get_pages();

		foreach ( $pages as $page ) {
			$post_name = $config['prefix'] . $page->id;

			// Register CMB2 metabox for this page.
			$cmb = new_cmb2_box(
				array(
					'id'           => $post_name . '_fields',
					'title'        => $page->title . ' Fields',
					'object_types' => array( $config['post_type'] ),
					'show_on'      => array(
						'key'   => 'post_name',
						'value' => $post_name,
					),
				)
			);

			// Add fields dynamically or via configuration.
			// This is just an example - customize based on your needs.
			$this->add_fields_for_page( $cmb, $page->id );
		}
	}

	/**
	 * Add fields for a specific page
	 *
	 * @param \CMB2  $cmb CMB2 instance.
	 * @param string $page_id Page ID.
	 * @return void
	 */
	private function add_fields_for_page( $cmb, string $page_id ): void {
		// Example: Add fields based on page ID.
		if ( 'general' === $page_id ) {
			$cmb->add_field(
				array(
					'name' => 'Site Title',
					'id'   => 'site_title',
					'type' => 'text',
				)
			);

			$cmb->add_field(
				array(
					'name' => 'Site Logo',
					'id'   => 'site_logo',
					'type' => 'file',
				)
			);
		}
	}
}

// ============================================================================
// Using the Custom Integration
// ============================================================================

$manager = Manager::create(
	'cmb2_settings',
	array(
		'menu_label'   => 'Site Settings',
		'integrations' => array(
			// Disable ACF.
			'acf'  => array(
				'enabled' => false,
			),
			// Enable custom CMB2 integration.
			'cmb2' => array(
				'enabled' => true,
				'class'   => 'CMB2Integration',
			),
		),
	)
);

$manager->register_page(
	array(
		'id'         => 'general',
		'title'      => 'General Settings',
		'capability' => 'manage_options',
	)
);

$manager->init();

		// Retrieve options (same API regardless of integration).
		$manager = Manager::get( 'cmb2_settings' );
		$options = $manager->get_options( 'general' );

		// ============================================================================
		// Key Points for Custom Integrations
		// ============================================================================

		/**
		 * 1. Implement IntegrationInterface
		 *    - Required methods: __construct(), is_available(), register_hooks(), get_name()
		 *
		 * 2. Check availability in is_available()
		 *    - Return false if the framework is not installed/active
		 *
		 * 3. Register hooks in register_hooks()
		 *    - Hook into the framework's registration system
		 *    - Show admin notice if framework is missing
		 *
		 * 4. Access Manager instance
		 *    - Use $this->manager to get config, pages, etc.
		 *    - Use $this->manager->get_config() for configuration
		 *    - Use $this->manager->get_pages() for registered pages
		 *
		 * 5. Register in config
		 *    - Add your integration class to the 'integrations' config array
		 *    - Set 'enabled' => true and 'class' => 'YourClassName'
		 *
		 * 6. The Manager will:
		 *    - Validate your class implements IntegrationInterface
		 *    - Check is_available() before instantiating
		 *    - Call register_hooks() automatically
		 *    - Log errors if integration fails to load
		 */
