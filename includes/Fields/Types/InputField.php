<?php
/**
 * Generic single-input field (text, email, url, number, password, hidden, mask).
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Fields\AbstractField;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * One configurable class drives all simple native inputs; behavior is keyed by
 * the field type passed to the constructor.
 */
class InputField extends AbstractField {

	/**
	 * Configuration per supported type.
	 *
	 * @var array<string,array>
	 */
	private static $map = array(
		'text'        => array( 'label' => 'Single Line Text', 'icon' => 'editor-textcolor', 'input' => 'text' ),
		'email'       => array( 'label' => 'Email', 'icon' => 'email', 'input' => 'email' ),
		'url'         => array( 'label' => 'Website / URL', 'icon' => 'admin-links', 'input' => 'url' ),
		'number'      => array( 'label' => 'Number', 'icon' => 'calculator', 'input' => 'number' ),
		'password'    => array( 'label' => 'Password', 'icon' => 'lock', 'input' => 'password' ),
		'hidden'      => array( 'label' => 'Hidden', 'icon' => 'hidden', 'input' => 'hidden' ),
		'masked_text' => array( 'label' => 'Mask Input', 'icon' => 'admin-customizer', 'input' => 'text' ),
	);

	/**
	 * Constructor.
	 *
	 * @param string $type Field type.
	 */
	public function __construct( $type ) {
		$conf             = isset( self::$map[ $type ] ) ? self::$map[ $type ] : self::$map['text'];
		$this->type       = $type;
		$this->label      = $conf['label'];
		$this->icon       = $conf['icon'];
		$this->input_type = $conf['input'];
		$this->category   = 'general';
		$this->input      = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		$value = wp_unslash( (string) $value );
		switch ( $this->type ) {
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'number':
				return is_numeric( $value ) ? $value + 0 : '';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, $field ) {
		$base = parent::validate( $value, $field );
		if ( true !== $base ) {
			return $base;
		}
		if ( $this->is_empty( $value ) ) {
			return true;
		}

		if ( 'email' === $this->type && ! is_email( $value ) ) {
			return __( 'Please enter a valid email address.', 'easyforms' );
		}
		if ( 'url' === $this->type && ! wp_http_validate_url( $value ) ) {
			return __( 'Please enter a valid URL.', 'easyforms' );
		}
		if ( 'number' === $this->type ) {
			if ( ! is_numeric( $value ) ) {
				return __( 'Please enter a valid number.', 'easyforms' );
			}
			$min = Arr::get( $field, 'min', '' );
			$max = Arr::get( $field, 'max', '' );
			if ( '' !== $min && $value < $min ) {
				/* translators: %s: minimum value. */
				return sprintf( __( 'Value must be at least %s.', 'easyforms' ), $min );
			}
			if ( '' !== $max && $value > $max ) {
				/* translators: %s: maximum value. */
				return sprintf( __( 'Value must be at most %s.', 'easyforms' ), $max );
			}
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value = null === $value ? Arr::get( $field, 'default', '' ) : $value;
		$extra = '';
		if ( 'number' === $this->type ) {
			$min  = Arr::get( $field, 'min', '' );
			$max  = Arr::get( $field, 'max', '' );
			$step = Arr::get( $field, 'step', '' );
			$extra .= '' !== $min ? ' min="' . esc_attr( $min ) . '"' : '';
			$extra .= '' !== $max ? ' max="' . esc_attr( $max ) . '"' : '';
			$extra .= '' !== $step ? ' step="' . esc_attr( $step ) . '"' : '';
		}
		if ( 'masked_text' === $this->type ) {
			$mask = Arr::get( $field, 'mask', '' );
			if ( '' !== $mask ) {
				$extra .= ' data-easyforms-mask="' . esc_attr( $mask ) . '"';
			}
		}

		$control = sprintf(
			'<input type="%1$s" class="easyforms-input" value="%2$s"%3$s%4$s />',
			esc_attr( $this->input_type ),
			esc_attr( $value ),
			$this->input_attrs( $field ),
			$extra
		);

		if ( 'hidden' === $this->type ) {
			return $control;
		}

		return $this->wrap( $field, $control );
	}
}
