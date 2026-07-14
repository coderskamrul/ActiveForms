<?php
/**
 * Signature field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;
use RadiusFormsPro\Support\Uploads;

defined( 'ABSPATH' ) || exit;

/**
 * Captures a hand-drawn signature on a <canvas>. The frontend writes a PNG data
 * URL into a hidden input; on submit the image is saved to the uploads folder
 * and the stored value is the file's relative path (kept small in the DB).
 */
class SignatureField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'signature';
		$this->label    = __( 'Signature', 'radiusforms' );
		$this->icon     = 'edit';
		$this->category = 'advanced';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Receives either a data URL (new signature) or an already-stored relative
	 * path (re-submission). Data URLs are persisted to disk here.
	 */
	public function sanitize( $value, $field ) {
		$value = is_array( $value ) ? '' : (string) $value;
		$value = wp_unslash( $value );

		if ( '' === $value ) {
			return '';
		}
		if ( 0 === strpos( $value, 'data:image/' ) ) {
			$stored = Uploads::store_data_url( $value );
			return is_wp_error( $stored ) ? '' : $stored;
		}
		// Treat as an existing stored reference only if it really exists.
		return Uploads::exists( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$key   = esc_attr( Arr::get( $field, 'key', '' ) );
		$value = (string) ( null === $value ? '' : $value );

		$preview = '';
		if ( '' !== $value && Uploads::exists( $value ) ) {
			$preview = ' style="background-image:url(' . esc_url( Uploads::url_for( $value ) ) . ')"';
		}

		$control  = '<div class="radiusforms-signature" data-radiusforms-signature>';
		$control .= '<canvas class="radiusforms-signature__pad" width="600" height="200"' . $preview . '></canvas>';
		$control .= '<div class="radiusforms-signature__bar">';
		$control .= '<button type="button" class="radiusforms-signature__clear">' . esc_html__( 'Clear', 'radiusforms' ) . '</button>';
		$control .= '</div>';
		$control .= sprintf(
			'<input type="hidden" name="%1$s" id="radiusforms-%1$s" value="%2$s" data-radiusforms-signature-input />',
			$key,
			esc_attr( $value )
		);
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
