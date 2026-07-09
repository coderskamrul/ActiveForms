<?php
/**
 * Composite Name field (first / middle / last).
 *
 * @package ActiveForms
 */

namespace ActiveForms\Fields\Types;

use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A grouped field whose value is { first, middle, last }. Each sub-field is
 * independently toggleable and configurable; visible sub-fields render side by
 * side in a single row.
 */
class NameField extends AbstractCompositeField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'name';
		$this->label    = 'Name Fields';
		$this->icon     = 'admin-users';
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_subfields() {
		return array(
			array( 'key' => 'first', 'label' => __( 'First Name', 'activeforms' ), 'placeholder' => __( 'First Name', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'middle', 'label' => __( 'Middle Name', 'activeforms' ), 'placeholder' => __( 'Middle Name', 'activeforms' ), 'visible' => false, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'last', 'label' => __( 'Last Name', 'activeforms' ), 'placeholder' => __( 'Last Name', 'activeforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value   = (array) $value;
		$control = '<div class="activeforms-subfields activeforms-name">';
		foreach ( $this->visible_subfields( $field ) as $sub ) {
			$control .= $this->subfield_control( $field, $sub, $value );
		}
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
