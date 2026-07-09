<?php
/**
 * Slack notification integration.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Integrations\Providers;

use ActiveForms\Integrations\AbstractIntegration;
use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Posts a summary of each submission to a Slack incoming webhook.
 */
class SlackIntegration extends AbstractIntegration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->slug     = 'slack';
		$this->title    = __( 'Slack', 'activeforms' );
		$this->category = 'notification';
	}

	/**
	 * {@inheritDoc}
	 */
	public function global_settings_fields() {
		return array(); // Webhook URL is per-feed.
	}

	/**
	 * {@inheritDoc}
	 */
	public function feed_settings_fields() {
		return array(
			array( 'key' => 'webhook_url', 'type' => 'url', 'label' => __( 'Slack Webhook URL', 'activeforms' ), 'required' => true ),
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
		$url = isset( $feed['webhook_url'] ) ? esc_url_raw( $feed['webhook_url'] ) : '';
		if ( ! $url ) {
			return new \WP_Error( 'activeforms_slack_url', __( 'Slack webhook URL is missing.', 'activeforms' ) );
		}

		$lines = array( '*' . Arr::get( $form, 'title', __( 'New submission', 'activeforms' ) ) . '*' );
		foreach ( (array) Arr::get( $entry, 'response', array() ) as $key => $value ) {
			$value   = is_array( $value ) ? implode( ', ', $value ) : $value;
			$lines[] = $key . ': ' . wp_strip_all_tags( (string) $value );
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'text' => implode( "\n", $lines ) ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return 200 === (int) wp_remote_retrieve_response_code( $response );
	}
}
