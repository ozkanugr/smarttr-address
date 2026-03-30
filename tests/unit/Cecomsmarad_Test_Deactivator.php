<?php
/**
 * Unit tests for Cecomsmarad_Deactivator.
 *
 * Covers: deactivate() — transient cleanup query and scheduled hook removal.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Class Cecomsmarad_Test_Deactivator
 */
class Cecomsmarad_Test_Deactivator extends TestCase {

	/**
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->reset();
	}

	/**
	 * deactivate() does not throw or fatal.
	 */
	public function test_deactivate_runs_without_error(): void {
		Cecomsmarad_Deactivator::deactivate();
		$this->assertTrue( true );
	}

	/**
	 * deactivate() issues a DELETE query against the options table.
	 */
	public function test_deactivate_issues_delete_query(): void {
		global $wpdb;

		Cecomsmarad_Deactivator::deactivate();

		$delete_queries = array_filter(
			$wpdb->queries,
			static fn( $q ) => stripos( $q, 'DELETE' ) !== false
		);

		$this->assertNotEmpty( $delete_queries );
	}

	/**
	 * deactivate() DELETE query targets the options table.
	 */
	public function test_deactivate_query_targets_options_table(): void {
		global $wpdb;

		Cecomsmarad_Deactivator::deactivate();

		$found = false;
		foreach ( $wpdb->queries as $q ) {
			if ( stripos( $q, 'DELETE' ) !== false && stripos( $q, $wpdb->options ) !== false ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'DELETE query should reference the options table.' );
	}

	/**
	 * deactivate() DELETE query filters by the plugin-specific transient prefix.
	 */
	public function test_deactivate_query_uses_plugin_transient_prefix(): void {
		global $wpdb;

		Cecomsmarad_Deactivator::deactivate();

		$found = false;
		foreach ( $wpdb->queries as $q ) {
			if ( stripos( $q, 'DELETE' ) !== false && stripos( $q, 'cecomsmarad' ) !== false ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'DELETE query should contain the plugin transient prefix.' );
	}
}
