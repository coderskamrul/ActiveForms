<?php
/**
 * Shared base for upload-style fields (File, Image).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;
use RadiusFormsPro\Support\Uploads;

defined( 'ABSPATH' ) || exit;

/**
 * Files are uploaded asynchronously to the Pro upload REST endpoint as soon as
 * the user picks them; the endpoint returns a stored reference which the
 * frontend stashes (JSON-encoded) in a hidden input. On submit this base only
 * has to verify those references really exist and re-shape them for storage.
 */
abstract class AbstractUploadField extends AbstractField {

	/**
	 * Default allowed extensions for this upload type. Empty = WP defaults.
	 *
	 * @return array<string>
	 */
	protected function default_extensions() {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		return array_merge(
			parent::default_schema(),
			array(
				'allowed_types' => $this->default_extensions(),
				'max_size'      => 5120, // KB.
				'max_files'     => 1,
			)
		);
	}

	/**
	 * Allowed extensions configured for an instance, falling back to defaults.
	 *
	 * @param array $field Field schema.
	 * @return array<string>
	 */
	public function allowed_types( $field ) {
		$types = Arr::get( $field, 'allowed_types', array() );
		if ( is_string( $types ) ) {
			$types = array_filter( array_map( 'trim', explode( ',', $types ) ) );
		}
		$types = array_filter( array_map( 'strtolower', (array) $types ) );
		return ! empty( $types ) ? array_values( $types ) : $this->default_extensions();
	}

	/**
	 * Maximum file size in KB.
	 *
	 * @param array $field Field schema.
	 * @return int
	 */
	public function max_size_kb( $field ) {
		return max( 0, (int) Arr::get( $field, 'max_size', 5120 ) );
	}

	/**
	 * Maximum number of files.
	 *
	 * @param array $field Field schema.
	 * @return int
	 */
	public function max_files( $field ) {
		return max( 1, (int) Arr::get( $field, 'max_files', 1 ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Keeps only references that point at real files inside our upload base.
	 */
	public function sanitize( $value, $field ) {
		if ( is_string( $value ) ) {
			$value = json_decode( wp_unslash( $value ), true );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		$max   = $this->max_files( $field );
		$clean = array();
		foreach ( $value as $item ) {
			$path = is_array( $item ) ? Arr::get( $item, 'path', '' ) : (string) $item;
			$path = ltrim( (string) $path, '/' );
			if ( '' === $path || ! Uploads::exists( $path ) ) {
				continue;
			}
			$clean[] = array(
				'path' => $path,
				'url'  => Uploads::url_for( $path ),
				'name' => is_array( $item ) ? sanitize_text_field( (string) Arr::get( $item, 'name', basename( $path ) ) ) : basename( $path ),
				'size' => is_array( $item ) ? (int) Arr::get( $item, 'size', 0 ) : 0,
			);
			if ( count( $clean ) >= $max ) {
				break;
			}
		}
		return $clean;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function is_empty( $value ) {
		return ! is_array( $value ) || 0 === count( $value );
	}

	/**
	 * Whether this picker only accepts images (affects the input accept attr).
	 *
	 * @return bool
	 */
	protected function images_only() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$key     = esc_attr( Arr::get( $field, 'key', '' ) );
		$files   = is_array( $value ) ? array_values( $value ) : array();
		$accept  = $this->images_only() ? 'image/*' : '';
		$allowed = $this->allowed_types( $field );

		$control  = sprintf(
			'<div class="radiusforms-upload%5$s" data-radiusforms-upload data-max-files="%1$d" data-max-size="%2$d" data-allowed="%3$s" data-key="%4$s">',
			$this->max_files( $field ),
			$this->max_size_kb( $field ),
			esc_attr( implode( ',', $allowed ) ),
			$key,
			$this->images_only() ? ' radiusforms-upload--image' : ''
		);
		$control .= '<label class="radiusforms-upload__drop">';
		$control .= '<span class="radiusforms-upload__cta">' . esc_html__( 'Choose a file or drag it here', 'radiusforms' ) . '</span>';
		$control .= sprintf(
			'<input type="file" class="radiusforms-upload__input"%1$s%2$s />',
			$accept ? ' accept="' . esc_attr( $accept ) . '"' : '',
			$this->max_files( $field ) > 1 ? ' multiple' : ''
		);
		$control .= '</label>';
		$control .= '<div class="radiusforms-upload__list"></div>';
		$control .= '<div class="radiusforms-upload__error" role="alert"></div>';
		$control .= sprintf(
			'<input type="hidden" name="%1$s" id="radiusforms-%1$s" value="%2$s" data-radiusforms-upload-input />',
			$key,
			esc_attr( wp_json_encode( $files ) )
		);
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
