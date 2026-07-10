<?php
/**
 * Path Helper
 *
 * Utility for path and URL calculations.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Path_Helper class
 *
 * Provides utility methods for path and URL resolution.
 *
 * @since 1.1.0
 */
class Path_Helper {

	/**
	 * Get plugin base path
	 *
	 * @return string Absolute path to plugin root directory.
	 */
	public static function get_base_path(): string {
		return dirname( dirname( __DIR__ ) );
	}

	/**
	 * Get plugin base URL
	 *
	 * Uses WordPress plugins_url() for reliable path-to-URL conversion.
	 * Works with regular composer packages, scoped packages, and symlinked directories.
	 *
	 * @return string Base URL of the plugin.
	 */
	public static function get_base_url(): string {
		return plugins_url( '', dirname( dirname( __DIR__ ) ) );
	}

	/**
	 * Get asset URL
	 *
	 * @param string $relative_path Relative path to asset (e.g., 'css/admin.css').
	 * @return string Full URL to asset.
	 */
	public static function get_asset_url( string $relative_path ): string {
		return plugins_url( 'assets/' . ltrim( $relative_path, '/' ), dirname( dirname( __DIR__ ) ) );
	}

	/**
	 * Get current screen safely
	 *
	 * Returns null if get_current_screen() is not available or screen is not set.
	 *
	 * @return \WP_Screen|null Current screen or null.
	 */
	public static function get_current_screen(): ?\WP_Screen {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return null;
		}

		$screen = get_current_screen();
		return $screen instanceof \WP_Screen ? $screen : null;
	}
}
