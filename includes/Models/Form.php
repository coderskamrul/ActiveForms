<?php
/**
 * Form data-mapper model.
 *
 * @package EasyForms
 */

namespace EasyForms\Models;

use EasyForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight active-record-ish wrapper around the forms table. Uses $wpdb
 * directly with prepared statements; JSON columns are encoded/decoded here.
 */
class Form {

	/**
	 * Fully-qualified forms table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		$tables = Config::tables();
		return $wpdb->prefix . $tables['forms'];
	}

	/**
	 * Fetch a form row as an associative array with decoded JSON.
	 *
	 * @param int $id Form ID.
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
	 * List forms with basic pagination and search.
	 *
	 * @param array $args Query arguments (search, status, per_page, page, orderby, order).
	 * @return array{items:array,total:int}
	 */
	public static function all( $args = array() ) {
		global $wpdb;
		$table = self::table();

		$args = wp_parse_args(
			$args,
			array(
				'search'   => '',
				'status'   => '',
				'per_page' => 20,
				'page'     => 1,
				'orderby'  => 'id',
				'order'    => 'DESC',
			)
		);

		$where  = 'WHERE 1=1';
		$params = array();

		if ( '' !== $args['status'] ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		if ( '' !== $args['search'] ) {
			$where   .= ' AND title LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$allowed_orderby = array( 'id', 'title', 'created_at', 'updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		// Total count.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );

		$list_sql       = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$list_params    = array_merge( $params, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A );

		$items = array_map( array( __CLASS__, 'hydrate' ), $rows ? $rows : array() );

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Create a form.
	 *
	 * @param array $data Form data (title, fields, settings, type, status).
	 * @return int Inserted form ID.
	 */
	public static function create( $data ) {
		global $wpdb;
		$now = current_time( 'mysql' );

		$insert = array(
			'title'       => isset( $data['title'] ) ? $data['title'] : __( 'Untitled Form', 'easyforms' ),
			'slug'        => isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '',
			'status'      => isset( $data['status'] ) ? $data['status'] : 'published',
			'type'        => isset( $data['type'] ) ? $data['type'] : 'classic',
			'fields'      => wp_json_encode( isset( $data['fields'] ) ? $data['fields'] : array() ),
			'settings'    => wp_json_encode( isset( $data['settings'] ) ? $data['settings'] : array() ),
			'has_payment' => ! empty( $data['has_payment'] ) ? 1 : 0,
			'created_by'  => get_current_user_id(),
			'created_at'  => $now,
			'updated_at'  => $now,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( self::table(), $insert );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a form.
	 *
	 * @param int   $id   Form ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$update = array( 'updated_at' => current_time( 'mysql' ) );

		if ( isset( $data['title'] ) ) {
			$update['title'] = $data['title'];
		}
		if ( isset( $data['status'] ) ) {
			$update['status'] = $data['status'];
		}
		if ( isset( $data['type'] ) ) {
			$update['type'] = $data['type'];
		}
		if ( isset( $data['fields'] ) ) {
			$update['fields']      = wp_json_encode( $data['fields'] );
			$update['has_payment'] = self::schema_has_payment( $data['fields'] ) ? 1 : 0;
		}
		if ( isset( $data['settings'] ) ) {
			$update['settings'] = wp_json_encode( $data['settings'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return false !== $wpdb->update( self::table(), $update, array( 'id' => (int) $id ) );
	}

	/**
	 * Duplicate a form (and its meta).
	 *
	 * @param int $id Source form ID.
	 * @return int|null New form ID.
	 */
	public static function duplicate( $id ) {
		$source = self::find( $id );
		if ( ! $source ) {
			return null;
		}

		/* translators: %s: original form title. */
		$source['title'] = sprintf( __( '%s (Copy)', 'easyforms' ), $source['title'] );
		unset( $source['id'] );

		return self::create( $source );
	}

	/**
	 * Delete a form and all related rows.
	 *
	 * @param int $id Form ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$id     = (int) $id;
		$tables = Config::tables();
		$prefix = $wpdb->prefix;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $prefix . $tables['form_meta'], array( 'form_id' => $id ) );
		$wpdb->delete( $prefix . $tables['entries'], array( 'form_id' => $id ) );
		$wpdb->delete( $prefix . $tables['entry_meta'], array( 'form_id' => $id ) );
		$wpdb->delete( $prefix . $tables['entry_detail'], array( 'form_id' => $id ) );
		$result = $wpdb->delete( self::table(), array( 'id' => $id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return (bool) $result;
	}

	/**
	 * Count entries for a form.
	 *
	 * @param int $id Form ID.
	 * @return int
	 */
	public static function entry_count( $id ) {
		global $wpdb;
		$tables = Config::tables();
		$table  = $wpdb->prefix . $tables['entries'];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE form_id = %d AND status != 'trashed'", $id ) );
	}

	/**
	 * Decode JSON columns into PHP arrays.
	 *
	 * @param array $row Raw DB row.
	 * @return array
	 */
	protected static function hydrate( $row ) {
		$row['id']          = (int) $row['id'];
		$row['has_payment'] = (int) $row['has_payment'];
		$row['fields']      = $row['fields'] ? json_decode( $row['fields'], true ) : array();
		$row['settings']    = $row['settings'] ? json_decode( $row['settings'], true ) : array();
		return $row;
	}

	/**
	 * Detect whether a field schema contains a payment field.
	 *
	 * @param array $fields Field schema.
	 * @return bool
	 */
	protected static function schema_has_payment( $fields ) {
		$payment_types = array( 'payment_item', 'custom_amount', 'subscription', 'quantity', 'payment_method', 'payment_summary' );
		foreach ( (array) $fields as $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : '';
			if ( in_array( $type, $payment_types, true ) ) {
				return true;
			}
			if ( ! empty( $field['columns'] ) ) {
				foreach ( $field['columns'] as $col ) {
					if ( ! empty( $col['fields'] ) && self::schema_has_payment( $col['fields'] ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}
}
