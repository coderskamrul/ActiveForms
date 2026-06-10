<?php
/**
 * Spam protection coordinator.
 *
 * @package EasyForms
 */

namespace EasyForms\Spam;

use EasyForms\Core\Config;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Runs honeypot and captcha checks against a submission.
 */
class SpamGuard {

	/**
	 * The honeypot field name rendered on every form.
	 *
	 * Deliberately neutral: names containing tokens like "email", "name", "url"
	 * or "phone" get auto-filled by browsers/password managers, which would
	 * trip the honeypot for legitimate users.
	 */
	const HONEYPOT = 'easyforms_hp_field';

	/**
	 * Inspect a submission for spam signals.
	 *
	 * @param array $form    Form schema.
	 * @param array $payload Raw posted payload.
	 * @return true|string True when clean, error message otherwise.
	 */
	public function check( $form, $payload ) {
		// Honeypot: a hidden field bots tend to fill in.
		$hp = isset( $payload[ self::HONEYPOT ] ) ? trim( (string) $payload[ self::HONEYPOT ] ) : '';
		if ( '' !== $hp ) {
			return __( 'Your submission was flagged as spam.', 'easyforms' );
		}

		$settings = Arr::get( $form, 'settings', array() );
		$captcha  = Arr::get( $settings, 'captcha', array() );

		if ( ! empty( $captcha['enabled'] ) ) {
			$token  = isset( $payload['easyforms_captcha_token'] ) ? $payload['easyforms_captcha_token'] : '';
			$result = $this->verify_captcha( $token );
			if ( true !== $result ) {
				return $result;
			}
		}

		/**
		 * Allow add-ons (Akismet, CleanTalk) to run additional spam checks.
		 *
		 * @param true|string $verdict Current verdict.
		 * @param array       $form    Form schema.
		 * @param array       $payload Posted payload.
		 */
		return apply_filters( 'easyforms/spam_check', true, $form, $payload );
	}

	/**
	 * Render the honeypot markup.
	 *
	 * @return string
	 */
	public function honeypot_markup() {
		return '<div class="easyforms-hp" aria-hidden="true" style="position:absolute;left:-9999px;">'
			. '<label>' . esc_html__( 'Leave this field empty', 'easyforms' ) . '</label>'
			. '<input type="text" name="' . esc_attr( self::HONEYPOT ) . '" tabindex="-1" autocomplete="off" value="" />'
			. '</div>';
	}

	/**
	 * Verify a captcha token against the configured provider.
	 *
	 * @param string $token Client token.
	 * @return true|string
	 */
	protected function verify_captcha( $token ) {
		$settings = get_option( Config::OPTION_SETTINGS, array() );
		$cfg      = isset( $settings['recaptcha'] ) ? $settings['recaptcha'] : array();
		$provider = isset( $cfg['provider'] ) ? $cfg['provider'] : '';
		$secret   = isset( $cfg['secret_key'] ) ? $cfg['secret_key'] : '';

		if ( ! $provider || ! $secret ) {
			return true; // Not configured: do not block.
		}
		if ( ! $token ) {
			return __( 'Please complete the captcha challenge.', 'easyforms' );
		}

		$endpoints = array(
			'recaptcha' => 'https://www.google.com/recaptcha/api/siteverify',
			'hcaptcha'  => 'https://hcaptcha.com/siteverify',
			'turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		);
		$endpoint = isset( $endpoints[ $provider ] ) ? $endpoints[ $provider ] : $endpoints['recaptcha'];

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return true; // Fail open on transport error.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['success'] ) ) {
			return __( 'Captcha verification failed. Please try again.', 'easyforms' );
		}

		return true;
	}
}
