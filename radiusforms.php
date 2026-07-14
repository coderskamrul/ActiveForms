<?php
/**
 * Plugin Name:       RadiusForms
 * Description:       Create contact forms, surveys, quizzes, lead generation forms, and conversational forms with an intuitive drag and drop form builder.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            hasandev
 * Author URI:        https://profiles.wordpress.org/hasandev/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       radiusforms
 * Domain Path:       /languages
 *
 * @package RadiusForms
 */

defined( 'ABSPATH' ) || exit;

/*
 * --------------------------------------------------------------------------
 * Plugin constants — the single source of truth for paths and version data.
 * Branding may change; these internal identifiers stay stable.
 * --------------------------------------------------------------------------
 */
define( 'RADIUSFORMS_VERSION', '1.0.0' );
define( 'RADIUSFORMS_DB_VERSION', '1.0.0' );
define( 'RADIUSFORMS_FILE', __FILE__ );
define( 'RADIUSFORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'RADIUSFORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'RADIUSFORMS_BASENAME', plugin_basename( __FILE__ ) );
define( 'RADIUSFORMS_MIN_PHP', '7.4' );
define( 'RADIUSFORMS_MIN_WP', '6.2' );

require_once RADIUSFORMS_PATH . 'includes/Core/Autoloader.php';

/**
 * Register the PSR-4 autoloader for the RadiusForms\ namespace.
 */
$radiusforms_autoloader = new RadiusForms\Core\Autoloader( 'RadiusForms\\', RADIUSFORMS_PATH . 'includes/' );
$radiusforms_autoloader->register();

/**
 * Register the autoloader for the formerly-separate Pro add-on, now merged into
 * this plugin under includes/Pro/. The RadiusFormsPro\ namespace is preserved so
 * the merged classes needed no rewrites.
 */
$radiusforms_pro_autoloader = new RadiusForms\Core\Autoloader( 'RadiusFormsPro\\', RADIUSFORMS_PATH . 'includes/Pro/' );
$radiusforms_pro_autoloader->register();

/*
 * --------------------------------------------------------------------------
 * Lifecycle hooks. Activation/deactivation are intentionally lightweight and
 * delegate to dedicated classes; nothing destructive runs on deactivation.
 * --------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, array( 'RadiusForms\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RadiusForms\\Core\\Deactivator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded so that integrations can hook into
 * the registries before fields/routes are assembled.
 *
 * @return \RadiusForms\Core\Plugin
 */
function radiusforms() {
	return RadiusForms\Core\Plugin::instance();
}

add_action( 'plugins_loaded', 'radiusforms', 9 );

/**
 * Boot the merged Pro feature set. It wires field types, integrations, and the
 * upload endpoint onto the core's radiusforms_register_* extension points, so it
 * must run after the core has booted but before the lazy registries resolve.
 *
 * @return void
 */
function radiusforms_pro() {
	RadiusFormsPro\Plugin::instance();
}

add_action( 'radiusforms/booted', 'radiusforms_pro' );
