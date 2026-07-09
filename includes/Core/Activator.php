<?php
/**
 * Activation routine.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Core;

use ActiveForms\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Runs on plugin activation: creates tables and seeds default options.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		Schema::install();

		// Seed default global settings only if absent.
		if ( false === get_option( Config::OPTION_SETTINGS, false ) ) {
			add_option( Config::OPTION_SETTINGS, self::default_settings() );
		}

		update_option( Config::OPTION_DB_VERSION, ACTIVEFORMS_DB_VERSION, false );

		// Stamp the install time once.
		if ( false === get_option( 'activeforms_installed_at', false ) ) {
			add_option( 'activeforms_installed_at', time(), '', false );
		}
	}

	/**
	 * Default global settings. Delegates to {@see Settings::defaults()} so the
	 * activator, the REST sanitizer, and every reader share one canonical shape.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_settings() {
		return Settings::defaults();
	}
}
