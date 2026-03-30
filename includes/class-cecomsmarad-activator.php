<?php
/**
 * Plugin activation handler.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Activator
 *
 * Runs on plugin activation: validates environment requirements,
 * creates database tables, and imports address data.
 */
class Cecomsmarad_Activator {

	/**
	 * Minimum PHP version required.
	 *
	 * @var string
	 */
	private const MIN_PHP_VERSION = '8.1';

	/**
	 * Minimum WordPress version required.
	 *
	 * @var string
	 */
	private const MIN_WP_VERSION = '6.4';

	/**
	 * Minimum WooCommerce version required.
	 *
	 * @var string
	 */
	private const MIN_WC_VERSION = '7.0';

	/**
	 * Plugin activation entry point.
	 *
	 * Creates database tables immediately, then schedules a background
	 * WP-Cron job to sync address data from the cecom-address-tr API.
	 * The cron job is spawned right away (non-blocking) so data arrives
	 * within seconds without delaying the activation request itself.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::check_requirements();
		self::create_tables();
		self::schedule_data_sync();
		Cecomsmarad_Mu_Installer::install();
	}

	/**
	 * Validate PHP, WordPress, and WooCommerce requirements.
	 *
	 * Deactivates the plugin and halts with wp_die() on failure.
	 *
	 * @return void
	 */
	private static function check_requirements(): void {
		$errors = array();

		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Required PHP version, 2: Current PHP version. */
				__( 'SmartTR Address requires PHP %1$s or higher. You are running PHP %2$s.', 'smarttr-address' ),
				self::MIN_PHP_VERSION,
				PHP_VERSION
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version. */
				__( 'SmartTR Address requires WordPress %1$s or higher. You are running WordPress %2$s.', 'smarttr-address' ),
				self::MIN_WP_VERSION,
				get_bloginfo( 'version' )
			);
		}

		if ( ! defined( 'WC_VERSION' ) ) {
			$errors[] = __( 'SmartTR Address requires WooCommerce to be installed and active.', 'smarttr-address' );
		} elseif ( version_compare( WC_VERSION, self::MIN_WC_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Required WooCommerce version, 2: Current WooCommerce version. */
				__( 'SmartTR Address requires WooCommerce %1$s or higher. You are running WooCommerce %2$s.', 'smarttr-address' ),
				self::MIN_WC_VERSION,
				WC_VERSION
			);
		}

		if ( ! empty( $errors ) ) {
			deactivate_plugins( plugin_basename( CECOMSMARAD_PLUGIN_FILE ) );

			wp_die(
				'<p>' . implode( '</p><p>', array_map( 'esc_html', $errors ) ) . '</p>',
				esc_html__( 'SmartTR Address Activation Error', 'smarttr-address' ),
				array(
					'response'  => 500,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Create custom database tables via dbDelta().
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		Cecomsmarad_Data_Importer::create_tables();
	}

	/**
	 * Schedule a background address data sync via WP-Cron, if needed.
	 *
	 * Only schedules a sync when the address tables are missing or empty.
	 * If data is already present (e.g. plugin reactivation after an update),
	 * the existing data is kept and no network request is made.
	 *
	 * @return void
	 */
	private static function schedule_data_sync(): void {
		if ( ! Cecomsmarad_Data_Importer::has_data() ) {
			Cecomsmarad_Remote_Sync::schedule_sync();
		}
	}
}
