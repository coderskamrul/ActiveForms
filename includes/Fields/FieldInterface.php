<?php
/**
 * Field type contract.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Every field type (core or Pro) implements this contract so the registry,
 * renderer, and validator can treat them uniformly.
 */
interface FieldInterface {

	/**
	 * Unique field type key (e.g. "text", "email").
	 *
	 * @return string
	 */
	public function type();

	/**
	 * Builder palette definition: metadata + default field schema + editor
	 * settings the React builder uses to render this field's options.
	 *
	 * @return array<string,mixed>
	 */
	public function definition();

	/**
	 * Whether this field stores a submitted value (false for layout/content).
	 *
	 * @return bool
	 */
	public function is_input();

	/**
	 * Sanitize a submitted value.
	 *
	 * @param mixed $value Raw value.
	 * @param array $field Field schema instance.
	 * @return mixed
	 */
	public function sanitize( $value, $field );

	/**
	 * Validate a submitted value.
	 *
	 * @param mixed $value Sanitized value.
	 * @param array $field Field schema instance.
	 * @return true|string True when valid, error string otherwise.
	 */
	public function validate( $value, $field );

	/**
	 * Render the field HTML for the frontend.
	 *
	 * @param array $field Field schema instance.
	 * @param mixed $value Current value (for repopulation).
	 * @return string
	 */
	public function render( $field, $value = null );
}
