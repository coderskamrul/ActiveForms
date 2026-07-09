<?php
/**
 * Prebuilt form templates.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Builder;

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
		return apply_filters( 'activeforms/templates', $templates );
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
				'message' => __( 'Thank you! Your submission has been received.', 'activeforms' ),
			),
			'notifications' => array(
				array(
					'enabled' => true,
					'name'    => __( 'Admin Notification', 'activeforms' ),
					'to'      => get_bloginfo( 'admin_email' ),
					'subject' => __( 'New Form Submission', 'activeforms' ),
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
				'name'        => __( 'Blank Form', 'activeforms' ),
				'description' => __( 'Start from scratch.', 'activeforms' ),
				'icon'        => 'plus',
				'fields'      => array(
					self::field( 'submit', 'submit', __( 'Submit', 'activeforms' ) ),
				),
				'settings'    => $settings,
			),
			'contact' => array(
				'name'        => __( 'Contact Form', 'activeforms' ),
				'description' => __( 'Name, email, and message.', 'activeforms' ),
				'icon'        => 'email',
				'category'    => 'general',
				'fields'      => array(
					self::field( 'name', 'name', __( 'Name', 'activeforms' ), array( 'required' => true, 'fields' => array( 'first' => array( 'visible' => true, 'label' => __( 'First Name', 'activeforms' ) ), 'last' => array( 'visible' => true, 'label' => __( 'Last Name', 'activeforms' ) ) ) ) ),
					self::field( 'email', 'email', __( 'Email', 'activeforms' ), array( 'required' => true ) ),
					self::field( 'text', 'subject', __( 'Subject', 'activeforms' ) ),
					self::field( 'textarea', 'message', __( 'Message', 'activeforms' ), array( 'required' => true, 'rows' => 5 ) ),
					self::field( 'submit', 'submit', __( 'Send Message', 'activeforms' ) ),
				),
				'settings'    => $settings,
			),
			'support' => array(
				'name'        => __( 'Support Request', 'activeforms' ),
				'description' => __( 'Collect support tickets with priority.', 'activeforms' ),
				'icon'        => 'sos',
				'fields'      => array(
					self::field( 'email', 'email', __( 'Email', 'activeforms' ), array( 'required' => true ) ),
					self::field( 'select', 'priority', __( 'Priority', 'activeforms' ), array( 'options' => array( array( 'label' => __( 'Low', 'activeforms' ), 'value' => 'low' ), array( 'label' => __( 'High', 'activeforms' ), 'value' => 'high' ) ) ) ),
					self::field( 'textarea', 'details', __( 'Details', 'activeforms' ), array( 'required' => true, 'rows' => 5 ) ),
					self::field( 'submit', 'submit', __( 'Submit Ticket', 'activeforms' ) ),
				),
				'settings'    => $settings,
			),
			'newsletter' => array(
				'name'        => __( 'Newsletter Signup', 'activeforms' ),
				'description' => __( 'Simple email capture.', 'activeforms' ),
				'icon'        => 'megaphone',
				'fields'      => array(
					self::field( 'email', 'email', __( 'Email Address', 'activeforms' ), array( 'required' => true ) ),
					self::field( 'gdpr', 'consent', __( 'Consent', 'activeforms' ) ),
					self::field( 'submit', 'submit', __( 'Subscribe', 'activeforms' ) ),
				),
				'settings'    => $settings,
			),
		);
	}
}
