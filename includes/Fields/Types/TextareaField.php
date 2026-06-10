<?php
/**
 * Multi-line textarea field.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Fields\AbstractField;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Paragraph / textarea input.
 */
class TextareaField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'textarea';
		$this->label    = 'Paragraph';
		$this->icon     = 'editor-paragraph';
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return sanitize_textarea_field( wp_unslash( (string) $value ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value  = null === $value ? Arr::get( $field, 'default', '' ) : $value;
		$rows   = (int) Arr::get( $field, 'rows', 4 );
		$resize = ! empty( $field['auto_resize'] ) ? ' data-easyforms-autoresize="1"' : '';

		$control = sprintf(
			'<textarea class="easyforms-input easyforms-textarea" rows="%1$d"%2$s%3$s>%4$s</textarea>',
			$rows ? $rows : 4,
			$this->input_attrs( $field ),
			$resize,
			esc_textarea( $value )
		);

		return $this->wrap( $field, $control );
	}
}
