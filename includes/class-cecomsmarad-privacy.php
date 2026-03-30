<?php
/**
 * Privacy compliance — personal data exporter and eraser.
 *
 * Registers callbacks with WordPress's built-in privacy tools (introduced
 * in WP 4.9.6) so administrators can export or erase SmartTR Address data
 * (Turkish address fields) for any WooCommerce
 * customer via Tools → Export Personal Data / Erase Personal Data.
 *
 * @package CecomsmaradAddress
 * @see https://developer.wordpress.org/plugins/privacy/
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Privacy
 *
 * Implements the two WordPress privacy hooks:
 *   - wp_privacy_personal_data_exporters
 *   - wp_privacy_personal_data_erasers
 *
 * Data covered:
 *   - Turkish address meta stored on orders (_billing_cecomsmarad_* / _shipping_cecomsmarad_*)
 */
class Cecomsmarad_Privacy {

	/**
	 * Register privacy exporter, eraser, and policy content hooks.
	 *
	 * Called from plugins_loaded so translations are already available.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'register_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'register_erasers' ) );
		add_action( 'admin_init', array( self::class, 'add_privacy_policy_content' ) );
	}

	/**
	 * Add a suggested privacy policy snippet to the WordPress privacy policy guide.
	 *
	 * @return void
	 */
	public static function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = wp_kses_post(
			'<p>' .
			__( 'This plugin collects and stores Turkish address information (province, district) entered during the WooCommerce checkout process. This data is saved in WooCommerce order records and is used solely to process and fulfil orders.', 'smarttr-address' ) .
			'</p><p>' .
			__( 'All collected address data can be exported or permanently erased via the WordPress Tools → Export Personal Data and Erase Personal Data screens.', 'smarttr-address' ) .
			'</p>'
		);

		wp_add_privacy_policy_content( 'SmartTR Address', $content );
	}

	/**
	 * Register the SmartTR Address personal data exporter.
	 *
	 * @param array[] $exporters Existing registered exporters.
	 * @return array[] Modified exporters array.
	 */
	public static function register_exporters( array $exporters ): array {
		$exporters['smarttr-address'] = array(
			'exporter_friendly_name' => __( 'SmartTR Address Data', 'smarttr-address' ),
			'callback'               => array( self::class, 'export_customer_data' ),
		);

		return $exporters;
	}

	/**
	 * Register the SmartTR Address personal data eraser.
	 *
	 * @param array[] $erasers Existing registered erasers.
	 * @return array[] Modified erasers array.
	 */
	public static function register_erasers( array $erasers ): array {
		$erasers['smarttr-address'] = array(
			'eraser_friendly_name' => __( 'SmartTR Address Data', 'smarttr-address' ),
			'callback'             => array( self::class, 'erase_customer_data' ),
		);

		return $erasers;
	}

	/**
	 * Export SmartTR Address order data for a given customer email.
	 *
	 * Includes:
	 *   - Turkish province / district per order
	 *
	 * @param string $email Customer email address.
	 * @param int    $page  Pagination page (1-based).
	 * @return array{data: array, done: bool}
	 */
	public static function export_customer_data( string $email, int $page = 1 ): array {
		$per_page       = 20;
		$data_to_export = array();

		$orders = wc_get_orders(
			array(
				'billing_email' => sanitize_email( $email ),
				'limit'         => $per_page,
				'paged'         => $page,
			)
		);

		foreach ( $orders as $order ) {
			$order_data = array();

			// Turkish address meta (billing and shipping).
			foreach ( array( 'billing', 'shipping' ) as $addr_type ) {
				$prefix = '_' . $addr_type . '_cecomsmarad_';

				$province     = $order->get_meta( $prefix . 'province_name' );
				$district     = $order->get_meta( $prefix . 'district_name' );

				if ( $province ) {
					$order_data[] = array(
						/* translators: %s: address type (Billing or Shipping) */
						'name'  => sprintf( __( '%s Province', 'smarttr-address' ), ucfirst( $addr_type ) ),
						'value' => $province,
					);
				}
				if ( $district ) {
					$order_data[] = array(
						/* translators: %s: address type (Billing or Shipping) */
						'name'  => sprintf( __( '%s District', 'smarttr-address' ), ucfirst( $addr_type ) ),
						'value' => $district,
					);
				}
			}

			if ( ! empty( $order_data ) ) {
				$data_to_export[] = array(
					'group_id'    => 'cecomsmarad-order-' . $order->get_id(),
					'group_label' => sprintf(
						/* translators: %s: WooCommerce order number */
						__( 'SmartTR Address — Order #%s', 'smarttr-address' ),
						$order->get_order_number()
					),
					'item_id'     => 'order-' . $order->get_id(),
					'data'        => $order_data,
				);
			}
		}

		return array(
			'data' => $data_to_export,
			'done' => count( $orders ) < $per_page,
		);
	}

	/**
	 * Erase SmartTR Address order data for a given customer email.
	 *
	 * Removes:
	 *   - Turkish province / district meta from all orders
	 *
	 * @param string $email Customer email address.
	 * @param int    $page  Pagination page (1-based).
	 * @return array{items_removed: bool, items_retained: bool, messages: array, done: bool}
	 */
	public static function erase_customer_data( string $email, int $page = 1 ): array {
		$per_page      = 20;
		$items_removed = false;

		$orders = wc_get_orders(
			array(
				'billing_email' => sanitize_email( $email ),
				'limit'         => $per_page,
				'paged'         => $page,
			)
		);

		foreach ( $orders as $order ) {
			$changed = false;

			// Erase Turkish address meta.
			foreach ( array( 'billing', 'shipping' ) as $addr_type ) {
				$prefix = '_' . $addr_type . '_cecomsmarad_';
				foreach ( array( 'province_code', 'province_name', 'district_name'
						) as $suffix ) {
					if ( '' !== $order->get_meta( $prefix . $suffix ) ) {
						$order->delete_meta_data( $prefix . $suffix );
						$changed       = true;
						$items_removed = true;
					}
				}
			}

			if ( $changed ) {
				$order->save();
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $orders ) < $per_page,
		);
	}
}
