<?php
/**
 * Integration Manager
 *
 * Manages plugin integrations (ACF, CMB2, etc.).
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Integration_Manager class
 *
 * Handles loading, validation, and lifecycle of integrations.
 *
 * @since 1.1.0
 */
class Integration_Manager {

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Loaded integrations
	 *
	 * @var array<string, \CodeSoup\Options\Integrations\IntegrationInterface>
	 */
	private array $integrations = array();

	/**
	 * Constructor
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Load integrations from configuration
	 *
	 * @param array $integrations_config Integration configuration array.
	 * @return void
	 */
	public function load( array $integrations_config ): void {
		if ( empty( $integrations_config ) ) {
			return;
		}

		foreach ( $integrations_config as $key => $config ) {
			$this->load_integration( $key, $config );
		}
	}

	/**
	 * Load single integration
	 *
	 * @param string $key Integration key.
	 * @param array  $config Integration configuration.
	 * @return void
	 */
	private function load_integration( string $key, array $config ): void {
		$logger = $this->manager->get_logger();

		// Skip if disabled.
		if ( isset( $config['enabled'] ) && ! $config['enabled'] ) {
			return;
		}

		// Get class name.
		$class = $config['class'] ?? null;

		if ( ! $class || ! is_string( $class ) ) {
			$logger->warning(
				sprintf(
					/* translators: %s: integration key */
					__( 'Integration class name must be a string for key: %s', 'codesoup-options' ),
					$key
				)
			);
			return;
		}

		// Validate class name format.
		if ( ! preg_match( '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\\\]*$/', $class ) ) {
			$logger->warning(
				sprintf(
					/* translators: %s: class name */
					__( 'Invalid integration class name format: %s', 'codesoup-options' ),
					$class
				)
			);
			return;
		}

		// Check class exists.
		if ( ! class_exists( $class ) ) {
			$logger->warning(
				sprintf(
					/* translators: %s: class name */
					__( 'Integration class not found: %s', 'codesoup-options' ),
					$class
				)
			);
			return;
		}

		// Verify interface implementation.
		$implements = class_implements( $class );
		if ( ! $implements || ! in_array( 'CodeSoup\Options\Integrations\IntegrationInterface', $implements, true ) ) {
			$logger->error(
				sprintf(
					'Integration must implement IntegrationInterface: %s',
					$class
				)
			);
			return;
		}

		// Check if available.
		if ( ! $class::is_available() ) {
			$logger->info(
				sprintf(
					'Integration dependencies not available: %s',
					$class::get_name()
				)
			);
			return;
		}

		// Instantiate and store.
		$this->integrations[ $key ] = new $class( $this->manager );
	}

	/**
	 * Register hooks for all loaded integrations
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		foreach ( $this->integrations as $integration ) {
			$integration->register_hooks();
		}
	}

	/**
	 * Check if any integrations are active in config
	 *
	 * Static method to check config before Manager fully initialized.
	 *
	 * @param array $integrations_config Integration configuration array.
	 * @return bool
	 */
	public static function has_active_integrations( array $integrations_config ): bool {
		if ( empty( $integrations_config ) ) {
			return false;
		}

		foreach ( $integrations_config as $integration_config ) {
			if ( ! isset( $integration_config['enabled'] ) || true === $integration_config['enabled'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get integration instance
	 *
	 * @param string $key Integration key.
	 * @return \CodeSoup\Options\Integrations\IntegrationInterface|null
	 */
	public function get( string $key ) {
		$integration = $this->integrations[ $key ] ?? null;

		if ( $integration && ! $integration instanceof \CodeSoup\Options\Integrations\IntegrationInterface ) {
			$this->manager->get_logger()->error(
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
	 * Check if integration is loaded
	 *
	 * @param string $key Integration key.
	 * @return bool
	 */
	public function has( string $key ): bool {
		return isset( $this->integrations[ $key ] );
	}

	/**
	 * Get all loaded integrations
	 *
	 * @return array<string, \CodeSoup\Options\Integrations\IntegrationInterface>
	 */
	public function get_all(): array {
		return $this->integrations;
	}
}
