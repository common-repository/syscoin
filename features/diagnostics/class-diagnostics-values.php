<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Diagnostics_Values class which provides functions to get the values used in diagnostics.
 *
 * @package syscoin
 */

/**
 * This class handles diagnostics values generation.
 */
class Syscoin_Diagnostics_Values {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_utils;
		$this->utils = $syscoin_utils;
	}

	/**
	 * Reference to global variable $syscoin_utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Gets a list of installed plugins.
	 */
	public function get_plugins_list() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		wp_update_plugins();

		$plugin_updates = get_plugin_updates();

		$plugins_info = array();

		foreach ( $all_plugins as $plugin_file => $plugin ) {
			$is_enabled = is_plugin_active( $plugin_file );

			if ( isset( $plugin_updates[ $plugin_file ] ) ) {
				$update = $plugin_updates[ $plugin_file ]->update;

				$is_up_to_date  = false;
				$newest_version = $update->new_version;
			} else {
				$is_up_to_date = true;
			}

			$plugins_info[ $plugin_file ] = array(
				'name'            => $plugin['Name'],
				'is_up_to_date'   => $is_up_to_date,
				'is_enabled'      => $is_enabled,
				'current_version' => $plugin['Version'],
				'newest_version'  => isset( $newest_version ) ? $newest_version : null,
			);
		}

		return $plugins_info;
	}

	/**
	 * Retrieves an associative array of all installed themes with their data, update status, activation status, and parent theme (if applicable).
	 */
	public function get_themes_list() {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-includes/theme.php';
		}

		$all_themes = wp_get_themes();

		wp_update_themes();

		$theme_updates = get_theme_updates();

		$active_theme     = wp_get_theme();
		$active_theme_dir = $active_theme->get_stylesheet();

		$themes_info = array();

		foreach ( $all_themes as $theme_dir => $theme ) {
			$is_enabled = ( $theme_dir === $active_theme_dir );

			if ( isset( $theme_updates[ $theme_dir ] ) ) {
				$update = $theme_updates[ $theme_dir ]->update;

				$is_up_to_date  = false;
				$newest_version = $update['new_version'];
			} else {
				$is_up_to_date = true;
			}

			$parent_theme_dir = $theme->get_template();
			$parent_theme     = null;

			if ( $parent_theme_dir && $parent_theme_dir !== $theme_dir ) {
				$parent_theme = wp_get_theme( $parent_theme_dir );
			}

			$themes_info[ $theme_dir ] = array(
				'name'            => $theme->get( 'Name' ),
				'is_up_to_date'   => $is_up_to_date,
				'is_enabled'      => $is_enabled,
				'current_version' => $theme->get( 'Version' ),
				'newest_version'  => isset( $newest_version ) ? $newest_version : null,
				'parent_theme'    => $parent_theme ? $parent_theme->get( 'Name' ) : null,
			);
		}

		return $themes_info;
	}

	/**
	 * Finds out if the website is configured to be shown in search engines or not.
	 */
	public function get_setting_allow_se() {
		$blog_public = get_option( 'blog_public' );

		if ( '1' === $blog_public ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets a list of admin users.
	 */
	public function get_admin_users() {
		$admin_users     = get_users( array( 'role' => 'Administrator' ) );
		$admin_usernames = array();

		foreach ( $admin_users as $user ) {
			$admin_usernames[] = $user->user_login;
		}

		return $admin_usernames;
	}

	/**
	 * Checks if an SMTP server is being used instedo of PHP's mailing function.
	 */
	public function get_using_smtp() {
		global $phpmailer;

		if ( ! empty( $phpmailer ) ) {
			$mailer = $phpmailer->Mailer; /* phpcs:ignore */

			if ( 'smtp' === $mailer ) {
				return true;
			}

			if ( ! empty( $phpmailer->getSMTPInstance() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the website has SSL configured.
	 */
	public function get_ssl_configured() {
		$site_url = get_option( 'siteurl' );
		$home_url = get_option( 'home' );

		if ( strpos( $site_url, 'https://' ) === 0 && strpos( $home_url, 'https://' ) === 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks for file ownership issues.
	 */
	public function get_file_ownership_issues() {
		$issues          = array();
		$current_user_id = getmyuid();

		$paths = array(
			ABSPATH . 'wp-config.php',
			ABSPATH . 'wp-admin',
			ABSPATH . 'wp-includes',
			ABSPATH . 'wp-content',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				$owner_id = fileowner( $path );

				if ( $owner_id !== $current_user_id ) {
					$issues[] = $path;
				}
			}
		}

		return $issues;
	}

	/**
	 * Checks for recent errors in the logs.
	 */
	public function get_recent_error_logs() {
		$log_file   = ini_get( 'error_log' );
		$hours_back = 168; // One week. IF YOU CHANGE THIS, UPDATE i18n STRINGS (for all languages).

		if ( ! file_exists( $log_file ) ) {
			return array( 'error' => 'Error log file not found.' );
		}

		$errors = array(
			'fatal'   => 0,
			'warning' => 0,
			'notice'  => 0,
		);

		$logs         = file( $log_file );
		$period_start = time() - ( $hours_back * 3600 );

		// Define possible log date formats (Apache, NGINX, etc.).
		$date_formats = array(
			'apache' => '/\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} (?:[A-Z]{3}))\]/', // Apache: [26-Sep-2024 20:09:23 UTC].
			'nginx'  => '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',                   // NGINX: 2024-09-26 20:09:23.
		);

		foreach ( $logs as $temp_log_line ) {
			$temp_log_time = null;

			// Try to match Apache or NGINX date formats.
			foreach ( $date_formats as $date_format => $regex_pattern ) {
				$temp_matched = preg_match( $regex_pattern, $temp_log_line, $date_matches );

				if ( $temp_matched ) {
					if ( 'apache' === $date_format ) {
							$temp_date_string = str_replace( ' UTC', '', $date_matches[1] );
					} elseif ( 'nginx' === $date_format ) {
							$temp_date_string = $date_matches[1];
					}

						$temp_log_time = strtotime( $temp_date_string );

						break;
				}
			}

			if ( $temp_log_time && $temp_log_time >= $period_start ) {
				if ( preg_match( '/PHP Fatal error:/', $temp_log_line ) ) {
					$errors['fatal'] += 1;
				} elseif ( preg_match( '/PHP Warning:/', $temp_log_line ) ) {
					$errors['warning'] += 1;
				} elseif ( preg_match( '/PHP Notice:/', $temp_log_line ) ) {
					$errors['notice'] += 1;
				}
			}
		}

		return array(
			'hours_back'   => $hours_back,
			'period_start' => $period_start,
			'errors'       => $errors,
		);
	}

	/**
	 * Gets current caching configuration.
	 */
	public function get_caching_status() {
		$caching_status = array(
			'object_cache'    => null, // boolean.
			'page_cache'      => array(), // indexed array.
			'caching_headers' => null, // boolean.
		);

		$caching_status['object_cache'] = wp_using_ext_object_cache();

		$cache_plugins = array(
			'wp-super-cache/wp-cache.php',
			'w3-total-cache/w3-total-cache.php',
			'wp-fastest-cache/wpFastestCache.php',
			'litespeed-cache/litespeed-cache.php',
			'autoptimize/autoptimize.php',
			'comet-cache/comet-cache.php',
			'cache-enabler/cache-enabler.php',
			'sg-cachepress/sg-cachepress.php',
			'swift-performance/swift-performance.php',
			'wp-rocket/wp-rocket.php',
			'breeze/breeze.php',
			'hummingbird/core.php',
			'wp-optimize/wp-optimize.php',
			'wp-rocket/wp-rocket.php',
			'simple-cache/simple-cache.php',
		);

		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		foreach ( $cache_plugins as $temp_plugin ) {
			if ( in_array( $temp_plugin, $active_plugins, true ) ) {
				$caching_status['page_cache'][] = $temp_plugin;
			}
		}

		$caching_status['caching_headers'] = ! empty( $_SERVER['CACHE_CONTROL'] ) || ! empty( $_SERVER['X-CACHE'] );

		return $caching_status;
	}

	/**
	 * Gets cron job diagnosis information.
	 */
	public function get_cron_info() {
		$result = array(
			'overdue_jobs'   => array(),
			'recurring_jobs' => array(),
		);

		$jobs = _get_cron_array();
		$now  = time();

		foreach ( $jobs as $temp_jobs_timestamp => $temp_timestamp_hooks ) {
			foreach ( $temp_timestamp_hooks as $temp_hook => $temp_hook_details ) {
				foreach ( $temp_hook_details as $temp_job ) {

					// Check for overdue jobs.
					if ( $temp_jobs_timestamp < $now ) {
						$result['overdue_jobs'][] = array(
							'hook'      => $temp_hook,
							'timestamp' => $temp_jobs_timestamp,
							'due_in'    => human_time_diff( $temp_jobs_timestamp, $now ) . ' ago',
						);
					}

					// Check for recurring jobs.
					if ( isset( $temp_job['schedule'] ) ) {
						$result['recurring_jobs'][] = array(
							'hook'     => $temp_hook,
							'schedule' => $temp_job['schedule'],
							'next_run' => human_time_diff( $temp_jobs_timestamp ),
						);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Looks for common directories that might be open to the public.
	 */
	public function get_open_directories() {
		$directories = array(
			'/wp-content/uploads/',
			'/wp-includes/',
			'/wp-content/plugins/',
			'/wp-content/themes/',
			'/wp-content/',
			'/wp-config.php',
			'/.htaccess',
			'/cgi-bin/',
			'/wp-content/cache/',
			'/wp-content/upgrade/',
			'/wp-content/languages/',
			'/wp-content/mu-plugins/',
			'/wp-content/advanced-cache.php',
		);

		$open_directories = array();

		foreach ( $directories as $directory ) {
			$url = site_url( $directory );

			try {
				$response = $this->utils->http_get( $url, array(), array(), false, 1 );
				$code     = $this->utils->extract_response_code( $response );
				$body     = $this->utils->extract_response_body( $response );

				if ( 200 === $code ) {
					if ( '' === $body ) {
						$open_directories[ $directory ] = false;
					} else {
						$open_directories[ $directory ] = true;
					}
				} else {
					$open_directories[ $directory ] = false;
				}
			} catch ( Exception $exception ) {
				$open_directories[ $directory ] = null;
			}
		}

		return $open_directories;
	}
}

global $syscoin_diagnostics_values;

$syscoin_diagnostics_values = new Syscoin_Diagnostics_Values();
