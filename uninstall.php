<?php
/**
 * CecomsmaradAddress uninstall handler.
 *
 * Removes all plugin data: custom tables, options, and transients.
 * Fires only when the plugin is deleted through the WordPress admin.
 *
 * @package CecomsmaradAddress
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

/*
 * Cancel all scheduled Action Scheduler actions registered by this plugin.
 * Action Scheduler is bundled with WooCommerce and must be cleaned up separately
 * from wp_options since it stores actions in its own tables.
 */
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'cecomsmarad_do_address_sync', array(), '' );
}

/*
 * Drop custom tables in reverse dependency order.
 *
 * Table names are hardcoded — no user input is interpolated.
 */

$cecomsmarad_tables = array(
	$wpdb->prefix . 'cecomsmarad_address_book',
	$wpdb->prefix . 'cecomsmarad_districts',
	$wpdb->prefix . 'cecomsmarad_provinces',
);

foreach ( $cecomsmarad_tables as $cecomsmarad_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall cleanup: dropping plugin-owned custom tables; table names are hardcoded above.
	$wpdb->query( "DROP TABLE IF EXISTS {$cecomsmarad_table}" );
}

/*
 * Delete all cecomsmarad_* options.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup: bulk-delete all plugin options.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'cecomsmarad_' ) . '%'
	)
);

/*
 * Delete all cecomsmarad_* transients.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup: bulk-delete all plugin transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_cecomsmarad_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_cecomsmarad_' ) . '%'
	)
);
