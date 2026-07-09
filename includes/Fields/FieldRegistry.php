<?php
/**
 * Field type registry.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Fields;

use ActiveForms\Fields\Types\InputField;
use ActiveForms\Fields\Types\TextareaField;
use ActiveForms\Fields\Types\ChoiceField;
use ActiveForms\Fields\Types\NameField;
use ActiveForms\Fields\Types\AddressField;
use ActiveForms\Fields\Types\DateTimeField;
use ActiveForms\Fields\Types\CountryField;
use ActiveForms\Fields\Types\ConsentField;
use ActiveForms\Fields\Types\LayoutField;

defined( 'ABSPATH' ) || exit;

/**
 * Central registry of all field types. Core fields register here; the Pro
 * add-on appends advanced fields via the activeforms/register_fields action.
 */
class FieldRegistry {

	/**
	 * Registered field handlers keyed by type.
	 *
	 * @var array<string,FieldInterface>
	 */
	private $fields = array();

	/**
	 * Register a field handler.
	 *
	 * @param FieldInterface $field Field handler.
	 * @return void
	 */
	public function register( FieldInterface $field ) {
		$this->fields[ $field->type() ] = $field;
	}

	/**
	 * Register all core (free) field types.
	 *
	 * @return void
	 */
	public function register_defaults() {
		foreach ( array( 'text', 'email', 'url', 'number', 'password', 'hidden', 'masked_text' ) as $type ) {
			$this->register( new InputField( $type ) );
		}

		$this->register( new TextareaField() );

		foreach ( array( 'select', 'radio', 'checkbox', 'multiselect' ) as $type ) {
			$this->register( new ChoiceField( $type ) );
		}

		$this->register( new NameField() );
		$this->register( new AddressField() );
		$this->register( new DateTimeField() );
		$this->register( new CountryField() );

		foreach ( array( 'terms', 'gdpr' ) as $type ) {
			$this->register( new ConsentField( $type ) );
		}

		foreach ( array( 'section', 'html', 'container', 'step', 'submit' ) as $type ) {
			$this->register( new LayoutField( $type ) );
		}
	}

	/**
	 * Get a field handler by type.
	 *
	 * @param string $type Field type.
	 * @return FieldInterface|null
	 */
	public function get( $type ) {
		return isset( $this->fields[ $type ] ) ? $this->fields[ $type ] : null;
	}

	/**
	 * Whether a type is registered.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	public function has( $type ) {
		return isset( $this->fields[ $type ] );
	}

	/**
	 * All registered handlers.
	 *
	 * @return array<string,FieldInterface>
	 */
	public function all() {
		return $this->fields;
	}

	/**
	 * Builder palette definitions for the React app.
	 *
	 * @return array<int,array>
	 */
	public function definitions() {
		$defs = array();
		foreach ( $this->fields as $field ) {
			$defs[] = $field->definition();
		}
		return $defs;
	}
}
