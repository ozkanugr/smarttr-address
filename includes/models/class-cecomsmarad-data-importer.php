<?php
/**
 * Database schema creation and address data import helpers.
 *
 * This class no longer reads local JSON files. All data is supplied
 * by Cecomsmarad_Remote_Sync, which fetches it from the cecom-address-tr API.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Data_Importer
 *
 * Responsibilities:
 *  - Create the two custom DB tables (idempotent via dbDelta).
 *  - Accept address data arrays from the caller and insert them in batches.
 *  - Store import metadata (counts, timestamp) in wp_options.
 */
class Cecomsmarad_Data_Importer {

	/**
	 * Number of rows per batch INSERT.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 500;

	/**
	 * Allowlisted custom table suffixes.
	 *
	 * Used to validate table names before interpolation into SQL.
	 *
	 * @var string[]
	 */
	private static array $allowed_tables = array(
		'cecomsmarad_provinces',
		'cecomsmarad_districts',
	);

	/**
	 * Validate and return a fully-qualified table name.
	 *
	 * Only table suffixes in the allowlist are accepted. This prevents
	 * arbitrary table names from being interpolated into SQL queries.
	 *
	 * @param string $suffix Table suffix without the WP prefix (e.g. 'cecomsmarad_provinces').
	 * @return string Fully-qualified table name.
	 * @throws \InvalidArgumentException If the suffix is not in the allowlist.
	 */
	private static function validated_table( string $suffix ): string {
		if ( ! in_array( $suffix, self::$allowed_tables, true ) ) {
			throw new \InvalidArgumentException( 'Unknown table suffix: ' . esc_html( $suffix ) );
		}

		global $wpdb;
		return $wpdb->prefix . $suffix;
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Schema
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Create all three custom tables via dbDelta().
	 *
	 * Uses WordPress dbDelta() for safe, idempotent schema management.
	 * No FOREIGN KEY constraints — dbDelta does not support them;
	 * referential integrity is enforced at the application layer.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}cecomsmarad_provinces (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			code VARCHAR(4) NOT NULL,
			name VARCHAR(100) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_code (code),
			KEY idx_name (name)
		) {$charset_collate};

		CREATE TABLE {$wpdb->prefix}cecomsmarad_districts (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			province_code VARCHAR(4) NOT NULL,
			name VARCHAR(100) NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_province_code (province_code),
			KEY idx_province_name (province_code, name)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Provinces
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Import provinces from a data array.
	 *
	 * @param array<string,string> $data Associative array {code => name}.
	 * @return int Number of rows inserted.
	 */
	public static function import_provinces_data( array $data ): int {
		global $wpdb;

		if ( empty( $data ) ) {
			return 0;
		}

		$table = self::validated_table( 'cecomsmarad_provinces' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Bulk import: table is truncated before re-seeding; table name validated via allowlist.
		$wpdb->query( "TRUNCATE TABLE `{$table}`" );

		$values = [];
		foreach ( $data as $code => $name ) {
			$values[] = (string) $code;
			$values[] = (string) $name;
		}

		$count = 0;
		foreach ( array_chunk( $values, self::BATCH_SIZE * 2 ) as $batch_values ) {
			$row_count    = count( $batch_values ) / 2;
			$placeholders = implode( ', ', array_fill( 0, $row_count, '(%s, %s)' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Bulk batch insert; table name validated via allowlist; dynamic placeholders from array_fill.
			$wpdb->query( $wpdb->prepare( "INSERT INTO `{$table}` (code, name) VALUES {$placeholders}", $batch_values ) );
			$count += $row_count;
		}

		return $count;
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Districts
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Import districts from a data array.
	 *
	 * @param array<string,string[]> $data Associative array {province_code => [district_names]}.
	 * @return int Number of rows inserted.
	 */
	public static function import_districts_data( array $data ): int {
		global $wpdb;

		if ( empty( $data ) ) {
			return 0;
		}

		$table = self::validated_table( 'cecomsmarad_districts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Bulk import: table is truncated before re-seeding; table name validated via allowlist.
		$wpdb->query( "TRUNCATE TABLE `{$table}`" );

		$values = [];
		foreach ( $data as $province_code => $districts ) {
			foreach ( (array) $districts as $name ) {
				$values[] = (string) $province_code;
				$values[] = (string) $name;
			}
		}

		$count = 0;
		foreach ( array_chunk( $values, self::BATCH_SIZE * 2 ) as $batch_values ) {
			$row_count    = count( $batch_values ) / 2;
			$placeholders = implode( ', ', array_fill( 0, $row_count, '(%s, %s)' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Bulk batch insert; table name validated via allowlist; dynamic placeholders from array_fill.
			$wpdb->query( $wpdb->prepare( "INSERT INTO `{$table}` (province_code, name) VALUES {$placeholders}", $batch_values ) );
			$count += $row_count;
		}

		return $count;
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Data presence check
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Check whether address data has already been imported.
	 *
	 * Returns true when the provinces table exists AND contains at least one
	 * row. Provinces are always the first table populated during a sync, so
	 * their presence reliably indicates that a full import has completed.
	 *
	 * Used by the activator to skip scheduling a redundant sync when the
	 * plugin is reactivated with data already in place.
	 *
	 * @return bool True if address data is present, false if tables are missing or empty.
	 */
	public static function has_data(): bool {
		global $wpdb;

		$table = self::validated_table( 'cecomsmarad_provinces' );

		// Suppress DB errors: if the table does not exist yet, get_var() returns
		// null (treated as 0) rather than triggering a visible DB error.
		$wpdb->suppress_errors( true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Activation-time check; must reflect real-time state; table name validated via allowlist.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		$wpdb->suppress_errors( false );

		return $count > 0;
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Metadata
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Store import metadata in wp_options.
	 *
	 * Records the last import timestamp and row counts for each table
	 * so the admin panel can display them.
	 *
	 * @return void
	 */
	public static function store_metadata(): void {
		global $wpdb;

		update_option( 'cecomsmarad_last_import', gmdate( 'Y-m-d H:i:s' ), false );

		$counts = [
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Post-import count snapshot; must reflect just-inserted data.
			'provinces' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}cecomsmarad_provinces`" ),
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'districts' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}cecomsmarad_districts`" ),
		];

		update_option( 'cecomsmarad_record_counts', wp_json_encode( $counts ), false );
	}
}
