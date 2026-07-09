<?php
/**
 * Admin React app asset loader + PHP→JS config bridge.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Admin;

use ActiveForms\Core\Config;
use ActiveForms\Core\Container;
use ActiveForms\Support\Countries;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the built React bundle only on ActiveForms screens and publishes the
 * single localized config object the app reads at boot.
 */
class AdminAssets {

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the app on ActiveForms admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( false === strpos( $hook, Config::MENU_SLUG ) ) {
			return;
		}

		$dist = ACTIVEFORMS_URL . 'assets/dist/';
		$path = ACTIVEFORMS_PATH . 'assets/dist/';

		$js_handle  = 'activeforms-app';
		$css_handle = 'activeforms-app-css';

		if ( file_exists( $path . 'activeforms.css' ) ) {
			// Dashicons backs the builder field/palette iconography.
			wp_enqueue_style( $css_handle, $dist . 'activeforms.css', array( 'dashicons' ), Config::asset_version( 'assets/dist/activeforms.css' ) );
		}

		if ( file_exists( $path . 'activeforms.js' ) ) {
			wp_enqueue_script( $js_handle, $dist . 'activeforms.js', array(), Config::asset_version( 'assets/dist/activeforms.js' ), true );
		} else {
			// Build not present yet: show a helpful notice instead of a blank screen.
			add_action( 'admin_notices', array( $this, 'build_notice' ) );
			return;
		}

		// Emit design tokens as CSS variables so PHP and React stay in sync.
		wp_add_inline_style( $css_handle, $this->tokens_css() );

		wp_localize_script( $js_handle, 'ActiveFormsConfig', $this->config() );
	}

	/**
	 * The single config object shared with React.
	 *
	 * @return array<string,mixed>
	 */
	private function config() {
		$caps = Config::capabilities();
		$user_caps = array();
		foreach ( $caps as $key => $cap ) {
			$user_caps[ $key ] = current_user_can( $cap );
		}

		return array(
			'version'       => ACTIVEFORMS_VERSION,
			'restUrl'       => esc_url_raw( rest_url( Config::REST_NAMESPACE ) ),
			'restNamespace' => Config::REST_NAMESPACE,
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'adminUrl'      => esc_url_raw( admin_url( 'admin.php?page=' . Config::MENU_SLUG ) ),
			'home'          => esc_url_raw( home_url( '/' ) ),
			'assetsUrl'     => esc_url_raw( ACTIVEFORMS_URL . 'assets/' ),
			'capabilities'  => $user_caps,
			'brand'         => Config::brand(),
			'designTokens'  => Config::design_tokens(),
			'currencies'    => array( 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'BDT', 'INR' ),
			'dateFormat'    => get_option( 'date_format' ),
			'countries'     => Countries::all(),
			'strings'       => $this->strings(),
		);
	}

	/**
	 * Translatable strings dictionary for the React app (keeps i18n in PHP).
	 *
	 * @return array<string,string>
	 */
	private function strings() {
		return array(
			'dashboard'    => __( 'Dashboard', 'activeforms' ),
			'forms'        => __( 'Forms', 'activeforms' ),
			'entries'      => __( 'Entries', 'activeforms' ),
			'reports'      => __( 'Reports', 'activeforms' ),
			'settings'     => __( 'Settings', 'activeforms' ),
			'integrations' => __( 'Integrations', 'activeforms' ),
			'addNew'       => __( 'Add New Form', 'activeforms' ),
			'save'         => __( 'Save', 'activeforms' ),
			'saved'        => __( 'Saved', 'activeforms' ),
			'preview'      => __( 'Preview', 'activeforms' ),
			'delete'       => __( 'Delete', 'activeforms' ),
			'duplicate'    => __( 'Duplicate', 'activeforms' ),
			'edit'         => __( 'Edit', 'activeforms' ),
			'search'       => __( 'Search…', 'activeforms' ),
			'noForms'      => __( 'No forms yet. Create your first form.', 'activeforms' ),
			'fieldLibrary' => __( 'Field Library', 'activeforms' ),
			'fieldSettings' => __( 'Field Settings', 'activeforms' ),
			'dropHere'     => __( 'Drag fields here to build your form', 'activeforms' ),
			'confirmDelete' => __( 'Are you sure you want to delete this?', 'activeforms' ),
			'inputFields'  => __( 'Input Fields', 'activeforms' ),
			'customize'    => __( 'Input Customization', 'activeforms' ),
			'history'      => __( 'History', 'activeforms' ),
			'saveForm'     => __( 'Save Form', 'activeforms' ),
			'previewDesign' => __( 'Preview & Design', 'activeforms' ),
			'unsaved'      => __( 'Unsaved changes', 'activeforms' ),
			'searchFields' => __( 'Search fields ( press / to focus )', 'activeforms' ),
			'undo'         => __( 'Undo', 'activeforms' ),
			'redo'         => __( 'Redo', 'activeforms' ),
			'conditionalLogic' => __( 'Conditional Logic', 'activeforms' ),
			'back'         => __( 'Back', 'activeforms' ),
		);
	}

	/**
	 * Build the inline CSS-variable block from design tokens.
	 *
	 * @return string
	 */
	private function tokens_css() {
		$t   = Config::design_tokens();
		$css = ':root, #activeforms-app {';

		foreach ( $t['color'] as $key => $value ) {
			$css .= '--activeforms-color-' . $this->kebab( $key ) . ':' . $value . ';';
		}
		foreach ( $t['radius'] as $key => $value ) {
			$css .= '--activeforms-radius-' . $key . ':' . $value . ';';
		}
		foreach ( $t['shadow'] as $key => $value ) {
			$css .= '--activeforms-shadow-' . $key . ':' . $value . ';';
		}
		foreach ( $t['space'] as $i => $value ) {
			$css .= '--activeforms-space-' . $i . ':' . $value . ';';
		}
		$css .= '--activeforms-font:' . $t['font']['family'] . ';';
		foreach ( $t['font']['size'] as $key => $value ) {
			$css .= '--activeforms-font-' . $key . ':' . $value . ';';
		}
		$css .= '--activeforms-motion:' . $t['motion']['normal'] . ' ' . $t['motion']['easing'] . ';';
		$css .= '}';

		return $css;
	}

	/**
	 * camelCase → kebab-case.
	 *
	 * @param string $value Source.
	 * @return string
	 */
	private function kebab( $value ) {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $value ) );
	}

	/**
	 * Notice shown when the JS build is missing.
	 *
	 * @return void
	 */
	public function build_notice() {
		echo '<div class="notice notice-warning"><p>'
			. esc_html__( 'ActiveForms admin assets have not been built yet. Run "nvm use 20 && npm install && npm run build" in the plugin directory.', 'activeforms' )
			. '</p></div>';
	}
}
