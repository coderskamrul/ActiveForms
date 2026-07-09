<?php
/**
 * [activeforms] shortcode + frontend asset loading.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Frontend;

use ActiveForms\Core\Container;
use ActiveForms\Core\Config;
use ActiveForms\Models\Form;
use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the public shortcode and loads frontend assets only when a form is
 * actually rendered on the page.
 */
class Shortcode {

	/**
	 * Container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Whether frontend assets have been enqueued already.
	 *
	 * @var bool
	 */
	private $assets_loaded = false;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'activeforms', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (but do not enqueue) frontend assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'activeforms-frontend', ACTIVEFORMS_URL . 'assets/frontend/form.css', array(), Config::asset_version( 'assets/frontend/form.css' ) );
		wp_register_script( 'activeforms-frontend', ACTIVEFORMS_URL . 'assets/frontend/form.js', array(), Config::asset_version( 'assets/frontend/form.js' ), true );
		wp_localize_script(
			'activeforms-frontend',
			'ActiveFormsFront',
			array(
				'restUrl' => esc_url_raw( rest_url( 'activeforms/v1/submit' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'   => 0,
				'type' => '',
			),
			$atts,
			'activeforms'
		);

		$form = Form::find( (int) $atts['id'] );
		if ( ! $form || 'published' !== $form['status'] ) {
			return '';
		}

		if ( ! $this->assets_loaded ) {
			wp_enqueue_style( 'activeforms-frontend' );
			wp_enqueue_script( 'activeforms-frontend' );
			$this->assets_loaded = true;
		}

		/** @var FormRenderer $renderer */
		$renderer = $this->container->get( 'renderer' );
		return $renderer->render( $form );
	}
}
