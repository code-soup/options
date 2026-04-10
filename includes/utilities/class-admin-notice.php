<?php
/**
 * Admin Notice Utility
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Admin_Notice class
 *
 * Utility for rendering WordPress admin notices.
 *
 * @since 1.3.0
 */
class Admin_Notice {

	/**
	 * Render admin notice
	 *
	 * @param string $message Message to display.
	 * @param string $type Notice type (success, error, warning, info).
	 * @param bool   $dismissible Whether notice is dismissible.
	 * @return void
	 */
	public static function render(
		string $message,
		string $type = 'success',
		bool $dismissible = true
	): void {
		$allowed_types = array( 'success', 'error', 'warning', 'info' );
		$type          = in_array( $type, $allowed_types, true )
			? $type
			: 'success';

		$classes = sprintf(
			'notice notice-%s%s',
			esc_attr( $type ),
			$dismissible ? ' is-dismissible' : ''
		);

		printf(
			'<div class="%s"><p>%s</p></div>',
			esc_attr( $classes ),
			esc_html( $message )
		);
	}

	/**
	 * Render success notice
	 *
	 * @param string $message Message to display.
	 * @param bool   $dismissible Whether notice is dismissible.
	 * @return void
	 */
	public static function success( string $message, bool $dismissible = true ): void {
		self::render( $message, 'success', $dismissible );
	}

	/**
	 * Render error notice
	 *
	 * @param string $message Message to display.
	 * @param bool   $dismissible Whether notice is dismissible.
	 * @return void
	 */
	public static function error( string $message, bool $dismissible = true ): void {
		self::render( $message, 'error', $dismissible );
	}

	/**
	 * Render warning notice
	 *
	 * @param string $message Message to display.
	 * @param bool   $dismissible Whether notice is dismissible.
	 * @return void
	 */
	public static function warning( string $message, bool $dismissible = true ): void {
		self::render( $message, 'warning', $dismissible );
	}

	/**
	 * Render info notice
	 *
	 * @param string $message Message to display.
	 * @param bool   $dismissible Whether notice is dismissible.
	 * @return void
	 */
	public static function info( string $message, bool $dismissible = true ): void {
		self::render( $message, 'info', $dismissible );
	}
}
