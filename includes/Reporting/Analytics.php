<?php
/**
 * Reporting aggregator.
 *
 * @package EasyForms
 */

namespace EasyForms\Reporting;

use EasyForms\Core\Config;
use EasyForms\Models\Form;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Computes dashboard overview metrics and per-form field distributions over
 * the entries / entry_details tables.
 */
class Analytics {

	/**
	 * Dashboard overview.
	 *
	 * @return array<string,mixed>
	 */
	public static function overview() {
		global $wpdb;
		$tables  = Config::tables();
		$forms   = $wpdb->prefix . $tables['forms'];
		$entries = $wpdb->prefix . $tables['entries'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_forms   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$forms}" );
		$total_entries = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$entries} WHERE status != 'trashed'" );
		$unread        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$entries} WHERE status = 'unread'" );

		$trend = $wpdb->get_results(
			"SELECT DATE(created_at) AS day, COUNT(*) AS count
			 FROM {$entries}
			 WHERE status != 'trashed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			 GROUP BY DATE(created_at) ORDER BY day ASC",
			ARRAY_A
		);

		$top = $wpdb->get_results(
			"SELECT form_id, COUNT(*) AS count FROM {$entries}
			 WHERE status != 'trashed' GROUP BY form_id ORDER BY count DESC LIMIT 5",
			ARRAY_A
		);
		// phpcs:enable

		$top_forms = array();
		foreach ( (array) $top as $row ) {
			$form        = Form::find( (int) $row['form_id'] );
			$top_forms[] = array(
				'form_id' => (int) $row['form_id'],
				'title'   => $form ? $form['title'] : __( '(deleted form)', 'easyforms' ),
				'count'   => (int) $row['count'],
			);
		}

		return array(
			'totals'    => array(
				'forms'   => $total_forms,
				'entries' => $total_entries,
				'unread'  => $unread,
			),
			'trend'     => array_map(
				function ( $r ) {
					return array( 'day' => $r['day'], 'count' => (int) $r['count'] );
				},
				(array) $trend
			),
			'topForms'  => $top_forms,
		);
	}

	/**
	 * Per-form field distribution report.
	 *
	 * @param int $form_id Form ID.
	 * @return array<string,mixed>
	 */
	public static function form_report( $form_id ) {
		global $wpdb;
		$tables = Config::tables();
		$detail = $wpdb->prefix . $tables['entry_detail'];

		$form = Form::find( $form_id );
		if ( ! $form ) {
			return array( 'fields' => array() );
		}

		$reportable = array( 'select', 'radio', 'checkbox', 'country' );
		/**
		 * Filter which field types are reportable.
		 *
		 * @param array $reportable Reportable field types.
		 */
		$reportable = apply_filters( 'easyforms/reportable_fields', $reportable );

		$flat    = Arr::flatten_fields( $form['fields'] );
		$reports = array();

		foreach ( $flat as $key => $field ) {
			if ( ! in_array( Arr::get( $field, 'type' ), $reportable, true ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT field_value AS label, COUNT(*) AS count
					 FROM {$detail} WHERE form_id = %d AND field_key = %s
					 GROUP BY field_value ORDER BY count DESC",
					$form_id,
					$key
				),
				ARRAY_A
			);

			$reports[] = array(
				'key'    => $key,
				'label'  => Arr::get( $field, 'label', $key ),
				'type'   => Arr::get( $field, 'type' ),
				'buckets' => array_map(
					function ( $r ) {
						return array( 'label' => $r['label'], 'count' => (int) $r['count'] );
					},
					(array) $rows
				),
			);
		}

		return array(
			'form'   => array( 'id' => $form['id'], 'title' => $form['title'] ),
			'fields' => $reports,
		);
	}
}
