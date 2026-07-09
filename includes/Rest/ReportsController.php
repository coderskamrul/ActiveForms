<?php
/**
 * Reports / analytics REST controller.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Rest;

use ActiveForms\Reporting\Analytics;

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
			'/reports/dashboard',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'dashboard' ),
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
	 * Dashboard overview (legacy minimal payload, kept for compatibility).
	 *
	 * @return \WP_REST_Response
	 */
	public function overview() {
		return $this->ok( Analytics::overview() );
	}

	/**
	 * Full analytics dashboard, scoped by form + date range.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function dashboard( $request ) {
		return $this->ok(
			Analytics::dashboard(
				array(
					'form_id' => (int) $request->get_param( 'form_id' ),
					'from'    => sanitize_text_field( (string) $request->get_param( 'from' ) ),
					'to'      => sanitize_text_field( (string) $request->get_param( 'to' ) ),
				)
			)
		);
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
