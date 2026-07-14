<?php
/**
 * Phone / mobile field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A tel input with light format validation.
 */
class PhoneField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type       = 'phone';
		$this->label      = __( 'Phone / Mobile', 'radiusforms' );
		$this->icon       = 'phone';
		$this->category   = 'general';
		$this->input      = true;
		$this->input_type = 'tel';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, $field ) {
		$base = parent::validate( $value, $field );
		if ( true !== $base ) {
			return $base;
		}
		if ( '' === trim( (string) $value ) ) {
			return true;
		}
		if ( ! preg_match( '/^[0-9 ()+\-.]{6,20}$/', (string) $value ) ) {
			return __( 'Please enter a valid phone number.', 'radiusforms' );
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value   = null === $value ? Arr::get( $field, 'default', '' ) : $value;
		$control = sprintf(
			'<input type="tel" class="radiusforms-input" value="%1$s"%2$s />',
			esc_attr( $value ),
			$this->input_attrs( $field )
		);
		return $this->wrap( $field, $control );
	}
}
