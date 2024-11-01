<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Analytics class which provides functions related to the analytics features of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../../tables/class-access-logs.php';
require_once plugin_dir_path( __FILE__ ) . 'class-analytics-tabs.php';

/**
 * This class handles analytics-related functionality.
 */
class Syscoin_Analytics {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_utils;
		$this->utils = $syscoin_utils;

		global $syscoin_table_access_logs;

		$this->tabs   = new Syscoin_Analytics_Tabs();
		$this->charts = new Syscoin_Analytics_Charts();

		add_action( 'template_redirect', array( $this, 'log_page_access' ) );
		add_action( 'admin_init', array( $syscoin_table_access_logs, 'create_table' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_getRequestsTable', array( $this, 'handle_logs_request' ) );
		add_action( 'wp_ajax_getPeriodData', array( $this, 'handle_period_change' ) );
	}

	/**
	 * Reference to global variable $syscoin_utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Instance of AnalyticsTabs.
	 *
	 * @var Syscoin_Analytics_Tabs
	 */
	private $tabs;

	/**
	 * Instance of AnalyticsCharts.
	 *
	 * @var Syscoin_Analytics_Charts
	 */
	private $charts;

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		global $syscoin_env;
		global $syscoin_settings;

		if ( $syscoin_env['AGENCY'] . '_page_syscoin-analytics-overview' !== $hook ) {
			return;
		}

		$ver = $this->utils->get_plugin_version();

		wp_enqueue_script( 'chartjs', plugin_dir_url( __FILE__ ) . '../../assets/js/chart.umd.js', array(), '4.4.4', true );

		wp_enqueue_script( 'syscoin-analytics-overview-js', plugin_dir_url( __FILE__ ) . 'analytics-overview.js', array( 'jquery', 'syscoin-utils-js' ), $ver, true );

		$user = $syscoin_settings->get_user();

		wp_localize_script(
			'syscoin-analytics-overview-js',
			'script_vars',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'syscoin_nonce' ),
				'logged_in' => isset( $user ) && $user['logged_in'],
			)
		);
	}

	/**
	 * Array of patterns to exclude from analytics.
	 *
	 * @var array
	 */
	private $exclude_patterns = array(
		'/\.ico$/i',
		'/\.png$/i',
		'/\.jpg$/i',
		'/\.jpeg$/i',
		'/\.gif$/i',
		'/\.css$/i',
		'/\.js$/i',
	);

	/**
	 * Capture page access.
	 */
	public function log_page_access() {
		$requested_page = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		foreach ( $this->exclude_patterns as $pattern ) {
			if ( preg_match( $pattern, $requested_page ) ) {
				return;
			}
		}

		$parsed_url = wp_parse_url( $requested_page );

		if ( is_user_logged_in() ) {
			$user_id = wp_get_current_user()->ID;
		} else {
			$user_id = null;
		}

		$utm = array(
			'source'   => isset( $_GET['utm_source'] ) ? wp_unslash( sanitize_text_field( $_GET['utm_source'] ) ) : null, // phpcs:ignore
			'medium'   => isset( $_GET['utm_medium'] ) ? wp_unslash( sanitize_text_field( $_GET['utm_medium'] ) ) : null, // phpcs:ignore
			'campaign' => isset( $_GET['utm_campaign'] ) ? wp_unslash( sanitize_text_field( $_GET['utm_campaign'] ) ) : null, // phpcs:ignore
			'term'     => isset( $_GET['utm_term'] ) ? wp_unslash( sanitize_text_field( $_GET['utm_term'] ) ) : null, // phpcs:ignore
			'content'  => isset( $_GET['utm_content'] ) ? wp_unslash( sanitize_text_field( $_GET['utm_content'] ) ) : null, // phpcs:ignore
		);

		$access_data = array(
			'access_time'    => $this->utils->create_date( 'now', 'UTC' )->format( 'Y-m-d H:i:s' ),
			'user_ip'        => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'requested_page' => isset( $parsed_url['path'] ) ? $parsed_url['path'] : '',
			'referer'        => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : 'Direct/None',
			'utm_source'     => $utm['source'] ?? 'No source',
			'utm_medium'     => $utm['medium'] ?? 'No medium',
			'utm_campaign'   => $utm['campaign'] ?? 'No campaign',
			'utm_term'       => $utm['term'] ?? 'No term',
			'utm_content'    => $utm['content'] ?? 'No content',
			'user_id'        => strval( $user_id ),
		);

		global $syscoin_table_access_logs;

		$syscoin_table_access_logs->log_access( $access_data );
	}

	/**
	 * Callback function for the subpage.
	 */
	public function analytics_page_callback() {
		?>
		<div class="syscoin-page-header">
			<div>
				<h1> 
					<?php esc_html_e( 'ANALYTICS', 'syscoin' ); ?> 
				</h1>

				<p>
					<?php esc_html_e( 'ANALYTICS_DESCRIPTION', 'syscoin' ); ?> 
				</p>
			</div>

			<div style="height: 100px; display: flex; align-items: center;">
				<div style="height: 100px; display:flex; flex-direction: column; justify-content: space-around;">
					<select id="syscoin-analytics-period-select">
						<!-- <option value="prev-day">Ontem</option> -->
						<option value="last-7days"> <?php esc_html_e( 'LAST_7_DAYS', 'syscoin' ); ?> </option>
						<option value="last-30days"> <?php esc_html_e( 'LAST_30_DAYS', 'syscoin' ); ?> </option>
						<option value="last-90days"> <?php esc_html_e( 'LAST_90_DAYS', 'syscoin' ); ?> </option>
						<option value="last-180days"> <?php esc_html_e( 'LAST_180_DAYS', 'syscoin' ); ?> </option>
						<option value="this-month"> <?php esc_html_e( 'THIS_MONTH', 'syscoin' ); ?> </option>
						<option value="prev-month"> <?php esc_html_e( 'LAST_MONTH', 'syscoin' ); ?> </option>
						<option value="this-year"> <?php esc_html_e( 'THIS_YEAR', 'syscoin' ); ?> </option>
						<!-- <option value="prev-year"> <!?php esc_html_e( 'LAST_YEAR', 'syscoin' ); ?> </option> -->
						<!-- <option value="custom"> <!?php esc_html_e( 'CUSTOM_PERIOD', 'syscoin' ); ?> </option> -->
					</select>

					<!-- <div id="syscoin-custom-period-div">
						<input type="date" id="syscoin-analytics-period-custom-start">
						<input type="date" id="syscoin-analytics-period-custom-end">
					</div> -->
				</div>

				<div class="syscoin-name" style="width: 200px; background-position: right;"></div>
			</div>
		</div>

		<div class="syscoin-initially-shown" style="flex-direction: column; justify-content: left;">
			<div>
				<hr style="margin-top: 35px;">
			</div>

			<div class="syscoin-loading-spinner-container" style="display:flex; flex-direction: row; margin-top: 25px;">
				<div class="syscoin-loading-spinner"></div>
				<i id="syscoin-reports-saving"> &nbsp; &bull; <?php esc_html_e( 'LOADING', 'syscoin' ); ?> </i>
			</div>
		</div>

		<div class="syscoin-initially-hidden">
			<div id="syscoin-analytics-for-logged-out-users">
				<hr style="margin-top: 35px;">
				<?php esc_html_e( 'YOU_HAVE_TO_LOG_IN_TO_USE_THE_PLUGINS_FEATURES', 'syscoin' ); ?> 
			</div>

			<div id="syscoin-analytics-for-empty-access-logs">
				<hr style="margin-top: 35px;">
				<?php esc_html_e( 'ANALYTICS_NOT_ENOUGH_DATA', 'syscoin' ); ?> 
			</div>

			<div id="syscoin-analytics-for-logged-in-users">
				<nav class="nav-tab-wrapper" style="padding-top: 0; display: flex;">
					<div style="flex: 1;">
						<a id="syscoin-analytics-navbar-general" href="#general" class="nav-tab nav-tab-active">
							<?php esc_html_e( 'GENERAL', 'syscoin' ); ?> 
						</a>

						<a id="syscoin-analytics-navbar-utm" href="#utm" class="nav-tab">
							<?php esc_html_e( 'TRAFFIC_SOURCES', 'syscoin' ); ?> 
						</a>

						<a id="syscoin-analytics-navbar-individual" href="#individual" class="nav-tab">
							<?php esc_html_e( 'INDIVIDUAL_REQUESTS', 'syscoin' ); ?> 
						</a>
					</div>

					<div style="flex: 1;">
						<div style="display: flex; justify-content: center; margin-top: 10px; ">
							<?php esc_html_e( 'DATA_FOR_PERIOD', 'syscoin' ); ?> &nbsp; 

							<b id="syscoin-analytics-period-start"> </b>

							&nbsp; <?php esc_html_e( 'TO', 'syscoin' ); ?> &nbsp;
							
							<b id="syscoin-analytics-period-end"> </b>
						</div>
					</div>

					<div style="flex: 1;"></div>
				</nav>

				<div id="syscoin-analytics-tab-general">
					<?php $this->tabs->general(); ?>
				</div>

				<div id="syscoin-analytics-tab-utm">
					<?php $this->tabs->utm(); ?>
				</div>

				<div id="syscoin-analytics-tab-individual">
					<?php $this->tabs->individual(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles queries for individual requests table.
	 */
	public function handle_logs_request() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( ! isset( $_POST['start'] ) || ! isset( $_POST['end'] ) ) {
			wp_send_json(
				array(
					'success' => false,
				)
			);

			wp_die();

			return;
		}

		$start = sanitize_text_field( wp_unslash( $_POST['start'] ) );
		$end   = sanitize_text_field( wp_unslash( $_POST['end'] ) );

		ob_start();
		$this->charts->display_access_logs_table( $start, $end );
		$table_html = ob_get_clean();

		wp_send_json(
			array(
				'success'   => true,
				'htmlTable' => $table_html,
			)
		);
		wp_die();
	}

	/**
	 * Handles period changes in the front-end.
	 */
	public function handle_period_change() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( ! isset( $_POST['start'] ) || ! isset( $_POST['end'] ) || ! isset( $_POST['granularity'] ) || ! isset( $_POST['tz'] ) ) {
			wp_send_json(
				array(
					'success' => false,
				)
			);

			wp_die();

			return;
		}

		$in_start       = sanitize_text_field( wp_unslash( $_POST['start'] ) );
		$in_end         = sanitize_text_field( wp_unslash( $_POST['end'] ) );
		$in_granularity = sanitize_text_field( wp_unslash( $_POST['granularity'] ) );
		$in_timezone    = sanitize_text_field( wp_unslash( $_POST['tz'] ) );

		$utc_start = $this->utils->timestring_to_utc( $in_start, $in_timezone );
		$utc_end   = $this->utils->timestring_to_utc( $in_end, $in_timezone );

		global $syscoin_table_access_logs;

		$logs = $syscoin_table_access_logs->get_access_logs( $utc_start, $utc_end );
		$logs = $this->utils->filter_unique_accesses( $logs );
		$utm  = $syscoin_table_access_logs->calc_utm_overview( $logs );

		$day_counts = $syscoin_table_access_logs->calc_day_counts( $logs, $utc_start, $utc_end );

		wp_send_json(
			array(
				'success' => true,
				'data'    => array(
					'date_start'  => $this->utils->create_date( $utc_start, 'UTC', true )->format( DateTime::ATOM ),
					'date_end'    => $this->utils->create_date( $utc_end, 'UTC', true )->format( DateTime::ATOM ),
					'is_empty'    => $syscoin_table_access_logs->is_empty(),
					'page_counts' => $syscoin_table_access_logs->calc_page_counts( $logs ),
					'day_counts'  => $day_counts,
					'utm_counts'  => $utm,
				),
				'html'    => array(
					'general' => array(
						'summary' => $this->charts->generate_summary_table( $logs, $day_counts ),
						'pages'   => $this->charts->generate_page_requests_table( $logs ),
					),
					'utm'     => array(
						'campaign' => $this->charts->generate_utm_count_table( 'Campaign', 'campaign', $utm ),
						'content'  => $this->charts->generate_utm_count_table( 'Content', 'content', $utm ),
						'medium'   => $this->charts->generate_utm_count_table( 'Medium', 'medium', $utm ),
						'source'   => $this->charts->generate_utm_count_table( 'Source', 'source', $utm ),
						'term'     => $this->charts->generate_utm_count_table( 'Term', 'term', $utm ),
					),
				),
			)
		);

		wp_die();
	}
}

global $syscoin_analytics;

$syscoin_analytics = new Syscoin_Analytics();
