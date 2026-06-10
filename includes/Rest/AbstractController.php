<?php
/**
 * Base REST controller.
 *
 * @package EasyForms
 */

namespace EasyForms\Rest;

use EasyForms\Core\Config;
use EasyForms\Core\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Shared base for EasyForms REST controllers: namespace, permission helpers,
 * and response shaping.
 */
abstract class AbstractController {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = Config::REST_NAMESPACE;

	/**
	 * Route base (without leading slash).
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register routes for this controller.
	 *
	 * @return void
	 */
	abstract public function register_routes();

	/**
	 * Permission check for read operations.
	 *
	 * @return bool
	 */
	public function can_read() {
		return current_user_can( Config::cap( 'view_entries' ) );
	}

	/**
	 * Permission check for managing forms.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( Config::cap( 'manage' ) );
	}

	/**
	 * Shape a successful response.
	 *
	 * @param mixed $data   Payload.
	 * @param int   $status HTTP status.
	 * @return \WP_REST_Response
	 */
	protected function ok( $data, $status = 200 ) {
		return new \WP_REST_Response( array( 'success' => true, 'data' => $data ), $status );
	}

	/**
	 * Shape an error response.
	 *
	 * @param string $message Message.
	 * @param int    $status  HTTP status.
	 * @return \WP_REST_Response
	 */
	protected function fail( $message, $status = 400 ) {
		return new \WP_REST_Response( array( 'success' => false, 'message' => $message ), $status );
	}
}
