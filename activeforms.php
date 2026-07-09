<?php
/**
 * Plugin Name:       ActiveForms – Drag & Drop Form Builder for WordPress
 * Description:       Create contact forms, surveys, quizzes, lead generation forms, and conversational forms with an intuitive drag and drop form builder.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            pluginshift
 * Author URI:        https://pluginshift.com
 * Plugin URI:        https://pluginshift.com/activeforms
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       activeforms
 * Domain Path:       /languages
 *
 * @package ActiveForms
 */

defined( 'ABSPATH' ) || exit;

/*
 * --------------------------------------------------------------------------
 * Plugin constants — the single source of truth for paths and version data.
 * Branding may change; these internal identifiers stay stable.
 * --------------------------------------------------------------------------
 */
define( 'ACTIVEFORMS_VERSION', '1.0.0' );
define( 'ACTIVEFORMS_DB_VERSION', '1.0.0' );
define( 'ACTIVEFORMS_FILE', __FILE__ );
define( 'ACTIVEFORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACTIVEFORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'ACTIVEFORMS_BASENAME', plugin_basename( __FILE__ ) );
define( 'ACTIVEFORMS_MIN_PHP', '7.4' );
define( 'ACTIVEFORMS_MIN_WP', '6.2' );

require_once ACTIVEFORMS_PATH . 'includes/Core/Autoloader.php';

/**
 * Register the PSR-4 autoloader for the ActiveForms\ namespace.
 */
$activeforms_autoloader = new ActiveForms\Core\Autoloader( 'ActiveForms\\', ACTIVEFORMS_PATH . 'includes/' );
$activeforms_autoloader->register();

/**
 * Register the autoloader for the formerly-separate Pro add-on, now merged into
 * this plugin under includes/Pro/. The ActiveFormsPro\ namespace is preserved so
 * the merged classes needed no rewrites.
 */
$activeforms_pro_autoloader = new ActiveForms\Core\Autoloader( 'ActiveFormsPro\\', ACTIVEFORMS_PATH . 'includes/Pro/' );
$activeforms_pro_autoloader->register();

/*
 * --------------------------------------------------------------------------
 * Lifecycle hooks. Activation/deactivation are intentionally lightweight and
 * delegate to dedicated classes; nothing destructive runs on deactivation.
 * --------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, array( 'ActiveForms\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ActiveForms\\Core\\Deactivator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded so that integrations can hook into
 * the registries before fields/routes are assembled.
 *
 * @return \ActiveForms\Core\Plugin
 */
function activeforms() {
	return ActiveForms\Core\Plugin::instance();
}

add_action( 'plugins_loaded', 'activeforms', 9 );

/**
 * Boot the merged Pro feature set. It wires field types, integrations, and the
 * upload endpoint onto the core's activeforms/register_* extension points, so it
 * must run after the core has booted but before the lazy registries resolve.
 *
 * @return void
 */
function activeforms_pro() {
	ActiveFormsPro\Plugin::instance();
}

add_action( 'activeforms/booted', 'activeforms_pro' );
