<?php
/**
 * Shared base for grouped fields with toggleable sub-fields (Name, Address).
 *
 * @package ActiveForms
 */

namespace ActiveForms\Fields\Types;

use ActiveForms\Fields\AbstractField;
use ActiveForms\Support\Arr;
use ActiveForms\Support\Countries;

defined( 'ABSPATH' ) || exit;

/**
 * Models the Fluent Forms "grouped field" pattern: a single field entity whose
 * value is an object keyed by sub-field. Each sub-field is independently
 * toggleable (visible) and configurable (label, placeholder, required), and the
 * sub-fields are stored as an ordered list so the editor can reorder them.
 *
 * Sub-values travel as name="key[sub]" inputs, which the frontend serializer
 * collapses back into { key: { sub: value } } before submission.
 */
abstract class AbstractCompositeField extends AbstractField {

	/**
	 * Default ordered sub-field list for this composite.
	 *
	 * Each entry: key, label, placeholder, visible (bool), required (bool),
	 * and optionally type ('text' | 'country').
	 *
	 * @return array<int,array<string,mixed>>
	 */
	abstract protected function default_subfields();

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema           = parent::default_schema();
		$schema['fields'] = $this->default_subfields();
		unset( $schema['placeholder'], $schema['default'] );
		return $schema;
	}

	/**
	 * Normalize the configured sub-fields to a clean ordered list, tolerating
	 * the legacy associative-map shape ({ first: {...} }).
	 *
	 * @param array $field Field schema.
	 * @return array<int,array<string,mixed>>
	 */
	protected function subfields( $field ) {
		$raw      = Arr::get( $field, 'fields', array() );
		$defaults = $this->default_subfields();

		// Legacy map → list (preserve default order, merge overrides).
		if ( is_array( $raw ) && $raw && array_keys( $raw ) !== range( 0, count( $raw ) - 1 ) ) {
			$list = array();
			foreach ( $defaults as $def ) {
				$over   = isset( $raw[ $def['key'] ] ) ? (array) $raw[ $def['key'] ] : array();
				$list[] = array_merge( $def, $over );
			}
			$raw = $list;
		}

		if ( empty( $raw ) || ! is_array( $raw ) ) {
			return $defaults;
		}

		$byKey = array();
		foreach ( $defaults as $def ) {
			$byKey[ $def['key'] ] = $def;
		}

		$out = array();
		foreach ( $raw as $item ) {
			$key = isset( $item['key'] ) ? sanitize_key( $item['key'] ) : '';
			if ( '' === $key ) {
				continue;
			}
			$base  = isset( $byKey[ $key ] ) ? $byKey[ $key ] : array(
				'key'   => $key,
				'label' => $key,
				'type'  => 'text',
			);
			$entry = array(
				'key'             => $key,
				'label'           => isset( $item['label'] ) ? (string) $item['label'] : $base['label'],
				'placeholder'     => isset( $item['placeholder'] ) ? (string) $item['placeholder'] : Arr::get( $base, 'placeholder', '' ),
				'visible'         => ! empty( $item['visible'] ),
				'required'        => ! empty( $item['required'] ),
				'label_placement' => isset( $item['label_placement'] ) ? sanitize_html_class( $item['label_placement'] ) : '',
				'type'            => Arr::get( $base, 'type', 'text' ),
			);

			// Preserve country-specific options on the country sub-field.
			if ( 'country' === $entry['type'] ) {
				$entry['show_flags']        = ! empty( $item['show_flags'] );
				$entry['searchable']        = ! empty( $item['searchable'] );
				$entry['country_list_mode'] = isset( $item['country_list_mode'] ) ? (string) $item['country_list_mode'] : 'all';
				$entry['country_list']      = isset( $item['country_list'] ) ? array_map( 'sanitize_text_field', (array) $item['country_list'] ) : array();
			}

			$out[] = $entry;
		}

		return $out ? $out : $defaults;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		$value = (array) $value;
		$out   = array();
		foreach ( $this->subfields( $field ) as $sub ) {
			$raw          = isset( $value[ $sub['key'] ] ) ? $value[ $sub['key'] ] : '';
			$out[ $sub['key'] ] = sanitize_text_field( wp_unslash( (string) $raw ) );
		}
		return $out;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Required is enforced per visible sub-field (mirrors Fluent Forms).
	 */
	public function validate( $value, $field ) {
		$value = (array) $value;
		foreach ( $this->subfields( $field ) as $sub ) {
			if ( empty( $sub['visible'] ) || empty( $sub['required'] ) ) {
				continue;
			}
			if ( '' === trim( (string) Arr::get( $value, $sub['key'], '' ) ) ) {
				/* translators: %s: sub-field label. */
				return sprintf( __( '%s is required.', 'activeforms' ), $sub['label'] );
			}
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function is_empty( $value ) {
		$value = (array) $value;
		foreach ( $value as $v ) {
			if ( '' !== trim( (string) $v ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Render a single sub-field control (text input or country select).
	 *
	 * @param array $field Parent field schema.
	 * @param array $sub   Normalized sub-field config.
	 * @param array $value Current composite value.
	 * @return string
	 */
	protected function subfield_control( $field, $sub, $value ) {
		$key   = esc_attr( Arr::get( $field, 'key', '' ) );
		$name  = $key . '[' . esc_attr( $sub['key'] ) . ']';
		$id    = 'activeforms-' . $key . '-' . esc_attr( $sub['key'] );
		$pval  = isset( $value[ $sub['key'] ] ) ? $value[ $sub['key'] ] : '';
		$req   = ! empty( $sub['required'] ) ? ' required' : '';
		$plabel = '' !== (string) $sub['placeholder'] ? $sub['placeholder'] : $sub['label'];

		// Per-sub-field label placement: top (default) | right | bottom | left | hide.
		$placement = Arr::get( $sub, 'label_placement', '' );
		$placement = $placement ? $placement : 'top';
		$lp_class  = ' activeforms-subfield--lp-' . sanitize_html_class( $placement );

		$html = '<div class="activeforms-subfield' . $lp_class . '">';

		if ( 'country' === Arr::get( $sub, 'type', 'text' ) ) {
			$searchable = ! empty( $sub['searchable'] ) ? ' data-activeforms-searchable="1"' : '';
			$html .= '<select class="activeforms-input activeforms-select activeforms-country" name="' . $name . '" id="' . $id . '"' . $req . $searchable . '>';
			$html .= '<option value="">' . esc_html__( '— Select Country —', 'activeforms' ) . '</option>';
			foreach ( Countries::resolve( $sub ) as $code => $clabel ) {
				$html .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $code ),
					selected( $pval, $code, false ),
					esc_html( $clabel )
				);
			}
			$html .= '</select>';
		} else {
			$html .= sprintf(
				'<input type="text" class="activeforms-input" name="%1$s" id="%2$s" placeholder="%3$s" value="%4$s"%5$s />',
				$name,
				$id,
				esc_attr( $plabel ),
				esc_attr( $pval ),
				$req
			);
		}

		if ( 'hide' !== $placement ) {
			$html .= '<small class="activeforms-sublabel">' . esc_html( $sub['label'] );
			if ( ! empty( $sub['required'] ) ) {
				$html .= ' <span class="activeforms-required" aria-hidden="true">*</span>';
			}
			$html .= '</small>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Only the visible sub-fields, in order.
	 *
	 * @param array $field Field schema.
	 * @return array<int,array<string,mixed>>
	 */
	protected function visible_subfields( $field ) {
		return array_values(
			array_filter(
				$this->subfields( $field ),
				function ( $s ) {
					return ! empty( $s['visible'] );
				}
			)
		);
	}
}
