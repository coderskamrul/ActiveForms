<?php
/**
 * Standalone frontend preview page.
 *
 * Renders a saved form with the real FormRenderer + frontend stylesheet inside
 * a lightweight "browser window" chrome with device toggles, served at
 * `?easyforms_preview=<id>`. Restricted to users who can manage forms. Form
 * submission is disabled in preview so no entries are created.
 *
 * @package EasyForms
 */

namespace EasyForms\Frontend;

use EasyForms\Core\Config;
use EasyForms\Core\Container;
use EasyForms\Models\Form;

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
		if ( ! isset( $_GET['easyforms_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$id = absint( wp_unslash( $_GET['easyforms_preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! current_user_can( Config::cap( 'manage' ) ) ) {
			wp_die( esc_html__( 'You are not allowed to preview EasyForms forms.', 'easyforms' ), 403 );
		}

		$form = Form::find( $id );
		if ( ! $form ) {
			wp_die( esc_html__( 'Form not found.', 'easyforms' ), 404 );
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
		$renderer  = $this->container->get( 'renderer' );
		$form_html = $renderer->render( $form );
		$title     = isset( $form['title'] ) ? $form['title'] : __( 'Form Preview', 'easyforms' );

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );

		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex,nofollow" />
	<title><?php echo esc_html( $title ); ?> — <?php esc_html_e( 'EasyForms Preview', 'easyforms' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( EASYFORMS_URL . 'assets/frontend/form.css' ); ?>?v=<?php echo esc_attr( Config::asset_version( 'assets/frontend/form.css' ) ); ?>" />
	<?php echo $this->inline_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</head>
<body class="easyforms-preview-body">
	<header class="easyforms-pvp__bar">
		<div class="easyforms-pvp__name"><span class="dashicons dashicons-feedback"></span> <?php echo esc_html( $title ); ?></div>
		<div class="easyforms-pvp__badge"><?php esc_html_e( 'Preview Only', 'easyforms' ); ?></div>
		<div class="easyforms-pvp__devices" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'easyforms' ); ?>">
			<button type="button" data-device="desktop" class="is-active" title="Desktop">&#9633;</button>
			<button type="button" data-device="tablet" title="Tablet">&#9645;</button>
			<button type="button" data-device="mobile" title="Mobile">&#9647;</button>
		</div>
		<div class="easyforms-pvp__sc"><code>[easyforms id="<?php echo esc_attr( (int) $form['id'] ); ?>"]</code></div>
	</header>

	<main class="easyforms-pvp__stage">
		<div class="easyforms-pvp__window" id="easyforms-pvp-window">
			<div class="easyforms-pvp__dots"><span></span><span></span><span></span></div>
			<div class="easyforms-pvp__canvas">
				<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</main>

	<script>
		(function () {
			var win = document.getElementById('easyforms-pvp-window');
			var widths = { desktop: '100%', tablet: '768px', mobile: '390px' };
			document.querySelectorAll('.easyforms-pvp__devices button').forEach(function (btn) {
				btn.addEventListener('click', function () {
					document.querySelectorAll('.easyforms-pvp__devices button').forEach(function (b) { b.classList.remove('is-active'); });
					btn.classList.add('is-active');
					win.style.maxWidth = widths[btn.getAttribute('data-device')] || '100%';
				});
			});
			// Disable real submission in preview.
			var form = document.querySelector('.easyforms-form');
			if (form) {
				form.addEventListener('submit', function (e) {
					e.preventDefault();
					var msg = form.querySelector('.easyforms-form-message');
					if (msg) { msg.className = 'easyforms-form-message easyforms-form-message--success'; msg.textContent = <?php echo wp_json_encode( __( 'Preview mode — submission is disabled.', 'easyforms' ) ); ?>; }
				});
			}
		})();
	</script>
</body>
</html>
		<?php
	}

	/**
	 * Inline CSS for the preview chrome + design tokens.
	 *
	 * @return string
	 */
	private function inline_css() {
		$tokens  = Config::design_tokens();
		$primary = isset( $tokens['color']['primary'] ) ? $tokens['color']['primary'] : '#4f46e5';
		$hover   = isset( $tokens['color']['primaryHover'] ) ? $tokens['color']['primaryHover'] : '#4338ca';
		$border  = isset( $tokens['color']['border'] ) ? $tokens['color']['border'] : '#e5e7eb';

		$css = ':root{'
			. '--easyforms-color-primary:' . $primary . ';'
			. '--easyforms-color-primary-hover:' . $hover . ';'
			. '--easyforms-color-border:' . $border . ';'
			. '--easyforms-radius-md:8px;}'
			. 'body.easyforms-preview-body{margin:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#1f2937;}'
			. '.easyforms-pvp__bar{display:flex;align-items:center;gap:16px;padding:12px 20px;background:#fff;border-bottom:1px solid ' . $border . ';position:sticky;top:0;z-index:10;}'
			. '.easyforms-pvp__name{font-weight:700;display:flex;align-items:center;gap:8px;}'
			. '.easyforms-pvp__badge{font-size:12px;font-weight:600;color:' . $primary . ';background:#eef2ff;padding:4px 10px;border-radius:999px;}'
			. '.easyforms-pvp__devices{margin-left:auto;display:flex;gap:4px;background:#f3f4f6;padding:3px;border-radius:8px;}'
			. '.easyforms-pvp__devices button{width:34px;height:28px;border:none;background:transparent;border-radius:6px;cursor:pointer;font-size:15px;color:#6b7280;}'
			. '.easyforms-pvp__devices button.is-active{background:#fff;color:' . $primary . ';box-shadow:0 1px 2px rgba(16,24,40,.1);}'
			. '.easyforms-pvp__sc code{font-size:12px;background:#f3f4f6;border:1px solid ' . $border . ';padding:5px 10px;border-radius:6px;color:#374151;}'
			. '.easyforms-pvp__stage{padding:32px 20px 64px;}'
			. '.easyforms-pvp__window{max-width:100%;margin:0 auto;background:#fff;border:1px solid ' . $border . ';border-radius:14px;box-shadow:0 12px 32px rgba(16,24,40,.12);overflow:hidden;transition:max-width .25s ease;}'
			. '.easyforms-pvp__dots{display:flex;gap:7px;padding:14px 18px;background:#f9fafb;border-bottom:1px solid ' . $border . ';}'
			. '.easyforms-pvp__dots span{width:12px;height:12px;border-radius:50%;background:#e5e7eb;}'
			. '.easyforms-pvp__dots span:nth-child(1){background:#f87171;}.easyforms-pvp__dots span:nth-child(2){background:#fbbf24;}.easyforms-pvp__dots span:nth-child(3){background:#34d399;}'
			. '.easyforms-pvp__canvas{padding:36px;}'
			. '.easyforms-pvp__canvas .easyforms-form-wrap{max-width:none;}';

		return '<style>' . $css . '</style>';
	}
}
