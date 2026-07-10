<?php
/**
 * ActiveForms Pro bootstrap.
 *
 * @package ActiveFormsPro
 */

namespace ActiveFormsPro;

use ActiveFormsPro\Fields\PhoneField;
use ActiveFormsPro\Fields\RatingField;
use ActiveFormsPro\Fields\RangeField;
use ActiveFormsPro\Fields\ColorField;
use ActiveFormsPro\Fields\NpsField;
use ActiveFormsPro\Fields\SignatureField;
use ActiveFormsPro\Fields\RichTextField;
use ActiveFormsPro\Fields\RepeaterField;
use ActiveFormsPro\Fields\FileUploadField;
use ActiveFormsPro\Fields\ImageUploadField;
use ActiveFormsPro\Integrations\GoogleSheetsIntegration;
use ActiveFormsPro\Rest\UploadController;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the former Pro features onto the free plugin's documented extension
 * points (activeforms/register_fields, activeforms/register_integrations). Since the
 * Pro add-on has been merged into ActiveForms, these features are always enabled.
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

		add_action( 'activeforms/register_fields', array( $this, 'register_fields' ) );
		add_action( 'activeforms/register_integrations', array( $this, 'register_integrations' ) );
		// Register the Pro bundle alongside the free one...
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 20 );
		// ...then enqueue it exactly when a form actually renders (the free plugin
		// enqueues its own assets lazily at this same moment, during the_content).
		add_filter( 'activeforms/rendering_form', array( $this, 'enqueue_assets' ), 10, 2 );
	}

	/**
	 * Register the bundled advanced field types.
	 *
	 * @param \ActiveForms\Fields\FieldRegistry $registry Field registry.
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
	 * Register the bundled integrations.
	 *
	 * @param \ActiveForms\Integrations\IntegrationRegistry $registry Integration registry.
	 * @return void
	 */
	public function register_integrations( $registry ) {
		$registry->register( new GoogleSheetsIntegration() );
	}

	/**
	 * Register (but do not enqueue) the Pro frontend bundle.
	 *
	 * @return void
	 */
	public function register_assets() {
		$js  = ACTIVEFORMS_PATH . 'assets/frontend/pro-forms.js';
		$css = ACTIVEFORMS_PATH . 'assets/frontend/pro-forms.css';

		wp_register_style(
			'activeforms-pro-frontend',
			ACTIVEFORMS_URL . 'assets/frontend/pro-forms.css',
			array( 'activeforms-frontend' ),
			file_exists( $css ) ? (string) filemtime( $css ) : ACTIVEFORMS_VERSION
		);
		wp_register_script(
			'activeforms-pro-frontend',
			ACTIVEFORMS_URL . 'assets/frontend/pro-forms.js',
			array( 'activeforms-frontend' ),
			file_exists( $js ) ? (string) filemtime( $js ) : ACTIVEFORMS_VERSION,
			true
		);
		wp_localize_script(
			'activeforms-pro-frontend',
			'ActiveFormsProFront',
			array(
				'uploadUrl' => esc_url_raw( rest_url( UploadController::NAMESPACE . '/upload' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Enqueue the Pro bundle when a form renders. Hooked on the free plugin's
	 * activeforms/rendering_form filter so it loads only on pages with a form.
	 *
	 * @param string $html Rendered form markup (returned unchanged).
	 * @param array  $form Form schema.
	 * @return string
	 */
	public function enqueue_assets( $html, $form = array() ) {
		// Ensure registration ran even if wp_enqueue_scripts already fired.
		if ( ! wp_style_is( 'activeforms-pro-frontend', 'registered' ) ) {
			$this->register_assets();
		}
		wp_enqueue_style( 'activeforms-pro-frontend' );
		wp_enqueue_script( 'activeforms-pro-frontend' );
		return $html;
	}
}
