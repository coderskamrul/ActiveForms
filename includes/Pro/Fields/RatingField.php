<?php
/**
 * Star rating field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Stores an integer rating (1..max) collected via radio inputs.
 */
class RatingField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'rating';
		$this->label    = __( 'Star Rating', 'radiusforms' );
		$this->icon     = 'star-filled';
		$this->category = 'advanced';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		return array_merge( parent::default_schema(), array( 'max_rating' => 5 ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return '' === $value ? '' : (int) $value;
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
		$max = max( 1, (int) Arr::get( $field, 'max_rating', 5 ) );
		if ( (int) $value < 1 || (int) $value > $max ) {
			return __( 'Please choose a valid rating.', 'radiusforms' );
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value   = (int) ( null === $value ? Arr::get( $field, 'default', 0 ) : $value );
		$max     = max( 1, (int) Arr::get( $field, 'max_rating', 5 ) );
		$key     = esc_attr( Arr::get( $field, 'key', '' ) );
		$control = '<div class="radiusforms-rating" role="radiogroup">';
		for ( $i = 1; $i <= $max; $i++ ) {
			$control .= sprintf(
				'<label class="radiusforms-rating__star"><input type="radio" name="%1$s" value="%2$d" %3$s /> <span aria-hidden="true">&#9733;</span></label>',
				$key,
				$i,
				checked( $value, $i, false )
			);
		}
		$control .= '</div>';
		return $this->wrap( $field, $control );
	}
}
