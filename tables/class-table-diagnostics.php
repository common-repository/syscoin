<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Table_Diagnostics class which provides functions related to the diagnostics feature of the plugin.
 *
 * @package syscoin
 */

/**
 * This class represents the diagnostics table and provides methods to create and update it.
 */
class Syscoin_Table_Diagnostics {
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
	 * The name of the diagnostics table.
	 *
	 * @var string
	 */
	private $table_name = 'syscoin_diagnostics';

	/**
	 * Create diagnostics table.
	 */
	public function create_table() {
		global $wpdb;

		$full_table_name = $wpdb->prefix . $this->table_name;

		$sql = "CREATE TABLE $full_table_name (
			id BIGINT NOT NULL,
			mode VARCHAR(32),
			score INT,
			content TEXT NOT NULL,
			PRIMARY KEY (id)
    )";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Saves a diagnosis result to the database.
	 *
	 * @param int    $timestamp The timestamp for the row. e.g. `1727909889`.
	 * @param array  $diagnosis The diagnosis data.
	 * @param number $score The score for the diagnosis.
	 * @param string $mode If the diagnosis was requested manually or scheduled.
	 * @return int | null The ID of the saved diagnosis.
	 */
	public function save_diagnosis( $timestamp, $diagnosis, $score, $mode ) {
		global $wpdb;

		$full_table_name = $wpdb->prefix . $this->table_name;

		try {
			// phpcs:ignore
			$wpdb->insert( 
				$full_table_name,
				array(
					'id'      => $timestamp,
					'mode'    => $mode,
					'score'   => $score,
					'content' => wp_json_encode( $diagnosis ),
				)
			);

			$rows = $this->get_diagnosis_list( 999 );

			if ( count( $rows ) > 10 ) {
				$rows_to_delete = array_slice( $rows, 10 );

				foreach ( $rows_to_delete as $row ) {
					// phpcs:ignore
					$wpdb->delete(
						$full_table_name,
						array( 'id' => $row['id'] ),
						array( '%d' )
					);
				}
			}

			return $timestamp;
		} catch ( Exception $error ) {
			return null;
		}
	}

	/**
	 * Retrieves a diagnosis by its ID.
	 *
	 * @param int $id The ID of the diagnosis to retrieve.
	 * @return array|null The decoded diagnosis data as an associative array, or null if not found or on failure.
	 */
	public function get_diagnosis( $id ) {
		global $wpdb;

		$id = (int) $id;

		$table_name = $wpdb->prefix . $this->table_name;

		$query = $wpdb->prepare( 'SELECT * FROM ' . $table_name . ' WHERE id = %d', $id ); /* phpcs:ignore */
    $row = $wpdb->get_row( $query, ARRAY_A ); /* phpcs:ignore */

		if ( $row ) {
			$row['content'] = json_decode( $row['content'], true );

			$latest = $this->get_diagnosis_list( 1 )[0];

			if ( $latest['id'] === $row['id'] ) {
				$row['is_latest'] = true;
			} else {
				$row['is_latest'] = false;
			}
		}

		return $row;
	}

	/**
	 * Retrieves a list of diagnosis from the database.
	 *
	 * @param int $amount Amount of diagnosis reports to retrieve.
	 * @return array|null An associative array with the list of diagnosis, each having the fields `id` and `score`
	 */
	public function get_diagnosis_list( $amount ) {
		global $wpdb;

		$amount = absint( $amount );
		if ( $amount <= 0 ) {
				return array();
		}

		$table_name = $wpdb->prefix . $this->table_name;

		$query = $wpdb->prepare( "SELECT id, score FROM {$table_name} ORDER BY id DESC LIMIT %d", $amount ); /* phpcs:ignore */
		$rows = $wpdb->get_results( $query, ARRAY_A ); /* phpcs:ignore */

		return $rows;
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
}

global $syscoin_table_diagnostics;

$syscoin_table_diagnostics = new Syscoin_Table_Diagnostics();
