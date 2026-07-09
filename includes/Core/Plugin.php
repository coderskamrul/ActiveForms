<?php
/**
 * Plugin bootstrap / orchestrator.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Core;

use ActiveForms\Admin\Menu;
use ActiveForms\Admin\AdminAssets;
use ActiveForms\Fields\FieldRegistry;
use ActiveForms\Integrations\IntegrationRegistry;
use ActiveForms\Notifications\SmartCodes;
use ActiveForms\Rest\RestServiceProvider;
use ActiveForms\Frontend\Shortcode;
use ActiveForms\Frontend\FormRenderer;
use ActiveForms\Frontend\SubmissionProcessor;
use ActiveForms\Frontend\PreviewPage;
use ActiveForms\Support\Logger;

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
				 * Allow add-ons (ActiveForms Pro) to register field types.
				 *
				 * @param FieldRegistry $registry Field registry.
				 */
				do_action( 'activeforms/register_fields', $registry );
				return $registry;
			}
		);

		$c->bind(
			'integrations',
			function () {
				$registry = new IntegrationRegistry();
				$registry->register_defaults();
				/**
				 * Allow add-ons to register integrations.
				 *
				 * @param IntegrationRegistry $registry Integration registry.
				 */
				do_action( 'activeforms/register_integrations', $registry );
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

		// Translations.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Fires once ActiveForms has finished booting its core subsystems.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'activeforms/booted', $this );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'activeforms', false, dirname( ACTIVEFORMS_BASENAME ) . '/languages' );
	}
}
