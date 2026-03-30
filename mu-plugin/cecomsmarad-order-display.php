<?php
/**
 * SmartTR Address — Order Display Fallback
 *
 * This must-use plugin ensures custom field data saved by SmartTR Address
 * remains visible in WooCommerce admin order pages even after the main plugin
 * is deactivated or deleted.
 *
 * It is installed automatically when SmartTR Address is activated.
 * Safe to remove manually if you no longer need legacy SmartTR field display.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	static function () {
		// Defer to the main plugin when active — it registers its own display hooks.
		if ( class_exists( 'Cecomsmarad_Order_Controller' ) ) {
			return;
		}

		add_action( 'woocommerce_admin_order_data_after_billing_address', 'cecomsmarad_fallback_display_billing' );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', 'cecomsmarad_fallback_display_shipping' );
	},
	20
);

/**
 * Display fallback billing fields on the admin order page.
 *
 * @param WC_Order $order Order object.
 */
function cecomsmarad_fallback_display_billing( WC_Order $order ): void {
	cecomsmarad_fallback_display_fields( $order, 'billing' );
}

/**
 * Display fallback shipping fields on the admin order page.
 *
 * @param WC_Order $order Order object.
 */
function cecomsmarad_fallback_display_shipping( WC_Order $order ): void {
	cecomsmarad_fallback_display_fields( $order, 'shipping' );
}

/**
 * Render custom fields for a given address type from the per-order snapshot.
 *
 * Reads the `_cecomsmarad_fields_snapshot` meta stored at checkout time. This meta
 * is intentionally kept in `_wc_orders_meta` and is never removed on uninstall,
 * so the data remains accessible regardless of plugin state.
 *
 * @param WC_Order $order Order object.
 * @param string   $type  'billing' or 'shipping'.
 */
function cecomsmarad_fallback_display_fields( WC_Order $order, string $type ): void {
	$snapshot_json = $order->get_meta( '_cecomsmarad_fields_snapshot' );
	if ( empty( $snapshot_json ) ) {
		return;
	}

	$snapshot = json_decode( $snapshot_json, true );
	if ( ! is_array( $snapshot ) ) {
		return;
	}

	foreach ( $snapshot as $key => $cf ) {
		if ( ( $cf['address_type'] ?? '' ) !== $type ) {
			continue;
		}

		if ( empty( $cf['show_in_order'] ) ) {
			continue;
		}

		$value = $order->get_meta( '_' . $key );
		if ( '' === $value || false === $value ) {
			continue;
		}

		$label = $cf['label'] ?? $key;

		if ( 'file' === ( $cf['type'] ?? '' ) ) {
			$att_id   = (int) $order->get_meta( '_' . $key . '_attachment_id' );
			$edit_url = $att_id > 0 ? get_edit_post_link( $att_id ) : '';
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ';
			echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( basename( $value ) ) . '</a>';
			if ( $edit_url ) {
				echo ' (<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'View in Media', 'smarttr-address' ) . '</a>)';
			}
			echo '</p>';
		} else {
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
		}
	}
}
