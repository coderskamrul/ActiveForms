<?php
/**
 * Database-backed logger for integration/API events.
 *
 * @package EasyForms
 */

namespace EasyForms\Support;

use EasyForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Writes structured log rows used by the entries "API logs" view.
 */
class Logger {

	/**
	 * Record a log entry.
	 *
	 * @param string $component Component name (e.g. integration slug).
	 * @param string $status    success|failed|info.
	 * @param string $title     Short title.
	 * @param string $message   Detail message.
	 * @param array  $context   Optional form_id / entry_id.
	 * @return void
	 */
	public function log( $component, $status, $title, $message = '', $context = array() ) {
		global $wpdb;
		$tables = Config::tables();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->prefix . $tables['logs'],
			array(
				'form_id'    => isset( $context['form_id'] ) ? (int) $context['form_id'] : null,
				'entry_id'   => isset( $context['entry_id'] ) ? (int) $context['entry_id'] : null,
				'component'  => substr( (string) $component, 0, 60 ),
				'status'     => substr( (string) $status, 0, 20 ),
				'title'      => substr( (string) $title, 0, 255 ),
				'message'    => is_scalar( $message ) ? (string) $message : wp_json_encode( $message ),
				'created_at' => current_time( 'mysql' ),
			)
		);
	}
}
