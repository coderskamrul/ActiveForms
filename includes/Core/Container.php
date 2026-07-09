<?php
/**
 * Minimal service container.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Stores shared singletons (registries, services) keyed by name. Lazy
 * factories are supported so services instantiate on first access.
 */
class Container {

	/**
	 * Resolved instances.
	 *
	 * @var array<string,mixed>
	 */
	private $instances = array();

	/**
	 * Lazy factories.
	 *
	 * @var array<string,callable>
	 */
	private $factories = array();

	/**
	 * Bind a lazy factory.
	 *
	 * @param string   $key     Service key.
	 * @param callable $factory Factory returning the service.
	 * @return void
	 */
	public function bind( $key, callable $factory ) {
		$this->factories[ $key ] = $factory;
	}

	/**
	 * Store an already-built instance.
	 *
	 * @param string $key      Service key.
	 * @param mixed  $instance Service instance.
	 * @return mixed
	 */
	public function set( $key, $instance ) {
		$this->instances[ $key ] = $instance;
		return $instance;
	}

	/**
	 * Resolve a service.
	 *
	 * @param string $key Service key.
	 * @return mixed|null
	 */
	public function get( $key ) {
		if ( isset( $this->instances[ $key ] ) ) {
			return $this->instances[ $key ];
		}

		if ( isset( $this->factories[ $key ] ) ) {
			$this->instances[ $key ] = call_user_func( $this->factories[ $key ], $this );
			return $this->instances[ $key ];
		}

		return null;
	}

	/**
	 * Whether a service is registered.
	 *
	 * @param string $key Service key.
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->instances[ $key ] ) || isset( $this->factories[ $key ] );
	}
}
