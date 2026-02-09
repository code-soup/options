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
	 * Constructor
	 *
	 * @param array $args Metabox arguments.
	 * @throws \InvalidArgumentException If required fields are missing.
	 */
	public function __construct( array $args ) {
		if ( empty( $args['page'] ) ) {
			throw new \InvalidArgumentException(
				__( 'Metabox page is required', 'codesoup-options' )
			);
		}

		if ( empty( $args['title'] ) ) {
			throw new \InvalidArgumentException(
				__( 'Metabox title is required', 'codesoup-options' )
			);
		}

		if ( empty( $args['path'] ) ) {
			throw new \InvalidArgumentException(
				__( 'Metabox path is required', 'codesoup-options' )
			);
		}

		$path = $args['path'];

		if ( ! file_exists( $path ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					__( 'Metabox template file does not exist: %s', 'codesoup-options' ),
					$path
				)
			);
		}

		if ( ! is_readable( $path ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					__( 'Metabox template file is not readable: %s', 'codesoup-options' ),
					$path
				)
			);
		}

		$this->page     = sanitize_key( $args['page'] );
		$this->title    = sanitize_text_field( $args['title'] );
		$this->path     = $path;
		$this->context  = sanitize_key( $args['context'] ?? 'normal' );
		$this->priority = sanitize_key( $args['priority'] ?? 'default' );
		$this->order    = absint( $args['order'] ?? 10 );
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

		add_meta_box(
			$id,
			$this->title,
			function( $post ) use ( $path ) {
				require $path;
			},
			$post_type,
			$this->context,
			$this->priority
		);
	}
}

