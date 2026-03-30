<?php
/**
 * Unit tests for Cecomsmarad_Checkout_Controller (free edition).
 *
 * Covers hook registration, province-to-state mapping, country locale
 * injection, noscript body class guarding, and the three custom form-field
 * renderers (file, radio, checkbox).
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Cecomsmarad_Test_Checkout_Controller extends TestCase {

	private Cecomsmarad_Checkout_Controller $controller;

	/**
	 * Prepare a clean environment before every test.
	 *
	 * Flushes the object cache so Province::get_all() always issues a fresh
	 * DB query, resets the wpdb mock, clears recorded hooks, and ensures
	 * the plugin option is set to enabled so the constructor registers hooks.
	 */
	protected function set_up(): void {
		parent::set_up();

		global $wpdb, $cecomsmarad_test_hooks, $cecomsmarad_test_options;

		wp_cache_flush();
		$wpdb->reset();
		$cecomsmarad_test_hooks   = array();
		$cecomsmarad_test_options = array();

		// Default: plugin enabled.
		$cecomsmarad_test_options['cecomsmarad_enabled'] = '1';

		$this->controller = new Cecomsmarad_Checkout_Controller();
	}

	/**
	 * Tear down — clear globals after each test.
	 */
	protected function tear_down(): void {
		global $cecomsmarad_test_hooks, $cecomsmarad_test_options;
		$cecomsmarad_test_hooks   = array();
		$cecomsmarad_test_options = array();

		parent::tear_down();
	}

	/*
	|--------------------------------------------------------------------------
	| Hook registration
	|--------------------------------------------------------------------------
	*/

	/**
	 * Constructor registers all expected WooCommerce filter and action hooks.
	 */
	public function test_constructor_registers_hooks(): void {
		global $cecomsmarad_test_hooks;
		$cecomsmarad_test_hooks = array();
		new Cecomsmarad_Checkout_Controller();
		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );
		$this->assertContains( 'woocommerce_states', $tags );
		$this->assertContains( 'woocommerce_checkout_fields', $tags );
		$this->assertContains( 'woocommerce_get_country_locale', $tags );
	}

	/**
	 * Constructor registers custom form-field renderer hooks.
	 */
	public function test_constructor_registers_form_field_renderer_hooks(): void {
		global $cecomsmarad_test_hooks;
		$cecomsmarad_test_hooks = array();
		new Cecomsmarad_Checkout_Controller();
		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );
		$this->assertContains( 'woocommerce_form_field_file', $tags );
		$this->assertContains( 'woocommerce_form_field_radio', $tags );
		$this->assertContains( 'woocommerce_form_field_checkbox', $tags );
	}

	/**
	 * Constructor registers body_class and noscript render action hooks.
	 */
	public function test_constructor_registers_noscript_hooks(): void {
		global $cecomsmarad_test_hooks;
		$cecomsmarad_test_hooks = array();
		new Cecomsmarad_Checkout_Controller();
		$tags = array_column( $cecomsmarad_test_hooks, 'tag' );
		$this->assertContains( 'body_class', $tags );
		$this->assertContains( 'woocommerce_after_checkout_billing_form', $tags );
		$this->assertContains( 'woocommerce_after_checkout_shipping_form', $tags );
	}

	/**
	 * Constructor skips all hook registration when the plugin option is '0'.
	 */
	public function test_constructor_skips_hooks_when_disabled(): void {
		global $cecomsmarad_test_hooks, $cecomsmarad_test_options;
		$cecomsmarad_test_options['cecomsmarad_enabled'] = '0';
		$cecomsmarad_test_hooks = array();
		new Cecomsmarad_Checkout_Controller();
		$this->assertEmpty( $cecomsmarad_test_hooks );
	}

	/*
	|--------------------------------------------------------------------------
	| register_provinces_as_states()
	|--------------------------------------------------------------------------
	*/

	/**
	 * register_provinces_as_states() maps province objects to TR states keyed by code.
	 */
	public function test_register_provinces_as_states_builds_tr_entry(): void {
		global $wpdb;

		$wpdb->next_results = array(
			(object) array( 'id' => '34', 'code' => 'TR34', 'name' => 'İstanbul' ),
			(object) array( 'id' => '6',  'code' => 'TR06', 'name' => 'Ankara' ),
			(object) array( 'id' => '1',  'code' => 'TR01', 'name' => 'Adana' ),
		);

		$result = $this->controller->register_provinces_as_states( array() );

		$this->assertArrayHasKey( 'TR', $result );
		$this->assertSame( 'İstanbul', $result['TR']['TR34'] );
		$this->assertSame( 'Ankara', $result['TR']['TR06'] );
		$this->assertSame( 'Adana', $result['TR']['TR01'] );
	}

	/**
	 * register_provinces_as_states() preserves existing non-TR state entries.
	 */
	public function test_register_provinces_as_states_preserves_other_countries(): void {
		global $wpdb;

		$wpdb->next_results = array(
			(object) array( 'id' => '34', 'code' => 'TR34', 'name' => 'İstanbul' ),
		);

		$existing = array( 'US' => array( 'CA' => 'California', 'NY' => 'New York' ) );

		$result = $this->controller->register_provinces_as_states( $existing );

		$this->assertArrayHasKey( 'US', $result );
		$this->assertSame( 'California', $result['US']['CA'] );
		$this->assertArrayHasKey( 'TR', $result );
	}

	/**
	 * register_provinces_as_states() produces an empty TR array when there are no provinces.
	 */
	public function test_register_provinces_as_states_empty_when_no_provinces(): void {
		global $wpdb;
		$wpdb->next_results = array();

		$result = $this->controller->register_provinces_as_states( array() );

		$this->assertArrayHasKey( 'TR', $result );
		$this->assertIsArray( $result['TR'] );
		$this->assertEmpty( $result['TR'] );
	}

	/**
	 * register_provinces_as_states() uses province code as the state key (not name).
	 */
	public function test_register_provinces_as_states_uses_code_as_key(): void {
		global $wpdb;

		$wpdb->next_results = array(
			(object) array( 'id' => '7', 'code' => 'TR07', 'name' => 'Antalya' ),
		);

		$result = $this->controller->register_provinces_as_states( array() );

		$this->assertArrayHasKey( 'TR07', $result['TR'] );
		$this->assertSame( 'Antalya', $result['TR']['TR07'] );
	}

	/*
	|--------------------------------------------------------------------------
	| set_field_locale()
	|--------------------------------------------------------------------------
	*/

	/**
	 * set_field_locale() adds cascade classes to the city field in TR locale.
	 */
	public function test_set_field_locale_adds_cascade_class_to_city(): void {
		$locale = array();

		$result = $this->controller->set_field_locale( $locale );

		$this->assertArrayHasKey( 'TR', $result );
		$this->assertArrayHasKey( 'city', $result['TR'] );
		$this->assertContains( 'cecomsmarad-field', $result['TR']['city']['class'] );
		$this->assertContains( 'cecomsmarad-district', $result['TR']['city']['class'] );
	}

	/**
	 * set_field_locale() adds cascade classes to the postcode field in TR locale.
	 */
	public function test_set_field_locale_adds_cascade_class_to_postcode(): void {
		$locale = array();

		$result = $this->controller->set_field_locale( $locale );

		$this->assertArrayHasKey( 'postcode', $result['TR'] );
		$this->assertContains( 'cecomsmarad-field', $result['TR']['postcode']['class'] );
		$this->assertContains( 'cecomsmarad-postcode', $result['TR']['postcode']['class'] );
	}

	/**
	 * set_field_locale() defaults to form-row-wide as base class when TR locale is absent.
	 */
	public function test_set_field_locale_defaults_to_form_row_wide_when_no_existing_class(): void {
		$locale = array();

		$result = $this->controller->set_field_locale( $locale );

		$this->assertContains( 'form-row-wide', $result['TR']['city']['class'] );
		$this->assertContains( 'form-row-wide', $result['TR']['postcode']['class'] );
	}

	/**
	 * set_field_locale() merges cascade classes with existing class values.
	 */
	public function test_set_field_locale_merges_with_existing_class(): void {
		$locale = array(
			'TR' => array(
				'city' => array( 'class' => 'existing-class' ),
			),
		);

		$result = $this->controller->set_field_locale( $locale );

		$city_classes = $result['TR']['city']['class'];
		$this->assertContains( 'existing-class', $city_classes );
		$this->assertContains( 'cecomsmarad-field', $city_classes );
		$this->assertContains( 'cecomsmarad-district', $city_classes );
	}

	/**
	 * set_field_locale() does not modify non-TR locale entries.
	 */
	public function test_set_field_locale_leaves_non_tr_locales_unchanged(): void {
		$locale = array(
			'DE' => array( 'postcode' => array( 'required' => true ) ),
		);

		$result = $this->controller->set_field_locale( $locale );

		// DE entry must be untouched.
		$this->assertSame( array( 'required' => true ), $result['DE']['postcode'] );
	}

	/*
	|--------------------------------------------------------------------------
	| add_noscript_body_class()
	|--------------------------------------------------------------------------
	*/

	/**
	 * add_noscript_body_class() returns classes unchanged when is_checkout() is not defined.
	 *
	 * The controller guards with function_exists('is_checkout'). The test
	 * bootstrap does not define is_checkout(), so the class must never be added.
	 */
	public function test_add_noscript_body_class_unchanged_when_is_checkout_undefined(): void {
		$classes = array( 'home', 'woocommerce' );

		$result = $this->controller->add_noscript_body_class( $classes );

		$this->assertNotContains( 'cecomsmarad-no-js', $result );
		$this->assertSame( $classes, $result );
	}

	/**
	 * add_noscript_body_class() returns an empty class array unchanged.
	 */
	public function test_add_noscript_body_class_preserves_empty_array(): void {
		$result = $this->controller->add_noscript_body_class( array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/*
	|--------------------------------------------------------------------------
	| render_file_form_field()
	|--------------------------------------------------------------------------
	*/

	/**
	 * render_file_form_field() outputs a file input element with the correct key.
	 */
	public function test_render_file_form_field_contains_file_input(): void {
		$args = array(
			'label'    => 'Upload Document',
			'required' => false,
			'class'    => array( 'form-row-wide' ),
		);

		$html = $this->controller->render_file_form_field( '', 'billing_document', $args, '' );

		$this->assertStringContainsString( 'type="file"', $html );
		$this->assertStringContainsString( 'id="billing_document"', $html );
		$this->assertStringContainsString( 'name="billing_document"', $html );
	}

	/**
	 * render_file_form_field() includes a hidden companion URL input.
	 */
	public function test_render_file_form_field_contains_hidden_url_input(): void {
		$args = array( 'label' => 'File', 'required' => false );

		$html = $this->controller->render_file_form_field( '', 'billing_doc', $args, '' );

		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertStringContainsString( 'name="billing_doc_url"', $html );
		$this->assertStringContainsString( 'id="billing_doc_url"', $html );
	}

	/**
	 * render_file_form_field() renders label text.
	 */
	public function test_render_file_form_field_renders_label(): void {
		$args = array( 'label' => 'Tax Certificate', 'required' => false );

		$html = $this->controller->render_file_form_field( '', 'billing_tax_cert', $args, '' );

		$this->assertStringContainsString( 'Tax Certificate', $html );
		$this->assertStringContainsString( '<label', $html );
	}

	/**
	 * render_file_form_field() adds required mark and aria attribute when required.
	 */
	public function test_render_file_form_field_marks_required_field(): void {
		$args = array( 'label' => 'ID Scan', 'required' => true );

		$html = $this->controller->render_file_form_field( '', 'billing_id', $args, '' );

		$this->assertStringContainsString( 'aria-required="true"', $html );
		$this->assertStringContainsString( 'class="required"', $html );
		$this->assertStringContainsString( 'validate-required', $html );
	}

	/**
	 * render_file_form_field() adds accept attribute from allowed_extensions.
	 */
	public function test_render_file_form_field_adds_accept_attribute(): void {
		$args = array(
			'label'              => 'Document',
			'required'           => false,
			'allowed_extensions' => 'pdf, jpg, png',
		);

		$html = $this->controller->render_file_form_field( '', 'billing_upload', $args, '' );

		$this->assertStringContainsString( 'accept=".pdf,.jpg,.png"', $html );
	}

	/**
	 * render_file_form_field() adds a cecomsmarad-file-status span.
	 */
	public function test_render_file_form_field_contains_status_span(): void {
		$args = array( 'label' => 'File', 'required' => false );

		$html = $this->controller->render_file_form_field( '', 'billing_file', $args, '' );

		$this->assertStringContainsString( 'cecomsmarad-file-status', $html );
		$this->assertStringContainsString( 'aria-live="polite"', $html );
	}

	/*
	|--------------------------------------------------------------------------
	| render_radio_form_field()
	|--------------------------------------------------------------------------
	*/

	/**
	 * render_radio_form_field() returns the original field when options are empty.
	 */
	public function test_render_radio_form_field_returns_original_field_when_no_options(): void {
		$original = '<p>wc-generated-radio</p>';
		$args     = array( 'options' => array() );

		$result = $this->controller->render_radio_form_field( $original, 'billing_type', $args, '' );

		$this->assertSame( $original, $result );
	}

	/**
	 * render_radio_form_field() renders radio inputs for each option.
	 */
	public function test_render_radio_form_field_renders_radio_inputs(): void {
		$args = array(
			'label'   => 'Delivery Type',
			'options' => array(
				'standard' => 'Standard Delivery',
				'express'  => 'Express Delivery',
			),
		);

		$html = $this->controller->render_radio_form_field( '', 'billing_delivery', $args, 'standard' );

		$this->assertStringContainsString( 'type="radio"', $html );
		$this->assertStringContainsString( 'Standard Delivery', $html );
		$this->assertStringContainsString( 'Express Delivery', $html );
	}

	/**
	 * render_radio_form_field() marks the matching option as checked.
	 */
	public function test_render_radio_form_field_checks_selected_option(): void {
		$args = array(
			'label'   => 'Delivery',
			'options' => array(
				'standard' => 'Standard',
				'express'  => 'Express',
			),
		);

		$html = $this->controller->render_radio_form_field( '', 'billing_delivery', $args, 'express' );

		// The express option should be checked; standard should not.
		$this->assertStringContainsString( 'value="express"', $html );
		$pos_express  = strpos( $html, 'value="express"' );
		$pos_checked  = strpos( $html, 'checked="checked"', $pos_express );
		$this->assertNotFalse( $pos_checked );
	}

	/**
	 * render_radio_form_field() places the required mark on the group label only.
	 */
	public function test_render_radio_form_field_required_mark_on_group_label_only(): void {
		$args = array(
			'label'    => 'Choose',
			'required' => true,
			'options'  => array( 'a' => 'Option A', 'b' => 'Option B' ),
		);

		$html = $this->controller->render_radio_form_field( '', 'billing_choice', $args, '' );

		// abbr.required must appear exactly once (on the group label).
		$this->assertSame( 1, substr_count( $html, 'class="required"' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| render_checkbox_form_field()
	|--------------------------------------------------------------------------
	*/

	/**
	 * render_checkbox_form_field() returns the original field when options are empty.
	 */
	public function test_render_checkbox_form_field_returns_original_field_when_no_options(): void {
		$original = '<p>wc-generated-checkbox</p>';
		$args     = array( 'options' => array() );

		$result = $this->controller->render_checkbox_form_field( $original, 'billing_agree', $args, '' );

		$this->assertSame( $original, $result );
	}

	/**
	 * render_checkbox_form_field() renders checkbox inputs for each option.
	 */
	public function test_render_checkbox_form_field_renders_checkbox_inputs(): void {
		$args = array(
			'label'   => 'Preferred Contact',
			'options' => array(
				'email' => 'Email',
				'phone' => 'Phone',
				'sms'   => 'SMS',
			),
		);

		$html = $this->controller->render_checkbox_form_field( '', 'billing_contact', $args, '' );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'Email', $html );
		$this->assertStringContainsString( 'Phone', $html );
		$this->assertStringContainsString( 'SMS', $html );
	}

	/**
	 * render_checkbox_form_field() wraps options in a woocommerce-input-wrapper span.
	 */
	public function test_render_checkbox_form_field_wraps_in_input_wrapper(): void {
		$args = array(
			'label'   => 'Options',
			'options' => array( 'yes' => 'Yes' ),
		);

		$html = $this->controller->render_checkbox_form_field( '', 'billing_opt', $args, '' );

		$this->assertStringContainsString( 'woocommerce-input-wrapper', $html );
	}

	/**
	 * render_checkbox_form_field() adds validate-required class when field is required.
	 */
	public function test_render_checkbox_form_field_required_adds_validate_class(): void {
		$args = array(
			'label'    => 'Terms',
			'required' => true,
			'options'  => array( 'yes' => 'I agree' ),
		);

		$html = $this->controller->render_checkbox_form_field( '', 'billing_terms', $args, '' );

		$this->assertStringContainsString( 'validate-required', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
	}
}
