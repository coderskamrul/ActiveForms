<?php
/**
 * Date / time field.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Fields\Types;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a single text input enhanced into a custom date / time / range picker
 * by the frontend script (form.js). Using a text input — rather than the native
 * date/time controls — gives a consistent, branded picker across every browser
 * and device, and the same picker drives the form preview.
 */
class DateTimeField extends AbstractField {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->type     = 'date_time';
		$this->label    = 'Time & Date';
		$this->icon     = 'calendar-alt';
		$this->category = 'general';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema = parent::default_schema();
		// mode: date | time | datetime | range.
		$schema['mode']            = 'date';
		$schema['date_format']     = 'm/d/Y';
		$schema['datetime_format'] = 'm/d/Y h:i K';
		$schema['time_format']     = 'h:i K';
		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $field, $value = null ) {
		$value  = null === $value ? Arr::get( $field, 'default', '' ) : $value;
		$mode   = $this->mode( $field );
		$format = $this->resolve_format( $field, $mode );
		$key    = esc_attr( Arr::get( $field, 'key', '' ) );

		$placeholder = Arr::get( $field, 'placeholder', '' );
		if ( '' === $placeholder ) {
			$placeholder = $this->default_placeholder( $mode );
		}

		$required = $this->is_required( $field ) ? ' required' : '';
		$icon     = 'time' === $mode ? $this->clock_svg() : $this->calendar_svg();

		$control = sprintf(
			'<div class="radiusforms-dp-field">'
				. '<input type="text" class="radiusforms-input radiusforms-datepicker" id="radiusforms-%1$s" name="%1$s" value="%2$s" placeholder="%3$s" autocomplete="off" readonly'
				. ' data-radiusforms-datepicker data-ef-mode="%4$s" data-ef-format="%5$s"%6$s />'
				. '<span class="radiusforms-dp-icon" aria-hidden="true">%7$s</span>'
				. '</div>',
			$key,
			esc_attr( $value ),
			esc_attr( $placeholder ),
			esc_attr( $mode ),
			esc_attr( $format ),
			$required,
			$icon
		);

		return $this->wrap( $field, $control );
	}

	/**
	 * Normalize the configured mode.
	 *
	 * @param array $field Field schema.
	 * @return string
	 */
	private function mode( $field ) {
		$mode = Arr::get( $field, 'mode', 'date' );
		return in_array( $mode, array( 'date', 'time', 'datetime', 'range' ), true ) ? $mode : 'date';
	}

	/**
	 * Resolve the active format token string for the given mode.
	 *
	 * @param array  $field Field schema.
	 * @param string $mode  Resolved mode.
	 * @return string
	 */
	private function resolve_format( $field, $mode ) {
		switch ( $mode ) {
			case 'time':
				return (string) Arr::get( $field, 'time_format', 'h:i K' );
			case 'datetime':
				return (string) Arr::get( $field, 'datetime_format', 'm/d/Y h:i K' );
			case 'range':
			case 'date':
			default:
				return (string) Arr::get( $field, 'date_format', 'm/d/Y' );
		}
	}

	/**
	 * Default placeholder copy per mode.
	 *
	 * @param string $mode Resolved mode.
	 * @return string
	 */
	private function default_placeholder( $mode ) {
		switch ( $mode ) {
			case 'time':
				return __( 'Select a time', 'radiusforms' );
			case 'datetime':
				return __( 'Select date & time', 'radiusforms' );
			case 'range':
				return __( 'Select a date range', 'radiusforms' );
			case 'date':
			default:
				return __( 'Select a date', 'radiusforms' );
		}
	}

	/**
	 * Inline calendar icon.
	 *
	 * @return string
	 */
	private function calendar_svg() {
		return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
	}

	/**
	 * Inline clock icon.
	 *
	 * @return string
	 */
	private function clock_svg() {
		return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
	}
}
