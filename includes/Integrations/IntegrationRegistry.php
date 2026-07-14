<?php
/**
 * Integration registry.
 *
 * @package RadiusForms
 */

namespace RadiusForms\Integrations;

use RadiusForms\Integrations\Providers\WebhookIntegration;
use RadiusForms\Integrations\Providers\SlackIntegration;
use RadiusForms\Integrations\Providers\MailchimpIntegration;

defined( 'ABSPATH' ) || exit;

/**
 * Holds available integrations and exposes them to the REST/admin layer.
 */
class IntegrationRegistry {

	/**
	 * Registered integrations keyed by slug.
	 *
	 * @var array<string,AbstractIntegration>
	 */
	private $integrations = array();

	/**
	 * Register an integration.
	 *
	 * @param AbstractIntegration $integration Integration instance.
	 * @return void
	 */
	public function register( AbstractIntegration $integration ) {
		$this->integrations[ $integration->slug() ] = $integration;
	}

	/**
	 * Register the free integration set.
	 *
	 * @return void
	 */
	public function register_defaults() {
		$this->register( new WebhookIntegration() );
		$this->register( new SlackIntegration() );
		$this->register( new MailchimpIntegration() );
	}

	/**
	 * Get an integration by slug.
	 *
	 * @param string $slug Integration slug.
	 * @return AbstractIntegration|null
	 */
	public function get( $slug ) {
		return isset( $this->integrations[ $slug ] ) ? $this->integrations[ $slug ] : null;
	}

	/**
	 * All integrations.
	 *
	 * @return array<string,AbstractIntegration>
	 */
	public function all() {
		return $this->integrations;
	}

	/**
	 * Descriptors for the integrations UI.
	 *
	 * @return array<int,array>
	 */
	public function describe_all() {
		$out = array();
		foreach ( $this->integrations as $integration ) {
			$out[] = $integration->describe();
		}
		return $out;
	}
}
