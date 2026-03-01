<?php
/**
 * Metabox Value Object
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;

/**
 * Metabox class
 *
 * Represents a custom metabox configuration.
 *
 * @since 1.0.0
 */
class Metabox {

	/**
	 * Page ID to attach metabox to
	 *
	 * @var string
	 */
	public readonly string $page;

	/**
	 * Metabox title
	 *
	 * @var string
	 */
	public readonly string $title;

	/**
	 * Path to template file
	 *
	 * @var string
	 */
	public readonly string $path;

	/**
	 * Metabox context
	 *
	 * @var string
	 */
	public readonly string $context;

	/**
	 * Metabox priority
	 *
	 * @var string
	 */
	public readonly string $priority;

	/**
	 * Display order
	 *
	 * @var int
	 */
	public readonly int $order;

	/**
	 * Custom CSS classes
	 *
	 * @since 1.1.0
	 * @var array
	 */
	public readonly array $class;

	/**
	 * Custom arguments to pass to template
	 *
	 * @var array
	 */
	public readonly array $args;

	/**
	 * Constructor
	 *
	 * @param array $args {
	 *     Metabox arguments.
	 *
	 *     @type string       $page     Required. Page ID to attach metabox to.
	 *     @type string       $title    Required. Metabox title.
	 *     @type string       $path     Required. Path to template file.
	 *     @type string       $context  Optional. Metabox context (normal, side, advanced). Default 'normal'.
	 *     @type string       $priority Optional. Metabox priority (high, core, default, low). Default 'default'.
	 *     @type int          $order    Optional. Display order. Default 10.
	 *     @type string|array $class    Optional. CSS class name(s). Accepts string (space-separated) or array. Default empty.
	 *     @type array        $args     Optional. Custom arguments to pass to template. Default empty array.
	 * }
	 * @throws \InvalidArgumentException If required fields are missing.
	 */
	public function __construct( array $args ) {
		if ( empty( $args['page'] ) ) {
			throw new \InvalidArgumentException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				__( 'Metabox page is required', 'codesoup-options' )
			);
		}

		if ( empty( $args['title'] ) ) {
			throw new \InvalidArgumentException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				__( 'Metabox title is required', 'codesoup-options' )
			);
		}

		if ( empty( $args['path'] ) ) {
			throw new \InvalidArgumentException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				__( 'Metabox path is required', 'codesoup-options' )
			);
		}

		$path = $args['path'];

		if ( ! file_exists( $path ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: file path */
					esc_html__( 'Metabox template file does not exist: %s', 'codesoup-options' ),
					esc_html( $path )
				)
			);
		}

		if ( ! is_readable( $path ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: file path */
					esc_html__( 'Metabox template file is not readable: %s', 'codesoup-options' ),
					esc_html( $path )
				)
			);
		}

		$this->page     = sanitize_key( $args['page'] );
		$this->title    = sanitize_text_field( $args['title'] );
		$this->path     = $path;
		$this->context  = sanitize_key( $args['context'] ?? 'normal' );
		$this->priority = sanitize_key( $args['priority'] ?? 'default' );
		$this->order    = absint( $args['order'] ?? 10 );
		$this->class    = $this->sanitize_classes( $args['class'] ?? '' );
		$this->args     = $args['args'] ?? array();
	}

	/**
	 * Register the metabox with WordPress
	 *
	 * @param string $id Metabox ID.
	 * @param string $post_type Post type to register metabox for.
	 * @return void
	 */
	public function register( string $id, string $post_type ): void {
		$path = $this->path;
		$args = $this->args;

		add_meta_box(
			$id,
			$this->title,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by WordPress callback signature.
			function ( $post ) use ( $path, $args ) {
				require $path;
			},
			$post_type,
			$this->context,
			$this->priority
		);

		if ( ! empty( $this->class ) ) {
			$custom_classes = $this->class;
			add_filter(
				sprintf(
					'postbox_classes_%s_%s',
					$post_type,
					$id
				),
				function ( $classes ) use ( $custom_classes ) {
					return array_merge( $classes, $custom_classes );
				}
			);
		}
	}

	/**
	 * Sanitize CSS class input
	 *
	 * Accepts string or array of class names and returns sanitized array.
	 *
	 * @param string|array $input Class name(s) to sanitize.
	 * @return array Sanitized class names.
	 */
	private function sanitize_classes( $input ): array {
		if ( empty( $input ) ) {
			return array();
		}

		// Convert to array if string.
		if ( is_string( $input ) ) {
			$input = explode( ' ', $input );
		}

		// Ensure we have an array.
		if ( ! is_array( $input ) ) {
			return array();
		}

		// Sanitize each class and remove empty values.
		$sanitized = array_map( 'sanitize_html_class', $input );
		$sanitized = array_filter( $sanitized );

		// Remove duplicates and re-index.
		return array_values( array_unique( $sanitized ) );
	}
}
