<?php
/**
 * Unit tests for Cecomsmarad_Admin_Controller.
 *
 * Covers hook registration, the private static input-validation helpers
 * (via reflection), prepare_field_updates(), get_current_tab(), and
 * the get_db_health() static method.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Admin_Controller extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		global $wpdb, $cecomsmarad_test_hooks, $cecomsmarad_test_options,
			$cecomsmarad_test_current_user_can, $menu, $submenu;

		$wpdb->reset();
		wp_cache_flush();
		$cecomsmarad_test_hooks           = array();
		$cecomsmarad_test_options         = array();
		$cecomsmarad_test_current_user_can = true;
		$menu                             = array();
		$submenu                          = array();
		$_GET                             = array();
		$_POST                            = array();
	}

	protected function tear_down(): void {
		global $cecomsmarad_test_hooks, $cecomsmarad_test_options,
			$cecomsmarad_test_current_user_can, $menu, $submenu;

		$cecomsmarad_test_hooks           = array();
		$cecomsmarad_test_options         = array();
		$cecomsmarad_test_current_user_can = true;
		$menu                             = array();
		$submenu                          = array();
		$_GET                             = array();
		$_POST                            = array();

		parent::tear_down();
	}

	// ──────────────────────────────────────────────────────────────
	// Hook registration
	// ──────────────────────────────────────────────────────────────

	/**
	 * Constructor registers the expected admin action hooks.
	 */
	public function test_constructor_registers_admin_hooks(): void {
		global $cecomsmarad_test_hooks;

		new Cecomsmarad_Admin_Controller();

		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );

		$this->assertContains( 'admin_menu', $tags );
		$this->assertContains( 'admin_init', $tags );
		$this->assertContains( 'admin_enqueue_scripts', $tags );
	}

	/**
	 * Constructor registers the expected AJAX action hooks.
	 */
	public function test_constructor_registers_ajax_hooks(): void {
		global $cecomsmarad_test_hooks;

		new Cecomsmarad_Admin_Controller();

		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );

		$this->assertContains( 'wp_ajax_cecomsmarad_save_general', $tags );
		$this->assertContains( 'wp_ajax_cecomsmarad_reset_fields', $tags );
		$this->assertContains( 'wp_ajax_cecomsmarad_reimport_data', $tags );
		$this->assertContains( 'wp_ajax_cecomsmarad_submit_deactivation_feedback', $tags );
	}

	// ──────────────────────────────────────────────────────────────
	// valid_visibility() — private static, accessed via reflection
	// ──────────────────────────────────────────────────────────────

	/**
	 * Helper: invoke the private static valid_visibility() via reflection.
	 */
	private function call_valid_visibility( string $value ): string {
		$method = new \ReflectionMethod( Cecomsmarad_Admin_Controller::class, 'valid_visibility' );
		$method->setAccessible( true );
		return $method->invoke( null, $value );
	}

	/**
	 * 'visible' is a recognised value and passes through unchanged.
	 */
	public function test_valid_visibility_returns_visible(): void {
		$this->assertSame( 'visible', $this->call_valid_visibility( 'visible' ) );
	}

	/**
	 * 'hidden' is a recognised value and passes through unchanged.
	 */
	public function test_valid_visibility_returns_hidden(): void {
		$this->assertSame( 'hidden', $this->call_valid_visibility( 'hidden' ) );
	}

	/**
	 * 'unset' is a recognised value and passes through unchanged.
	 */
	public function test_valid_visibility_returns_unset(): void {
		$this->assertSame( 'unset', $this->call_valid_visibility( 'unset' ) );
	}

	/**
	 * An unrecognised value defaults to 'visible'.
	 */
	public function test_valid_visibility_defaults_to_visible_for_unknown_value(): void {
		$this->assertSame( 'visible', $this->call_valid_visibility( 'invisible' ) );
	}

	/**
	 * Empty string defaults to 'visible'.
	 */
	public function test_valid_visibility_defaults_to_visible_for_empty_string(): void {
		$this->assertSame( 'visible', $this->call_valid_visibility( '' ) );
	}

	// ──────────────────────────────────────────────────────────────
	// valid_field_type() — private static, accessed via reflection
	// ──────────────────────────────────────────────────────────────

	/**
	 * Helper: invoke the private static valid_field_type() via reflection.
	 */
	private function call_valid_field_type( string $value ): string {
		$method = new \ReflectionMethod( Cecomsmarad_Admin_Controller::class, 'valid_field_type' );
		$method->setAccessible( true );
		return $method->invoke( null, $value );
	}

	/**
	 * 'text' is a recognised type and passes through.
	 */
	public function test_valid_field_type_returns_text(): void {
		$this->assertSame( 'text', $this->call_valid_field_type( 'text' ) );
	}

	/**
	 * 'email' is a recognised type and passes through.
	 */
	public function test_valid_field_type_returns_email(): void {
		$this->assertSame( 'email', $this->call_valid_field_type( 'email' ) );
	}

	/**
	 * 'select' is a recognised type and passes through.
	 */
	public function test_valid_field_type_returns_select(): void {
		$this->assertSame( 'select', $this->call_valid_field_type( 'select' ) );
	}

	/**
	 * 'file' is a recognised type and passes through.
	 */
	public function test_valid_field_type_returns_file(): void {
		$this->assertSame( 'file', $this->call_valid_field_type( 'file' ) );
	}

	/**
	 * An unrecognised type defaults to 'text'.
	 */
	public function test_valid_field_type_defaults_to_text_for_unknown(): void {
		$this->assertSame( 'text', $this->call_valid_field_type( 'dropdown' ) );
	}

	/**
	 * Empty string defaults to 'text'.
	 */
	public function test_valid_field_type_defaults_to_text_for_empty(): void {
		$this->assertSame( 'text', $this->call_valid_field_type( '' ) );
	}

	// ──────────────────────────────────────────────────────────────
	// prepare_field_updates() — private static, accessed via reflection
	// ──────────────────────────────────────────────────────────────

	/**
	 * Helper: invoke private static prepare_field_updates() via reflection.
	 */
	private function call_prepare_field_updates( array $submitted ): array {
		$method = new \ReflectionMethod( Cecomsmarad_Admin_Controller::class, 'prepare_field_updates' );
		$method->setAccessible( true );
		return $method->invoke( null, $submitted );
	}

	/**
	 * Sanitized props include all required keys.
	 */
	public function test_prepare_field_updates_includes_all_keys(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city' => array(
				'type'  => 'text',
				'label' => 'District',
			),
		) );

		$expected_keys = array(
			'type', 'label', 'description', 'placeholder', 'class',
			'required', 'clear', 'label_class', 'options', 'priority',
			'visibility', 'allowed_extensions',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $result['billing_city'], "Missing key: {$key}" );
		}
	}

	/**
	 * 'required' is true when the key is present in the submitted props.
	 */
	public function test_prepare_field_updates_required_is_true_when_present(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city' => array(
				'type'     => 'text',
				'required' => '1',
			),
		) );

		$this->assertTrue( $result['billing_city']['required'] );
	}

	/**
	 * 'required' is false when the key is absent from the submitted props.
	 */
	public function test_prepare_field_updates_required_is_false_when_absent(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city' => array( 'type' => 'text' ),
		) );

		$this->assertFalse( $result['billing_city']['required'] );
	}

	/**
	 * 'priority' defaults to 100 when not supplied.
	 */
	public function test_prepare_field_updates_priority_defaults_to_100(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city' => array( 'type' => 'text' ),
		) );

		$this->assertSame( 100, $result['billing_city']['priority'] );
	}

	/**
	 * Non-array props entries are skipped.
	 */
	public function test_prepare_field_updates_skips_non_array_entries(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city'  => array( 'type' => 'text' ),
			'billing_state' => 'not-an-array',
		) );

		$this->assertArrayHasKey( 'billing_city', $result );
		$this->assertArrayNotHasKey( 'billing_state', $result );
	}

	/**
	 * 'clear' is true for select type with the clear flag set.
	 */
	public function test_prepare_field_updates_clear_is_true_for_select_type(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city' => array(
				'type'  => 'select',
				'clear' => '1',
			),
		) );

		$this->assertTrue( $result['billing_city']['clear'] );
	}

	/**
	 * 'clear' is false for non-select, non-eligible field keys even when submitted.
	 */
	public function test_prepare_field_updates_clear_is_false_for_text_type(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_first_name' => array(
				'type'  => 'text',
				'clear' => '1',
			),
		) );

		$this->assertFalse( $result['billing_first_name']['clear'] );
	}

	/**
	 * Unknown visibility defaults to 'visible' after sanitization.
	 */
	public function test_prepare_field_updates_unknown_visibility_defaults_to_visible(): void {
		$result = $this->call_prepare_field_updates( array(
			'billing_city' => array(
				'type'       => 'text',
				'visibility' => 'stealth',
			),
		) );

		$this->assertSame( 'visible', $result['billing_city']['visibility'] );
	}

	// ──────────────────────────────────────────────────────────────
	// get_current_tab() — private, accessed via reflection
	// ──────────────────────────────────────────────────────────────

	/**
	 * Helper: invoke private get_current_tab() on a fresh controller instance.
	 */
	private function call_get_current_tab(): string {
		$controller = new Cecomsmarad_Admin_Controller();
		$method     = new \ReflectionMethod( Cecomsmarad_Admin_Controller::class, 'get_current_tab' );
		$method->setAccessible( true );
		return $method->invoke( $controller );
	}

	/**
	 * Defaults to 'general' when no tab is in the query string.
	 */
	public function test_get_current_tab_defaults_to_general(): void {
		$this->assertSame( 'general', $this->call_get_current_tab() );
	}

	/**
	 * Returns 'fields' when $_GET['tab'] is 'fields'.
	 */
	public function test_get_current_tab_returns_fields(): void {
		$_GET['tab'] = 'fields';
		$this->assertSame( 'fields', $this->call_get_current_tab() );
	}

	/**
	 * Returns 'data' when $_GET['tab'] is 'data'.
	 */
	public function test_get_current_tab_returns_data(): void {
		$_GET['tab'] = 'data';
		$this->assertSame( 'data', $this->call_get_current_tab() );
	}

	/**
	 * Falls back to 'general' for an unrecognised tab slug.
	 */
	public function test_get_current_tab_falls_back_to_general_for_unknown(): void {
		$_GET['tab'] = 'hacking_attempt';
		$this->assertSame( 'general', $this->call_get_current_tab() );
	}

	// ──────────────────────────────────────────────────────────────
	// get_db_health()
	// ──────────────────────────────────────────────────────────────

	/**
	 * get_db_health() returns an array with expected keys.
	 */
	public function test_get_db_health_returns_expected_keys(): void {
		global $wpdb;
		$wpdb->next_row = null; // Tables don't exist.

		$result = Cecomsmarad_Admin_Controller::get_db_health();

		$this->assertArrayHasKey( 'tables', $result );
		$this->assertArrayHasKey( 'total_size', $result );
		$this->assertArrayHasKey( 'charset', $result );
		$this->assertArrayHasKey( 'prefix', $result );
	}

	/**
	 * get_db_health() reports both table slots.
	 */
	public function test_get_db_health_reports_both_tables(): void {
		global $wpdb;
		$wpdb->next_row = null;

		$result = Cecomsmarad_Admin_Controller::get_db_health();

		$this->assertArrayHasKey( 'provinces', $result['tables'] );
		$this->assertArrayHasKey( 'districts', $result['tables'] );
	}

	/**
	 * Table entries are false when get_row() returns null.
	 */
	public function test_get_db_health_tables_false_when_not_found(): void {
		global $wpdb;
		$wpdb->next_row = null; // All consecutive calls return null.

		$result = Cecomsmarad_Admin_Controller::get_db_health();

		$this->assertFalse( $result['tables']['provinces'] );
		$this->assertFalse( $result['tables']['districts'] );
	}

	/**
	 * get_db_health() returns the wpdb prefix.
	 */
	public function test_get_db_health_returns_correct_prefix(): void {
		global $wpdb;
		$wpdb->next_row = null;

		$result = Cecomsmarad_Admin_Controller::get_db_health();

		$this->assertSame( 'wp_', $result['prefix'] );
	}
}
