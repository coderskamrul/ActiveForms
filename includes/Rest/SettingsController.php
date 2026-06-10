<?php
/**
 * Global settings REST controller.
 *
 * @package EasyForms
 */

namespace EasyForms\Rest;

use EasyForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the global settings option.
 */
class SettingsController extends AbstractController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Return current settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function show() {
		return $this->ok( get_option( Config::OPTION_SETTINGS, array() ) );
	}

	/**
	 * Persist settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function update( $request ) {
		$body    = $request->get_json_params();
		$body    = $body ? $body : $request->get_params();
		$current = get_option( Config::OPTION_SETTINGS, array() );

		$clean = array();
		$clean['label_placement'] = isset( $body['label_placement'] ) ? sanitize_key( $body['label_placement'] ) : 'top';
		$clean['remove_data_on_uninstall'] = ! empty( $body['remove_data_on_uninstall'] );

		if ( isset( $body['recaptcha'] ) && is_array( $body['recaptcha'] ) ) {
			$clean['recaptcha'] = array(
				'provider'   => isset( $body['recaptcha']['provider'] ) ? sanitize_key( $body['recaptcha']['provider'] ) : '',
				'site_key'   => isset( $body['recaptcha']['site_key'] ) ? sanitize_text_field( $body['recaptcha']['site_key'] ) : '',
				'secret_key' => isset( $body['recaptcha']['secret_key'] ) ? sanitize_text_field( $body['recaptcha']['secret_key'] ) : '',
			);
		}

		if ( isset( $body['messages'] ) && is_array( $body['messages'] ) ) {
			$clean['messages'] = map_deep( $body['messages'], 'sanitize_text_field' );
		}

		$merged = array_merge( (array) $current, $clean );
		update_option( Config::OPTION_SETTINGS, $merged );

		return $this->ok( $merged );
	}
}
