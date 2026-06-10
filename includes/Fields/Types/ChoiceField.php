<?php
/**
 * Choice fields: select, radio, checkbox.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Fields\AbstractField;
use EasyForms\Support\Arr;

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
		'select'   => array( 'label' => 'Dropdown', 'icon' => 'arrow-down-alt2' ),
		'radio'    => array( 'label' => 'Radio', 'icon' => 'marker' ),
		'checkbox' => array( 'label' => 'Checkboxes', 'icon' => 'yes' ),
	);

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
				'label' => __( 'Option 1', 'easyforms' ),
				'value' => 'option_1',
			),
			array(
				'label' => __( 'Option 2', 'easyforms' ),
				'value' => 'option_2',
			),
		);
		$schema['multiple'] = 'checkbox' === $this->type;
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		if ( 'checkbox' === $this->type ) {
			$value = (array) $value;
			return array_map( 'sanitize_text_field', wp_unslash( $value ) );
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
			$control = '<select class="easyforms-input easyforms-select"' . $this->input_attrs( $field ) . '>';
			$control .= '<option value="">' . esc_html( Arr::get( $field, 'placeholder', __( '— Select —', 'easyforms' ) ) ) . '</option>';
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

		// Radio / checkbox.
		$multiple = 'checkbox' === $this->type;
		$name     = $multiple ? $key . '[]' : $key;
		$values   = (array) $value;

		$control = '<div class="easyforms-choices" role="group">';
		foreach ( $options as $i => $opt ) {
			$id      = 'easyforms-' . $key . '-' . $i;
			$checked = in_array( $opt['value'], $values, true ) ? ' checked' : '';
			$control .= '<label class="easyforms-choice" for="' . esc_attr( $id ) . '">';
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
