<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Syscoin_Product_Metabox class which provides functions related to the product metabox of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../../options/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . '../../utils/class-utils.php';

/**
 * This class represents a meta box for managing product information.
 */
class Syscoin_Product_Metabox {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_generateAiDescription', array( $this, 'generate_ai_description' ) );
	}

	/**
	 * Enqueues the necessary scripts for the product metabox feature.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$pages = array( 'post-new.php', 'post.php' );

		if ( ! in_array( $hook, $pages, true ) ) {
			return;
		}

		global $syscoin_utils;

		$ver = $syscoin_utils->get_plugin_version();

		wp_enqueue_script( 'syscoin-product-metabox-js', plugin_dir_url( __FILE__ ) . 'product-metabox.js', array( 'syscoin-utils-js' ), $ver, true );

		global $syscoin_settings;

		$user = $syscoin_settings->get_user();

		wp_localize_script(
			'syscoin-product-metabox-js',
			'script_vars',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'syscoin_nonce' ),
				'is_logged_in'   => $user['logged_in'],
				'is_premium'     => $user['premium'],
				'has_openai_key' => (bool) $user['openai_key_preview'],
			)
		);
	}

	/**
	 * Registers the metabox for the Syscoin plugin.
	 */
	public function register_metabox() {
		global $syscoin_env;

		$agency_formatted = $syscoin_env['AGENCY_F'];

		add_meta_box(
			'syscoin-field',
			$agency_formatted,
			array( $this, 'metabox_callback' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * Callback function for the product metabox. Renders the HTML content of the metabox.
	 */
	public function metabox_callback() {
		$current_language      = get_bloginfo( 'language' );
		$current_language_code = explode( '-', $current_language )[0];

		$languages = array(
			'en' => 'English',
			'pt' => 'Português',
			'es' => 'Español',
			'da' => 'Dansk',
			'it' => 'Italiano',
			'de' => 'Deutsch',
		);

		?>
		<div id="syscoin-product-metabox">
			<div class="syscoin-initially-shown">
				<div class="syscoin-loading-spinner-container">
					<div class="syscoin-loading-spinner"></div>
				</div>
			</div>
			
			<div class="syscoin-initially-hidden">
				<div id="syscoin-product-metabox-for-non-premium">
					<?php esc_html_e( 'FEATURE_EXCLUSIVE_FOR_PREMIUM_USERS', 'syscoin' ); ?>
					<a href="https://dashboard.syscoin.com.br"> <?php esc_html_e( 'UPGRADE_NOW', 'syscoin' ); ?> </a>
				</div>

				<div id="syscoin-product-metabox-for-non-logged-in">
					<?php esc_html_e( 'YOU_HAVE_TO_LOG_IN_TO_USE_THE_PLUGINS_FEATURES', 'syscoin' ); ?>  
					<a href='admin.php?page=syscoin-settings'> <?php esc_html_e( 'GO_TO_SETTINGS_TO_LOG_IN', 'syscoin' ); ?> </a>
				</div>

				<div id="syscoin-product-metabox-for-premium">
					<div id="syscoin-product-metabox-first-title" style="display: flex; flex-direction: row; justify-content: space-between;">
						<p>
							<b> <?php esc_html_e( 'AI_DESCRIPTION_GENERATOR', 'syscoin' ); ?> </b> 
							&bull; 
							<i> <?php esc_html_e( 'AI_DESCRIPTION_GENERATOR_DESCRIPTION', 'syscoin' ); ?> </i>
						</p>

						<div style="display: flex; flex-direction: row; justify-content: center; align-items: center; margin-bottom: 5px;">
							<div class="syscoin-loading-spinner-container" id="syscoin-metabox-spinner" style="height: 100%; margin-right: 10px;">
								<i> <?php esc_html_e( 'GENERATING_DESCRIPTION', 'syscoin' ); ?> &nbsp; &bull; &nbsp; </i>
								<div class="syscoin-loading-spinner"></div>
							</div>

							<div class="syscoin-name" style="width: 175px; background-position: right; margin: 0;"></div>
						</div>
					</div>

					<div id="syscoin-product-metabox-for-no-token">
						<?php esc_html_e( 'NO_OPENAI_TOKEN', 'syscoin' ); ?>  
						<a href='admin.php?page=syscoin-settings'> <?php esc_html_e( 'GO_TO_SETTINGS_TO_CONFIGURE', 'syscoin' ); ?> </a>
					</div>

					<div id="syscoin-product-metabox-generator-content" style="display: flex; justify-content: space-between;">
						<div id="syscoin-product-metabox-generator-draft-container" style="width: 50%; padding-right: 10px;">
							<input 
								style="width: 100%; height: 100%;"
								id="syscoin-ai-desc-draft"
								type="text"
								placeholder="<?php esc_html_e( 'WRITE_DRAFT_FOR_DESCRIPTIONS', 'syscoin' ); ?>"
							>
						</div>

						<div style="flex: 1;"></div>

						<div id="syscoin-product-metabox-generator-count-lang-container" style="display: flex; height: 30px; justify-content: space-between;">
							<div id="syscoin-product-metabox-generator-count">
								<input 
									type="range"
									class="slider"
									id="syscoin-word-count-slider"
									min="0"
									max="100"
									value="50"
								>
								<div style="display: flex; justify-content: space-between">
									<div>
										<?php esc_html_e( 'WORDS', 'syscoin' ); ?>:
									</div>
									<div class="value-display" id="syscoin-word-count-display">
										50
									</div>
								</div>
							</div>

							<div id="syscoin-product-metabox-generator-lang">
								<select name="syscoin-language" id="syscoin-language-selector">
									<?php foreach ( $languages as $code => $name ) : ?>
										<option 
											value="<?php echo esc_attr( $code ); ?>"
											
											<?php
											if ( $current_language_code === $code ) {
												echo 'selected';
											}
											?>
										>
											<?php echo esc_html( $name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div style="flex: 1;"></div>

						<div id="syscoin-product-metabox-generator-actions" style="display: flex; justify-content: space-between;">
							<button class="button syscoin-button" id="syscoin-button-gen-ai-desc"> <?php esc_html_e( 'GENERATE_DESCRIPTION', 'syscoin' ); ?> </button>
							<button class="button syscoin-button" id="syscoin-button-gen-ai-desc-short"> <?php esc_html_e( 'GENERATE_SHORT_DESCRIPTION', 'syscoin' ); ?> </button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles requests for AI-generated post descriptions.
	 */
	public function generate_ai_description() {
		if ( ! check_ajax_referer( 'syscoin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce verification failed', 403 );
		}

		$product_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : null;
		$categories   = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : null;
		$language     = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : null;
		$variations   = isset( $_POST['variations'] ) ? sanitize_text_field( wp_unslash( $_POST['variations'] ) ) : null;
		$draft        = isset( $_POST['draft'] ) ? sanitize_text_field( wp_unslash( $_POST['draft'] ) ) : null;
		$word_count   = isset( $_POST['amountOfWords'] ) ? sanitize_text_field( wp_unslash( $_POST['amountOfWords'] ) ) : null;

		if ( empty( $language ) || empty( $product_name ) ) {
			wp_send_json( array( 'success' => false ) );
			wp_die();
		}

		global $syscoin_backend;
		global $syscoin_utils;

		$generic_ai_desc_error_message = 'Could not generate a product description at this time. Please try again later.';

		try {
			$result = $syscoin_backend->generate_product_description( $language, $product_name, $categories, $variations, $word_count, $draft );
		} catch ( Exception $e ) {
			wp_send_json(
				array(
					'success'  => false,
					'message'  => $generic_ai_desc_error_message,
					'response' => $result,
				)
			);

			wp_die();
		}

		$body = $result['body'];

		if ( ! $body || 200 !== $syscoin_utils->extract_response_code( $result ) || ! array_key_exists( 'text', $body ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'message'  => $generic_ai_desc_error_message,
					'response' => $result,
				)
			);

			wp_die();
		}

		$description = $body['text'];

		wp_send_json(
			array(
				'success'     => true,
				'description' => $description,
			)
		);

		wp_die();
	}
}

new Syscoin_Product_Metabox();
