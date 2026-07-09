<?php
/**
 * Base field implementation with shared rendering & validation helpers.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Fields;

use ActiveForms\Core\Settings;
use ActiveForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Concrete fields extend this and override behavior as needed.
 */
abstract class AbstractField implements FieldInterface {

	/**
	 * Field type key.
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Human label shown in the builder palette.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Palette category: general | layout | advanced | payment.
	 *
	 * @var string
	 */
	protected $category = 'general';

	/**
	 * Dashicon / icon key for the palette.
	 *
	 * @var string
	 */
	protected $icon = 'forms';

	/**
	 * Whether the field stores a value.
	 *
	 * @var bool
	 */
	protected $input = true;

	/**
	 * The native HTML input type, when applicable.
	 *
	 * @var string
	 */
	protected $input_type = 'text';

	/**
	 * {@inheritDoc}
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_input() {
		return $this->input;
	}

	/**
	 * Default field schema produced when the field is dropped on the canvas.
	 *
	 * @return array<string,mixed>
	 */
	protected function default_schema() {
		return array(
			'type'            => $this->type,
			'label'           => $this->label,
			'placeholder'     => '',
			'help'            => '',
			'required'        => false,
			'default'         => '',
			'css_class'       => '',
			'label_placement' => 'top',
			'conditional'     => array(
				'enabled' => false,
				'logic'   => 'all',
				'rules'   => array(),
			),
		);
	}

	/**
	 * Editor settings descriptor consumed by the React settings panel.
	 *
	 * @return array<int,array>
	 */
	protected function editor_settings() {
		return array(
			array( 'key' => 'label', 'type' => 'text', 'label' => __( 'Label', 'activeforms' ) ),
			array( 'key' => 'placeholder', 'type' => 'text', 'label' => __( 'Placeholder', 'activeforms' ) ),
			array( 'key' => 'help', 'type' => 'text', 'label' => __( 'Help text', 'activeforms' ) ),
			array( 'key' => 'default', 'type' => 'text', 'label' => __( 'Default value', 'activeforms' ) ),
			array( 'key' => 'required', 'type' => 'toggle', 'label' => __( 'Required', 'activeforms' ) ),
			array( 'key' => 'css_class', 'type' => 'text', 'label' => __( 'CSS class', 'activeforms' ) ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function definition() {
		return array(
			'type'     => $this->type,
			'label'    => $this->label,
			'category' => $this->category,
			'icon'     => $this->icon,
			'isInput'  => $this->input,
			'schema'   => $this->default_schema(),
			'settings' => $this->editor_settings(),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', wp_unslash( $value ) );
		}
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, $field ) {
		if ( $this->is_required( $field ) && $this->is_empty( $value ) ) {
			return $this->required_message( $field );
		}
		return true;
	}

	/**
	 * Whether the field is required.
	 *
	 * @param array $field Field schema.
	 * @return bool
	 */
	protected function is_required( $field ) {
		return ! empty( $field['required'] );
	}

	/**
	 * Whether a value is empty.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	protected function is_empty( $value ) {
		if ( is_array( $value ) ) {
			return 0 === count( array_filter( $value, 'strlen' ) );
		}
		return '' === trim( (string) $value );
	}

	/**
	 * Required validation message.
	 *
	 * @param array $field Field schema.
	 * @return string
	 */
	protected function required_message( $field ) {
		return Settings::message( 'required', __( 'This field is required.', 'activeforms' ) );
	}

	/**
	 * Build the wrapper markup around a field's control.
	 *
	 * @param array  $field   Field schema.
	 * @param string $control Inner control HTML.
	 * @return string
	 */
	protected function wrap( $field, $control ) {
		$key      = esc_attr( Arr::get( $field, 'key', '' ) );
		$label    = Arr::get( $field, 'label', '' );
		$help     = Arr::get( $field, 'help', '' );
		$required = $this->is_required( $field );
		$css      = esc_attr( Arr::get( $field, 'css_class', '' ) );

		// Label placement: a field may override it; otherwise fall back to the
		// site-wide default configured in Settings → General → Layout.
		$placement = Arr::get( $field, 'label_placement', '' );
		if ( '' === $placement ) {
			$placement = Settings::default_label_placement();
		}
		$lp_class = ( $placement && 'top' !== $placement ) ? ' activeforms-field--lp-' . sanitize_html_class( $placement ) : '';

		$html  = '<div class="activeforms-field activeforms-field--' . esc_attr( $this->type ) . $lp_class . ' ' . $css . '" data-field="' . $key . '">';
		if ( $label && $this->input && 'hide' !== $placement ) {
			$html .= '<label class="activeforms-label" for="activeforms-' . $key . '">' . esc_html( $label );
			if ( $required ) {
				$html .= ' <span class="activeforms-required" aria-hidden="true">*</span>';
			}
			$html .= '</label>';
		}
		$html .= '<div class="activeforms-control">' . $control . '</div>';
		if ( $help ) {
			$html .= '<small class="activeforms-help">' . esc_html( $help ) . '</small>';
		}
		$html .= '<span class="activeforms-error" role="alert"></span>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Common HTML attributes for an input control.
	 *
	 * @param array $field Field schema.
	 * @return string
	 */
	protected function input_attrs( $field ) {
		$key   = esc_attr( Arr::get( $field, 'key', '' ) );
		$attrs = array(
			'id'   => 'activeforms-' . $key,
			'name' => $key,
		);

		$placeholder = Arr::get( $field, 'placeholder', '' );
		if ( $placeholder ) {
			$attrs['placeholder'] = $placeholder;
		}
		if ( $this->is_required( $field ) ) {
			$attrs['required'] = 'required';
		}

		$out = '';
		foreach ( $attrs as $name => $value ) {
			$out .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}
		return $out;
	}
}
