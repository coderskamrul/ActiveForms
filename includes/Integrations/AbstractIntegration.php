<?php
/**
 * Base class for third-party integrations.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Integrations declare their settings schema and a per-entry dispatch method.
 * Core ships a representative set; the Pro add-on registers the full catalog
 * by extending this class.
 */
abstract class AbstractIntegration {

	/**
	 * Unique integration slug.
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Display title.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Category (email_marketing, crm, automation, notification, storage).
	 *
	 * @var string
	 */
	protected $category = 'automation';

	/**
	 * Slug accessor.
	 *
	 * @return string
	 */
	public function slug() {
		return $this->slug;
	}

	/**
	 * Metadata for the integrations UI.
	 *
	 * @return array<string,mixed>
	 */
	public function describe() {
		return array(
			'slug'        => $this->slug,
			'title'       => $this->title,
			'category'    => $this->category,
			'configured'  => $this->is_configured(),
			'globalFields' => $this->global_settings_fields(),
			'feedFields'  => $this->feed_settings_fields(),
		);
	}

	/**
	 * Global (account-level) settings fields.
	 *
	 * @return array<int,array>
	 */
	abstract public function global_settings_fields();

	/**
	 * Per-form feed settings fields.
	 *
	 * @return array<int,array>
	 */
	public function feed_settings_fields() {
		return array();
	}

	/**
	 * Whether the integration has valid global credentials.
	 *
	 * @return bool
	 */
	abstract public function is_configured();

	/**
	 * Dispatch a submission to the remote service.
	 *
	 * @param array $feed  Per-form feed config.
	 * @param array $entry Entry data.
	 * @param array $form  Form schema.
	 * @return bool|\WP_Error
	 */
	abstract public function process( $feed, $entry, $form );

	/**
	 * Read stored global settings for this integration.
	 *
	 * @return array<string,mixed>
	 */
	protected function settings() {
		$all = get_option( 'activeforms_integration_' . $this->slug, array() );
		return is_array( $all ) ? $all : array();
	}
}
