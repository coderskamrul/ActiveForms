<?php
/**
 * Centralized configuration & design system.
 *
 * Single source of truth for identifiers, option keys, capabilities, table
 * names, REST namespace, and design tokens. Both PHP and React consume values
 * that originate here so branding/white-label changes happen in one place.
 *
 * @package EasyForms
 */

namespace EasyForms\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Static accessor for plugin-wide configuration.
 */
class Config {

	/**
	 * Text domain for translations.
	 */
	const TEXT_DOMAIN = 'easyforms';

	/**
	 * REST API namespace.
	 */
	const REST_NAMESPACE = 'easyforms/v1';

	/**
	 * Admin menu slug.
	 */
	const MENU_SLUG = 'easyforms';

	/**
	 * Option key holding global settings.
	 */
	const OPTION_SETTINGS = 'easyforms_settings';

	/**
	 * Option key holding the installed DB schema version.
	 */
	const OPTION_DB_VERSION = 'easyforms_db_version';

	/**
	 * Custom database table base names (without the WP prefix).
	 *
	 * @return array<string,string>
	 */
	public static function tables() {
		return array(
			'forms'        => 'easyforms_forms',
			'form_meta'    => 'easyforms_form_meta',
			'entries'      => 'easyforms_entries',
			'entry_meta'   => 'easyforms_entry_meta',
			'entry_detail' => 'easyforms_entry_details',
			'logs'         => 'easyforms_logs',
			'scheduled'    => 'easyforms_scheduled_actions',
		);
	}

	/**
	 * Capability map. Internal keys stay stable; the mapped capability can be
	 * filtered for granular role control.
	 *
	 * @return array<string,string>
	 */
	public static function capabilities() {
		$caps = array(
			'manage'        => 'manage_options',
			'edit_forms'    => 'manage_options',
			'view_entries'  => 'manage_options',
			'manage_entries' => 'manage_options',
			'manage_settings' => 'manage_options',
		);

		/**
		 * Filter the EasyForms capability map.
		 *
		 * @param array $caps Capability map.
		 */
		return apply_filters( 'easyforms/capabilities', $caps );
	}

	/**
	 * Cache-busting version string for a bundled asset.
	 *
	 * Uses the file's modification time so rebuilt CSS/JS is fetched fresh
	 * (avoids stale browser caches between releases), falling back to the plugin
	 * version if the file is unreadable.
	 *
	 * @param string $relative Path relative to the plugin root (e.g. assets/frontend/form.css).
	 * @return string
	 */
	public static function asset_version( $relative ) {
		$file = EASYFORMS_PATH . ltrim( $relative, '/' );
		$mtime = file_exists( $file ) ? filemtime( $file ) : 0;
		return $mtime ? EASYFORMS_VERSION . '.' . $mtime : EASYFORMS_VERSION;
	}

	/**
	 * Resolve a single capability key to its WordPress capability.
	 *
	 * @param string $key Capability key.
	 * @return string
	 */
	public static function cap( $key ) {
		$caps = self::capabilities();
		return isset( $caps[ $key ] ) ? $caps[ $key ] : 'manage_options';
	}

	/**
	 * Design tokens shared between PHP-rendered surfaces and the React admin.
	 * Emitted as CSS custom properties and passed to JS via localization.
	 *
	 * @return array<string,mixed>
	 */
	public static function design_tokens() {
		$tokens = array(
			'color'   => array(
				'primary'        => '#4f46e5',
				'primaryHover'   => '#4338ca',
				'primarySoft'    => '#eef2ff',
				'accent'         => '#0ea5e9',
				'success'        => '#16a34a',
				'warning'        => '#d97706',
				'danger'         => '#dc2626',
				'text'           => '#1f2937',
				'textMuted'      => '#6b7280',
				'border'         => '#e5e7eb',
				'surface'        => '#ffffff',
				'surfaceAlt'     => '#f9fafb',
				'canvas'         => '#f3f4f6',
			),
			'space'   => array( '0', '4px', '8px', '12px', '16px', '24px', '32px', '48px', '64px' ),
			'radius'  => array(
				'sm' => '4px',
				'md' => '8px',
				'lg' => '12px',
				'xl' => '16px',
				'pill' => '999px',
			),
			'shadow'  => array(
				'sm' => '0 1px 2px rgba(16,24,40,0.06)',
				'md' => '0 4px 12px rgba(16,24,40,0.08)',
				'lg' => '0 12px 32px rgba(16,24,40,0.12)',
			),
			'font'    => array(
				'family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
				'size'   => array(
					'xs' => '12px',
					'sm' => '13px',
					'md' => '14px',
					'lg' => '16px',
					'xl' => '20px',
					'2xl' => '28px',
				),
			),
			'zIndex'  => array(
				'dropdown' => 1000,
				'drawer'   => 1100,
				'modal'    => 1200,
				'toast'    => 1300,
			),
			'breakpoint' => array(
				'sm' => '640px',
				'md' => '768px',
				'lg' => '1024px',
				'xl' => '1280px',
			),
			'motion'  => array(
				'fast'   => '120ms',
				'normal' => '200ms',
				'slow'   => '320ms',
				'easing' => 'cubic-bezier(0.4, 0, 0.2, 1)',
			),
		);

		/**
		 * Filter EasyForms design tokens (white-label / theming entry point).
		 *
		 * @param array $tokens Design token tree.
		 */
		return apply_filters( 'easyforms/design_tokens', $tokens );
	}

	/**
	 * Brand metadata, kept separate from internal identifiers so rebranding is
	 * purely cosmetic.
	 *
	 * @return array<string,string>
	 */
	public static function brand() {
		return apply_filters(
			'easyforms/brand',
			array(
				'name'      => __( 'EasyForms', 'easyforms' ),
				'shortName' => __( 'EasyForms', 'easyforms' ),
				'tagline'   => __( 'Drag & Drop Form Builder', 'easyforms' ),
			)
		);
	}
}
