<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Syscoin_Settings class which provides functions related to the
 * WordPress option that contains information about the current user.
 *
 * @package syscoin
 */

/**
 * This class represents the current user and provides methods to set, reset, and retrieve user information.
 */
class Syscoin_Settings {
	/**
	 * The name of the option used to store user information.
	 *
	 * @var string
	 */
	private $option_name = 'syscoin_settings';

	/**
	 * Sets the user information in the options.
	 *
	 * @param string $username The username of the user.
	 * @param string $token The token associated with the user.
	 * @param bool   $premium Whether the user has a premium account or not.
	 * @param string $openai_key_preview Masked user's OpenAI key.
	 * @return void
	 */
	public function log_in( $username, $token, $premium, $openai_key_preview ) {
		global $syscoin_utils;

		$user = array(
			'logged_in'          => true,
			'username'           => $username,
			'token'              => $token,
			'premium'            => $premium,
			'last_updated'       => new DateTime(),
			'openai_key_preview' => $openai_key_preview,
			'environment'        => $syscoin_utils->get_env(),
		);

		$this->update_single_entry( 'user', $user );
	}

	/**
	 * Logs the user out.
	 *
	 * @param string $reason The reason for logout.
	 * @return void
	 */
	public function log_out( $reason ) {
		$user = array(
			'logged_in'          => false,
			'username'           => null,
			'token'              => null,
			'premium'            => false,
			'last_updated'       => new DateTime(),
			'openai_key_preview' => null,
			'logout_reason'      => $reason,
			'environment'        => null,
		);

		$this->update_single_entry( 'user', $user );
	}

	/**
	 * Retrieves the plugin's settings.
	 */
	public function get_settings() {
		return get_option( $this->option_name );
	}

	/**
	 * Retrieves the user information stored in the plugin's option.
	 */
	public function get_user() {
		// TODO: move most of this function over to the Account class. This function should be responsible for the option only.
		$settings = $this->get_settings();

		if ( isset( $settings['user'] ) && ! empty( $settings['user'] ) ) {
			$user = $settings['user'];

			if ( isset( $user['environment'] ) && ! empty( $user['environment'] ) ) {
				global $syscoin_utils;
				$env = $syscoin_utils->get_env();

				if ( $user['environment'] !== $env ) {
					$this->log_out( 'env_changed' );

					$settings = $this->get_settings();
				}
			}

			$user = $settings['user'];

			if ( ! $user['logged_in'] ) {
				global $syscoin_account;// TODO: improve - I don't like referencing that in here.
				$syscoin_account->login_anon();
			}

			return $settings['user'];
		} else {
			return null;
		}
	}

	/**
	 * Updates the timestamp of the user information. This should be used to indicate that the user information is still valid.
	 */
	public function update_timestamp() {
		$user = $this->get_user();

		$user['last_updated'] = new DateTime();

		$this->update_single_entry( 'user', $user );
	}

	/**
	 * Updates the user's OpenAI key preview.
	 *
	 * @param string $key The key. Should be masked.
	 */
	public function update_openai_key_preview( $key ) {
		$user = $this->get_user();

		$user['openai_key_preview'] = $key;

		$this->update_single_entry( 'user', $user );
	}

	/**
	 * Updates the user's report settings.
	 *
	 * @param boolean $enabled True or false.
	 * @param string  $frequency Frequency to send reports in.
	 * @param string  $time Time to send reports.
	 * @param string  $mobile Mobile number to send to.
	 * @param string  $weekday The weekday in the case of a 'weekly' frequency.
	 */
	public function update_report_settings( $enabled, $frequency, $time, $mobile, $weekday ) {
		$report_settings = array(
			'enabled'   => $enabled,
			'frequency' => $frequency,
			'time'      => $time,
			'weekday'   => $weekday,
			'mobile'    => $mobile,
		);

		$this->update_single_entry( 'report_settings', $report_settings );
	}

	/**
	 * Updates or adds a single entry leaving the rest intact.
	 *
	 * @param string $entry_name The name of the entry to be updated or added.
	 * @param mixed  $value The value to be written.
	 */
	public function update_single_entry( $entry_name, $value ) {
		$current_info = $this->get_settings();

		$current_info[ $entry_name ] = $value;

		update_option( $this->option_name, $current_info );
	}
}

global $syscoin_settings;

$syscoin_settings = new Syscoin_Settings();
