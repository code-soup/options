<?php
/**
 * Page Value Object
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;

/**
 * Page class
 *
 * Represents an ACF options page configuration.
 *
 * @since 1.0.0
 */
class Page {

	/**
	 * Page ID
	 *
	 * @var string
	 */
	public readonly string $id;

	/**
	 * Page title
	 *
	 * @var string
	 */
	public readonly string $title;

	/**
	 * Required capability
	 *
	 * @var string
	 */
	public readonly string $capability;

	/**
	 * Page description
	 *
	 * @var string|null
	 */
	public readonly ?string $description;

	/**
	 * Constructor
	 *
	 * @param array $args Page arguments.
	 * @throws \InvalidArgumentException If required fields are missing.
	 */
	public function __construct( array $args ) {
		if ( empty( $args['id'] ) ) {
			throw new \InvalidArgumentException(
				__( 'Page ID is required', 'codesoup-options' )
			);
		}

		if ( empty( $args['title'] ) ) {
			throw new \InvalidArgumentException(
				__( 'Page title is required', 'codesoup-options' )
			);
		}

		if ( empty( $args['capability'] ) ) {
			throw new \InvalidArgumentException(
				__( 'Page capability is required', 'codesoup-options' )
			);
		}

		$this->id          = sanitize_key( $args['id'] );
		$this->title       = sanitize_text_field( $args['title'] );
		$this->capability  = sanitize_key( $args['capability'] );
		$this->description = isset( $args['description'] )
			? sanitize_text_field( $args['description'] )
			: null;
	}
}
