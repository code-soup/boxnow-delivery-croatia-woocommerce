<?php
/**
 * Custom autoloader for WordPress-style filenames.
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Namespace to directory mappings.
	 *
	 * @var array
	 */
	private static $namespace_map = array(
		'CodeSoup\BoxNow\\Core\\'                        => 'includes/core/',
		'CodeSoup\BoxNow\\Admin\\'                       => 'includes/admin/',
		'CodeSoup\BoxNow\\Frontend\\'                    => 'includes/frontend/',
		'CodeSoup\BoxNow\\Providers\\'                   => 'includes/providers/',
		'CodeSoup\BoxNow\\Abstracts\\'                   => 'includes/abstracts/',
		'CodeSoup\BoxNow\\Interfaces\\'                  => 'includes/interfaces/',
		'CodeSoup\BoxNow\\Traits\\'                      => 'includes/traits/',
		'CodeSoup\BoxNow\\Helpers\\'                     => 'includes/helpers/',
		'CodeSoup\BoxNow\\Constants\\'                   => 'includes/constants/',
		'CodeSoup\BoxNow\\Exceptions\\'                  => 'includes/exceptions/',
		'CodeSoup\BoxNow\\Services\\Shipping\\'          => 'includes/services/shipping/',
		'CodeSoup\BoxNow\\Services\\API\\'               => 'includes/services/api/',
		'CodeSoup\BoxNow\\Services\\Checkout\\'          => 'includes/services/checkout/',
		'CodeSoup\BoxNow\\Services\\Orders\\'            => 'includes/services/orders/',
		'CodeSoup\BoxNow\\Services\\Email\\'             => 'includes/services/email/',
		'CodeSoup\BoxNow\\Services\\Ajax\\'              => 'includes/services/ajax/',
		'CodeSoup\BoxNow\\Services\\Shortcodes\\'        => 'includes/services/shortcodes/',
		'CodeSoup\BoxNow\\Services\\'                    => 'includes/services/',
		'CodeSoup\BoxNow\\Integrations\\WooCommerce\\'   => 'includes/integrations/woocommerce/',
	);

	/**
	 * Base directory.
	 *
	 * @var string
	 */
	private static $base_dir = '';

	/**
	 * Register the autoloader.
	 *
	 * @param string $base_dir Base directory path.
	 * @return void
	 */
	public static function register( $base_dir ) {
		self::$base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class Full class name with namespace.
	 * @return void
	 */
	public static function autoload( $class ) {
		// Check if class belongs to CodeSoup\BoxNow namespace.
		if ( ! str_starts_with( $class, 'CodeSoup\BoxNow\\' ) ) {
			return;
		}

		$file = self::get_file_path( $class );

		if ( $file && file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Convert class name to WordPress-style filename.
	 *
	 * @param string $class Full class name with namespace.
	 * @return string|false File path or false if not found.
	 */
	private static function get_file_path( $class ) {
		$directory = self::get_directory( $class );

		if ( ! $directory ) {
			return false;
		}

		$class_name = self::get_class_name( $class );
		$filename   = self::convert_to_filename( $class_name, $class );

		return self::$base_dir . $directory . $filename;
	}

	/**
	 * Get directory for class based on namespace.
	 *
	 * @param string $class Full class name with namespace.
	 * @return string|false Directory path or false if not found.
	 */
	private static function get_directory( $class ) {
		foreach ( self::$namespace_map as $namespace => $directory ) {
			if ( str_starts_with( $class, $namespace ) ) {
				return $directory;
			}
		}

		return false;
	}

	/**
	 * Get class name without namespace.
	 *
	 * @param string $class Full class name with namespace.
	 * @return string Class name without namespace.
	 */
	private static function get_class_name( $class ) {
		$parts = explode( '\\', $class );
		return end( $parts );
	}

	/**
	 * Convert class name to WordPress-style filename.
	 *
	 * @param string $class_name Class name without namespace.
	 * @param string $full_class Full class name with namespace.
	 * @return string Filename.
	 */
	private static function convert_to_filename( $class_name, $full_class ) {
		$prefix = self::get_file_prefix( $class_name, $full_class );

		// Convert CamelCase to kebab-case.
		$filename = self::camel_to_kebab( $class_name );

		// Remove suffix from filename if present.
		$filename = self::remove_suffix( $filename );

		return $prefix . $filename . '.php';
	}

	/**
	 * Get file prefix based on class type.
	 *
	 * @param string $class_name Class name without namespace.
	 * @param string $full_class Full class name with namespace.
	 * @return string File prefix (class-, trait-, interface-).
	 */
	private static function get_file_prefix( $class_name, $full_class ) {
		if ( str_contains( $full_class, '\\Traits\\' ) || str_ends_with( $full_class, '_Trait' ) ) {
			return 'trait-';
		}

		if ( str_contains( $full_class, '\\Interfaces\\' ) || str_ends_with( $full_class, 'Interface' ) ) {
			return 'interface-';
		}

		return 'class-';
	}

	/**
	 * Convert CamelCase to kebab-case.
	 *
	 * @param string $string CamelCase string.
	 * @return string kebab-case string.
	 */
	private static function camel_to_kebab( $string ) {
		// Handle consecutive uppercase letters (e.g., APIServiceProvider -> API-Service-Provider)
		$string = preg_replace( '/([A-Z]+)([A-Z][a-z])/', '$1-$2', $string );
		// Handle normal CamelCase (e.g., ServiceProvider -> Service-Provider)
		$string = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $string );
		// Convert underscores to dashes
		$string = str_replace( '_', '-', $string );
		return strtolower( $string );
	}

	/**
	 * Remove common suffixes from filename.
	 *
	 * @param string $filename Filename.
	 * @return string Filename without suffix.
	 */
	private static function remove_suffix( $filename ) {
		$suffixes = array( '-trait', '-interface' );

		foreach ( $suffixes as $suffix ) {
			if ( substr( $filename, -strlen( $suffix ) ) === $suffix ) {
				return substr( $filename, 0, -strlen( $suffix ) );
			}
		}

		return $filename;
	}
}

