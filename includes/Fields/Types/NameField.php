<?php
/**
 * Composite Name field (first / middle / last).
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Support\Arr;

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
		$this->label    = 'Name';
		$this->icon     = 'admin-users';
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_subfields() {
		return array(
			array( 'key' => 'first', 'label' => __( 'First Name', 'easyforms' ), 'placeholder' => __( 'First Name', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'middle', 'label' => __( 'Middle Name', 'easyforms' ), 'placeholder' => __( 'Middle Name', 'easyforms' ), 'visible' => false, 'required' => false, 'type' => 'text' ),
			array( 'key' => 'last', 'label' => __( 'Last Name', 'easyforms' ), 'placeholder' => __( 'Last Name', 'easyforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value   = (array) $value;
		$control = '<div class="easyforms-subfields easyforms-name">';
		foreach ( $this->visible_subfields( $field ) as $sub ) {
			$control .= $this->subfield_control( $field, $sub, $value );
		}
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
