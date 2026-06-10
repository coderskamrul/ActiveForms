<?php
/**
 * Server-side form HTML renderer.
 *
 * @package EasyForms
 */

namespace EasyForms\Frontend;

use EasyForms\Fields\FieldRegistry;
use EasyForms\Spam\SpamGuard;
use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a form's field schema into accessible, progressively-enhanced HTML.
 */
class FormRenderer {

	/**
	 * Field registry.
	 *
	 * @var FieldRegistry
	 */
	private $fields;

	/**
	 * Constructor.
	 *
	 * @param FieldRegistry $fields Field registry.
	 */
	public function __construct( FieldRegistry $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Render a complete form.
	 *
	 * @param array $form Form schema (id, title, fields, settings).
	 * @return string
	 */
	public function render( $form ) {
		$id     = (int) Arr::get( $form, 'id', 0 );
		$schema = Arr::get( $form, 'fields', array() );
		$nonce  = wp_create_nonce( 'easyforms_submit_' . $id );

		$html  = '<div class="easyforms-form-wrap" data-form-id="' . esc_attr( $id ) . '">';
		$html .= '<form class="easyforms-form" method="post" data-form-id="' . esc_attr( $id ) . '" novalidate>';
		$html .= '<input type="hidden" name="easyforms_form_id" value="' . esc_attr( $id ) . '" />';
		$html .= '<input type="hidden" name="easyforms_nonce" value="' . esc_attr( $nonce ) . '" />';

		$guard = new SpamGuard();
		$html .= $guard->honeypot_markup();

		$html .= '<div class="easyforms-fields">';
		$html .= $this->render_fields( $schema );
		$html .= '</div>';

		if ( ! $this->has_submit( $schema ) ) {
			$html .= '<div class="easyforms-submit easyforms-submit--left"><button type="submit" class="easyforms-btn easyforms-btn--primary">'
				. esc_html__( 'Submit', 'easyforms' ) . '</button></div>';
		}

		$html .= '<div class="easyforms-form-message" role="status" aria-live="polite"></div>';
		$html .= '</form>';
		$html .= '</div>';

		/**
		 * Filter the rendered form markup.
		 *
		 * @param string $html Form HTML.
		 * @param array  $form Form schema.
		 */
		return apply_filters( 'easyforms/rendering_form', $html, $form );
	}

	/**
	 * Render a list of fields (recurses into containers).
	 *
	 * @param array $fields Field schema list.
	 * @return string
	 */
	protected function render_fields( $fields ) {
		$html = '';

		foreach ( (array) $fields as $field ) {
			$type = Arr::get( $field, 'type' );

			if ( 'container' === $type ) {
				$html .= $this->render_container( $field );
				continue;
			}

			$handler = $this->fields->get( $type );
			if ( ! $handler ) {
				continue;
			}

			if ( empty( $field['key'] ) && $handler->is_input() ) {
				continue; // Input fields must have a key.
			}

			$html .= $handler->render( $field, null );
		}

		return $html;
	}

	/**
	 * Render a column container.
	 *
	 * @param array $field Container field schema.
	 * @return string
	 */
	protected function render_container( $field ) {
		$columns = (array) Arr::get( $field, 'columns', array() );
		$count   = count( $columns );
		// Inline display:flex + gap so the side-by-side layout and the gap work
		// even if the (cached) stylesheet is stale; CSS handles mobile stacking.
		$html    = '<div class="easyforms-row easyforms-row--cols-' . esc_attr( $count ) . '" style="display:flex;flex-wrap:wrap;gap:20px;">';

		foreach ( $columns as $column ) {
			$width = (int) Arr::get( $column, 'width', 100 );
			$inner = Arr::get( $column, 'fields', array() );
			// flex-grow tracks the column width so columns SHARE the row
			// (flex-basis 0) instead of wrapping; CSS stacks them on mobile.
			$html .= '<div class="easyforms-col" style="flex:' . esc_attr( max( 1, $width ) ) . ' 1 0;min-width:0;">';
			$html .= $this->render_fields( $inner );
			$html .= '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Whether the schema already contains a submit button.
	 *
	 * @param array $fields Field schema.
	 * @return bool
	 */
	protected function has_submit( $fields ) {
		foreach ( (array) $fields as $field ) {
			if ( 'submit' === Arr::get( $field, 'type' ) ) {
				return true;
			}
			if ( 'container' === Arr::get( $field, 'type' ) ) {
				foreach ( (array) Arr::get( $field, 'columns', array() ) as $col ) {
					if ( $this->has_submit( Arr::get( $col, 'fields', array() ) ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}
}
