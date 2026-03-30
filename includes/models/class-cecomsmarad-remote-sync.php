<?php
/**
 * Remote address data sync client.
 *
 * Fetches Turkish address data from the cecom-address-tr REST API and
 * writes it into the local database via Cecomsmarad_Data_Importer.
 *
 * Flow:
 *   1. Provinces  — single call, small payload.
 *   2. Districts  — single call, moderate payload.
 *
 * Authentication: WooCommerce REST API keys
 *   Authorization: Basic base64(consumer_key:consumer_secret)
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Remote_Sync
 */
class Cecomsmarad_Remote_Sync {

	/**
	 * wp_option key for the pending-sync flag.
	 * Set to '1' when a sync is needed; cleared after a successful sync.
	 *
	 * @var string
	 */
	public const OPT_SYNC_NEEDED = 'cecomsmarad_sync_needed';

	/**
	 * Request timeout in seconds for each API call.
	 *
	 * @var int
	 */
	public const REQUEST_TIMEOUT = 60;

	// ──────────────────────────────────────────────────────────────────────────
	// Public API
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Transient key used as an advisory lock to prevent concurrent syncs.
	 *
	 * @var string
	 */
	private const SYNC_LOCK = 'cecomsmarad_sync_in_progress';

	/**
	 * How long (seconds) the advisory lock is held before expiring automatically.
	 * 10 minutes is generous enough for a full provinces + districts import.
	 *
	 * @var int
	 */
	private const SYNC_LOCK_TTL = 600;

	/**
	 * Perform a full address data sync from the remote cecom-address-tr API.
	 *
	 * Safe to call multiple times — each run truncates the two tables
	 * and re-imports from scratch.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function sync(): array {
		// ── Advisory lock — prevent concurrent syncs ──────────────────────────
		// Two PHP processes can enter sync() simultaneously when:
		//   a) spawn_cron() fires the WP-Cron HTTP request at the same time a
		//      server-side cron also runs wp-cron.php, or
		//   b) cecomsmarad_do_address_sync and cecomsmarad_do_auto_update both become
		//      due at the same moment.
		// Without a lock the provinces/districts tables end up with duplicate rows
		// because Process B calls TRUNCATE while Process A is still inserting,
		// then both processes complete their remaining INSERT batches
		// independently into the same tables.
		if ( false !== get_transient( self::SYNC_LOCK ) ) {
			return [
				'success' => false,
				'message' => __( 'A sync is already in progress. Please try again shortly.', 'smarttr-address' ),
			];
		}
		set_transient( self::SYNC_LOCK, '1', self::SYNC_LOCK_TTL );

		try {
			return self::do_sync();
		} finally {
			delete_transient( self::SYNC_LOCK );
		}
	}

	/**
	 * Internal sync implementation. Called exclusively from sync() after the
	 * advisory lock has been acquired.
	 *
	 * @return array{success: bool, message: string}
	 */
	private static function do_sync(): array {
		$source_url = self::get_source_url();

		if ( '' === $source_url ) {
			return [
				'success' => false,
				'message' => __( 'Source URL is not configured.', 'smarttr-address' ),
			];
		}

		$headers = [ 'Accept' => 'application/json' ];

		// Append WooCommerce REST API credentials as query parameters.
		// This is more reliable than the Authorization header, which many
		// Apache/hosting configurations strip before reaching PHP.
		$auth_query = http_build_query(
			[
				'consumer_key'    => CECOMSMARAD_API_CONSUMER_KEY,
				'consumer_secret' => CECOMSMARAD_API_CONSUMER_SECRET,
			]
		);

		$base = rtrim( $source_url, '/' ) . '/wp-json/cecom-address-tr/v1/address-data';

		// ── 1. Provinces ─────────────────────────────────────────────────────
		$result = self::fetch( $base . '/provinces?' . $auth_query, $headers );
		if ( ! $result['success'] ) {
			return $result;
		}
		Cecomsmarad_Data_Importer::import_provinces_data( $result['body']['data'] ?? [] );

		// ── 2. Districts ──────────────────────────────────────────────────────
		$result = self::fetch( $base . '/districts?' . $auth_query, $headers );
		if ( ! $result['success'] ) {
			return $result;
		}
		Cecomsmarad_Data_Importer::import_districts_data( $result['body']['data'] ?? [] );

		// ── 3. Metadata & version ─────────────────────────────────────────────
		$version_result = self::fetch( $base . '/version?' . $auth_query, $headers );
		if ( $version_result['success'] ) {
			$version = $version_result['body']['version'] ?? '';
			update_option( 'cecomsmarad_data_version', $version, false );
		}

		Cecomsmarad_Data_Importer::store_metadata();
		wp_cache_delete( 'cecomsmarad_provinces_all' );

		// Clear the pending-sync flag.
		delete_option( self::OPT_SYNC_NEEDED );

		return [
			'success' => true,
			'message' => __( 'Address data successfully updated.', 'smarttr-address' ),
		];
	}

	/**
	 * Schedule a one-time WP-Cron sync and immediately spawn cron.
	 *
	 * Used during plugin activation so the sync runs asynchronously
	 * without blocking the activation request.
	 *
	 * @return void
	 */
	public static function schedule_sync(): void {
		update_option( self::OPT_SYNC_NEEDED, '1', false );

		if ( ! wp_next_scheduled( 'cecomsmarad_do_address_sync' ) ) {
			wp_schedule_single_event( time(), 'cecomsmarad_do_address_sync' );
		}

		// Fire cron immediately in the background (non-blocking).
		spawn_cron();
	}

	/**
	 * Get the source URL (statically embedded in the plugin).
	 *
	 * Enforces HTTPS so that address data is never fetched over an unencrypted
	 * connection, regardless of how CECOMSMARAD_DATA_SOURCE_URL is defined.
	 *
	 * @return string Base URL of the cecom-address-tr installation, or empty string
	 *                when the constant is not defined or is not an HTTPS URL.
	 */
	public static function get_source_url(): string {
		if ( ! defined( 'CECOMSMARAD_DATA_SOURCE_URL' ) ) {
			return '';
		}

		$url = rtrim( CECOMSMARAD_DATA_SOURCE_URL, '/' );

		// Reject non-HTTPS URLs — all API calls must travel over TLS.
		if ( ! str_starts_with( $url, 'https://' ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Whether a sync is pending (scheduled but not yet completed).
	 *
	 * @return bool
	 */
	public static function is_sync_pending(): bool {
		return '1' === get_option( self::OPT_SYNC_NEEDED, '0' );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Internal helpers
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Perform a GET request and return decoded JSON body.
	 *
	 * @param string               $url     Full URL.
	 * @param array<string,string> $headers HTTP headers.
	 * @return array{success: bool, message: string, body: array<string,mixed>}
	 */
	private static function fetch( string $url, array $headers ): array {
		$response = wp_remote_get(
			$url,
			[
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => $headers,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'API connection error: %s', 'smarttr-address' ),
					$response->get_error_message()
				),
				'body'    => [],
			];
		}

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return [
				'success' => false,
				'message' => __( 'Unexpected response from the data server (invalid JSON). Please try again.', 'smarttr-address' ),
				'body'    => [],
			];
		}

		if ( 401 === $code || 403 === $code ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Access to the data API was denied (HTTP %d). Please check your source server configuration.', 'smarttr-address' ),
					$code
				),
				'body'    => [],
			];
		}

		if ( $code < 200 || $code >= 300 ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Server error: HTTP %d', 'smarttr-address' ),
					$code
				),
				'body'    => [],
			];
		}

		return [
			'success' => true,
			'message' => '',
			'body'    => is_array( $body ) ? $body : [],
		];
	}
}
