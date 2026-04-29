<?php
/**
 * Plugin Name:       SmartTR Address
 * Plugin URI:        https://cecom.in/smarttr-address-turkish-address
 * Description:       Turkish address auto-fill for WooCommerce checkout — cascading Province & District dropdowns.
 * Version:           1.4.1
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            ugurozkan
 * Author URI:        https://cecom.in
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smarttr-address
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.6
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 */
define( 'CECOMSMARAD_VERSION', '1.4.1' );
define( 'CECOMSMARAD_PLUGIN_FILE', __FILE__ );
define( 'CECOMSMARAD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CECOMSMARAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CECOMSMARAD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Base URL of the cecom-address-tr installation that serves address data.
 * Address data is fetched from this endpoint via the secure REST API.
 */
define( 'CECOMSMARAD_DATA_SOURCE_URL', 'https://cecom.in' );

/**
 * WooCommerce REST API credentials for the cecom-address-tr data source.
 * Override in wp-config.php to use your own credentials:
 *   define( 'CECOMSMARAD_API_CONSUMER_KEY', 'ck_your_key' );
 *   define( 'CECOMSMARAD_API_CONSUMER_SECRET', 'cs_your_secret' );
 */
if ( ! defined( 'CECOMSMARAD_API_CONSUMER_KEY' ) ) {
	define( 'CECOMSMARAD_API_CONSUMER_KEY', 'ck_1268112fa2c5b64bd00c25cc313d5da805495f73' );
}
if ( ! defined( 'CECOMSMARAD_API_CONSUMER_SECRET' ) ) {
	define( 'CECOMSMARAD_API_CONSUMER_SECRET', 'cs_8050fef913fdcd7e8d8e9972ecc8bc2981849a73' );
}

/**
 * Autoloader — load Composer when available, always register manual autoloader for plugin classes.
 */
if ( file_exists( CECOMSMARAD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CECOMSMARAD_PLUGIN_DIR . 'vendor/autoload.php';
}
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/class-cecomsmarad-autoloader.php';
Cecomsmarad_Autoloader::register();

/**
 * Declare HPOS (High-Performance Order Storage) compatibility.
 *
 * Must run on `before_woocommerce_init` — before WC initializes.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Plugin activation.
 */
register_activation_hook( CECOMSMARAD_PLUGIN_FILE, array( 'Cecomsmarad_Activator', 'activate' ) );

/**
 * Plugin deactivation.
 */
register_deactivation_hook( CECOMSMARAD_PLUGIN_FILE, array( 'Cecomsmarad_Deactivator', 'deactivate' ) );

/**
 * Boot the plugin after all plugins are loaded.
 */
add_action(
	'plugins_loaded',
	static function () {
		$i18n = new Cecomsmarad_I18n();
		$i18n->load_plugin_textdomain();

		new Cecomsmarad_Admin_Controller();
		new Cecomsmarad_Ecosystem_Controller();
		new Cecomsmarad_Checkout_Controller();
		new Cecomsmarad_Order_Controller();
		Cecomsmarad_Privacy::register_hooks();
	}
);

/**
 * Fallback sync: if WP-Cron failed to run the pending sync, trigger it
 * directly when the admin visits the SmartTR settings page.
 */
add_action(
	'admin_init',
	static function () {
		// Direct fallback: run sync when admin visits our settings page and sync is still pending.
		if (
			current_user_can( 'manage_woocommerce' )
			&& Cecomsmarad_Remote_Sync::is_sync_pending()
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing; no data is modified based on this value.
			&& isset( $_GET['page'] )
			&& 'cecomsmarad-settings' === sanitize_key( wp_unslash( $_GET['page'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ! get_transient( 'cecomsmarad_sync_running' )
		) {
			set_transient( 'cecomsmarad_sync_running', '1', 10 * MINUTE_IN_SECONDS );
			Cecomsmarad_Remote_Sync::sync();
			delete_transient( 'cecomsmarad_sync_running' );
		}
	}
);

/**
 * WP-Cron: run the background address data sync scheduled on activation.
 */
add_action( 'cecomsmarad_do_address_sync', array( 'Cecomsmarad_Remote_Sync', 'sync' ) );

/**
 * Plugin action links — adds Settings, Docs, and (free-tier only) Upgrade
 * links to the plugin row on the Plugins admin page.
 */
add_filter(
	'plugin_action_links_' . CECOMSMARAD_PLUGIN_BASENAME,
	static function ( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=cecomsmarad-settings' ) ),
			esc_html__( 'Settings', 'smarttr-address' )
		);

		$docs_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill' ),
			esc_html__( 'Docs', 'smarttr-address' )
		);

		// Prepend Settings and Docs so they appear before Deactivate.
		array_unshift( $links, $docs_link );
		array_unshift( $links, $settings_link );

		$upgrade_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#00a32a;font-weight:600;">%s</a>',
			esc_url( 'https://cecom.in/smarttr-address-turkish-address#pricing' ),
			esc_html__( 'Upgrade', 'smarttr-address' )
		);

		// Append Upgrade after Settings and Docs but before Deactivate.
		array_splice( $links, 2, 0, array( $upgrade_link ) );

		return $links;
	}
);

if ( is_admin() ) {

	/**
	 * Plugin row meta — fetches the WordPress.org rating (cached 12 h) and
	 * appends star icons inline to the plugin-version-author-uri row.
	 * WordPress core already supplies the "View details" link for .org-hosted
	 * plugins, so no duplicate is added here.
	 */
	add_filter(
		'plugin_row_meta',
		static function ( array $links, string $plugin_file ): array {
			if ( CECOMSMARAD_PLUGIN_BASENAME !== $plugin_file ) {
				return $links;
			}

			$transient_key = 'cecomsmarad_wporg_rating';
			$data          = get_transient( $transient_key );

			// Discard any cached value that is not an array (e.g. stale string from a prior run).
			if ( false !== $data && ! is_array( $data ) ) {
				delete_transient( $transient_key );
				$data = false;
			}

			if ( false === $data ) {
				$response = wp_remote_get(
					'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information'
					. '&request[slug]=smarttr-address'
					. '&request[fields][rating]=1'
					. '&request[fields][num_ratings]=1',
					array( 'timeout' => 5 )
				);

				if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
					$body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( isset( $body['rating'], $body['num_ratings'] ) ) {
						$data = array(
							'rating'      => (float) $body['rating'],
							'num_ratings' => (int) $body['num_ratings'],
						);
					} else {
						$data = array();
					}
				} else {
					$data = array();
				}

				set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
			}

			if ( is_array( $data ) && ! empty( $data ) ) {
				$rating      = (float) $data['rating'];
				$full_stars  = (int) floor( $rating / 20 );
				$half_star   = ( ( $rating / 20 ) - $full_stars ) >= 0.5 ? 1 : 0;
				$empty_stars = 5 - $full_stars - $half_star;
				$stars_html  = '';
				$review_url  = 'https://wordpress.org/support/plugin/smarttr-address/reviews/';
				for ( $i = 0; $i < $full_stars; $i++ ) {
					$stars_html .= '<span class="dashicons dashicons-star-filled" style="color:#ffb900;font-size:16px;width:16px;height:16px;" aria-hidden="true"></span>';
				}
				if ( $half_star ) {
					$stars_html .= '<span class="dashicons dashicons-star-half" style="color:#ffb900;font-size:16px;width:16px;height:16px;" aria-hidden="true"></span>';
				}
				for ( $i = 0; $i < $empty_stars; $i++ ) {
					$stars_html .= '<span class="dashicons dashicons-star-empty" style="color:#ffb900;font-size:16px;width:16px;height:16px;" aria-hidden="true"></span>';
				}

				$links[] = '<a href="' . esc_url( $review_url . '#new-post' ) . '" target="_blank" rel="noopener noreferrer">'
					. esc_html__( 'Add Review', 'smarttr-address' ) . '</a>';

				$links[] = wp_kses(
					'<a href="' . esc_url( $review_url ) . '" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">'
						. $stars_html . '</a>',
					array(
						'a'    => array(
							'href'   => true,
							'target' => true,
							'rel'    => true,
							'style'  => true,
						),
						'span' => array(
							'class'       => true,
							'style'       => true,
							'aria-hidden' => true,
						),
					)
				);
			}

			return $links;
		},
		10,
		2
	);

} // end is_admin()

/**
 * Admin notice: shown when a data sync is pending (scheduled but not yet complete).
 */
add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! Cecomsmarad_Remote_Sync::is_sync_pending() ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=cecomsmarad-settings&tab=data' );
		printf(
			'<div class="notice notice-info"><p><strong>SmartTR Address:</strong> %s <a href="%s">%s</a></p></div>',
			esc_html__( 'Address data sync is pending. Data is being loaded in the background. If not completed, you can start it manually from the Data Management tab:', 'smarttr-address' ),
			esc_url( $settings_url ),
			esc_html__( 'Data Management', 'smarttr-address' )
		);
	}
);
