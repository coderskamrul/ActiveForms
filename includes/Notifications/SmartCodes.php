<?php
/**
 * Smart-code (merge-tag) parser.
 *
 * @package EasyForms
 */

namespace EasyForms\Notifications;

use EasyForms\Support\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces {tokens} in strings with submission/site/user values. Output is
 * escaped by callers as appropriate; this returns plain text.
 */
class SmartCodes {

	/**
	 * Parse smart codes within a string.
	 *
	 * @param string $text    Source text.
	 * @param array  $entry   Entry data (with 'response').
	 * @param array  $form    Form schema.
	 * @return string
	 */
	public function parse( $text, $entry = array(), $form = array() ) {
		if ( false === strpos( (string) $text, '{' ) ) {
			return (string) $text;
		}

		return preg_replace_callback(
			'/\{([a-z0-9_.\-]+)\}/i',
			function ( $matches ) use ( $entry, $form ) {
				return $this->resolve( $matches[1], $entry, $form );
			},
			(string) $text
		);
	}

	/**
	 * Resolve a single token.
	 *
	 * @param string $token Token name (without braces).
	 * @param array  $entry Entry data.
	 * @param array  $form  Form schema.
	 * @return string
	 */
	protected function resolve( $token, $entry, $form ) {
		$response = Arr::get( $entry, 'response', array() );

		// Site/admin tokens.
		switch ( $token ) {
			case 'site.url':
				return home_url();
			case 'site.name':
				return get_bloginfo( 'name' );
			case 'admin.email':
				return get_bloginfo( 'admin_email' );
			case 'date.now':
				return date_i18n( get_option( 'date_format' ) );
			case 'form.title':
				return Arr::get( $form, 'title', '' );
			case 'all.fields':
				return $this->render_all_fields( $response );
		}

		// User tokens: user.email, user.display_name, etc.
		if ( 0 === strpos( $token, 'user.' ) ) {
			$user = wp_get_current_user();
			if ( ! $user || ! $user->ID ) {
				return '';
			}
			$prop = substr( $token, 5 );
			$map  = array(
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'login'        => $user->user_login,
				'id'           => (string) $user->ID,
				'first_name'   => $user->first_name,
				'last_name'    => $user->last_name,
			);
			return isset( $map[ $prop ] ) ? (string) $map[ $prop ] : '';
		}

		// GET parameter tokens: get.param.
		if ( 0 === strpos( $token, 'get.' ) ) {
			$param = substr( $token, 4 );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
		}

		// Field tokens: field.key or bare key.
		$key = 0 === strpos( $token, 'field.' ) ? substr( $token, 6 ) : $token;
		if ( isset( $response[ $key ] ) ) {
			$value = $response[ $key ];
			return is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		}

		return '';
	}

	/**
	 * Render all submitted fields as an HTML table fragment.
	 *
	 * @param array $response Response map.
	 * @return string
	 */
	protected function render_all_fields( $response ) {
		$rows = '';
		foreach ( (array) $response as $key => $value ) {
			$value = is_array( $value ) ? implode( ', ', $value ) : $value;
			$rows .= '<tr><td><strong>' . esc_html( $key ) . '</strong></td><td>' . esc_html( $value ) . '</td></tr>';
		}
		return '<table>' . $rows . '</table>';
	}
}
