<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Diagnostics class which provides functions related to the diagnostics features of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . 'class-diagnostics-values.php';
require_once plugin_dir_path( __FILE__ ) . '../../tables/class-table-diagnostics.php';
require_once plugin_dir_path( __FILE__ ) . './class-diagnostics-topics.php';

/**
 * This class handles diagnostics-related functionality.
 */
class Syscoin_Diagnostics {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_utils;
		$this->utils = $syscoin_utils;

		global $syscoin_backend;
		$this->backend = $syscoin_backend;

		global $syscoin_diagnostics_values;
		$this->values = $syscoin_diagnostics_values;

		global $syscoin_table_diagnostics;
		$this->db = $syscoin_table_diagnostics;

		$this->topics = new Syscoin_Diagnostics_Topics();

		add_action( 'admin_init', array( $this->db, 'create_table' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_diagnose', array( $this, 'handle_diagnose' ) );
		add_action( 'wp_ajax_setSchedule', array( $this, 'handle_toggle_schedule' ) );
		add_action( $this->hook_name, array( $this, 'diagnose' ) );
	}

	/**
	 * Reference to global variable $syscoin_utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Reference to global variable $syscoin_backend.
	 *
	 * @var Syscoin_Backend
	 */
	private $backend;

	/**
	 * Reference to global variable $syscoin_diagnostics_values.
	 *
	 * @var Syscoin_Diagnostics_Values
	 */
	private $values;

	/**
	 * Reference to global variable $syscoin_table_diagnostics.
	 *
	 * @var Syscoin_Table_Diagnostics
	 */
	private $db;

	/**
	 * Instance of Syscoin_Diagnostics_Topics
	 *
	 * @var Syscoin_Diagnostics_Topics
	 */
	private $topics;

	/**
	 * Hook name used for cron scheduling.
	 *
	 * @var string;
	 */
	private $hook_name = 'syscoin_diagnose_scheduled';

	/**
	 * Caregories of overall scores.
	 *
	 * @var array
	 */
	private $score_categories = array(
		'ok'       => 80,
		'warning'  => 50,
		'critical' => 0,
	);

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		global $syscoin_env;

		if ( $syscoin_env['AGENCY'] . '_page_syscoin-diagnostics' !== $hook ) {
			return;
		}

		$ver = $this->utils->get_plugin_version();

		wp_enqueue_script( 'chartjs', plugin_dir_url( __FILE__ ) . '../../assets/js/chart.umd.js', array(), '4.4.4', true );
		wp_enqueue_script( 'luxon', plugin_dir_url( __FILE__ ) . '../../assets/js/luxon.min.js', array(), '3.5.0', true );
		wp_enqueue_script( 'luxon-adapter', plugin_dir_url( __FILE__ ) . '../../assets/js/chartjs-adapter-luxon.umd.js', array(), '1.3.1', true );

		wp_enqueue_script( 'syscoin-diagnostics-js', plugin_dir_url( __FILE__ ) . 'diagnostics.js', array( 'jquery', 'syscoin-utils-js' ), $ver, true );

		global $syscoin_settings;

		$user = $syscoin_settings->get_user();

		$history = $this->get_score_history();

		wp_localize_script(
			'syscoin-diagnostics-js',
			'script_vars',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'syscoin_nonce' ),
				'logged_in'   => isset( $user ) && $user['logged_in'],
				'is_customer' => false, // TODO: implement customer verification.
				'history'     => $history,
				'scheduled'   => $this->cron_schedule(),
			)
		);
	}

	/**
	 * Callback function for the subpage.
	 */
	public function diagnostics_page_callback() {
		if ( isset( $_REQUEST['id'] )) {/* phpcs:ignore */
			$id = sanitize_text_field( $_REQUEST['id'] ); /* phpcs:ignore */
		} else {
			$id = null;
		}

		?>
		<div class="syscoin-page-header">
			<div>
				<h1>
					<?php esc_html_e( 'DIAGNOSTICS', 'syscoin' ); ?>
				</h1>

				<p>
					<?php esc_html_e( 'DIAGNOSTICS_DESCRIPTION', 'syscoin' ); ?>
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
				if ( null === $id ) {
					$this->callback_overview();
				} else {
					$this->callback_report( $id );
				}
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
		<div style="display: flex; justify-content: center; align-items: center;">
			<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; min-width: 650px; max-width: 900px; width: 50%;">
				<div style="width: 100%; padding: 10px; display: flex; flex-direction: column; align-items: center;">
					<table class="widefat" style="border-radius: 10px;">
						<tr>
							<td class="syscoin-background-tr" style="display: flex; justify-content: space-between; border-radius: 10px; padding: 0;">
								<div style="height: 140px; display: flex; flex-grow: 1; flex-basis: 0; padding: 20px;">
									<?php
									$diagnosis_list = $this->db->get_diagnosis_list( 1 );

									if ( isset( $diagnosis_list[0] ) ) {
										$last_diagnosis = $diagnosis_list[0];

										$score        = $last_diagnosis['score'];
										$score_string = $this->get_score_string( $score );
										?>
											<div style="width: 100px; display: flex; flex-direction: column; justify-content: space-between; align-items: center;">
												<div style="font-size: 12px; white-space: nowrap;">
													<?php esc_html_e( 'DIAG_LAST_REPORT', 'syscoin' ); ?>
												</div>

												<div class="syscoin-bar">
													<div class="syscoin-bar-filled <?php echo esc_html( $this->get_css_color( $score ) ); ?>" style="width: <?php echo esc_html( $score . '%' ); ?>;"></div>
												</div>

												<div class="syscoin-rounded" style="height: 50px;">
													<div style="font-size: 40px; font-weight: 600;">
														<?php echo esc_html( $score ); ?> 
													</div>

													<div style="height: 100%; display: flex; flex-direction: row; align-items: end;">
														<div style="font-size: 14px;">
															/100
														</div>
													</div>
												</div>

												<div class="syscoin-rounded <?php echo esc_html( $this->get_css_color( $score ) ); ?>" style="font-weight: 600;">
													<?php echo esc_html( strtoupper( $score_string ) ); ?>
												</div>
											</div>
									<?php } ?>
								</div>

								<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; flex: 0 0 auto; padding-top: 20px;">
									<div>
										<?php esc_html_e( 'DIAGNOSE_NOW_DESC', 'syscoin' ); ?> 
									</div>

									<button class="button syscoin-button" id="syscoin-diagnostics-request-diagnosis" style="margin: 10px 0;"> 
										<?php esc_html_e( 'DIAGNOSE_NOW', 'syscoin' ); ?> 
									</button>

									<div>
										<input type="checkbox" id="syscoin-diagnostics-schedule" name="syscoin-diagnostics-schedule" style="margin: 5px;"></input>
										<label for="syscoin-diagnostics-schedule" style="white-space: nowrap;"> <?php esc_html_e( 'DIAG_PERFORM_WEEKLY', 'syscoin' ); ?> </label>
									</div>

									<div style="height: 20px;">
										<div class="syscoin-loading-spinner-container" id="syscoin-diagnostics-spinner-diagnosing">
											<div class="syscoin-loading-spinner"></div>
											<i> &nbsp; &bull; <?php esc_html_e( 'DIAGNOSING', 'syscoin' ); ?> </i>
										</div>

										<div class="syscoin-loading-spinner-container" id="syscoin-diagnostics-spinner-saving">
											<div class="syscoin-loading-spinner"></div>
											<i> &nbsp; &bull; <?php esc_html_e( 'SAVING', 'syscoin' ); ?> </i>
										</div>
									</div>
								</div>

								<div style="display: flex; justify-content: flex-end; align-items: flex-end; flex-grow: 1; flex-basis: 0; padding: 20px;">
								</div>
							</td>
						</tr>
					</table>
				</div>

				<?php $this->echo_history(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Echoes the score history div.
	 */
	public function echo_history() {
		$diagnosis_list = $this->db->get_diagnosis_list( 10 );

		if ( empty( $diagnosis_list ) || ! is_array( $diagnosis_list ) ) {
			?>
			<p> <?php esc_html_e( 'NO_DIAGNOSTICS_HISTORY', 'syscoin' ); ?> </p> 
			<?php

			return;
		}

		?>
		<h2>
			<?php esc_html_e( 'DIAG_HISTORY', 'syscoin' ); ?>
		</h2>

		<?php if ( count( $diagnosis_list ) > 1 ) { ?>
			<div style="width: 100%; padding: 10px; display: flex; flex-direction: column; align-items: center;">
				<table class="widefat" style="border-radius: 10px;">
					<tr>
						<td>
							<canvas id="scoreHistoryLineChart" height="85px;"></canvas>
						</td>
					</tr>
				</table>
			</div>
		<?php } ?>


		<div style="width: 100%; padding: 10px; display: flex; flex-direction: column; align-items: center;">
			<table class="widefat" style="border-radius: 10px;">
				<tbody>
					<?php foreach ( $diagnosis_list as $row ) : ?>
						<tr id="syscoin-diagnostics-list-id-<?php echo esc_html( $row['id'] ); ?>">
							<td style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding: 15px;">
								<div id="syscoin-diagnostics-list-title-id-<?php echo esc_html( $row['id'] ); ?>">
									<?php echo esc_html( $row['id'] ); ?>
								</div>

								<div style="display: flex; justify-content: space-between; align-items: center;">
									<div class="syscoin-rounded" style="height: 20px; width: fit-content; font-weight: 600;">
										<?php echo esc_html( strtoupper( $this->get_score_string( $row['score'] ) ) ); ?>
									</div>

									<div style="width: 150px; margin: 0 10px;">
										<div class="syscoin-bar" style="height: 20px;">
											<div class="syscoin-bar-filled <?php echo esc_html( $this->get_css_color( $row['score'] ) ); ?>" style="width: <?php echo esc_html( $row['score'] . '%' ); ?>;">
												<?php echo ( $row['score'] >= 20 ) ? esc_html( $row['score'] ) : ''; ?>
											</div>

											<div class="syscoin-bar-empty">
												<?php echo ( $row['score'] < 20 ) ? esc_html( $row['score'] ) : ''; ?>
											</div>
										</div>
									</div>
	
									<button class="button syscoin-button" id="syscoin-diagnostics-list-open-id-<?php echo esc_html( $row['id'] ); ?>" style="height: 30px;"> 
										<?php esc_html_e( 'OPEN_DIAGNOSIS', 'syscoin' ); ?> 
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php
			if ( count( $diagnosis_list ) === 10 ) {
				?>
				<div style="margin-top: 15px;">
					<?php esc_html_e( 'DIAG_LAST_10_SAVED', 'syscoin' ); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Callback function for the report page.
	 *
	 * @param int|string $id The report identificator.
	 */
	public function callback_report( $id ) {
		$diagnosis = $this->db->get_diagnosis( $id );

		$topics = $diagnosis['content'];
		$score  = $diagnosis['score'];

		$back_arrow = plugin_dir_url( __FILE__ ) . '../../assets/syscoin-arrow-back.svg';

		$score_string = $this->get_score_string( $score );

		?>
		<div class="syscoin-flex-horizontal-responsive" style="display: flex; justify-content: space-between; padding: 10px;">
			<div style="display: flex; align-items: start; justify-content: start;">
				<button class="button syscoin-button" id="syscoin-diagnostics-back" style="display: flex; align-items: center; margin: 0 20px 20px 0;"> 
					<img src="<?php echo esc_url( $back_arrow ); ?>" style="height: 17px; margin-right: 5px;">

					<div> <?php esc_html_e( 'BACK', 'syscoin' ); ?> </div>
				</button>
			</div>

			<div style="width: 1000px;">
				<div style="display: flex; justify-content: center; margin-bottom: 10px; width: 100%;">
					<table class="widefat" style="width: 100%; border-radius: 10px; border-radius: 10px; padding: 20px;">
						<tbody>
							<tr>
								<td>
									<div style="display: flex; height: 100%;">
										<div style="width: 100%; display: flex; justify-content: space-between;">
											<div style="display: flex; flex-direction: column; justify-content: space-between;">
												<h1 style="margin: 0;"> <?php esc_html_e( 'DIAGNOSIS_REPORT', 'syscoin' ); ?> </h1>
												<p id="syscoin-diagnostics-report-date"> <?php echo esc_html( __( 'DIAG_MADE_IN', 'syscoin' ) . ' -REPORT_DATE-' ); ?> </p>
												<p> <?php echo esc_html__( 'DIAG_SCORE_DESC_LONG', 'syscoin' ); ?> </p>
											</div>
											
											<div style="flex: 1;"></div>

											<div style="width: 100px; height: 120px; display: flex; flex-direction: column; justify-content: space-between; align-items: center;">
												<div class="syscoin-bar">
													<div class="syscoin-bar-filled <?php echo esc_html( $this->get_css_color( $score ) ); ?>" style="width: <?php echo esc_html( $score . '%' ); ?>;"></div>
												</div>

												<div class="syscoin-rounded" style="height: 50px;">
													<div style="font-size: 40px; font-weight: 600;">
														<?php echo esc_html( $score ); ?> 
													</div>

													<div style="height: 100%; display: flex; flex-direction: row; align-items: end;">
														<div style="font-size: 14px;">
															/100
														</div>
													</div>
												</div>

												<div class="syscoin-rounded <?php echo esc_html( $this->get_css_color( $score ) ); ?>" style="font-weight: 600;">
													<?php echo esc_html( strtoupper( $score_string ) ); ?>
												</div>
											</div>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php	if ( false === $diagnosis['is_latest'] ) { ?>
				<div style="display: flex; flex-direction: column; justify-content: center; align-items: center;">
					<div style="font-size: 15px;">⚠️</div>
					<p> <?php echo esc_html__( 'DIAG_WARN_OLD_REPORT', 'syscoin' ); ?> </p>
				</div>
				<?php } ?>
				
				<div style="display: flex; justify-content: center;">
					<table class="widefat" style="border-radius: 10px;">
						<tbody>
							<?php $this->topics->echo_row( $topics, 'version_plugin' ); ?>
							<?php $this->topics->echo_row( $topics, 'version_php' ); ?>
							<?php $this->topics->echo_row( $topics, 'version_wp' ); ?>
							<?php $this->topics->echo_row( $topics, 'plugins_outdated' ); ?>
							<?php $this->topics->echo_row( $topics, 'plugins_inactive' ); ?>
							<?php $this->topics->echo_row( $topics, 'theme_outdated' ); ?>
							<?php $this->topics->echo_row( $topics, 'themes_inactive' ); ?>
							<?php $this->topics->echo_row( $topics, 'se_allowed' ); ?>
							<?php $this->topics->echo_row( $topics, 'using_ssl' ); ?>
							<?php $this->topics->echo_row( $topics, 'using_smtp' ); ?>
							<?php $this->topics->echo_row( $topics, 'admin_count' ); ?>
							<?php $this->topics->echo_row( $topics, 'admin_usernames' ); ?>
							<?php $this->topics->echo_row( $topics, 'caching' ); ?>
							<?php $this->topics->echo_row( $topics, 'error_logs' ); ?>
							<?php $this->topics->echo_row( $topics, 'cron' ); ?>
							<?php $this->topics->echo_row( $topics, 'fo_issues' ); ?>
							<?php $this->topics->echo_row( $topics, 'open_dirs' ); ?>
						</tbody>
					</table>
				</div>
			</div>

			<div></div>
		</div>
		<?php
	}

	/**
	 * Returns the score string for a given overall score.
	 *
	 * @param mixed $score The numeric score.
	 */
	public function get_score_string( $score ) {
		if ( $score > $this->score_categories['ok'] ) {
			return __( 'DIAG_TOPIC_OK', 'syscoin' );
		} elseif ( $score > $this->score_categories['warning'] ) {
			return __( 'DIAG_TOPIC_WARNING', 'syscoin' );
		} else {
			return __( 'DIAG_TOPIC_CRITICAL', 'syscoin' );
		}
	}

	/**
	 * Returns the CSS class for the color of a given overall score.
	 *
	 * @param mixed $score The numeric score.
	 */
	public function get_css_color( $score ) {
		if ( $score > $this->score_categories['ok'] ) {
			return 'syscoin-bg-green';
		} elseif ( $score > $this->score_categories['warning'] ) {
			return 'syscoin-bg-yellow';
		} else {
			return 'syscoin-bg-red';
		}
	}

	/**
	 * Returns an assoc with the score history.
	 */
	public function get_score_history() {
		$list = $this->db->get_diagnosis_list( 10 );

		if ( ! isset( $list ) ) {
			return array();
		}

		$history = array();

		foreach ( $list as $item ) {
			$history[] = array(
				'x' => $item['id'] * 1000,
				'y' => intval( $item['score'] ),
			);
		}

		return $history;
	}

	/**
	 * Diagnoses the website.
	 *
	 * @param string $mode `MANUAL` or `SCHEDULED`.
	 */
	public function diagnose( $mode = 'SCHEDULED' ) {
		global $syscoin_api;
		global $syscoin_settings;

		$array = $syscoin_api->get_info();
		$info  = array(
			'site_data'  => $array['info'],
			'user_data'  => $syscoin_settings->get_user(),
			'plugins'    => $this->values->get_plugins_list(),
			'themes'     => $this->values->get_themes_list(),
			'se_allowed' => $this->values->get_setting_allow_se(),
			'admins'     => $this->values->get_admin_users(),
			'using_smtp' => $this->values->get_using_smtp(),
			'using_ssl'  => $this->values->get_ssl_configured(),
			'fo_issues'  => $this->values->get_file_ownership_issues(),
			'caching'    => $this->values->get_caching_status(),
			'error_logs' => $this->values->get_recent_error_logs(),
			'cron'       => $this->values->get_cron_info(),
			'open_dirs'  => $this->values->get_open_directories(),
		);

		try {
			$response = $this->backend->get_diagnostics( $info );
			$body     = $this->utils->extract_response_body( $response );
		} catch ( Exception $e ) {
			$body = null;
		}

		if ( ! isset( $body ) || ! isset( $body['result'] ) ) {
			$json = wp_json_encode( $body, JSON_PRETTY_PRINT );
			$this->utils->log( "Could not get diagnosis from backend: $json" );

			return 'BACKEND_ERROR';
		}

		$diagnosis = (array) $body['result'];
		$topics    = $diagnosis['topics'];
		$score     = $diagnosis['score'];
		$timestamp = $diagnosis['timestamp'];

		$this->db->save_diagnosis( $timestamp, $topics, $score, $mode );

		if ( ! isset( $timestamp ) || false === $timestamp ) {
			$json = wp_json_encode( $body, JSON_PRETTY_PRINT );
			$this->utils->log( "Could not save diagnosis to database: $timestamp $json" );

			return 'DATABASE_ERROR';
		}

		$this->cron_schedule( true );

		return $timestamp;
	}

	/**
	 * Schedules or unschedules weekly scans, or returns the current status: `true` if it has a schedule, `false` if not.
	 *
	 * @param boolean $enable `true` to enable. If not provided, the function return the current status.
	 */
	public function cron_schedule( $enable = null ) {
		$timestamp = wp_next_scheduled( $this->hook_name );

		if ( null === $enable ) {
			return false !== $timestamp;
		}

		if ( $enable ) {
			if ( ! $timestamp ) {
				// Schedule the cron event.
				wp_schedule_single_event( time() + 7 * DAY_IN_SECONDS, $this->hook_name );
			}
		} elseif ( $timestamp ) {
			// Unschedule the event if it's scheduled.
			wp_unschedule_event( $timestamp, $this->hook_name );
		}

		return wp_next_scheduled( $this->hook_name );
	}

	/**
	 * Handles requests for diagnosis.
	 */
	public function handle_diagnose() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		$id = $this->diagnose( 'MANUAL' );

		if ( 'BACKEND_ERROR' === $id ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => 'Diagnosis failed. Please try again later or contact support. (backend error)',
				)
			);

			return wp_die();
		}

		if ( 'DATABASE_ERROR' === $id ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => 'Diagnosis failed. Please try again later or contact support. (database error)',
				)
			);

			return wp_die();
		}

		wp_send_json(
			array(
				'success' => true,
				'id'      => (string) $id,
			)
		);

		wp_die();
	}

	/**
	 * Handles requests to enable/disable weekly scans.
	 */
	public function handle_toggle_schedule() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		$enable = isset( $_POST['enable'] ) ? filter_var( wp_unslash( $_POST['enable'] ), FILTER_VALIDATE_BOOLEAN ) : null;

		if ( is_null( $enable ) ) {
				wp_send_json_error( 'Invalid data provided', 400 );
		}

		// Call the scheduling function to handle enabling/disabling.
		$updated_timestamp = $this->cron_schedule( $enable );

		if ( ( $enable && $updated_timestamp ) || ( ! $enable && ! $updated_timestamp ) ) {
			wp_send_json(
				array(
					'success'   => true,
					'timestamp' => $updated_timestamp,
				)
			);
		} else {
			wp_send_json(
				array(
					'success' => false,
					'message' => 'Failed to update diagnostics schedule settings. Please try again or contact support.',
				)
			);
		}

		wp_die();
	}
}

global $syscoin_diagnostics;

$syscoin_diagnostics = new Syscoin_Diagnostics();
