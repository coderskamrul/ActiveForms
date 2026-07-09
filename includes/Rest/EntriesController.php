<?php
/**
 * Entries REST controller.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Rest;

use ActiveForms\Models\Entry;
use ActiveForms\Models\Form;
use ActiveForms\Support\Arr;

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
			'/forms/(?P<form_id>\d+)/entries/bulk',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk' ),
				'permission_callback' => array( $this, 'can_manage' ),
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
				'orderby'  => sanitize_key( (string) $request->get_param( 'orderby' ) ),
				'order'    => sanitize_key( (string) $request->get_param( 'order' ) ),
			)
		);

		// Attach a lightweight submitter label to each row for the list view.
		$result['items'] = array_map(
			function ( $entry ) {
				$entry['user'] = $this->resolve_user( isset( $entry['user_id'] ) ? $entry['user_id'] : 0 );
				return $entry;
			},
			$result['items']
		);

		$form              = Form::find( $form_id );
		$result['form']    = $form ? array( 'id' => $form['id'], 'title' => $form['title'], 'fields' => $form['fields'] ) : null;
		$result['counts']  = Entry::counts( $form_id );

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
			return $this->fail( __( 'Entry not found.', 'activeforms' ), 404 );
		}
		if ( 'unread' === $entry['status'] ) {
			Entry::update( $entry['id'], array( 'status' => 'read' ) );
			$entry['status'] = 'read';
		}

		// Enrich with the full diagnostic record + parent form schema so the
		// detail view can render labelled fields and a metadata sidebar.
		$entry['meta']      = Entry::meta( $entry['id'] );
		$entry['user']      = $this->resolve_user( isset( $entry['user_id'] ) ? $entry['user_id'] : 0 );
		$entry['neighbors'] = Entry::neighbors( $entry['form_id'], $entry['id'] );

		$form          = Form::find( $entry['form_id'] );
		$entry['form'] = $form ? array( 'id' => $form['id'], 'title' => $form['title'], 'fields' => $form['fields'] ) : null;

		return $this->ok( $entry );
	}

	/**
	 * Resolve a submitter into a display label and edit link.
	 *
	 * @param int $user_id User ID (0 for guests).
	 * @return array{id:int,name:string,edit_link:string}
	 */
	protected function resolve_user( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return array(
				'id'        => 0,
				'name'      => __( 'Guest', 'activeforms' ),
				'edit_link' => '',
			);
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array(
				'id'        => $user_id,
				'name'      => __( 'Deleted user', 'activeforms' ),
				'edit_link' => '',
			);
		}

		return array(
			'id'        => $user_id,
			'name'      => $user->display_name,
			'edit_link' => current_user_can( 'edit_users' ) ? get_edit_user_link( $user_id ) : '',
		);
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
		if ( isset( $body['response'] ) && is_array( $body['response'] ) ) {
			$data['response'] = $this->sanitize_response( $body['response'] );
		}

		Entry::update( $id, $data );
		return $this->ok( Entry::find( $id ) );
	}

	/**
	 * Recursively sanitize an edited response payload, preserving array shape.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_response( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_response' ), $value );
		}
		// Multi-line field values (textarea/address) must keep their newlines.
		return sanitize_textarea_field( (string) $value );
	}

	/**
	 * Apply a bulk action to a set of entries belonging to a form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function bulk( $request ) {
		$form_id = (int) $request['form_id'];
		$body    = $request->get_json_params();
		$body    = $body ? $body : $request->get_params();

		$action = isset( $body['action'] ) ? sanitize_key( $body['action'] ) : '';
		$ids     = isset( $body['ids'] ) && is_array( $body['ids'] ) ? array_map( 'intval', $body['ids'] ) : array();
		$ids     = array_filter( $ids );

		if ( empty( $ids ) ) {
			return $this->fail( __( 'No entries selected.', 'activeforms' ), 422 );
		}

		$affected = 0;
		foreach ( $ids as $id ) {
			$entry = Entry::find( $id );
			if ( ! $entry || (int) $entry['form_id'] !== $form_id ) {
				continue;
			}

			switch ( $action ) {
				case 'delete':
					Entry::delete( $id );
					break;
				case 'trash':
					Entry::update( $id, array( 'status' => 'trashed' ) );
					break;
				case 'restore':
					Entry::update( $id, array( 'status' => 'read' ) );
					break;
				case 'read':
				case 'unread':
					Entry::update( $id, array( 'status' => $action ) );
					break;
				case 'favorite':
				case 'unfavorite':
					Entry::update( $id, array( 'is_favorite' => 'favorite' === $action ? 1 : 0 ) );
					break;
				default:
					return $this->fail( __( 'Unknown bulk action.', 'activeforms' ), 422 );
			}
			++$affected;
		}

		return $this->ok(
			array(
				'affected' => $affected,
				'counts'   => Entry::counts( $form_id ),
			)
		);
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
			return $this->fail( __( 'Form not found.', 'activeforms' ), 404 );
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
