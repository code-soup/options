<?php
/**
 * Config Helper
 *
 * Handles configuration normalization, validation, and backward compatibility.
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

defined( 'ABSPATH' ) || die;

/**
 * Config_Helper class
 *
 * Provides configuration utilities:
 * - Normalization: Converts old flat structure to new nested structure
 * - Validation: Validates configuration values and constraints
 * - Backward compatibility: Handles deprecated keys with warnings
 *
 * @since 1.1.0
 */
class Config_Helper {

	/**
	 * Mapping of old flat keys to new nested paths
	 *
	 * @var array<string, array<string>>
	 */
	private const KEY_MAPPING = array(
		'menu_label'       => array( 'menu', 'label' ),
		'menu_icon'        => array( 'menu', 'icon' ),
		'menu_position'    => array( 'menu', 'position' ),
		'parent_menu'      => array( 'menu', 'parent' ),
		'ui_mode'          => array( 'ui', 'mode' ),
		'tab_position'     => array( 'ui', 'tab_position' ),
		'templates_dir'    => array( 'ui', 'templates_dir' ),
		'disable_styles'   => array( 'assets', 'disable_styles' ),
		'disable_scripts'  => array( 'assets', 'disable_scripts' ),
		'disable_branding' => array( 'assets', 'disable_branding' ),
	);

	/**
	 * Normalize configuration structure
	 *
	 * Converts old flat keys to new nested structure.
	 * Shows admin warning if deprecated keys are detected.
	 *
	 * @param array  $config User-provided configuration.
	 * @param string $instance_key Instance identifier for warning context.
	 * @return array Normalized configuration.
	 */
	public static function normalize( array $config, string $instance_key ): array {
		$normalized      = array();
		$deprecated_keys = array();

		foreach ( $config as $key => $value ) {
			if ( isset( self::KEY_MAPPING[ $key ] ) ) {
				$deprecated_keys[]          = $key;
				list( $group, $nested_key ) = self::KEY_MAPPING[ $key ];

				if ( ! isset( $normalized[ $group ] ) ) {
					$normalized[ $group ] = array();
				}

				$normalized[ $group ][ $nested_key ] = $value;
			} else {
				$normalized[ $key ] = $value;
			}
		}

		// Show deprecation warning if old keys were used.
		if ( ! empty( $deprecated_keys ) ) {
			self::show_deprecation_warning( $deprecated_keys, $instance_key );
		}

		return $normalized;
	}

	/**
	 * Show deprecation warning in WordPress admin
	 *
	 * @param array  $deprecated_keys List of deprecated keys used.
	 * @param string $instance_key Instance identifier.
	 * @return void
	 */
	private static function show_deprecation_warning( array $deprecated_keys, string $instance_key ): void {
		add_action(
			'admin_notices',
			function () use ( $deprecated_keys, $instance_key ) {
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>CodeSoup Options (%s):</strong> Deprecated configuration detected: <code>%s</code>. Please migrate to nested structure. <a href="https://github.com/code-soup/codesoup-options/blob/main/docs/migration-v1.1.md" target="_blank">View Migration Guide</a></p></div>',
					esc_html( $instance_key ),
					esc_html( implode( ', ', $deprecated_keys ) )
				);
			}
		);
	}

	/**
	 * Check if config uses deprecated keys
	 *
	 * @param array $config Configuration to check.
	 * @return bool True if deprecated keys found.
	 */
	public static function has_deprecated_keys( array $config ): bool {
		foreach ( array_keys( self::KEY_MAPPING ) as $old_key ) {
			if ( isset( $config[ $old_key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get list of deprecated keys
	 *
	 * @return array<string> List of deprecated configuration keys.
	 */
	public static function get_deprecated_keys(): array {
		return array_keys( self::KEY_MAPPING );
	}

	/**
	 * Get mapping for a specific deprecated key
	 *
	 * @param string $old_key Deprecated key.
	 * @return array|null Array with [group, nested_key] or null if not deprecated.
	 */
	public static function get_mapping( string $old_key ): ?array {
		return self::KEY_MAPPING[ $old_key ] ?? null;
	}

	/**
	 * Translate deprecated key to array path
	 *
	 * Converts old flat keys to array path for nested access.
	 *
	 * Examples:
	 * - 'menu_label' -> ['menu', 'label']
	 * - 'ui_mode' -> ['ui', 'mode']
	 * - 'post_type' -> null (unchanged, direct access)
	 *
	 * @param string $key Configuration key (old or new format).
	 * @return array|null Array path for nested access or null if not deprecated.
	 */
	public static function translate_key( string $key ): ?array {
		return self::get_mapping( $key );
	}

	/**
	 * Validate configuration
	 *
	 * @param array  $config Configuration array to validate.
	 * @param Logger $logger Logger instance for warnings.
	 * @return void
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public static function validate( array $config, Logger $logger ): void {
		// Validate required config keys exist.
		if ( empty( $config['post_type'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "post_type" is required.', 'codesoup-options' )
			);
		}

		if ( empty( $config['prefix'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "prefix" is required.', 'codesoup-options' )
			);
		}

		if ( empty( $config['menu']['label'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "menu.label" is required.', 'codesoup-options' )
			);
		}

		// Validate menu_position is numeric and within range.
		if ( isset( $config['menu']['position'] ) ) {
			if ( ! is_numeric( $config['menu']['position'] ) ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %s: type of the value provided */
						esc_html__( 'Config "menu.position" must be numeric, "%s" given.', 'codesoup-options' ),
						esc_html( gettype( $config['menu']['position'] ) )
					)
				);
			}

			$position = (int) $config['menu']['position'];
			if ( $position < 1 || $position > 100 ) {
				throw new \InvalidArgumentException(
					sprintf(
						/* translators: %d: position value */
						esc_html__( 'Config "menu.position" must be between 1 and 100, %d given.', 'codesoup-options' ),
						esc_html( (string) $position )
					)
				);
			}
		}

		// Validate menu_icon starts with dashicons- or is a valid URL/base64.
		if ( isset( $config['menu']['icon'] ) ) {
			$icon = $config['menu']['icon'];
			if ( ! empty( $icon ) &&
				strpos( $icon, 'dashicons-' ) !== 0 &&
				strpos( $icon, 'data:image' ) !== 0 &&
				! filter_var( $icon, FILTER_VALIDATE_URL ) ) {
				throw new \InvalidArgumentException(
					esc_html__( 'Config "menu.icon" must be a dashicon class, data URI, or valid URL.', 'codesoup-options' )
				);
			}
		}

		// Validate integrations config structure.
		if ( isset( $config['integrations'] ) && ! is_array( $config['integrations'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "integrations" must be an array.', 'codesoup-options' )
			);
		}

		// Validate revisions is boolean.
		if ( isset( $config['revisions'] ) && ! is_bool( $config['revisions'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'Config "revisions" must be a boolean.', 'codesoup-options' )
			);
		}

		// Validate prefix doesn't use reserved values.
		$reserved_prefixes = array( 'wp_', 'wordpress_', 'admin_', 'post_', 'page_', 'user_', 'option_', 'meta_' );
		$prefix            = $config['prefix'];

		foreach ( $reserved_prefixes as $reserved ) {
			if ( strpos( $prefix, $reserved ) === 0 ) {
				$logger->warning(
					sprintf(
						/* translators: 1: prefix, 2: reserved prefix */
						__( 'Prefix "%1$s" starts with reserved prefix "%2$s". This may cause conflicts.', 'codesoup-options' ),
						$prefix,
						$reserved
					)
				);
			}
		}

		// Check if prefix is too short (increases collision risk).
		if ( strlen( $prefix ) < 3 ) {
			$logger->warning(
				sprintf(
					/* translators: 1: prefix, 2: character count */
					__( 'Prefix "%1$s" is very short (%2$d characters). Consider using a longer, more unique prefix.', 'codesoup-options' ),
					$prefix,
					strlen( $prefix )
				)
			);
		}

		// Check for existing post types with similar names.
		$post_types = get_post_types( array(), 'names' );
		$post_type  = $config['post_type'];

		foreach ( $post_types as $existing_type ) {
			if ( $existing_type !== $post_type && strpos( $existing_type, $prefix ) === 0 ) {
				$logger->warning(
					sprintf(
						/* translators: %s: post type name */
						__( 'Post type "%s" already exists with similar prefix. This may cause confusion.', 'codesoup-options' ),
						$existing_type
					)
				);
			}
		}
	}
}
