<?php
/**
 * Province model — read-only queries for Turkish provinces.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Province
 *
 * Provides read-only access to the provinces table (81 records).
 * Data is populated during plugin activation via Cecomsmarad_Data_Importer.
 */
class Cecomsmarad_Province {

	/**
	 * Get all provinces ordered alphabetically by name.
	 *
	 * @return object[] Array of row objects with properties: id, code, name.
	 */
	public static function get_all(): array {
		$cache_key = 'cecomsmarad_provinces_all';
		$cached    = wp_cache_get( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query; result is cached via wp_cache_set below.
			"SELECT id, code, name FROM `{$wpdb->prefix}cecomsmarad_provinces` ORDER BY name ASC"
		);

		$data = is_array( $results ) ? $results : array();
		wp_cache_set( $cache_key, $data );

		return $data;
	}

	/**
	 * Get a single province by its code.
	 *
	 * @param string $code Province code (e.g. 'TR34').
	 * @return object|null Row object with id, code, name — or null if not found.
	 */
	public static function get_by_code( string $code ): ?object {
		$cache_key = 'cecomsmarad_province_code_' . sanitize_key( $code );
		$cached    = wp_cache_get( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query; result is cached via wp_cache_set below.
			$wpdb->prepare(
				"SELECT id, code, name FROM `{$wpdb->prefix}cecomsmarad_provinces` WHERE code = %s LIMIT 1",
				$code
			)
		);

		$data = $result ? $result : null;
		wp_cache_set( $cache_key, $data );

		return $data;
	}
}
