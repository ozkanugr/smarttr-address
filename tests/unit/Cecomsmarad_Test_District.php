<?php
/**
 * Unit tests for Cecomsmarad_District model.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_District extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->reset();
		wp_cache_flush();
	}

	/**
	 * get_by_province() returns districts for a valid province code.
	 */
	public function test_get_by_province_returns_districts(): void {
		global $wpdb;

		$wpdb->next_results = array(
			(object) array( 'id' => '1', 'name' => 'Adalar' ),
			(object) array( 'id' => '2', 'name' => 'Bakırköy' ),
			(object) array( 'id' => '3', 'name' => 'Beşiktaş' ),
		);

		$districts = Cecomsmarad_District::get_by_province( 'TR34' );

		$this->assertCount( 3, $districts );
		$this->assertSame( 'Adalar', $districts[0]->name );
		$this->assertSame( 'Beşiktaş', $districts[2]->name );
	}

	/**
	 * get_by_province() returns empty array when no districts exist.
	 */
	public function test_get_by_province_returns_empty_for_unknown_code(): void {
		global $wpdb;
		$wpdb->next_results = array();

		$districts = Cecomsmarad_District::get_by_province( 'TR99' );

		$this->assertIsArray( $districts );
		$this->assertEmpty( $districts );
	}

	/**
	 * get_by_province() query uses prepared statement.
	 */
	public function test_get_by_province_query_is_prepared(): void {
		global $wpdb;
		$wpdb->next_results = array();

		Cecomsmarad_District::get_by_province( 'TR06' );

		$this->assertCount( 1, $wpdb->queries );
		$this->assertStringContainsString( "'TR06'", $wpdb->queries[0] );
		$this->assertStringContainsString( 'ORDER BY name ASC', $wpdb->queries[0] );
	}

	/**
	 * get_by_id() returns a district object with province_code.
	 */
	public function test_get_by_id_returns_district(): void {
		global $wpdb;

		$wpdb->next_row = (object) array(
			'id'            => '42',
			'name'          => 'Kadıköy',
			'province_code' => 'TR34',
		);

		$district = Cecomsmarad_District::get_by_id( 42 );

		$this->assertNotNull( $district );
		$this->assertSame( 'Kadıköy', $district->name );
		$this->assertSame( 'TR34', $district->province_code );
	}

	/**
	 * get_by_id() returns null for non-existent ID.
	 */
	public function test_get_by_id_returns_null_for_invalid_id(): void {
		global $wpdb;
		$wpdb->next_row = null;

		$district = Cecomsmarad_District::get_by_id( 999999 );

		$this->assertNull( $district );
	}

	/**
	 * get_by_id() uses prepared statement with integer parameter.
	 */
	public function test_get_by_id_query_uses_integer_param(): void {
		global $wpdb;
		$wpdb->next_row = null;

		Cecomsmarad_District::get_by_id( 5 );

		$this->assertCount( 1, $wpdb->queries );
		$this->assertStringContainsString( '5', $wpdb->queries[0] );
		$this->assertStringContainsString( 'LIMIT 1', $wpdb->queries[0] );
	}
}
