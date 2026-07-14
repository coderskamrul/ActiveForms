<?php
/**
 * Global settings accessor.
 *
 * Thin, cached read layer over the {@see Config::OPTION_SETTINGS} option so the
 * rest of the plugin never has to know the storage shape. Everything that
 * consumes a global setting — label placement, validation copy, privacy flags —
 * goes through here, which keeps the REST controller free to evolve the option
 * shape without hunting down every reader.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Core;

use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Static accessor for the merged global settings tree.
 */
class Settings {

	/**
	 * Request-level cache of the merged settings.
	 *
	 * @var array<string,mixed>|null
	 */
	private static $cache = null;

	/**
	 * Return the full settings tree, merged over defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored      = get_option( Config::OPTION_SETTINGS, array() );
			self::$cache = array_replace_recursive( self::defaults(), is_array( $stored ) ? $stored : array() );
		}
		return self::$cache;
	}

	/**
	 * Read a single value by dot path (e.g. "messages.required").
	 *
	 * @param string $path    Dot-delimited key path.
	 * @param mixed  $default Fallback when unset.
	 * @return mixed
	 */
	public static function get( $path, $default = null ) {
		return Arr::get( self::all(), $path, $default );
	}

	/**
	 * Resolve a user-customizable validation message, falling back to the
	 * built-in default when the admin left the field blank.
	 *
	 * @param string $key     Message key (required, invalid_email, ...).
	 * @param string $default Built-in default copy.
	 * @return string
	 */
	public static function message( $key, $default ) {
		$value = Arr::get( self::all(), 'messages.' . $key, '' );
		$value = is_string( $value ) ? trim( $value ) : '';
		return '' !== $value ? $value : $default;
	}

	/**
	 * The site-wide default label placement applied when a field does not
	 * override it.
	 *
	 * @return string One of: top, right, bottom, left, hide.
	 */
	public static function default_label_placement() {
		$value = (string) self::get( 'label_placement', 'top' );
		return $value ? $value : 'top';
	}

	/**
	 * Bust the request cache (call after a write within the same request).
	 *
	 * @return void
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Canonical default settings tree. Mirrored by the REST sanitizer and the
	 * activator so a freshly installed site and a saved-once site agree.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'label_placement'          => 'top',
			'help_placement'           => 'below',
			'error_placement'          => 'below',
			'remove_data_on_uninstall' => false,
			'recaptcha'                => array(
				'provider'   => '',
				'site_key'   => '',
				'secret_key' => '',
			),
			'messages'                 => array(
				'required'       => '',
				'invalid_email'  => '',
				'invalid_url'    => '',
				'invalid_number' => '',
			),
		);
	}
}
