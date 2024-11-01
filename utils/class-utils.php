<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Utils class which provides utility functions for various tasks.
 *
 * @package syscoin
 */

/**
 * This class provides utility functions for various tasks.
 */
class Syscoin_Utils {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		global $syscoin_env;

		$this->env = $syscoin_env;
	}

	/**
	 * Reference to global variable syscoin_env, which contains the plugin's environment variables.
	 *
	 * @var array
	 */
	public $env;

	/**
	 * Enqueue script dependencies for all pages.
	 */
	public function enqueue_scripts() {
		$ver = $this->get_plugin_version();

		wp_enqueue_script( 'syscoin-utils-js', plugin_dir_url( __FILE__ ) . 'script-utils.js', array( 'jquery' ), $ver, true );

		wp_localize_script(
			'syscoin-utils-js',
			'script_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'syscoin_nonce' ),
			)
		);
	}

	/**
	 * Authentication query parameters.
	 *
	 * @var array
	 */
	public function get_auth_params() {
		global $syscoin_settings;

		$user_info = $syscoin_settings->get_user();

		return array(
			'token'  => $user_info['token'],
			'agency' => $this->env['AGENCY'],
		);
	}

	/**
	 * Sends a POST request using cURL.
	 *
	 * @param string  $endpoint The URL endpoint to send the request to.
	 * @param array   $body (associative array) Optional. The request body data.
	 * @param array   $query (associative array) Optional. The query parameters to append to the URL.
	 * @param array   $headers (indexed array) Optional. The headers to include in the request.
	 * @param boolean $authenticate Optional. Send `token` and `agency` as query parameters.
	 * @return array|string The parsed response data.
	 * @throws Exception If an error occurs during the request.
	 */
	public function http_post( $endpoint, $body = array(), $query = array(), $headers = array(), $authenticate = false ) {
		if ( $authenticate ) {
			$query = array_merge( $query, $this->get_auth_params() );
		}

		if ( ! empty( $query ) ) {
			$query_string = http_build_query( $query );
			$endpoint    .= '?' . $query_string;
		}

		$formatted_headers = array();
		foreach ( $headers as $header ) {
			list( $key, $value )               = explode( ':', trim( $header ), 2 );
			$formatted_headers[ trim( $key ) ] = trim( $value );
		}

		if ( ! isset( $formatted_headers['Content-Type'] ) ) {
			$formatted_headers['Content-Type'] = 'application/json';
		}

		$args = array(
			'headers'     => $formatted_headers,
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => 15,
		);

		$result = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $result ) ) {
			throw new Exception( 'Error: ' . esc_html( $result->get_error_message() ) );
		}

		$response_body = wp_remote_retrieve_body( $result );

		if ( is_string( $response_body ) && json_decode( $response_body, true ) ) {
			$result['body'] = json_decode( $response_body, true );
		}

		return $result;
	}

	/**
	 * Sends a GET request using cURL.
	 *
	 * @param string         $endpoint The URL endpoint to send the request to.
	 * @param array          $query (associative array) Optional. The query parameters to append to the URL.
	 * @param array          $headers (indexed array) Optional. The headers to include in the request.
	 * @param boolean        $authenticate Optional. Send `token` and `agency` as query parameters.
	 * @param boolean|number $limit Optional. Time limit in seconds; `15` if not provided.
	 * @return array|string The parsed response data.
	 * @throws Exception If an error occurs during the request.
	 */
	public function http_get( $endpoint, $query = array(), $headers = array(), $authenticate = false, $limit = false ) {
		if ( $authenticate ) {
			$query = array_merge( $query, $this->get_auth_params() );
		}

		if ( ! empty( $query ) ) {
			$query_string = http_build_query( $query );
			$endpoint    .= '?' . $query_string;
		}

		$formatted_headers = array();
		foreach ( $headers as $header ) {
			list( $key, $value )               = explode( ':', trim( $header ), 2 );
			$formatted_headers[ trim( $key ) ] = trim( $value );
		}

		if ( ! isset( $formatted_headers['Content-Type'] ) ) {
			$formatted_headers['Content-Type'] = 'application/json';
		}

		$args = array(
			'headers' => $formatted_headers,
			'timeout' => $limit ?? 15,
		);

		$result = wp_remote_get( $endpoint, $args );

		if ( is_wp_error( $result ) ) {
			throw new Exception( 'Error: ' . esc_html( $result->get_error_message() ) );
		}

		$response_body = wp_remote_retrieve_body( $result );

		if ( is_string( $response_body ) && json_decode( $response_body, true ) ) {
			$result['body'] = json_decode( $response_body, true );
		}

		return $result;
	}

	/**
	 * Checks if a given timestamp is older than a specified number of seconds.
	 *
	 * @param int $timestamp The timestamp to compare.
	 * @param int $seconds The number of seconds to compare against.
	 * @return bool Returns true if the timestamp is older than the specified number of seconds, false otherwise.
	 */
	public function is_older_than_x_seconds( $timestamp, $seconds ) {
		$difference = time() - $timestamp;

		if ( $difference > $seconds ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Extracts the HTTP code of a given response.
	 *
	 * @param array $response The response from which to extract the HTTP code.
	 * @return number
	 */
	public function extract_response_code( $response ) {
		if ( array_key_exists( 'response', $response ) && array_key_exists( 'code', $response['response'] ) ) {
			return $response['response']['code'];
		} else {
			return 0;
		}
	}

	/**
	 * Extracts the body of a given response.
	 *
	 * @param array $response The response from which to extract the body.
	 * @return string|null The response body or null if not found.
	 */
	public function extract_response_body( $response ) {
		if ( array_key_exists( 'body', $response ) ) {
				return $response['body'];
		} else {
				return null;
		}
	}

	/**
	 * Truncates a URL to end after a specified number of slashes.
	 *
	 * This function splits the given URL at each slash, reassembles it up to the specified limit of slashes,
	 * and returns the truncated URL. It ensures that the output ends right after the last slash included in the limit.
	 * This is useful for normalizing URLs or simplifying them by removing deeper path segments.
	 *
	 * @param string $url The URL to be truncated.
	 * @param int    $slash_limit The number of slashes to keep in the URL, after which the rest of the URL will be discarded.
	 * @return string The URL truncated to the desired number of slashes.
	 */
	public function truncate_url( $url, $slash_limit ) {
		$trimmed   = ltrim( $url, '/' );
		$parts     = explode( '/', $trimmed );
		$sliced    = array_slice( $parts, 0, $slash_limit );
		$truncated = '/' . implode( '/', $sliced );

		if ( strlen( $url ) > strlen( $truncated ) ) {
			$truncated = $truncated . '/*';
		}

		return $truncated;
	}

	/**
	 * Generates an array of days from the start of a given period until a specified end date.
	 *
	 * @param string $in_start A date string (e.g., '3 months ago') that specifies the start of the period.
	 * @param string $in_end A date string (e.g., 'today') that specifies the end of the period.
	 * @return array An associative array with keys as dates ('Y-m-d') from the specified period start to the end date, all values set to 0.
	 *
	 * @example
	 * $days_array = $this->create_period_days_array('3 months ago', 'today');
	 */
	public function create_period_days_array( $in_start, $in_end ) {
		$days_array = array();

		$user_tz = $this->get_wp_timezone();

		$dt_start = $this->create_date( $in_start )->setTimezone( $user_tz )->modify( 'midnight' );
		$dt_end   = $this->create_date( $in_end )->setTimezone( $user_tz )->modify( 'midnight' );

		$interval = new DateInterval( 'P1D' );

		$date_range = new DatePeriod( $dt_start, $interval, $dt_end );

		foreach ( $date_range as $date ) {
			$days_array[ $date->format( 'Y-m-d' ) ] = 0;
		}

		return $days_array;
	}

	/**
	 * Calculates the average value of an associative array.
	 *
	 * This function calculates the average value of an associative array by summing all
	 * the values and dividing by the number of elements in the array.
	 *
	 * @param array $target The associative array for which to calculate the average.
	 * @return float|int The average value of the array.
	 */
	public function calculate_array_average( $target ) {
		$sum = array_sum( $target );

		$count = count( $target );

		$average = ( $count > 0 ) ? $sum / $count : 0;

		return $average;
	}

	/**
	 * Ensures custom schedules used by the plugin are defined.
	 */
	public function ensure_schedule_definitions() {
		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				// Adds 'every3days' schedule to the existing schedules.
				$schedules['every3days'] = array(
					'interval' => 3 * DAY_IN_SECONDS,   // 3 days, calculated in seconds.
					'display'  => __( 'Every 3 Days' ), // Description for the WordPress admin.
				);

				// Adds 'weekly' schedule to the existing schedules.
				$schedules['weekly'] = array(
					'interval' => 7 * DAY_IN_SECONDS,   // 7 days, calculated in seconds.
					'display'  => __( 'Once Weekly' ),  // Description for the WordPress admin.
				);

				return $schedules;
			}
		);
	}

	/**
	 * Retrieve all scheduled instances of a specific hook.
	 *
	 * @param string $hook The hook to check for in the WP-Cron system.
	 * @return array An array of Unix timestamps when the hook is scheduled.
	 */
	public function get_all_scheduled_instances( $hook ) {
		$schedules = array();
		$crons     = get_option( 'cron' );

		if ( ! empty( $crons ) ) {
			foreach ( $crons as $timestamp => $jobs_assigned ) {
				if ( isset( $jobs_assigned[ $hook ] ) ) {
					$schedules[] = array(
						'timestamp' => $timestamp,
						'readable'  => gmdate( 'l, Y-m-d H:i:s', $timestamp ) . ' GMT',
						'schedule'  => $jobs_assigned[ $hook ][ array_key_first( $jobs_assigned[ $hook ] ) ]['schedule'],
						'args'      => $jobs_assigned[ $hook ][ array_key_first( $jobs_assigned[ $hook ] ) ]['args'],
					);
				}
			}
		}

		return $schedules;
	}

	/**
	 * Returns a DateTime instance according to the provided information.
	 *
	 * @param string  $when A string like `tomorrow` or `3 months ago`. Default: `now`.
	 * @param string  $where The timezone to be used. Either `wp` for the WordPress user's timezone or something like `America/New_York` or `UTC`. Default: `wp`.
	 * @param boolean $time_is_utc If the time provided in `$when` is in UTC. If so, it will be converted to the timezone provided in `$where`. Default: `false`.
	 *
	 * @return DateTime The DateTime instance.
	 */
	public function create_date( $when = 'now', $where = 'wp', $time_is_utc = false ) {
		if ( 'wp' === $where ) {
			$timezone = $this->get_wp_timezone();
		} else {
			$timezone = new DateTimeZone( $where );
		}

		if ( $time_is_utc ) {
			$dt = new DateTime( $when, new DateTimeZone( 'UTC' ) );
			$dt->setTimezone( $timezone );
		} else {
			$dt = new DateTime( $when, $timezone );
		}

		return $dt;
	}

	/**
	 * Returns the timezone configured in WordPress.
	 */
	public function get_wp_timezone() {
		$timezone_string = get_option( 'timezone_string' );

		if ( ! empty( $timezone_string ) ) {
			$timezone = new DateTimeZone( $timezone_string );
		} else {
			$gmt_offset    = get_option( 'gmt_offset' );
			$timezone_name = timezone_name_from_abbr( '', $gmt_offset * 3600, false );

			if ( $timezone_name ) {
				$timezone = new DateTimeZone( $timezone_name );
			} else {
				$timezone = new DateTimeZone( 'UTC' ); // Fallback to UTC if timezone name couldn't be determined.
			}
		}

		return $timezone;
	}

	/**
	 * Replaces specified substrings in a text based on key-value pairs provided in an associative array.
	 *
	 * @param string $text The original text to modify.
	 * @param array  $replacements Associative array where keys are substrings to replace, and values are the replacements.
	 * @return string The modified text after all replacements have been applied.
	 */
	public function apply_values( $text, $replacements ) {
		if ( ! is_array( $replacements ) || empty( $replacements ) ) {
			return $text;
		}

		$replacements = array_map(
			function ( $value ) {
				return isset( $value ) ? $value : 'unknown';
			},
			$replacements
		);

		$search  = array_keys( $replacements );
		$replace = array_values( $replacements );

		$result = str_replace( $search, $replace, $text );

		return $result;
	}

	/**
	 * Calculate the change from a to b in percents.
	 *
	 * @param number $a The base number.
	 * @param number $b The number with the change.
	 */
	public function calculate_percentage_difference( $a, $b ) {
		if ( 0 === $a || 0.0 === $a ) {
			return null;
		}

		return round( ( ( $b - $a ) / $a ) * 100, 2 );
	}


	/**
	 * Returns the version of the plugin.
	 *
	 * @return string The plugin version.
	 */
	public function get_plugin_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! defined( 'SYSCOIN_PLUGIN_FILE' ) ) {
			return 'unknown';
		}

		$plugin_data = get_plugin_data( SYSCOIN_PLUGIN_FILE );

		if ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] ) ) {
			return $plugin_data['Version'];
		}

		return 'unknown';
	}

	/**
	 * Verifies if the WooCommece plugin is active.
	 */
	public function is_woocommerce_active() {
		// Include the plugin.php file to use the is_plugin_active function.
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Check if WooCommerce is active.
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}


	/**
	 * Returns the plugin's agency from the environment variables.
	 *
	 * @param boolean $formatted `true` to get the formatted version of the agency variable. `false` if not provided.
	 */
	public function get_agency( $formatted = false ) {
		if ( $formatted ) {
			return $this->env['AGENCY_F'];
		} else {
			return $this->env['AGENCY'];
		}
	}

	/**
	 * Returns the plugin's environment name from the environment variables.
	 */
	public function get_env() {
		return $this->env['ENV'];
	}

	/**
	 * Validate a PHP date string representation.
	 *
	 * @param string $date_string The date string to validate.
	 * @return bool True if the string is a valid date representation, false otherwise.
	 */
	public function is_valid_date_string( $date_string ) {
		try {
			$date = new DateTime( $date_string );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Sets a datestring as a date object at midnight (of the user's tz) in UTC, in the format expected by the rest of the plugin.
	 *
	 * @param string $delimiter The timestring.
	 * @param string $no_utc `false` to convert the date into UTC, `true` to keep it as is.
	 */
	public function prepare_period_delimiter( $delimiter, $no_utc = false ) {
		$utc = new DateTimeZone( 'UTC' );

		if ( false === $no_utc ) {
			return $this->create_date( $delimiter )->modify( 'midnight' )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		} else {
			return $this->create_date( $delimiter, 'UTC', true )->modify( 'midnight' )->format( 'Y-m-d H:i:s' );
		}
	}

	/**
	 * Calculates the difference between all nested values in two structurally identical associative arrays.
	 *
	 * @param array $previous Associative array of the old data.
	 * @param array $current Associative array of the new data.
	 * @return array An array in the same structure of the ones received as parameters - but with percentages in place of their values.
	 */
	public function array_diff_assoc_recursive( $previous, $current ) {
		$result = array();

		foreach ( $previous as $key => $prev_value ) {
			$curr_value = $current[ $key ];

			if ( is_array( $prev_value ) && isset( $curr_value ) && is_array( $curr_value ) ) { // is array.
				$value = $this->array_diff_assoc_recursive( $prev_value, $curr_value );
			} elseif ( isset( $curr_value ) && 0 !== $prev_value && is_numeric( $prev_value ) && is_numeric( $curr_value ) ) { // is number.
				$difference = $this->calculate_percentage_difference( $prev_value, $curr_value );
				$sign       = $difference >= 0 ? '+' : '-';
				$value      = sprintf( '%s%.2f%%', $sign, abs( $difference ) );
			} else {
				$value = 0 !== $prev_value ? '+100.00%' : '+0.00%';
			}

			if ( '+0.00%' === $value ) {
				$value = null;
			}

			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Gets a string with general information about the page and plugin.
	 */
	public function get_info_string() {
		$version  = $this->get_plugin_version();
		$agency   = $this->env['AGENCY'];
		$env_name = $this->env['ENV'];
		$wp_ver   = get_bloginfo( 'version' ) ?? '?';
		$php_ver  = phpversion() ?? '?';

		return "$agency $version ($env_name) - WP $wp_ver - PHP $php_ver";
	}

	/**
	 * Concatenates a timestring with the offset in seconds to make it UTC.
	 * Feeding this function with `now` at `America/Sao_Paulo` (GMT-3) will make it return `now + 10800 seconds`.
	 *
	 * @param string      $timestring The timestring to be converted.
	 * @param string|null $timezone The timezone of the timestring, in format `America/Sao_Paulo`. Uses the WP timezone if not supplied.
	 */
	public function timestring_to_utc( $timestring, $timezone = null ) {
		if ( isset( $timezone ) ) {
			$timezone = new DateTimeZone( $timezone );
		} else {
			$timezone = $this->get_wp_timezone();
		}

		$datetime = new DateTime( $timestring, $timezone );

		$offset = $datetime->getOffset();

		if ( $offset >= 0 ) {
			return $timestring . ' - ' . $offset . ' seconds';
		} else {
				return $timestring . ' + ' . abs( $offset ) . ' seconds';
		}
	}

	/**
	 * Generates a table.
	 *
	 * @param array     $titles Associative array in format `array( "column_name" => "Column Title" )`.
	 * @param array     $data Regular array of associative arrays in format `array( array( "column_name" => "value" ) )`.
	 * @param boolean   $center Center the cell text or not. `true` by default.
	 * @param int|false $limit Cull the table to fit into this limit. No limit by default.
	 */
	public function generate_table( $titles, $data, $center = true, $limit = false ) {
		if ( empty( $data ) || empty( $titles ) ) {
			return '';
		}

		$output = '<div>';

		if ( $center ) {
			$output .= '<table class="widefat" style="text-align: center;">';
		} else {
			$output .= '<table class="widefat">';
		}

		$output .= '<thead><tr>';

		foreach ( $titles as $key => $title ) {
			$output .= '<th> <b>' . $title . '</b> </th>';
		}

		$output .= '</tr></thead>';
		$output .= '<tbody>';

		$count = 0;

		foreach ( $data as $row ) {
			if ( false !== $count && $count === $limit ) {
				$output .= '<tr> <td style="text-align: center;> ... </td> </tr>';
				break;
			}

			$output .= '<tr>';

			foreach ( $titles as $key => $title ) {
				$output .= '<td>' . ( isset( $row[ $key ] ) ? $row[ $key ] : '' ) . '</td>';
			}

			$output .= '</tr>';

			++$count;
		}

		$output .= '</tbody>';
		$output .= '</table>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Returns a random HEX color that's pseudorandom based on a string received.
	 *
	 * @param string $name The string to generate the color from.
	 */
	public function get_hash_color( $name ) {
		if ( empty( $name ) || strpos( $name, 'No ' ) === 0 ) {
			return '#253137';
		}

		// Hash the name to generate a number.
		$length = strlen( $name );
		$hash   = 0;

		for ( $i = 0; $i < $length; $i++ ) {
			$hash = ord( $name[ $i ] ) + ( ( $hash << 5 ) - $hash );
		}

		// Convert the hash to a color.
		$color = '#';
		for ( $i = 0; $i < 3; $i++ ) {
			// Shift bits and ensure color is light (between 100 and 255).
			$value       = ( $hash >> ( $i * 8 ) ) & 0xFF;
			$light_value = max( 100, $value ); // Ensure the color is not too dark.
			$color      .= str_pad( dechex( $light_value ), 2, '0', STR_PAD_LEFT ); // Convert to hex and ensure 2 digits.
		}

		return $color;
	}

	/**
	 * Returns a demonstrator for a HEX color.
	 *
	 * @param string $hex The string to generate the color from.
	 */
	public function get_color_demo( $hex ) {
		return '<div style="display: inline-block; background-color: ' . $hex . '; width: 10px; height: 10px;"></div> &nbsp;';
	}

	/**
	 * If the string received is part of a url in the format `/wp-admin`, returns an anchor to it. Else, returns the string unchanged.
	 *
	 * @param string $value The string to check and parse.
	 */
	public function make_clickable_if_url( $value ) {
		if ( strpos( $value, '/' ) === 0 && substr( $value, -1 ) !== '*' ) {
			$url     = home_url( $value );
			$element = '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $value ) . '</a>';
			return $element;
		}

		return esc_html( $value );
	}

	/**
	 * Filter an array of access logs by keeping only unique accesses in each day.
	 *
	 * @param array $logs The access logs.
	 * @return array The filtered access logs.
	 */
	public function filter_unique_accesses( $logs ) {
		$unique_users  = array();
		$filtered_logs = array();

		foreach ( $logs as $log ) {
			// Extract the date part from access_time (assuming 'YYYY-MM-DD HH:MM:SS' format).
			$date = substr( $log->access_time, 0, 10 );

			// Determine the unique identifier for the user.
			if ( isset( $log->user_id ) && ! empty( $log->user_id ) ) {
				// Use user_id (WordPress account number) if available.
				// This ensures that each logged-in user is uniquely identified.
				$identifier = 'uid:' . $log->user_id;
			} else {
				// Fallback to IP and User Agent for anonymous users.
				// Combining IP and User Agent helps approximate uniqueness.
				$identifier = 'ip:' . $log->user_ip . '|ua:' . $log->user_agent;
			}

			// Hash the identifier to ensure consistent key length and privacy.
			$hashed_identifier = md5( $identifier );

			// Create a unique key combining the hashed identifier and the date.
			$unique_key = $hashed_identifier . '|' . $date;

			// Check if this unique_key has already been recorded.
			if ( ! isset( $unique_users[ $unique_key ] ) ) {
				// Mark this unique_key as seen.
				$unique_users[ $unique_key ] = true;

				// Add the current log to the filtered results.
				$filtered_logs[] = $log;
			}
		}

		return $filtered_logs;
	}

	/**
	 * Generates an HTML list from the provided array.
	 *
	 * @param array $items The iterative (not associative) array.
	 */
	public function array_to_html_list( $items ) {
		if ( ! isset( $items ) || ! $items ) {
			return '';
		}

		$html = '<ul>';

		foreach ( $items as $item ) {
			$html .= '<li> &bull; ' . $item . '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Logs to the plugin's log file.
	 *
	 * @param string $message The line to write.
	 */
	public function log( $message ) {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$log_file = plugin_dir_path( __DIR__ ) . 'logs.log';

		$timestamp   = gmdate( 'Y-m-d H:i:s' );
		$log_message = '[' . $timestamp . ' GMT] ' . $message . PHP_EOL;

		if ( ! $wp_filesystem->exists( $log_file ) ) {
			$wp_filesystem->put_contents( $log_file, $log_message );
		} else {
			$existing_content = $wp_filesystem->get_contents( $log_file );
			$wp_filesystem->put_contents( $log_file, $existing_content . $log_message );
		}
	}
}

global $syscoin_utils;

$syscoin_utils = new Syscoin_Utils();
