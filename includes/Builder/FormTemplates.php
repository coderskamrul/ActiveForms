<?php
/**
 * Prebuilt form templates.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Provides starter form schemas for the templates gallery.
 */
class FormTemplates {

	/**
	 * Build a field schema entry helper.
	 *
	 * @param string $type  Field type.
	 * @param string $key   Field key.
	 * @param string $label Field label.
	 * @param array  $extra Extra schema.
	 * @return array
	 */
	private static function field( $type, $key, $label, $extra = array() ) {
		return array_merge(
			array(
				'type'     => $type,
				'key'      => $key,
				'label'    => $label,
				'required' => false,
			),
			$extra
		);
	}

	/**
	 * All available templates (id => meta + schema).
	 *
	 * @return array<int,array>
	 */
	public static function all() {
		$templates = array();

		foreach ( self::definitions() as $id => $tpl ) {
			$templates[] = array(
				'id'          => $id,
				'name'        => $tpl['name'],
				'description' => $tpl['description'],
				'icon'        => isset( $tpl['icon'] ) ? $tpl['icon'] : 'forms',
				'category'    => isset( $tpl['category'] ) ? $tpl['category'] : 'general',
			);
		}

		/**
		 * Filter the list of available templates (Pro adds more).
		 *
		 * @param array $templates Template descriptors.
		 */
		return apply_filters( 'radiusforms/templates', $templates );
	}

	/**
	 * Resolve a single template's seed data.
	 *
	 * @param string $id Template id.
	 * @return array{fields:array,settings:array}
	 */
	public static function get( $id ) {
		$defs = self::definitions();
		if ( ! isset( $defs[ $id ] ) ) {
			return array( 'fields' => array(), 'settings' => array() );
		}
		return array(
			'fields'   => $defs[ $id ]['fields'],
			'settings' => isset( $defs[ $id ]['settings'] ) ? $defs[ $id ]['settings'] : self::default_settings(),
		);
	}

	/**
	 * Default settings applied to new forms.
	 *
	 * @return array
	 */
	private static function default_settings() {
		return array(
			'confirmation' => array(
				'type'    => 'message',
				'message' => __( 'Thank you! Your submission has been received.', 'radiusforms' ),
			),
			'notifications' => array(
				array(
					'enabled' => true,
					'name'    => __( 'Admin Notification', 'radiusforms' ),
					'to'      => get_bloginfo( 'admin_email' ),
					'subject' => __( 'New Form Submission', 'radiusforms' ),
					'body'    => '{all.fields}',
				),
			),
		);
	}

	/**
	 * Template definitions.
	 *
	 * @return array<string,array>
	 */
	private static function definitions() {
		$settings = self::default_settings();

		return array(
			'blank'   => array(
				'name'        => __( 'Blank Form', 'radiusforms' ),
				'description' => __( 'Start from scratch.', 'radiusforms' ),
				'icon'        => 'plus',
				'fields'      => array(
					self::field( 'submit', 'submit', __( 'Submit', 'radiusforms' ) ),
				),
				'settings'    => $settings,
			),
			'contact' => array(
				'name'        => __( 'Contact Form', 'radiusforms' ),
				'description' => __( 'Name, email, and message.', 'radiusforms' ),
				'icon'        => 'email',
				'category'    => 'general',
				'fields'      => array(
					// Sub-fields must be an ordered LIST (not a key => value map): the schema
				// is JSON-encoded, and a PHP associative array would serialize to a JSON
				// object, which the builder cannot iterate.
				self::field(
					'name',
					'name',
					__( 'Name', 'radiusforms' ),
					array(
						'required' => true,
						'fields'   => array(
							array( 'key' => 'first', 'label' => __( 'First Name', 'radiusforms' ), 'placeholder' => __( 'First Name', 'radiusforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
							array( 'key' => 'middle', 'label' => __( 'Middle Name', 'radiusforms' ), 'placeholder' => __( 'Middle Name', 'radiusforms' ), 'visible' => false, 'required' => false, 'type' => 'text' ),
							array( 'key' => 'last', 'label' => __( 'Last Name', 'radiusforms' ), 'placeholder' => __( 'Last Name', 'radiusforms' ), 'visible' => true, 'required' => false, 'type' => 'text' ),
						),
					)
				),
					self::field( 'email', 'email', __( 'Email', 'radiusforms' ), array( 'required' => true ) ),
					self::field( 'text', 'subject', __( 'Subject', 'radiusforms' ) ),
					self::field( 'textarea', 'message', __( 'Message', 'radiusforms' ), array( 'required' => true, 'rows' => 5 ) ),
					self::field( 'submit', 'submit', __( 'Send Message', 'radiusforms' ) ),
				),
				'settings'    => $settings,
			),
			'support' => array(
				'name'        => __( 'Support Request', 'radiusforms' ),
				'description' => __( 'Collect support tickets with priority.', 'radiusforms' ),
				'icon'        => 'sos',
				'fields'      => array(
					self::field( 'email', 'email', __( 'Email', 'radiusforms' ), array( 'required' => true ) ),
					self::field( 'select', 'priority', __( 'Priority', 'radiusforms' ), array( 'options' => array( array( 'label' => __( 'Low', 'radiusforms' ), 'value' => 'low' ), array( 'label' => __( 'High', 'radiusforms' ), 'value' => 'high' ) ) ) ),
					self::field( 'textarea', 'details', __( 'Details', 'radiusforms' ), array( 'required' => true, 'rows' => 5 ) ),
					self::field( 'submit', 'submit', __( 'Submit Ticket', 'radiusforms' ) ),
				),
				'settings'    => $settings,
			),
			'newsletter' => array(
				'name'        => __( 'Newsletter Signup', 'radiusforms' ),
				'description' => __( 'Simple email capture.', 'radiusforms' ),
				'icon'        => 'megaphone',
				'fields'      => array(
					self::field( 'email', 'email', __( 'Email Address', 'radiusforms' ), array( 'required' => true ) ),
					self::field( 'gdpr', 'consent', __( 'Consent', 'radiusforms' ) ),
					self::field( 'submit', 'submit', __( 'Subscribe', 'radiusforms' ) ),
				),
				'settings'    => $settings,
			),
		);
	}
}
