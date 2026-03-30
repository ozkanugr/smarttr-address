<?php
/**
 * Unit tests for Cecomsmarad_Settings model.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Settings extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		global $cecomsmarad_test_options;
		$cecomsmarad_test_options = array();
	}

	/**
	 * get_fields() returns all 20 managed fields with defaults.
	 */
	public function test_get_fields_returns_all_defaults(): void {
		$fields = Cecomsmarad_Settings::get_fields();

		$this->assertCount( 20, $fields );
		$this->assertArrayHasKey( 'billing_first_name', $fields );
		$this->assertArrayHasKey( 'billing_last_name', $fields );
		$this->assertArrayHasKey( 'billing_company', $fields );
		$this->assertArrayHasKey( 'billing_country', $fields );
		$this->assertArrayHasKey( 'billing_address_1', $fields );
		$this->assertArrayHasKey( 'billing_address_2', $fields );
		$this->assertArrayHasKey( 'billing_city', $fields );
		$this->assertArrayHasKey( 'billing_state', $fields );
		$this->assertArrayHasKey( 'billing_postcode', $fields );
		$this->assertArrayHasKey( 'billing_email', $fields );
		$this->assertArrayHasKey( 'billing_phone', $fields );
		$this->assertArrayHasKey( 'shipping_first_name', $fields );
		$this->assertArrayHasKey( 'shipping_last_name', $fields );
		$this->assertArrayHasKey( 'shipping_company', $fields );
		$this->assertArrayHasKey( 'shipping_country', $fields );
		$this->assertArrayHasKey( 'shipping_address_1', $fields );
		$this->assertArrayHasKey( 'shipping_address_2', $fields );
		$this->assertArrayHasKey( 'shipping_city', $fields );
		$this->assertArrayHasKey( 'shipping_state', $fields );
		$this->assertArrayHasKey( 'shipping_postcode', $fields );
	}

	/**
	 * Each default field has all required properties.
	 */
	public function test_default_fields_have_all_properties(): void {
		$fields   = Cecomsmarad_Settings::get_fields();
		$expected = array( 'type', 'label', 'description', 'placeholder', 'priority', 'required', 'clear', 'class', 'label_class', 'options', 'visibility' );

		foreach ( $fields as $key => $props ) {
			foreach ( $expected as $prop ) {
				$this->assertArrayHasKey( $prop, $props, "Field {$key} missing property {$prop}" );
			}
		}
	}

	/**
	 * get_fields() merges saved options over defaults.
	 */
	public function test_get_fields_merges_saved_options(): void {
		global $cecomsmarad_test_options;

		$cecomsmarad_test_options['cecomsmarad_field_settings'] = array(
			'billing_state' => array(
				'label'    => 'Custom Label',
				'priority' => 200,
			),
		);

		$fields = Cecomsmarad_Settings::get_fields();

		$this->assertSame( 'Custom Label', $fields['billing_state']['label'] );
		$this->assertSame( 200, $fields['billing_state']['priority'] );
		// Other properties should remain as defaults.
		$this->assertTrue( $fields['billing_state']['required'] );
	}

	/**
	 * update_field() saves valid data.
	 */
	public function test_update_field_saves_valid_props(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_city', array(
			'label'    => 'My District',
			'priority' => 150,
			'required' => false,
		) );

		$this->assertTrue( $result );

		$fields = Cecomsmarad_Settings::get_fields();
		$this->assertSame( 'My District', $fields['billing_city']['label'] );
		$this->assertSame( 150, $fields['billing_city']['priority'] );
		$this->assertFalse( $fields['billing_city']['required'] );
	}

	/**
	 * update_field() rejects unknown field keys.
	 */
	public function test_update_field_rejects_unknown_key(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_unknown', array(
			'label' => 'Test',
		) );

		$this->assertFalse( $result );
	}

	/**
	 * update_field() rejects empty props.
	 */
	public function test_update_field_rejects_empty_props(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_state', array() );

		$this->assertFalse( $result );
	}

	/**
	 * update_field() rejects priority out of range.
	 */
	public function test_update_field_rejects_invalid_priority(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_state', array(
			'priority' => 0,
		) );

		$this->assertFalse( $result );

		$result = Cecomsmarad_Settings::update_field( 'billing_state', array(
			'priority' => 1000,
		) );

		$this->assertFalse( $result );
	}

	/**
	 * update_field() accepts valid CSS class characters.
	 */
	public function test_update_field_accepts_valid_css_class(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_state', array(
			'class' => 'form-row-wide',
		) );
		$this->assertTrue( $result );

		$fields = Cecomsmarad_Settings::get_fields();
		$this->assertSame( 'form-row-wide', $fields['billing_state']['class'] );
	}

	/**
	 * update_field() strips HTML tags from CSS class value.
	 *
	 * sanitize_text_field strips tags: 'invalid<class>' → 'invalid'
	 * which passes the regex, so the update succeeds.
	 */
	public function test_update_field_sanitizes_css_class_html(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_state', array(
			'class' => 'invalid<class>',
		) );
		// After sanitize_text_field (strip_tags), becomes 'invalid' which is valid.
		$this->assertTrue( $result );

		$fields = Cecomsmarad_Settings::get_fields();
		$this->assertSame( 'invalid', $fields['billing_state']['class'] );
	}

	/**
	 * update_field() rejects CSS class with special characters that survive sanitization.
	 */
	public function test_update_field_rejects_css_class_with_special_chars(): void {
		// Characters like dots and colons are not stripped by sanitize_text_field
		// but fail the regex /^[a-zA-Z0-9\s\-_]*$/
		$result = Cecomsmarad_Settings::update_field( 'billing_state', array(
			'class' => 'class.name:invalid',
		) );
		$this->assertFalse( $result );
	}

	/**
	 * update_field() rejects label longer than 100 chars.
	 */
	public function test_update_field_rejects_long_label(): void {
		$result = Cecomsmarad_Settings::update_field( 'billing_state', array(
			'label' => str_repeat( 'A', 101 ),
		) );

		$this->assertFalse( $result );
	}

	/**
	 * reset_defaults() removes saved settings.
	 */
	public function test_reset_defaults_removes_option(): void {
		global $cecomsmarad_test_options;

		Cecomsmarad_Settings::update_field( 'billing_state', array( 'label' => 'Custom' ) );
		$this->assertArrayHasKey( 'cecomsmarad_field_settings', $cecomsmarad_test_options );

		Cecomsmarad_Settings::reset_defaults();
		$this->assertArrayNotHasKey( 'cecomsmarad_field_settings', $cecomsmarad_test_options );
	}

	/**
	 * After reset, get_fields() returns pure defaults.
	 */
	public function test_fields_are_default_after_reset(): void {
		Cecomsmarad_Settings::update_field( 'billing_state', array( 'label' => 'Custom' ) );
		Cecomsmarad_Settings::reset_defaults();

		$fields   = Cecomsmarad_Settings::get_fields();
		$defaults = Cecomsmarad_Settings::get_default_fields();

		$this->assertSame( $defaults['billing_state']['label'], $fields['billing_state']['label'] );
	}

	/**
	 * Billing postcode is NOT required by default.
	 */
	public function test_postcode_not_required_by_default(): void {
		$fields = Cecomsmarad_Settings::get_default_fields();

		$this->assertFalse( $fields['billing_postcode']['required'] );
		$this->assertFalse( $fields['shipping_postcode']['required'] );
	}

	/**
	 * Province, district, address_2 (neighborhood) are required by default.
	 */
	public function test_address_fields_required_by_default(): void {
		$fields = Cecomsmarad_Settings::get_default_fields();

		$this->assertTrue( $fields['billing_state']['required'] );
		$this->assertTrue( $fields['billing_city']['required'] );
		$this->assertTrue( $fields['billing_address_2']['required'] );
		$this->assertTrue( $fields['shipping_state']['required'] );
		$this->assertTrue( $fields['shipping_city']['required'] );
		$this->assertTrue( $fields['shipping_address_2']['required'] );
	}
}
