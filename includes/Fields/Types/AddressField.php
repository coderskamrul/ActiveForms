<?php
/**
 * Composite Address field.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Support\Arr;

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
			array( 'key' => 'address_line_1', 'label' => __( 'Address Line 1', 'easyforms' ), 'placeholder' => __( 'Address Line 1', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'address_line_2', 'label' => __( 'Address Line 2', 'easyforms' ), 'placeholder' => __( 'Address Line 2', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'city', 'label' => __( 'City', 'easyforms' ), 'placeholder' => __( 'City', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'state', 'label' => __( 'State', 'easyforms' ), 'placeholder' => __( 'State', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'zip', 'label' => __( 'Zip Code', 'easyforms' ), 'placeholder' => __( 'Zip', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'country', 'label' => __( 'Country', 'easyforms' ), 'placeholder' => '', 'visible' => true, 'required' => false, 'type' => 'country' ),
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
		$control = '<div class="easyforms-address">';

		// Two-column grid: chunk visible sub-fields into rows of two.
		foreach ( array_chunk( $visible, 2 ) as $pair ) {
			$control .= '<div class="easyforms-subfields easyforms-address__row">';
			foreach ( $pair as $sub ) {
				$control .= $this->subfield_control( $field, $sub, $value );
			}
			$control .= '</div>';
		}

		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
