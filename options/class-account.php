<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Syscoin_Account class which provides functions related to user and authentication.
 *
 * @package syscoin
 */

/**
 * This class has methods related to the plugin's account and authentication functions.
 */
class Syscoin_Account {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_settings;
		$this->settings = $syscoin_settings;

		global $syscoin_backend;
		$this->backend = $syscoin_backend;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;
	}

	/**
	 * Global instance of Syscoin_Settings
	 *
	 * @var Syscoin_Settings
	 */
	private $settings;

	/**
	 * Global instance of Syscoin_Backend
	 *
	 * @var Syscoin_Backend
	 */
	private $backend;

	/**
	 * Global instance of Syscoin_Utils
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Logs a user in.
	 *
	 * @param string $username The user's email.
	 * @param string $password The user's password.
	 */
	public function login_user( $username, $password ) {
		try {
			$result = $this->backend->login_user( $username, $password );
		} catch ( Exception $e ) {
			return array(
				'success'  => false,
				'message'  => 'Could not log in at this time. Please contact support. (server exception)',
				'response' => $e,
			);
		}

		if ( 200 !== $this->utils->extract_response_code( $result ) ) {
			return array(
				'success'  => false,
				'message'  => 'Invalid username or password',
				'response' => $result,
			);
		}

		$body = $result['body'];

		if ( ! $body || ! array_key_exists( 'pluginToken', $body ) || ! array_key_exists( 'premium', $body ) ) {
			return array(
				'success'  => false,
				'message'  => 'Server response did not contain the expected fields',
				'response' => $result,
			);
		}

		$plugin_token       = $body['pluginToken'];
		$is_premium         = $body['premium'];
		$openai_key_preview = $body['openaiKeyPreview'];

		$this->settings->log_in( $username, $plugin_token, $is_premium, $openai_key_preview );

		return array(
			'success'          => true,
			'token'            => $plugin_token,
			'openAiKeyPreview' => $openai_key_preview,
		);
	}

	/**
	 * Logs in as anonymous.
	 */
	public function login_anon() {
		try {
			$result = $this->backend->login_anon();
		} catch ( Exception $e ) {
			return array(
				'success'  => false,
				'message'  => 'Could not log in at this time. Please contact support. (server exception)',
				'response' => $e,
			);
		}

		if ( 200 !== $this->utils->extract_response_code( $result ) ) {
			return array(
				'success'  => false,
				'message'  => 'Error logging in anonymously',
				'response' => $result,
			);
		}

		$body = $result['body'];

		if ( ! $body || ! array_key_exists( 'pluginToken', $body ) || ! array_key_exists( 'premium', $body ) ) {
			return array(
				'success'  => false,
				'message'  => 'Server response did not contain the expected fields',
				'response' => $result,
			);
		}

		$plugin_token = $body['pluginToken'];

		$this->settings->log_in( 'anonymous', $plugin_token, true, null );

		return array(
			'success'          => true,
			'token'            => $plugin_token,
			'openAiKeyPreview' => null,
		);
	}

	/**
	 * Logs out of the current account. Does an anonymous login right after.
	 */
	public function logout() {
		$this->settings->log_out( 'manual' );
	}

	/**
	 * Saves a custom OpenAI key in the local options and in the backend.
	 *
	 * @param string $openai_key The custom OpenAI key.
	 */
	public function save_openai_key( $openai_key ) {
		try {
			$result = $this->backend->save_openai_key( $openai_key );
		} catch ( Exception $e ) {
			return array(
				'success'  => false,
				'message'  => 'Could not save your custom OpenAI key. Please contact support. (server exception)',
				'response' => $e,
			);
		}

		$body = $result['body'];

		if ( ! $body || 200 !== $this->utils->extract_response_code( $result ) || ! array_key_exists( 'success', $body ) || ! $body['success'] ) {
			return array(
				'success'  => false,
				'message'  => 'Could not save your custom OpenAI key. Please contact support.',
				'response' => $result,
			);
		}

		$this->settings->update_openai_key_preview( $body['masked'] );

		return array( 'success' => true );
	}

	/**
	 * Removes the custom OpenAI key from the local options and in the backend.
	 */
	public function remove_openai_key() {
		try {
			$result = $this->backend->remove_openai_key();
		} catch ( Exception $e ) {
			return array(
				'success'  => false,
				'message'  => 'Could not remove your custom OpenAI key. Please contact support. (server exception)',
				'response' => $e,
			);
		}

		$body = $result['body'];

		if ( ! $body || 200 !== $this->utils->extract_response_code( $result ) || ! array_key_exists( 'success', $body ) || ! $body['success'] ) {
			return array(
				'success'  => false,
				'message'  => 'Could not remove your custom OpenAI key. Please contact support.',
				'response' => $result,
			);
		}

		$this->settings->update_openai_key_preview( null );

		return array( 'success' => true );
	}

	/**
	 * Tests and saves a custom OpenAI key in the local options and in the backend.
	 *
	 * @param string $openai_key The custom OpenAI key.
	 */
	public function test_and_save_openai_key( $openai_key ) {
		$save_result = $this->save_openai_key( $openai_key );

		if ( false === $save_result['success'] ) {
			return $save_result;
		}

		$test_result = $this->backend->generate_product_description(
			'en',
			'test',
			'test',
			'test',
			'10',
			'test'
		);

		if ( 200 !== $this->utils->extract_response_code( $test_result ) ) {
			$this->remove_openai_key();

			return array(
				'success'  => false,
				'message'  => 'TEST_FAILED',
				'response' => $test_result,
			);
		}

		return array( 'success' => true );
	}
}

global $syscoin_account;

$syscoin_account = new Syscoin_Account();
