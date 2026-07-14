<?php
/**
 * Base REST controller.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Rest;

use RadiusForms\Core\Config;
use RadiusForms\Core\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Shared base for RadiusForms REST controllers: namespace, permission helpers,
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
	 * Permission check for read operations on entry data (entries, reports).
	 *
	 * @return bool
	 */
	public function can_read() {
		return current_user_can( Config::cap( 'view_entries' ) );
	}

	/**
	 * Permission check for reading form definitions.
	 *
	 * A form definition carries its schema, settings, and integration configuration,
	 * which is editor-level data. It is therefore gated on the form-editing
	 * capability rather than the entry-viewing one, so a user who may only read
	 * entries never receives full form definitions.
	 *
	 * @return bool
	 */
	public function can_edit_forms() {
		return current_user_can( Config::cap( 'edit_forms' ) );
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
