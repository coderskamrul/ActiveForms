<?php
/**
 * Deactivation routine (non-destructive).
 *
 * @package EasyForms
 */

namespace EasyForms\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Clears scheduled events and transient caches only. No data is removed.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'easyforms_process_scheduled_actions' );
		delete_transient( 'easyforms_dashboard_stats' );
	}
}
