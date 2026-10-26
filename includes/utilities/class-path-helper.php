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
	 * Detects installation type and generates correct URL:
	 * - Standalone plugin: uses index.php
	 * - Vendor package: uses __FILE__ navigation
	 * - Scoped vendor: uses __FILE__ navigation
	 *
	 * @return string Base URL of the plugin.
	 */
	public static function get_base_url(): string {
		$package_root = dirname( dirname( __DIR__ ) );

		// Detect installation type by checking path
		if ( self::is_standalone_plugin( $package_root ) ) {
			// Standalone plugin - use index.php
			return plugins_url( '', $package_root . '/index.php' );
		}

		// Vendor package (regular or scoped) - use __FILE__ navigation
		// plugins_url('', __FILE__) returns parent directory of this file
		$base_url = plugins_url( '', __FILE__ );
		return dirname( dirname( $base_url ) );
	}

	/**
	 * Detect if installed as standalone plugin
	 *
	 * @param string $package_root Package root path.
	 * @return bool True if standalone plugin, false if vendor package.
	 */
	private static function is_standalone_plugin( string $package_root ): bool {
		// Match composer vendor directories by path segment (not substring).
		// Standalone: /plugins/codesoup-options/
		// Vendor:     /plugins/my-plugin/vendor/codesoup/options/
		// Scoped:     /plugins/my-plugin/vendor-prefixed/codesoup/options/
		$normalized = function_exists( 'wp_normalize_path' )
			? wp_normalize_path( $package_root )
			: str_replace( '\\', '/', $package_root );
		$segments   = explode( '/', trim( $normalized, '/' ) );

		foreach ( $segments as $segment ) {
			if ( 'vendor' === $segment || 'vendor-prefixed' === $segment ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get asset URL
	 *
	 * Generates URL to an asset file (CSS, JS, images).
	 *
	 * @param string $relative_path Relative path to asset (e.g., 'css/admin.css').
	 * @return string Full URL to asset.
	 */
	public static function get_asset_url( string $relative_path ): string {
		$package_root = dirname( dirname( __DIR__ ) );
		$asset_path   = 'assets/' . ltrim( $relative_path, '/' );

		if ( self::is_standalone_plugin( $package_root ) ) {
			return plugins_url( $asset_path, $package_root . '/index.php' );
		}

		// Vendor package - construct from base URL
		return self::get_base_url() . '/' . $asset_path;
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
