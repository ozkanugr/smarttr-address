<?php
/**
 * Unit tests for Cecomsmarad_Province model.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Province extends TestCase {

	/**
	 * Reset wpdb mock before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->reset();
		wp_cache_flush();
	}

	/**
	 * get_all() returns an array of province objects ordered by name.
	 */
	public function test_get_all_returns_provinces(): void {
		global $wpdb;

		$wpdb->next_results = array(
			(object) array( 'id' => '1', 'code' => 'TR01', 'name' => 'Adana' ),
			(object) array( 'id' => '34', 'code' => 'TR34', 'name' => 'İstanbul' ),
			(object) array( 'id' => '6', 'code' => 'TR06', 'name' => 'Ankara' ),
		);

		$provinces = Cecomsmarad_Province::get_all();

		$this->assertCount( 3, $provinces );
		$this->assertSame( 'TR01', $provinces[0]->code );
		$this->assertSame( 'Adana', $provinces[0]->name );
		$this->assertSame( 'İstanbul', $provinces[1]->name );
	}

	/**
	 * get_all() returns empty array when table is empty.
	 */
	public function test_get_all_returns_empty_array_when_no_data(): void {
		global $wpdb;
		$wpdb->next_results = array();

		$provinces = Cecomsmarad_Province::get_all();

		$this->assertIsArray( $provinces );
		$this->assertEmpty( $provinces );
	}

	/**
	 * get_all() issues correct SQL query.
	 */
	public function test_get_all_query_uses_correct_table_and_order(): void {
		global $wpdb;
		$wpdb->next_results = array();

		Cecomsmarad_Province::get_all();

		$this->assertCount( 1, $wpdb->queries );
		$this->assertStringContainsString( 'wp_cecomsmarad_provinces', $wpdb->queries[0] );
		$this->assertStringContainsString( 'ORDER BY name ASC', $wpdb->queries[0] );
	}

	/**
	 * get_by_code() returns a province object for valid code.
	 */
	public function test_get_by_code_returns_province(): void {
		global $wpdb;

		$wpdb->next_row = (object) array( 'id' => '34', 'code' => 'TR34', 'name' => 'İstanbul' );

		$province = Cecomsmarad_Province::get_by_code( 'TR34' );

		$this->assertNotNull( $province );
		$this->assertSame( 'TR34', $province->code );
		$this->assertSame( 'İstanbul', $province->name );
	}

	/**
	 * get_by_code() returns null for unknown code.
	 */
	public function test_get_by_code_returns_null_for_invalid_code(): void {
		global $wpdb;
		$wpdb->next_row = null;

		$province = Cecomsmarad_Province::get_by_code( 'TR99' );

		$this->assertNull( $province );
	}

	/**
	 * get_by_code() uses prepared statement with the province code.
	 */
	public function test_get_by_code_query_uses_prepare(): void {
		global $wpdb;
		$wpdb->next_row = null;

		Cecomsmarad_Province::get_by_code( 'TR06' );

		$this->assertCount( 1, $wpdb->queries );
		$this->assertStringContainsString( "'TR06'", $wpdb->queries[0] );
		$this->assertStringContainsString( 'LIMIT 1', $wpdb->queries[0] );
	}

	/**
	 * get_by_code() handles empty string code.
	 */
	public function test_get_by_code_with_empty_string(): void {
		global $wpdb;
		$wpdb->next_row = null;

		$province = Cecomsmarad_Province::get_by_code( '' );

		$this->assertNull( $province );
	}
}
