<?php
/**
 * Mailchimp email-marketing integration.
 *
 * @package EasyForms
 */

namespace EasyForms\Integrations\Providers;

use EasyForms\Integrations\AbstractIntegration;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Subscribes the submitter's email to a Mailchimp audience.
 */
class MailchimpIntegration extends AbstractIntegration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->slug     = 'mailchimp';
		$this->title    = __( 'Mailchimp', 'easyforms' );
		$this->category = 'email_marketing';
	}

	/**
	 * {@inheritDoc}
	 */
	public function global_settings_fields() {
		return array(
			array( 'key' => 'api_key', 'type' => 'password', 'label' => __( 'API Key', 'easyforms' ), 'required' => true ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function feed_settings_fields() {
		return array(
			array( 'key' => 'list_id', 'type' => 'text', 'label' => __( 'Audience / List ID', 'easyforms' ), 'required' => true ),
			array( 'key' => 'email_field', 'type' => 'field_map', 'label' => __( 'Email field', 'easyforms' ), 'required' => true ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_configured() {
		$settings = $this->settings();
		return ! empty( $settings['api_key'] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function process( $feed, $entry, $form ) {
		$settings = $this->settings();
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		if ( ! $api_key || false === strpos( $api_key, '-' ) ) {
			return new \WP_Error( 'easyforms_mc_key', __( 'Mailchimp API key is not configured.', 'easyforms' ) );
		}

		$dc       = substr( strrchr( $api_key, '-' ), 1 );
		$list_id  = isset( $feed['list_id'] ) ? sanitize_text_field( $feed['list_id'] ) : '';
		$response = Arr::get( $entry, 'response', array() );
		$email    = '';

		$email_key = isset( $feed['email_field'] ) ? $feed['email_field'] : '';
		if ( $email_key && isset( $response[ $email_key ] ) ) {
			$email = $response[ $email_key ];
		}

		if ( ! is_email( $email ) || ! $list_id ) {
			return new \WP_Error( 'easyforms_mc_email', __( 'A valid email and list ID are required.', 'easyforms' ) );
		}

		$url = sprintf( 'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s', $dc, $list_id, md5( strtolower( $email ) ) );

		$result = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'apikey ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'email_address' => $email,
						'status_if_new' => 'subscribed',
						'status'        => 'subscribed',
					)
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$code = (int) wp_remote_retrieve_response_code( $result );
		return $code >= 200 && $code < 300;
	}
}
