<?php
/**
 * Date / time field.
 *
 * @package EasyForms
 */

namespace EasyForms\Fields\Types;

use EasyForms\Fields\AbstractField;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a native date, time, or datetime-local input based on settings.
 */
class DateTimeField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'date_time';
		$this->label    = 'Date / Time';
		$this->icon     = 'calendar-alt';
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema         = parent::default_schema();
		$schema['mode'] = 'date'; // date | time | datetime.
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value = null === $value ? Arr::get( $field, 'default', '' ) : $value;
		$mode  = Arr::get( $field, 'mode', 'date' );
		$type  = 'time' === $mode ? 'time' : ( 'datetime' === $mode ? 'datetime-local' : 'date' );

		$control = sprintf(
			'<input type="%1$s" class="easyforms-input easyforms-date" value="%2$s"%3$s />',
			esc_attr( $type ),
			esc_attr( $value ),
			$this->input_attrs( $field )
		);

		return $this->wrap( $field, $control );
	}
}
