<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress
 * It includes the Syscoin_Reports class which provides functions related
 * to reports.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . './class-reports-generator.php';

/**
 * This class represents the reports feature of the plugin.
 */
class Syscoin_Reports {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_settings;
		$this->settings = $syscoin_settings;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;

		$this->generator = new Syscoin_Reports_Generator();

		$this->utils->ensure_schedule_definitions();

		add_action( $this->schedule_hook_prefix . '_daily', array( $this, 'send_report' ) );
		add_action( $this->schedule_hook_prefix . '_weekly', array( $this, 'send_report' ) );
		add_action( $this->schedule_hook_prefix . '_monthly', array( $this, 'send_report' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_updateReportSettings', array( $this, 'handle_update_report_settings' ) );
		add_action( 'wp_ajax_sendReportNow', array( $this, 'handle_send_report_now' ) );
	}

	/**
	 * Enqueues the necessary scripts for the settings page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $syscoin_env;

		if ( $syscoin_env['AGENCY'] . '_page_syscoin-reports' !== $hook ) {
			return;
		}

		global $syscoin_settings;
		global $syscoin_utils;

		$ver = $syscoin_utils->get_plugin_version();

		wp_enqueue_script( 'syscoin-reports-page-js', plugin_dir_url( __FILE__ ) . 'script-reports.js', array( 'jquery', 'syscoin-utils-js' ), $ver, true );

		wp_localize_script(
			'syscoin-reports-page-js',
			'script_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'syscoin_nonce' ),
				'settings' => $syscoin_settings->get_settings(),
			)
		);
	}

	/**
	 * The hook name for the job.
	 *
	 * @var string
	 */
	private $schedule_hook_prefix = 'syscoin_report_send';

	/**
	 * The key in the plugin's settings option for the report settings.
	 *
	 * @var string;
	 */
	private $settings_key = 'report_settings';

	/**
	 * Reference to global instance of Syscoin_Settings.
	 *
	 * @var Syscoin_Settings
	 */
	private $settings;

	/**
	 * Reference to global instance of Syscoin_Utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Instance of Syscoin_Reports_Generator
	 *
	 * @var Syscoin_Reports_Generator
	 */
	private $generator;

	/**
	 * Callback function for the reports settings page.
	 */
	public function reports_callback() {
		global $syscoin_settings;
		global $syscoin_env;
		global $syscoin_utils;

		?>
		<div class="syscoin-page-header">
			<div>
				<h1>
					<?php echo esc_html( 'Relatórios' ); ?>
				</h1>

				<p>
					<?php echo esc_html( 'Receba um relatório da atividade desta loja periodicamente' ); ?>
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
			<div id="syscoin-reports-settings">
				<div style="display: flex; flex-direction: column;">
					<div style="display: flex; align-items: center; height: 40px;">
						<input type="checkbox" id="syscoin-reports-input-enable-daily" name="syscoin-reports-input-enable-daily">
						<label for="syscoin-reports-input-enable-daily">Receber relatórios diários</label>
					</div>

					<div style="display: flex; align-items: center; height: 40px;">
						<input type="checkbox" id="syscoin-reports-input-enable-weekly" name="syscoin-reports-input-enable-weekly">
						<label for="syscoin-reports-input-enable-weekly">Receber relatórios semanais aos/às</label>

						<select style="margin-left: 10px;" id="syscoin-reports-input-weekly-weekday" name="syscoin-reports-input-weekly-weekday">
							<option value="sunday">domingos</option>
							<option value="monday">segundas-feiras</option>
							<option value="tuesday">terças-feiras</option>
							<option value="wednesday">quartas-feiras</option>
							<option value="thursday">quintas-feiras</option>
							<option value="friday">sextas-feiras</option>
							<option value="saturday">sábados</option>
						</select>
					</div>

					<div style="display: flex; align-items: center; height: 40px;">
						<input type="checkbox" id="syscoin-reports-input-enable-monthly" name="syscoin-reports-input-enable-monthly">
						<label for="syscoin-reports-input-enable-monthly">Receber relatórios mensais</label>
					</div>

					<div>
						Horário de preferência: <input style="margin-left: 10px;" type="time" id="syscoin-reports-input-preferred-time" name="syscoin-reports-input-preferred-time" />
					</div>

					<hr class="syscoin-settings-separator">

					<div>
						<p style="margin-top: 0;">
							Você pode adicionar até três números para receber os relatórios programados ou informar uma url a ser chamada no horário programado.
						</p>

						<div>
							<label>Números de WhatsApp:</label>
							<input style="margin-right: 10px;" type="tel" id="syscoin-reports-input-mobile-1" placeholder="Adicione um número">
							<input style="margin-right: 10px;" type="tel" id="syscoin-reports-input-mobile-2" placeholder="Adicione um número">
							<input type="tel" id="syscoin-reports-input-mobile-3" placeholder="Adicione um número">
							(somente números, com código do país, DDD e dígito 9 - ex: 5561912345678)
						</div>
						
						<div style="margin-top: 13px;">
							<label>Webhook:</label>
							<input style="width: 500px" type="text" id="syscoin-reports-input-webhook" placeholder="Insira a URL do seu webhook aqui">
						</div>
					</div>

					<hr class="syscoin-settings-separator">

					<div style="display: flex; flex-direction: column;">
						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-accesses" name="syscoin-reports-input-optional-accesses">
							<label for="syscoin-reports-input-optional-accesses">Incluir quantidade de acessos</label>
						</div>

						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-sales" name="syscoin-reports-input-optional-sales">
							<label for="syscoin-reports-input-optional-sales">Incluir quantidade de vendas</label>
						</div>

						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-income" name="syscoin-reports-input-optional-income">
							<label for="syscoin-reports-input-optional-income">Incluir receita somada</label>
						</div>

						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-utm" name="syscoin-reports-input-optional-utm">
							<label for="syscoin-reports-input-optional-utm">Incluir resumo de dados UTM</label>
						</div>

						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-most-accessed-pages" name="syscoin-reports-input-optional-most-accessed-pages">
							<label for="syscoin-reports-input-optional-most-accessed-pages">Incluir lista de páginas mais acessadas</label>
						</div>

						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-most-viewed-prods" name="syscoin-reports-input-optional-most-viewed-prods">
							<label for="syscoin-reports-input-optional-most-viewed-prods">Incluir lista de produtos mais acessados</label>
						</div>

						<div>
							<input type="checkbox" id="syscoin-reports-input-optional-most-sold-prods" name="syscoin-reports-input-optional-most-sold-prods">
							<label for="syscoin-reports-input-optional-most-sold-prods">Incluir lista de produtos mais vendidos</label>
						</div>
					</div>

					<hr class="syscoin-settings-separator">

					<div style="display: flex;">
						<div>
							<button class="button syscoin-button" id="syscoin-reports-action-save"> Atualizar preferências de relatório </button>
						</div>
						
						<div style="margin-left: 10px;">
							<button class="button syscoin-button" id="syscoin-reports-action-send"> Receber um relatório agora </button>
						</div>

						<div style="margin-left: 10px;" class="syscoin-loading-spinner-container" id="syscoin-save-reports-settings-spinner">
							<div class="syscoin-loading-spinner"></div>

							<i id="syscoin-reports-saving"> &nbsp; &bull; <?php esc_html_e( 'SAVING', 'syscoin' ); ?> </i>
						</div>

						<div style="margin-left: 10px;" class="syscoin-loading-spinner-container" id="syscoin-send-reports-settings-spinner">
							<div class="syscoin-loading-spinner"></div>

							<i id="syscoin-reports-sending"> &nbsp; &bull; <?php esc_html_e( 'SENDING', 'syscoin' ); ?> </i>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles requests for updating report settings.
	 */
	public function handle_update_report_settings() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( ! isset( $_POST['json'] ) || empty( $_POST['json'] ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'settings' => $this->get_current_settings(),
					'message'  => 'Could not save report settings at this time. Please try again later.',
				)
			);

			wp_die();
		}

		$json = sanitize_text_field( wp_unslash( $_POST['json'] ) );

		$data = json_decode( $json, true );

		$result = $this->update_report_settings( $data );

		if ( true === $result ) {
			wp_send_json(
				array(
					'success'  => true,
					'settings' => $this->get_current_settings(),
				)
			);
		} else {
			wp_send_json(
				array(
					'success'  => false,
					'settings' => $this->get_current_settings(),
					'message'  => 'Could not save report settings at this time. Please try again later.',
				)
			);
		}

		wp_die();
	}

	/**
	 * Handles requests for sending a report immediately.
	 */
	public function handle_send_report_now() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		$result = $this->send_report();

		if ( true === $result ) {
			wp_send_json( array( 'success' => true ) );
		} else {
			wp_send_json(
				array(
					'success' => false,
					'message' => 'Could not generate and send report right now',
				)
			);
		}

		wp_die();
	}

	/**
	 * Updates (by overwriting) the report settings.
	 *
	 * Note: all time manipulation here must be done in UTC.
	 *
	 * @param array $settings The new settings.
	 */
	public function update_report_settings( $settings ) {
		$this->clear_reports_settings();

		$schedules = $settings['schedules'];
		$time      = $settings['time'];

		$this->update_report_schedule( 'daily', $schedules['daily']['enable'], $time );
		$this->update_report_schedule( 'weekly', $schedules['weekly']['enable'], $time, $schedules['weekly']['weekday'] );
		$this->update_report_schedule( 'monthly', $schedules['monthly']['enable'], $time );

		$this->settings->update_single_entry( $this->settings_key, $settings );

		return true;
	}

	/**
	 * Update settings for the report schedule of a given frequency.
	 *
	 * @param string  $frequency The frequency of the schedule.
	 * @param boolean $enabled Enable or disable the daily schedule.
	 * @param string  $time The time for the report to be sent at.
	 * @param string  $weekday The weekday - if frequency is weekly.
	 */
	private function update_report_schedule( $frequency, $enabled, $time, $weekday = null ) {
		$hook_name = $this->schedule_hook_prefix . '_' . $frequency;

		$next_run = wp_next_scheduled( $hook_name );

		if ( $next_run ) {
			wp_clear_scheduled_hook( $hook_name );
		}

		if ( ! $enabled ) {
			return true;
		}

		$current_time = strtotime( 'now' );

		$time_if_today = strtotime( $time );
		$next_run      = $time_if_today;

		if ( 'daily' === $frequency ) {
			if ( $time_if_today > $current_time ) {
				$next_run = $time_if_today;
			} else {
				$next_run = strtotime( '+1 day', $time_if_today );
			}
		} elseif ( 'weekly' === $frequency ) {
			$hour   = gmdate( 'H', $time_if_today );
			$minute = gmdate( 'i', $time_if_today );

			$time_string = "next $weekday $hour:$minute";

			$next_run = strtotime( $time_string, $time_if_today );
		} elseif ( 'monthly' === $frequency ) {
			$hour   = gmdate( 'H', $time_if_today );
			$minute = gmdate( 'i', $time_if_today );

			$time_string = "first day of next month $hour:$minute";

			$next_run = strtotime( $time_string, $time_if_today );
		}

		$result = wp_schedule_event( $next_run, $frequency, $hook_name );

		return $result;
	}

	/**
	 * Send the report as scheduled.
	 */
	public function send_report() {
		$report_settings = $this->get_current_settings();
		$store_name      = get_bloginfo( 'name' );

		$mobiles = $report_settings['mobiles'];
		$webhook = $report_settings['webhook'];
		$topics  = $report_settings['topics'];

		if ( isset( $mobiles['mobile1'] ) && ! empty( $mobiles['mobile1'] ) ) {
			$message = $this->generator->generate( 'daily', $store_name, $topics );

			$this->send_whatsapp_message( $mobiles['mobile1'], $message );
		}

		if ( isset( $mobiles['mobile2'] ) && ! empty( $mobiles['mobile2'] ) ) {
			$message = $this->generator->generate( 'daily', $store_name, $topics );

			$this->send_whatsapp_message( $mobiles['mobile2'], $message );
		}

		if ( isset( $mobiles['mobile3'] ) && ! empty( $mobiles['mobile3'] ) ) {
			$message = $this->generator->generate( 'daily', $store_name, $topics );

			$this->send_whatsapp_message( $mobiles['mobile3'], $message );
		}

		if ( isset( $webhook ) && ! empty( $webhook ) ) {
			$message = $this->generator->generate( 'daily', $store_name, $topics );

			$this->send_webhook_call( $webhook, $message );
		}

		return true;
	}

	/**
	 * Sends a WhatsApp message to the specified phone number.
	 *
	 * @param string $phone The recipient's phone number.
	 * @param string $message The message to be sent.
	 */
	public function send_whatsapp_message( $phone, $message ) {
		if ( null === $message ) {
			return null;
		}

		global $syscoin_backend;

		try {
			$response = $syscoin_backend->send_whatsapp_message( $phone, $message );
		} catch ( Exception $e ) {
			return false;
		}

		if ( 200 === $this->utils->extract_response_code( $response ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sends a call to a webhook.
	 *
	 * @param string $url The webhook url.
	 * @param string $message The message to be sent.
	 */
	public function send_webhook_call( $url, $message ) {
		if ( null === $message ) {
			return null;
		}

		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return null;
		}

		try {
			$response = $this->utils->http_post(
				$url,
				array(
					'message' => $message,
				),
				array(),
				array(),
				false
			);
		} catch ( Exception $e ) {
			return false;
		}

		if ( 200 === $this->utils->extract_response_code( $response ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns an array in the format expected to be found at the reports settings item.
	 */
	private function generate_settings_template() {
		return array(
			'schedules' => array(
				'daily'   => array(
					'enable' => null,
				),
				'weekly'  => array(
					'enable'  => null,
					'weekday' => null,
				),
				'monthly' => array(
					'enable' => null,
				),
			),
			'mobiles'   => array(
				'mobile1' => null,
				'mobile2' => null,
				'mobile3' => null,
			),
			'time'      => null,
			'webhook'   => null,
			'topics'    => array(),
		);
	}

	/**
	 * Clears the settings for the reports in the plugin's settings option.
	 */
	private function clear_reports_settings() {
		$default = $this->generate_settings_template();

		$default['topics'] = array(
			'access_count',
			'sales_count',
			'income',
			'most_viewed_prods',
			'most_sold_prods',
		);

		$this->settings->update_single_entry( $this->settings_key, $default );
	}

	/**
	 * Returns the current saved settings.
	 */
	public function get_current_settings() {
		$plugin_settings = $this->settings->get_settings();

		if ( isset( $plugin_settings[ $this->settings_key ] ) && ! empty( $plugin_settings[ $this->settings_key ] ) ) {
			return $plugin_settings[ $this->settings_key ];
		} else {
			return null;
		}
	}

	/**
	 * Returns the report schedules in the WP cron system.
	 */
	public function get_schedules() {
		return array(
			'daily'   => $this->utils->get_all_scheduled_instances( $this->schedule_hook_prefix . '_daily' ),
			'weekly'  => $this->utils->get_all_scheduled_instances( $this->schedule_hook_prefix . '_weekly' ),
			'monthly' => $this->utils->get_all_scheduled_instances( $this->schedule_hook_prefix . '_monthly' ),
		);
	}
}

global $syscoin_reports;

$syscoin_reports = new Syscoin_Reports();
