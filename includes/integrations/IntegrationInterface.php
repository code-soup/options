<?php
/**
 * Integration Interface
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options\Integrations;

use CodeSoup\Options\Manager;

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;

/**
 * Integration Interface
 *
 * All integrations must implement this interface to be loaded by Manager.
 *
 * @since 1.1.0
 */
interface IntegrationInterface {

	/**
	 * Constructor must accept Manager instance
	 *
	 * @param Manager $manager Manager instance.
	 */
	public function __construct( Manager $manager );

	/**
	 * Check if integration dependencies are available
	 *
	 * @return bool
	 */
	public static function is_available(): bool;

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void;

	/**
	 * Get integration name/identifier
	 *
	 * @return string
	 */
	public static function get_name(): string;
}

