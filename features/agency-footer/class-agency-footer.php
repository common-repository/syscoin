<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Agency_Footer class which provides functions related to the
 * agency footer that is added to the website by the plugin.
 *
 * @package syscoin
 */

/**
 * This class handles admin script-related functionality.
 */
class Syscoin_Agency_Footer {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_settings;
		$this->settings = $syscoin_settings;

		global $syscoin_env;
		$this->url = $syscoin_env['URL_AGENCY'];

		$this->reflect_flag();
	}

	/**
	 * The key of the setting in the plugin's settings option.
	 *
	 * @var string
	 */
	private $setting_key = 'show_agency_footer';

	/**
	 * Reference to global instance of Syscoin_Settings.
	 *
	 * @var Syscoin_Settings
	 */
	private $settings;

	/**
	 * The url that the footer will point to.
	 *
	 * @var string;
	 */
	private $url;

	/**
	 * Set wether to display the agency footer or not.
	 *
	 * @param boolean $enable Enable or disable the agency footer.
	 */
	public function set_flag( $enable ) {
		$this->settings->update_single_entry( $this->setting_key, $enable );
	}

	/**
	 * Get the current state of the flag.
	 */
	public function get_flag() {
		$settings = $this->settings->get_settings();

		if ( ! isset( $settings[ $this->setting_key ] ) ) {
			$this->set_flag( false );

			$settings = $this->settings->get_settings();
		}

		return $settings[ $this->setting_key ];
	}

	/**
	 * Reflect the current state of the flag by setting or removing the WordPress action that adds the footer.
	 */
	public function reflect_flag() {
		$current = $this->get_flag();

		if ( true === $current ) {
			add_action( 'wp_footer', array( $this, 'footer_callback' ) );
		} else {
			remove_action( 'wp_footer', array( $this, 'footer_callback' ) );
		}
	}

	/**
	 * The callback that echoes the footer's HTML code.
	 */
	public function footer_callback() {
		?>
		<div id="syscoin-footer" style="background-color: #fff; padding: 3px; font-size: 12px;">
			<a target="_blank" href="<?php echo esc_html( $this->url ); ?>" style="display: flex; justify-content: center; align-items: center; text-decoration: none; color: #000">
				<?php esc_html_e( 'SUPPORT', 'syscoin' ); ?>
				<img src="<?php echo esc_url( plugins_url( '../../assets/syscoin-name.png', __FILE__ ) ); ?>" style="margin: 10px; height: 25px">
			</a>
		</div>
		<?php
	}
}

global $syscoin_agency_footer;

$syscoin_agency_footer = new Syscoin_Agency_Footer();
