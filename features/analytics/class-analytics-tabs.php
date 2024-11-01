<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Analytics Tabs class which provides functions related to the
 * tabs of the analytics page of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . 'class-analytics-charts.php';

/**
 * This class handles analytics-related functionality.
 */
class Syscoin_Analytics_Tabs {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->charts = new Syscoin_Analytics_Charts();
	}

	/**
	 * Instance of AnalyticsCharts.
	 *
	 * @var Syscoin_Analytics_Charts
	 */
	private $charts;

	/**
	 * Contents of the "general" tab.
	 */
	public function general() {
		?>
			<div>
				<h1> 
					<?php esc_html_e( 'GENERAL_INFORMATION', 'syscoin' ); ?> 
				</h1>

				<p>
					<?php esc_html_e( 'GENERAL_INFORMATION_DESCRIPTION', 'syscoin' ); ?> 
				</p>
				
				<hr>

				<h2> 
					<?php esc_html_e( 'ACCESSES_TO_ENTIRE_WEBSITE', 'syscoin' ); ?>
				</h2>

				<div>
					<div style="display: flex; flex-direction: row;">
						<div style="width: 50%;">
							<table class="widefat">
								<tr>
									<td>
										<canvas id="dailyAccessLineChart"></canvas>
									</td>
								</tr>
							</table>
						</div>

						<div style="width: 50%; overflow: auto;" id="syscoin-analytics-general-summary">
							display_day_counts_table
						</div>
					</div>

					<hr>

					<h2> 
						<?php esc_html_e( 'MOST_REQUESTED_PAGES', 'syscoin' ); ?> 
					</h2>

					<div style="display: flex; flex-direction: row;">
						<div style="width: 50%;">
							<table class="widefat">
								<tr>
									<td>
										<canvas id="pageAccessPieChart"></canvas>
									</td>
								</tr>
							</table>
						</div>

						<div style="width: 50%; overflow: auto;" id="syscoin-analytics-general-pages">
							page_requests_table
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Contents of the "utm" tab.
	 */
	public function utm() {
		?>
		<div>
			<h1> 
				<?php esc_html_e( 'UTM_DATA', 'syscoin' ); ?> 
			</h1>

			<p>
				<?php esc_html_e( 'UTM_DATA_DESCRIPTION', 'syscoin' ); ?> 
			</p>

			<hr>

			<div style="display: flex; flex-direction: row">
				<div style="width: 20%;">
					<h3>Campaigns</h3>

					<div class="syscoin-small-donut-wrapper">
						<canvas id="utmCampaignsPieChart"></canvas>
					</div>

					<div id="syscoin-analytics-utm-campaign">
						campaigns_chart
					</div>
				</div>

				<div style="width: 20%;">
					<h3>Content</h3>

					<div class="syscoin-small-donut-wrapper">
						<canvas id="utmContentsPieChart"></canvas>
					</div>

					<div id="syscoin-analytics-utm-content">
						contents_chart
					</div>
				</div>

				<div style="width: 20%;">
					<h3>Mediums</h3>

					<div class="syscoin-small-donut-wrapper">
						<canvas id="utmMediumsPieChart"></canvas>
					</div>

					<div id="syscoin-analytics-utm-medium">
						mediums_chart
					</div>
				</div>

				<div style="width: 20%;">
					<h3>Sources</h3>

					<div class="syscoin-small-donut-wrapper">
						<canvas id="utmSourcesPieChart"></canvas>
					</div>

					<div id="syscoin-analytics-utm-source">
						sources_chart
					</div>
				</div>

				<div style="width: 20%;">
					<h3>Terms</h3>

					<div class="syscoin-small-donut-wrapper">
						<canvas id="utmTermsPieChart"></canvas>
					</div>

					<div id="syscoin-analytics-utm-term">
						terms_chart
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Contents of the "individual" tab.
	 */
	public function individual() {
		?>
		<div>
			<h1> 
				<?php esc_html_e( 'INDIVIDUAL_REQUESTS', 'syscoin' ); ?> 
			</h1>

			<p>
				<?php esc_html_e( 'INDIVIDUAL_REQUESTS_DESCRIPTION', 'syscoin' ); ?> 
			</p>

			<hr>

			<div style="display: flex;">
				<button id="syscoin-open-individual-requests" class="button syscoin-button">
					<?php esc_html_e( 'OPEN_TABLE', 'syscoin' ); ?>
				</button>

				<div style="margin-left: 10px;" class="syscoin-loading-spinner-container" id="syscoin-analytics-logs-loading">
					<div class="syscoin-loading-spinner"></div>

					<i> &nbsp; &bull; <?php esc_html_e( 'LOADING', 'syscoin' ); ?> </i>
				</div>
			</div>
		</div>
		<?php
	}
}

global $syscoin_analytics_tabs;

$syscoin_analytics_tabs = new Syscoin_Analytics_Tabs();
