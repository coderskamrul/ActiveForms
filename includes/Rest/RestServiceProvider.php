<?php
/**
 * Registers all ActiveForms REST controllers.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Rest;

use ActiveForms\Core\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Wires controllers onto rest_api_init.
 */
class RestServiceProvider {

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
	 * Register the hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Instantiate and register each controller.
	 *
	 * @return void
	 */
	public function register_routes() {
		$controllers = array(
			new FormsController( $this->container ),
			new EntriesController( $this->container ),
			new SettingsController( $this->container ),
			new BuilderController( $this->container ),
			new IntegrationsController( $this->container ),
			new ReportsController( $this->container ),
		);

		/**
		 * Filter the list of REST controllers (Pro can append more).
		 *
		 * @param array     $controllers Controller instances.
		 * @param Container  $container   Service container.
		 */
		$controllers = apply_filters( 'activeforms/rest_controllers', $controllers, $this->container );

		foreach ( $controllers as $controller ) {
			if ( $controller instanceof AbstractController ) {
				$controller->register_routes();
			}
		}
	}
}
