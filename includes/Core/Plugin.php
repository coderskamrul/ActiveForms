<?php
/**
 * Plugin bootstrap / orchestrator.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Core;

use RadiusForms\Admin\Menu;
use RadiusForms\Admin\AdminAssets;
use RadiusForms\Fields\FieldRegistry;
use RadiusForms\Notifications\SmartCodes;
use RadiusForms\Rest\RestServiceProvider;
use RadiusForms\Frontend\Shortcode;
use RadiusForms\Frontend\FormRenderer;
use RadiusForms\Frontend\SubmissionProcessor;
use RadiusForms\Frontend\PreviewPage;
use RadiusForms\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton that builds the service container and registers WordPress hooks.
 */
final class Plugin {

	/**
	 * Single instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Resolve the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wires services and hooks.
	 */
	private function __construct() {
		$this->container = new Container();
		$this->register_services();
		$this->boot();
	}

	/**
	 * Access the container.
	 *
	 * @return Container
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Convenience accessor for a container service.
	 *
	 * @param string $key Service key.
	 * @return mixed
	 */
	public function make( $key ) {
		return $this->container->get( $key );
	}

	/**
	 * Register core services as lazy singletons.
	 *
	 * @return void
	 */
	private function register_services() {
		$c = $this->container;

		$c->bind(
			'logger',
			function () {
				return new Logger();
			}
		);

		$c->bind(
			'fields',
			function () {
				$registry = new FieldRegistry();
				$registry->register_defaults();
				/**
				 * Allow add-ons (RadiusForms Pro) to register field types.
				 *
				 * @param FieldRegistry $registry Field registry.
				 */
				do_action( 'radiusforms_register_fields', $registry );
				return $registry;
			}
		);

		$c->bind(
			'smartcodes',
			function () {
				return new SmartCodes();
			}
		);

		$c->bind(
			'renderer',
			function ( $c ) {
				return new FormRenderer( $c->get( 'fields' ) );
			}
		);
	}

	/**
	 * Register WordPress hooks for each subsystem.
	 *
	 * @return void
	 */
	private function boot() {
		// REST API.
		( new RestServiceProvider( $this->container ) )->register();

		// Frontend: shortcode, rendering, submission endpoint, assets.
		( new Shortcode( $this->container ) )->register();
		( new SubmissionProcessor( $this->container ) )->register();
		( new PreviewPage( $this->container ) )->register();

		// Admin: menu and React app assets.
		if ( is_admin() ) {
			( new Menu() )->register();
			( new AdminAssets( $this->container ) )->register();
		}

		// Translations for WordPress.org-hosted plugins load automatically since
		// WP 4.6, so no manual load_plugin_textdomain() call is needed.

		/**
		 * Fires once RadiusForms has finished booting its core subsystems.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'radiusforms/booted', $this );
	}
}
