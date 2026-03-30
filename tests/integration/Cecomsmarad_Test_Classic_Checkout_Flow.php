<?php
/**
 * Integration test — Classic checkout end-to-end flow (cecomsmarad-12.1).
 *
 * Tests the full cascade: Turkey selection → province dropdown → district
 * filter → order meta save.
 *
 * Requires a full WordPress + WooCommerce test environment (wp-env or
 * WP PHPUnit bootstrap). Run via:
 *   phpunit --testsuite integration
 *
 * @package CecomsmaradAddress
 * @group   integration
 * @group   checkout
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @requires function wc_get_order
 */
class Cecomsmarad_Test_Classic_Checkout_Flow extends TestCase {

	/**
	 * Province dropdown populates with all 81 Turkish provinces.
	 *
	 * Verifies that Cecomsmarad_Province::get_all() returns 81 rows after
	 * plugin activation and data import.
	 */
	public function test_provinces_populated_after_activation(): void {
		$provinces = Cecomsmarad_Province::get_all();

		$this->assertCount( 81, $provinces, 'All 81 Turkish provinces should be imported.' );

		// Verify alphabetical order.
		$names = array_column( $provinces, 'name' );
		$sorted = $names;
		sort( $sorted, SORT_LOCALE_STRING );
		$this->assertSame( $sorted, $names, 'Provinces should be ordered alphabetically.' );
	}

	/**
	 * Selecting a province returns correct districts.
	 *
	 * İstanbul (TR34) should have a known number of districts.
	 */
	public function test_district_filter_for_istanbul(): void {
		$districts = Cecomsmarad_District::get_by_province( 'TR34' );

		$this->assertNotEmpty( $districts, 'İstanbul should have districts.' );
		$this->assertGreaterThanOrEqual( 39, count( $districts ), 'İstanbul has at least 39 districts.' );

		$names = array_column( $districts, 'name' );
		$this->assertContains( 'Kadıköy', $names );
		$this->assertContains( 'Beşiktaş', $names );
	}

	/**
	 * Order validation rejects TR order with missing address fields.
	 */
	public function test_validation_rejects_incomplete_tr_order(): void {
		$controller = new Cecomsmarad_Order_Controller();
		$errors     = new WP_Error();

		$data = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => '',   // Missing district.
			'billing_address_2' => '',   // Missing address_2.
		);

		$controller->validate_turkish_fields( $data, $errors );

		$this->assertTrue( $errors->has_errors() );
		$codes = $errors->get_error_codes();
		$this->assertContains( 'cecomsmarad_billing_district', $codes );
		$this->assertContains( 'cecomsmarad_billing_neighborhood', $codes );
	}

	/**
	 * Order validation passes TR order with all address fields.
	 */
	public function test_validation_passes_complete_tr_order(): void {
		$controller = new Cecomsmarad_Order_Controller();
		$errors     = new WP_Error();

		$data = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => 'Caferağa Mah.',
		);

		$controller->validate_turkish_fields( $data, $errors );

		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * Non-TR orders skip all Turkish validation.
	 */
	public function test_validation_skips_non_tr_orders(): void {
		$controller = new Cecomsmarad_Order_Controller();
		$errors     = new WP_Error();

		$data = array(
			'billing_country'   => 'DE',
			'billing_state'     => '',
			'billing_city'      => '',
			'billing_address_2' => '',
		);

		$controller->validate_turkish_fields( $data, $errors );

		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * Full cascade: province → district → order meta.
	 *
	 * End-to-end test simulating the complete checkout flow for a TR order.
	 */
	public function test_full_checkout_cascade_saves_order_meta(): void {
		// Step 1: Select province.
		$province = Cecomsmarad_Province::get_by_code( 'TR34' );
		$this->assertNotNull( $province );
		$this->assertSame( 'İstanbul', $province->name );

		// Step 2: Filter districts for the province.
		$districts = Cecomsmarad_District::get_by_province( 'TR34' );
		$kadikoy   = null;
		foreach ( $districts as $d ) {
			if ( 'Kadıköy' === $d->name ) {
				$kadikoy = $d;
				break;
			}
		}
		$this->assertNotNull( $kadikoy );

		// Step 3: Simulate order creation with province and district.
		$controller = new Cecomsmarad_Order_Controller();
		$order      = wc_create_order();
		$order_id   = $order->get_id();

		$posted_data = array(
			'billing_country' => 'TR',
			'billing_state'   => 'TR34',
			'billing_city'    => 'Kadıköy',
		);

		$controller->save_order_meta( $order_id, $posted_data );

		// Step 4: Verify order meta (ADR-004: both code AND name stored).
		$saved_order = wc_get_order( $order_id );
		$this->assertSame( 'TR34', $saved_order->get_meta( '_billing_cecomsmarad_province_code' ) );
		$this->assertSame( 'İstanbul', $saved_order->get_meta( '_billing_cecomsmarad_province_name' ) );
		$this->assertSame( 'Kadıköy', $saved_order->get_meta( '_billing_cecomsmarad_district_name' ) );

		// Step 5: Verify address formatting.
		$address = array(
			'state' => 'TR34',
			'city'  => 'Kadıköy',
		);

		$formatted = $controller->format_billing_address( $address, $saved_order );
		$this->assertSame( 'İstanbul', $formatted['state'] );
		$this->assertSame( 'Kadıköy', $formatted['city'] );
	}

	/**
	 * Data import is idempotent — second import produces same record counts.
	 */
	public function test_data_import_idempotency(): void {
		// Get counts before.
		$provinces_before = count( Cecomsmarad_Province::get_all() );

		// Reimport.
		Cecomsmarad_Data_Importer::import_all();

		// Counts should be identical.
		$provinces_after = count( Cecomsmarad_Province::get_all() );
		$this->assertSame( $provinces_before, $provinces_after );
	}
}
