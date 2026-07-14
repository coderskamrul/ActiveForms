<?php
/**
 * Spam protection coordinator.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Spam;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the honeypot check against a submission.
 *
 * Protection is entirely local: nothing about a submission is sent to any
 * external service. Add-ons can layer on further checks via the
 * radiusforms/spam_check filter.
 */
class SpamGuard {

	/**
	 * The honeypot field name rendered on every form.
	 *
	 * Deliberately neutral: names containing tokens like "email", "name", "url"
	 * or "phone" get auto-filled by browsers/password managers, which would
	 * trip the honeypot for legitimate users.
	 */
	const HONEYPOT = 'radiusforms_hp_field';

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
			return __( 'Your submission was flagged as spam.', 'radiusforms' );
		}

		/**
		 * Allow add-ons (Akismet, CleanTalk) to run additional spam checks.
		 *
		 * @param true|string $verdict Current verdict.
		 * @param array       $form    Form schema.
		 * @param array       $payload Posted payload.
		 */
		return apply_filters( 'radiusforms/spam_check', true, $form, $payload );
	}

	/**
	 * Render the honeypot markup.
	 *
	 * @return string
	 */
	public function honeypot_markup() {
		return '<div class="radiusforms-hp" aria-hidden="true" style="position:absolute;left:-9999px;">'
			. '<label>' . esc_html__( 'Leave this field empty', 'radiusforms' ) . '</label>'
			. '<input type="text" name="' . esc_attr( self::HONEYPOT ) . '" tabindex="-1" autocomplete="off" value="" />'
			. '</div>';
	}
}
