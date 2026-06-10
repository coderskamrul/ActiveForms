<?php
/**
 * Activation routine.
 *
 * @package EasyForms
 */

namespace EasyForms\Core;

use EasyForms\Database\Schema;

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

		update_option( Config::OPTION_DB_VERSION, EASYFORMS_DB_VERSION, false );

		// Stamp the install time once.
		if ( false === get_option( 'easyforms_installed_at', false ) ) {
			add_option( 'easyforms_installed_at', time(), '', false );
		}
	}

	/**
	 * Default global settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_settings() {
		return array(
			'label_placement'     => 'top',
			'remove_data_on_uninstall' => false,
			'recaptcha'           => array(
				'provider'   => '',
				'site_key'   => '',
				'secret_key' => '',
			),
			'messages'            => array(
				'required'       => __( 'This field is required.', 'easyforms' ),
				'invalid_email'  => __( 'Please enter a valid email address.', 'easyforms' ),
				'invalid_url'    => __( 'Please enter a valid URL.', 'easyforms' ),
				'invalid_number' => __( 'Please enter a valid number.', 'easyforms' ),
			),
		);
	}
}
