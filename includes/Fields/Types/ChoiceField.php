<?php
/**
 * Choice fields: select, radio, checkbox, multiselect.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Fields\Types;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Handles single- and multi-select choice inputs.
 */
class ChoiceField extends AbstractField {

	/**
	 * Config per type.
	 *
	 * @var array<string,array>
	 */
	private static $map = array(
		'select'      => array( 'label' => 'Dropdown', 'icon' => 'arrow-down-alt2' ),
		'radio'       => array( 'label' => 'Radio Field', 'icon' => 'marker' ),
		'checkbox'    => array( 'label' => 'Checkboxes', 'icon' => 'yes' ),
		'multiselect' => array( 'label' => 'Multi Select', 'icon' => 'list-view' ),
	);

	/**
	 * Choice types whose value is a list of selected options.
	 *
	 * @return bool
	 */
	private function is_multi() {
		return in_array( $this->type, array( 'checkbox', 'multiselect' ), true );
	}

	/**
	 * Constructor.
	 *
	 * @param string $type Field type.
	 */
	public function __construct( $type ) {
		$conf           = isset( self::$map[ $type ] ) ? self::$map[ $type ] : self::$map['select'];
		$this->type     = $type;
		$this->label    = $conf['label'];
		$this->icon     = $conf['icon'];
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema            = parent::default_schema();
		$schema['options'] = array(
			array(
				'label' => __( 'Option 1', 'radiusforms' ),
				'value' => 'option_1',
			),
			array(
				'label' => __( 'Option 2', 'radiusforms' ),
				'value' => 'option_2',
			),
		);
		$schema['multiple'] = $this->is_multi();
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		if ( $this->is_multi() ) {
			$value = (array) $value;
			return array_values( array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
		}
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$options = (array) Arr::get( $field, 'options', array() );
		$key     = esc_attr( Arr::get( $field, 'key', '' ) );

		if ( 'select' === $this->type ) {
			$control = '<select class="radiusforms-input radiusforms-select"' . $this->input_attrs( $field ) . '>';
			$control .= '<option value="">' . esc_html( Arr::get( $field, 'placeholder', __( '— Select —', 'radiusforms' ) ) ) . '</option>';
			foreach ( $options as $opt ) {
				$control .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $opt['value'] ),
					selected( $value, $opt['value'], false ),
					esc_html( $opt['label'] )
				);
			}
			$control .= '</select>';
			return $this->wrap( $field, $control );
		}

		// Multi-select: a native multiple <select> (posts key[]) that the
		// frontend script enhances into a tag-style picker. Server-side
		// validation handles "required", so the native control isn't marked
		// required (it's visually hidden once enhanced and couldn't be focused).
		if ( 'multiselect' === $this->type ) {
			$values      = array_map( 'strval', (array) $value );
			$placeholder = Arr::get( $field, 'placeholder', __( 'Select options…', 'radiusforms' ) );
			$control     = sprintf(
				'<select multiple class="radiusforms-input radiusforms-multiselect" id="radiusforms-%1$s" name="%1$s[]" data-radiusforms-multiselect data-placeholder="%2$s">',
				esc_attr( $key ),
				esc_attr( $placeholder )
			);
			foreach ( $options as $opt ) {
				$control .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $opt['value'] ),
					in_array( (string) $opt['value'], $values, true ) ? ' selected' : '',
					esc_html( $opt['label'] )
				);
			}
			$control .= '</select>';
			return $this->wrap( $field, $control );
		}

		// Radio / checkbox.
		$multiple = 'checkbox' === $this->type;
		$name     = $multiple ? $key . '[]' : $key;
		$values   = (array) $value;

		$control = '<div class="radiusforms-choices" role="group">';
		foreach ( $options as $i => $opt ) {
			$id      = 'radiusforms-' . $key . '-' . $i;
			$checked = in_array( $opt['value'], $values, true ) ? ' checked' : '';
			$control .= '<label class="radiusforms-choice" for="' . esc_attr( $id ) . '">';
			$control .= sprintf(
				'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s"%5$s /> <span>%6$s</span>',
				$multiple ? 'checkbox' : 'radio',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $opt['value'] ),
				$checked,
				esc_html( $opt['label'] )
			);
			$control .= '</label>';
		}
		$control .= '</div>';

		return $this->wrap( $field, $control );
	}
}
