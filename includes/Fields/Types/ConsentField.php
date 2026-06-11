<?php
/**
 * Consent fields: Terms & Conditions and GDPR agreement.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Fields\AbstractField;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * A single required-by-default consent checkbox with HTML content.
 */
class ConsentField extends AbstractField {

	/**
	 * Config per type.
	 *
	 * @var array<string,array>
	 */
	private static $map = array(
		'terms' => array( 'label' => 'Terms & Conditions', 'icon' => 'text-page' ),
		'gdpr'  => array( 'label' => 'GDPR Agreement', 'icon' => 'shield' ),
	);

	/**
	 * Constructor.
	 *
	 * @param string $type Field type.
	 */
	public function __construct( $type ) {
		$conf           = isset( self::$map[ $type ] ) ? self::$map[ $type ] : self::$map['terms'];
		$this->type     = $type;
		$this->label    = $conf['label'];
		$this->icon     = $conf['icon'];
		// Consent fields are specialized; they live under the advanced set, not
		// the everyday general fields.
		$this->category = 'advanced';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema             = parent::default_schema();
		$schema['required'] = true;
		$schema['content']  = 'terms' === $this->type
			? __( 'I have read and agree to the Terms & Conditions.', 'easyforms' )
			: __( 'I consent to having this website store my submitted information.', 'easyforms' );
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return $value ? 1 : 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, $field ) {
		if ( $this->is_required( $field ) && empty( $value ) ) {
			return __( 'You must accept to continue.', 'easyforms' );
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$key     = esc_attr( Arr::get( $field, 'key', '' ) );
		$content = Arr::get( $field, 'content', '' );
		$checked = $value ? ' checked' : '';

		$control = '<label class="easyforms-consent" for="easyforms-' . $key . '">';
		$control .= sprintf(
			'<input type="checkbox" id="easyforms-%1$s" name="%1$s" value="1"%2$s%3$s /> ',
			$key,
			$this->is_required( $field ) ? ' required' : '',
			$checked
		);
		$control .= '<span>' . wp_kses_post( $content ) . '</span>';
		$control .= '</label>';

		// No label header for consent fields; content carries the text.
		$saved_input = $this->input;
		$saved_label = Arr::get( $field, 'label' );
		$field['label'] = '';
		$html           = $this->wrap( $field, $control );
		$field['label'] = $saved_label;
		return $html;
	}
}
