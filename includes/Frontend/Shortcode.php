<?php
/**
 * [radiusforms] shortcode + frontend asset loading.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Frontend;

use RadiusForms\Core\Container;
use RadiusForms\Core\Config;
use RadiusForms\Models\Form;
use RadiusForms\Support\Arr;

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
		add_shortcode( 'radiusforms', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (but do not enqueue) frontend assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'radiusforms-frontend', RADIUSFORMS_URL . 'assets/frontend/form.css', array(), Config::asset_version( 'assets/frontend/form.css' ) );
		wp_register_script( 'radiusforms-frontend', RADIUSFORMS_URL . 'assets/frontend/form.js', array(), Config::asset_version( 'assets/frontend/form.js' ), true );
		wp_localize_script(
			'radiusforms-frontend',
			'RadiusFormsFront',
			array(
				'restUrl' => esc_url_raw( rest_url( 'radiusforms/v1/submit' ) ),
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
			'radiusforms'
		);

		$form = Form::find( (int) $atts['id'] );
		if ( ! $form || 'published' !== $form['status'] ) {
			return '';
		}

		if ( ! $this->assets_loaded ) {
			wp_enqueue_style( 'radiusforms-frontend' );
			wp_enqueue_script( 'radiusforms-frontend' );
			$this->assets_loaded = true;
		}

		/** @var FormRenderer $renderer */
		$renderer = $this->container->get( 'renderer' );
		return $renderer->render( $form );
	}
}
