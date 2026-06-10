<?php
/**
 * Entries REST controller.
 *
 * @package EasyForms
 */

namespace EasyForms\Rest;

use EasyForms\Models\Entry;
use EasyForms\Models\Form;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Read, update status, delete, and export entries.
 */
class EntriesController extends AbstractController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/forms/(?P<form_id>\d+)/entries',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/entries/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'can_read' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_read' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'destroy' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/forms/(?P<form_id>\d+)/entries/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);
	}

	/**
	 * List entries for a form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function index( $request ) {
		$form_id = (int) $request['form_id'];
		$result  = Entry::for_form(
			$form_id,
			array(
				'status'   => sanitize_key( (string) $request->get_param( 'status' ) ),
				'search'   => sanitize_text_field( (string) $request->get_param( 'search' ) ),
				'per_page' => (int) ( $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 20 ),
				'page'     => (int) ( $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1 ),
			)
		);

		$form           = Form::find( $form_id );
		$result['form'] = $form ? array( 'id' => $form['id'], 'title' => $form['title'], 'fields' => $form['fields'] ) : null;

		return $this->ok( $result );
	}

	/**
	 * Show one entry (marks it read).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function show( $request ) {
		$entry = Entry::find( (int) $request['id'] );
		if ( ! $entry ) {
			return $this->fail( __( 'Entry not found.', 'easyforms' ), 404 );
		}
		if ( 'unread' === $entry['status'] ) {
			Entry::update( $entry['id'], array( 'status' => 'read' ) );
			$entry['status'] = 'read';
		}
		return $this->ok( $entry );
	}

	/**
	 * Update entry status flags.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update( $request ) {
		$id   = (int) $request['id'];
		$body = $request->get_json_params();
		$body = $body ? $body : $request->get_params();

		$data = array();
		if ( isset( $body['status'] ) ) {
			$data['status'] = sanitize_key( $body['status'] );
		}
		if ( isset( $body['is_favorite'] ) ) {
			$data['is_favorite'] = $body['is_favorite'] ? 1 : 0;
		}

		Entry::update( $id, $data );
		return $this->ok( Entry::find( $id ) );
	}

	/**
	 * Permanently delete an entry.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function destroy( $request ) {
		Entry::delete( (int) $request['id'] );
		return $this->ok( array( 'deleted' => true ) );
	}

	/**
	 * Export entries as CSV (streamed via response body).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function export( $request ) {
		$form_id = (int) $request['form_id'];
		$form    = Form::find( $form_id );
		if ( ! $form ) {
			return $this->fail( __( 'Form not found.', 'easyforms' ), 404 );
		}

		$result  = Entry::for_form( $form_id, array( 'per_page' => 5000, 'page' => 1 ) );
		$columns = array_keys( Arr::flatten_fields( $form['fields'] ) );

		$rows   = array();
		$header = array_merge( array( 'id', 'created_at' ), $columns );
		$rows[] = $header;

		foreach ( $result['items'] as $entry ) {
			$row = array( $entry['id'], $entry['created_at'] );
			foreach ( $columns as $col ) {
				$value = isset( $entry['response'][ $col ] ) ? $entry['response'][ $col ] : '';
				$row[] = is_array( $value ) ? implode( ' | ', map_deep( $value, 'strval' ) ) : (string) $value;
			}
			$rows[] = $row;
		}

		$csv = '';
		foreach ( $rows as $row ) {
			$escaped = array_map(
				function ( $cell ) {
					return '"' . str_replace( '"', '""', (string) $cell ) . '"';
				},
				$row
			);
			$csv .= implode( ',', $escaped ) . "\r\n";
		}

		return $this->ok(
			array(
				'filename' => sanitize_file_name( $form['title'] . '-entries.csv' ),
				'mime'     => 'text/csv',
				'content'  => base64_encode( $csv ),
			)
		);
	}
}
