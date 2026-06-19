<?php
/**
 * Address Book controller — checkout UI, AJAX endpoints, My Account tab.
 *
 * Receives a Cecomsmarad_Address_Book_Model instance via constructor injection.
 * The free plugin passes the free model (cap = 2); the premium plugin passes
 * its own model (unlimited when license is valid).
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Address_Book_Controller
 */
class Cecomsmarad_Address_Book_Controller {

	/** Upgrade purchase URL shown when free users hit the 2-address cap. */
	private const UPGRADE_URL = 'https://cecom.in/smarttr-address-turkish-address#pricing';

	/** @var Cecomsmarad_Address_Book_Model */
	private Cecomsmarad_Address_Book_Model $model;

	/**
	 * @param Cecomsmarad_Address_Book_Model $model Injected address book model.
	 */
	public function __construct( Cecomsmarad_Address_Book_Model $model ) {
		$this->model = $model;

		if ( '0' === get_option( 'cecomsmarad_enabled', '1' ) ) {
			return;
		}

		// Address-saving feature toggle (General settings). When off, no checkout
		// fields, AJAX endpoints, or My Account page are registered.
		if ( '0' === get_option( 'cecomsmarad_address_book_enabled', '1' ) ) {
			return;
		}

		// Checkout load dropdown.
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'render_billing_load' ), 5 );
		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'render_shipping_load' ), 5 );

		// Checkout save checkbox.
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'render_billing_save' ) );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'render_shipping_save' ) );

		// Save on order completion (server-side, within WC's checkout AJAX call).
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'maybe_save_address' ), 10, 3 );

		// AJAX — logged-in users only.
		add_action( 'wp_ajax_cecomsmarad_address_book_load', array( $this, 'ajax_load' ) );
		add_action( 'wp_ajax_cecomsmarad_address_book_delete', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_cecomsmarad_address_book_rename', array( $this, 'ajax_rename' ) );
		add_action( 'wp_ajax_cecomsmarad_address_book_update', array( $this, 'ajax_update' ) );

		// My Account.
		add_action( 'init', array( $this, 'register_account_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_account_menu_item' ) );
		add_action( 'woocommerce_account_address-book_endpoint', array( $this, 'render_account_page' ) );

		// Scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// My Account endpoint
	// ──────────────────────────────────────────────────────────────────────────

	public function register_account_endpoint(): void {
		add_rewrite_endpoint( 'address-book', EP_ROOT | EP_PAGES );
	}

	public function add_account_menu_item( array $items ): array {
		if ( ! is_user_logged_in() ) {
			return $items;
		}
		// Insert before logout.
		$logout = $items['customer-logout'] ?? null;
		unset( $items['customer-logout'] );
		$items['address-book'] = __( 'Address Book', 'smarttr-address' );
		if ( null !== $logout ) {
			$items['customer-logout'] = $logout;
		}
		return $items;
	}

	public function render_account_page(): void {
		$user_id     = get_current_user_id();
		$addresses   = $this->model->get_addresses( $user_id );
		$can_save    = $this->model->can_save_more( $user_id );
		$count       = $this->model->get_count( $user_id );
		$upgrade_url = self::UPGRADE_URL;
		include CECOMSMARAD_PLUGIN_DIR . 'includes/views/account/address-book.php';
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Checkout — Load dropdown
	// ──────────────────────────────────────────────────────────────────────────

	public function render_billing_load(): void {
		$this->render_load_for_type( 'billing' );
	}

	public function render_shipping_load(): void {
		$this->render_load_for_type( 'shipping' );
	}

	private function render_load_for_type( string $type ): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$addresses = $this->model->get_addresses( get_current_user_id() );
		if ( empty( $addresses ) ) {
			return;
		}
		include CECOMSMARAD_PLUGIN_DIR . 'includes/views/checkout/address-book-load.php';
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Checkout — Save checkbox
	// ──────────────────────────────────────────────────────────────────────────

	public function render_billing_save(): void {
		$this->render_save_for_type( 'billing' );
	}

	public function render_shipping_save(): void {
		$this->render_save_for_type( 'shipping' );
	}

	private function render_save_for_type( string $type ): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$user_id     = get_current_user_id();
		$can_save    = $this->model->can_save_more( $user_id );
		$upgrade_url = self::UPGRADE_URL;
		include CECOMSMARAD_PLUGIN_DIR . 'includes/views/checkout/address-book-save.php';
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Checkout — Save on order completion
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Save a billing or shipping address when the customer checks "Save this address".
	 *
	 * Fires on woocommerce_checkout_order_processed, which runs server-side inside
	 * WooCommerce's own checkout AJAX call. The checkbox state and nickname arrive as
	 * plain POST fields alongside the rest of the checkout form data.
	 *
	 * @param int   $order_id    Newly created order ID.
	 * @param array $posted_data All posted checkout form data.
	 * @param mixed $order       WC_Order object.
	 */
	public function maybe_save_address( int $order_id, array $posted_data, $order ): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			$save_key = 'cecomsmarad_save_' . $type . '_address';

			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce checkout before woocommerce_checkout_order_processed fires.
			if ( empty( $_POST[ $save_key ] ) ) {
				continue;
			}

			// Province and district are required; skip if missing.
			$province_code = sanitize_text_field( wp_unslash( $posted_data[ $type . '_state' ] ?? '' ) );
			$district_name = sanitize_text_field( wp_unslash( $posted_data[ $type . '_city' ] ?? '' ) );

			if ( empty( $province_code ) || empty( $district_name ) ) {
				continue;
			}

			// Re-verify cap server-side (defence against race condition).
			if ( ! $this->model->can_save_more( $user_id ) ) {
				continue;
			}

			$nickname_raw = sanitize_text_field( wp_unslash( $_POST[ 'cecomsmarad_' . $type . '_nickname' ] ?? '' ) );
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			$nickname     = mb_substr( $nickname_raw ?: ( 'billing' === $type ? __( 'Home', 'smarttr-address' ) : __( 'Work', 'smarttr-address' ) ), 0, 50 );

			// Resolve province name from code.
			$province      = Cecomsmarad_Province::get_by_code( $province_code );
			$province_name = $province ? $province->name : $province_code;

			$this->model->save_address(
				$user_id,
				$nickname,
				$type,
				array(
					'province_code' => $province_code,
					'province_name' => $province_name,
					'district_name' => $district_name,
					'neighborhood'  => sanitize_text_field( wp_unslash( $posted_data[ $type . '_address_2'  ] ?? '' ) ),
					'postcode'      => sanitize_text_field( wp_unslash( $posted_data[ $type . '_postcode'   ] ?? '' ) ),
					'address_1'     => sanitize_text_field( wp_unslash( $posted_data[ $type . '_address_1'  ] ?? '' ) ),
					'first_name'    => sanitize_text_field( wp_unslash( $posted_data[ $type . '_first_name' ] ?? '' ) ),
					'last_name'     => sanitize_text_field( wp_unslash( $posted_data[ $type . '_last_name'  ] ?? '' ) ),
					'company'       => sanitize_text_field( wp_unslash( $posted_data[ $type . '_company'    ] ?? '' ) ),
					'phone'         => 'billing' === $type ? sanitize_text_field( wp_unslash( $posted_data['billing_phone'] ?? '' ) ) : '',
					'email'         => 'billing' === $type ? sanitize_email( wp_unslash( $posted_data['billing_email'] ?? '' ) ) : '',
					'country'       => sanitize_text_field( wp_unslash( $posted_data[ $type . '_country'    ] ?? '' ) ),
				)
			);
		}
	}

	// ──────────────────────────────────────────────────────────────────────────
	// AJAX handlers
	// ──────────────────────────────────────────────────────────────────────────

	public function ajax_load(): void {
		check_ajax_referer( 'cecomsmarad_address_book', 'nonce' );

		$user_id    = get_current_user_id();
		$address_id = absint( $_POST['address_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified via check_ajax_referer above.

		if ( ! $user_id || ! $address_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'smarttr-address' ) ) );
		}

		$address = $this->model->get_address( $address_id, $user_id );

		if ( ! $address ) {
			wp_send_json_error( array( 'message' => __( 'Address not found.', 'smarttr-address' ) ) );
		}

		wp_send_json_success( array(
			'province_code' => $address->province_code,
			'district_name' => $address->district_name,
			'neighborhood'  => $address->neighborhood,
			'postcode'      => $address->postcode,
			'address_1'     => $address->address_1,
			'first_name'    => $address->first_name,
			'last_name'     => $address->last_name,
			'company'       => $address->company,
			'phone'         => $address->phone,
			'email'         => $address->email,
			'country'       => $address->country ?? '',
		) );
	}

	public function ajax_delete(): void {
		check_ajax_referer( 'cecomsmarad_address_book', 'nonce' );

		$user_id    = get_current_user_id();
		$address_id = absint( $_POST['address_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $user_id || ! $address_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'smarttr-address' ) ) );
		}

		if ( $this->model->delete_address( $address_id, $user_id ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => __( 'Address could not be deleted.', 'smarttr-address' ) ) );
		}
	}

	public function ajax_rename(): void {
		check_ajax_referer( 'cecomsmarad_address_book', 'nonce' );

		$user_id    = get_current_user_id();
		$address_id = absint( $_POST['address_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nickname   = mb_substr( sanitize_text_field( wp_unslash( $_POST['nickname'] ?? '' ) ), 0, 50 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $user_id || ! $address_id || ! $nickname ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'smarttr-address' ) ) );
		}

		if ( $this->model->rename_address( $address_id, $user_id, $nickname ) ) {
			wp_send_json_success( array( 'nickname' => $nickname ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Name could not be changed.', 'smarttr-address' ) ) );
		}
	}

	/**
	 * Map posted generic (non-Türkiye) address fields onto the storage columns.
	 *
	 * State → province_code (+ province_name resolved from the WooCommerce state
	 * label when known), City → district_name, Address line 2 → neighborhood.
	 * Pure helper (no WooCommerce / request access) so it is unit-testable.
	 *
	 * @param array $input  Sanitized input. Keys: state, city, address_2.
	 * @param array $states WooCommerce states for the country: code => name.
	 * @return array{province_code:string, province_name:string, district_name:string, neighborhood:string}
	 */
	public static function map_foreign_address( array $input, array $states ): array {
		$state = sanitize_text_field( (string) ( $input['state'] ?? '' ) );
		$city  = sanitize_text_field( (string) ( $input['city'] ?? '' ) );
		$addr2 = sanitize_text_field( (string) ( $input['address_2'] ?? '' ) );

		return array(
			'province_code' => $state,
			'province_name' => ( '' !== $state && isset( $states[ $state ] ) ) ? (string) $states[ $state ] : $state,
			'district_name' => $city,
			'neighborhood'  => $addr2,
		);
	}

	public function ajax_update(): void {
		check_ajax_referer( 'cecomsmarad_address_book', 'nonce' );

		$user_id       = get_current_user_id();
		$address_id    = absint( $_POST['address_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nickname      = mb_substr( sanitize_text_field( wp_unslash( $_POST['nickname'] ?? '' ) ), 0, 50 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$province_code = sanitize_text_field( wp_unslash( $_POST['province_code'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$district_name = sanitize_text_field( wp_unslash( $_POST['district_name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$country      = sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$country_name = WC()->countries->countries[ $country ] ?? $country;
		$is_tr        = ( 'TR' === $country );

		// Türkiye uses the Province → District cascade; every other country uses a
		// single Town/City free-text field (stored in the district_name column).
		if ( $is_tr ) {
			if ( ! $user_id || ! $address_id || ! $nickname || ! $province_code || ! $district_name ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'smarttr-address' ) ) );
			}

			$province      = Cecomsmarad_Province::get_by_code( $province_code );
			$province_name = $province ? $province->name : $province_code;
			$neighborhood  = sanitize_text_field( wp_unslash( $_POST['neighborhood'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$city = sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! $user_id || ! $address_id || ! $nickname || ! $city ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'smarttr-address' ) ) );
			}

			$states = WC()->countries->get_states( $country );
			$states = is_array( $states ) ? $states : array();

			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified via check_ajax_referer at the top of ajax_update.
			$mapped = self::map_foreign_address(
				array(
					'state'     => sanitize_text_field( wp_unslash( $_POST['state'] ?? '' ) ),
					'city'      => $city,
					'address_2' => sanitize_text_field( wp_unslash( $_POST['address_2'] ?? '' ) ),
				),
				$states
			);
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$province_code = $mapped['province_code'];
			$province_name = $mapped['province_name'];
			$district_name = $mapped['district_name'];
			$neighborhood  = $mapped['neighborhood'];
		}

		$data = array(
			'province_code' => $province_code,
			'province_name' => $province_name,
			'district_name' => $district_name,
			'neighborhood'  => $neighborhood,
			'postcode'      => sanitize_text_field( wp_unslash( $_POST['postcode'] ?? '' ) ),      // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'address_1'     => sanitize_text_field( wp_unslash( $_POST['address_1'] ?? '' ) ),     // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'first_name'    => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),    // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'last_name'     => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),     // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'company'       => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),       // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'phone'         => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),         // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'email'         => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),              // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'country'       => $country,
		);

		if ( $this->model->update_address( $address_id, $user_id, $nickname, $data ) ) {
			wp_send_json_success( array(
				'nickname'      => $nickname,
				'province_name' => $province_name,
				'district_name' => $district_name,
				'neighborhood'  => $data['neighborhood'],
				'postcode'      => $data['postcode'],
				'address_1'     => $data['address_1'],
				'first_name'    => $data['first_name'],
				'last_name'     => $data['last_name'],
				'company'       => $data['company'],
				'country'       => $country,
				'country_name'  => $country_name,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Address could not be updated.', 'smarttr-address' ) ) );
		}
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Scripts
	// ──────────────────────────────────────────────────────────────────────────

	public function enqueue_scripts(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$on_checkout     = function_exists( 'is_checkout' ) && is_checkout();
		$on_account      = function_exists( 'is_account_page' ) && is_account_page();
		$on_edit_address = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'edit-address' );

		if ( ! $on_checkout && ! $on_account ) {
			return;
		}

		// My Account → Edit Address uses WooCommerce defaults; skip address-book scripts there.
		if ( $on_edit_address ) {
			return;
		}

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$js_file = CECOMSMARAD_PLUGIN_DIR . 'assets/js/cecomsmarad-address-book' . $suffix . '.js';

		if ( ! file_exists( $js_file ) ) {
			return;
		}

		// On checkout and account page, depend on selectWoo for cascade selects.
		$deps = array( 'jquery' );
		if ( $on_checkout ) {
			$deps[] = 'cecomsmarad-checkout';
		}
		if ( $on_checkout || $on_account ) {
			$deps[] = 'selectWoo';
		}

		wp_enqueue_script(
			'cecomsmarad-address-book',
			CECOMSMARAD_PLUGIN_URL . 'assets/js/cecomsmarad-address-book' . $suffix . '.js',
			$deps,
			(string) filemtime( $js_file ),
			true
		);

		$user_id = get_current_user_id();

		wp_localize_script(
			'cecomsmarad-address-book',
			'smarttrAddressBook',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'cecomsmarad_address_book' ),
				'addresses'   => $this->get_addresses_for_js( $user_id ),
				'canSaveMore' => $this->model->can_save_more( $user_id ),
				'upgradeUrl'  => self::UPGRADE_URL,
				'provinces'   => $on_account ? $this->get_provinces_for_js() : array(),
				'districts'   => $on_account ? $this->get_districts_for_js() : array(),
				'states'      => $on_account ? WC()->countries->get_states() : array(),
				'locale'      => $on_account ? WC()->countries->get_country_locale() : array(),
				'i18n'        => array(
					'confirmDelete'    => __( 'Are you sure you want to delete this address?', 'smarttr-address' ),
					'defaultNicknameB' => __( 'Home', 'smarttr-address' ),
					'defaultNicknameS' => __( 'Work', 'smarttr-address' ),
					'yes'              => __( 'Yes', 'smarttr-address' ),
					'no'               => __( 'No', 'smarttr-address' ),
					'selectProvince'      => __( 'Select province', 'smarttr-address' ),
					'selectDistrict'      => __( 'Select district', 'smarttr-address' ),
					'selectNeighborhood'  => __( 'Select neighborhood', 'smarttr-address' ),
					'selectState'         => __( 'Select an option…', 'smarttr-address' ),
				),
			)
		);
	}

	private function get_provinces_for_js(): array {
		$provinces = Cecomsmarad_Province::get_all();
		$out       = array();
		foreach ( $provinces as $p ) {
			$out[] = array(
				'code' => $p->code,
				'name' => $p->name,
			);
		}
		return $out;
	}

	private function get_districts_for_js(): array {
		$provinces = Cecomsmarad_Province::get_all();
		$out       = array();
		foreach ( $provinces as $p ) {
			$districts = Cecomsmarad_District::get_by_province( $p->code );
			$list      = array();
			foreach ( $districts as $d ) {
				$list[] = array(
					'id'   => (int) $d->id,
					'name' => $d->name,
				);
			}
			$out[ $p->code ] = $list;
		}
		return $out;
	}

	private function get_addresses_for_js( int $user_id ): array {
		$out = array();
		foreach ( $this->model->get_addresses( $user_id ) as $address ) {
			$out[] = array(
				'id'            => (int) $address->id,
				'nickname'      => $address->nickname,
				'address_type'  => $address->address_type,
				'province_code' => $address->province_code,
				'district_name' => $address->district_name,
				'neighborhood'  => $address->neighborhood,
				'postcode'      => $address->postcode,
				'address_1'     => $address->address_1,
				'first_name'    => $address->first_name,
				'last_name'     => $address->last_name,
				'company'       => $address->company,
				'phone'         => $address->phone,
				'email'         => $address->email,
				'country'       => $address->country ?? '',
			);
		}
		return $out;
	}
}
