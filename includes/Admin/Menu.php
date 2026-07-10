<?php
/**
 * Admin menu registration.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Admin;

use ActiveForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the top-level ActiveForms menu. All sub-screens are routed
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
			__( 'ActiveForms', 'activeforms' ),
			__( 'ActiveForms', 'activeforms' ),
			$cap,
			$slug,
			array( $this, 'render_app' ),
			'dashicons-feedback',
			26
		);

		$subpages = array(
			''            => __( 'Dashboard', 'activeforms' ),
			'#/forms'     => __( 'All Forms', 'activeforms' ),
			'#/forms/new' => __( 'Add New', 'activeforms' ),
			'#/entries'   => __( 'Entries', 'activeforms' ),
			'#/reports'   => __( 'Reports', 'activeforms' ),
			// '#/integrations' => __( 'Integrations', 'activeforms' ),
			'#/settings'  => __( 'Settings', 'activeforms' ),
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
		echo '<div class="wrap activeforms-wrap"><div id="activeforms-app" data-theme="light">';
		echo '<div class="activeforms-loading"><span class="spinner is-active"></span> ' . esc_html__( 'Loading ActiveForms…', 'activeforms' ) . '</div>';
		echo '</div></div>';
	}
}
