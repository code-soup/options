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
	 * Registry of instance configurations
	 *
	 * @var array<string, array{instance_key: string, menu_label: string}>
	 */
	private static array $instance_registry = array();

	/**
	 * Register instance configuration
	 *
	 * @param string $instance_key Manager instance key.
	 * @param string $menu_label Menu label from config.
	 * @return string Location type name.
	 */
	public static function create_for_instance( string $instance_key, string $menu_label ): string {
		$sanitized_key = sanitize_key( $instance_key );

		if ( empty( $sanitized_key ) ) {
			$sanitized_key = 'default';
		}

		self::$instance_registry[ $sanitized_key ] = array(
			'instance_key' => $instance_key,
			'menu_label'   => $menu_label,
		);

		return __CLASS__;
	}

	/**
	 * Initialize the location type
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->name        = 'codesoup_options';
		$this->label       = __( 'Options', 'codesoup-options' );
		$this->category    = 'CodeSoup';
		$this->object_type = 'post';
	}

	/**
	 * Get available values for this location
	 *
	 * @param array $rule The location rule.
	 * @return array
	 */
	public function get_values( $rule ): array {
		$choices  = array();
		$managers = Manager::get_all();

		foreach ( $managers as $manager ) {
			$manager_instance_key = $manager->get_instance_key();
			$pages                = $manager->get_pages();

			foreach ( $pages as $page ) {
				$key             = Manager::normalize_slug(
					$manager->get_config( 'prefix' ) . $page->id
				);
				$choices[ $key ] = sprintf(
					'%s - %s',
					$manager->get_config( 'menu_label' ),
					$page->title
				);
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
		$post_type = $screen['post_type'] ?? '';
		$post_id   = $screen['post_id'] ?? 0;

		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$matches = ( $post->post_name === $rule['value'] );

		return $this->compare( $matches, $rule );
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