<?php
/**
 * Server-side submission validator & sanitizer.
 *
 * @package EasyForms
 */

namespace EasyForms\Frontend;

use EasyForms\Fields\FieldRegistry;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Walks a form's field schema, sanitizing and validating each posted value.
 */
class Validator {

	/**
	 * Field registry.
	 *
	 * @var FieldRegistry
	 */
	private $fields;

	/**
	 * Validation errors keyed by field key.
	 *
	 * @var array<string,string>
	 */
	private $errors = array();

	/**
	 * Sanitized, validated response values.
	 *
	 * @var array<string,mixed>
	 */
	private $values = array();

	/**
	 * Constructor.
	 *
	 * @param FieldRegistry $fields Field registry.
	 */
	public function __construct( FieldRegistry $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Run validation against a posted payload.
	 *
	 * @param array $form    Form schema (with 'fields').
	 * @param array $payload Raw posted data.
	 * @return bool True when valid.
	 */
	public function validate( $form, $payload ) {
		$this->errors = array();
		$this->values = array();

		$flat = Arr::flatten_fields( Arr::get( $form, 'fields', array() ) );

		foreach ( $flat as $key => $field ) {
			$handler = $this->fields->get( Arr::get( $field, 'type' ) );
			if ( ! $handler || ! $handler->is_input() ) {
				continue;
			}

			$field['key'] = $key;
			$raw          = isset( $payload[ $key ] ) ? $payload[ $key ] : ( 'checkbox' === $field['type'] ? array() : '' );
			$clean        = $handler->sanitize( $raw, $field );

			$verdict = $handler->validate( $clean, $field );
			if ( true !== $verdict ) {
				$this->errors[ $key ] = $verdict;
			}

			$this->values[ $key ] = $clean;
		}

		/**
		 * Allow add-ons to add custom validation errors.
		 *
		 * @param array $errors Errors keyed by field key.
		 * @param array $values Sanitized values.
		 * @param array $form   Form schema.
		 */
		$this->errors = apply_filters( 'easyforms/validation_errors', $this->errors, $this->values, $form );

		return empty( $this->errors );
	}

	/**
	 * Errors from the last run.
	 *
	 * @return array<string,string>
	 */
	public function errors() {
		return $this->errors;
	}

	/**
	 * Sanitized values from the last run.
	 *
	 * @return array<string,mixed>
	 */
	public function values() {
		return $this->values;
	}
}
