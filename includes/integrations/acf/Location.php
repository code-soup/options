<?php
/**
 * ACF Location Rule for ACF Options
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options\Integrations\ACF;

use CodeSoup\Options\Manager;

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die;

/**
 * Location class
 *
 * Provides ACF location rule for assigning field groups to options pages.
 *
 * @since 1.0.0
 */
class Location extends \ACF_Location {

	/**
	 * Instance key for this location type
	 *
	 * @var string|null
	 */
	protected static ?string $instance_key = null;

	/**
	 * Menu label for this location type
	 *
	 * @var string|null
	 */
	protected static ?string $menu_label = null;

	/**
	 * Registry of created location classes
	 *
	 * @var array<string, string>
	 */
	private static array $location_classes = array();

	/**
	 * Create a location class for a specific Manager instance
	 *
	 * Note: This method uses eval() to dynamically create classes because ACF's
	 * acf_register_location_type() requires a class name (string), not an instance.
	 * The instance_key is sanitized with sanitize_key() to prevent code injection.
	 * All values are properly escaped using var_export() which is safe for eval().
	 *
	 * @param string $instance_key Manager instance key.
	 * @param string $menu_label Menu label from config.
	 * @return string Class name of the created location class.
	 */
	public static function create_for_instance( string $instance_key, string $menu_label ): string {
		$sanitized_key = sanitize_key( $instance_key );

		if ( empty( $sanitized_key ) ) {
			$sanitized_key = 'default';
		}

		$class_name = 'CodeSoup\\Options\\Integrations\\ACF\\Location_' . $sanitized_key;

		if ( isset( self::$location_classes[ $sanitized_key ] ) ) {
			return self::$location_classes[ $sanitized_key ];
		}

		if ( ! class_exists( $class_name ) ) {
			$parent_class = __CLASS__;

			$class_code = sprintf(
				'namespace CodeSoup\\Options\\Integrations\\ACF;
				class Location_%s extends \\%s {
					protected static ?string $instance_key = %s;
					protected static ?string $menu_label = %s;
				}',
				$sanitized_key,
				$parent_class,
				var_export( $instance_key, true ),
				var_export( $menu_label, true )
			);

			// phpcs:ignore Squiz.PHP.Eval.Discouraged -- Required by ACF's architecture which needs class names, not instances.
			eval( $class_code );
		}

		self::$location_classes[ $sanitized_key ] = $class_name;

		return $class_name;
	}

	/**
	 * Initialize the location type
	 *
	 * @return void
	 */
	public function initialize(): void {
		$instance_key = static::$instance_key;

		if ( $instance_key ) {
			$manager = Manager::get( $instance_key );
			if ( $manager ) {
				$this->name  = 'codesoup_options_' . $instance_key;
				$this->label = $manager->get_config( 'menu_label' );
			} else {
				$this->name  = 'codesoup_options_' . $instance_key;
				$this->label = __( 'Options', 'codesoup-options' );
			}
		} else {
			// Fallback for base class.
			$this->name  = 'codesoup_options';
			$this->label = __( 'CodeSoup Options', 'codesoup-options' );
		}

		$this->category    = 'forms';
		$this->object_type = 'post';
	}

	/**
	 * Get available values for this location
	 *
	 * @param array $rule The location rule.
	 * @return array
	 */
	public function get_values( $rule ): array {
		$choices      = array();
		$instance_key = static::$instance_key;

		// If this is an instance-specific location, only show pages for that instance.
		if ( $instance_key ) {
			$manager = Manager::get( $instance_key );
			if ( $manager ) {
				$pages = $manager->get_pages();
				foreach ( $pages as $page ) {
					$key             = $instance_key . ':' . $page->id;
					$choices[ $key ] = $page->title;
				}
			}
		} else {
			// Fallback: show all instances (for base class).
			$managers = Manager::get_all();
			foreach ( $managers as $manager ) {
				$manager_instance_key = $manager->get_instance_key();
				$pages                = $manager->get_pages();

				foreach ( $pages as $page ) {
					$key             = $manager_instance_key . ':' . $page->id;
					$choices[ $key ] = sprintf(
						'%s - %s',
						$manager->get_config( 'menu_label' ),
						$page->title
					);
				}
			}
		}

		return $choices;
	}

	/**
	 * Match the location rule
	 *
	 * @param array $rule The location rule.
	 * @param array $screen The screen data.
	 * @param array $field_group The field group data.
	 * @return bool
	 */
	public function match( $rule, $screen, $field_group ): bool {
		$parts = explode( ':', $rule['value'] );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $instance_key, $page_id ) = $parts;

		$manager = Manager::get( $instance_key );
		if ( ! $manager ) {
			return false;
		}

		$post_type = $screen['post_type'] ?? '';
		$post_id   = $screen['post_id'] ?? 0;

		if ( $post_type !== $manager->get_config( 'post_type' ) ) {
			return false;
		}

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				return false;
			}

			$expected_post_name = $config['prefix'] . $page_id;
			$matches            = ( $post->post_name === $expected_post_name );

			return $this->compare( $matches, $rule );
		}

		return false;
	}

	/**
	 * Compare the match result with the rule operator
	 *
	 * @param bool  $result The match result.
	 * @param array $rule The location rule.
	 * @return bool
	 */
	private function compare( bool $result, array $rule ): bool {
		$operator = $rule['operator'] ?? '==';

		if ( '!=' === $operator ) {
			return ! $result;
		}

		return $result;
	}
}

