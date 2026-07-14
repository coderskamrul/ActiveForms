<?php
/**
 * Net Promoter Score field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A 0..10 "how likely are you to recommend us" scale, collected via radio
 * inputs. Stores an integer 0..10. Pure HTML — no JavaScript required.
 */
class NpsField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'nps';
		$this->label    = __( 'Net Promoter', 'radiusforms' );
		$this->icon     = 'chart-bar';
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
				'low_label'  => __( 'Not likely', 'radiusforms' ),
				'high_label' => __( 'Very likely', 'radiusforms' ),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return '' === (string) $value ? '' : (int) $value;
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
		if ( (int) $value < 0 || (int) $value > 10 ) {
			return __( 'Please choose a score between 0 and 10.', 'radiusforms' );
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value = (string) ( null === $value ? Arr::get( $field, 'default', '' ) : $value );
		$key   = esc_attr( Arr::get( $field, 'key', '' ) );
		$low   = esc_html( Arr::get( $field, 'low_label', __( 'Not likely', 'radiusforms' ) ) );
		$high  = esc_html( Arr::get( $field, 'high_label', __( 'Very likely', 'radiusforms' ) ) );

		$control = '<div class="radiusforms-nps" role="radiogroup">';
		for ( $i = 0; $i <= 10; $i++ ) {
			$control .= sprintf(
				'<label class="radiusforms-nps__opt"><input type="radio" name="%1$s" value="%2$d" %3$s /><span>%2$d</span></label>',
				$key,
				$i,
				checked( $value, (string) $i, false )
			);
		}
		$control .= '</div>';
		$control .= '<div class="radiusforms-nps__labels"><span>' . $low . '</span><span>' . $high . '</span></div>';

		return $this->wrap( $field, $control );
	}
}
