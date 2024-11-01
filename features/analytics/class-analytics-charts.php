<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Analytics Charts class which provides functions related to the charts present in the analytics page of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../../tables/class-access-logs.php';
require_once plugin_dir_path( __FILE__ ) . './class-analytics-values.php';

/**
 * This class handles analytics-related functionality.
 */
class Syscoin_Analytics_Charts {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_analytics_values;
		$this->values = $syscoin_analytics_values;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;
	}

	/**
	 * Reference to global instance of AnalyticsCharts.
	 *
	 * @var Syscoin_Analytics_Values
	 */
	private $values;

	/**
	 * Reference to global instance of Utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Display the access logs table.
	 *
	 * @param string $in_start The start date of the timeframe.
	 * @param string $in_end   The end date of the timeframe.
	 */
	public function display_access_logs_table( $in_start, $in_end ) {
		global $syscoin_table_access_logs;

		$access_logs = $syscoin_table_access_logs->get_access_logs( $in_start, $in_end );

		if ( ! $access_logs ) {
			?> 
				<p> <?php esc_html_e( 'NO_DATA_FOR_THIS_PERIOD', 'syscoin' ); ?> </p> 
			<?php

			return;
		}

		$formatted_start = ( new DateTime( $in_start ) )->format( 'Y-m-d H:i:s' );
		$formatted_end   = ( new DateTime( $in_end ) )->format( 'Y-m-d H:i:s' );

		?>
		<div>
			<p> 
				<?php
					echo esc_html__( 'THIS_TABLE_HAS_LOGS_FOR_PERIOD', 'syscoin' ) . ' ' . esc_html( $formatted_start . ' - ' . $formatted_end );
				?>
			</p>

			<table class="syscoin-utm-table">
				<thead>
					<tr>
						<th> <?php esc_html_e( 'ACCESS_TIME', 'syscoin' ); ?> </th>
						<th> <?php esc_html_e( 'USER_IP', 'syscoin' ); ?> </th>
						<th> <?php esc_html_e( 'REQUESTED_PAGE', 'syscoin' ); ?> </th>
						<th> Referer </th>
						<th> Source (UTM)</th>
						<th> Medium (UTM) </th>
						<th> Campaign (UTM) </th>
						<th> Term (UTM) </th>
						<th> Content (UTM) </th>
						<th> <?php esc_html_e( 'USER_AGENT', 'syscoin' ); ?> </th>
						<th> User ID </th>
					</tr>
				</thead>

				<tbody>
		<?php

		foreach ( $access_logs as $log ) {
			echo '<tr>';
			echo '<td>' . esc_html( $log->access_time ) . '</td>';
			echo '<td>' . esc_html( $log->user_ip ) . '</td>';
			echo '<td><a href="' . esc_html( $log->requested_page ) . '" target="_blank">' . esc_html( $log->requested_page ) . '</a></td>';
			echo '<td>' . esc_html( $log->referer ) . '</td>';
			echo '<td>' . esc_html( $log->utm_source ) . '</td>';
			echo '<td>' . esc_html( $log->utm_medium ) . '</td>';
			echo '<td>' . esc_html( $log->utm_campaign ) . '</td>';
			echo '<td>' . esc_html( $log->utm_term ) . '</td>';
			echo '<td>' . esc_html( $log->utm_content ) . '</td>';
			echo '<td>' . esc_html( $log->user_agent ) . '</td>';
			echo '<td>' . esc_html( $log->user_id ) . '</td>';
			echo '</tr>';
		}

		?>
				</tbody>
			</table>

			<p> <?php esc_html_e( 'END_OF_LOGS', 'syscoin' ); ?> </p>
		</div>
		<?php
	}

	/**
	 * Display the access logs table.
	 *
	 * @param array $access_logs Access logs.
	 */
	public function generate_page_requests_table( $access_logs ) {
		global $syscoin_table_access_logs;

		$page_counts = $syscoin_table_access_logs->calc_page_counts( $access_logs );

		if ( ! $page_counts ) {
			return "<div style='margin: 25px'> <p>" . esc_html__( 'NO_DATA_FOR_THIS_PERIOD', 'syscoin' ) . '</p> </div>';
		}

		$rows = array();

		foreach ( $page_counts as $requested_page => $occurrences ) {
			array_push(
				$rows,
				array(
					'page'     => $this->utils->get_color_demo( $this->utils->get_hash_color( $requested_page ) ) . $this->utils->make_clickable_if_url( $requested_page ),
					'requests' => $occurrences,
				)
			);
		}

		$output = '<div style="display: flex; justify-content: center;">';

		$output .= $this->utils->generate_table(
			array(
				'page'     => esc_html__( 'PAGE', 'syscoin' ),
				'requests' => esc_html__( 'SESSIONS', 'syscoin' ),
			),
			$rows,
			false
		);

		$output .= '</div>';

		return $output;
	}

	/**
	 * Display the access logs table.
	 *
	 * @param array $access_logs Access logs for the period.
	 * @param array $day_counts  Day counts.
	 */
	public function generate_summary_table( $access_logs, $day_counts ) {
		$requests_today                = $this->values->requests_in_period( 'today', 'tomorrow - 1 second' );
		$requests_in_period            = $this->values->count_requests( $access_logs );
		$average_daily_requests        = round( $this->utils->calculate_array_average( $day_counts ), 2 );
		$average_time_between_requests = $this->values->calc_average_time_between_requests( $access_logs );

		ob_start();

		if ( ! $this->values->is_data_available() ) {
			?>
				<p> <?php esc_html_e( 'NO_DATA', 'syscoin' ); ?> </p> 
			<?php

			return ob_get_clean();
		}

		?>
		<div class="syscoin-4x4-grid" style="padding: 25px;">
			<div>
				<?php
				// phpcs:ignore
				echo $this->utils->generate_table(
					array( 'total' => esc_html__( 'TOTAL_ACCESSES_TODAY', 'syscoin' ) ),
					array( array( 'total' => esc_html( $requests_today ) ) )
				)
				?>
			</div>

			<div>
				<?php
				// phpcs:ignore
				echo $this->utils->generate_table(
					array( 'total' => esc_html__( 'TOTAL_ACCESSES_IN_PERIOD', 'syscoin' ) ),
					array( array( 'total' => esc_html( $requests_in_period ) ) )
				)
				?>
			</div>

			<div>
				<?php
				// phpcs:ignore
				echo $this->utils->generate_table(
					array( 'total' => esc_html__( 'AVERAGE_ACCESSES_PER_DAY', 'syscoin' ) ),
					array( array( 'total' => esc_html( $average_daily_requests ) ) )
				)
				?>
			</div>

			<div>
				<?php
				// phpcs:ignore
				echo $this->utils->generate_table(
					array( 'total' => esc_html__( 'AVERAGE_TIME_BETWEEN_ACCESSES', 'syscoin' ) ),
					array( array( 'total' => esc_html( $average_time_between_requests ) . 's' ) )
				)
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Display the UTM count table.
	 *
	 * @param string $type_f The type of data to display.
	 * @param string $type The type of UTM data.
	 * @param array  $utm The associative array that is returned by `Access_Logs->calc_utm_overview`. Should be retrieved first.
	 */
	public function generate_utm_count_table( $type_f, $type, $utm ) {
		if ( ! $utm ) {
			ob_start();

			?>
				<p> <?php esc_html_e( 'NO_DATA', 'syscoin' ); ?> </p> 
			<?php

			return ob_get_clean();
		}

		$counts = $utm[ $type ];

		$rows = array();

		foreach ( $counts as $group => $occurrences ) {
			array_push(
				$rows,
				array(
					'page'     => $this->utils->get_color_demo( $this->utils->get_hash_color( $group ) ) . $this->utils->make_clickable_if_url( $group ),
					'requests' => $occurrences,
				)
			);
		}

		$output = '<div style="margin: 25px;">';

		$output .= $this->utils->generate_table(
			array(
				'page'     => esc_html__( 'PAGE', 'syscoin' ),
				'requests' => esc_html__( 'REQUESTS', 'syscoin' ),
			),
			$rows,
			false
		);

		$output .= '</div>';

		return $output;
	}
}

global $syscoin_analytics_charts;

$syscoin_analytics_charts = new Syscoin_Analytics_Charts();
