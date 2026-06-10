<?php
/**
 * Country dropdown field.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Fields\AbstractField;
use EasyForms\Support\Arr;
use EasyForms\Support\Countries;

defined( 'ABSPATH' ) || exit;

/**
 * Select populated with the full ISO country list, with optional flag emoji,
 * include/exclude list filtering, and a searchable (combobox) frontend.
 */
class CountryField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'country';
		$this->label    = 'Country';
		$this->icon     = 'admin-site';
		$this->category = 'general';
	}

	/**
	 * Full country list (kept for back-compat; delegates to the shared source).
	 *
	 * @return array<string,string>
	 */
	public static function countries() {
		return Countries::all();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		return array_merge(
			parent::default_schema(),
			array(
				'show_flags'        => false,
				'searchable'        => false,
				'country_list_mode' => 'all',
				'country_list'      => array(),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value      = null === $value ? Arr::get( $field, 'default', '' ) : $value;
		$searchable = ! empty( $field['searchable'] ) ? ' data-easyforms-searchable="1"' : '';

		$control  = '<select class="easyforms-input easyforms-select easyforms-country"' . $this->input_attrs( $field ) . $searchable . '>';
		$control .= '<option value="">' . esc_html__( '— Select Country —', 'easyforms' ) . '</option>';
		foreach ( Countries::resolve( $field ) as $code => $label ) {
			$control .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $code ),
				selected( $value, $code, false ),
				esc_html( $label )
			);
		}
		$control .= '</select>';

		return $this->wrap( $field, $control );
	}
}
