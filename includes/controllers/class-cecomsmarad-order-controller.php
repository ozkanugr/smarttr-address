<?php
/**
 * Order controller — validation, meta save, address formatting.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Order_Controller
 *
 * Handles Turkish address data throughout the WooCommerce order lifecycle:
 * checkout validation, HPOS-compatible meta storage (ADR-004 dual code+name),
 * and formatted address output for emails.
 */
class Cecomsmarad_Order_Controller {

	/**
	 * Register all hooks.
	 */
	public function __construct() {
		// Validation.
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_turkish_fields' ), 10, 2 );

		// Order meta save.
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_order_meta' ), 10, 2 );

		// Formatted address (emails, thank-you page, order view).
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'format_billing_address' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'format_shipping_address' ), 10, 2 );
	}

	/**
	 * Validate Turkish address fields before order creation.
	 *
	 * Adds WP_Error entries when required fields are empty for Turkey orders.
	 * Non-TR orders are skipped entirely.
	 *
	 * @param array     $posted_data Checkout POST data.
	 * @param \WP_Error $errors      Error accumulator.
	 * @return void
	 */
	public function validate_turkish_fields( array $posted_data, \WP_Error $errors ): void {
		$this->validate_address_type( 'billing', $posted_data, $errors );

		if ( ! empty( $posted_data['ship_to_different_address'] ) ) {
			$this->validate_address_type( 'shipping', $posted_data, $errors );
		}
	}

	/**
	 * Validate fields for a single address type.
	 *
	 * @param string    $type        'billing' or 'shipping'.
	 * @param array     $posted_data Checkout POST data.
	 * @param \WP_Error $errors      Error accumulator.
	 * @return void
	 */
	private function validate_address_type( string $type, array $posted_data, \WP_Error $errors ): void {
		$country = $posted_data[ $type . '_country' ] ?? '';

		if ( 'TR' !== $country ) {
			return;
		}

		$settings = Cecomsmarad_Settings::get_fields();

		$state_vis = $settings[ $type . '_state' ]['visibility'] ?? 'visible';
		if ( 'visible' === $state_vis && empty( $posted_data[ $type . '_state' ] ) ) {
			$errors->add(
				'cecomsmarad_' . $type . '_province',
				/* translators: %s: address type label (Billing or Shipping). */
				sprintf( __( '%s province is required.', 'smarttr-address' ), $this->get_type_label( $type ) )
			);
		} elseif ( 'visible' === $state_vis && ! empty( $posted_data[ $type . '_state' ] ) ) {
			// Validate submitted province code against the database.
			// Only reject if province data has been imported — skip silently when
			// the table is empty (e.g., first request after activation before sync).
			//
			// cecomsmarad_record_counts is stored as a JSON-encoded string by
			// Cecomsmarad_Data_Importer::store_metadata(), so it must be decoded
			// before array key access.
			$record_counts_raw = (string) get_option( 'cecomsmarad_record_counts', '{}' );
			$record_counts     = json_decode( $record_counts_raw, true );
			$province_count    = (int) ( ( is_array( $record_counts ) ? $record_counts : array() )['provinces'] ?? 0 );

			if ( $province_count > 0 ) {
				$province_code = sanitize_text_field( $posted_data[ $type . '_state' ] );

				if ( ! Cecomsmarad_Province::get_by_code( $province_code ) ) {
					$errors->add(
						'cecomsmarad_' . $type . '_province_invalid',
						/* translators: %s: address type label. */
						sprintf( __( '%s province is not valid.', 'smarttr-address' ), $this->get_type_label( $type ) )
					);
				}
			}
		}

		$city_vis = $settings[ $type . '_city' ]['visibility'] ?? 'visible';
		if ( 'visible' === $city_vis && empty( $posted_data[ $type . '_city' ] ) ) {
			$errors->add(
				'cecomsmarad_' . $type . '_district',
				/* translators: %s: address type label. */
				sprintf( __( '%s district is required.', 'smarttr-address' ), $this->get_type_label( $type ) )
			);
		}

		$addr2_vis = $settings[ $type . '_address_2' ]['visibility'] ?? 'visible';
		$addr2_req = $settings[ $type . '_address_2' ]['required'] ?? true;
		if ( 'visible' === $addr2_vis && $addr2_req && empty( $posted_data[ $type . '_address_2' ] ) ) {
			$errors->add(
				'cecomsmarad_' . $type . '_neighborhood',
				/* translators: %s: address type label. */
				sprintf( __( '%s neighborhood is required.', 'smarttr-address' ), $this->get_type_label( $type ) )
			);
		}
	}

	/**
	 * Save Turkish address meta to the order (HPOS-compatible).
	 *
	 * Stores both province code and name (ADR-004) plus district name,
	 * neighborhood name, and postal code for both billing and shipping.
	 *
	 * @param int   $order_id    WooCommerce order ID.
	 * @param array $posted_data Checkout POST data.
	 * @return void
	 */
	public function save_order_meta( int $order_id, array $posted_data ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->save_address_meta( $order, 'billing', $posted_data );

		if ( ! empty( $posted_data['ship_to_different_address'] ) ) {
			$this->save_address_meta( $order, 'shipping', $posted_data );
		}

		$order->save();
	}

	/**
	 * Save address meta for a single address type.
	 *
	 * @param \WC_Order $order       Order object.
	 * @param string    $type        'billing' or 'shipping'.
	 * @param array     $posted_data Checkout POST data.
	 * @return void
	 */
	private function save_address_meta( \WC_Order $order, string $type, array $posted_data ): void {
		$country = $posted_data[ $type . '_country' ] ?? '';

		if ( 'TR' !== $country ) {
			return;
		}

		$province_code     = sanitize_text_field( $posted_data[ $type . '_state' ] ?? '' );
		$district_name     = sanitize_text_field( $posted_data[ $type . '_city' ] ?? '' );
		$neighborhood_name = sanitize_text_field( $posted_data[ $type . '_address_2' ] ?? '' );
		$post_code         = sanitize_text_field( $posted_data[ $type . '_postcode' ] ?? '' );

		// Resolve province name from code.
		$province_name = '';
		if ( $province_code ) {
			$province = Cecomsmarad_Province::get_by_code( $province_code );
			if ( $province ) {
				$province_name = $province->name;
			}
		}

		$prefix = '_' . $type . '_cecomsmarad_';

		$order->update_meta_data( $prefix . 'province_code', $province_code );
		$order->update_meta_data( $prefix . 'province_name', $province_name );
		$order->update_meta_data( $prefix . 'district_name', $district_name );
		$order->update_meta_data( $prefix . 'neighborhood_name', $neighborhood_name );
		$order->update_meta_data( $prefix . 'post_code', $post_code );
	}

	/**
	 * Inject province name and district name into the formatted billing address.
	 *
	 * @param array     $address Formatted address parts.
	 * @param \WC_Order $order   Order object.
	 * @return array Modified address parts.
	 */
	public function format_billing_address( array $address, \WC_Order $order ): array {
		return $this->inject_address_names( $address, $order, 'billing' );
	}

	/**
	 * Inject province name and district name into the formatted shipping address.
	 *
	 * @param array     $address Formatted address parts.
	 * @param \WC_Order $order   Order object.
	 * @return array Modified address parts.
	 */
	public function format_shipping_address( array $address, \WC_Order $order ): array {
		return $this->inject_address_names( $address, $order, 'shipping' );
	}

	/**
	 * Replace state code and city with human-readable names in formatted address.
	 *
	 * WooCommerce uses {state} and {city} placeholders. By default {state}
	 * contains the province code (TR34). This replaces it with the province
	 * name (İstanbul) and ensures {city} holds the district name.
	 *
	 * @param array     $address Address parts array.
	 * @param \WC_Order $order   Order object.
	 * @param string    $type    'billing' or 'shipping'.
	 * @return array Modified address parts.
	 */
	private function inject_address_names( array $address, \WC_Order $order, string $type ): array {
		$prefix = '_' . $type . '_cecomsmarad_';

		$province_name = $order->get_meta( $prefix . 'province_name' );
		if ( $province_name ) {
			$address['state'] = $province_name;
		}

		$district_name = $order->get_meta( $prefix . 'district_name' );
		if ( $district_name ) {
			$address['city'] = $district_name;
		}

		$neighborhood_name = $order->get_meta( $prefix . 'neighborhood_name' );
		if ( $neighborhood_name ) {
			$address['address_2'] = $neighborhood_name;
		}

		return $address;
	}

	/**
	 * Get human-readable label for an address type.
	 *
	 * @param string $type 'billing' or 'shipping'.
	 * @return string Translated label.
	 */
	private function get_type_label( string $type ): string {
		return 'shipping' === $type
			? __( 'Shipping', 'smarttr-address' )
			: __( 'Billing', 'smarttr-address' );
	}
}
