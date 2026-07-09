<?php
/**
 * Email notification dispatcher.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Notifications;

use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Sends configured email notifications for a submission, parsing smart codes
 * and honoring conditional-send rules.
 */
class EmailNotifier {

	/**
	 * Smart-code parser.
	 *
	 * @var SmartCodes
	 */
	private $codes;

	/**
	 * Constructor.
	 *
	 * @param SmartCodes $codes Smart-code parser.
	 */
	public function __construct( SmartCodes $codes ) {
		$this->codes = $codes;
	}

	/**
	 * Send all enabled notifications for a form.
	 *
	 * @param array $form  Form schema (with settings).
	 * @param array $entry Entry data.
	 * @return void
	 */
	public function send( $form, $entry ) {
		$settings      = Arr::get( $form, 'settings', array() );
		$notifications = Arr::get( $settings, 'notifications', array() );

		foreach ( (array) $notifications as $notification ) {
			if ( empty( $notification['enabled'] ) ) {
				continue;
			}

			$to = $this->codes->parse( Arr::get( $notification, 'to', '' ), $entry, $form );
			$to = sanitize_email( $to );
			if ( ! is_email( $to ) ) {
				continue;
			}

			$subject = wp_strip_all_tags( $this->codes->parse( Arr::get( $notification, 'subject', '' ), $entry, $form ) );
			$body    = $this->codes->parse( Arr::get( $notification, 'body', '' ), $entry, $form );

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			$reply_to = sanitize_email( $this->codes->parse( Arr::get( $notification, 'reply_to', '' ), $entry, $form ) );
			if ( is_email( $reply_to ) ) {
				$headers[] = 'Reply-To: ' . $reply_to;
			}

			/**
			 * Filter notification arguments before sending.
			 *
			 * @param array $args Email args.
			 * @param array $form Form schema.
			 * @param array $entry Entry data.
			 */
			$args = apply_filters(
				'activeforms/notification_args',
				array(
					'to'      => $to,
					'subject' => $subject,
					'body'    => $this->wrap_body( $subject, $body ),
					'headers' => $headers,
				),
				$form,
				$entry
			);

			wp_mail( $args['to'], $args['subject'], $args['body'], $args['headers'] );
		}
	}

	/**
	 * Wrap the email body in a minimal branded HTML shell.
	 *
	 * @param string $subject Subject line.
	 * @param string $body    Body HTML.
	 * @return string
	 */
	protected function wrap_body( $subject, $body ) {
		$html  = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1f2937;">';
		$html .= '<div style="padding:16px 0;">' . wp_kses_post( wpautop( $body ) ) . '</div>';
		$html .= '</div>';
		return $html;
	}
}
