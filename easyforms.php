<?php
/**
 * Plugin Name:       EasyForms – Drag & Drop Form Builder for WordPress
 * Description:       Create contact forms, surveys, quizzes, lead generation forms, and conversational forms with an intuitive drag and drop form builder.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            pluginshift
 * Author URI:        https://pluginshift.com
 * Plugin URI:        https://pluginshift.com/easyforms
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easyforms
 * Domain Path:       /languages
 *
 * @package EasyForms
 */

defined( 'ABSPATH' ) || exit;

/*
 * --------------------------------------------------------------------------
 * Plugin constants — the single source of truth for paths and version data.
 * Branding may change; these internal identifiers stay stable.
 * --------------------------------------------------------------------------
 */
define( 'EASYFORMS_VERSION', '1.0.0' );
define( 'EASYFORMS_DB_VERSION', '1.0.0' );
define( 'EASYFORMS_FILE', __FILE__ );
define( 'EASYFORMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'EASYFORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'EASYFORMS_BASENAME', plugin_basename( __FILE__ ) );
define( 'EASYFORMS_MIN_PHP', '7.4' );
define( 'EASYFORMS_MIN_WP', '6.2' );

require_once EASYFORMS_PATH . 'includes/Core/Autoloader.php';

/**
 * Register the PSR-4 autoloader for the EasyForms\ namespace.
 */
$easyforms_autoloader = new EasyForms\Core\Autoloader( 'EasyForms\\', EASYFORMS_PATH . 'includes/' );
$easyforms_autoloader->register();

/*
 * --------------------------------------------------------------------------
 * Lifecycle hooks. Activation/deactivation are intentionally lightweight and
 * delegate to dedicated classes; nothing destructive runs on deactivation.
 * --------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, array( 'EasyForms\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'EasyForms\\Core\\Deactivator', 'deactivate' ) );

/**
 * Boot the plugin once all plugins are loaded so that integrations and the Pro
 * add-on can hook into the registries before fields/routes are assembled.
 *
 * @return \EasyForms\Core\Plugin
 */
function easyforms() {
	return EasyForms\Core\Plugin::instance();
}

add_action( 'plugins_loaded', 'easyforms', 9 );
