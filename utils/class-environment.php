<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Environment class which provides values like urls to the rest of the plugin.
 * This was changed from a .env file into this class as per the WordPress plugin guidelines.
 *
 * @package syscoin
 */

/**
 * This class provides environment values for the rest of the plugin.
 */
class Syscoin_Environment {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->values = array(
			'ENV'                      => 'prod',
			'AGENCY'                   => 'syscoin',
			'AGENCY_F'                 => 'Syscoin',
			'EP_LOG_IN'                => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-login',
			'EP_GET_TOKEN'             => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-getToken',
			'EP_CHECK_TOKEN'           => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-checkToken',
			'EP_REVOKE_TOKEN'          => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-revokeToken',
			'EP_GENERATE_PRODUCT_DESC' => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-generateProductDescription',
			'EP_SAVE_OPENAI_KEY'       => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-saveCustomOpenAiKey',
			'EP_CHECK_OPENAI_KEY'      => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-checkCustomOpenAiKey',
			'EP_REMOVE_OPENAI_KEY'     => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-removeCustomOpenAiKey',
			'URL_CREATE_ACCOUNT'       => 'https://dashboard.dashcommerce.com.br/login',
			'URL_FORGOT_PASSWORD'      => 'https://dashboard.dashcommerce.com.br/login',
			'URL_APP_APP_STORE'        => 'https://apps.apple.com/br/app/syscoin/id1467841266',
			'URL_APP_GOOGLE_PLAY'      => 'https://play.google.com/store/apps/details?id=br.com.syscoin',
			'EP_SEND_WHATSAPP_MESSAGE' => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-sendWhatsAppMessage',
			'URL_AGENCY'               => 'https://syscoin.com.br/',
			'EP_LOG_IN_ANON'           => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-loginAnon',
			'EP_GET_DIAGNOSTICS'       => 'https://us-central1-dashcommerce-app.cloudfunctions.net/pluginFunctions-getDiagnostics',
		);
	}

	/**
	 * The variable to hold the environment values.
	 *
	 * @var array
	 */
	public $values;
}

global $syscoin_env;

$syscoin_env = ( new Syscoin_Environment() )->values;

$a = 'a';
