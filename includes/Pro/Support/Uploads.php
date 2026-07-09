<?php
/**
 * Upload storage helper for Pro fields (File, Image, Signature).
 *
 * @package ActiveFormsPro
 */

namespace ActiveFormsPro\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Centralizes where ActiveForms Pro stores uploaded files and how they are
 * validated. Files live under wp-content/uploads/activeforms-pro/{Y}/{m}/ and are
 * referenced from submissions by their path relative to that base.
 */
class Uploads {

	/**
	 * Sub-directory (under wp_upload_dir basedir) for Pro uploads.
	 */
	const SUBDIR = 'activeforms-pro';

	/**
	 * Absolute base directory for uploads, ensuring it exists and is protected.
	 *
	 * @return string Absolute path with trailing slash.
	 */
	public static function base_dir() {
		$uploads = wp_upload_dir();
		$base    = trailingslashit( $uploads['basedir'] ) . self::SUBDIR;

		if ( ! is_dir( $base ) ) {
			wp_mkdir_p( $base );
			// Block directory listing / hotlinking guesswork.
			@file_put_contents( $base . '/index.html', '' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return trailingslashit( $base );
	}

	/**
	 * Base public URL for uploads.
	 *
	 * @return string URL with trailing slash.
	 */
	public static function base_url() {
		$uploads = wp_upload_dir();
		return trailingslashit( trailingslashit( $uploads['baseurl'] ) . self::SUBDIR );
	}

	/**
	 * Resolve a stored relative path to an absolute path.
	 *
	 * @param string $relative Relative path (e.g. "2026/06/abc.jpg").
	 * @return string
	 */
	public static function path_for( $relative ) {
		return self::base_dir() . ltrim( (string) $relative, '/' );
	}

	/**
	 * Resolve a stored relative path to a public URL.
	 *
	 * @param string $relative Relative path.
	 * @return string
	 */
	public static function url_for( $relative ) {
		return self::base_url() . ltrim( (string) $relative, '/' );
	}

	/**
	 * Current Y/m sub-path, creating the directory if needed.
	 *
	 * @return string Relative "Y/m" with trailing slash, or '' on failure.
	 */
	protected static function month_dir() {
		$rel = gmdate( 'Y/m' ) . '/';
		$abs = self::base_dir() . $rel;
		if ( ! is_dir( $abs ) && ! wp_mkdir_p( $abs ) ) {
			return '';
		}
		return $rel;
	}

	/**
	 * Store an uploaded file from a $_FILES-style entry.
	 *
	 * @param array         $file    Single $_FILES entry (name, type, tmp_name, error, size).
	 * @param array<string> $allowed Allowed extensions (lowercase, no dot). Empty = WP defaults.
	 * @param int           $max_kb  Max size in kilobytes (0 = no extra limit).
	 * @return array|\WP_Error { path, url, name, size, mime } on success.
	 */
	public static function store_file( $file, $allowed = array(), $max_kb = 0 ) {
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'activeforms_upload_invalid', __( 'No valid file was uploaded.', 'activeforms' ) );
		}
		if ( ! empty( $file['error'] ) ) {
			return new \WP_Error( 'activeforms_upload_error', __( 'The file failed to upload. Please try again.', 'activeforms' ) );
		}

		$name      = sanitize_file_name( (string) $file['name'] );
		$ext       = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$size      = (int) $file['size'];
		$check     = wp_check_filetype_and_ext( $file['tmp_name'], $name );
		$real_ext  = $check['ext'] ? strtolower( $check['ext'] ) : $ext;
		$real_mime = $check['type'] ? $check['type'] : '';

		if ( ! $real_ext || ! $real_mime ) {
			return new \WP_Error( 'activeforms_upload_type', __( 'This file type is not allowed.', 'activeforms' ) );
		}
		if ( ! empty( $allowed ) && ! in_array( $real_ext, array_map( 'strtolower', $allowed ), true ) ) {
			return new \WP_Error( 'activeforms_upload_type', __( 'This file type is not allowed.', 'activeforms' ) );
		}
		if ( $max_kb > 0 && $size > $max_kb * 1024 ) {
			/* translators: %s: maximum size in kilobytes. */
			return new \WP_Error( 'activeforms_upload_size', sprintf( __( 'The file is too large (max %s KB).', 'activeforms' ), number_format_i18n( $max_kb ) ) );
		}

		$month = self::month_dir();
		if ( '' === $month ) {
			return new \WP_Error( 'activeforms_upload_dir', __( 'Could not prepare the upload directory.', 'activeforms' ) );
		}

		$base     = pathinfo( $name, PATHINFO_FILENAME );
		$unique   = $base . '-' . substr( md5( $name . microtime( true ) . wp_rand() ), 0, 8 ) . '.' . $real_ext;
		$relative = $month . $unique;
		$dest     = self::base_dir() . $relative;

		if ( ! @move_uploaded_file( $file['tmp_name'], $dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new \WP_Error( 'activeforms_upload_move', __( 'Could not save the uploaded file.', 'activeforms' ) );
		}
		@chmod( $dest, 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.chmod_chmod

		return array(
			'path' => $relative,
			'url'  => self::url_for( $relative ),
			'name' => $name,
			'size' => $size,
			'mime' => $real_mime,
		);
	}

	/**
	 * Store a base64 data URL (used by the signature pad) as a PNG file.
	 *
	 * @param string $data_url A "data:image/png;base64,..." string.
	 * @return string|\WP_Error Relative path on success.
	 */
	public static function store_data_url( $data_url ) {
		if ( ! preg_match( '#^data:image/(png|jpeg);base64,#', (string) $data_url, $m ) ) {
			return new \WP_Error( 'activeforms_signature_invalid', __( 'Invalid signature data.', 'activeforms' ) );
		}

		$ext     = 'jpeg' === $m[1] ? 'jpg' : 'png';
		$encoded = substr( $data_url, strpos( $data_url, ',' ) + 1 );
		$decoded = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $decoded || strlen( $decoded ) < 8 ) {
			return new \WP_Error( 'activeforms_signature_invalid', __( 'Invalid signature data.', 'activeforms' ) );
		}

		$month = self::month_dir();
		if ( '' === $month ) {
			return new \WP_Error( 'activeforms_upload_dir', __( 'Could not prepare the upload directory.', 'activeforms' ) );
		}

		$relative = $month . 'signature-' . substr( md5( $decoded . microtime( true ) ), 0, 10 ) . '.' . $ext;
		$dest     = self::base_dir() . $relative;

		if ( false === @file_put_contents( $dest, $decoded ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new \WP_Error( 'activeforms_signature_save', __( 'Could not save the signature.', 'activeforms' ) );
		}

		return $relative;
	}

	/**
	 * Whether a stored relative path points to an existing file inside the
	 * upload base (guards against path traversal in submitted references).
	 *
	 * @param string $relative Relative path.
	 * @return bool
	 */
	public static function exists( $relative ) {
		$relative = ltrim( (string) $relative, '/' );
		if ( '' === $relative || false !== strpos( $relative, '..' ) ) {
			return false;
		}
		$abs  = self::path_for( $relative );
		$real = realpath( $abs );
		$base = realpath( self::base_dir() );
		return $real && $base && 0 === strpos( $real, $base ) && is_file( $real );
	}
}
