<?php
/**
 * Admin menu registration.
 *
 * @package EasyForms
 */

namespace EasyForms\Admin;

use EasyForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the top-level EasyForms menu. All sub-screens are routed
 * client-side by the React app, so each menu item renders the same mount node
 * with a different default route via the URL hash.
 */
class Menu {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add the menu and submenus.
	 *
	 * @return void
	 */
	public function add_menu() {
		$cap  = Config::cap( 'manage' );
		$slug = Config::MENU_SLUG;

		add_menu_page(
			__( 'EasyForms', 'easyforms' ),
			__( 'EasyForms', 'easyforms' ),
			$cap,
			$slug,
			array( $this, 'render_app' ),
			'dashicons-feedback',
			26
		);

		$subpages = array(
			''            => __( 'Dashboard', 'easyforms' ),
			'#/forms'     => __( 'All Forms', 'easyforms' ),
			'#/forms/new' => __( 'Add New', 'easyforms' ),
			'#/entries'   => __( 'Entries', 'easyforms' ),
			'#/reports'   => __( 'Reports', 'easyforms' ),
			'#/integrations' => __( 'Integrations', 'easyforms' ),
			'#/settings'  => __( 'Settings', 'easyforms' ),
		);

		foreach ( $subpages as $hash => $label ) {
			add_submenu_page(
				$slug,
				$label,
				$label,
				$cap,
				'' === $hash ? $slug : $slug . $hash,
				'' === $hash ? array( $this, 'render_app' ) : '__return_null'
			);
		}
	}

	/**
	 * Render the React mount point.
	 *
	 * @return void
	 */
	public function render_app() {
		echo '<div class="wrap easyforms-wrap"><div id="easyforms-app" data-theme="light">';
		echo '<div class="easyforms-loading"><span class="spinner is-active"></span> ' . esc_html__( 'Loading EasyForms…', 'easyforms' ) . '</div>';
		echo '</div></div>';
	}
}
