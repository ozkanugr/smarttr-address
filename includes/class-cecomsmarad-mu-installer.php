<?php
/**
 * Must-use plugin installer.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Mu_Installer
 *
 * Installs and updates the standalone order-display mu-plugin that keeps
 * SmartTR custom field values visible in WooCommerce admin order pages even
 * when SmartTR Address is deactivated or deleted.
 *
 * The mu-plugin reads the `_cecomsmarad_fields_snapshot` order meta saved at
 * checkout time, which is never removed by uninstall.php.
 */
class Cecomsmarad_Mu_Installer {

	/**
	 * Filename of the mu-plugin to install.
	 *
	 * @var string
	 */
	private const MU_PLUGIN_FILENAME = 'cecomsmarad-order-display.php';

	/**
	 * Install or update the mu-plugin.
	 *
	 * Copies `mu-plugin/cecomsmarad-order-display.php` from the plugin directory
	 * to WPMU_PLUGIN_DIR. Silently skips when the destination is not writable
	 * or when the WPMU_PLUGIN_DIR cannot be created.
	 *
	 * @return void
	 */
	public static function install(): void {
		$source = CECOMSMARAD_PLUGIN_DIR . 'mu-plugin/' . self::MU_PLUGIN_FILENAME;

		if ( ! file_exists( $source ) ) {
			return;
		}

		if ( ! file_exists( WPMU_PLUGIN_DIR ) ) {
			if ( ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
				return;
			}
		}

		if ( ! wp_is_writable( WPMU_PLUGIN_DIR ) ) {
			return;
		}

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . self::MU_PLUGIN_FILENAME;

		copy( $source, $dest );
	}
}
