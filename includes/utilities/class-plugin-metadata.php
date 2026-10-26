<?php
/**
 * Plugin release metadata
 *
 * Single source of truth for version and environment requirements.
 * Keep in sync with index.php plugin headers and readme.txt.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Plugin_Metadata class
 *
 * @since 1.3.0
 */
final class Plugin_Metadata {

	/**
	 * Plugin version (semver).
	 */
	public const VERSION = '1.3.0';

	/**
	 * Minimum WordPress version.
	 */
	public const WP_REQUIRED = '6.0';

	/**
	 * Highest WordPress branch verified in QA (major.minor; matches readme.txt).
	 */
	public const WP_TESTED = '7.1';

	/**
	 * Minimum PHP version.
	 */
	public const PHP_REQUIRED = '7.4';
}
