<?php
/**
 * PSR-4 Autoloader with WordPress naming conventions.
 *
 * Converts PSR-4 namespaced classes to WordPress-style file names:
 * - CodeSoup\Options\Manager → includes/class-manager.php
 * - CodeSoup\Options\Integrations\ACF\Init → includes/integrations/acf/class-init.php
 * - CodeSoup\Options\Integrations\IntegrationInterface → includes/integrations/integration-interface.php
 *
 * @package CodeSoup\Options
 */

namespace CodeSoup\Options;

/**
 * Autoloader class for WordPress-style file naming.
 *
 * Handles automatic loading of classes following WordPress naming conventions:
 * - Converts PascalCase to kebab-case
 * - Adds 'class-' prefix to class files
 * - Keeps interface files without 'class-' prefix
 * - All filenames and directories are lowercase
 */
class Autoloader {

	/**
	 * Base namespace for this autoloader.
	 *
	 * @var string
	 */
	private const NAMESPACE_PREFIX = 'CodeSoup\\Options\\';

	/**
	 * Base directory for class files.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir Base directory where class files are located.
	 */
	public function __construct( string $base_dir ) {
		$this->base_dir = rtrim( $base_dir, '/' );
	}

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'load_class' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public function load_class( string $class_name ): void {
		// Only handle classes in our namespace.
		if ( ! str_starts_with( $class_name, self::NAMESPACE_PREFIX ) ) {
			return;
		}

		$file_path = $this->get_file_path( $class_name );

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}

	/**
	 * Convert class name to file path.
	 *
	 * Transformation steps:
	 * 1. Remove namespace prefix: CodeSoup\Options\Manager → Manager
	 * 2. Convert namespace separators: Integrations\ACF\Init → Integrations/ACF/Init
	 * 3. Convert PascalCase to kebab-case: IntegrationInterface → integration-interface
	 * 4. Convert to lowercase: Manager → manager
	 * 5. Add class- prefix (except for interfaces): manager → class-manager
	 * 6. Add .php extension: class-manager → class-manager.php
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return string File path.
	 */
	private function get_file_path( string $class_name ): string {
		// Remove namespace prefix.
		$relative_class = str_replace( self::NAMESPACE_PREFIX, '', $class_name );

		// Convert namespace separators to directory separators.
		$relative_class = str_replace( '\\', '/', $relative_class );

		// Convert PascalCase to kebab-case before lowercasing.
		// This regex finds lowercase letter followed by uppercase letter and inserts a dash.
		$filename = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $relative_class );

		// Convert to lowercase.
		$filename = strtolower( $filename );

		// Add class- prefix for class files, but not for interfaces.
		if ( ! str_ends_with( $filename, 'interface' ) ) {
			$parts         = explode( '/', $filename );
			$key           = count( $parts ) - 1;
			$parts[ $key ] = 'class-' . $parts[ $key ];
			$filename      = implode( '/', $parts );
		}

		// Add .php extension.
		$filename .= '.php';

		// Build full path.
		return sprintf(
			'%s/includes/%s',
			$this->base_dir,
			$filename
		);
	}
}

// Initialize and register the autoloader.
( function () {
	$autoloader = new Autoloader( dirname( __DIR__ ) );
	$autoloader->register();
} )();
