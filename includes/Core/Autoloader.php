<?php
/**
 * Lightweight PSR-4 autoloader.
 *
 * @package EasyForms
 */

namespace EasyForms\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Maps the EasyForms\ namespace onto the includes/ directory without requiring
 * Composer in the distributed plugin.
 */
class Autoloader {

	/**
	 * Root namespace prefix this autoloader handles.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Absolute base directory for the prefix.
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $prefix   Namespace prefix, e.g. "EasyForms\".
	 * @param string $base_dir Directory the prefix maps to.
	 */
	public function __construct( $prefix, $base_dir ) {
		$this->prefix   = $prefix;
		$this->base_dir = rtrim( $base_dir, '/\\' ) . '/';
	}

	/**
	 * Register the autoloader with the SPL stack.
	 *
	 * @return void
	 */
	public function register() {
		spl_autoload_register( array( $this, 'load' ) );
	}

	/**
	 * Resolve and require a class file.
	 *
	 * @param string $class Fully-qualified class name.
	 * @return void
	 */
	public function load( $class ) {
		$len = strlen( $this->prefix );
		if ( 0 !== strncmp( $this->prefix, $class, $len ) ) {
			return;
		}

		$relative = substr( $class, $len );
		$path     = $this->base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
