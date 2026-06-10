<?php
/**
 * Entry (submission) data-mapper model.
 *
 * @package EasyForms
 */

namespace EasyForms\Models;

use EasyForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps the entries + entry_details tables.
 */
class Entry {

	/**
	 * Entries table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		$tables = Config::tables();
		return $wpdb->prefix . $tables['entries'];
	}

	/**
	 * Find an entry by ID.
	 *
	 * @param int $id Entry ID.
	 * @return array|null
	 */
	public static function find( $id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ? self::hydrate( $row ) : null;
	}

	/**
	 * List entries for a form.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $args    Query args (status, search, per_page, page).
	 * @return array{items:array,total:int}
	 */
	public static function for_form( $form_id, $args = array() ) {
		global $wpdb;
		$table = self::table();

		$args = wp_parse_args(
			$args,
			array(
				'status'   => '',
				'search'   => '',
				'per_page' => 20,
				'page'     => 1,
			)
		);

		$where  = 'WHERE form_id = %d';
		$params = array( (int) $form_id );

		if ( 'trashed' === $args['status'] ) {
			$where .= " AND status = 'trashed'";
		} elseif ( 'favorites' === $args['status'] ) {
			$where .= " AND is_favorite = 1 AND status != 'trashed'";
		} elseif ( in_array( $args['status'], array( 'read', 'unread' ), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		} else {
			$where .= " AND status != 'trashed'";
		}

		if ( '' !== $args['search'] ) {
			$where   .= ' AND response LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

		$list_sql    = "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$list_params = array_merge( $params, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A );

		return array(
			'items' => array_map( array( __CLASS__, 'hydrate' ), $rows ? $rows : array() ),
			'total' => $total,
		);
	}

	/**
	 * Insert a new entry plus flattened detail rows.
	 *
	 * @param array $data Entry data (form_id, response, meta...).
	 * @return int Entry ID.
	 */
	public static function create( $data ) {
		global $wpdb;
		$form_id = (int) $data['form_id'];
		$now     = current_time( 'mysql' );

		$serial = self::next_serial( $form_id );

		$insert = array(
			'form_id'        => $form_id,
			'serial'         => $serial,
			'response'       => wp_json_encode( isset( $data['response'] ) ? $data['response'] : array() ),
			'status'         => 'unread',
			'user_id'        => get_current_user_id() ? get_current_user_id() : null,
			'source_url'     => isset( $data['source_url'] ) ? esc_url_raw( $data['source_url'] ) : '',
			'ip'             => isset( $data['ip'] ) ? $data['ip'] : '',
			'browser'        => isset( $data['browser'] ) ? $data['browser'] : '',
			'device'         => isset( $data['device'] ) ? $data['device'] : '',
			'payment_status' => isset( $data['payment_status'] ) ? $data['payment_status'] : null,
			'payment_total'  => isset( $data['payment_total'] ) ? (int) $data['payment_total'] : null,
			'currency'       => isset( $data['currency'] ) ? $data['currency'] : null,
			'created_at'     => $now,
			'updated_at'     => $now,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( self::table(), $insert );
		$entry_id = (int) $wpdb->insert_id;

		if ( $entry_id && isset( $data['response'] ) ) {
			self::store_details( $form_id, $entry_id, $data['response'] );
		}

		return $entry_id;
	}

	/**
	 * Update an entry's status flags.
	 *
	 * @param int   $id   Entry ID.
	 * @param array $data Columns to update.
	 * @return bool
	 */
	public static function update( $id, $data ) {
		global $wpdb;
		$allowed = array( 'status', 'is_favorite', 'response', 'payment_status' );
		$update  = array( 'updated_at' => current_time( 'mysql' ) );

		foreach ( $allowed as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$update[ $key ] = 'response' === $key ? wp_json_encode( $data[ $key ] ) : $data[ $key ];
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return false !== $wpdb->update( self::table(), $update, array( 'id' => (int) $id ) );
	}

	/**
	 * Permanently delete an entry and its details.
	 *
	 * @param int $id Entry ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$id     = (int) $id;
		$tables = Config::tables();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . $tables['entry_detail'], array( 'entry_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . $tables['entry_meta'], array( 'entry_id' => $id ) );
		$result = $wpdb->delete( self::table(), array( 'id' => $id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return (bool) $result;
	}

	/**
	 * Compute the next per-form serial number.
	 *
	 * @param int $form_id Form ID.
	 * @return int
	 */
	protected static function next_serial( $form_id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(serial) FROM {$table} WHERE form_id = %d", $form_id ) );
		return $max + 1;
	}

	/**
	 * Store flattened detail rows for fast reporting/search.
	 *
	 * @param int   $form_id  Form ID.
	 * @param int   $entry_id Entry ID.
	 * @param array $response Key => value response map.
	 * @return void
	 */
	protected static function store_details( $form_id, $entry_id, $response ) {
		global $wpdb;
		$tables = Config::tables();
		$table  = $wpdb->prefix . $tables['entry_detail'];

		foreach ( (array) $response as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $sub => $sub_value ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->insert(
						$table,
						array(
							'form_id'     => $form_id,
							'entry_id'    => $entry_id,
							'field_key'   => (string) $key,
							'sub_field'   => (string) $sub,
							'field_value' => is_scalar( $sub_value ) ? (string) $sub_value : wp_json_encode( $sub_value ),
						)
					);
				}
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->insert(
					$table,
					array(
						'form_id'     => $form_id,
						'entry_id'    => $entry_id,
						'field_key'   => (string) $key,
						'sub_field'   => null,
						'field_value' => (string) $value,
					)
				);
			}
		}
	}

	/**
	 * Decode JSON columns.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	protected static function hydrate( $row ) {
		$row['id']          = (int) $row['id'];
		$row['form_id']     = (int) $row['form_id'];
		$row['serial']      = (int) $row['serial'];
		$row['is_favorite'] = (int) $row['is_favorite'];
		$row['response']    = $row['response'] ? json_decode( $row['response'], true ) : array();
		return $row;
	}
}
