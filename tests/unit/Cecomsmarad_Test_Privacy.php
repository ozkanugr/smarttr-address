<?php
/**
 * Unit tests for Cecomsmarad_Privacy.
 *
 * Covers: register_hooks(), register_exporters(), register_erasers(),
 *         export_customer_data(), erase_customer_data()
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Class Cecomsmarad_Test_Privacy
 */
class Cecomsmarad_Test_Privacy extends TestCase {

	/**
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		global $cecomsmarad_test_hooks, $cecomsmarad_test_wc_orders_list;
		$cecomsmarad_test_hooks          = array();
		$cecomsmarad_test_wc_orders_list = array();
	}

	/**
	 * @return void
	 */
	protected function tear_down(): void {
		global $cecomsmarad_test_hooks, $cecomsmarad_test_wc_orders_list;
		$cecomsmarad_test_hooks          = array();
		$cecomsmarad_test_wc_orders_list = array();

		parent::tear_down();
	}

	// ──────────────────────────────────────────────────────────────
	// register_hooks()
	// ──────────────────────────────────────────────────────────────

	/**
	 * register_hooks() registers the personal data exporter filter.
	 */
	public function test_register_hooks_registers_exporter_filter(): void {
		global $cecomsmarad_test_hooks;

		Cecomsmarad_Privacy::register_hooks();

		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );
		$this->assertContains( 'wp_privacy_personal_data_exporters', $tags );
	}

	/**
	 * register_hooks() registers the personal data eraser filter.
	 */
	public function test_register_hooks_registers_eraser_filter(): void {
		global $cecomsmarad_test_hooks;

		Cecomsmarad_Privacy::register_hooks();

		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );
		$this->assertContains( 'wp_privacy_personal_data_erasers', $tags );
	}

	/**
	 * register_hooks() registers the admin_init action for policy content.
	 */
	public function test_register_hooks_registers_admin_init_action(): void {
		global $cecomsmarad_test_hooks;

		Cecomsmarad_Privacy::register_hooks();

		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );
		$this->assertContains( 'admin_init', $tags );
	}

	// ──────────────────────────────────────────────────────────────
	// register_exporters()
	// ──────────────────────────────────────────────────────────────

	/**
	 * register_exporters() adds the 'smarttr-address' key to the exporters array.
	 */
	public function test_register_exporters_adds_smarttr_address_key(): void {
		$result = Cecomsmarad_Privacy::register_exporters( array() );

		$this->assertArrayHasKey( 'smarttr-address', $result );
	}

	/**
	 * register_exporters() preserves existing exporter entries.
	 */
	public function test_register_exporters_preserves_existing_entries(): void {
		$existing = array( 'other-plugin' => array( 'exporter_friendly_name' => 'Other Plugin' ) );

		$result = Cecomsmarad_Privacy::register_exporters( $existing );

		$this->assertArrayHasKey( 'other-plugin', $result );
		$this->assertArrayHasKey( 'smarttr-address', $result );
	}

	/**
	 * register_exporters() entry contains a callback key.
	 */
	public function test_register_exporters_entry_has_callback(): void {
		$result = Cecomsmarad_Privacy::register_exporters( array() );

		$this->assertArrayHasKey( 'callback', $result['smarttr-address'] );
		$this->assertIsCallable( $result['smarttr-address']['callback'] );
	}

	/**
	 * register_exporters() entry contains a friendly name.
	 */
	public function test_register_exporters_entry_has_friendly_name(): void {
		$result = Cecomsmarad_Privacy::register_exporters( array() );

		$this->assertArrayHasKey( 'exporter_friendly_name', $result['smarttr-address'] );
		$this->assertNotEmpty( $result['smarttr-address']['exporter_friendly_name'] );
	}

	// ──────────────────────────────────────────────────────────────
	// register_erasers()
	// ──────────────────────────────────────────────────────────────

	/**
	 * register_erasers() adds the 'smarttr-address' key to the erasers array.
	 */
	public function test_register_erasers_adds_smarttr_address_key(): void {
		$result = Cecomsmarad_Privacy::register_erasers( array() );

		$this->assertArrayHasKey( 'smarttr-address', $result );
	}

	/**
	 * register_erasers() preserves existing eraser entries.
	 */
	public function test_register_erasers_preserves_existing_entries(): void {
		$existing = array( 'woocommerce' => array( 'eraser_friendly_name' => 'WooCommerce' ) );

		$result = Cecomsmarad_Privacy::register_erasers( $existing );

		$this->assertArrayHasKey( 'woocommerce', $result );
		$this->assertArrayHasKey( 'smarttr-address', $result );
	}

	/**
	 * register_erasers() entry contains a callback key.
	 */
	public function test_register_erasers_entry_has_callback(): void {
		$result = Cecomsmarad_Privacy::register_erasers( array() );

		$this->assertArrayHasKey( 'callback', $result['smarttr-address'] );
		$this->assertIsCallable( $result['smarttr-address']['callback'] );
	}

	/**
	 * register_erasers() entry contains a friendly name.
	 */
	public function test_register_erasers_entry_has_friendly_name(): void {
		$result = Cecomsmarad_Privacy::register_erasers( array() );

		$this->assertArrayHasKey( 'eraser_friendly_name', $result['smarttr-address'] );
		$this->assertNotEmpty( $result['smarttr-address']['eraser_friendly_name'] );
	}

	// ──────────────────────────────────────────────────────────────
	// export_customer_data()
	// ──────────────────────────────────────────────────────────────

	/**
	 * export_customer_data() returns the expected structure keys.
	 */
	public function test_export_customer_data_returns_expected_keys(): void {
		$result = Cecomsmarad_Privacy::export_customer_data( 'test@example.com' );

		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
	}

	/**
	 * export_customer_data() returns empty data and done=true when no orders found.
	 */
	public function test_export_customer_data_returns_empty_when_no_orders(): void {
		// wc_get_orders stub returns [] by default.
		$result = Cecomsmarad_Privacy::export_customer_data( 'no-orders@example.com' );

		$this->assertIsArray( $result['data'] );
		$this->assertEmpty( $result['data'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * export_customer_data() exports billing province and district for an order with meta.
	 */
	public function test_export_customer_data_exports_province_and_district(): void {
		global $cecomsmarad_test_wc_orders_list;

		$order = new WC_Order( 501 );
		$order->update_meta_data( '_billing_cecomsmarad_province_name', 'İstanbul' );
		$order->update_meta_data( '_billing_cecomsmarad_district_name', 'Kadıköy' );
		$cecomsmarad_test_wc_orders_list = array( $order );

		$result = Cecomsmarad_Privacy::export_customer_data( 'test@example.com' );

		$this->assertNotEmpty( $result['data'] );

		// Collect all exported values.
		$values = array();
		foreach ( $result['data'] as $group ) {
			foreach ( $group['data'] as $item ) {
				$values[] = $item['value'];
			}
		}

		$this->assertContains( 'İstanbul', $values );
		$this->assertContains( 'Kadıköy', $values );
	}

	/**
	 * export_customer_data() marks done=true when fewer than 20 orders returned.
	 */
	public function test_export_customer_data_marks_done_for_small_result_set(): void {
		global $cecomsmarad_test_wc_orders_list;
		$cecomsmarad_test_wc_orders_list = array( new WC_Order( 600 ) );

		$result = Cecomsmarad_Privacy::export_customer_data( 'test@example.com' );

		$this->assertTrue( $result['done'] );
	}

	/**
	 * export_customer_data() omits orders that have no SmartTR meta.
	 */
	public function test_export_customer_data_skips_orders_without_smarttr_meta(): void {
		global $cecomsmarad_test_wc_orders_list;

		$order = new WC_Order( 502 );
		// No cecomsmarad meta set.
		$cecomsmarad_test_wc_orders_list = array( $order );

		$result = Cecomsmarad_Privacy::export_customer_data( 'test@example.com' );

		$this->assertEmpty( $result['data'] );
	}

	// ──────────────────────────────────────────────────────────────
	// erase_customer_data()
	// ──────────────────────────────────────────────────────────────

	/**
	 * erase_customer_data() returns the expected structure keys.
	 */
	public function test_erase_customer_data_returns_expected_keys(): void {
		$result = Cecomsmarad_Privacy::erase_customer_data( 'test@example.com' );

		$this->assertArrayHasKey( 'items_removed', $result );
		$this->assertArrayHasKey( 'items_retained', $result );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertArrayHasKey( 'done', $result );
	}

	/**
	 * erase_customer_data() reports items_removed=false when no orders.
	 */
	public function test_erase_customer_data_reports_nothing_removed_when_no_orders(): void {
		$result = Cecomsmarad_Privacy::erase_customer_data( 'noorders@example.com' );

		$this->assertFalse( $result['items_removed'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * erase_customer_data() removes billing meta and reports items_removed=true.
	 */
	public function test_erase_customer_data_removes_billing_meta(): void {
		global $cecomsmarad_test_wc_orders_list;

		$order = new WC_Order( 700 );
		$order->update_meta_data( '_billing_cecomsmarad_province_code', 'TR34' );
		$order->update_meta_data( '_billing_cecomsmarad_province_name', 'İstanbul' );
		$order->update_meta_data( '_billing_cecomsmarad_district_name', 'Kadıköy' );
		$cecomsmarad_test_wc_orders_list = array( $order );

		$result = Cecomsmarad_Privacy::erase_customer_data( 'test@example.com' );

		$this->assertTrue( $result['items_removed'] );
		$this->assertSame( '', $order->get_meta( '_billing_cecomsmarad_province_code' ) );
		$this->assertSame( '', $order->get_meta( '_billing_cecomsmarad_province_name' ) );
		$this->assertSame( '', $order->get_meta( '_billing_cecomsmarad_district_name' ) );
		$this->assertTrue( $order->save_called );
	}

	/**
	 * erase_customer_data() items_retained is always false for this plugin.
	 */
	public function test_erase_customer_data_items_retained_is_false(): void {
		$result = Cecomsmarad_Privacy::erase_customer_data( 'test@example.com' );

		$this->assertFalse( $result['items_retained'] );
	}

	/**
	 * erase_customer_data() messages is an empty array.
	 */
	public function test_erase_customer_data_messages_is_empty_array(): void {
		$result = Cecomsmarad_Privacy::erase_customer_data( 'test@example.com' );

		$this->assertIsArray( $result['messages'] );
		$this->assertEmpty( $result['messages'] );
	}
}
