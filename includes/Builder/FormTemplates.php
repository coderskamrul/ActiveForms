<?php
/**
 * Prebuilt form templates.
 *
 * @package EasyForms
 */

namespace EasyForms\Builder;

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
		return apply_filters( 'easyforms/templates', $templates );
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
				'message' => __( 'Thank you! Your submission has been received.', 'easyforms' ),
			),
			'notifications' => array(
				array(
					'enabled' => true,
					'name'    => __( 'Admin Notification', 'easyforms' ),
					'to'      => get_bloginfo( 'admin_email' ),
					'subject' => __( 'New Form Submission', 'easyforms' ),
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
				'name'        => __( 'Blank Form', 'easyforms' ),
				'description' => __( 'Start from scratch.', 'easyforms' ),
				'icon'        => 'plus',
				'fields'      => array(
					self::field( 'submit', 'submit', __( 'Submit', 'easyforms' ) ),
				),
				'settings'    => $settings,
			),
			'contact' => array(
				'name'        => __( 'Contact Form', 'easyforms' ),
				'description' => __( 'Name, email, and message.', 'easyforms' ),
				'icon'        => 'email',
				'category'    => 'general',
				'fields'      => array(
					self::field( 'name', 'name', __( 'Name', 'easyforms' ), array( 'required' => true, 'fields' => array( 'first' => array( 'visible' => true, 'label' => __( 'First Name', 'easyforms' ) ), 'last' => array( 'visible' => true, 'label' => __( 'Last Name', 'easyforms' ) ) ) ) ),
					self::field( 'email', 'email', __( 'Email', 'easyforms' ), array( 'required' => true ) ),
					self::field( 'text', 'subject', __( 'Subject', 'easyforms' ) ),
					self::field( 'textarea', 'message', __( 'Message', 'easyforms' ), array( 'required' => true, 'rows' => 5 ) ),
					self::field( 'submit', 'submit', __( 'Send Message', 'easyforms' ) ),
				),
				'settings'    => $settings,
			),
			'support' => array(
				'name'        => __( 'Support Request', 'easyforms' ),
				'description' => __( 'Collect support tickets with priority.', 'easyforms' ),
				'icon'        => 'sos',
				'fields'      => array(
					self::field( 'email', 'email', __( 'Email', 'easyforms' ), array( 'required' => true ) ),
					self::field( 'select', 'priority', __( 'Priority', 'easyforms' ), array( 'options' => array( array( 'label' => __( 'Low', 'easyforms' ), 'value' => 'low' ), array( 'label' => __( 'High', 'easyforms' ), 'value' => 'high' ) ) ) ),
					self::field( 'textarea', 'details', __( 'Details', 'easyforms' ), array( 'required' => true, 'rows' => 5 ) ),
					self::field( 'submit', 'submit', __( 'Submit Ticket', 'easyforms' ) ),
				),
				'settings'    => $settings,
			),
			'newsletter' => array(
				'name'        => __( 'Newsletter Signup', 'easyforms' ),
				'description' => __( 'Simple email capture.', 'easyforms' ),
				'icon'        => 'megaphone',
				'fields'      => array(
					self::field( 'email', 'email', __( 'Email Address', 'easyforms' ), array( 'required' => true ) ),
					self::field( 'gdpr', 'consent', __( 'Consent', 'easyforms' ) ),
					self::field( 'submit', 'submit', __( 'Subscribe', 'easyforms' ) ),
				),
				'settings'    => $settings,
			),
		);
	}
}
