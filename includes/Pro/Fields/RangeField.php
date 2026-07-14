<?php
/**
 * Range slider field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Numeric range input bounded by min/max/step.
 */
class RangeField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'range';
		$this->label    = __( 'Range Slider', 'radiusforms' );
		$this->icon     = 'leftright';
		$this->category = 'advanced';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		return array_merge( parent::default_schema(), array( 'min' => 0, 'max' => 100, 'step' => 1 ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return '' === $value ? '' : ( $value + 0 );
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, $field ) {
		$base = parent::validate( $value, $field );
		if ( true !== $base ) {
			return $base;
		}
		if ( '' === (string) $value ) {
			return true;
		}
		if ( ! is_numeric( $value ) ) {
			return __( 'Please enter a valid number.', 'radiusforms' );
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$min  = (float) Arr::get( $field, 'min', 0 );
		$max  = (float) Arr::get( $field, 'max', 100 );
		$step = Arr::get( $field, 'step', 1 );
		$val  = null === $value ? Arr::get( $field, 'default', $min ) : $value;

		$control = sprintf(
			'<input type="range" class="radiusforms-range" min="%1$s" max="%2$s" step="%3$s" value="%4$s"%5$s />',
			esc_attr( $min ),
			esc_attr( $max ),
			esc_attr( $step ),
			esc_attr( $val ),
			$this->input_attrs( $field )
		);
		return $this->wrap( $field, $control );
	}
}
