<?php
/**
 * Reporting aggregator.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Reporting;

use ActiveForms\Core\Config;
use ActiveForms\Models\Form;
use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Computes dashboard overview metrics and per-form field distributions over
 * the entries / entry_details tables.
 */
class Analytics {

	/**
	 * Full analytics dashboard scoped by form + date range.
	 *
	 * Every figure is computed live from the entries / logs tables. Where a
	 * metric has no data yet (e.g. geo, integration logs) the section returns an
	 * empty set so the UI can show a graceful empty state rather than break.
	 *
	 * @param array{form_id:int,from:string,to:string} $args Filters.
	 * @return array<string,mixed>
	 */
	public static function dashboard( $args ) {
		global $wpdb;
		$tables  = Config::tables();
		$entries = $wpdb->prefix . $tables['entries'];
		$forms   = $wpdb->prefix . $tables['forms'];
		$logs    = $wpdb->prefix . $tables['logs'];

		$form_id = isset( $args['form_id'] ) ? (int) $args['form_id'] : 0;

		// Resolve + clamp the date window (default: last 30 days).
		list( $from, $to ) = self::resolve_range( $args );
		$from_dt           = $from . ' 00:00:00';
		$to_dt             = $to . ' 23:59:59';

		// Previous equal-length window, for the trend deltas.
		$span      = (int) round( ( strtotime( $to ) - strtotime( $from ) ) / DAY_IN_SECONDS );
		$prev_to   = gmdate( 'Y-m-d', strtotime( $from . ' -1 day' ) );
		$prev_from = gmdate( 'Y-m-d', strtotime( $from . ' -' . ( $span + 1 ) . ' days' ) );

		// Form clause reused across queries.
		$fc     = $form_id ? ' AND form_id = ' . $form_id : '';
		$live   = " status NOT IN ('trashed','spam')";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders

		// ---- Stat cards ----
		$submissions = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$entries} WHERE {$live}{$fc} AND created_at BETWEEN %s AND %s", $from_dt, $to_dt ) );
		$prev_subs   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$entries} WHERE {$live}{$fc} AND created_at BETWEEN %s AND %s", $prev_from . ' 00:00:00', $prev_to . ' 23:59:59' ) );
		$spam        = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$entries} WHERE status = 'spam'{$fc} AND created_at BETWEEN %s AND %s", $from_dt, $to_dt ) );
		$unread      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$entries} WHERE status = 'unread'{$fc} AND created_at BETWEEN %s AND %s", $from_dt, $to_dt ) );
		$forms_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$forms} WHERE status != 'trashed'" );

		// ---- Daily status series ----
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS day,
					SUM(status NOT IN ('trashed','spam')) AS submissions,
					SUM(status = 'spam') AS spam,
					SUM(status = 'unread') AS unread,
					SUM(status = 'read') AS read_count,
					SUM(status = 'trashed') AS trashed
				 FROM {$entries} WHERE created_at BETWEEN %s AND %s{$fc}
				 GROUP BY DATE(created_at)",
				$from_dt,
				$to_dt
			),
			ARRAY_A
		);
		$by_day = array();
		foreach ( (array) $rows as $r ) {
			$by_day[ $r['day'] ] = $r;
		}
		$series = array();
		$cursor = strtotime( $from );
		$end    = strtotime( $to );
		while ( $cursor <= $end ) {
			$day = gmdate( 'Y-m-d', $cursor );
			$d   = isset( $by_day[ $day ] ) ? $by_day[ $day ] : array();
			$series[] = array(
				'day'         => $day,
				'submissions' => (int) ( isset( $d['submissions'] ) ? $d['submissions'] : 0 ),
				'spam'        => (int) ( isset( $d['spam'] ) ? $d['spam'] : 0 ),
				'unread'      => (int) ( isset( $d['unread'] ) ? $d['unread'] : 0 ),
				'read'        => (int) ( isset( $d['read_count'] ) ? $d['read_count'] : 0 ),
				'trashed'     => (int) ( isset( $d['trashed'] ) ? $d['trashed'] : 0 ),
			);
			$cursor = strtotime( '+1 day', $cursor );
		}

		// ---- Top performing forms ----
		$top_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT form_id, COUNT(*) AS count, COALESCE(SUM(payment_total),0) AS payments
				 FROM {$entries} WHERE {$live}{$fc} AND created_at BETWEEN %s AND %s
				 GROUP BY form_id ORDER BY count DESC LIMIT 6",
				$from_dt,
				$to_dt
			),
			ARRAY_A
		);
		$top_forms = array();
		foreach ( (array) $top_rows as $r ) {
			$form        = Form::find( (int) $r['form_id'] );
			$top_forms[] = array(
				'form_id'  => (int) $r['form_id'],
				'title'    => $form ? $form['title'] : __( '(deleted form)', 'activeforms' ),
				'count'    => (int) $r['count'],
				'payments' => (int) $r['payments'],
			);
		}

		// ---- Submissions by country ----
		$country_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT country, COUNT(*) AS count FROM {$entries}
				 WHERE {$live}{$fc} AND country IS NOT NULL AND country != '' AND created_at BETWEEN %s AND %s
				 GROUP BY country ORDER BY count DESC LIMIT 10",
				$from_dt,
				$to_dt
			),
			ARRAY_A
		);
		$by_country = array_map(
			function ( $r ) {
				return array( 'country' => $r['country'], 'count' => (int) $r['count'] );
			},
			(array) $country_rows
		);

		// ---- Day-of-week x hour timeline (0 = Sunday) ----
		$tl_rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DAYOFWEEK(created_at) AS dow, HOUR(created_at) AS hr, COUNT(*) AS c
				 FROM {$entries} WHERE {$live}{$fc} AND created_at BETWEEN %s AND %s
				 GROUP BY dow, hr",
				$from_dt,
				$to_dt
			),
			ARRAY_A
		);
		$timeline = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$timeline[ $i ] = array_fill( 0, 24, 0 );
		}
		foreach ( (array) $tl_rows as $r ) {
			$dow = ( (int) $r['dow'] ) - 1; // MySQL DAYOFWEEK: 1=Sun .. 7=Sat.
			$hr  = (int) $r['hr'];
			if ( $dow >= 0 && $dow < 7 && $hr >= 0 && $hr < 24 ) {
				$timeline[ $dow ][ $hr ] = (int) $r['c'];
			}
		}

		// ---- API / integration logs ----
		$log_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS day,
					SUM(status = 'success') AS success,
					SUM(status IN ('processing','pending')) AS processing,
					SUM(status IN ('failed','error')) AS failed
				 FROM {$logs} WHERE created_at BETWEEN %s AND %s{$fc}
				 GROUP BY DATE(created_at)",
				$from_dt,
				$to_dt
			),
			ARRAY_A
		);
		$logs_by_day = array();
		foreach ( (array) $log_rows as $r ) {
			$logs_by_day[ $r['day'] ] = $r;
		}
		$api_logs = array();
		$cursor   = strtotime( $from );
		while ( $cursor <= $end ) {
			$day = gmdate( 'Y-m-d', $cursor );
			$d   = isset( $logs_by_day[ $day ] ) ? $logs_by_day[ $day ] : array();
			$api_logs[] = array(
				'day'        => $day,
				'success'    => (int) ( isset( $d['success'] ) ? $d['success'] : 0 ),
				'processing' => (int) ( isset( $d['processing'] ) ? $d['processing'] : 0 ),
				'failed'     => (int) ( isset( $d['failed'] ) ? $d['failed'] : 0 ),
			);
			$cursor = strtotime( '+1 day', $cursor );
		}
		// phpcs:enable

		$incomplete = 0; // Partial-entry capture isn't tracked yet; all stored entries are complete.
		$complete   = $submissions;
		$pct        = ( $complete + $incomplete ) > 0 ? (int) round( $complete / ( $complete + $incomplete ) * 100 ) : 0;

		return array(
			'range'      => array( 'from' => $from, 'to' => $to ),
			'cards'      => array(
				'submissions' => array( 'value' => $submissions, 'delta' => self::delta( $submissions, $prev_subs ) ),
				'spam'        => array( 'value' => $spam ),
				'unread'      => array( 'value' => $unread ),
				'forms'       => array( 'value' => $forms_count ),
			),
			'series'     => $series,
			'completion' => array( 'complete' => $complete, 'incomplete' => $incomplete, 'percentage' => $pct ),
			'topForms'   => $top_forms,
			'byCountry'  => $by_country,
			'timeline'   => $timeline,
			'apiLogs'    => $api_logs,
		);
	}

	/**
	 * Resolve + sanitize a YYYY-MM-DD range, defaulting to the last 30 days and
	 * clamping the span to a year so the daily series stays bounded.
	 *
	 * @param array $args Filters.
	 * @return array{0:string,1:string}
	 */
	private static function resolve_range( $args ) {
		$valid = function ( $v ) {
			return is_string( $v ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
		};
		$to   = $valid( isset( $args['to'] ) ? $args['to'] : '' );
		$from = $valid( isset( $args['from'] ) ? $args['from'] : '' );
		if ( ! $to ) {
			$to = gmdate( 'Y-m-d', current_time( 'timestamp' ) );
		}
		if ( ! $from ) {
			$from = gmdate( 'Y-m-d', strtotime( $to . ' -29 days' ) );
		}
		if ( strtotime( $from ) > strtotime( $to ) ) {
			$tmp  = $from;
			$from = $to;
			$to   = $tmp;
		}
		// Clamp to 366 days.
		if ( ( strtotime( $to ) - strtotime( $from ) ) > 366 * DAY_IN_SECONDS ) {
			$from = gmdate( 'Y-m-d', strtotime( $to . ' -366 days' ) );
		}
		return array( $from, $to );
	}

	/**
	 * Percentage change between two counts (capped, integer).
	 *
	 * @param int $current  Current period.
	 * @param int $previous Previous period.
	 * @return int|null Null when there's no baseline to compare against.
	 */
	private static function delta( $current, $previous ) {
		if ( $previous <= 0 ) {
			return $current > 0 ? 100 : 0;
		}
		return (int) round( ( ( $current - $previous ) / $previous ) * 100 );
	}

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
				'title'   => $form ? $form['title'] : __( '(deleted form)', 'activeforms' ),
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
		$reportable = apply_filters( 'activeforms/reportable_fields', $reportable );

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
