<?php
/**
 * Manual PSR-0-style autoloader for CecomsmaradAddress classes.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Autoloader
 *
 * Resolves Cecomsmarad_* class names to file paths following the WordPress
 * filename convention: class-cecomsmarad-foo-bar.php.
 */
class Cecomsmarad_Autoloader {

	/**
	 * Directories to search for class files.
	 *
	 * @var string[]
	 */
	private static array $directories = array(
		'includes/',
		'includes/models/',
		'includes/controllers/',
	);

	/**
	 * Register the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class_name The fully-qualified class name.
	 * @return void
	 */
	public static function autoload( string $class_name ): void {
		if ( 0 !== strpos( $class_name, 'Cecomsmarad_' ) ) {
			return;
		}

		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		foreach ( self::$directories as $directory ) {
			$file_path = CECOMSMARAD_PLUGIN_DIR . $directory . $file_name;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				return;
			}
		}
	}
}
