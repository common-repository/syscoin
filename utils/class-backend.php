<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress
 * It includes the Syscoin_Backend class which provides functions for backend calls for the rest of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../utils/class-utils.php';

/**
 * This class provides functions that make backend calls.
 */
class Syscoin_Backend {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_env;
		$this->env = $syscoin_env;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;
	}

	/**
	 * Global instance of Syscoin_Environment
	 *
	 * @var Syscoin_Environment
	 */
	private $env;

	/**
	 * Global instance of Syscoin_Utils
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Request a login for an account.
	 *
	 * @param string $username The user's email.
	 * @param string $password The user's password.
	 */
	public function login_user( $username, $password ) {
		return $this->utils->http_post(
			$this->env['EP_LOG_IN'],
			array(
				'storeUrl' => home_url(),
				'email'    => $username,
				'password' => $password,
				'agency'   => $this->env['AGENCY'],
			),
			array(),
			array()
		);
	}

	/**
	 * Request an anonymous login.
	 */
	public function login_anon() {
		return $this->utils->http_post(
			$this->env ['EP_LOG_IN_ANON'],
			array(
				'storeUrl' => home_url(),
				'agency'   => $this->env['AGENCY'],
			),
			array(),
			array()
		);
	}

	/**
	 * Saves a custom OpenAI key.
	 *
	 * @param string $openai_key The custom key.
	 */
	public function save_openai_key( $openai_key ) {
		return $this->utils->http_post(
			$this->env['EP_SAVE_OPENAI_KEY'],
			array(),
			array(
				'key' => $openai_key,
			),
			array(),
			true
		);
	}

	/**
	 * Removes a custom OpenAI key.
	 */
	public function remove_openai_key() {
		return $this->utils->http_post(
			$this->env['EP_REMOVE_OPENAI_KEY'],
			array(),
			array(),
			array(),
			true
		);
	}

	/**
	 * Verifies if a token is valid.
	 *
	 * @param string $token The token to be verified.
	 */
	public function check_token( $token ) {
		return $this->utils->http_get(
			$this->env['EP_CHECK_TOKEN'],
			array(
				'token'  => $token,
				'agency' => $this->env['AGENCY'],
			),
			array(),
		);
	}

	/**
	 * Gets an AI-generated description for a given product.
	 *
	 * @param string $lang The language for the description. Should be in a format like `en` or `ptBR`.
	 * @param string $name The product name/title.
	 * @param string $categories The categories of the product. Should be a string with comma-separated values.
	 * @param string $variations The variations of the product. Should be a string with comma-separated values.
	 * @param string $words Amout of words. Should be a string with a numeric value, like `"15"`.
	 * @param string $draft A draft for the AI to generate the description from.
	 */
	public function generate_product_description( $lang, $name, $categories, $variations, $words, $draft ) {
		return $this->utils->http_get(
			$this->env['EP_GENERATE_PRODUCT_DESC'],
			array(
				'language'      => $lang,
				'name'          => $name,
				'categories'    => $categories,
				'variations'    => $variations,
				'amountOfWords' => $words,
				'draft'         => $draft,
			),
			array(),
			true
		);
	}

	/**
	 * Sends a WhatsApp message to the specified phone number.
	 *
	 * @param string $phone The recipient's phone number.
	 * @param string $message The message to be sent.
	 */
	public function send_whatsapp_message( $phone, $message ) {
		return $this->utils->http_post(
			$this->env['EP_SEND_WHATSAPP_MESSAGE'],
			array(
				'recipients' => array( $phone ),
				'message'    => $message,
			),
			array(),
			array(),
			true
		);
	}

	/**
	 * Requests diagnostics for the website.
	 *
	 * @param array $data Associative array containing information about the website.
	 */
	public function get_diagnostics( $data ) {
		return $this->utils->http_post(
			$this->env['EP_GET_DIAGNOSTICS'],
			array(
				'data' => $data,
			),
			array(),
			array(),
			true
		);
	}
}

global $syscoin_backend;

$syscoin_backend = new Syscoin_Backend();
