<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Analytics Charts class which provides functions related to the
 * values present in the analytics page of the plugin.
 *
 * @package syscoin
 */

require_once plugin_dir_path( __FILE__ ) . '../../tables/class-access-logs.php';

/**
 * This class handles analytics calculation.
 */
class Syscoin_Analytics_Values {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_table_access_logs;
		$this->access_logs = $syscoin_table_access_logs;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;
	}

	/**
	 * Reference to global variable $syscoin_table_access_logs.
	 *
	 * @var Syscoin_Access_Logs
	 */
	private $access_logs;

	/**
	 * Reference to global variable $syscoin_utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Returns `true` if analytics data is avaiable, `false` if not.
	 */
	public function is_data_available() {
		$day_counts = $this->access_logs->get_day_counts();

		if ( ! $day_counts ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Calculate amount of requests in a specified period.
	 *
	 * @param string $in_start The start of the period.
	 * @param string $in_end The end of the period. Default: `today`.
	 */
	public function requests_in_period( $in_start, $in_end = 'today' ) {
		$access_logs = $this->access_logs->get_access_logs( $in_start, $in_end );

		return $this->count_requests( $access_logs );
	}

	/**
	 * Count the amount of requests in an access logs array.
	 *
	 * @param array $access_logs Access logs.
	 */
	public function count_requests( $access_logs ) {
		$day_counts = $this->access_logs->calc_day_counts( $access_logs, '20 years ago', 'tomorrow' ); // TODO: improve this.

		$sum = 0;

		foreach ( $day_counts as $date => $value ) {
			$sum += $value;
		}

		return $sum;
	}

	/**
	 * Calculate average daily requests.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period. Default: `today`.
	 */
	public function average_daily_requests( $start, $end = 'today' ) {
		global $syscoin_utils;

		$day_counts = $this->access_logs->get_day_counts( $start, $end );

		return round( $syscoin_utils->calculate_array_average( $day_counts ), 2 );
	}

	/**
	 * Calculate average time between requests in seconds.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period. Default: `today`.
	 */
	public function average_time_between_requests( $start, $end = 'today' ) {
		$total_requests_sum = $this->requests_in_period( $start, $end );

		$seconds_day = 24 * 60 * 60;

		if ( $total_requests_sum > 0 ) {
			$average_time_between_requests = $seconds_day / $total_requests_sum;
			return round( $average_time_between_requests, 2 );
		} else {
			return 0;
		}
	}

	/**
	 * Calculate average time between requests in seconds.
	 *
	 * @param array $access_logs Access logs.
	 */
	public function calc_average_time_between_requests( $access_logs ) {
		$total_requests_sum = $this->count_requests( $access_logs );

		$seconds_day = 24 * 60 * 60;

		if ( $total_requests_sum > 0 ) {
			$average_time_between_requests = $seconds_day / $total_requests_sum;
			return round( $average_time_between_requests, 2 );
		} else {
			return 0;
		}
	}

	/**
	 * Get the amount of sales in the period indicated.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period. Default: `today`.
	 * @param array  $status The status of sales to filter. Optional.
	 */
	public function sales_in_period( $start, $end = 'today', $status = null ) {
		if ( ! $this->utils->is_woocommerce_active() ) {
			return array(
				'count' => null,
				'value' => null,
			);
		}

		$str_start_ltz = $this->utils->prepare_period_delimiter( $start, true );
		$str_end_ltz   = $this->utils->prepare_period_delimiter( $end, true );

		$args = array(
			'date_query' => array(
				'after'     => $str_start_ltz,
				'before'    => $str_end_ltz,
				'inclusive' => true,
			),
			'return'     => 'ids',
		);

		if ( ! is_null( $status ) ) {
			$args['status'] = $status;
		}

		$orders = wc_get_orders( $args ); // Note: WC saves order timestamps in UTC.

		$total = 0.0;

		foreach ( $orders as $order_id ) {
			$order  = wc_get_order( $order_id );
			$total += $order->get_total();
		}

		return array(
			'count' => count( $orders ),
			'value' => $total,
		);
	}

	/**
	 * Get a list of products sorted by sales amount in the specified period.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period. Default: `today`.
	 * @param array  $status The status of sales to filter. Optional.
	 */
	public function products_by_sales_in_period( $start, $end = 'today', $status = null ) {
		if ( ! $this->utils->is_woocommerce_active() ) {
			return null;
		}

		$str_start_ltz = $this->utils->prepare_period_delimiter( $start, true );
		$str_end_ltz   = $this->utils->prepare_period_delimiter( $end, true );

		$args = array(
			'date_created' => $str_start_ltz . '...' . $str_end_ltz,
			'limit'        => -1, // Fetch all orders.
		);

		if ( ! is_null( $status ) ) {
			$args['status'] = $status;
		}

		$order_query = new WC_Order_Query( $args );
		$orders      = $order_query->get_orders();

		$product_sales = array();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$quantity   = $item->get_quantity(); // Total amount for this item.

				if ( isset( $product_sales[ $product_id ] ) ) {
					$product_sales[ $product_id ] += $quantity;
				} else {
					$product_sales[ $product_id ] = $quantity;
				}
			}
		}

		// Sort products by sales amount descending.
		arsort( $product_sales );

		return $product_sales;
	}

	/**
	 * Get a list of products sorted by views in the specified period.
	 *
	 * @param array $access_logs Access logs.
	 * @return array List of products sorted by views.
	 */
	public function products_by_views( $access_logs ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$product_view_counts = array();

		foreach ( $query->posts as $product_id ) {
			$path = wp_parse_url( get_permalink( $product_id ) )['path'];

			$view_count = count(
				array_filter(
					$access_logs,
					function ( $log ) use ( $path ) {
						return isset( $log->requested_page ) && $log->requested_page === $path;
					}
				)
			);

			if ( $view_count > 0 ) {
				$product_view_counts[ $product_id ] = $view_count;
			}
		}

		arsort( $product_view_counts );

		return $product_view_counts;
	}

	/**
	 * Get the current total number of users.
	 */
	public function users_count() {
		$args = array(
			'role__in' => array( 'customer' ),
		);

		$user_query = new WP_User_Query( $args );

		$users = $user_query->get_results();

		return count( $users );
	}

	/**
	 * Get new users in period.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period.
	 */
	public function new_users_count( $start, $end ) {
		$start = $this->utils->prepare_period_delimiter( $start, true );
		$end   = $this->utils->prepare_period_delimiter( $end, true );

		$args = array(
			'role__in'   => array( 'customer' ),
			'date_query' => array(
				'after'     => $start,
				'before'    => $end,
				'inclusive' => true,
			),
		);

		$user_query = new WP_User_Query( $args );

		$users = $user_query->get_results();

		return count( $users );
	}

	/**
	 * Calculate conversion rate.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period.
	 */
	public function conversion_rate( $start, $end ) {
		$sales        = $this->sales_in_period( $start, $end );
		$access_count = $this->requests_in_period( $start, $end );

		$sales_count = $sales['count'];

		if ( $access_count > 0 ) {
			return round( $sales_count / $access_count, 2 );
		} else {
			return 0;
		}
	}

	/**
	 * Get a summary of analytics values for a specified period.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period.
	 */
	public function get_summary( $start, $end ) {
		$date_start_utc = $this->utils->timestring_to_utc( $start );
		$date_end_utc   = $this->utils->timestring_to_utc( $end );
		$logs           = $this->access_logs->get_access_logs( $date_start_utc, $date_end_utc );

		$most_sold_products = $this->products_by_sales_in_period( $start, $end );
		$most_view_products = $this->products_by_views( $logs );

		return array(
			'requests' => array(
				'period_avg_daily_count'  => $this->average_daily_requests( $start, $end ),
				'period_avg_time_between' => $this->average_time_between_requests( $start, $end ),
				'period_total_count'      => $this->requests_in_period( $start, $end ),
			),
			'products' => array(
				'period_most_sold_array'   => $most_sold_products,
				'period_most_viewed_array' => $most_view_products,
			),
			'users'    => array(
				'total_count'            => $this->users_count(),
				'period_new_count'       => $this->new_users_count( $start, $end ),
				'period_unique_visitors' => $this->get_unique_visitors_count( $start, $end ),
			),
			'pages'    => array(
				'period_most_requested' => $this->access_logs->get_requested_page_counts( $start, $end, false ),
			),
			'stats'    => array(
				'period_conversion_rate' => $this->conversion_rate( $start, $end ),
				'period_total_sales'     => $this->sales_in_period( $start, $end ),
			),
			'info'     => array(
				'products' => $this->get_mentioned_products( $most_sold_products + $most_view_products ),
			),
		);
	}

	/**
	 * Gets the count of unique visitors in the period provided.
	 *
	 * @param string $start The start of the period.
	 * @param string $end The end of the period.
	 */
	public function get_unique_visitors_count( $start, $end ) {
		return $this->access_logs->get_unique_visitors_count( $start, $end );
	}

	/**
	 * Returns a list of products present in the associative array provided, with product info as the value.
	 * Each product info will include the product name and a link to the product.
	 *
	 * @param array $products Associative array with product codes as keys and amount of sales as values.
	 * @return array Associative array with product details including name and link.
	 */
	public function get_mentioned_products( $products ) {
		$result = array();

		foreach ( $products as $product_id => $sales ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$result[ $product_id ] = array(
					'name' => $product->get_name(),
					'link' => $product->get_permalink(),
				);
			}
		}

		return $result;
	}
}

global $syscoin_analytics_values;

$syscoin_analytics_values = new Syscoin_Analytics_Values();
