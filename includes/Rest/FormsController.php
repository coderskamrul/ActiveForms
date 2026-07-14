<?php
/**
 * Forms REST controller.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Rest;

use RadiusForms\Models\Form;
use RadiusForms\Builder\FormTemplates;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD + duplicate/import/export for forms.
 */
class FormsController extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'forms';

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => array( $this, 'can_read' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'can_read' ),
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'destroy' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->id_arg(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/duplicate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'duplicate' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg(),
			)
		);

		register_rest_route(
			$this->namespace,
			'/templates',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'templates' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Shared id argument schema.
	 *
	 * @return array
	 */
	private function id_arg() {
		return array(
			'id' => array(
				'sanitize_callback' => 'absint',
				'validate_callback' => function ( $param ) {
					return is_numeric( $param );
				},
			),
		);
	}

	/**
	 * List forms.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function index( $request ) {
		$result = Form::all(
			array(
				'search'   => sanitize_text_field( (string) $request->get_param( 'search' ) ),
				'status'   => sanitize_text_field( (string) $request->get_param( 'status' ) ),
				'per_page' => (int) ( $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 20 ),
				'page'     => (int) ( $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1 ),
			)
		);

		// Attach entry counts for the list view.
		foreach ( $result['items'] as &$item ) {
			$item['entries'] = Form::entry_count( $item['id'] );
		}
		unset( $item );

		return $this->ok( $result );
	}

	/**
	 * Get a single form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function show( $request ) {
		$form = Form::find( (int) $request['id'] );
		if ( ! $form ) {
			return $this->fail( __( 'Form not found.', 'radiusforms' ), 404 );
		}
		return $this->ok( $form );
	}

	/**
	 * Create a form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function create( $request ) {
		$body = $request->get_json_params();
		$body = $body ? $body : $request->get_params();

		$template = isset( $body['template'] ) ? sanitize_key( $body['template'] ) : '';
		$seed     = $template ? FormTemplates::get( $template ) : array();

		// User-supplied schema/settings are sanitized exactly as in update(); a
		// template seed is trusted internal data and passes through untouched.
		$fields   = isset( $body['fields'] ) ? $this->sanitize_schema( $body['fields'] ) : ( isset( $seed['fields'] ) ? $seed['fields'] : array() );
		$settings = isset( $body['settings'] ) ? $this->sanitize_settings( $body['settings'] ) : ( isset( $seed['settings'] ) ? $seed['settings'] : array() );

		$id = Form::create(
			array(
				'title'    => isset( $body['title'] ) ? sanitize_text_field( $body['title'] ) : __( 'Untitled Form', 'radiusforms' ),
				'type'     => isset( $body['type'] ) ? sanitize_key( $body['type'] ) : 'classic',
				'fields'   => $fields,
				'settings' => $settings,
			)
		);

		return $this->ok( Form::find( $id ), 201 );
	}

	/**
	 * Update a form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update( $request ) {
		$id   = (int) $request['id'];
		$form = Form::find( $id );
		if ( ! $form ) {
			return $this->fail( __( 'Form not found.', 'radiusforms' ), 404 );
		}

		$body = $request->get_json_params();
		$body = $body ? $body : $request->get_params();

		$data = array();
		if ( isset( $body['title'] ) ) {
			$data['title'] = sanitize_text_field( $body['title'] );
		}
		if ( isset( $body['status'] ) ) {
			$data['status'] = sanitize_key( $body['status'] );
		}
		if ( isset( $body['type'] ) ) {
			$data['type'] = sanitize_key( $body['type'] );
		}
		if ( isset( $body['fields'] ) ) {
			$data['fields'] = $this->sanitize_schema( $body['fields'] );
		}
		if ( isset( $body['settings'] ) ) {
			$data['settings'] = $this->sanitize_settings( $body['settings'] );
		}

		Form::update( $id, $data );
		return $this->ok( Form::find( $id ) );
	}

	/**
	 * Delete a form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function destroy( $request ) {
		Form::delete( (int) $request['id'] );
		return $this->ok( array( 'deleted' => true ) );
	}

	/**
	 * Duplicate a form.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function duplicate( $request ) {
		$new_id = Form::duplicate( (int) $request['id'] );
		if ( ! $new_id ) {
			return $this->fail( __( 'Could not duplicate form.', 'radiusforms' ), 400 );
		}
		return $this->ok( Form::find( $new_id ), 201 );
	}

	/**
	 * List form templates.
	 *
	 * @return \WP_REST_Response
	 */
	public function templates() {
		return $this->ok( FormTemplates::all() );
	}

	/**
	 * Recursively sanitize a field schema. Field-specific data is kept but text
	 * values are sanitized; HTML content uses wp_kses_post.
	 *
	 * @param array $fields Field schema.
	 * @return array
	 */
	protected function sanitize_schema( $fields ) {
		$clean = array();
		foreach ( (array) $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$item = array();
			foreach ( $field as $key => $value ) {
				if ( 'content' === $key ) {
					$item[ $key ] = wp_kses_post( $value );
				} elseif ( 'columns' === $key && is_array( $value ) ) {
					$item[ $key ] = array();
					foreach ( $value as $col ) {
						$item[ $key ][] = array(
							'width'  => isset( $col['width'] ) ? (int) $col['width'] : 100,
							'fields' => isset( $col['fields'] ) ? $this->sanitize_schema( $col['fields'] ) : array(),
						);
					}
				} elseif ( is_array( $value ) ) {
					$item[ $key ] = map_deep( $value, 'sanitize_text_field' );
				} else {
					$item[ $key ] = sanitize_text_field( (string) $value );
				}
			}
			$clean[] = $item;
		}
		return $clean;
	}

	/**
	 * Sanitize form settings, preserving rich message HTML.
	 *
	 * @param array $settings Settings tree.
	 * @return array
	 */
	protected function sanitize_settings( $settings ) {
		$settings = (array) $settings;

		if ( isset( $settings['confirmation']['message'] ) ) {
			$settings['confirmation']['message'] = wp_kses_post( $settings['confirmation']['message'] );
		}
		if ( isset( $settings['notifications'] ) && is_array( $settings['notifications'] ) ) {
			foreach ( $settings['notifications'] as &$note ) {
				if ( isset( $note['body'] ) ) {
					$note['body'] = wp_kses_post( $note['body'] );
				}
			}
			unset( $note );
		}

		return $settings;
	}
}
