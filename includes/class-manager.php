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
 * ## Error Handling Strategy
 *
 * This class uses a consistent error handling approach:
 *
 * 1. **Constructors and Validation** - Throw exceptions for invalid configuration
 *    - InvalidArgumentException for invalid config values
 *    - Exceptions are translatable for user-facing error messages
 *    - Fail fast to prevent invalid state
 *
 * 2. **Integration Loading** - Log warnings/errors and continue
 *    - Missing integration classes: warning + skip
 *    - Interface violations: error + skip
 *    - Unavailable dependencies: info + skip
 *    - Allows partial functionality when integrations fail
 *
 * 3. **Page Creation** - Log errors and store for display
 *    - Duplicate slugs: error + attempt recovery
 *    - Creation failures: error + store in $creation_errors
 *    - Errors shown via admin_notices
 *    - Non-blocking to allow other pages to be created
 *
 * 4. **Data Operations** - Log errors and return safe defaults
 *    - Cache failures: log + continue without cache
 *    - Database errors: log + return empty/false
 *    - Graceful degradation for better UX
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Meta key for storing page capability
	 */
	public const META_KEY_CAPABILITY = '_codesoup_options_capability';

	/**
	 * Meta key for storing page description
	 */
	public const META_KEY_DESCRIPTION = '_codesoup_options_description';

	/**
	 * Default configuration values
	 */
	private const CONFIG_DEFAULTS = array(
		'menu_position'  => 99,
		'menu_icon'      => 'dashicons-admin-generic',
		'menu_label'     => 'Codesoup Options',
		'revisions'      => false,
		'parent_menu'    => null,
		'cache_duration' => HOUR_IN_SECONDS,
		'debug'          => false,
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
	 * Loaded integrations
	 *
	 * @var array
	 */
	private array $integrations = array();

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
	 * Cache handler
	 *
	 * @var Cache
	 */
	private Cache $cache;

	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	private Logger $logger;

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
	 * Example:
	 * add_action( 'save_post', function( $post_id ) {
	 *     $manager = Manager::get( 'site_settings' );
	 *     if ( isset( $_POST['my_fields'], $_POST['_wpnonce'] ) ) {
	 *         $manager->save_options(
	 *             array(
	 *                 'post_id' => $post_id,
	 *                 'nonce'   => $_POST['_wpnonce'],
	 *                 'data'    => $_POST['my_fields'],
	 *             )
	 *         );
	 *     }
	 * } );
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

			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $serialized,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %s: error message */
						__( 'Failed to save options: %s', 'codesoup-options' ),
						$result->get_error_message()
					)
				);
			}

			$this->invalidate_cache( $post_id );

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
	 * Clears all cached data and removes the instance from memory.
	 *
	 * @param string $instance_key Instance identifier.
	 * @return bool True if instance was destroyed, false if not found.
	 */
	public static function destroy( string $instance_key ): bool {
		if ( ! isset( self::$instances[ $instance_key ] ) ) {
			return false;
		}

		$instance = self::$instances[ $instance_key ];

		// Clear all cached data for this instance.
		$instance->cache->clear_all();

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
		$this->config       = array_merge(
			self::CONFIG_DEFAULTS,
			array(
				'post_type' => $this->instance_key . '_options',
				'prefix'    => $this->instance_key . '_options_',
			),
			$config
		);

		// Sanitize config values.
		$this->config['menu_label'] = sanitize_text_field( $this->config['menu_label'] );
		$this->config['post_type']  = sanitize_key( $this->config['post_type'] );
		$this->config['prefix']     = sanitize_key( $this->config['prefix'] );

		$this->validate_config();

		$cache_group  = 'cs_opt_' . $this->config['prefix'];
		$this->cache  = new Cache( $this->instance_key, $cache_group, $this->config['cache_duration'] );
		$this->logger = new Logger( $this->instance_key, $this->config['debug'] );

		$this->load_integrations();
	}

	/**
	 * Validate configuration options
	 *
	 * @return void
	 * @throws \InvalidArgumentException If validation fails.
	 */
	private function validate_config(): void {
		// Validate required config keys exist.
		$required_keys = array( 'post_type', 'prefix', 'menu_label' );
		foreach ( $required_keys as $key ) {
			if ( empty( $this->config[ $key ] ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %s: configuration key name */
						esc_html__( 'Config "%s" is required.', 'codesoup-options' ),
						esc_html( $key )
					)
				);
			}
		}

		// Validate menu_position is numeric and within range.
		if ( isset( $this->config['menu_position'] ) ) {
			if ( ! is_numeric( $this->config['menu_position'] ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %s: type of the value provided */
						esc_html__( 'Config "menu_position" must be numeric, "%s" given.', 'codesoup-options' ),
						esc_html( gettype( $this->config['menu_position'] ) )
					)
				);
			}

			$position = (int) $this->config['menu_position'];
			if ( $position < 1 || $position > 100 ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: position value */
						esc_html__( 'Config "menu_position" must be between 1 and 100, %d given.', 'codesoup-options' ),
						esc_html( (string) $position )
					)
				);
			}
		}

		// Validate cache_duration is positive integer.
		if ( isset( $this->config['cache_duration'] ) ) {
			if ( ! is_int( $this->config['cache_duration'] ) || $this->config['cache_duration'] <= 0 ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %s: type and value of the provided cache_duration */
						esc_html__( 'Config "cache_duration" must be a positive integer, %s given.', 'codesoup-options' ),
						esc_html(
							is_int( $this->config['cache_duration'] )
								? (string) $this->config['cache_duration']
								: gettype( $this->config['cache_duration'] )
						)
					)
				);
			}

			// Warn if cache duration is unreasonably long (more than 1 week).
			if ( $this->config['cache_duration'] > WEEK_IN_SECONDS ) {
				$this->logger->warning(
					sprintf(
						/* translators: %d: cache duration in seconds */
						__( 'Cache duration is very long (%d seconds). Consider using a shorter duration.', 'codesoup-options' ),
						$this->config['cache_duration']
					)
				);
			}
		}

		// Validate menu_icon starts with dashicons- or is a valid URL/base64.
		if ( isset( $this->config['menu_icon'] ) ) {
			$icon = $this->config['menu_icon'];
			if ( ! empty( $icon ) &&
				strpos( $icon, 'dashicons-' ) !== 0 &&
				strpos( $icon, 'data:image' ) !== 0 &&
				! filter_var( $icon, FILTER_VALIDATE_URL ) ) {
				throw new \InvalidArgumentException(
					esc_html__( 'Config "menu_icon" must be a dashicon class, data URI, or valid URL.', 'codesoup-options' )
				);
			}
		}

		// Validate integrations config structure.
		if ( isset( $this->config['integrations'] ) && ! is_array( $this->config['integrations'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "integrations" must be an array.', 'codesoup-options' )
			);
		}

		// Validate revisions is boolean.
		if ( isset( $this->config['revisions'] ) && ! is_bool( $this->config['revisions'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "revisions" must be a boolean.', 'codesoup-options' )
			);
		}

		// Validate debug is boolean.
		if ( isset( $this->config['debug'] ) && ! is_bool( $this->config['debug'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "debug" must be a boolean.', 'codesoup-options' )
			);
		}

		// Validate prefix doesn't use reserved values.
		$reserved_prefixes = array(
			'wp_',
			'wordpress_',
			'admin_',
			'post_',
			'page_',
			'user_',
			'option_',
			'meta_',
		);

		$prefix = $this->config['prefix'];

		foreach ( $reserved_prefixes as $reserved ) {
			if ( strpos( $prefix, $reserved ) === 0 ) {
				$this->logger->warning(
					sprintf(
						/* translators: 1: prefix, 2: reserved prefix */
						__( 'Prefix "%1$s" starts with reserved prefix "%2$s". This may cause conflicts.', 'codesoup-options' ),
						$prefix,
						$reserved
					)
				);
			}
		}

		// Check if prefix is too short (increases collision risk).
		if ( strlen( $prefix ) < 3 ) {
			$this->logger->warning(
				sprintf(
					/* translators: 1: prefix, 2: character count */
					__( 'Prefix "%1$s" is very short (%2$d characters). Consider using a longer, more unique prefix.', 'codesoup-options' ),
					$prefix,
					strlen( $prefix )
				)
			);
		}

		// Check for existing post types with similar names.
		$post_types = get_post_types( array(), 'names' );
		$post_type  = $this->config['post_type'];

		foreach ( $post_types as $existing_type ) {
			if ( $existing_type !== $post_type && strpos( $existing_type, $prefix ) === 0 ) {
				$this->logger->warning(
					sprintf(
						/* translators: %s: post type name */
						__( 'Post type "%s" already exists with similar prefix. This may cause confusion.', 'codesoup-options' ),
						$existing_type
					)
				);
			}
		}
	}

	/**
	 * Load integrations from config
	 *
	 * @return void
	 */
	private function load_integrations(): void {
		if ( empty( $this->config['integrations'] ) ) {
			return;
		}

		foreach ( $this->config['integrations'] as $key => $config ) {
			// Skip if disabled.
			if ( isset( $config['enabled'] ) && ! $config['enabled'] ) {
				continue;
			}

			// Get class name.
			$class = $config['class'] ?? null;

			if ( ! $class || ! is_string( $class ) ) {
				$this->logger->warning(
					sprintf(
						/* translators: %s: integration key */
						__( 'Integration class name must be a string for key: %s', 'codesoup-options' ),
						$key
					)
				);
				continue;
			}

			// Validate class name format (basic check for valid PHP class name).
			if ( ! preg_match( '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\\\]*$/', $class ) ) {
				$this->logger->warning(
					sprintf(
						/* translators: %s: class name */
						__( 'Invalid integration class name format: %s', 'codesoup-options' ),
						$class
					)
				);
				continue;
			}

			if ( ! class_exists( $class ) ) {
				$this->logger->warning(
					sprintf(
						/* translators: %s: class name */
						__( 'Integration class not found: %s', 'codesoup-options' ),
						$class
					)
				);
				continue;
			}

			// Verify interface implementation.
			$implements = class_implements( $class );
			if ( ! $implements || ! in_array( 'CodeSoup\Options\Integrations\IntegrationInterface', $implements, true ) ) {
				$this->logger->error(
					sprintf(
						'Integration must implement IntegrationInterface: %s',
						$class
					)
				);
				continue;
			}

			// Check if available.
			if ( ! $class::is_available() ) {
				$this->logger->info(
					sprintf(
						'Integration dependencies not available: %s',
						$class::get_name()
					)
				);
				continue;
			}

			// Instantiate and store.
			$this->integrations[ $key ] = new $class( $this );
		}
	}

	/**
	 * Register a new options page
	 *
	 * @param array $args Page arguments.
	 * @return void
	 */
	public function register_page( array $args ): void {
		$page          = new Page( $args );
		$this->pages[] = $page;
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
	 * @param array<string,mixed> $args Metabox configuration.
	 * @return void
	 */
	public function register_metabox( array $args ): void {
		$metabox                = new Metabox( $args );
		$this->metaboxes[]      = $metabox;
		$this->metaboxes_sorted = false;
	}

	/**
	 * Initialize the manager
	 *
	 * @return void
	 */
	public function init(): void {
		// Sort metaboxes once during initialization.
		$this->sort_metaboxes();
		$this->register_hooks();
	}

	/**
	 * Sort metaboxes by order property
	 *
	 * @return void
	 */
	private function sort_metaboxes(): void {
		if ( empty( $this->metaboxes ) || $this->metaboxes_sorted ) {
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

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'current_screen', array( $this, 'maybe_ensure_pages_exist' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'show_creation_errors' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ) );
		add_action( 'before_delete_post', array( $this, 'invalidate_cache_on_delete' ) );
		add_action( 'wp_trash_post', array( $this, 'invalidate_cache_on_delete' ) );
		add_filter( 'pre_get_posts', array( $this, 'filter_posts_by_capability' ) );
		add_filter( "views_edit-{$this->config['post_type']}", '__return_empty_array' );
		add_filter( "manage_{$this->config['post_type']}_posts_columns", array( $this, 'remove_date_column' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 2 );
		add_filter( 'parent_file', array( $this, 'set_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'set_submenu_file' ) );

		// Register integration hooks.
		foreach ( $this->integrations as $integration ) {
			$integration->register_hooks();
		}

		$this->hooks_registered = true;
	}

	/**
	 * Register custom post type
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$supports = array( 'title' );

		if ( $this->config['revisions'] ) {
			$supports[] = 'revisions';
		}

		register_post_type(
			$this->config['post_type'],
			array(
				'labels'              => array(
					'name'          => $this->config['menu_label'],
					'singular_name' => $this->config['menu_label'],
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'hierarchical'        => false,
				'menu_position'       => $this->config['menu_position'],
				'supports'            => $supports,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Maybe ensure pages exist
	 *
	 * @param \WP_Screen $screen Current screen.
	 * @return void
	 */
	public function maybe_ensure_pages_exist( \WP_Screen $screen ): void {
		// Only run on our post type screens.
		if ( $screen->post_type !== $this->config['post_type'] ) {
			return;
		}

		// Only run on list and edit screens.
		if ( ! in_array( $screen->base, array( 'edit', 'post' ), true ) ) {
			return;
		}

		$this->ensure_pages_exist();
	}

	/**
	 * Ensure all registered pages exist
	 *
	 * Uses batch query to check all pages at once for better performance.
	 *
	 * @return void
	 */
	private function ensure_pages_exist(): void {
		if ( empty( $this->pages ) ) {
			return;
		}

		// Get all post names we need to check.
		$post_names = array();
		$pages_map  = array();
		foreach ( $this->pages as $page ) {
			$post_name               = $this->get_post_name( $page->id );
			$post_names[]            = $post_name;
			$pages_map[ $post_name ] = $page;
		}

		// Batch query for existing posts using wpdb.
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $post_names ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing_posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_name
				FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_name IN ($placeholders)
				AND post_status = 'publish'",
				array_merge( array( $this->config['post_type'] ), $post_names )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Index by post_name for quick lookup.
		$existing_by_name = array();
		foreach ( $existing_posts as $post ) {
			$existing_by_name[ $post->post_name ] = (int) $post->ID;
		}

		// Process each page.
		foreach ( $this->pages as $page ) {
			$post_name = $this->get_post_name( $page->id );

			if ( isset( $existing_by_name[ $post_name ] ) ) {
				$post_id = $existing_by_name[ $post_name ];

				// Update capability if needed.
				$current_capability = get_post_meta( $post_id, self::META_KEY_CAPABILITY, true );
				if ( $current_capability !== $page->capability ) {
					update_post_meta( $post_id, self::META_KEY_CAPABILITY, $page->capability );
				}

				$this->cache->set_page_id( $page->id, $post_id );
			} else {
				// Create new page.
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
	private function create_page( Page $page ): void {
		$post_name = $this->get_post_name( $page->id );
		$post_type = $this->config['post_type'];

		// Check for conflicts with other post types.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$conflict = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID, post_type, post_title FROM $wpdb->posts WHERE post_name = %s AND post_type != %s LIMIT 1",
				$post_name,
				$post_type
			)
		);

		if ( $conflict ) {
			$error_message = sprintf(
				/* translators: 1: page title, 2: conflicting post type, 3: slug */
				__( 'Options page "%1$s" could not be created because a %2$s with the slug "%3$s" already exists. Please use a different prefix in your Manager configuration.', 'codesoup-options' ),
				$page->title,
				$conflict->post_type,
				$post_name
			);
			$this->logger->error( $error_message );
			$this->creation_errors[]          = $error_message;
			$this->created_pages[ $page->id ] = false;
			return;
		}

		// Create the post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => $page->title,
				'post_name'    => $post_name,
				'post_type'    => $post_type,
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			// Check if it's a duplicate error (race condition).
			if ( strpos( $post_id->get_error_message(), 'duplicate' ) !== false ) {
				// Another process created it, fetch and cache.
				$retry = get_posts(
					array(
						'name'          => $post_name,
						'post_type'     => $post_type,
						'numberposts'   => 1,
						'no_found_rows' => true,
					)
				);
				if ( ! empty( $retry ) ) {
					$this->cache->set_page_id( $page->id, $retry[0]->ID );
					return;
				}
			}

			// Log other errors.
			$this->logger->error(
				sprintf(
					/* translators: 1: page ID, 2: instance key, 3: error message */
					__( 'Failed to create page "%1$s" for instance "%2$s": %3$s', 'codesoup-options' ),
					$page->id,
					$this->instance_key,
					$post_id->get_error_message()
				)
			);
			$this->created_pages[ $page->id ] = false;
			return;
		}

		// Success - set metadata and cache.
		update_post_meta( $post_id, self::META_KEY_CAPABILITY, $page->capability );

		if ( ! empty( $page->description ) ) {
			update_post_meta( $post_id, self::META_KEY_DESCRIPTION, $page->description );
		}

		$this->cache->set_page_id( $page->id, $post_id );
		$this->created_pages[ $page->id ] = $post_id;
	}

	/**
	 * Show admin notices for page creation errors
	 *
	 * @return void
	 */
	public function show_creation_errors(): void {
		if ( empty( $this->creation_errors ) ) {
			return;
		}

		foreach ( $this->creation_errors as $error ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $error )
			);
		}
	}

	/**
	 * Remove date column from post list table
	 *
	 * @param array $columns The columns array.
	 * @return array
	 */
	public function remove_date_column( array $columns ): array {
		unset( $columns['date'] );
		return $columns;
	}

	/**
	 * Remove row actions from post list table
	 *
	 * @param array    $actions The actions array.
	 * @param \WP_Post $post The post object.
	 * @return array
	 */
	public function remove_row_actions( array $actions, \WP_Post $post ): array {
		if ( $post->post_type === $this->config['post_type'] ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['trash'] );
		}
		return $actions;
	}

	/**
	 * Filter posts list to only show pages user has capability for
	 *
	 * Uses meta_query for efficient database-level filtering.
	 * Sorts posts by post_title in ascending order.
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 * @return void
	 */
	public function filter_posts_by_capability( \WP_Query $query ): void {
		// Only filter on admin list screen for our post type.
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== $this->config['post_type'] ) {
			return;
		}

		// Get accessible post IDs for current user (cached).
		$accessible_ids = $this->get_accessible_post_ids( get_current_user_id() );

		if ( empty( $accessible_ids ) ) {
			// No accessible posts - show nothing.
			$query->set( 'post__in', array( 0 ) );
		} else {
			$query->set( 'post__in', $accessible_ids );
		}

		// Sort by post title.
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}

	/**
	 * Get accessible post IDs for a user based on capabilities
	 *
	 * Results are cached for performance.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of post IDs the user can access.
	 */
	private function get_accessible_post_ids( int $user_id ): array {
		$cache_key = 'accessible_posts_' . $user_id;
		$cached    = wp_cache_get( $cache_key, $this->cache->get_group() );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$user      = get_userdata( $user_id );
		$user_caps = array_keys( array_filter( $user->allcaps ) );

		if ( empty( $user_caps ) ) {
			wp_cache_set( $cache_key, array(), $this->cache->get_group(), HOUR_IN_SECONDS );
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $user_caps ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value IN ($placeholders)",
				array_merge( array( self::META_KEY_CAPABILITY ), $user_caps )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$post_ids = array_map( 'intval', $post_ids );

		wp_cache_set( $cache_key, $post_ids, $this->cache->get_group(), HOUR_IN_SECONDS );

		return $post_ids;
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
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		// Check if user can access at least one page and get the first accessible capability.
		$has_access     = false;
		$min_capability = 'manage_options'; // Default fallback.

		foreach ( $this->pages as $page ) {
			if ( current_user_can( $page->capability ) ) {
				$has_access     = true;
				$min_capability = $page->capability;
				break;
			}
		}

		// Don't show menu if user has no access to any page.
		if ( ! $has_access ) {
			return;
		}

		$menu_slug = 'edit.php?post_type=' . $this->config['post_type'];

		// Register as submenu if parent_menu is specified.
		if ( ! empty( $this->config['parent_menu'] ) ) {
			add_submenu_page(
				$this->config['parent_menu'],
				$this->config['menu_label'],
				$this->config['menu_label'],
				$min_capability,
				$menu_slug
			);
		} else {
			// Register as top-level menu.
			add_menu_page(
				$this->config['menu_label'],
				$this->config['menu_label'],
				$min_capability,
				$menu_slug,
				'',
				$this->config['menu_icon'],
				$this->config['menu_position']
			);
		}
	}

	/**
	 * Set parent file for submenu highlighting
	 *
	 * @param string $parent_file Parent file.
	 * @return string
	 */
	public function set_parent_file( string $parent_file ): string {
		global $current_screen;

		if ( ! $current_screen || $current_screen->post_type !== $this->config['post_type'] ) {
			return $parent_file;
		}

		if ( ! empty( $this->config['parent_menu'] ) ) {
			return $this->config['parent_menu'];
		}

		return $parent_file;
	}

	/**
	 * Set submenu file for submenu highlighting
	 *
	 * @param string|null $submenu_file Submenu file.
	 * @return string|null
	 */
	public function set_submenu_file( ?string $submenu_file ): ?string {
		global $current_screen;

		if ( ! $current_screen || $current_screen->post_type !== $this->config['post_type'] ) {
			return $submenu_file;
		}

		if ( ! empty( $this->config['parent_menu'] ) ) {
			return sprintf(
				'edit.php?post_type=%s',
				$this->config['post_type']
			);
		}

		return $submenu_file;
	}

	/**
	 * Register metaboxes for options pages
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== $this->config['post_type'] ) {
			return;
		}

		remove_meta_box(
			'submitdiv',
			$this->config['post_type'],
			'side'
		);

		// Register actions metabox.
		$actions_metabox = new Metabox(
			array(
				'page'     => 'all',
				'title'    => __( 'Actions', 'codesoup-options' ),
				'path'     => __DIR__ . '/metabox/actions.php',
				'context'  => 'side',
				'priority' => 'high',
			)
		);

		$actions_metabox->register(
			sprintf( '%s_actions', $this->instance_key ),
			$this->config['post_type']
		);

		if ( empty( $this->metaboxes ) ) {
			return;
		}

		// Metaboxes are already sorted during init().
		global $post;

		foreach ( $this->metaboxes as $metabox ) {
			if ( ! $post || ! $post->ID ) {
				continue;
			}

			$page_id = $this->extract_page_id_from_post_name( $post->post_name );

			if ( ! $page_id ) {
				continue;
			}

			if ( $page_id === $metabox->page ) {
				$metabox_id = sprintf(
					'%s_%s',
					$this->instance_key,
					$metabox->page
				);
				$metabox->register( $metabox_id, $this->config['post_type'] );
			}
		}
	}



	/**
	 * Get options by page ID (instance method)
	 *
	 * Retrieves options from post_content (serialized array).
	 * Uses object cache to avoid repeated database queries.
	 *
	 * @param string $page_id Page identifier.
	 * @return array Options array.
	 */
	public function get_options( $page_id ): array {
		$cache_key = $this->cache->get_key( $page_id );

		// Try to get from cache.
		$cached = $this->cache->get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$post = $this->get_post_by_page_id( $page_id );

		if ( ! $post ) {
			// Cache empty result to avoid repeated lookups.
			$this->cache->set( $cache_key, array() );
			return array();
		}

		$content = $post->post_content;
		if ( empty( $content ) ) {
			// Cache empty result.
			$this->cache->set( $cache_key, array() );
			return array();
		}

		// Use maybe_unserialize for safety.
		$options = maybe_unserialize( $content );
		$options = is_array( $options ) ? $options : array();

		// Cache the result.
		$this->cache->set( $cache_key, $options );

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
	 * Denormalize slug by converting dashes to underscores
	 *
	 * @param string $slug Slug to denormalize.
	 * @return string Denormalized slug with underscores.
	 */
	public static function denormalize_slug( string $slug ): string {
		return str_replace( '-', '_', $slug );
	}

	/**
	 * Get post name from page ID
	 *
	 * @param string $page_id Page identifier.
	 * @return string
	 */
	private function get_post_name( string $page_id ): string {
		return self::normalize_slug( $this->config['prefix'] . $page_id );
	}

	/**
	 * Extract page ID from post name
	 *
	 * @param string $post_name Post name.
	 * @return string|null Page ID or null if prefix doesn't match.
	 */
	private function extract_page_id_from_post_name( string $post_name ): ?string {
		$normalized_prefix = self::normalize_slug( $this->config['prefix'] );

		if ( strpos( $post_name, $normalized_prefix ) === 0 ) {
			$page_id_normalized = substr( $post_name, strlen( $normalized_prefix ) );
			return self::denormalize_slug( $page_id_normalized );
		}

		return null;
	}



	/**
	 * Get post by page ID
	 *
	 * Replaces deprecated get_page_by_path() with a more efficient query.
	 * Uses multi-layer cache (instance, object cache, transient) to avoid repeated database queries.
	 *
	 * @param string $page_id Page identifier.
	 * @return \WP_Post|null Post object or null if not found.
	 */
	public function get_post_by_page_id( string $page_id ): ?\WP_Post {
		// Check instance cache first.
		$cached_post_id = $this->cache->get_page_id( $page_id );
		if ( false !== $cached_post_id ) {
			$post = get_post( $cached_post_id );
			if ( $post && $this->config['post_type'] === $post->post_type ) {
				return $post;
			}
			// Cache is stale, clear it.
			$this->cache->clear_page_id( $page_id );
		}

		// Check object cache and transient layers via Cache::get().
		$cache_key = $this->cache->get_key( 'post_id_' . $page_id );
		$post_id   = $this->cache->get( $cache_key );

		if ( false !== $post_id ) {
			if ( 0 === $post_id ) {
				return null; // Cached "not found" result.
			}
			$post = get_post( $post_id );
			if ( $post && $this->config['post_type'] === $post->post_type ) {
				// Update instance cache.
				$this->cache->set_page_id( $page_id, $post_id );
				return $post;
			}
			// Cache is stale, clear it.
			$this->cache->delete( $cache_key );
		}

		// Query database using get_posts (more efficient than get_page_by_path).
		$post_name = $this->get_post_name( $page_id );
		$posts     = get_posts(
			array(
				'name'        => $post_name,
				'post_type'   => $this->config['post_type'],
				'numberposts' => 1,
				'post_status' => 'publish',
			)
		);

		$post = ! empty( $posts ) ? $posts[0] : null;

		// Cache the result in all layers (object cache and transient).
		$post_id_to_cache = $post ? $post->ID : 0;
		$this->cache->set( $cache_key, $post_id_to_cache );

		// Update instance cache if found.
		if ( $post ) {
			$this->cache->set_page_id( $page_id, $post->ID );
		}

		return $post;
	}



	/**
	 * Invalidate cache when post is deleted or trashed
	 *
	 * @param int $post_id Post ID being deleted/trashed.
	 * @return void
	 */
	public function invalidate_cache_on_delete( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== $this->config['post_type'] ) {
			return;
		}

		$this->invalidate_cache_by_post( $post );
	}

	/**
	 * Invalidate cache for a page
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function invalidate_cache( int $post_id ): void {
		$post = get_post( $post_id );

		// Early return if post doesn't exist.
		if ( ! $post ) {
			return;
		}

		$this->invalidate_cache_by_post( $post );
	}

	/**
	 * Invalidate cache for a page using post object
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	private function invalidate_cache_by_post( \WP_Post $post ): void {
		$page_id = $this->extract_page_id_from_post_name( $post->post_name );

		if ( ! $page_id ) {
			return;
		}

		// Invalidate options cache.
		$cache_key = $this->cache->get_key( $page_id );
		$this->cache->delete( $cache_key );

		// Invalidate post_id cache (used by get_post_by_page_id).
		$post_id_cache_key = $this->cache->get_key( 'post_id_' . $page_id );
		$this->cache->delete( $post_id_cache_key );
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
	 * @param string|null $key Optional config key to retrieve.
	 * @return mixed Full config array if no key provided, specific value if key provided, null if key not found.
	 */
	public function get_config( ?string $key = null ) {
		if ( null === $key ) {
			return $this->config;
		}

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
	 * Get cache group
	 *
	 * @return string
	 */
	public function get_cache_group(): string {
		return $this->cache->get_group();
	}

	/**
	 * Get cache instance
	 *
	 * @return Cache
	 */
	public function get_cache(): Cache {
		return $this->cache;
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
	 * Get integration instance
	 *
	 * @param string $key Integration key.
	 * @return \CodeSoup\Options\Integrations\IntegrationInterface|null Integration instance or null if not found.
	 */
	public function get_integration( string $key ) {
		$integration = $this->integrations[ $key ] ?? null;

		if ( $integration && ! $integration instanceof \CodeSoup\Options\Integrations\IntegrationInterface ) {
			$this->logger->error(
				sprintf(
					'Integration %s does not implement IntegrationInterface',
					$key
				)
			);
			return null;
		}

		return $integration;
	}

	/**
	 * Check if integration is loaded and active
	 *
	 * @param string $key Integration key.
	 * @return bool True if integration is loaded and active.
	 */
	public function has_integration( string $key ): bool {
		return isset( $this->integrations[ $key ] );
	}

	/**
	 * Get all loaded integrations
	 *
	 * @return array<string, object> Array of loaded integration instances.
	 */
	public function get_integrations(): array {
		return $this->integrations;
	}
}
