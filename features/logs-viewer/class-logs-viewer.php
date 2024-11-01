<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the syscoin Plugin for WordPress.
 * It includes the Logs_Viewer class which provides functions related to the logs viewer features of the plugin.
 *
 * @package syscoin
 */

/**
 * This class handles diagnostics-related functionality.
 */
class Syscoin_Logs_Viewer {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_utils;
		$this->utils = $syscoin_utils;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_getLogs', array( $this, 'handle_get_logs' ) );
	}

	/**
	 * Reference to global variable $syscoin_utils.
	 *
	 * @var syscoin_Utils
	 */
	private $utils;

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $syscoin_env;

		if ( $syscoin_env['AGENCY'] . '_page_syscoin-logs-viewer' !== $hook ) {
			return;
		}

		$ver = $this->utils->get_plugin_version();

		wp_enqueue_script( 'chartjs', plugin_dir_url( __FILE__ ) . '../../assets/js/chart.umd.js', array(), '4.4.4', true );
		wp_enqueue_script( 'luxon', plugin_dir_url( __FILE__ ) . '../../assets/js/luxon.min.js', array(), '3.5.0', true );
		wp_enqueue_script( 'luxon-adapter', plugin_dir_url( __FILE__ ) . '../../assets/js/chartjs-adapter-luxon.umd.js', array(), '1.3.1', true );

		wp_enqueue_script( 'syscoin-logs-viewer-js', plugin_dir_url( __FILE__ ) . 'script-logs-viewer.js', array( 'jquery', 'syscoin-utils-js' ), $ver, true );

		global $syscoin_settings;

		$user = $syscoin_settings->get_user();

		wp_localize_script(
			'syscoin-logs-viewer-js',
			'script_vars',
			array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'syscoin_nonce' ),
				'logged_in' => isset( $user ) && $user['logged_in'],
				'assets'    => array(
					'expand' => plugin_dir_url( __FILE__ ) . '../../assets/syscoin-expand-down.svg',
				),
			)
		);
	}

	/**
	 * Callback function for the subpage.
	 */
	public function logs_viewer_page_callback() {
		if ( isset( $_REQUEST['id'] )) {/* phpcs:ignore */
			$id = sanitize_text_field( $_REQUEST['id'] ); /* phpcs:ignore */
		} else {
			$id = null;
		}

		?>
		<div class="syscoin-page-header">
			<div>
				<h1>
					<?php esc_html_e( 'ERROR_VIEWER', 'syscoin' ); ?>
				</h1>

				<p>
					<?php esc_html_e( 'ERROR_VIEWER_DESCRIPTION', 'syscoin' ); ?>
				</p>
			</div>

			<div style="height: 100px; display: flex; align-items: center;">
				<div class="syscoin-name" style="width: 400px; background-position: right;"></div>
			</div>
		</div>

		<hr>

		<div class="syscoin-initially-shown" style="flex-direction: column; justify-content: left;">
			<div class="syscoin-loading-spinner-container" style="display:flex; flex-direction: row; margin-top: 25px;">
				<div class="syscoin-loading-spinner"></div>
				<i> &nbsp; &bull; <?php esc_html_e( 'LOADING', 'syscoin' ); ?> </i>
			</div>
		</div>

		<div class="syscoin-initially-hidden">
			<div id="syscoin-diagnostics-for-logged-out-users">
				<!?php esc_html_e( 'YOU_HAVE_TO_LOG_IN_TO_USE_THE_PLUGINS_FEATURES' , 'syscoin' ); ?>
			</div>

			<div id="syscoin-diagnostics-for-logged-in-users">
				<?php
					$this->callback_overview();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Callback function for the overview page.
	 */
	public function callback_overview() {
		?>
		<div style="display: flex; justify-content: space-between; padding: 10px;">
			<div></div>

			<div>
				<table class="widefat" style="border-radius: 10px; padding: 10px; margin-bottom: 20px;">
					<tbody>
						<tr>
							<td style="padding-bottom: 0 !important;">
								<canvas id="logsCountLineChart" height="85px;" style="margin-bottom: 0;"></canvas>
							</td>
						</tr>
						
						<tr>
							<td style="display: flex; justify-content: center; padding-top: 0;">
								<!-- <b> <!?php esc_html_e( 'SELECT_SEVERITY_TO_FILTER', 'syscoin' ); ?> </b> -->
							</td>
						</tr>
					</tbody>
				</table>
			
				<table class="widefat" style="border-radius: 10px;" id="syscoin-logs-table">
					<tbody>
					</tbody>
				</table>

				<p id="syscoin-logs-count" style="text-align: center;">
					<?php esc_html_e( 'SHOWING', 'syscoin' ); ?> <b>0</b> <?php esc_html_e( 'LOGS_LC', 'syscoin' ); ?>
				</p>

				<div style="display: flex; justify-content: center; align-items: center; margin-top: 20px; height: 30px;">
					<div style="flex: 1;">

					</div>

					<div style="display: flex;">
						<button class="button syscoin-button" id="syscoin-logs-load">
							<?php esc_html_e( 'LOAD_MORE', 'syscoin' ); ?>
						</button>
					</div>

					<div style="flex: 1; display: flex; justify-content: left;">
						<div class="syscoin-loading-spinner-container" id="syscoin-logs-spinner-fetching" style="margin-left: 10px;">
							<div class="syscoin-loading-spinner"></div>
							<i id="syscoin-reports-saving"> &nbsp; &bull; <?php esc_html_e( 'LOADING', 'syscoin' ); ?> </i>
						</div>
					</div>
				</div>
			</div>

			<div></div>
		</div>
		<?php
	}

	/**
	 * Gets logs.
	 *
	 * @param number $count How many lines to get.
	 * @param number $until The date to start from. In timestamp format ``.
	 * @param array  $filters An array with the types to filter off in counting. They will be sent anyway, but the will count towards limit.
	 */
	public function get_logs( $count, $until, $filters ) {
		$hard_limit = 1000;

		$log_file = ini_get( 'error_log' );

		if ( ! file_exists( $log_file ) ) {
			return array( 'error' => 'Error log file not found.' );
		}

		$logs = file( $log_file );

		if ( false === $logs ) {
			return array( 'error' => 'Error log file not accessible.' );
		}

		$logs = array_reverse( $logs );

		// Define possible log date formats (Apache, NGINX, etc.).
		$date_formats = array(
			'apache' => '/\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} (?:[A-Z]{3}))\]/', // Apache: [26-Sep-2024 20:09:23 UTC].
			'nginx'  => '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',                   // NGINX: 2024-09-26 20:09:23.
		);

		$errors = array();

		$target_type_count = 0;

		foreach ( $logs as $temp_log_line ) {
			$temp_log_time = null;

			// Try to match Apache or NGINX date formats.
			foreach ( $date_formats as $date_format => $regex_pattern ) {
				$temp_matched = preg_match( $regex_pattern, $temp_log_line, $date_matches );

				if ( $temp_matched ) {
					if ( 'apache' === $date_format ) {
						$temp_date_string = str_replace( ' UTC', '', $date_matches[1] );
					} elseif ( 'nginx' === $date_format ) {
						$temp_date_string = $date_matches[1];
					}

					$temp_log_time = strtotime( $temp_date_string );

					break;
				}
			}

			if ( $temp_log_time && $temp_log_time <= $until ) {
				if ( preg_match( '/PHP Fatal error:/', $temp_log_line ) ) {
					$error_type = 'fatal';
				} elseif ( preg_match( '/PHP Warning:/', $temp_log_line ) ) {
					$error_type = 'warning';
					continue;
				} elseif ( preg_match( '/PHP Notice:/', $temp_log_line ) ) {
					$error_type = 'notice';
					continue;
				} else {
					$error_type = 'other';
					continue; // TODO: handle log entries of other types.
				}

				$summarized = preg_replace( '/\[[^\]]*\]/', '', $temp_log_line );

				$summarized = preg_replace( '/PHP (Fatal error|Warning|Notice):/', '', $summarized );

				$summarized = trim( $summarized );

				$errors[] = array(
					'time'       => $temp_log_time,
					'type'       => $error_type,
					'summarized' => $summarized,
					'original'   => $temp_log_line,
				);

				if ( ! in_array( $error_type, $filters, true ) ) {
					++$target_type_count;
				}

				if ( $target_type_count >= $count || count( $errors ) >= $hard_limit ) {
					break;
				}
			}
		}

		return $errors;
	}

	/**
	 * Handles ajax requests for logs.
	 */
	public function handle_get_logs() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( ! isset( $_POST['until'] ) || ! isset( $_POST['count'] ) || ! isset( $_POST['filters'] ) ) {
			wp_send_json(
				array(
					'success' => false,
				)
			);

			wp_die();

			return;
		}

		$until   = sanitize_text_field( wp_unslash( $_POST['until'] ) );
		$count   = sanitize_text_field( wp_unslash( $_POST['count'] ) );
		$filters = sanitize_text_field( wp_unslash( $_POST['filters'] ) );

		wp_send_json(
			array(
				'success' => true,
				'logs'    => $this->get_logs(
					intval( $count ),
					$until,
					json_decode( $filters, true )
				),
			)
		);

		wp_die();
	}
}

global $syscoin_logs_viewer;

$syscoin_logs_viewer = new Syscoin_Logs_Viewer();
