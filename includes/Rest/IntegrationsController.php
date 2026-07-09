<?php
/**
 * Integrations REST controller.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Rest;

defined( 'ABSPATH' ) || exit;

/**
 * Lists integrations and stores their global credentials.
 */
class IntegrationsController extends AbstractController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/integrations',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/integrations/(?P<slug>[a-z0-9_\-]+)',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * List integration descriptors plus stored settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function index() {
		$registry = $this->container->get( 'integrations' );
		return $this->ok( $registry->describe_all() );
	}

	/**
	 * Save an integration's global settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update( $request ) {
		$slug     = sanitize_key( $request['slug'] );
		$registry = $this->container->get( 'integrations' );
		if ( ! $registry->get( $slug ) ) {
			return $this->fail( __( 'Unknown integration.', 'activeforms' ), 404 );
		}

		$body = $request->get_json_params();
		$body = $body ? $body : $request->get_params();
		$body = isset( $body['settings'] ) ? $body['settings'] : $body;

		$clean = is_array( $body ) ? map_deep( $body, 'sanitize_text_field' ) : array();
		update_option( 'activeforms_integration_' . $slug, $clean );

		return $this->ok( $registry->get( $slug )->describe() );
	}
}
