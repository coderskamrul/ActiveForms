<?php
/**
 * Public submission endpoint and processing pipeline.
 *
 * @package EasyForms
 */

namespace EasyForms\Frontend;

use EasyForms\Core\Container;
use EasyForms\Core\Config;
use EasyForms\Models\Form;
use EasyForms\Models\Entry;
use EasyForms\Spam\SpamGuard;
use EasyForms\Notifications\EmailNotifier;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the full submission lifecycle: nonce → spam → validation → store →
 * notify → integrations → confirmation response.
 */
class SubmissionProcessor {

	/**
	 * Container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register the public REST route.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
		add_action( 'easyforms_process_scheduled_actions', array( $this, 'process_queue' ) );
	}

	/**
	 * Register the submit route.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			Config::REST_NAMESPACE,
			'/submit',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true', // Public; nonce verified inside.
			)
		);
	}

	/**
	 * Handle a submission request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( $request ) {
		$payload = $request->get_params();
		$form_id = isset( $payload['easyforms_form_id'] ) ? (int) $payload['easyforms_form_id'] : 0;
		$nonce   = isset( $payload['easyforms_nonce'] ) ? $payload['easyforms_nonce'] : '';

		if ( ! $form_id || ! wp_verify_nonce( $nonce, 'easyforms_submit_' . $form_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security check failed. Please refresh and try again.', 'easyforms' ),
				),
				403
			);
		}

		$form = Form::find( $form_id );
		if ( ! $form || 'published' !== $form['status'] ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'This form is not available.', 'easyforms' ),
				),
				404
			);
		}

		// Spam check.
		$guard   = new SpamGuard();
		$verdict = $guard->check( $form, $payload );
		if ( true !== $verdict ) {
			return new \WP_REST_Response( array( 'success' => false, 'message' => $verdict ), 422 );
		}

		/**
		 * Fires before validation runs.
		 *
		 * @param array $form    Form schema.
		 * @param array $payload Raw payload.
		 */
		do_action( 'easyforms/before_validation', $form, $payload );

		// Validate + sanitize.
		$validator = new Validator( $this->container->get( 'fields' ) );
		if ( ! $validator->validate( $form, $payload ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please correct the highlighted fields.', 'easyforms' ),
					'errors'  => $validator->errors(),
				),
				422
			);
		}

		$values = $validator->values();

		// Persist.
		$entry_id = Entry::create(
			array(
				'form_id'    => $form_id,
				'response'   => $values,
				'source_url' => isset( $payload['easyforms_source_url'] ) ? $payload['easyforms_source_url'] : '',
				'ip'         => $this->client_ip(),
				'browser'    => $this->user_agent(),
			)
		);

		$entry = Entry::find( $entry_id );

		// Notifications.
		$notifier = new EmailNotifier( $this->container->get( 'smartcodes' ) );
		$notifier->send( $form, $entry );

		// Integrations (synchronous for the free core; queueable via Pro).
		$this->run_integrations( $form, $entry );

		/**
		 * Fires after a submission is stored and processed.
		 *
		 * @param array $entry Entry data.
		 * @param array $form  Form schema.
		 */
		do_action( 'easyforms/after_submission', $entry, $form );

		return new \WP_REST_Response(
			array(
				'success'      => true,
				'entry_id'     => $entry_id,
				'confirmation' => $this->confirmation( $form, $entry ),
			),
			200
		);
	}

	/**
	 * Build the confirmation response (message / redirect).
	 *
	 * @param array $form  Form schema.
	 * @param array $entry Entry data.
	 * @return array<string,string>
	 */
	protected function confirmation( $form, $entry ) {
		$settings     = Arr::get( $form, 'settings', array() );
		$confirmation = Arr::get( $settings, 'confirmation', array() );
		$type         = Arr::get( $confirmation, 'type', 'message' );

		$codes = $this->container->get( 'smartcodes' );

		if ( 'redirect' === $type && ! empty( $confirmation['url'] ) ) {
			return array(
				'type' => 'redirect',
				'url'  => esc_url_raw( $codes->parse( $confirmation['url'], $entry, $form ) ),
			);
		}

		$message = Arr::get( $confirmation, 'message', __( 'Thank you! Your submission has been received.', 'easyforms' ) );
		return array(
			'type'    => 'message',
			'message' => wp_kses_post( $codes->parse( $message, $entry, $form ) ),
		);
	}

	/**
	 * Run configured integration feeds for a form.
	 *
	 * @param array $form  Form schema.
	 * @param array $entry Entry data.
	 * @return void
	 */
	protected function run_integrations( $form, $entry ) {
		$settings = Arr::get( $form, 'settings', array() );
		$feeds    = Arr::get( $settings, 'integrations', array() );
		if ( empty( $feeds ) ) {
			return;
		}

		$registry = $this->container->get( 'integrations' );
		$logger   = $this->container->get( 'logger' );

		foreach ( (array) $feeds as $feed ) {
			if ( empty( $feed['enabled'] ) || empty( $feed['provider'] ) ) {
				continue;
			}
			$integration = $registry->get( $feed['provider'] );
			if ( ! $integration ) {
				continue;
			}

			$result = $integration->process( $feed, $entry, $form );
			if ( is_wp_error( $result ) ) {
				$logger->log( $feed['provider'], 'failed', $integration->slug(), $result->get_error_message(), array( 'form_id' => $form['id'], 'entry_id' => $entry['id'] ) );
			} else {
				$logger->log( $feed['provider'], $result ? 'success' : 'failed', $integration->slug(), '', array( 'form_id' => $form['id'], 'entry_id' => $entry['id'] ) );
			}
		}
	}

	/**
	 * Process the scheduled-actions queue (cron entry point, extensible).
	 *
	 * @return void
	 */
	public function process_queue() {
		/**
		 * Hook for add-ons to drain the async integration queue.
		 */
		do_action( 'easyforms/process_queue', $this->container );
	}

	/**
	 * Determine the client IP, respecting common proxy headers conservatively.
	 *
	 * @return string
	 */
	protected function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return preg_match( '/^[0-9a-f:.]+$/i', $ip ) ? $ip : '';
	}

	/**
	 * Short user-agent label.
	 *
	 * @return string
	 */
	protected function user_agent() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return substr( $ua, 0, 60 );
	}
}
