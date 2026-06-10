<?php
/**
 * Reports / analytics REST controller.
 *
 * @package EasyForms
 */

namespace EasyForms\Rest;

use EasyForms\Reporting\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard overview stats and per-form field reports.
 */
class ReportsController extends AbstractController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/reports/overview',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'overview' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/forms/(?P<form_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'form_report' ),
				'permission_callback' => array( $this, 'can_read' ),
			)
		);
	}

	/**
	 * Dashboard overview.
	 *
	 * @return \WP_REST_Response
	 */
	public function overview() {
		return $this->ok( Analytics::overview() );
	}

	/**
	 * Per-form field report.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function form_report( $request ) {
		return $this->ok( Analytics::form_report( (int) $request['form_id'] ) );
	}
}
