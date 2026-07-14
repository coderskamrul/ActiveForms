<?php
/**
 * Repeater field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A repeatable group of simple text sub-columns. The frontend manages add/remove
 * rows and keeps a JSON-encoded value in a single hidden input, so it travels
 * through the existing JSON submit pipeline as one field key. Stored as an array
 * of row objects keyed by column key.
 */
class RepeaterField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'repeater';
		$this->label    = __( 'Repeater', 'radiusforms' );
		$this->icon     = 'table-row-after';
		$this->category = 'advanced';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		return array_merge(
			parent::default_schema(),
			array(
				'columns'  => array(
					array( 'key' => 'col_1', 'label' => __( 'Item', 'radiusforms' ) ),
					array( 'key' => 'col_2', 'label' => __( 'Detail', 'radiusforms' ) ),
				),
				'max_rows' => 0,
			)
		);
	}

	/**
	 * Normalize the configured columns to a list of [key,label] pairs.
	 *
	 * @param array $field Field schema.
	 * @return array<int,array{key:string,label:string}>
	 */
	protected function columns( $field ) {
		$cols = (array) Arr::get( $field, 'columns', array() );
		$out  = array();
		foreach ( $cols as $i => $col ) {
			$key = sanitize_key( Arr::get( $col, 'key', 'col_' . ( $i + 1 ) ) );
			if ( '' === $key ) {
				$key = 'col_' . ( $i + 1 );
			}
			$out[] = array(
				'key'   => $key,
				'label' => (string) Arr::get( $col, 'label', $key ),
			);
		}
		if ( empty( $out ) ) {
			$out[] = array( 'key' => 'col_1', 'label' => __( 'Item', 'radiusforms' ) );
		}
		return $out;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Accepts a JSON string (from the hidden input) or an array, returning a
	 * clean array of rows limited to the configured columns.
	 */
	public function sanitize( $value, $field ) {
		if ( is_string( $value ) ) {
			$value = json_decode( wp_unslash( $value ), true );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		$columns = $this->columns( $field );
		$keys    = wp_list_pluck( $columns, 'key' );
		$max     = (int) Arr::get( $field, 'max_rows', 0 );

		$rows = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean = array();
			$empty = true;
			foreach ( $keys as $key ) {
				$cell           = isset( $row[ $key ] ) ? sanitize_text_field( (string) $row[ $key ] ) : '';
				$clean[ $key ] = $cell;
				if ( '' !== $cell ) {
					$empty = false;
				}
			}
			if ( ! $empty ) {
				$rows[] = $clean;
			}
			if ( $max > 0 && count( $rows ) >= $max ) {
				break;
			}
		}
		return $rows;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function is_empty( $value ) {
		return ! is_array( $value ) || 0 === count( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$key     = esc_attr( Arr::get( $field, 'key', '' ) );
		$columns = $this->columns( $field );
		$rows    = is_array( $value ) ? $value : array();
		$max     = (int) Arr::get( $field, 'max_rows', 0 );

		$control  = sprintf(
			'<div class="radiusforms-repeater" data-radiusforms-repeater data-columns="%1$s" data-max="%2$d">',
			esc_attr( wp_json_encode( $columns ) ),
			$max
		);
		$control .= '<div class="radiusforms-repeater__rows"></div>';
		$control .= '<button type="button" class="radiusforms-repeater__add radiusforms-btn radiusforms-btn--sm">'
			. esc_html__( 'Add Row', 'radiusforms' ) . '</button>';
		$control .= sprintf(
			'<input type="hidden" name="%1$s" id="radiusforms-%1$s" value="%2$s" data-radiusforms-repeater-input />',
			$key,
			esc_attr( wp_json_encode( array_values( $rows ) ) )
		);
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
