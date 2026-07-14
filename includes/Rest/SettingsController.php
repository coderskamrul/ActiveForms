<?php
/**
 * Global settings REST controller.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Rest;

use RadiusForms\Core\Config;
use RadiusForms\Core\Settings;

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
		// Return defaults merged over stored values so the UI always renders
		// every known control even before the option has been written.
		return $this->ok( Settings::all() );
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
		$current = is_array( $current ) ? $current : array();

		// Only persist values that map to a real, implemented feature. Controls
		// for not-yet-shipped features are rendered disabled in the UI and never
		// posted, so the option stays clean as the feature set grows.
		$clean = array();

		$clean['label_placement']          = $this->one_of( $body, 'label_placement', array( 'top', 'right', 'bottom', 'left', 'hide' ), 'top' );
		$clean['remove_data_on_uninstall'] = ! empty( $body['remove_data_on_uninstall'] );

		if ( isset( $body['recaptcha'] ) && is_array( $body['recaptcha'] ) ) {
			$clean['recaptcha'] = array(
				'provider'   => isset( $body['recaptcha']['provider'] ) ? sanitize_key( $body['recaptcha']['provider'] ) : '',
				'site_key'   => isset( $body['recaptcha']['site_key'] ) ? sanitize_text_field( $body['recaptcha']['site_key'] ) : '',
				'secret_key' => isset( $body['recaptcha']['secret_key'] ) ? sanitize_text_field( $body['recaptcha']['secret_key'] ) : '',
			);
		}

		if ( isset( $body['messages'] ) && is_array( $body['messages'] ) ) {
			$allowed = array( 'required', 'invalid_email', 'invalid_url', 'invalid_number' );
			$messages = array();
			foreach ( $allowed as $key ) {
				$messages[ $key ] = isset( $body['messages'][ $key ] ) ? sanitize_text_field( $body['messages'][ $key ] ) : '';
			}
			$clean['messages'] = $messages;
		}

		$merged = array_merge( $current, $clean );
		update_option( Config::OPTION_SETTINGS, $merged );
		Settings::flush();

		return $this->ok( Settings::all() );
	}

	/**
	 * Return a posted value only when it is one of an allowed set, else default.
	 *
	 * @param array  $body    Request body.
	 * @param string $key     Field key.
	 * @param array  $allowed Allowed values.
	 * @param string $default Fallback.
	 * @return string
	 */
	private function one_of( $body, $key, $allowed, $default ) {
		$value = isset( $body[ $key ] ) ? sanitize_key( $body[ $key ] ) : '';
		return in_array( $value, $allowed, true ) ? $value : $default;
	}
}
