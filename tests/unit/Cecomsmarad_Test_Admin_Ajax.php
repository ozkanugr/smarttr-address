<?php
/**
 * Unit tests for Cecomsmarad_Admin_Controller AJAX handlers.
 *
 * Covers: ajax_save_general(), ajax_reset_fields(), ajax_reimport_data(),
 *         ajax_submit_deactivation_feedback()
 *
 * The bootstrap stubs wp_send_json_success() / wp_send_json_error() so they
 * throw Cecomsmarad_Test_Json_Exception instead of calling exit(). Tests catch
 * that exception and inspect its ->response property.
 *
 * @package CecomsmaradAddress
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Class Cecomsmarad_Test_Admin_Ajax
 */
class Cecomsmarad_Test_Admin_Ajax extends TestCase {

	/**
	 * @var Cecomsmarad_Admin_Controller
	 */
	private Cecomsmarad_Admin_Controller $controller;

	/**
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		global $wpdb, $cecomsmarad_test_hooks, $cecomsmarad_test_options,
			$cecomsmarad_test_current_user_can, $cecomsmarad_test_transients,
			$cecomsmarad_test_wp_mail_log, $menu, $submenu;

		$wpdb->reset();
		wp_cache_flush();
		$cecomsmarad_test_hooks           = array();
		$cecomsmarad_test_options         = array();
		$cecomsmarad_test_transients      = array();
		$cecomsmarad_test_current_user_can = true;
		$cecomsmarad_test_wp_mail_log      = array();
		$menu                             = array();
		$submenu                          = array();
		$_POST                            = array();

		$this->controller = new Cecomsmarad_Admin_Controller();
	}

	/**
	 * @return void
	 */
	protected function tear_down(): void {
		global $cecomsmarad_test_hooks, $cecomsmarad_test_options,
			$cecomsmarad_test_current_user_can, $cecomsmarad_test_transients,
			$cecomsmarad_test_wp_mail_log, $menu, $submenu;

		$cecomsmarad_test_hooks           = array();
		$cecomsmarad_test_options         = array();
		$cecomsmarad_test_transients      = array();
		$cecomsmarad_test_current_user_can = true;
		$cecomsmarad_test_wp_mail_log      = array();
		$menu                             = array();
		$submenu                          = array();
		$_POST                            = array();

		parent::tear_down();
	}

	// ──────────────────────────────────────────────────────────────
	// Helpers
	// ──────────────────────────────────────────────────────────────

	/**
	 * Call an AJAX handler and return the JSON response.
	 *
	 * @param callable $handler Callable that executes the AJAX method.
	 * @return array Response from Cecomsmarad_Test_Json_Exception.
	 */
	private function call_ajax( callable $handler ): array {
		$response = array();
		try {
			$handler();
		} catch ( Cecomsmarad_Test_Json_Exception $e ) {
			$response = $e->response;
		}
		return $response;
	}

	// ──────────────────────────────────────────────────────────────
	// ajax_save_general()
	// ──────────────────────────────────────────────────────────────

	/**
	 * ajax_save_general() returns JSON error when user is unauthorized.
	 */
	public function test_ajax_save_general_returns_error_when_unauthorized(): void {
		global $cecomsmarad_test_current_user_can;
		$cecomsmarad_test_current_user_can = false;

		$response = $this->call_ajax( array( $this->controller, 'ajax_save_general' ) );

		$this->assertFalse( $response['success'] );
	}

	/**
	 * ajax_save_general() saves enabled='1' when cecomsmarad_enabled is in POST.
	 */
	public function test_ajax_save_general_saves_enabled_flag(): void {
		global $cecomsmarad_test_options;
		$_POST['cecomsmarad_enabled'] = '1';

		$response = $this->call_ajax( array( $this->controller, 'ajax_save_general' ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( '1', $cecomsmarad_test_options['cecomsmarad_enabled'] );
	}

	/**
	 * ajax_save_general() saves enabled='0' when cecomsmarad_enabled is absent from POST.
	 */
	public function test_ajax_save_general_saves_disabled_when_field_absent(): void {
		global $cecomsmarad_test_options;
		// 'cecomsmarad_enabled' intentionally not set in $_POST.

		$response = $this->call_ajax( array( $this->controller, 'ajax_save_general' ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( '0', $cecomsmarad_test_options['cecomsmarad_enabled'] );
	}

	/**
	 * ajax_save_general() response data includes a message string.
	 */
	public function test_ajax_save_general_response_includes_message(): void {
		$response = $this->call_ajax( array( $this->controller, 'ajax_save_general' ) );

		$this->assertArrayHasKey( 'message', $response['data'] );
		$this->assertIsString( $response['data']['message'] );
	}

	// ──────────────────────────────────────────────────────────────
	// ajax_reset_fields()
	// ──────────────────────────────────────────────────────────────

	/**
	 * ajax_reset_fields() returns JSON error when user is unauthorized.
	 */
	public function test_ajax_reset_fields_returns_error_when_unauthorized(): void {
		global $cecomsmarad_test_current_user_can;
		$cecomsmarad_test_current_user_can = false;

		$response = $this->call_ajax( array( $this->controller, 'ajax_reset_fields' ) );

		$this->assertFalse( $response['success'] );
	}

	/**
	 * ajax_reset_fields() returns success with reload=true.
	 */
	public function test_ajax_reset_fields_returns_success_with_reload(): void {
		$response = $this->call_ajax( array( $this->controller, 'ajax_reset_fields' ) );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $response['data']['reload'] );
	}

	/**
	 * ajax_reset_fields() clears saved field settings from wp_options.
	 */
	public function test_ajax_reset_fields_clears_saved_settings(): void {
		global $cecomsmarad_test_options;
		$cecomsmarad_test_options['cecomsmarad_field_settings'] = array(
			'billing_state' => array( 'label' => 'Custom' ),
		);

		$this->call_ajax( array( $this->controller, 'ajax_reset_fields' ) );

		$this->assertArrayNotHasKey( 'cecomsmarad_field_settings', $cecomsmarad_test_options );
	}

	/**
	 * ajax_reset_fields() response includes a non-empty message string.
	 */
	public function test_ajax_reset_fields_response_includes_message(): void {
		$response = $this->call_ajax( array( $this->controller, 'ajax_reset_fields' ) );

		$this->assertNotEmpty( $response['data']['message'] );
	}

	// ──────────────────────────────────────────────────────────────
	// ajax_reimport_data()
	// ──────────────────────────────────────────────────────────────

	/**
	 * ajax_reimport_data() returns JSON error when user is unauthorized.
	 */
	public function test_ajax_reimport_data_returns_error_when_unauthorized(): void {
		global $cecomsmarad_test_current_user_can;
		$cecomsmarad_test_current_user_can = false;

		$response = $this->call_ajax( array( $this->controller, 'ajax_reimport_data' ) );

		$this->assertFalse( $response['success'] );
	}

	/**
	 * ajax_reimport_data() returns error when within 30-day cooldown window.
	 */
	public function test_ajax_reimport_data_returns_error_within_monthly_cooldown(): void {
		global $cecomsmarad_test_options;
		// Simulate last sync 1 day ago — well within the 30-day cooldown.
		$cecomsmarad_test_options['cecomsmarad_last_manual_sync'] = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );

		$response = $this->call_ajax( array( $this->controller, 'ajax_reimport_data' ) );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'once per month', $response['data']['message'] );
	}

	/**
	 * ajax_reimport_data() does not apply cooldown when last sync was > 30 days ago.
	 */
	public function test_ajax_reimport_data_skips_cooldown_when_last_sync_old(): void {
		global $cecomsmarad_test_options;
		// 31 days ago — cooldown has expired.
		$cecomsmarad_test_options['cecomsmarad_last_manual_sync'] = gmdate( 'Y-m-d H:i:s', time() - ( 31 * DAY_IN_SECONDS ) );

		$response = $this->call_ajax( array( $this->controller, 'ajax_reimport_data' ) );

		// Source URL is not defined in unit test bootstrap → sync returns a
		// "Source URL is not configured" error, NOT a cooldown error.
		$this->assertFalse( $response['success'] );
		$this->assertStringNotContainsString( 'once per month', $response['data']['message'] );
	}

	/**
	 * ajax_reimport_data() propagates sync error message when source URL is absent.
	 */
	public function test_ajax_reimport_data_propagates_sync_error_message(): void {
		// No cooldown set — authorized user, but CECOMSMARAD_DATA_SOURCE_URL
		// is undefined in the unit test bootstrap so sync fails immediately.
		if ( defined( 'CECOMSMARAD_DATA_SOURCE_URL' ) ) {
			$this->markTestSkipped( 'CECOMSMARAD_DATA_SOURCE_URL is defined; cannot test unconfigured sync path.' );
		}

		$response = $this->call_ajax( array( $this->controller, 'ajax_reimport_data' ) );

		$this->assertFalse( $response['success'] );
		$this->assertArrayHasKey( 'message', $response['data'] );
		$this->assertStringContainsString( 'Source URL', $response['data']['message'] );
	}

	// ──────────────────────────────────────────────────────────────
	// ajax_submit_deactivation_feedback()
	// ──────────────────────────────────────────────────────────────

	/**
	 * ajax_submit_deactivation_feedback() returns error when user is unauthorized.
	 */
	public function test_ajax_submit_deactivation_feedback_returns_error_when_unauthorized(): void {
		global $cecomsmarad_test_current_user_can;
		$cecomsmarad_test_current_user_can = false;

		$response = $this->call_ajax( array( $this->controller, 'ajax_submit_deactivation_feedback' ) );

		$this->assertFalse( $response['success'] );
	}

	/**
	 * ajax_submit_deactivation_feedback() succeeds with a valid reason key.
	 */
	public function test_ajax_submit_deactivation_feedback_succeeds_with_valid_reason(): void {
		$_POST['reason']  = 'temporary';
		$_POST['details'] = 'Just testing the plugin temporarily.';

		$response = $this->call_ajax( array( $this->controller, 'ajax_submit_deactivation_feedback' ) );

		$this->assertTrue( $response['success'] );
	}

	/**
	 * ajax_submit_deactivation_feedback() falls back to 'other' for unrecognised reason.
	 */
	public function test_ajax_submit_deactivation_feedback_falls_back_to_other_reason(): void {
		$_POST['reason'] = 'this_reason_does_not_exist';

		$response = $this->call_ajax( array( $this->controller, 'ajax_submit_deactivation_feedback' ) );

		// Should still succeed (defaults to 'other').
		$this->assertTrue( $response['success'] );
	}

	/**
	 * ajax_submit_deactivation_feedback() triggers wp_mail().
	 */
	public function test_ajax_submit_deactivation_feedback_sends_email(): void {
		global $cecomsmarad_test_wp_mail_log;
		$_POST['reason'] = 'not_working';

		$this->call_ajax( array( $this->controller, 'ajax_submit_deactivation_feedback' ) );

		$this->assertCount( 1, $cecomsmarad_test_wp_mail_log );
		$this->assertStringContainsString( 'SmartTR Address', $cecomsmarad_test_wp_mail_log[0]['subject'] );
	}

	/**
	 * ajax_submit_deactivation_feedback() email body includes reason and site URL.
	 */
	public function test_ajax_submit_deactivation_feedback_email_body_includes_site_url(): void {
		global $cecomsmarad_test_wp_mail_log;
		$_POST['reason'] = 'found_better';

		$this->call_ajax( array( $this->controller, 'ajax_submit_deactivation_feedback' ) );

		$this->assertStringContainsString( 'example.com', $cecomsmarad_test_wp_mail_log[0]['message'] );
	}
}
