<?php
/**
 * Admin settings page controller.
 *
 * Registers the WooCommerce submenu, handles form submissions,
 * and enqueues admin-only assets.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Admin_Controller
 *
 * Manages the CecomsmaradAddress settings page with two tabs:
 * General and Data Management.
 */
class Cecomsmarad_Admin_Controller {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'cecomsmarad-settings';

	/**
	 * Required capability to access settings.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_woocommerce';

	/**
	 * Nonce action for form submissions.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'cecomsmarad_admin_save';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	private const NONCE_FIELD = '_cecomsmarad_nonce';

	/**
	 * Available tabs.
	 *
	 * @var string[]
	 */
	private const TABS = array( 'general', 'data', 'fields', 'upgrade' );

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX endpoints.
		add_action( 'wp_ajax_cecomsmarad_save_general', array( $this, 'ajax_save_general' ) );
		add_action( 'wp_ajax_cecomsmarad_reset_fields', array( $this, 'ajax_reset_fields' ) );
		add_action( 'wp_ajax_cecomsmarad_reimport_data', array( $this, 'ajax_reimport_data' ) );

		// Deactivation feedback AJAX endpoint (admin-only; no nopriv).
		add_action( 'wp_ajax_cecomsmarad_submit_deactivation_feedback', array( $this, 'ajax_submit_deactivation_feedback' ) );

		// Enqueue the deactivation feedback modal on the Plugins list page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_deactivation_feedback' ) );
	}

	/**
	 * Register the CECOM parent menu (singleton) and this plugin's submenu.
	 *
	 * All CECOM plugins share the 'cecomplgns' parent slug. The parent menu is
	 * registered only once — whichever plugin's admin_menu hook fires first.
	 *
	 * @return void
	 */
	public function register_menu(): void {

		// -- CECOM parent menu (singleton) ----------------------------------------
		global $menu;
		$cecom_registered = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'cecomplgns' === $item[2] ) {
					$cecom_registered = true;
					break;
				}
			}
		}

		if ( ! $cecom_registered ) {
			$icon_path = CECOMSMARAD_PLUGIN_DIR . 'assets/img/cecomplgns-menu-icon.svg';
			$icon_data = file_exists( $icon_path )
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				? 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $icon_path ) )
				: 'dashicons-admin-plugins';

			add_menu_page(
				esc_html__( 'CECOM', 'smarttr-address' ),
				esc_html__( 'CECOM', 'smarttr-address' ),
				'manage_options',
				'cecomplgns',
				'__return_null',
				$icon_data,
				58
			);

			// Remove the WordPress built-in separator at position 59 ('separator2')
			// which otherwise creates a visual gap directly below the CECOM menu item.
			remove_menu_page( 'separator2' );
		}

		// -- Plugin submenu --------------------------------------------------------
		add_submenu_page(
			'cecomplgns',
			esc_html__( 'SmartTR Address Settings', 'smarttr-address' ),
			esc_html__( 'SmartTR Address', 'smarttr-address' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		// WordPress auto-inserts the parent page as the first submenu entry.
		// Since the parent uses __return_null, this duplicate link serves no purpose.
		global $submenu;
		if ( isset( $submenu['cecomplgns'][0] ) && 'cecomplgns' === ( $submenu['cecomplgns'][0][2] ?? '' ) ) {
			unset( $submenu['cecomplgns'][0] );
		}
	}

	/**
	 * Enqueue admin assets only on the plugin settings page.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'cecom_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		// 1. Bootstrap CSS — bundled locally (CDN not allowed per WordPress.org guidelines).
		wp_enqueue_style(
			'bootstrap',
			CECOMSMARAD_PLUGIN_URL . 'assets/dist/css/bootstrap.min.css',
			array(),
			'5.3.8'
		);

		// 2. Bootstrap Icons.
		wp_enqueue_style(
			'bootstrap-icons',
			CECOMSMARAD_PLUGIN_URL . 'assets/icons/font/bootstrap-icons.css',
			array(),
			'1.13.1'
		);

		// 3. CECOM Framework CSS (brand color overrides — shared across all CECOM plugins).
		wp_enqueue_style(
			'cecom-framework',
			CECOMSMARAD_PLUGIN_URL . 'assets/css/cecom-plugin-admin-ui-framework.css',
			array( 'bootstrap' ),
			CECOMSMARAD_VERSION
		);

		// 4. Plugin-specific CSS.
		$css_file = CECOMSMARAD_PLUGIN_DIR . 'assets/css/cecomsmarad-admin.css';
		wp_enqueue_style(
			'cecomsmarad-admin',
			CECOMSMARAD_PLUGIN_URL . 'assets/css/cecomsmarad-admin.css',
			array( 'cecom-framework' ),
			file_exists( $css_file ) ? (string) filemtime( $css_file ) : CECOMSMARAD_VERSION
		);

		// 5. Bootstrap JS (includes Popper).
		wp_enqueue_script(
			'bootstrap',
			CECOMSMARAD_PLUGIN_URL . 'assets/dist/js/bootstrap.bundle.min.js',
			array(),
			'5.3.8',
			true
		);

		// 6. Plugin JS.
		$js_file = CECOMSMARAD_PLUGIN_DIR . 'assets/js/cecomsmarad-admin.js';
		wp_enqueue_script(
			'cecomsmarad-admin',
			CECOMSMARAD_PLUGIN_URL . 'assets/js/cecomsmarad-admin.js',
			array( 'jquery', 'bootstrap' ),
			file_exists( $js_file ) ? (string) filemtime( $js_file ) : CECOMSMARAD_VERSION,
			true
		);

		// 7. Localized data for AJAX and i18n.
		wp_localize_script(
			'cecomsmarad-admin',
			'cecomsmaradAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => array(
					'confirmReimport' => __( 'This will delete existing address data and reload it from the remote server. Do you want to continue?', 'smarttr-address' ),
					'saved'           => __( 'Settings saved.', 'smarttr-address' ),
					'saving'          => __( 'Saving…', 'smarttr-address' ),
					'error'           => __( 'An error occurred. Please try again.', 'smarttr-address' ),
					'reimportSuccess' => __( 'Address data successfully updated.', 'smarttr-address' ),
					'reimporting'     => __( 'Updating data…', 'smarttr-address' ),
					'cancel'          => __( 'Cancel', 'smarttr-address' ),
					'confirm'         => __( 'Confirm', 'smarttr-address' ),
					'reimportTitle'   => __( 'Reload Data', 'smarttr-address' ),
					'unsavedChanges'  => __( 'You have unsaved changes. Do you want to leave the page?', 'smarttr-address' ),
					'shortcodeCopied' => __( 'Shortcode copied!', 'smarttr-address' ),
				),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smarttr-address' ) );
		}

		$current_tab = $this->get_current_tab();
		$fields      = Cecomsmarad_Settings::get_fields();

		include CECOMSMARAD_PLUGIN_DIR . 'includes/views/admin/settings.php';
	}

	/**
	 * Handle form submissions for all tabs.
	 *
	 * @return void
	 */
	public function handle_form_submission(): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smarttr-address' ) );
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security verification failed.', 'smarttr-address' ) );
		}

		$action = isset( $_POST['cecomsmarad_action'] ) ? sanitize_text_field( wp_unslash( $_POST['cecomsmarad_action'] ) ) : '';

		switch ( $action ) {
			case 'save_general':
				$this->handle_save_general();
				break;

			case 'reimport_data':
				$this->handle_reimport();
				break;
		}
	}

	/**
	 * Handle general settings save.
	 *
	 * @return void
	 */
	private function handle_save_general(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_form_submission().
		$enabled = ! empty( $_POST['cecomsmarad_enabled'] ) ? '1' : '0';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_option( 'cecomsmarad_enabled', $enabled, false );

		$this->add_admin_notice( 'success', __( 'General settings saved.', 'smarttr-address' ) );
	}

	/**
	 * Handle address data update (remote sync).
	 *
	 * @return void
	 */
	private function handle_reimport(): void {
		Cecomsmarad_Data_Importer::create_tables();
		$result = Cecomsmarad_Remote_Sync::sync();

		if ( $result['success'] ) {
			$this->add_admin_notice( 'success', $result['message'] );
		} else {
			$this->add_admin_notice( 'error', $result['message'] );
		}
	}

	/**
	 * Get the current active tab.
	 *
	 * @return string Tab slug.
	 */
	private function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin tab routing; value is validated against the TABS allowlist below.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		return in_array( $tab, self::TABS, true ) ? $tab : 'general';
	}

	/**
	 * Queue an admin notice to be displayed after redirect.
	 *
	 * @param string $type    Notice type: success, error, warning, info.
	 * @param string $message Notice message.
	 * @return void
	 */
	private function add_admin_notice( string $type, string $message ): void {
		add_settings_error(
			'cecomsmarad_messages',
			'cecomsmarad_message',
			$message,
			$type
		);
	}

	/**
	 * Return a validated visibility value from POST input.
	 *
	 * Only the three values the plugin actually acts on are permitted.
	 * Any unrecognised value defaults to 'visible' so the field is never
	 * silently hidden by crafted input.
	 *
	 * @param string $value Raw input value.
	 * @return string 'visible', 'hidden', or 'unset'.
	 */
	private static function valid_visibility( string $value ): string {
		return in_array( $value, array( 'visible', 'hidden', 'unset' ), true ) ? $value : 'visible';
	}

	/**
	 * Return a validated field type from POST input.
	 *
	 * Constrained to the set of types the plugin renders and stores.
	 * Any unrecognised value defaults to 'text'.
	 *
	 * @param string $value Raw input value.
	 * @return string Validated field type.
	 */
	private static function valid_field_type( string $value ): string {
		$allowed = array(
			'text',
			'email',
			'tel',
			'password',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'date',
			'datetime-local',
			'file',
			'country',
			'state',
			'hidden',
		);

		return in_array( $value, $allowed, true ) ? $value : 'text';
	}

	/**
	 * Prepare submitted field data for bulk update.
	 *
	 * Sanitizes each field's properties from form input into a format
	 * suitable for Cecomsmarad_Settings::update_fields_bulk().
	 *
	 * @param array<string, mixed> $submitted Raw POST data from cecomsmarad_fields.
	 * @return array<string, array<string, mixed>> Sanitized field key => props.
	 */
	private static function prepare_field_updates( array $submitted ): array {
		$bulk = array();

		foreach ( $submitted as $field_key => $props ) {
			$field_key = sanitize_text_field( $field_key );

			if ( ! is_array( $props ) ) {
				continue;
			}

			$clear_key_suffixes = array( '_country', '_address_2', '_city', '_state' );
			$field_key_clear_ok = false;
			foreach ( $clear_key_suffixes as $suffix ) {
				if ( str_ends_with( $field_key, $suffix ) ) {
					$field_key_clear_ok = true;
					break;
				}
			}
			$type_val       = isset( $props['type'] ) ? self::valid_field_type( sanitize_text_field( $props['type'] ) ) : 'text';
			$visibility_val = isset( $props['visibility'] ) ? self::valid_visibility( sanitize_text_field( $props['visibility'] ) ) : 'visible';

			$bulk[ $field_key ] = array(
				'type'               => $type_val,
				'label'              => isset( $props['label'] ) ? sanitize_text_field( $props['label'] ) : '',
				'description'        => isset( $props['description'] ) ? sanitize_text_field( $props['description'] ) : '',
				'placeholder'        => isset( $props['placeholder'] ) ? sanitize_text_field( $props['placeholder'] ) : '',
				'class'              => isset( $props['class'] ) ? sanitize_text_field( $props['class'] ) : '',
				'required'           => isset( $props['required'] ),
				'clear'              => isset( $props['clear'] ) && ( 'select' === $type_val || $field_key_clear_ok ),
				'label_class'        => isset( $props['label_class'] ) ? sanitize_text_field( $props['label_class'] ) : '',
				'options'            => isset( $props['options'] ) ? sanitize_text_field( $props['options'] ) : '',
				'priority'           => isset( $props['priority'] ) ? absint( $props['priority'] ) : 100,
				'visibility'         => $visibility_val,
				'allowed_extensions' => isset( $props['allowed_extensions'] ) ? sanitize_text_field( $props['allowed_extensions'] ) : '',
			);
		}

		return $bulk;
	}

	/*
	 * ==================================================================
	 * AJAX Handlers
	 * ==================================================================
	 */

	/**
	 * AJAX: Save general settings.
	 *
	 * @return void
	 */
	public function ajax_save_general(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'smarttr-address' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, '_cecomsmarad_nonce' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$enabled = ! empty( $_POST['cecomsmarad_enabled'] ) ? '1' : '0';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_option( 'cecomsmarad_enabled', $enabled, false );

		wp_send_json_success( array( 'message' => __( 'General settings saved.', 'smarttr-address' ) ) );
	}

	/**
	 * AJAX: Reset fields to defaults.
	 *
	 * @return void
	 */
	public function ajax_reset_fields(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'smarttr-address' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, '_cecomsmarad_nonce' );

		Cecomsmarad_Settings::reset_defaults();
		wp_send_json_success(
			array(
				'message' => __( 'All fields reset to default values.', 'smarttr-address' ),
				'reload'  => true,
			)
		);
	}

	/**
	 * AJAX: Update address data from the remote cecom-address-tr API.
	 *
	 * @return void
	 */
	public function ajax_reimport_data(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'smarttr-address' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, '_cecomsmarad_nonce' );

		// ── Monthly cooldown ──────────────────────────────────────────
		$last_manual = get_option( 'cecomsmarad_last_manual_sync', '' );
		if ( '' !== $last_manual ) {
			$last_manual_ts = (int) strtotime( $last_manual );
			if ( ( time() - $last_manual_ts ) < 30 * DAY_IN_SECONDS ) {
				$next_ts  = $last_manual_ts + 30 * DAY_IN_SECONDS;
				$next_fmt = get_date_from_gmt(
					gmdate( 'Y-m-d H:i:s', $next_ts ),
					get_option( 'date_format' )
				);
				wp_send_json_error(
					array(
						'message' => sprintf(
						/* translators: %s: date the next manual sync becomes available */
							__( 'Address data can be updated once per month. Next update available: %s.', 'smarttr-address' ),
							$next_fmt
						),
					)
				);
			}
		}

		Cecomsmarad_Data_Importer::create_tables();
		$result = Cecomsmarad_Remote_Sync::sync();

		if ( $result['success'] ) {
			update_option( 'cecomsmarad_last_manual_sync', current_time( 'mysql', true ), false );
			wp_send_json_success(
				array(
					'message' => $result['message'],
					'reload'  => true,
				)
			);
		}

		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	/*
	 * ==================================================================
	 * Environment & Health Helpers
	 * ==================================================================
	 */

	/**
	 * Get environment information for the General tab dashboard.
	 *
	 * @return array{php: string, wp: string, wc: string, wc_active: bool, hpos: bool, checkout_type: string}
	 */
	public static function get_environment_info(): array {
		$wc_active = class_exists( 'WooCommerce' );

		$hpos = false;
		if ( $wc_active && class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
			$hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		$checkout_type = 'classic';
		if ( $wc_active && class_exists( \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::class ) ) {
			if ( \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() ) {
				$checkout_type = 'block';
			}
		}

		return array(
			'php'           => PHP_VERSION,
			'wp'            => get_bloginfo( 'version' ),
			'wc'            => $wc_active ? WC()->version : '',
			'wc_active'     => $wc_active,
			'hpos'          => $hpos,
			'checkout_type' => $checkout_type,
		);
	}

	/**
	 * Get database health information for the Data Management tab.
	 *
	 * @return array{tables: array<string, bool>, total_size: string, charset: string, prefix: string}
	 */
	public static function get_db_health(): array {
		global $wpdb;

		$table_names = array(
			'provinces' => $wpdb->prefix . 'cecomsmarad_provinces',
			'districts' => $wpdb->prefix . 'cecomsmarad_districts',
		);

		$tables     = array();
		$total_size = 0;

		foreach ( $table_names as $key => $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- information_schema metadata query; result changes on every write, caching would be stale.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT TABLE_NAME, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
					DB_NAME,
					$table
				)
			);

			$tables[ $key ] = null !== $row;

			if ( $row ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- INFORMATION_SCHEMA column names are UPPERCASE by MySQL convention.
				$total_size += (int) $row->DATA_LENGTH + (int) $row->INDEX_LENGTH;
			}
		}

		return array(
			'tables'     => $tables,
			'total_size' => size_format( $total_size ),
			'charset'    => $wpdb->charset ? $wpdb->charset : 'utf8mb4',
			'prefix'     => $wpdb->prefix,
		);
	}

	/*
	 * ====================================================================
	 * Deactivation Feedback
	 * ====================================================================
	 */

	/**
	 * Enqueue the deactivation-feedback modal assets on the Plugins list page.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_deactivation_feedback( string $hook_suffix ): void {
		if ( 'plugins.php' !== $hook_suffix ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$css_file = CECOMSMARAD_PLUGIN_DIR . 'assets/css/cecomsmarad-deactivation-feedback.css';
		$js_file  = CECOMSMARAD_PLUGIN_DIR . 'assets/js/cecomsmarad-deactivation-feedback.js';

		wp_enqueue_style(
			'cecomsmarad-deactivation-feedback',
			CECOMSMARAD_PLUGIN_URL . 'assets/css/cecomsmarad-deactivation-feedback.css',
			array(),
			(string) filemtime( $css_file )
		);

		wp_enqueue_script(
			'cecomsmarad-deactivation-feedback',
			CECOMSMARAD_PLUGIN_URL . 'assets/js/cecomsmarad-deactivation-feedback.js',
			array(),
			(string) filemtime( $js_file ),
			true
		);

		wp_localize_script(
			'cecomsmarad-deactivation-feedback',
			'cecomsmaradFeedback',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'cecomsmarad_deactivation_feedback_nonce' ),
				'pluginBasename' => CECOMSMARAD_PLUGIN_BASENAME,
				'iconUrl'        => CECOMSMARAD_PLUGIN_URL . 'assets/img/cecomsmarad-address-icon.png',
				'i18n'           => array(
					'title'               => __( 'Quick Feedback', 'smarttr-address' ),
					'close'               => __( 'Close', 'smarttr-address' ),
					'question'            => __( 'If you have a moment, please share why you are deactivating SmartTR Address:', 'smarttr-address' ),
					'temporary'           => __( 'This is a temporary deactivation for testing.', 'smarttr-address' ),
					'not_working'         => __( "The plugin isn't working properly.", 'smarttr-address' ),
					'found_better'        => __( 'I found a better alternative plugin.', 'smarttr-address' ),
					'missing_feature'     => __( "It's missing a specific feature.", 'smarttr-address' ),
					'other'               => __( 'Other', 'smarttr-address' ),
					'details_placeholder' => __( 'Please tell us more details.', 'smarttr-address' ),
					'submit'              => __( 'Submit & Deactivate', 'smarttr-address' ),
					'skip'                => __( 'Skip & Deactivate', 'smarttr-address' ),
					'sending'             => __( 'Sending\u2026', 'smarttr-address' ),
				),
			)
		);
	}

	/**
	 * Email address that receives deactivation feedback.
	 *
	 * Override in wp-config.php:
	 *   define( 'CECOMSMARAD_FEEDBACK_EMAIL', 'you@example.com' );
	 *
	 * @var string
	 */
	private const FEEDBACK_EMAIL = 'feedback@cecom.in';

	/**
	 * Receive a deactivation-feedback submission and email it to the plugin author.
	 *
	 * Uses wp_mail() so no external server endpoint is required. WordPress
	 * proceeds to the deactivation redirect via the JS caller regardless of
	 * whether the email was delivered successfully.
	 *
	 * @return void Sends JSON and terminates.
	 */
	public function ajax_submit_deactivation_feedback(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		check_ajax_referer( 'cecomsmarad_deactivation_feedback_nonce', '_wpnonce' );

		$valid_reasons = array(
			'temporary'       => 'This is a temporary deactivation for testing.',
			'not_working'     => "The plugin isn't working properly.",
			'found_better'    => 'I found a better alternative plugin.',
			'missing_feature' => "It's missing a specific feature.",
			'other'           => 'Other',
		);

		$reason_key = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : 'other';
		if ( ! array_key_exists( $reason_key, $valid_reasons ) ) {
			$reason_key = 'other';
		}

		$details     = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		$site_url    = esc_url_raw( get_site_url() );
		$reason_text = $valid_reasons[ $reason_key ];

		$to = self::FEEDBACK_EMAIL;
		if ( defined( 'CECOMSMARAD_FEEDBACK_EMAIL' ) && is_email( CECOMSMARAD_FEEDBACK_EMAIL ) ) {
			$to = sanitize_email( CECOMSMARAD_FEEDBACK_EMAIL );
		}
		$subject = sprintf( '[SmartTR Address v%s] Deactivation feedback — %s', CECOMSMARAD_VERSION, wp_parse_url( $site_url, PHP_URL_HOST ) );
		$message = implode(
			"\n",
			array(
				'A user has deactivated SmartTR Address.',
				'',
				'Reason : ' . $reason_text,
				'Details: ' . ( '' !== $details ? $details : '(none)' ),
				'Site   : ' . $site_url,
				'Version: ' . CECOMSMARAD_VERSION,
				'Date   : ' . wp_date( 'Y-m-d H:i:s T' ),
			)
		);

		wp_mail( $to, $subject, $message );

		wp_send_json_success();
	}
}
