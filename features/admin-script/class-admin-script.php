<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Syscoin_Admin_Script class which provides functions related to the admin script of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../../options/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . '../../utils/class-utils.php';

/**
 * This class handles admin script-related functionality.
 */
class Syscoin_Admin_Script {
	/**
	 * The status of the token.
	 *
	 * @var string
	 */
	public $token_status;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_settings;

		$current_user = $syscoin_settings->get_user();

		if ( ! $current_user || ! isset( $current_user['logged_in'] ) || ! $current_user['logged_in'] || ! isset( $current_user['token'] ) || ! $current_user['token'] ) {
			$this->token_status = 'NO_TOKEN';
		} elseif ( $current_user['logged_in'] && $current_user['token'] ) {
			$this->token_status = 'VALID_NOT_EXPIRED';
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );
	}

	/**
	 * Enqueues the admin script for the plugin.
	 */
	public function enqueue_script() {
		global $syscoin_settings;
		global $syscoin_utils;

		$ver = $syscoin_utils->get_plugin_version();

		wp_enqueue_script( 'syscoin-admin-script', plugin_dir_url( __FILE__ ) . 'admin-script.js', array( 'jquery' ), $ver, true );

		$current_user = $syscoin_settings->get_user();

		$token_status_timestamp = isset( $current_user['last_updated'] ) ? $current_user['last_updated'] : null;

		wp_localize_script(
			'syscoin-admin-script',
			'script_vars',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'syscoin_nonce' ),
				'tokenStatus'          => $this->token_status,
				'tokenStatusTimestamp' => $token_status_timestamp,
				'pluginVersion'        => $ver,
			)
		);
	}

	/**
	 * Check the validity of the token.
	 *
	 * @param array $current_user The current user information.
	 */
	public function check_token( $current_user ) {
		global $syscoin_settings;
		global $syscoin_backend;

		try {
			$result = $syscoin_backend->check_token( $current_user['token'] );
		} catch ( Exception $e ) {
			return array(
				'valid'   => null,
				'premium' => null,
			);
		}

		$body = $result['body'];

		if ( ! $body || ! array_key_exists( 'valid', $body ) || ! array_key_exists( 'premium', $body ) ) {
			return array(
				'valid'   => null,
				'premium' => null,
			);
		}

		if ( isset( $body['openaiKeyPreview'] ) ) {
			$syscoin_settings->update_openai_key_preview( $body['openaiKeyPreview'] );
		}

		return array(
			'valid'   => $body['valid'],
			'premium' => $body['premium'],
		);
	}
}

new Syscoin_Admin_Script();
