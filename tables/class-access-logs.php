<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Access_Logs class which provides functions related to the
 * WordPress database table that contains information about every access to the website.
 *
 * @package syscoin
 */

/**
 * This class represents the access logs table and provides methods to create and update it.
 */
class Syscoin_Access_Logs {
	/**
	 * The class constructor.
	 */
	public function __construct() {
		global $syscoin_utils;
		$this->utils = $syscoin_utils;
	}

	/**
	 * Reference to global variable $syscoin_utils
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * The name of the access logs table.
	 *
	 * @var string
	 */
	private $table_name = 'syscoin_website_access_logs';

	/**
	 * Create custom analytics table.
	 */
	public function create_table() {
		global $wpdb;

		$full_table_name = $wpdb->prefix . $this->table_name;

		$sql = "CREATE TABLE $full_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        access_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        user_ip varchar(45) NOT NULL,
        user_agent varchar(255) NOT NULL,
				requested_page varchar(255) NOT NULL,
				referer varchar(255) NOT NULL,
				utm_source varchar(255) NOT NULL,
				utm_medium varchar(255) NOT NULL,
				utm_campaign varchar(255) NOT NULL,
				utm_term varchar(255) NOT NULL,
				utm_content varchar(255) NOT NULL,
				user_id varchar(255),
        PRIMARY KEY (id)
    )";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Logs the access to the website.
	 *
	 * @param array $access_data The access data to be logged.
	 */
	public function log_access( $access_data ) {
		global $wpdb;

		$full_table_name = $wpdb->prefix . $this->table_name;

		// phpcs:ignore
		return $wpdb->insert( 
			$full_table_name,
			$access_data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) // Fixes an issue with user_id. This is each column's type - update when making changes to the table's columns.
		);
	}

	/**
	 * Retrieves the access logs within a specified timeframe.
	 * Allows the use of relative time strings like `today` or `3 months ago`.
	 * To retrieve all records, call function with `null` as both parameters.
	 *
	 * @param string|null $in_start The timestring start date of the timeframe.
	 * @param string|null $in_end   The timestring end date of the timeframe.
	 * @param string|null $in_tz    The timezone that the imestrings are in.
	 * @return array The access logs within the specified timeframe.
	 */
	public function get_access_logs( $in_start = null, $in_end = null, $in_tz = null ) {
		$in_start = $this->utils->timestring_to_utc( $in_start, $in_tz );
		$in_end   = $this->utils->timestring_to_utc( $in_end, $in_tz );

		$str_start_utc = $in_start ? $this->utils->prepare_period_delimiter( $in_start ) : null;
		$str_end_utc   = $in_end ? $this->utils->prepare_period_delimiter( $in_end ) : null;

		global $wpdb;

		$table_name = $wpdb->prefix . $this->table_name;

		if ( $str_start_utc && $str_end_utc ) {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE access_time BETWEEN %s AND %s ORDER BY access_time DESC", /* phpcs:ignore */
				$str_start_utc,
				$str_end_utc
			);
		} else { // If any of the dates are null, fetch all records.
			$query = "SELECT * FROM {$table_name} ORDER BY access_time DESC";
		}

		$results = $wpdb->get_results( $query ); /* phpcs:ignore */

		return $results;
	}

	/**
	 * Retrieves the count of requested pages.
	 *
	 * @param string  $start The start of the period.
	 * @param string  $end Optional. The end of the period. `today` if not provided.
	 * @param boolean $group Optional. Group requests into /* instead of the full URL. `true` if not provided.
	 * @return array The count of requested pages.
	 */
	public function get_requested_page_counts( $start = '3 months ago', $end = 'today', $group = true ) {
		$access_logs = $this->get_access_logs( $start, $end );

		return $this->calc_page_counts( $access_logs, $group );
	}

	/**
	 * Calculates page counts from supplied access logs.
	 *
	 * @param array   $access_logs Access logs.
	 * @param boolean $group Optional. Group requests into /* instead of the full URL. `true` if not provided.
	 * @return array The count of requested pages.
	 */
	public function calc_page_counts( $access_logs, $group = true ) {
		$plucked = wp_list_pluck( $access_logs, 'requested_page' );

		if ( $group ) {
			$plucked = array_map(
				function ( $url ) {
					return $this->utils->truncate_url( $url, 1 );
				},
				$plucked
			);
		}

		$page_counts = array_count_values( $plucked );

		arsort( $page_counts );

		return $page_counts;
	}

	/**
	 * Retrieves the count of access logs per day.
	 *
	 * @param string $in_start The start of the period. `24 months ago` if not provided.
	 * @param string $in_end Optional. The end of the period. `tomorrow` if not provided.
	 * @return array The count of access logs per day.
	 */
	public function get_day_counts( $in_start = '24 months ago', $in_end = 'tomorrow' ) {
		$access_logs = $this->get_access_logs( $in_start, $in_end );

		return $this->calc_day_counts( $access_logs, $in_start, $in_end );
	}

	/**
	 * Calculates the count of access logs per day from supplied access logs.
	 *
	 * @param array  $access_logs Access logs.
	 * @param string $in_start The start of the period.
	 * @param string $in_end The end of the period.
	 * @return array The count of access logs per day.
	 */
	public function calc_day_counts( $access_logs, $in_start, $in_end ) {
		$user_tz = $this->utils->get_wp_timezone();

		$access_dates = array_map(
			function ( $log ) use ( $user_tz ) {
				$date = $this->utils->create_date( $log->access_time, 'UTC', true );

				$date->setTimezone( $user_tz );

				return $date->format( 'Y-m-d' );
			},
			$access_logs
		);

		$day_counts = array_count_values( $access_dates );

		$days = $this->utils->create_period_days_array( $in_start, $in_end );

		foreach ( $days as $date => $number ) {
			if ( isset( $day_counts [ $date ] ) ) {
				$days[ $date ] = $day_counts[ $date ];
			}
		}

		return $days;
	}

	/**
	 * Retrieves the overview of UTM sources.
	 *
	 * @param string $start The start of the period.
	 * @param string $end Optional. The end of the period. `today` if not provided.
	 * @return array The count of UTM sources.
	 */
	public function get_utm_overview( $start = '3 months ago', $end = 'today' ) {
		$access_logs = $this->get_access_logs( $start, $end );

		return $this->calc_utm_overview( $access_logs );
	}

	/**
	 * Calculates the overview of UTM sources.
	 *
	 * @param array $access_logs The access logs.
	 * @return array The count of UTM sources.
	 */
	public function calc_utm_overview( $access_logs ) {
		$source   = array_count_values( wp_list_pluck( $access_logs, 'utm_source' ) );
		$medium   = array_count_values( wp_list_pluck( $access_logs, 'utm_medium' ) );
		$campaign = array_count_values( wp_list_pluck( $access_logs, 'utm_campaign' ) );
		$term     = array_count_values( wp_list_pluck( $access_logs, 'utm_term' ) );
		$content  = array_count_values( wp_list_pluck( $access_logs, 'utm_content' ) );

		arsort( $source );
		arsort( $medium );
		arsort( $campaign );
		arsort( $term );
		arsort( $content );

		return array(
			'source'   => $source,
			'medium'   => $medium,
			'campaign' => $campaign,
			'term'     => $term,
			'content'  => $content,
		);
	}

	/**
	 * Checks if the access logs table is empty.
	 *
	 * @return bool `true` if the table is empty, `false` otherwise.
	 */
	public function is_empty() {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->table_name;
    $results = $wpdb->get_var("SELECT COUNT(*) FROM $table_name"); // phpcs:ignore

		return 0 === $results;
	}

	/**
	 * Gets the count of unique visitors in the period provided.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period. Default: `today`.
	 * @return int The count of unique visitors.
	 */
	public function get_unique_visitors_count( $start, $end = 'today' ) {
		global $wpdb;

		$str_start_utc = $start ? $this->utils->prepare_period_delimiter( $start ) : null;
		$str_end_utc   = $end ? $this->utils->prepare_period_delimiter( $end ) : null;

		if ( ! $str_start_utc || ! $str_end_utc ) {
			return 0;
		}

		$table_name = $wpdb->prefix . $this->table_name;

		/* phpcs:ignore */
		$query = $wpdb->prepare("SELECT COUNT(DISTINCT CONCAT(user_id, '_', user_ip)) AS user_count FROM %i WHERE access_time BETWEEN %s AND %s",
			$table_name,
			$str_start_utc,
			$str_end_utc
		);

		/* phpcs:ignore */
		$result = (int) $wpdb->get_var( $query );

		return $result ? $result : 0;
	}
}

global $syscoin_table_access_logs;

$syscoin_table_access_logs = new Syscoin_Access_Logs();
