<?php
/**
 * Async file upload REST endpoint (Pro).
 *
 * @package ActiveFormsPro
 */

namespace ActiveFormsPro\Rest;

use ActiveFormsPro\Support\Uploads;

defined( 'ABSPATH' ) || exit;

/**
 * Receives a single file via multipart/form-data the moment a visitor picks it
 * in a File/Image upload field, stores it, and returns a reference the frontend
 * carries into the eventual form submission. Gated by a per-page nonce.
 */
class UploadController {

	/**
	 * REST namespace for Pro routes.
	 */
	const NAMESPACE = 'activeforms-pro/v1';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the upload route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/upload',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'check' ),
			)
		);
	}

	/**
	 * Permission callback: valid REST nonce.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function check( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error( 'activeforms_bad_nonce', __( 'Security check failed.', 'activeforms' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Handle the upload.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => __( 'No file was received.', 'activeforms' ) ),
				400
			);
		}

		// Constraints are echoed by the field markup; clamp them to safe bounds.
		$max_size = (int) $request->get_param( 'max_size' );
		$allowed  = (string) $request->get_param( 'allowed' );
		$allowed  = '' !== $allowed ? array_filter( array_map( 'trim', explode( ',', strtolower( $allowed ) ) ) ) : array();

		$stored = Uploads::store_file( $files['file'], $allowed, $max_size );
		if ( is_wp_error( $stored ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => $stored->get_error_message() ),
				422
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'file'    => array(
					'path' => $stored['path'],
					'url'  => $stored['url'],
					'name' => $stored['name'],
					'size' => $stored['size'],
				),
			),
			200
		);
	}
}
