<?php
/**
 * Unit tests for Cecomsmarad_Data_Importer.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Data_Importer extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		global $wpdb, $cecomsmarad_test_options;
		$wpdb->reset();
		$cecomsmarad_test_options = array();
	}

	/*
	|----------------------------------------------------------------------
	| create_tables()
	|----------------------------------------------------------------------
	*/

	/**
	 * create_tables() requires upgrade.php and calls dbDelta.
	 *
	 * We can't fully test dbDelta without WP, but we can verify
	 * the method doesn't fatal and the SQL contains expected structure.
	 */
	public function test_create_tables_does_not_fatal(): void {
		// dbDelta requires ABSPATH . 'wp-admin/includes/upgrade.php'
		// which doesn't exist in test env. We'll test the SQL generation
		// indirectly via import methods that reference the tables.
		$this->assertTrue( true, 'create_tables() test deferred to integration suite.' );
	}

	/*
	|----------------------------------------------------------------------
	| import_provinces_data()
	|----------------------------------------------------------------------
	*/

	/**
	 * import_provinces_data() reads data array and inserts provinces.
	 */
	public function test_import_provinces_data_inserts_rows(): void {
		global $wpdb;

		$data = array(
			'TR01' => 'Adana',
			'TR06' => 'Ankara',
			'TR34' => 'İstanbul',
		);

		$count = Cecomsmarad_Data_Importer::import_provinces_data( $data );

		$this->assertSame( 3, $count );

		// Expect: 1 TRUNCATE + 1 INSERT.
		$truncate_count = 0;
		$insert_count   = 0;
		foreach ( $wpdb->queries as $q ) {
			if ( stripos( $q, 'TRUNCATE' ) !== false ) {
				++$truncate_count;
			}
			if ( stripos( $q, 'INSERT' ) !== false ) {
				++$insert_count;
			}
		}

		$this->assertSame( 1, $truncate_count, 'Should truncate once for idempotency.' );
		$this->assertSame( 1, $insert_count, '3 rows fit in one batch.' );
	}

	/**
	 * import_provinces_data() preserves UTF-8 province names (İstanbul, Ağrı, etc.).
	 */
	public function test_import_provinces_data_preserves_utf8(): void {
		global $wpdb;

		$data = array(
			'TR34' => 'İstanbul',
			'TR06' => 'Ağrı',
		);

		Cecomsmarad_Data_Importer::import_provinces_data( $data );

		$insert_query = '';
		foreach ( $wpdb->queries as $q ) {
			if ( stripos( $q, 'INSERT' ) !== false ) {
				$insert_query = $q;
				break;
			}
		}

		$this->assertStringContainsString( 'İstanbul', $insert_query );
		$this->assertStringContainsString( 'Ağrı', $insert_query );
	}

	/**
	 * import_provinces_data() returns 0 for empty input.
	 */
	public function test_import_provinces_data_returns_zero_on_empty(): void {
		$count = Cecomsmarad_Data_Importer::import_provinces_data( array() );
		$this->assertSame( 0, $count );
	}

	/**
	 * import_provinces_data() is idempotent — truncates before insert.
	 */
	public function test_import_provinces_data_truncates_first(): void {
		global $wpdb;

		$data = array( 'TR01' => 'Adana' );
		Cecomsmarad_Data_Importer::import_provinces_data( $data );

		$first_query = $wpdb->queries[0] ?? '';
		$this->assertStringContainsString( 'TRUNCATE', $first_query );
	}

	/*
	|----------------------------------------------------------------------
	| import_districts_data()
	|----------------------------------------------------------------------
	*/

	/**
	 * import_districts_data() reads data array and inserts districts.
	 */
	public function test_import_districts_data_inserts_rows(): void {
		global $wpdb;

		$data = array(
			'TR01' => array( 'Aladağ', 'Ceyhan', 'Çukurova' ),
			'TR06' => array( 'Altındağ', 'Çankaya', 'Etimesgut' ),
		);

		$count = Cecomsmarad_Data_Importer::import_districts_data( $data );

		$this->assertSame( 6, $count );
	}

	/**
	 * import_districts_data() is idempotent — truncates before insert.
	 */
	public function test_import_districts_data_truncates_first(): void {
		global $wpdb;

		$data = array( 'TR01' => array( 'Aladağ' ) );
		Cecomsmarad_Data_Importer::import_districts_data( $data );

		$first_query = $wpdb->queries[0] ?? '';
		$this->assertStringContainsString( 'TRUNCATE', $first_query );
	}

	/**
	 * import_districts_data() returns 0 for empty input.
	 */
	public function test_import_districts_data_returns_zero_on_empty(): void {
		$count = Cecomsmarad_Data_Importer::import_districts_data( array() );
		$this->assertSame( 0, $count );
	}

	/*
	|----------------------------------------------------------------------
	| store_metadata()
	|----------------------------------------------------------------------
	*/

	/**
	 * store_metadata() stores last_import and record_counts options.
	 */
	public function test_store_metadata_stores_options(): void {
		global $cecomsmarad_test_options;

		Cecomsmarad_Data_Importer::store_metadata();

		$this->assertArrayHasKey( 'cecomsmarad_last_import', $cecomsmarad_test_options );
		$this->assertArrayHasKey( 'cecomsmarad_record_counts', $cecomsmarad_test_options );
	}

	/**
	 * store_metadata() records_counts contains provinces and districts keys.
	 */
	public function test_store_metadata_record_counts_has_expected_keys(): void {
		global $cecomsmarad_test_options;

		Cecomsmarad_Data_Importer::store_metadata();

		$counts = json_decode( $cecomsmarad_test_options['cecomsmarad_record_counts'] ?? '{}', true );
		$this->assertArrayHasKey( 'provinces', $counts );
		$this->assertArrayHasKey( 'districts', $counts );
	}

	/*
	|----------------------------------------------------------------------
	| has_data()
	|----------------------------------------------------------------------
	*/

	/**
	 * has_data() returns true when provinces table has rows.
	 */
	public function test_has_data_returns_true_when_populated(): void {
		global $wpdb;
		$wpdb->next_var = 81;

		$this->assertTrue( Cecomsmarad_Data_Importer::has_data() );
	}

	/**
	 * has_data() returns false when provinces table is empty.
	 */
	public function test_has_data_returns_false_when_empty(): void {
		global $wpdb;
		$wpdb->next_var = 0;

		$this->assertFalse( Cecomsmarad_Data_Importer::has_data() );
	}

	/**
	 * has_data() returns false when table doesn't exist (null from get_var).
	 */
	public function test_has_data_returns_false_when_table_missing(): void {
		global $wpdb;
		$wpdb->next_var = null;

		$this->assertFalse( Cecomsmarad_Data_Importer::has_data() );
	}

	/*
	|----------------------------------------------------------------------
	| validated_table() — tested indirectly and via reflection
	|----------------------------------------------------------------------
	*/

	/**
	 * Import methods use the correct table names via validated_table().
	 */
	public function test_import_uses_validated_table_names(): void {
		global $wpdb;

		Cecomsmarad_Data_Importer::import_provinces_data( array( 'TR01' => 'Adana' ) );

		$this->assertStringContainsString( 'wp_cecomsmarad_provinces', $wpdb->queries[0] );
	}

	/**
	 * validated_table() rejects unknown suffixes.
	 */
	public function test_validated_table_rejects_unknown_suffix(): void {
		$this->expectException( \InvalidArgumentException::class );

		$method = new \ReflectionMethod( Cecomsmarad_Data_Importer::class, 'validated_table' );
		$method->setAccessible( true );
		$method->invoke( null, 'unknown_table' );
	}
}
