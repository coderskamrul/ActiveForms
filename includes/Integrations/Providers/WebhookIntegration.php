<?php
/**
 * Generic webhook integration.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Integrations\Providers;

use ActiveForms\Integrations\AbstractIntegration;

defined( 'ABSPATH' ) || exit;

/**
 * Posts the submitted entry as JSON to a configured per-form URL.
 */
class WebhookIntegration extends AbstractIntegration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->slug     = 'webhook';
		$this->title    = __( 'Webhook', 'activeforms' );
		$this->category = 'automation';
	}

	/**
	 * {@inheritDoc}
	 */
	public function global_settings_fields() {
		return array(); // No account-level config required.
	}

	/**
	 * {@inheritDoc}
	 */
	public function feed_settings_fields() {
		return array(
			array( 'key' => 'url', 'type' => 'url', 'label' => __( 'Request URL', 'activeforms' ), 'required' => true ),
			array(
				'key'     => 'method',
				'type'    => 'select',
				'label'   => __( 'Method', 'activeforms' ),
				'options' => array(
					array( 'value' => 'POST', 'label' => 'POST' ),
					array( 'value' => 'PUT', 'label' => 'PUT' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_configured() {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function process( $feed, $entry, $form ) {
		$url = isset( $feed['url'] ) ? esc_url_raw( $feed['url'] ) : '';
		if ( ! $url ) {
			return new \WP_Error( 'activeforms_webhook_url', __( 'Webhook URL is missing.', 'activeforms' ) );
		}

		$method = isset( $feed['method'] ) && 'PUT' === $feed['method'] ? 'PUT' : 'POST';

		$response = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'form_id' => isset( $entry['form_id'] ) ? $entry['form_id'] : 0,
						'entry'   => isset( $entry['response'] ) ? $entry['response'] : array(),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}
}
