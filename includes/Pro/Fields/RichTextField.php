<?php
/**
 * Rich text field (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Fields;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A WYSIWYG text area. The frontend enhances a hidden textarea with a small
 * contenteditable toolbar; the submitted HTML is filtered through wp_kses_post.
 */
class RichTextField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'rich_text';
		$this->label    = __( 'Rich Text', 'radiusforms' );
		$this->icon     = 'editor-paragraph';
		$this->category = 'advanced';
		$this->input    = true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		return array_merge( parent::default_schema(), array( 'rows' => 5 ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		$value = is_array( $value ) ? '' : (string) $value;
		return wp_kses_post( wp_unslash( $value ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Required-emptiness ignores HTML tags so an empty "<p></p>" still counts
	 * as blank.
	 */
	protected function is_empty( $value ) {
		return '' === trim( wp_strip_all_tags( (string) $value ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$key   = esc_attr( Arr::get( $field, 'key', '' ) );
		$value = (string) ( null === $value ? Arr::get( $field, 'default', '' ) : $value );
		$rows  = max( 2, (int) Arr::get( $field, 'rows', 5 ) );

		$required = $this->is_required( $field ) ? ' required' : '';

		$control  = '<div class="radiusforms-richtext" data-radiusforms-richtext>';
		$control .= sprintf(
			'<textarea class="radiusforms-richtext__source" name="%1$s" id="radiusforms-%1$s" rows="%2$d"%3$s>%4$s</textarea>',
			$key,
			$rows,
			$required,
			esc_textarea( $value )
		);
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
