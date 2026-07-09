<?php
/**
 * Composite Address field.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Fields\Types;

use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A grouped field whose value is { address_line_1, address_line_2, city, state,
 * zip, country }. Each sub-field is toggleable, reorderable, and configurable;
 * visible sub-fields render in a two-column grid (Fluent Forms parity). Country
 * is a select populated from the shared country list.
 */
class AddressField extends AbstractCompositeField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'address';
		$this->label    = 'Address Fields';
		$this->icon     = 'location';
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_subfields() {
		return array(
			array( 'key' => 'address_line_1', 'label' => __( 'Address Line 1', 'activeforms' ), 'placeholder' => __( 'Address Line 1', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'address_line_2', 'label' => __( 'Address Line 2', 'activeforms' ), 'placeholder' => __( 'Address Line 2', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'city', 'label' => __( 'City', 'activeforms' ), 'placeholder' => __( 'City', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'state', 'label' => __( 'State', 'activeforms' ), 'placeholder' => __( 'State', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'zip', 'label' => __( 'Zip Code', 'activeforms' ), 'placeholder' => __( 'Zip', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'country', 'label' => __( 'Country', 'activeforms' ), 'placeholder' => '', 'visible' => true, 'required' => false, 'type' => 'country' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema                         = parent::default_schema();
		$schema['autocomplete_provider'] = 'none';
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value   = (array) $value;
		$visible = $this->visible_subfields( $field );
		$control = '<div class="activeforms-address">';

		// Two-column grid: chunk visible sub-fields into rows of two.
		foreach ( array_chunk( $visible, 2 ) as $pair ) {
			$control .= '<div class="activeforms-subfields activeforms-address__row">';
			foreach ( $pair as $sub ) {
				$control .= $this->subfield_control( $field, $sub, $value );
			}
			$control .= '</div>';
		}

		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
