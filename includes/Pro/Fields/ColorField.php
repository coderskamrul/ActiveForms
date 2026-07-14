<?php
/**
 * Color picker field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Native color input storing a hex value.
 */
class ColorField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'color';
		$this->label    = __( 'Color Picker', 'radiusforms' );
		$this->icon     = 'art';
		$this->category = 'advanced';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		$hex = sanitize_hex_color( (string) $value );
		return $hex ? $hex : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$val     = null === $value ? Arr::get( $field, 'default', '#4f46e5' ) : $value;
		$control = sprintf(
			'<input type="color" class="radiusforms-color" value="%1$s"%2$s />',
			esc_attr( $val ? $val : '#4f46e5' ),
			$this->input_attrs( $field )
		);
		return $this->wrap( $field, $control );
	}
}
