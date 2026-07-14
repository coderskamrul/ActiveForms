<?php
/**
 * RadiusForms uninstall routine.
 *
 * Only removes data when the user opted in via the "Delete all data on
 * uninstall" setting. Otherwise form/entry data is preserved.
 *
 * @package RadiusForms
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$radiusforms_settings = get_option( 'radiusforms_settings', array() );

if ( empty( $radiusforms_settings['remove_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Drop custom tables.
$radiusforms_tables = array(
	'radiusforms_forms',
	'radiusforms_form_meta',
	'radiusforms_entries',
	'radiusforms_entry_meta',
	'radiusforms_entry_details',
	'radiusforms_logs',
	'radiusforms_scheduled_actions',
);

foreach ( $radiusforms_tables as $radiusforms_table ) {
	$radiusforms_name = $wpdb->prefix . $radiusforms_table;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- uninstall cleanup.
	$wpdb->query( "DROP TABLE IF EXISTS {$radiusforms_name}" );
}

// Delete options.
delete_option( 'radiusforms_settings' );
delete_option( 'radiusforms_db_version' );
delete_option( 'radiusforms_installed_at' );

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'radiusforms_process_scheduled_actions' );
