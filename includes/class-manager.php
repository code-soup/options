<?php
/**
 * Options Manager
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;

/**
 * Manager class for Options
 *
 * Manages options pages using custom post types with instance key support.
 * Supports multiple instances with different instance keys.
 *
 * Architecture uses composition with specialized components:
 * - Post_Manager: Post lifecycle and creation
 * - Hook_Registry: WordPress hook registration
 * - Metabox_Registry: Metabox registration and rendering
 * - Integration_Manager: Third-party integration loading
 * - Config_Helper: Configuration normalization, validation, and BC layer
 *
 * @see https://github.com/code-soup/codesoup-options/blob/main/README.md Documentation
 * @see https://github.com/code-soup/codesoup-options/blob/main/docs/api.md API Reference
 *
 * @since 1.0.0
 * @version 1.1.0
 */
class Manager {

	/**
	 * Meta key for storing page capability
	 */
	public const META_KEY_CAPABILITY = '_codesoup_options_capability';

	/**
	 * Default configuration values
	 */
	private const CONFIG_DEFAULTS = array(
		'post_type'      => 'cs_options',
		'prefix'         => 'cs_opt_',
		'revisions'      => false,
		'debug'          => false,
		'menu'           => array(
			'label'    => 'Codesoup Options',
			'icon'     => 'dashicons-admin-generic',
			'position' => 99,
			'parent'   => null,
		),
		'ui'             => array(
			'mode'          => 'pages',
			'tab_position'  => 'top',
			'templates_dir' => null,
		),
		'assets'         => array(
			'disable_styles'   => false,
			'disable_scripts'  => false,
			'disable_branding' => false,
		),
		'integrations'   => array(
			'acf' => array(
				'enabled' => true,
				'class'   => 'CodeSoup\Options\Integrations\ACF\Init',
			),
		),
	);

	/**
	 * Registry of all Manager instances
	 *
	 * @var array<string, Manager>
	 */
	private static array $instances = array();

	/**
	 * Instance identifier
	 *
	 * @var string
	 */
	private string $instance_key;

	/**
	 * Configuration options
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Registered pages
	 *
	 * @var array<Page>
	 */
	private array $pages = array();

	/**
	 * Whether hooks have been registered
	 *
	 * @var bool
	 */
	private bool $hooks_registered = false;

	/**
	 * Page creation errors
	 *
	 * @var array
	 */
	private array $creation_errors = array();

	/**
	 * Track created page IDs to prevent duplicates
	 *
	 * @var array
	 */
	private array $created_pages = array();

	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Admin page handler (for tabs mode)
	 *
	 * @var Admin_Page|null
	 */
	private ?Admin_Page $admin_page = null;

	/**
	 * Pages list page handler (for pages mode)
	 *
	 * @var Pages_List_Page|null
	 */
	private ?Pages_List_Page $pages_list_page = null;

	/**
	 * Form handler
	 *
	 * @var Form_Handler|null
	 */
	private ?Form_Handler $form_handler = null;

	/**
	 * Admin header handler
	 *
	 * @var Admin_Header|null
	 */
	private ?Admin_Header $admin_header = null;

	/**
	 * Post manager
	 *
	 * @var Post_Manager
	 */
	private Post_Manager $post_manager;

	/**
	 * Hook registry
	 *
	 * @var Hook_Registry
	 */
	private Hook_Registry $hook_registry;

	/**
	 * Metabox registry
	 *
	 * @var Metabox_Registry
	 */
	private Metabox_Registry $metabox_registry;

	/**
	 * Integration manager
	 *
	 * @var Integration_Manager
	 */
	private Integration_Manager $integration_manager;

	/**
	 * Create a new Manager instance
	 *
	 * @param string $instance_key Unique instance identifier.
	 * @param array  $config Configuration options.
	 * @return Manager
	 */
	public static function create( string $instance_key, array $config = array() ): Manager {
		if ( isset( self::$instances[ $instance_key ] ) ) {
			return self::$instances[ $instance_key ];
		}

		$instance                         = new self( $instance_key, $config );
		self::$instances[ $instance_key ] = $instance;

		return $instance;
	}

	/**
	 * Get an existing Manager instance
	 *
	 * @param string $instance_key Instance identifier.
	 * @return Manager|null
	 */
	public static function get( string $instance_key ): ?Manager {
		return self::$instances[ $instance_key ] ?? null;
	}

	/**
	 * Save options data for native metaboxes
	 *
	 * Use this method in your save_post hook to save custom metabox data.
	 * Data is serialized and stored in post_content for retrieval via get_options().
	 *
	 * This method uses direct database updates to avoid triggering save_post hooks,
	 * preventing infinite loops when called from within save_post callbacks.
	 *
	 * @see https://github.com/code-soup/codesoup-options/blob/main/docs/native.md Native metabox usage
	 *
	 * @param array $args {
	 *     Arguments for saving options.
	 *
	 *     @type int    $post_id Post ID.
	 *     @type string $nonce   Nonce value for verification.
	 *     @type array  $data    Data to save (will be serialized). IMPORTANT: Developers must sanitize
	 *                           all input data before calling this method. Never pass unsanitized $_POST
	 *                           data directly. Use appropriate sanitization functions like
	 *                           sanitize_text_field(), sanitize_email(), etc.
	 * }
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function save_options( array $args ) {
		try {
			if ( ! isset( $args['post_id'] ) || ! is_int( $args['post_id'] ) ) {
				throw new \InvalidArgumentException(
					__( 'Post ID is required and must be an integer.', 'codesoup-options' )
				);
			}

			if ( ! isset( $args['nonce'] ) || ! is_string( $args['nonce'] ) ) {
				throw new \InvalidArgumentException(
					__( 'Nonce is required and must be a string.', 'codesoup-options' )
				);
			}

			if ( ! isset( $args['data'] ) || ! is_array( $args['data'] ) ) {
				throw new \InvalidArgumentException(
					__( 'Data is required and must be an array.', 'codesoup-options' )
				);
			}

			$post_id = $args['post_id'];
			$nonce   = $args['nonce'];
			$data    = $args['data'];

			$post = get_post( $post_id );

			if ( ! $post ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: post ID */
						__( 'Post with ID %d does not exist.', 'codesoup-options' ),
						$post_id
					)
				);
			}

			if ( $post->post_type !== $this->config['post_type'] ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: post ID */
						__( 'Post ID %d is not a valid options post.', 'codesoup-options' ),
						$post_id
					)
				);
			}

			if ( ! wp_verify_nonce( $nonce, 'update-post_' . $post_id ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: post ID */
						__( 'Nonce verification failed for post %d.', 'codesoup-options' ),
						$post_id
					)
				);
			}

			if ( ! $this->can_edit_page( $post_id ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: post ID */
						__( 'Current user does not have permission to edit post %d.', 'codesoup-options' ),
						$post_id
					)
				);
			}

			$serialized = maybe_serialize( $data );

			// Use direct database update to avoid triggering save_post hooks
			// which would cause infinite loops when called from save_post.
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Intentional to prevent infinite loops.
			$result = $wpdb->update(
				$wpdb->posts,
				array(
					'post_content' => $serialized,
				),
				array(
					'ID' => $post_id,
				),
				array(
					'%s',
				),
				array(
					'%d',
				)
			);

			if ( false === $result ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: post ID */
						__( 'Failed to save options for post %d.', 'codesoup-options' ),
						$post_id
					)
				);
			}

			// Clear post cache to ensure fresh data on next read.
			clean_post_cache( $post_id );

			return true;
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'save_options_failed',
				$e->getMessage()
			);
		}
	}


	/**
	 * Get all Manager instances
	 *
	 * @return array<string, Manager>
	 */
	public static function get_all(): array {
		return self::$instances;
	}

	/**
	 * Destroy a Manager instance
	 *
	 * Removes the instance from memory and clears ACF location registry.
	 *
	 * @param string $instance_key Instance identifier.
	 * @return bool True if instance was destroyed, false if not found.
	 */
	public static function destroy( string $instance_key ): bool {
		if ( ! isset( self::$instances[ $instance_key ] ) ) {
			return false;
		}

		$instance = self::$instances[ $instance_key ];

		// Clear ACF Location static registry for this instance.
		if ( isset( $instance->integrations['acf'] ) ) {
			$acf_integration = $instance->integrations['acf'];
			if ( method_exists( $acf_integration, 'clear_location_registry' ) ) {
				$acf_integration->clear_location_registry( $instance_key );
			}
		}

		unset( self::$instances[ $instance_key ] );
		return true;
	}

	/**
	 * Debug instance - get complete state information
	 *
	 * Returns configuration, registered pages, and current values for all pages.
	 *
	 * WARNING: This method returns raw data that may contain user input.
	 * Always escape output when displaying debug information:
	 * - Use esc_html() for text
	 * - Use wp_json_encode() for structured data
	 * - Use print_r() with htmlspecialchars() for arrays
	 *
	 * @param string $instance_key Instance identifier.
	 * @return array Debug information or error.
	 */
	public static function debug( string $instance_key ): array {
		$instance = self::get( $instance_key );

		if ( ! $instance ) {
			return array(
				'success' => false,
				'error'   => __( 'Instance not found', 'codesoup-options' ),
			);
		}

		$config = $instance->get_config();
		$pages  = $instance->get_pages();

		$debug_info = array(
			'success'      => true,
			'instance_key' => $instance_key,
			'config'       => $config,
			'pages'        => array(),
		);

		foreach ( $pages as $page ) {
			$post_name = self::normalize_slug( $config['prefix'] . $page->id );

			$page_data = array(
				'id'          => $page->id,
				'title'       => $page->title,
				'capability'  => $page->capability,
				'description' => $page->description,
				'post_name'   => $post_name,
				'values'      => $instance->get_options( $page->id ),
			);

			$debug_info['pages'][] = $page_data;
		}

		return $debug_info;
	}

	/**
	 * Migrate instance configuration and data
	 *
	 * @deprecated Use Migration::migrate() instead
	 * @param string $instance_key Instance identifier.
	 * @param array  $old_config Old configuration with 'post_type' and 'prefix'.
	 * @param array  $new_pages Array of new page definitions with updated capabilities.
	 * @return array Migration results with counts.
	 */
	public static function migrate( string $instance_key, array $old_config, array $new_pages = array() ): array {
		return Migration::migrate( $instance_key, $old_config, $new_pages );
	}

	/**
	 * Private constructor
	 *
	 * @param string $instance_key Unique instance identifier.
	 * @param array  $config Configuration options.
	 * @throws InvalidArgumentException If config validation fails.
	 */
	private function __construct( string $instance_key, array $config ) {
		$this->instance_key = sanitize_key( $instance_key );

		// Apply backward compatibility layer.
		$config = Config_Helper::normalize( $config, $instance_key );

		// Merge with defaults.
		$this->config = array_replace_recursive(
			self::CONFIG_DEFAULTS,
			array(
				'post_type' => $this->instance_key . '_options',
				'prefix'    => $this->instance_key . '_options_',
			),
			$config
		);

		// Sanitize config values.
		$this->config['menu']['label'] = sanitize_text_field( $this->config['menu']['label'] );
		$this->config['post_type']     = sanitize_key( $this->config['post_type'] );
		$this->config['prefix']        = sanitize_key( $this->config['prefix'] );

		// Initialize logger (needed for validation).
		$this->logger = new Logger( $this->instance_key, $this->config['debug'] );

		// Validate configuration.
		Config_Helper::validate( $this->config, $this->logger );

		// Force pages mode if any integration is active.
		// Tabs mode only works reliably with native metaboxes.
		if ( Integration_Manager::has_active_integrations( $this->config['integrations'] ) ) {
			$this->config['ui']['mode'] = 'pages';
		}

		$this->post_manager        = new Post_Manager( $this );
		$this->hook_registry       = new Hook_Registry( $this );
		$this->metabox_registry    = new Metabox_Registry( $this );
		$this->integration_manager = new Integration_Manager( $this );

		if ( 'tabs' === $this->config['ui']['mode'] ) {
			$this->admin_page   = new Admin_Page( $this );
			$this->form_handler = new Form_Handler( $this );
		} else {
			$this->pages_list_page = new Pages_List_Page( $this );
		}

		$this->admin_header = new Admin_Header( $this );

		$this->integration_manager->load( $this->config['integrations'] );
	}

	/**
	 * Register a new options page
	 *
	 * @see https://github.com/code-soup/codesoup-options/blob/main/docs/api.md#page-configuration Page configuration
	 *
	 * @param array $args Page arguments.
	 * @return void
	 */
	public function register_page( array $args ): void {
		$page                     = new Page( $args );
		$this->pages[ $page->id ] = $page;
	}

	/**
	 * Register multiple options pages
	 *
	 * @param array $pages Array of page arguments.
	 * @return void
	 * @throws \InvalidArgumentException If pages array is invalid.
	 */
	public function register_pages( array $pages ): void {
		if ( empty( $pages ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Pages array cannot be empty.', 'codesoup-options' )
			);
		}

		foreach ( $pages as $index => $page_args ) {
			if ( ! is_array( $page_args ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: 1: array index, 2: type of value */
						esc_html__( 'Page at index %1$d must be an array, %2$s given.', 'codesoup-options' ),
						esc_html( (string) $index ),
						esc_html( gettype( $page_args ) )
					)
				);
			}

			$this->register_page( $page_args );
		}
	}

	/**
	 * Register a metabox for a specific page
	 *
	 * @see https://github.com/code-soup/codesoup-options/blob/main/docs/api.md#metabox-configuration Metabox configuration
	 *
	 * @param array<string,mixed> $args Metabox configuration.
	 * @return void
	 * @throws \InvalidArgumentException If page ID doesn't exist.
	 */
	public function register_metabox( array $args ): void {
		// Validate page exists
		if ( isset( $args['page'] ) ) {
			$page_exists = false;
			foreach ( $this->pages as $page ) {
				if ( $page->id === sanitize_key( $args['page'] ) ) {
					$page_exists = true;
					break;
				}
			}

			if ( ! $page_exists ) {
				$this->logger->warning(
					sprintf(
						'Metabox registered for non-existent page "%s". Available pages: %s',
						$args['page'],
						implode( ', ', array_map( fn( $p ) => $p->id, $this->pages ) )
					)
				);
			}
		}

		$metabox = new Metabox( $args );
		$this->metabox_registry->register_metabox( $metabox );
	}

	/**
	 * Initialize the manager
	 *
	 * @return void
	 */
	public function init(): void {
		// Register all WordPress hooks.
		$this->register_hooks();
	}



	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		if ( $this->hooks_registered ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		// Register post management hooks.
		add_action( 'init', array( $this->post_manager, 'register_post_type' ) );
		add_action( 'current_screen', array( $this->post_manager, 'maybe_ensure_pages_exist' ) );

		// Register metabox hooks.
		add_action( 'add_meta_boxes', array( $this->metabox_registry, 'register' ) );

		// Register UI hooks via Hook_Registry.
		$this->hook_registry->register();

		// Register integration hooks.
		$this->integration_manager->register_hooks();

		if ( $this->form_handler ) {
			$this->form_handler->register_hooks();
		}

		if ( $this->admin_header && ! $this->config['assets']['disable_branding'] ) {
			$this->admin_header->register_hooks();
		}

		$this->hooks_registered = true;
	}

	/**
	 * Ensure pages exist - delegates to Post_Manager
	 *
	 * @return void
	 */
	private function ensure_pages_exist(): void {
		$this->post_manager->ensure_pages_exist();
	}



	/**
	 * Render tabs navigation on post edit page
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_tabs_navigation( \WP_Post $post ): void {
		if ( $post->post_type !== $this->get_post_type() ) {
			return;
		}

		if ( 'tabs' === $this->config['ui']['mode'] && $this->admin_page ) {
			$this->admin_page->render_tabs_on_edit_page();
		}
	}





	/**
	 * Check if current user can edit a page
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function can_edit_page( int $post_id ): bool {
		$capability = $this->get_page_capability( $post_id );

		if ( empty( $capability ) ) {
			return false;
		}

		return current_user_can( $capability );
	}

	/**
	 * Get page capability by post ID
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Capability or null if not found.
	 */
	public function get_page_capability( int $post_id ): ?string {
		$capability = get_post_meta( $post_id, self::META_KEY_CAPABILITY, true );

		return ! empty( $capability ) ? $capability : null;
	}


	/**
	 * Get menu slug
	 *
	 * @return string
	 */
	public function get_menu_slug(): string {
		if ( 'tabs' === $this->config['ui']['mode'] ) {
			return $this->admin_page->get_page_slug();
		} else {
			return $this->pages_list_page->get_page_slug();
		}
	}



	/**
	 * Get options by page ID (instance method)
	 *
	 * Retrieves options from post_content (serialized array).
	 *
	 * @param string $page_id Page identifier.
	 * @return array Options array.
	 */
	public function get_options( $page_id ): array {
		$post = $this->get_post_by_page_id( $page_id );

		if ( ! $post ) {
			return array();
		}

		$content = $post->post_content;

		if ( empty( $content ) ) {
			return array();
		}

		// Use maybe_unserialize for safety.
		$options = maybe_unserialize( $content );
		$options = is_array( $options ) ? $options : array();

		return $options;
	}

	/**
	 * Get single option by page ID and field name
	 *
	 * Retrieves single field value from postmeta using the specified integration.
	 *
	 * @param string $page_id Page identifier.
	 * @param string $field_name field name.
	 * @param mixed  $default_value Default value if field not found.
	 * @param string $integration Integration key to use (default: 'acf').
	 * @return mixed Field value or default.
	 */
	public function get_option( string $page_id, string $field_name, $default_value = null, string $integration = 'acf' ) {
		if ( ! $this->has_integration( $integration ) ) {
			return $default_value;
		}

		$integration_instance = $this->get_integration( $integration );

		if ( ! $integration_instance || ! method_exists( $integration_instance, 'get_option' ) ) {
			return $default_value;
		}

		return $integration_instance->get_option( $page_id, $field_name, $default_value );
	}

	/**
	 * Normalize slug by converting underscores to dashes
	 *
	 * @param string $slug Slug to normalize.
	 * @return string Normalized slug with dashes.
	 */
	public static function normalize_slug( string $slug ): string {
		return str_replace( '_', '-', sanitize_title( $slug ) );
	}

	/**
	 * Get post name from page ID - delegates to Post_Manager
	 *
	 * @param string $page_id Page identifier.
	 * @return string
	 */
	private function get_post_name( string $page_id ): string {
		return $this->post_manager->get_post_name( $page_id );
	}

	/**
	 * Extract page ID from post name - delegates to Post_Manager
	 *
	 * @param string $post_name Post name.
	 * @return string|null Page ID or null if prefix doesn't match.
	 */
	private function extract_page_id_from_post_name( string $post_name ): ?string {
		return $this->post_manager->extract_page_id_from_post_name( $post_name );
	}



	/**
	 * Get post by page ID - delegates to Post_Manager
	 *
	 * @param string $page_id Page identifier.
	 * @return \WP_Post|null Post object or null if not found.
	 */
	public function get_post_by_page_id( string $page_id ): ?\WP_Post {
		return $this->post_manager->get_post_by_page_id( $page_id );
	}



	/**
	 * Get instance key
	 *
	 * @return string
	 */
	public function get_instance_key(): string {
		return $this->instance_key;
	}

	/**
	 * Get config
	 *
	 * Supports both new nested array keys and deprecated flat keys (backward compatibility).
	 *
	 * @see https://github.com/code-soup/codesoup-options/blob/main/docs/api.md Configuration options
	 * @see https://github.com/code-soup/codesoup-options/blob/main/docs/migration-v1.1.md Deprecated keys
	 *
	 * @param string|null $key Optional config key to retrieve.
	 * @return mixed Full config array if no key provided, specific value if key provided, null if key not found.
	 */
	public function get_config( ?string $key = null ) {
		if ( null === $key ) {
			return $this->config;
		}

		// Check if deprecated key needs translation.
		$array_path = Config_Helper::translate_key( $key );

		if ( $array_path ) {
			// Access nested config using array path.
			$value = $this->config;
			foreach ( $array_path as $path_key ) {
				if ( ! isset( $value[ $path_key ] ) ) {
					return null;
				}
				$value = $value[ $path_key ];
			}
			return $value;
		}

		// Direct access for non-deprecated keys.
		return $this->config[ $key ] ?? null;
	}

	/**
	 * Get pages
	 *
	 * @return array<Page>
	 */
	public function get_pages(): array {
		return $this->pages;
	}

	/**
	 * Get post type
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		return $this->config['post_type'];
	}

	/**
	 * Get logger instance
	 *
	 * @return Logger
	 */
	public function get_logger(): Logger {
		return $this->logger;
	}

	/**
	 * Get integration instance - delegates to Integration_Manager
	 *
	 * @param string $key Integration key.
	 * @return \CodeSoup\Options\Integrations\IntegrationInterface|null Integration instance or null if not found.
	 */
	public function get_integration( string $key ) {
		return $this->integration_manager->get( $key );
	}

	/**
	 * Check if integration is loaded - delegates to Integration_Manager
	 *
	 * @param string $key Integration key.
	 * @return bool True if integration is loaded and active.
	 */
	public function has_integration( string $key ): bool {
		return $this->integration_manager->has( $key );
	}

	/**
	 * Get all loaded integrations - delegates to Integration_Manager
	 *
	 * @return array<string, object> Array of loaded integration instances.
	 */
	public function get_integrations(): array {
		return $this->integration_manager->get_all();
	}

	/**
	 * Get admin page handler
	 *
	 * @return Admin_Page|null
	 */
	public function get_admin_page(): ?Admin_Page {
		return $this->admin_page;
	}

	/**
	 * Get pages list page handler
	 *
	 * @return Pages_List_Page|null
	 */
	public function get_pages_list_page(): ?Pages_List_Page {
		return $this->pages_list_page;
	}

	/**
	 * Get template path
	 *
	 * Returns path to template file, checking custom templates_dir first if configured.
	 *
	 * @param string $relative_path Relative path from templates directory (e.g., 'tabs/wrapper.php').
	 * @return string Full path to template file.
	 */
	public function get_template_path( string $relative_path ): string {
		$custom_dir = $this->config['ui']['templates_dir'];

		if ( $custom_dir && file_exists( trailingslashit( $custom_dir ) . $relative_path ) ) {
			return trailingslashit( $custom_dir ) . $relative_path;
		}

		return dirname( __DIR__ ) . '/includes/templates/' . $relative_path;
	}

	/**
	 * Get creation errors
	 *
	 * @return array
	 */
	public function get_creation_errors(): array {
		return $this->creation_errors;
	}

	/**
	 * Get post manager instance
	 *
	 * @return Post_Manager
	 */
	public function get_post_manager(): Post_Manager {
		return $this->post_manager;
	}

	/**
	 * Get metabox registry instance
	 *
	 * @return Metabox_Registry
	 */
	public function get_metabox_registry(): Metabox_Registry {
		return $this->metabox_registry;
	}
}
