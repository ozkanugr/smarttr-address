<?php
/**
 * Unit tests for Cecomsmarad_Order_Controller.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Order_Controller extends TestCase {

	private Cecomsmarad_Order_Controller $controller;

	protected function set_up(): void {
		parent::set_up();
		global $wpdb, $cecomsmarad_test_hooks, $cecomsmarad_test_orders;
		$wpdb->reset();
		wp_cache_flush();
		$cecomsmarad_test_hooks  = array();
		$cecomsmarad_test_orders = array();

		$this->controller = new Cecomsmarad_Order_Controller();
	}

	/*
	|----------------------------------------------------------------------
	| Hook registration
	|----------------------------------------------------------------------
	*/

	/**
	 * Constructor registers all expected hooks.
	 */
	public function test_constructor_registers_hooks(): void {
		global $cecomsmarad_test_hooks;

		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );

		$this->assertContains( 'woocommerce_after_checkout_validation', $tags );
		$this->assertContains( 'woocommerce_checkout_update_order_meta', $tags );
		$this->assertContains( 'woocommerce_order_formatted_billing_address', $tags );
		$this->assertContains( 'woocommerce_order_formatted_shipping_address', $tags );
	}

	/*
	|----------------------------------------------------------------------
	| validate_turkish_fields()
	|----------------------------------------------------------------------
	*/

	/**
	 * Non-TR billing country skips validation.
	 */
	public function test_validation_skips_non_tr_country(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country' => 'US',
			'billing_state'   => '',
			'billing_city'    => '',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * TR billing with empty province adds an error.
	 */
	public function test_validation_requires_province_for_tr(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country'   => 'TR',
			'billing_state'     => '',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => 'Caferağa',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$codes = $errors->get_error_codes();
		$this->assertContains( 'cecomsmarad_billing_province', $codes );
	}

	/**
	 * TR billing with empty district adds an error.
	 */
	public function test_validation_requires_district_for_tr(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => '',
			'billing_address_2' => 'Caferağa',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$codes = $errors->get_error_codes();
		$this->assertContains( 'cecomsmarad_billing_district', $codes );
	}

	/**
	 * TR billing with empty neighborhood adds an error.
	 */
	public function test_validation_requires_neighborhood_for_tr(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => '',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$codes = $errors->get_error_codes();
		$this->assertContains( 'cecomsmarad_billing_neighborhood', $codes );
	}

	/**
	 * TR billing neighborhood not validated when required=false in settings.
	 */
	public function test_validation_skips_neighborhood_when_not_required(): void {
		// Save settings with neighborhood required=false.
		$fields                                    = Cecomsmarad_Settings::get_default_fields();
		$fields['billing_address_2']['required']   = false;
		$fields['shipping_address_2']['required']  = false;
		update_option( 'cecomsmarad_field_settings', $fields );

		$errors = new WP_Error();
		$data   = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => '',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$codes = $errors->get_error_codes();
		$this->assertNotContains( 'cecomsmarad_billing_neighborhood', $codes );

		// Restore defaults.
		delete_option( 'cecomsmarad_field_settings' );
	}

	/**
	 * TR billing with all fields filled passes validation.
	 */
	public function test_validation_passes_with_all_fields(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => 'Caferağa Mah.',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * Shipping validation fires when ship_to_different_address is set.
	 */
	public function test_validation_includes_shipping_when_different(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country'           => 'TR',
			'billing_state'             => 'TR34',
			'billing_city'              => 'Kadıköy',
			'billing_address_2'         => 'Caferağa Mah.',
			'ship_to_different_address' => '1',
			'shipping_country'          => 'TR',
			'shipping_state'            => '',
			'shipping_city'             => '',
			'shipping_address_2'        => '',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$codes = $errors->get_error_codes();
		$this->assertContains( 'cecomsmarad_shipping_province', $codes );
		$this->assertContains( 'cecomsmarad_shipping_district', $codes );
		$this->assertContains( 'cecomsmarad_shipping_neighborhood', $codes );
	}

	/**
	 * Shipping validation is skipped when not shipping to different address.
	 */
	public function test_validation_skips_shipping_when_same(): void {
		$errors = new WP_Error();
		$data   = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => 'Caferağa Mah.',
			'shipping_country'  => 'TR',
			'shipping_state'    => '',
		);

		$this->controller->validate_turkish_fields( $data, $errors );

		$codes = $errors->get_error_codes();
		$this->assertNotContains( 'cecomsmarad_shipping_province', $codes );
	}

	/*
	|----------------------------------------------------------------------
	| save_order_meta()
	|----------------------------------------------------------------------
	*/

	/**
	 * save_order_meta() stores 5 meta keys for billing TR order.
	 */
	public function test_save_order_meta_stores_billing_meta(): void {
		global $wpdb, $cecomsmarad_test_orders;

		$order = new WC_Order( 100 );
		$cecomsmarad_test_orders[100] = $order;

		// Province lookup: return İstanbul for TR34.
		$wpdb->next_row = (object) array(
			'id'   => '34',
			'code' => 'TR34',
			'name' => 'İstanbul',
		);

		$data = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR34',
			'billing_city'      => 'Kadıköy',
			'billing_address_2' => 'Caferağa Mah.',
			'billing_postcode'  => '34710',
		);

		$this->controller->save_order_meta( 100, $data );

		$meta = $order->get_all_meta();

		$this->assertSame( 'TR34', $meta['_billing_cecomsmarad_province_code'] );
		$this->assertSame( 'İstanbul', $meta['_billing_cecomsmarad_province_name'] );
		$this->assertSame( 'Kadıköy', $meta['_billing_cecomsmarad_district_name'] );
		$this->assertSame( 'Caferağa Mah.', $meta['_billing_cecomsmarad_neighborhood_name'] );
		$this->assertSame( '34710', $meta['_billing_cecomsmarad_post_code'] );
		$this->assertTrue( $order->save_called );
	}

	/**
	 * save_order_meta() stores both code AND name (ADR-004).
	 */
	public function test_save_order_meta_stores_code_and_name(): void {
		global $wpdb, $cecomsmarad_test_orders;

		$order = new WC_Order( 101 );
		$cecomsmarad_test_orders[101] = $order;

		$wpdb->next_row = (object) array(
			'id'   => '6',
			'code' => 'TR06',
			'name' => 'Ankara',
		);

		$data = array(
			'billing_country'   => 'TR',
			'billing_state'     => 'TR06',
			'billing_city'      => 'Çankaya',
			'billing_address_2' => 'Kavaklıdere Mah.',
			'billing_postcode'  => '06690',
		);

		$this->controller->save_order_meta( 101, $data );

		$this->assertSame( 'TR06', $order->get_meta( '_billing_cecomsmarad_province_code' ) );
		$this->assertSame( 'Ankara', $order->get_meta( '_billing_cecomsmarad_province_name' ) );
	}

	/**
	 * save_order_meta() skips non-TR orders.
	 */
	public function test_save_order_meta_skips_non_tr(): void {
		global $cecomsmarad_test_orders;

		$order = new WC_Order( 102 );
		$cecomsmarad_test_orders[102] = $order;

		$data = array(
			'billing_country' => 'US',
			'billing_state'   => 'CA',
			'billing_city'    => 'Los Angeles',
		);

		$this->controller->save_order_meta( 102, $data );

		$meta = $order->get_all_meta();
		$this->assertEmpty( $meta );
		$this->assertTrue( $order->save_called );
	}

	/**
	 * save_order_meta() saves shipping when ship_to_different_address is set.
	 */
	public function test_save_order_meta_includes_shipping(): void {
		global $wpdb, $cecomsmarad_test_orders;

		$order = new WC_Order( 103 );
		$cecomsmarad_test_orders[103] = $order;

		// First get_row for billing province lookup.
		$wpdb->next_row = (object) array( 'id' => '34', 'code' => 'TR34', 'name' => 'İstanbul' );

		$data = array(
			'billing_country'           => 'TR',
			'billing_state'             => 'TR34',
			'billing_city'              => 'Kadıköy',
			'billing_address_2'         => 'Caferağa Mah.',
			'billing_postcode'          => '34710',
			'ship_to_different_address' => '1',
			'shipping_country'          => 'TR',
			'shipping_state'            => 'TR06',
			'shipping_city'             => 'Çankaya',
			'shipping_address_2'        => 'Kavaklıdere Mah.',
			'shipping_postcode'         => '06690',
		);

		$this->controller->save_order_meta( 103, $data );

		$this->assertSame( 'TR34', $order->get_meta( '_billing_cecomsmarad_province_code' ) );
		// Shipping province lookup: next_row was consumed by billing. Shipping will get null/empty.
		$this->assertSame( 'TR06', $order->get_meta( '_shipping_cecomsmarad_province_code' ) );
	}

	/**
	 * save_order_meta() handles invalid order ID.
	 */
	public function test_save_order_meta_handles_invalid_order(): void {
		// wc_get_order returns false for unknown ID.
		$this->controller->save_order_meta( 999, array() );

		// No exception — method returns early.
		$this->assertTrue( true );
	}

	/*
	|----------------------------------------------------------------------
	| format_billing_address() / format_shipping_address()
	|----------------------------------------------------------------------
	*/

	/**
	 * format_billing_address() replaces state code with province name.
	 */
	public function test_format_billing_address_injects_province_name(): void {
		$order = new WC_Order( 200 );
		$order->update_meta_data( '_billing_cecomsmarad_province_name', 'İstanbul' );
		$order->update_meta_data( '_billing_cecomsmarad_district_name', 'Kadıköy' );
		$order->update_meta_data( '_billing_cecomsmarad_neighborhood_name', 'Caferağa Mah.' );

		$address = array(
			'state'     => 'TR34',
			'city'      => 'Kadıköy',
			'address_2' => '',
		);

		$result = $this->controller->format_billing_address( $address, $order );

		$this->assertSame( 'İstanbul', $result['state'] );
		$this->assertSame( 'Kadıköy', $result['city'] );
		$this->assertSame( 'Caferağa Mah.', $result['address_2'] );
	}

	/**
	 * format_shipping_address() works the same as billing.
	 */
	public function test_format_shipping_address_injects_names(): void {
		$order = new WC_Order( 201 );
		$order->update_meta_data( '_shipping_cecomsmarad_province_name', 'Ankara' );
		$order->update_meta_data( '_shipping_cecomsmarad_district_name', 'Çankaya' );

		$address = array(
			'state' => 'TR06',
			'city'  => '',
		);

		$result = $this->controller->format_shipping_address( $address, $order );

		$this->assertSame( 'Ankara', $result['state'] );
		$this->assertSame( 'Çankaya', $result['city'] );
	}

	/**
	 * Format address preserves original values when no meta is set.
	 */
	public function test_format_address_preserves_original_when_no_meta(): void {
		$order = new WC_Order( 202 );

		$address = array(
			'state'     => 'CA',
			'city'      => 'Los Angeles',
			'address_2' => 'Apt 5',
		);

		$result = $this->controller->format_billing_address( $address, $order );

		$this->assertSame( 'CA', $result['state'] );
		$this->assertSame( 'Los Angeles', $result['city'] );
		$this->assertSame( 'Apt 5', $result['address_2'] );
	}
}
