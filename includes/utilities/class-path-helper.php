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
	 * Package-relative assets directory path.
	 */
	private const PACKAGE_ASSETS_DIR = 'assets';

	/**
	 * Get plugin base path
	 *
	 * @return string Absolute path to plugin root directory.
	 */
	public static function get_base_path(): string {
		$default = dirname( dirname( __DIR__ ) );

		return self::resolve_package_root( $default ) ?? $default;
	}

	/**
	 * Get plugin base URL
	 *
	 * Detects installation type and generates correct URL:
	 * - Standalone plugin: uses index.php
	 * - Vendor package: resolves under the host plugin in wp-content/plugins
	 *
	 * @return string Base URL of the plugin.
	 */
	public static function get_base_url(): string {
		$package_root = self::get_base_path();

		if ( self::is_standalone_plugin( $package_root ) ) {
			return plugins_url( '', $package_root . '/index.php' );
		}

		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$plugins_dir  = self::normalize_path( WP_PLUGIN_DIR );
			$package_path = self::normalize_path( $package_root );

			if ( str_starts_with( $package_path, $plugins_dir ) ) {
				$relative = trim( substr( $package_path, strlen( $plugins_dir ) ), '/' );

				return trailingslashit( content_url( 'plugins/' . $relative ) );
			}
		}

		$base_url = plugins_url( '', __FILE__ );

		return trailingslashit( dirname( dirname( $base_url ) ) );
	}

	/**
	 * Detect if installed as standalone plugin
	 *
	 * @param string $package_root Package root path.
	 * @return bool True if standalone plugin, false if vendor package.
	 */
	private static function is_standalone_plugin( string $package_root ): bool {
		$normalized = self::normalize_path( $package_root );
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
		$asset_path = self::PACKAGE_ASSETS_DIR . '/' . ltrim( $relative_path, '/' );

		return trailingslashit( self::get_base_url() ) . $asset_path;
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

	/**
	 * Resolve package root when Composer symlinks leave realpath outside wp-content/plugins.
	 *
	 * @param string $default Default path derived from __DIR__.
	 * @return string|null Path under WP_PLUGIN_DIR when found.
	 */
	private static function resolve_package_root( string $default ): ?string {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return null;
		}

		$plugins_dir  = self::normalize_path( WP_PLUGIN_DIR );
		$default_norm = self::normalize_path( $default );

		if ( str_starts_with( $default_norm, $plugins_dir ) ) {
			return $default;
		}

		$candidates = array_merge(
			glob( $plugins_dir . '/*/vendor/codesoup/options', GLOB_ONLYDIR ) ?: array(),
			glob( $plugins_dir . '/*/vendor-prefixed/codesoup/options', GLOB_ONLYDIR ) ?: array()
		);

		foreach ( $candidates as $candidate ) {
			if ( is_dir( $candidate ) ) {
				return self::normalize_path( $candidate );
			}
		}

		return null;
	}

	/**
	 * Normalize filesystem path for comparisons.
	 *
	 * @param string $path Path to normalize.
	 * @return string Normalized path.
	 */
	private static function normalize_path( string $path ): string {
		if ( function_exists( 'wp_normalize_path' ) ) {
			return wp_normalize_path( $path );
		}

		return str_replace( '\\', '/', $path );
	}
}