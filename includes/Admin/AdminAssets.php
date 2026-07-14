<?php
/**
 * Admin React app asset loader + PHP→JS config bridge.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Admin;

use RadiusForms\Core\Config;
use RadiusForms\Core\Container;
use RadiusForms\Support\Countries;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the built React bundle only on RadiusForms screens and publishes the
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
	 * Enqueue the app on RadiusForms admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( false === strpos( $hook, Config::MENU_SLUG ) ) {
			return;
		}

		$dist = RADIUSFORMS_URL . 'assets/dist/';
		$path = RADIUSFORMS_PATH . 'assets/dist/';

		$js_handle  = 'radiusforms-app';
		$css_handle = 'radiusforms-app-css';

		if ( file_exists( $path . 'radiusforms.css' ) ) {
			// Dashicons backs the builder field/palette iconography.
			wp_enqueue_style( $css_handle, $dist . 'radiusforms.css', array( 'dashicons' ), Config::asset_version( 'assets/dist/radiusforms.css' ) );
		}

		if ( file_exists( $path . 'radiusforms.js' ) ) {
			wp_enqueue_script( $js_handle, $dist . 'radiusforms.js', array(), Config::asset_version( 'assets/dist/radiusforms.js' ), true );
		} else {
			// Build not present yet: show a helpful notice instead of a blank screen.
			add_action( 'admin_notices', array( $this, 'build_notice' ) );
			return;
		}

		// Emit design tokens as CSS variables so PHP and React stay in sync.
		wp_add_inline_style( $css_handle, $this->tokens_css() );

		wp_localize_script( $js_handle, 'RadiusFormsConfig', $this->config() );
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
			'version'       => RADIUSFORMS_VERSION,
			'restUrl'       => esc_url_raw( rest_url( Config::REST_NAMESPACE ) ),
			'restNamespace' => Config::REST_NAMESPACE,
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'adminUrl'      => esc_url_raw( admin_url( 'admin.php?page=' . Config::MENU_SLUG ) ),
			'home'          => esc_url_raw( home_url( '/' ) ),
			'assetsUrl'     => esc_url_raw( RADIUSFORMS_URL . 'assets/' ),
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
			'dashboard'    => __( 'Dashboard', 'radiusforms' ),
			'forms'        => __( 'Forms', 'radiusforms' ),
			'entries'      => __( 'Entries', 'radiusforms' ),
			'reports'      => __( 'Reports', 'radiusforms' ),
			'settings'     => __( 'Settings', 'radiusforms' ),
			'addNew'       => __( 'Add New Form', 'radiusforms' ),
			'save'         => __( 'Save', 'radiusforms' ),
			'saved'        => __( 'Saved', 'radiusforms' ),
			'preview'      => __( 'Preview', 'radiusforms' ),
			'delete'       => __( 'Delete', 'radiusforms' ),
			'duplicate'    => __( 'Duplicate', 'radiusforms' ),
			'edit'         => __( 'Edit', 'radiusforms' ),
			'search'       => __( 'Search…', 'radiusforms' ),
			'noForms'      => __( 'No forms yet. Create your first form.', 'radiusforms' ),
			'fieldLibrary' => __( 'Field Library', 'radiusforms' ),
			'fieldSettings' => __( 'Field Settings', 'radiusforms' ),
			'dropHere'     => __( 'Drag fields here to build your form', 'radiusforms' ),
			'confirmDelete' => __( 'Are you sure you want to delete this?', 'radiusforms' ),
			'inputFields'  => __( 'Input Fields', 'radiusforms' ),
			'customize'    => __( 'Input Customization', 'radiusforms' ),
			'history'      => __( 'History', 'radiusforms' ),
			'saveForm'     => __( 'Save Form', 'radiusforms' ),
			'previewDesign' => __( 'Preview & Design', 'radiusforms' ),
			'unsaved'      => __( 'Unsaved changes', 'radiusforms' ),
			'searchFields' => __( 'Search fields ( press / to focus )', 'radiusforms' ),
			'undo'         => __( 'Undo', 'radiusforms' ),
			'redo'         => __( 'Redo', 'radiusforms' ),
			'back'         => __( 'Back', 'radiusforms' ),
		);
	}

	/**
	 * Build the inline CSS-variable block from design tokens.
	 *
	 * The token tree is filterable (radiusforms/design_tokens), so every key and
	 * value is sanitized before it is concatenated into the CSS context: a key is
	 * reduced to [a-z0-9-] and a value is stripped of any character that could
	 * terminate the declaration or the rule block, or escape the <style> element.
	 *
	 * @return string
	 */
	private function tokens_css() {
		$t   = Config::design_tokens();
		$css = ':root, #radiusforms-app {';

		$groups = array(
			'color'  => isset( $t['color'] ) ? $t['color'] : array(),
			'radius' => isset( $t['radius'] ) ? $t['radius'] : array(),
			'shadow' => isset( $t['shadow'] ) ? $t['shadow'] : array(),
			'space'  => isset( $t['space'] ) ? $t['space'] : array(),
		);

		foreach ( $groups as $group => $items ) {
			foreach ( (array) $items as $key => $value ) {
				$css .= $this->css_var( $group . '-' . $this->kebab( $key ), $value );
			}
		}

		if ( isset( $t['font']['family'] ) ) {
			$css .= $this->css_var( 'font', $t['font']['family'] );
		}
		if ( isset( $t['font']['size'] ) ) {
			foreach ( (array) $t['font']['size'] as $key => $value ) {
				$css .= $this->css_var( 'font-' . $this->kebab( $key ), $value );
			}
		}
		if ( isset( $t['motion']['normal'], $t['motion']['easing'] ) ) {
			$css .= $this->css_var( 'motion', $t['motion']['normal'] . ' ' . $t['motion']['easing'] );
		}

		$css .= '}';

		return $css;
	}

	/**
	 * Render one sanitized CSS custom property, or '' when it is unsafe/empty.
	 *
	 * @param string $name  Variable name (without the --radiusforms- prefix).
	 * @param mixed  $value Raw token value.
	 * @return string
	 */
	private function css_var( $name, $value ) {
		$name  = preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $name ) );
		$value = $this->css_value( $value );

		if ( '' === $name || '' === $value ) {
			return '';
		}

		return '--radiusforms-' . $name . ':' . $value . ';';
	}

	/**
	 * Sanitize a value for use inside a CSS declaration.
	 *
	 * Removes comment markers and any character that could close the declaration,
	 * the rule block, or the surrounding <style> element, and rejects values that
	 * try to pull in external resources or script-like CSS.
	 *
	 * @param mixed $value Raw token value.
	 * @return string Safe CSS value, or '' when it must be dropped.
	 */
	private function css_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = (string) $value;
		$value = preg_replace( '#/\*|\*/#', '', $value );
		$value = str_replace( array( '<', '>', '{', '}', ';', '\\' ), '', $value );

		if ( preg_match( '/(?:url\s*\(|expression\s*\(|@import|javascript\s*:)/i', $value ) ) {
			return '';
		}

		return trim( $value );
	}

	/**
	 * camelCase → kebab-case.
	 *
	 * @param string $value Source.
	 * @return string
	 */
	private function kebab( $value ) {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', (string) $value ) );
	}

	/**
	 * Notice shown when the JS build is missing.
	 *
	 * @return void
	 */
	public function build_notice() {
		echo '<div class="notice notice-warning"><p>'
			. esc_html__( 'RadiusForms admin assets have not been built yet. Run "nvm use 20 && npm install && npm run build" in the plugin directory.', 'radiusforms' )
			. '</p></div>';
	}
}
