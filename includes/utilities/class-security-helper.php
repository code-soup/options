<?php
/**
 * Security Helper
 *
 * Utility for security-related operations.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Security_Helper class
 *
 * Provides utility methods for nonce verification and security checks.
 *
 * @since 1.1.0
 */
class Security_Helper {

	/**
	 * Verify nonce from POST data
	 *
	 * @param string $nonce_key    Key in $_POST array (default: '_wpnonce').
	 * @param string|int $nonce_action Action name for nonce (default: -1).
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_post_nonce( string $nonce_key = '_wpnonce', $nonce_action = -1 ): bool {
		if ( ! isset( $_POST[ $nonce_key ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) );
		return (bool) wp_verify_nonce( $nonce, $nonce_action );
	}

	/**
	 * Verify user capability
	 *
	 * @param string $capability Required capability.
	 * @return bool True if current user has capability, false otherwise.
	 */
	public static function current_user_can( string $capability ): bool {
		return current_user_can( $capability );
	}

	/**
	 * Verify nonce and capability
	 *
	 * Combined check for nonce and user capability.
	 *
	 * @param string $capability   Required capability.
	 * @param string $nonce_key    Key in $_POST array.
	 * @param string|int $nonce_action Action name for nonce (default: -1).
	 * @return bool True if both checks pass, false otherwise.
	 */
	public static function verify_post_request( string $capability, string $nonce_key = '_wpnonce', $nonce_action = -1 ): bool {
		return self::current_user_can( $capability ) && self::verify_post_nonce( $nonce_key, $nonce_action );
	}
}
