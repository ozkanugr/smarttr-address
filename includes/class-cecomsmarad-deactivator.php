<?php
/**
 * Plugin deactivation handler.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Deactivator
 *
 * Runs on plugin deactivation: clears transients and flushes rewrite rules.
 * Does NOT remove data — that happens only on uninstall.
 */
class Cecomsmarad_Deactivator {

	/**
	 * Plugin deactivation entry point.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::clear_transients();
		wp_clear_scheduled_hook( 'cecomsmarad_do_address_sync' );
		flush_rewrite_rules();
	}

	/**
	 * Delete all CecomsmaradAddress transients from the database.
	 *
	 * @return void
	 */
	private static function clear_transients(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deactivation cleanup: bulk-delete all plugin transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_cecomsmarad_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_cecomsmarad_' ) . '%'
			)
		);
	}
}
