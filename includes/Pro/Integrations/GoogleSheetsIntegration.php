<?php
/**
 * Google Sheets integration (Pro).
 *
 * @package RadiusFormsPro
 */

namespace RadiusFormsPro\Integrations;

use RadiusForms\Integrations\AbstractIntegration;

defined( 'ABSPATH' ) || exit;

/**
 * Demonstrates registering a Pro integration on the free plugin's registry.
 * The dispatch is a stub; a production build would push rows to the Sheets API.
 */
class GoogleSheetsIntegration extends AbstractIntegration {

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'google_sheets';

	/**
	 * {@inheritDoc}
	 */
	protected $title = 'Google Sheets';

	/**
	 * {@inheritDoc}
	 */
	protected $category = 'storage';

	/**
	 * {@inheritDoc}
	 */
	public function global_settings_fields() {
		return array(
			array(
				'key'   => 'service_account',
				'type'  => 'textarea',
				'label' => __( 'Service Account JSON', 'radiusforms' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'key'   => 'spreadsheet_id',
				'type'  => 'text',
				'label' => __( 'Spreadsheet ID', 'radiusforms' ),
			),
			array(
				'key'   => 'worksheet',
				'type'  => 'text',
				'label' => __( 'Worksheet name', 'radiusforms' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_configured() {
		$settings = $this->settings();
		return ! empty( $settings['service_account'] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function process( $feed, $entry, $form ) {
		/**
		 * Fires when a Google Sheets feed would dispatch (stub for the scaffold).
		 *
		 * @param array $feed  Feed config.
		 * @param array $entry Entry data.
		 * @param array $form  Form schema.
		 */
		do_action( 'radiusforms_pro/google_sheets_dispatch', $feed, $entry, $form );
		return true;
	}
}
