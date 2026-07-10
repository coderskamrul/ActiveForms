<?php
/**
 * Standalone frontend preview page.
 *
 * Renders a saved form with the real FormRenderer + frontend stylesheet inside
 * a lightweight "browser window" chrome with device toggles, served at
 * `?activeforms_preview=<id>`. Restricted to users who can manage forms. Form
 * submission is disabled in preview so no entries are created.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Frontend;

use ActiveForms\Core\Config;
use ActiveForms\Core\Container;
use ActiveForms\Models\Form;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the preview screen onto template_redirect.
 */
class PreviewPage {

	/**
	 * Service container.
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
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	/**
	 * Render the preview page when requested.
	 *
	 * @return void
	 */
	public function maybe_render() {
		if ( ! isset( $_GET['activeforms_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$id = absint( wp_unslash( $_GET['activeforms_preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! current_user_can( Config::cap( 'manage' ) ) ) {
			wp_die( esc_html__( 'You are not allowed to preview ActiveForms forms.', 'activeforms' ), 403 );
		}

		$form = Form::find( $id );
		if ( ! $form ) {
			wp_die( esc_html__( 'Form not found.', 'activeforms' ), 404 );
		}

		$this->render( $form );
		exit;
	}

	/**
	 * Output the full preview document.
	 *
	 * @param array $form Form schema.
	 * @return void
	 */
	private function render( $form ) {
		$title = isset( $form['title'] ) ? $form['title'] : __( 'Form Preview', 'activeforms' );

		// Run the exact same asset pipeline the front end uses so the preview is
		// a true representation. Firing wp_enqueue_scripts lets the shortcode and
		// the Pro add-on register their frontend bundles (with localized data);
		// rendering the form then triggers the activeforms/rendering_form filter
		// that enqueues the Pro bundle, just like a real page.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally fires the WordPress core enqueue hook so registered frontend assets load on this standalone preview template.
		do_action( 'wp_enqueue_scripts' );

		$renderer  = $this->container->get( 'renderer' );
		$form_html = $renderer->render( $form );

		// The core bundle (form.css + form.js) always loads on the front end; the
		// JS is what enhances the multi-select into a tag picker, applies input
		// masks, turns searchable selects into dropdowns, etc.
		wp_enqueue_style( 'activeforms-frontend' );
		wp_enqueue_script( 'activeforms-frontend' );

		// Attach the preview chrome CSS and the device-switcher / submit-disable JS
		// as inline additions to the frontend handles, so they print through the
		// normal wp_print_styles()/wp_print_scripts() pipeline instead of raw
		// <style>/<script> tags.
		wp_add_inline_style( 'activeforms-frontend', $this->preview_css() );
		wp_add_inline_script( 'activeforms-frontend', $this->preview_js() );

		// Print only ActiveForms' own handles (core + Pro when present) so the clean
		// preview window isn't polluted by theme/other-plugin assets.
		$handles = array( 'activeforms-frontend' );
		if ( wp_script_is( 'activeforms-pro-frontend', 'registered' ) || wp_style_is( 'activeforms-pro-frontend', 'registered' ) ) {
			$handles[] = 'activeforms-pro-frontend';
		}

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );

		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex,nofollow" />
	<title><?php echo esc_html( $title ); ?> — <?php esc_html_e( 'ActiveForms Preview', 'activeforms' ); ?></title>
	<?php wp_print_styles( $handles ); ?>
</head>
<body class="activeforms-preview-body">
	<header class="activeforms-pvp__bar">
		<div class="activeforms-pvp__name"><span class="dashicons dashicons-feedback"></span> <?php echo esc_html( $title ); ?></div>
		<div class="activeforms-pvp__badge"><?php esc_html_e( 'Preview Only', 'activeforms' ); ?></div>
		<div class="activeforms-pvp__devices" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'activeforms' ); ?>">
			<button type="button" data-device="desktop" class="is-active" title="Desktop">&#9633;</button>
			<button type="button" data-device="tablet" title="Tablet">&#9645;</button>
			<button type="button" data-device="mobile" title="Mobile">&#9647;</button>
		</div>
		<div class="activeforms-pvp__sc"><code>[activeforms id="<?php echo esc_attr( (int) $form['id'] ); ?>"]</code></div>
	</header>

	<main class="activeforms-pvp__stage">
		<div class="activeforms-pvp__window" id="activeforms-pvp-window">
			<div class="activeforms-pvp__dots"><span></span><span></span><span></span></div>
			<div class="activeforms-pvp__canvas">
				<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</main>

	<?php wp_print_scripts( $handles ); ?>
</body>
</html>
		<?php
	}

	/**
	 * Inline CSS rules for the preview chrome + design tokens.
	 *
	 * Colors come from the filterable design-token tree, so each is passed
	 * through sanitize_hex_color() before it is interpolated into the CSS
	 * context (a rogue filter cannot break out of the stylesheet).
	 *
	 * @return string
	 */
	private function preview_css() {
		$tokens  = Config::design_tokens();
		$primary = sanitize_hex_color( isset( $tokens['color']['primary'] ) ? $tokens['color']['primary'] : '' );
		$hover   = sanitize_hex_color( isset( $tokens['color']['primaryHover'] ) ? $tokens['color']['primaryHover'] : '' );
		$border  = sanitize_hex_color( isset( $tokens['color']['border'] ) ? $tokens['color']['border'] : '' );

		$primary = $primary ? $primary : '#4f46e5';
		$hover   = $hover ? $hover : '#4338ca';
		$border  = $border ? $border : '#e5e7eb';

		$css = ':root{'
			. '--activeforms-color-primary:' . $primary . ';'
			. '--activeforms-color-primary-hover:' . $hover . ';'
			. '--activeforms-color-border:' . $border . ';'
			. '--activeforms-radius-md:8px;}'
			. 'body.activeforms-preview-body{margin:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#1f2937;}'
			. '.activeforms-pvp__bar{display:flex;align-items:center;gap:16px;padding:12px 20px;background:#fff;border-bottom:1px solid ' . $border . ';position:sticky;top:0;z-index:10;}'
			. '.activeforms-pvp__name{font-weight:700;display:flex;align-items:center;gap:8px;}'
			. '.activeforms-pvp__badge{font-size:12px;font-weight:600;color:' . $primary . ';background:#eef2ff;padding:4px 10px;border-radius:999px;}'
			. '.activeforms-pvp__devices{margin-left:auto;display:flex;gap:4px;background:#f3f4f6;padding:3px;border-radius:8px;}'
			. '.activeforms-pvp__devices button{width:34px;height:28px;border:none;background:transparent;border-radius:6px;cursor:pointer;font-size:15px;color:#6b7280;}'
			. '.activeforms-pvp__devices button.is-active{background:#fff;color:' . $primary . ';box-shadow:0 1px 2px rgba(16,24,40,.1);}'
			. '.activeforms-pvp__sc code{font-size:12px;background:#f3f4f6;border:1px solid ' . $border . ';padding:5px 10px;border-radius:6px;color:#374151;}'
			. '.activeforms-pvp__stage{padding:32px 20px 64px;}'
			. '.activeforms-pvp__window{max-width:100%;margin:0 auto;background:#fff;border:1px solid ' . $border . ';border-radius:14px;box-shadow:0 12px 32px rgba(16,24,40,.12);overflow:hidden;transition:max-width .25s ease;}'
			. '.activeforms-pvp__dots{display:flex;gap:7px;padding:14px 18px;background:#f9fafb;border-bottom:1px solid ' . $border . ';}'
			. '.activeforms-pvp__dots span{width:12px;height:12px;border-radius:50%;background:#e5e7eb;}'
			. '.activeforms-pvp__dots span:nth-child(1){background:#f87171;}.activeforms-pvp__dots span:nth-child(2){background:#fbbf24;}.activeforms-pvp__dots span:nth-child(3){background:#34d399;}'
			. '.activeforms-pvp__canvas{padding:36px;}'
			// Keep the frontend's column width + center it so the preview reads
			// like a real page instead of sprawling edge-to-edge.
			. '.activeforms-pvp__canvas .activeforms-form-wrap{margin:0 auto;}';

		return $css;
	}

	/**
	 * Inline JS for the preview chrome: device-width switcher, plus a capture-
	 * phase submit blocker so no entry is ever created in preview.
	 *
	 * Registered as an inline addition to the frontend script handle, so it runs
	 * during parse — before form.js attaches its own DOMContentLoaded handler —
	 * and the capture phase + stopImmediatePropagation keep the real AJAX submit
	 * handler from firing.
	 *
	 * @return string
	 */
	private function preview_js() {
		$message = wp_json_encode( __( 'Preview mode — submission is disabled.', 'activeforms' ) );

		return '(function () {'
			. 'var win = document.getElementById("activeforms-pvp-window");'
			. 'var widths = { desktop: "100%", tablet: "768px", mobile: "390px" };'
			. 'document.querySelectorAll(".activeforms-pvp__devices button").forEach(function (btn) {'
			. 'btn.addEventListener("click", function () {'
			. 'document.querySelectorAll(".activeforms-pvp__devices button").forEach(function (b) { b.classList.remove("is-active"); });'
			. 'btn.classList.add("is-active");'
			. 'win.style.maxWidth = widths[btn.getAttribute("data-device")] || "100%";'
			. '});'
			. '});'
			. 'var form = document.querySelector(".activeforms-form");'
			. 'if (form) {'
			. 'form.addEventListener("submit", function (e) {'
			. 'e.preventDefault();'
			. 'e.stopImmediatePropagation();'
			. 'var msg = form.querySelector(".activeforms-form-message");'
			. 'if (msg) { msg.className = "activeforms-form-message activeforms-form-message--success"; msg.textContent = ' . $message . '; }'
			. '}, true);'
			. '}'
			. '})();';
	}
}
