<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress
 * It includes the SettingsPage class which provides functions related to the settings page of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../../options/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . '../../utils/class-utils.php';

/**
 * This class represents the settings page for the plugin.
 */
class Syscoin_Settings_Page {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_login', array( $this, 'handle_login' ) );
		add_action( 'wp_ajax_logout', array( $this, 'handle_logout' ) );
		add_action( 'wp_ajax_saveOpenAiKey', array( $this, 'handle_save_openai_key' ) );
		add_action( 'wp_ajax_removeOpenAiKey', array( $this, 'handle_remove_openai_key' ) );
		add_action( 'wp_ajax_updateFooterSettings', array( $this, 'handle_update_footer_settings' ) );
	}

	/**
	 * Enqueues the necessary scripts for the settings page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $syscoin_env;

		if ( $syscoin_env['AGENCY'] . '_page_syscoin-settings' !== $hook ) {
			return;
		}

		global $syscoin_settings;
		global $syscoin_utils;

		$ver = $syscoin_utils->get_plugin_version();

		wp_enqueue_script( 'syscoin-settings-page-js', plugin_dir_url( __FILE__ ) . 'settings-page.js', array( 'jquery', 'syscoin-utils-js' ), $ver, true );

		wp_localize_script(
			'syscoin-settings-page-js',
			'script_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'syscoin_nonce' ),
				'settings' => $syscoin_settings->get_settings(),
			)
		);
	}

	/**
	 * Callback function for the settings page. Renders the HTML content for the page.
	 */
	public function settings_callback() {
		global $syscoin_settings;
		global $syscoin_env;
		global $syscoin_utils;

		$user    = $syscoin_settings->get_user();
		$version = $syscoin_utils->get_plugin_version();

		?>
		<div class="syscoin-page-header" style="justify-content: start; align-items: end;">
			<div class="syscoin-name" style="width: 225px;" title="<?php echo esc_html( $syscoin_utils->get_info_string() ); ?>"></div>
			<div style="font-size: 12px;"> <?php echo esc_html( $version ); ?> </div>
		</div>

		<h1> <?php esc_html_e( 'PLUGIN_SETTINGS', 'syscoin' ); ?> </h1>

		<div class="syscoin-initially-shown">
			<div class="syscoin-loading-spinner-container">
				<div class="syscoin-loading-spinner"></div>
			</div>
		</div>

		<div class="syscoin-initially-hidden">
			<div id="syscoin-settings-for-logged-in-users">
				<div id="syscoin-custom-openai-token">
					<hr class="syscoin-settings-separator">

					<div class="syscoin-settings-section-title">
						<h3 style="display: inline;"> 
							<?php esc_html_e( 'PRODUCT_DESCRIPTION_GENERATOR', 'syscoin' ); ?> 
						</h3>
						&bull;
						<i> 
							<?php esc_html_e( 'PRODUCT_DESCRIPTION_GENERATOR_DESC', 'syscoin' ); ?> 
						</i>
					</div>

					<div id="syscoin-custom-openai-token-for-not-saved">
						<p> 
							<?php esc_html_e( 'ENTER_CUSTOM_OPENAI_TOKEN_HERE_MANDATORY', 'syscoin' ); ?>
							
							<a href="https://platform.openai.com/api-keys" target="_blank">
								<?php esc_html_e( 'FIND_OPENAI_KEY_HERE', 'syscoin' ); ?>
							</a>
						</p>
						
						<div style="display: flex;">
							<input style="width: 500px" type="text" id="syscoin-input-custom-openai-token" placeholder=" <?php esc_html_e( 'YOUR_OPENAI_TOKEN', 'syscoin' ); ?> "></input>
							<button style="margin-left: 10px;" class="button syscoin-button" id="syscoin-save-custom-openai-token"> <?php esc_html_e( 'SAVE', 'syscoin' ); ?> </button>
							<div class="syscoin-loading-spinner-container" id="syscoin-save-openai-key-spinner-save">
								<div class="syscoin-loading-spinner"></div>
								<i> &nbsp; &bull; <?php esc_html_e( 'SAVING_AND_VALIDATING_KEY', 'syscoin' ); ?> </i>
							</div>
						</div>
					</div>

					<div id="syscoin-custom-openai-token-for-saved">
						<p> <?php esc_html_e( 'YOUR_SAVED_KEY_IS', 'syscoin' ); ?> <?php echo '"' . esc_html( $user['openai_key_preview'] ?? '-' ) . '"'; ?> </p>

						<div style="display: flex;">
							<button class="button syscoin-button" id="syscoin-remove-custom-openai-token"> <?php esc_html_e( 'REMOVE_OPENAI_KEY', 'syscoin' ); ?> </button>
							<div class="syscoin-loading-spinner-container" id="syscoin-save-openai-key-spinner-remove">
								<div class="syscoin-loading-spinner"></div>
								<i> &nbsp; &bull; <?php esc_html_e( 'REMOVING_KEY', 'syscoin' ); ?> </i>
							</div>
						</div>
					</div>
				</div>

				<div id="syscoin-footer">
					<hr class="syscoin-settings-separator">

					<div class="syscoin-settings-section-title">
						<h3 style="display: inline;"> <?php esc_html_e( 'OTHER_SETTINGS', 'syscoin' ); ?> </h3>
						&bull;
						<i> <?php esc_html_e( 'OTHER_SETTINGS_DESC', 'syscoin' ); ?> </i>
					</div>

					<div style="display: flex; align-items: center;">
						<p style="margin-bottom: 0;">
							<input type="checkbox" id="syscoin-footer-enable" name="syscoin-footer-enable">
							<label for="syscoin-footer-enable"> <?php esc_html_e( 'ENABLE_AGENCY_FOOTER', 'syscoin' ); ?> </label>
						</p>
						<div class="syscoin-loading-spinner-container" id="syscoin-misc-footer-spinner">
							<div class="syscoin-loading-spinner"></div>
						</div>
					</div>
				</div>

				<div id="syscoin-logout-form">
					<hr class="syscoin-settings-separator">
					
					<div class="syscoin-settings-section-title">
						<h3 style="display: inline;"> <?php esc_html_e( 'APP_INTEGRATION', 'syscoin' ); ?> </h3>
						&bull;
						<i> <?php esc_html_e( 'APP_INTEGRATION_DESC', 'syscoin' ); ?> </i>
					</div>

					<p>
						<?php esc_html_e( 'APP_INTEGRATION_LONG', 'syscoin' ); ?>
					</p>

					<div style="display: flex; align-items: center;">
						<span> 
							&bull;
							<?php esc_html_e( 'YOU_ARE_LOGGED_IN_AS', 'syscoin' ); ?> 
							<b><?php echo esc_html( $user['username'] ?? '-' ); ?></b>.
							
							<?php
							if ( 'syscoin' === $syscoin_env['AGENCY'] ) {
								?>
								<span id="syscoin-message-for-premium">
									<?php esc_html_e( 'THIS_IS_A_PREMIUM_ACCOUNT', 'syscoin' ); ?> 
								</span>
								
								<span id="syscoin-message-for-non-premium">
									<?php esc_html_e( 'THIS_IS_NOT_A_PREMIUM_ACCOUNT', 'syscoin' ); ?> <a href="https://dashboard.syscoin.com.br"> <?php esc_html_e( 'UPGRADE_NOW', 'syscoin' ); ?> </a>
								</span>
								<?php
							}
							?>
						</span>

						<span style="margin-left: 10px; display: flex; flex-direction: row;">
							<button class="button syscoin-button" id="syscoin-logout-button"> <?php esc_html_e( 'LOG_OUT', 'syscoin' ); ?> </button>
							<div class="syscoin-loading-spinner-container" id="syscoin-logout-spinner">
								<div class="syscoin-loading-spinner"></div>
								<i> &nbsp; &bull; <?php esc_html_e( 'LOGGING_OUT', 'syscoin' ); ?> </i>
							</div>
						</span>
					</div>
				</div>
			</div>

			<div id="syscoin-settings-for-logged-out-users">
				<hr class="syscoin-settings-separator">

				<div id="syscoin-login-form">
					<div class="syscoin-settings-section-title">
						<h3 style="display: inline;"> <?php esc_html_e( 'APP_INTEGRATION', 'syscoin' ); ?> </h3>
						&bull;
						<i> <?php esc_html_e( 'APP_INTEGRATION_DESC', 'syscoin' ); ?> </i>
					</div>

					<p>
						<?php esc_html_e( 'APP_INTEGRATION_LONG', 'syscoin' ); ?>
					</p>

					<p> 
						<?php esc_html_e( 'LOG_IN_LONG', 'syscoin' ); ?> 

						<a target="_blank" href="<?php echo esc_html( $syscoin_env['URL_CREATE_ACCOUNT'] ); ?>">
							<?php esc_html_e( 'SIGN_UP_APP_NOW', 'syscoin' ); ?>
						</a>
					</p>

					<div style="display: flex;">
						<input 
							style="margin-right: 10px;"
							id="syscoin-login-username"
							type="text"
							name="login_settings[username]"
							placeholder="<?php esc_html_e( 'EMAIL', 'syscoin' ); ?>"
						/>

						<input 
							style="margin-right: 10px;"
							id="syscoin-login-password"
							type="password"
							name="login_settings[password]"
							placeholder="<?php esc_html_e( 'PASSWORD', 'syscoin' ); ?>"
						/>

						<button style="margin-right: 10px;" class="button syscoin-button" id="syscoin-login-button">
							<?php esc_html_e( 'LOG_IN', 'syscoin' ); ?>
						</button>

						<div class="syscoin-loading-spinner-container" id="syscoin-login-spinner">
							<div class="syscoin-loading-spinner"></div>
							<i> &nbsp; &bull; <?php esc_html_e( 'LOGGING_IN', 'syscoin' ); ?> </i>
						</div>
					</div>

					<p>
						<a href="<?php echo esc_html( $syscoin_env['URL_FORGOT_PASSWORD'] ); ?>"> 
							<?php esc_html_e( 'FORGOT_MY_PASSWORD', 'syscoin' ); ?> 
						</a>
					</p>
				</div>
			</div>

			<div>
				<p> 
					<?php esc_html_e( 'GET_APP_LONG', 'syscoin' ); ?>
					<a target="_blank" href="https://dashboard.syscoin.com.br"> <?php esc_html_e( 'OPEN_APP_WEB_VERSION', 'syscoin' ); ?> </a>
				</p>

				<div style="display: flex; flex-direction: row; align-items: center;">
					<a href="<?php echo esc_html( $syscoin_env['URL_APP_APP_STORE'] ); ?>">
						<div class="syscoin-badge-app-store" style="margin-left: 0;"></div>
					</a>

					<a href="<?php echo esc_html( $syscoin_env['URL_APP_GOOGLE_PLAY'] ); ?>">
						<div class="syscoin-badge-play-store"></div>
					</a>
				</div>
			</div>

			<hr class="syscoin-settings-separator" style="margin-bottom: 0;">
		</div>
		<?php
	}

	/**
	 * Handles requests for login.
	 */
	public function handle_login() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( isset( $_POST['username'] ) && ! empty( $_POST['username'] ) ) {
			$username = sanitize_text_field( wp_unslash( $_POST['username'] ) );
		}

		if ( isset( $_POST['password'] ) && ! empty( $_POST['password'] ) ) {
			$password = sanitize_text_field( wp_unslash( $_POST['password'] ) );
		}

		if ( ! $username || empty( $username ) || ! $password || empty( $password ) ) {
			wp_send_json( array( 'success' => false ) );
			wp_die();
		}

		global $syscoin_account;

		$result = $syscoin_account->login_user( $username, $password );

		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Handles requests for logout.
	 */
	public function handle_logout() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		global $syscoin_account;

		$syscoin_account->logout();

		wp_send_json( array( 'success' => true ) );
		wp_die();
	}

	/**
	 * Handles requests to save a custom OpenAI key.
	 */
	public function handle_save_openai_key() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( isset( $_POST['key'] ) && ! empty( $_POST['key'] ) ) {
			$openai_key = sanitize_text_field( wp_unslash( $_POST['key'] ) );
		}

		if ( ! $openai_key || empty( $openai_key ) ) {
			wp_send_json( array( 'success' => false ) );
			wp_die();
		}

		global $syscoin_account;

		$result = $syscoin_account->test_and_save_openai_key( $openai_key );

		if ( 'TEST_FAILED' === $result['message'] ) {
			$result['message'] = esc_html__( 'INVALID_OPENAI_KEY', 'syscoin' );
		}

		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Handles requests for the  removal of the custom OpenAI key.
	 */
	public function handle_remove_openai_key() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		global $syscoin_account;

		$result = $syscoin_account->remove_openai_key();

		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Handles requests for updating agency footer requests.
	 */
	public function handle_update_footer_settings() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		if ( isset( $_POST['enable'] ) ) {
			$enable = filter_var( wp_unslash( $_POST['enable'] ), FILTER_VALIDATE_BOOLEAN );
		}

		global $syscoin_agency_footer;

		$syscoin_agency_footer->set_flag( $enable );

		wp_send_json(
			array(
				'success' => true,
				'state'   => $syscoin_agency_footer->get_flag(),
			)
		);

		wp_die();
	}
}

global $syscoin_settings_page;

$syscoin_settings_page = new Syscoin_Settings_Page();
