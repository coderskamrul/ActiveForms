<?php
/**
 * EasyForms uninstall routine.
 *
 * Only removes data when the user opted in via the "Delete all data on
 * uninstall" setting. Otherwise form/entry data is preserved.
 *
 * @package EasyForms
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$easyforms_settings = get_option( 'easyforms_settings', array() );

if ( empty( $easyforms_settings['remove_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Drop custom tables.
$easyforms_tables = array(
	'easyforms_forms',
	'easyforms_form_meta',
	'easyforms_entries',
	'easyforms_entry_meta',
	'easyforms_entry_details',
	'easyforms_logs',
	'easyforms_scheduled_actions',
);

foreach ( $easyforms_tables as $easyforms_table ) {
	$easyforms_name = $wpdb->prefix . $easyforms_table;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- uninstall cleanup.
	$wpdb->query( "DROP TABLE IF EXISTS {$easyforms_name}" );
}

// Delete options.
delete_option( 'easyforms_settings' );
delete_option( 'easyforms_db_version' );
delete_option( 'easyforms_installed_at' );

// Delete integration option rows (easyforms_integration_*).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'easyforms_integration_%'" );

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'easyforms_process_scheduled_actions' );
