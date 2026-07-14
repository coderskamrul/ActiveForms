<?php
/**
 * RadiusForms Pro bootstrap.
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro;

use RadiusFormsPro\Fields\PhoneField;
use RadiusFormsPro\Fields\RatingField;
use RadiusFormsPro\Fields\RangeField;
use RadiusFormsPro\Fields\ColorField;
use RadiusFormsPro\Fields\NpsField;
use RadiusFormsPro\Fields\SignatureField;
use RadiusFormsPro\Fields\RichTextField;
use RadiusFormsPro\Fields\RepeaterField;
use RadiusFormsPro\Fields\FileUploadField;
use RadiusFormsPro\Fields\ImageUploadField;
use RadiusFormsPro\Rest\UploadController;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the former Pro features onto the free plugin's documented extension
 * points (radiusforms_register_fields, radiusforms_register_integrations). Since the
 * Pro add-on has been merged into RadiusForms, these features are always enabled.
 */
final class Plugin {

	/**
	 * Singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

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
	 * Constructor.
	 */
	private function __construct() {
		( new UploadController() )->register();

		add_action( 'radiusforms_register_fields', array( $this, 'register_fields' ) );
		// Register the Pro bundle alongside the free one...
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 20 );
		// ...then enqueue it exactly when a form actually renders (the free plugin
		// enqueues its own assets lazily at this same moment, during the_content).
		add_filter( 'radiusforms/rendering_form', array( $this, 'enqueue_assets' ), 10, 2 );
	}

	/**
	 * Register the bundled advanced field types.
	 *
	 * @param \RadiusForms\Fields\FieldRegistry $registry Field registry.
	 * @return void
	 */
	public function register_fields( $registry ) {
		$registry->register( new PhoneField() );
		$registry->register( new RatingField() );
		$registry->register( new RangeField() );
		$registry->register( new ColorField() );
		$registry->register( new NpsField() );
		$registry->register( new SignatureField() );
		$registry->register( new RichTextField() );
		$registry->register( new RepeaterField() );
		$registry->register( new FileUploadField() );
		$registry->register( new ImageUploadField() );
	}

	/**
	 * Register (but do not enqueue) the Pro frontend bundle.
	 *
	 * @return void
	 */
	public function register_assets() {
		$js  = RADIUSFORMS_PATH . 'assets/frontend/pro-forms.js';
		$css = RADIUSFORMS_PATH . 'assets/frontend/pro-forms.css';

		wp_register_style(
			'radiusforms-pro-frontend',
			RADIUSFORMS_URL . 'assets/frontend/pro-forms.css',
			array( 'radiusforms-frontend' ),
			file_exists( $css ) ? (string) filemtime( $css ) : RADIUSFORMS_VERSION
		);
		wp_register_script(
			'radiusforms-pro-frontend',
			RADIUSFORMS_URL . 'assets/frontend/pro-forms.js',
			array( 'radiusforms-frontend' ),
			file_exists( $js ) ? (string) filemtime( $js ) : RADIUSFORMS_VERSION,
			true
		);
		wp_localize_script(
			'radiusforms-pro-frontend',
			'RadiusFormsProFront',
			array(
				'uploadUrl' => esc_url_raw( rest_url( UploadController::NAMESPACE . '/upload' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Enqueue the Pro bundle when a form renders. Hooked on the free plugin's
	 * radiusforms/rendering_form filter so it loads only on pages with a form.
	 *
	 * @param string $html Rendered form markup (returned unchanged).
	 * @param array  $form Form schema.
	 * @return string
	 */
	public function enqueue_assets( $html, $form = array() ) {
		// Ensure registration ran even if wp_enqueue_scripts already fired.
		if ( ! wp_style_is( 'radiusforms-pro-frontend', 'registered' ) ) {
			$this->register_assets();
		}
		wp_enqueue_style( 'radiusforms-pro-frontend' );
		wp_enqueue_script( 'radiusforms-pro-frontend' );
		return $html;
	}
}
