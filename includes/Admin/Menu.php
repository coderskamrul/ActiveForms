<?php
/**
 * Admin menu registration.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Admin;

use RadiusForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the top-level RadiusForms menu. All sub-screens are routed
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
			__( 'RadiusForms', 'radiusforms' ),
			__( 'RadiusForms', 'radiusforms' ),
			$cap,
			$slug,
			array( $this, 'render_app' ),
			'dashicons-feedback',
			26
		);

		$subpages = array(
			''            => __( 'Dashboard', 'radiusforms' ),
			'#/forms'     => __( 'All Forms', 'radiusforms' ),
			'#/forms/new' => __( 'Add New', 'radiusforms' ),
			'#/entries'   => __( 'Entries', 'radiusforms' ),
			'#/reports'   => __( 'Reports', 'radiusforms' ),
			'#/settings'  => __( 'Settings', 'radiusforms' ),
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
		echo '<div class="wrap radiusforms-wrap"><div id="radiusforms-app" data-theme="light">';
		echo '<div class="radiusforms-loading"><span class="spinner is-active"></span> ' . esc_html__( 'Loading RadiusForms…', 'radiusforms' ) . '</div>';
		echo '</div></div>';
	}
}
