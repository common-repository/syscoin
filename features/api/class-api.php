<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress
 * It includes the Syscoin_Direct class which provides functions related to the plugin's API.
 *
 * @package syscoin
 */

/**
 * This class represents the direct requests feature of the plugin.
 */
class Syscoin_Api {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_settings;
		$this->settings = $syscoin_settings;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;

		global $syscoin_analytics_values;
		$this->analytics = $syscoin_analytics_values;

		global $syscoin_reports;
		$this->reports = $syscoin_reports;

		add_action(
			'rest_api_init',
			array( $this, 'register_endpoints' )
		);
	}

	/**
	 * The current version of the API. Increase when adding breaking changes.
	 *
	 * @var string;
	 */
	private $version = 'v1';

	/**
	 * Reference to global instance of Syscoin_Settings.
	 *
	 * @var Syscoin_Settings
	 */
	private $settings;

	/**
	 * Reference to global instance of Syscoin_Analytics_Values.
	 *
	 * @var Syscoin_Analytics_Values
	 */
	private $analytics;

	/**
	 * Reference to global instance of Syscoin_Utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Reference to global instance of Syscoin_Reports.
	 *
	 * @var Syscoin_Reports
	 */
	private $reports;

	/**
	 * Generate an associative array with information about the website installation.
	 */
	public function get_info() {
		$settings = $this->settings->get_settings();

		if ( isset( $settings ) && ! empty( $settings ) ) {
			$data = array(
				'success'      => true,
				'info'         => array(
					'plugin_ver'    => $this->utils->get_plugin_version(),
					'plugin_env'    => $this->utils->get_env(),
					'plugin_agency' => $this->utils->get_agency(),
					'wp_ver'        => get_bloginfo( 'version' ) ?? '?',
					'php_ver'       => phpversion() ?? '?',
					'timezone'      => $this->utils->get_wp_timezone(),
					'woo_active'    => $this->utils->is_woocommerce_active(),
					'site_name'     => get_bloginfo( 'name' ),
					'site_url'      => site_url(),
				),
				'settings'     => $this->settings->get_settings(),
				'next_reports' => $this->reports->get_schedules(),
			);
		} else {
			$data = array(
				'success' => false,
			);
		}

		return $data;
	}

	/**
	 * Returns the endpoints namespace, a string in the format `agency/v1`.
	 */
	public function get_endpoint_prefix() {
		return $this->utils->get_agency() . '/' . $this->version;
	}

	/**
	 * Checks if the request is authenticated.
	 *
	 * @param WP_REST_Request $request The REST API request.
	 */
	public function authenticate_request( $request ) {
		$request_token = $request->get_header( 'Authorization' );
		$saved_token   = $this->settings->get_user()['token'];

		if ( $request_token === $saved_token ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Authenticate a request regardless of the request's content.
	 */
	public function authenticate_request_always() {
		return true;
	}

	/**
	 * Handle CORS.
	 */
	public function handle_cors() {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
	}

	/**
	 * Register the endpoints of the plugin's API.
	 */
	public function register_endpoints() {
		// The current plugin settings.
		register_rest_route(
			$this->get_endpoint_prefix(),
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_request_settings' ),
				'permission_callback' => array( $this, 'authenticate_request' ),
			)
		);

		// Analytics.
		register_rest_route(
			$this->get_endpoint_prefix(),
			'/analytics',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_request_analytics' ),
				'permission_callback' => array( $this, 'authenticate_request' ),
			)
		);

		// Plugin installation check.
		register_rest_route(
			$this->get_endpoint_prefix(),
			'/check',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_plugin_check' ),
				'permission_callback' => array( $this, 'authenticate_request_always' ),
			)
		);
	}

	/**
	 * Handle REST API request
	 *
	 * @return WP_REST_Response The response for the request.
	 */
	public function handle_request_settings() {
		$this->handle_cors();

		$settings = $this->settings->get_settings();

		if ( isset( $settings ) && ! empty( $settings ) ) {
			$data = $this->get_info();
		} else {
			$data = array(
				'success' => false,
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Handle REST API request
	 *
	 * @param WP_REST_Request $request The REST API request.
	 * @return WP_REST_Response The response for the request.
	 */
	public function handle_request_analytics( WP_REST_Request $request ) {
		$this->handle_cors();

		$start = $request->get_param( 'start' );
		$end   = $request->get_param( 'end' );
		$delta = ' ' . $request->get_param( 'delta' );

		if ( ! $this->utils->is_valid_date_string( $start ) || ! $this->utils->is_valid_date_string( $end ) ) {
			$data = array(
				'success'   => false,
				'analytics' => null,
				'reason'    => 'PERIOD_INVALID',
			);

			return new WP_REST_Response( $data, 400 );
		}

		if ( ! $this->analytics->is_data_available() ) {
			$data = array(
				'success'   => false,
				'analytics' => null,
				'reason'    => 'DATA_UNAVAILABLE',
			);

			return new WP_REST_Response( $data, 404 );
		}

		$analytics_current  = $this->analytics->get_summary( $start, $end );
		$analytics_previous = $this->analytics->get_summary( $start . $delta, $end . $delta );

		$analytics = array(
			'current'  => $analytics_current,
			'previous' => $analytics_previous,
			'change'   => $this->utils->array_diff_assoc_recursive( $analytics_previous, $analytics_current ),
		);

		if ( isset( $analytics ) && ! empty( $analytics ) ) {
			$data = array(
				'success'    => true,
				'analytics'  => $analytics,
				'curr_start' => $start,
				'curr_end'   => $end,
				'prev_start' => $start . $delta,
				'prev_end'   => $end . $delta,
			);
		} else {
			$data = array(
				'success'   => false,
				'analytics' => null,
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Handle requests to check plugin installation.
	 *
	 * @return WP_REST_Response The response for the request.
	 */
	public function handle_plugin_check() {
		$this->handle_cors();

		$user = $this->settings->get_user();

		$data = array(
			'logged_in' => $user['logged_in'],
		);

		return new WP_REST_Response(
			$data,
			200
		);
	}
}

global $syscoin_api;

$syscoin_api = new Syscoin_Api();
