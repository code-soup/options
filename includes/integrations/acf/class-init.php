<?php
/**
 * ACF Integration Handler
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options\Integrations\ACF;

use CodeSoup\Options\Manager;
use CodeSoup\Options\Integrations\IntegrationInterface;

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;

/**
 * Init class
 *
 * Handles all ACF-specific functionality for the options manager.
 *
 * @since 1.0.4
 */
class Init implements IntegrationInterface {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Track which instances have registered their location types
	 *
	 * @var array
	 */
	private static array $registered_location_types = array();

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Check if ACF is available
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'acf' );
	}

	/**
	 * Get integration name
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'Advanced Custom Fields';
	}

	/**
	 * Register ACF-specific hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! self::is_available() ) {
			add_action(
				'admin_notices',
				array(
					$this,
					'show_acf_missing_notice',
				)
			);
			return;
		}

		add_action(
			'acf/save_post',
			array(
				$this,
				'save_options',
			),
			20
		);

		$instance_key = $this->manager->get_instance_key();
		if ( ! isset( self::$registered_location_types[ $instance_key ] ) ) {
			// Only register if acf/init hasn't fired yet.
			if ( ! did_action( 'acf/init' ) ) {
				add_action(
					'acf/init',
					array(
						$this,
						'register_acf_location_type',
					)
				);
			} else {
				// acf/init already fired, register immediately.
				$this->register_acf_location_type();
			}
			self::$registered_location_types[ $instance_key ] = true;
		}
	}

	/**
	 * Show admin notice when ACF is missing
	 *
	 * @return void
	 */
	public function show_acf_missing_notice(): void {
		printf(
			'<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
			esc_html__( 'ACF Options Manager', 'codesoup-options' ),
			esc_html__( 'Advanced Custom Fields plugin is required. Please install and activate ACF.', 'codesoup-options' )
		);
	}

	/**
	 * Register ACF location type
	 *
	 * @return void
	 */
	public function register_acf_location_type(): void {
		if ( ! function_exists( 'acf_register_location_type' ) ) {
			return;
		}

		$instance_key = $this->manager->get_instance_key();
		$class_name   = Location::create_for_instance( $instance_key, $this->manager->get_config( 'menu_label' ) );

		acf_register_location_type( $class_name );
	}

	/**
	 * Save options to post_content
	 *
	 * Intentionally stores data in both ACF meta AND post_content.
	 * - ACF meta: Used by ACF for field rendering and validation
	 * - post_content: Used for fast retrieval via get_options()
	 *
	 * This double storage is by design to support both ACF's field system
	 * and efficient option retrieval without ACF overhead.
	 *
	 * Note: ACF handles nonce verification before firing acf/save_post hook,
	 * but we add defensive checks for security in depth.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_options( $post_id ): void {
		if ( get_post_type( $post_id ) !== $this->manager->get_config( 'post_type' ) ) {
			return;
		}

		// Defensive nonce verification (ACF should handle this, but verify anyway).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF handles nonce, this is defensive check.
		if ( isset( $_POST['acf'] ) && ! empty( $_POST['_wpnonce'] ) ) {
			// Verify ACF nonce if present.
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
				$this->manager->get_logger()->warning(
					sprintf(
						/* translators: %d: post ID */
						__( 'Nonce verification failed for post %d', 'codesoup-options' ),
						$post_id
					)
				);
				return;
			}
		}

		if ( ! $this->manager->can_edit_page( $post_id ) ) {
			return;
		}

		if ( ! function_exists( 'get_fields' ) ) {
			return;
		}

		$fields = get_fields( $post_id );

		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$serialized = maybe_serialize( $fields );

		remove_action(
			'acf/save_post',
			array(
				$this,
				'save_options',
			),
			20
		);

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $serialized,
			),
			true
		);

		add_action(
			'acf/save_post',
			array(
				$this,
				'save_options',
			),
			20
		);

		if ( is_wp_error( $result ) ) {
			$this->manager->get_logger()->error(
				sprintf(
					'Failed to save options for post %d: %s',
					$post_id,
					$result->get_error_message()
				)
			);
			return;
		}

		$this->manager->invalidate_cache( $post_id );
	}

	/**
	 * Get single option by page ID and field name
	 *
	 * Retrieves single field value from postmeta using ACF's get_field().
	 *
	 * @param string $page_id Page identifier.
	 * @param string $field_name ACF field name.
	 * @param mixed  $default_value Default value if field not found.
	 * @return mixed Field value or default.
	 */
	public function get_option( string $page_id, string $field_name, $default_value = null ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default_value;
		}

		$post = $this->manager->get_post_by_page_id( $page_id );

		if ( ! $post ) {
			return $default_value;
		}

		$value = get_field( $field_name, $post->ID );

		return ( false === $value )
			? $default_value
			: $value;
	}
}
