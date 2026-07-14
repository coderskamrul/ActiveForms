<?php
/**
 * Array helper utilities.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Small array helpers used across the plugin.
 */
class Arr {

	/**
	 * Read a value from a nested array using "dot" notation.
	 *
	 * @param array  $array   Source array.
	 * @param string $key     Dot path.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( $array, $key, $default = null ) {
		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( is_array( $array ) && array_key_exists( $segment, $array ) ) {
				$array = $array[ $segment ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	/**
	 * Flatten a form field schema (including containers/columns) into a flat
	 * list of input fields keyed by field key.
	 *
	 * @param array $fields Field schema.
	 * @return array<string,array>
	 */
	public static function flatten_fields( $fields ) {
		$flat = array();

		foreach ( (array) $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( 'container' === self::get( $field, 'type' ) && ! empty( $field['columns'] ) ) {
				foreach ( $field['columns'] as $column ) {
					$inner = isset( $column['fields'] ) ? $column['fields'] : array();
					$flat  = array_merge( $flat, self::flatten_fields( $inner ) );
				}
				continue;
			}

			$key = self::get( $field, 'key' );
			if ( $key ) {
				$flat[ $key ] = $field;
			}
		}

		return $flat;
	}
}
