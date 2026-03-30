<?php
/**
 * District model — read-only queries for Turkish districts.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_District
 *
 * Provides read-only access to the districts table (~970 records).
 * Data is populated during plugin activation via Cecomsmarad_Data_Importer.
 */
class Cecomsmarad_District {

	/**
	 * Get all districts for a given province, ordered alphabetically by name.
	 *
	 * @param string $province_code Province code (e.g. 'TR34').
	 * @return object[] Array of row objects with properties: id, name.
	 */
	public static function get_by_province( string $province_code ): array {
		$cache_key = 'cecomsmarad_districts_' . sanitize_key( $province_code );
		$cached    = wp_cache_get( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query; result is cached via wp_cache_set below.
			$wpdb->prepare(
				"SELECT id, name FROM `{$wpdb->prefix}cecomsmarad_districts` WHERE province_code = %s ORDER BY name ASC",
				$province_code
			)
		);

		$data = is_array( $results ) ? $results : array();
		wp_cache_set( $cache_key, $data );

		return $data;
	}

	/**
	 * Get a single district by its ID.
	 *
	 * @param int $id District ID.
	 * @return object|null Row object with id, name, province_code — or null if not found.
	 */
	public static function get_by_id( int $id ): ?object {
		$cache_key = 'cecomsmarad_district_' . $id;
		$cached    = wp_cache_get( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query; result is cached via wp_cache_set below.
			$wpdb->prepare(
				"SELECT id, name, province_code FROM `{$wpdb->prefix}cecomsmarad_districts` WHERE id = %d LIMIT 1",
				$id
			)
		);

		$data = $result ? $result : null;
		wp_cache_set( $cache_key, $data );

		return $data;
	}
}
