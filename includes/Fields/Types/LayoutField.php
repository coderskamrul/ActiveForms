<?php
/**
 * Non-input layout & content fields: section, html, container, submit.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Fields\Types;

use RadiusForms\Fields\AbstractField;
use RadiusForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * These fields render structure/content and do not store submitted values.
 */
class LayoutField extends AbstractField {

	/**
	 * Config per type.
	 *
	 * @var array<string,array>
	 */
	private static $map = array(
		'section'   => array( 'label' => 'Section Break', 'icon' => 'minus', 'category' => 'layout' ),
		'html'      => array( 'label' => 'Custom HTML', 'icon' => 'editor-code', 'category' => 'general' ),
		'container' => array( 'label' => 'Columns', 'icon' => 'columns', 'category' => 'layout' ),
		'submit'    => array( 'label' => 'Submit Button', 'icon' => 'button', 'category' => 'layout' ),
	);

	/**
	 * Constructor.
	 *
	 * @param string $type Field type.
	 */
	public function __construct( $type ) {
		$conf           = isset( self::$map[ $type ] ) ? self::$map[ $type ] : self::$map['section'];
		$this->type     = $type;
		$this->label    = $conf['label'];
		$this->icon     = $conf['icon'];
		$this->category = $conf['category'];
		$this->input    = false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function default_schema() {
		$schema = array(
			'type'      => $this->type,
			'css_class' => '',
		);

		switch ( $this->type ) {
			case 'section':
				$schema['label']       = __( 'Section', 'radiusforms' );
				$schema['description'] = '';
				break;
			case 'html':
				$schema['content'] = '<p>' . esc_html__( 'Custom HTML content', 'radiusforms' ) . '</p>';
				break;
			case 'container':
				$schema['columns'] = array(
					array( 'width' => 50, 'fields' => array() ),
					array( 'width' => 50, 'fields' => array() ),
				);
				break;
			case 'submit':
				$schema['label'] = __( 'Submit', 'radiusforms' );
				$schema['align'] = 'left';
				break;
		}

		return $schema;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, $field ) {
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, $field ) {
		return true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Container rendering is delegated to FormRenderer; this returns content
	 * markup for the non-container layout fields.
	 */
	public function render( $field, $value = null ) {
		switch ( $this->type ) {
			case 'section':
				$out  = '<div class="radiusforms-section">';
				$out .= '<h3 class="radiusforms-section-title">' . esc_html( Arr::get( $field, 'label', '' ) ) . '</h3>';
				$desc = Arr::get( $field, 'description', '' );
				if ( $desc ) {
					$out .= '<p class="radiusforms-section-desc">' . esc_html( $desc ) . '</p>';
				}
				$out .= '</div>';
				return $out;

			case 'html':
				return '<div class="radiusforms-html">' . wp_kses_post( Arr::get( $field, 'content', '' ) ) . '</div>';

			case 'submit':
				// The builder persists this as `align`; `alignment` is the older key.
				$align = esc_attr( Arr::get( $field, 'align', Arr::get( $field, 'alignment', 'left' ) ) );
				$label = esc_html( Arr::get( $field, 'label', __( 'Submit', 'radiusforms' ) ) );
				return '<div class="radiusforms-submit radiusforms-submit--' . $align . '"><button type="submit" class="radiusforms-btn radiusforms-btn--primary">' . $label . '</button></div>';


			case 'container':
				// Rendered by FormRenderer with nested fields.
				return '';
		}

		return '';
	}
}
