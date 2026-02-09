<?php
/**
 * Logger class for handling all logging operations
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

/**
 * Logger class
 */
class Logger {
	/**
	 * Instance key for namespacing log messages
	 *
	 * @var string
	 */
	private string $instance_key;

	/**
	 * Whether logging is enabled
	 *
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * Constructor
	 *
	 * @param string $instance_key Instance key for namespacing.
	 * @param bool   $enabled Whether logging is enabled.
	 */
	public function __construct( string $instance_key, bool $enabled = true ) {
		$this->instance_key = $instance_key;
		$this->enabled      = $enabled;
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( 'ERROR', $message, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( 'WARNING', $message, $context );
	}

	/**
	 * Log an info message
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( 'INFO', $message, $context );
	}

	/**
	 * Log a debug message
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$this->log( 'DEBUG', $message, $context );
	}

	/**
	 * Internal log method
	 *
	 * Sanitizes messages to prevent log injection attacks by removing newlines
	 * and control characters that could be used to inject fake log entries.
	 *
	 * @param string $level Log level.
	 * @param string $message Message to log.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log( string $level, string $message, array $context = array() ): void {
		if ( ! $this->enabled ) {
			return;
		}

		// Sanitize message to prevent log injection.
		// Remove newlines and control characters.
		$sanitized_message = preg_replace( '/[\r\n\t\x00-\x1F\x7F]/', ' ', $message );

		$formatted_message = sprintf(
			'[%s] [%s] %s',
			sanitize_key( $this->instance_key ),
			sanitize_key( $level ),
			$sanitized_message
		);

		if ( ! empty( $context ) ) {
			$formatted_message .= ' ' . wp_json_encode( $context );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted_message );
	}
}

