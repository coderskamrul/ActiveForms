<?php
/**
 * ActiveForms uninstall routine.
 *
 * Only removes data when the user opted in via the "Delete all data on
 * uninstall" setting. Otherwise form/entry data is preserved.
 *
 * @package ActiveForms
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$activeforms_settings = get_option( 'activeforms_settings', array() );

if ( empty( $activeforms_settings['remove_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Drop custom tables.
$activeforms_tables = array(
	'activeforms_forms',
	'activeforms_form_meta',
	'activeforms_entries',
	'activeforms_entry_meta',
	'activeforms_entry_details',
	'activeforms_logs',
	'activeforms_scheduled_actions',
);

foreach ( $activeforms_tables as $activeforms_table ) {
	$activeforms_name = $wpdb->prefix . $activeforms_table;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- uninstall cleanup.
	$wpdb->query( "DROP TABLE IF EXISTS {$activeforms_name}" );
}

// Delete options.
delete_option( 'activeforms_settings' );
delete_option( 'activeforms_db_version' );
delete_option( 'activeforms_installed_at' );

// Delete integration option rows (activeforms_integration_*).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'activeforms_integration_%'" );

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'activeforms_process_scheduled_actions' );
