<?php
/**
 * Unit tests for Cecomsmarad_Remote_Sync.
 *
 * Tests the pure-logic methods that do not require real HTTP calls:
 * is_sync_pending(), get_source_url(), and the advisory-lock behavior of sync().
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Remote_Sync extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		global $cecomsmarad_test_options, $cecomsmarad_test_transients;
		$cecomsmarad_test_options    = array();
		$cecomsmarad_test_transients = array();
	}

	// ──────────────────────────────────────────────────────────────
	// is_sync_pending()
	// ──────────────────────────────────────────────────────────────

	/**
	 * Returns true when the pending-sync option is '1'.
	 */
	public function test_is_sync_pending_returns_true_when_option_is_1(): void {
		global $cecomsmarad_test_options;
		$cecomsmarad_test_options[ Cecomsmarad_Remote_Sync::OPT_SYNC_NEEDED ] = '1';

		$this->assertTrue( Cecomsmarad_Remote_Sync::is_sync_pending() );
	}

	/**
	 * Returns false when the pending-sync option is '0'.
	 */
	public function test_is_sync_pending_returns_false_when_option_is_0(): void {
		global $cecomsmarad_test_options;
		$cecomsmarad_test_options[ Cecomsmarad_Remote_Sync::OPT_SYNC_NEEDED ] = '0';

		$this->assertFalse( Cecomsmarad_Remote_Sync::is_sync_pending() );
	}

	/**
	 * Returns false when the option is absent (defaults to '0').
	 */
	public function test_is_sync_pending_returns_false_when_option_not_set(): void {
		$this->assertFalse( Cecomsmarad_Remote_Sync::is_sync_pending() );
	}

	// ──────────────────────────────────────────────────────────────
	// get_source_url()
	// ──────────────────────────────────────────────────────────────

	/**
	 * Returns '' when CECOMSMARAD_DATA_SOURCE_URL is not defined.
	 */
	public function test_get_source_url_returns_empty_when_constant_not_defined(): void {
		if ( defined( 'CECOMSMARAD_DATA_SOURCE_URL' ) ) {
			$this->markTestSkipped( 'CECOMSMARAD_DATA_SOURCE_URL is already defined; cannot test undefined path.' );
		}

		$this->assertSame( '', Cecomsmarad_Remote_Sync::get_source_url() );
	}

	// ──────────────────────────────────────────────────────────────
	// sync() — advisory lock behavior
	// ──────────────────────────────────────────────────────────────

	/**
	 * sync() returns a failure result immediately when the advisory lock is held.
	 */
	public function test_sync_returns_error_immediately_when_lock_held(): void {
		global $cecomsmarad_test_transients;
		// Simulate a concurrent process holding the lock.
		$cecomsmarad_test_transients['cecomsmarad_sync_in_progress'] = '1';

		$result = Cecomsmarad_Remote_Sync::sync();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'already in progress', $result['message'] );
	}

	/**
	 * sync() releases the advisory lock even when do_sync fails early.
	 */
	public function test_sync_releases_lock_after_failed_run(): void {
		global $cecomsmarad_test_transients;

		// No lock held; CECOMSMARAD_DATA_SOURCE_URL not defined → do_sync fails.
		Cecomsmarad_Remote_Sync::sync();

		$this->assertArrayNotHasKey( 'cecomsmarad_sync_in_progress', $cecomsmarad_test_transients );
	}

	/**
	 * sync() returns a source-URL error when the data source is not configured.
	 */
	public function test_sync_returns_source_url_error_when_not_configured(): void {
		if ( defined( 'CECOMSMARAD_DATA_SOURCE_URL' ) ) {
			$this->markTestSkipped( 'CECOMSMARAD_DATA_SOURCE_URL is defined; cannot test unconfigured path.' );
		}

		$result = Cecomsmarad_Remote_Sync::sync();

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Source URL', $result['message'] );
	}
}
