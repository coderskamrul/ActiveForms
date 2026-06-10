<?php
/**
 * Builder metadata REST controller.
 *
 * @package EasyForms
 */

namespace EasyForms\Rest;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies the React builder with field definitions and editor metadata.
 */
class BuilderController extends AbstractController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/builder/fields',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'fields' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/builder/preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Render an unsaved schema to frontend-accurate HTML for the builder preview.
	 *
	 * The output is produced by the same FormRenderer used on the front end, so
	 * the preview matches production exactly. Field handlers escape their own
	 * output; this endpoint is restricted to the manage capability.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function preview( $request ) {
		$body   = $request->get_json_params();
		$body   = $body ? $body : $request->get_params();
		$fields = isset( $body['fields'] ) ? (array) $body['fields'] : array();

		$renderer = $this->container->get( 'renderer' );
		$html     = $renderer->render(
			array(
				'id'     => isset( $body['id'] ) ? (int) $body['id'] : 0,
				'title'  => isset( $body['title'] ) ? sanitize_text_field( $body['title'] ) : '',
				'fields' => $fields,
			)
		);

		return $this->ok( array( 'html' => $html ) );
	}

	/**
	 * Return field palette definitions.
	 *
	 * @return \WP_REST_Response
	 */
	public function fields() {
		$registry = $this->container->get( 'fields' );
		return $this->ok(
			array(
				'fields'     => $registry->definitions(),
				'categories' => array(
					array( 'key' => 'general', 'label' => __( 'General Fields', 'easyforms' ) ),
					array( 'key' => 'layout', 'label' => __( 'Layout', 'easyforms' ) ),
					array( 'key' => 'advanced', 'label' => __( 'Advanced (Pro)', 'easyforms' ) ),
					array( 'key' => 'payment', 'label' => __( 'Payment', 'easyforms' ) ),
				),
			)
		);
	}
}
