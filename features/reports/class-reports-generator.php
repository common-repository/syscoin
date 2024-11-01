<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress
 * It includes the Syscoin_Reports class which provides functions related
 * to reports message generations.
 *
 * @package syscoin
 */

/**
 * This class represents the reports message generator.
 */
class Syscoin_Reports_Generator {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		global $syscoin_analytics_values;
		$this->analytics = $syscoin_analytics_values;

		global $syscoin_utils;
		$this->utils = $syscoin_utils;

		global $syscoin_table_access_logs;
		$this->access_logs = $syscoin_table_access_logs;
	}

	/**
	 * Reference to global variable $syscoin_utils.
	 *
	 * @var Syscoin_Utils
	 */
	private $utils;

	/**
	 * Reference to the global instance of Syscoin_Analytics_Values.
	 *
	 * @var Syscoin_Analytics_Values
	 */
	private $analytics;

	/**
	 * Reference to global instance of Syscoin_Access_Logs
	 *
	 * @var Syscoin_Access_Logs
	 */
	private $access_logs;

	/**
	 * Generates a report string.
	 *
	 * @param string $frequency The frequency of the report.
	 * @param string $store The name of the store.
	 * @param array  $topics The sections to be present in the plugin.
	 */
	public function generate( $frequency, $store, $topics ) {
		$header = $this->get_header( $frequency, $store );
		$footer = $this->get_footer();

		$body = $this->get_statistics( $frequency, $topics );

		if ( null === $body ) {
			return null;
		}

		$message = $header . "\n\n" . $body . "\n\n" . $footer;

		return $message;
	}

	/**
	 * Selects a greeting based on the current time.
	 *
	 * @param string $frequency The frequency.
	 * @param string $store The store name.
	 */
	private function get_header( $frequency, $store ) {
		$hour = (int) $this->utils->create_date()->format( 'H' );

		if ( $hour >= 0 && $hour < 5 ) {
			$greet = 'Boa madrugada!';
		} elseif ( $hour >= 5 && $hour < 12 ) {
			$greet = 'Bom dia!';
		} elseif ( $hour >= 12 && $hour < 18 ) {
			$greet = 'Boa tarde!';
		} elseif ( $hour >= 18 && $hour < 24 ) {
			$greet = 'Boa noite!';
		} else {
			$greet = 'Olá!'; // should not happen.
		}

		$time_indicator = $greet . " 👋\n";

		$date      = $this->utils->create_date();
		$indicator = str_replace( '<store>', $store, 'Este é o relatório <freq> da loja *<store>*' );

		if ( 'hourly' === $frequency ) {
			$indicator = str_replace( '<freq>', 'programado', $indicator );
			$segue     = 'Na última hora:';
		} elseif ( 'daily' === $frequency ) {
			$date->modify( '-1 day' ); // Subtract one day.
			$formatted_date = $date->format( 'd/m/Y' );

			$indicator = str_replace( '<freq>', 'diário', $indicator );
			$segue     = "Ontem, dia $formatted_date:";
		} elseif ( 'every3days' === $frequency ) {
			$date->modify( '-3 days' ); // Subtract three days.
			$formatted_date = $date->format( 'd/m/Y' );
			$end_date       = $date->modify( '+2 days' )->format( 'd/m/Y' );

			$indicator = str_replace( '<freq>', 'tri-diário', $indicator );
			$segue     = "De $formatted_date a $end_date:";
		} elseif ( 'weekly' === $frequency ) {
			$date->modify( '-1 week' ); // Subtract one week.
			$formatted_date = $date->format( 'd/m/Y' );
			$end_date       = $date->modify( '+6 days' )->format( 'd/m/Y' );

			$indicator = str_replace( '<freq>', 'semanal', $indicator );
			$segue     = "Na semana de $formatted_date a $end_date:";
		} else { // should not happen.
			$indicator = 'Frequência inesperada ' . $frequency;
			$segue     = '(!!!)';
		}

		$frequency_indicator = $indicator . " 📝\n" . $segue;

		return $time_indicator . $frequency_indicator;
	}

	/**
	 * Get the footer for the report message.
	 */
	private function get_footer() {
		global $syscoin_env;

		return $this->utils->apply_values(
			'Para obter informações mais detalhadas, visite a tela de Analytics da <agency> no painel do WordPress da sua loja.',
			array(
				'<agency>' => $syscoin_env['AGENCY_F'],
			)
		);
	}

	/**
	 * Gets the statistics for the report string.
	 *
	 * @param string $frequency The report frequency.
	 * @param array  $topics The sections allowed in the report.
	 */
	private function get_statistics( $frequency, $topics ) {
		if ( 'hourly' === $frequency ) {
			$timeframe_start = '1 hour ago';
			$delta           = '1 hour';
		} elseif ( 'daily' === $frequency ) {
			$timeframe_start = '1 day ago';
			$delta           = '1 day';
		} elseif ( 'every3days' === $frequency ) {
			$timeframe_start = '3 days ago';
			$delta           = '3 days';
		} elseif ( 'weekly' === $frequency ) {
			$timeframe_start = '1 week ago';
			$delta           = '1 week';
		} else { // should not happen.
			$timeframe_start = '3 months ago';
			$delta           = '3 months';
		}

		$curr = $this->calc_stats( $timeframe_start, 'today' );
		$prev = $this->calc_stats( $timeframe_start . ' -' . $delta, 'today -' . $delta );

		$lines = array(
			'access_count'       => $this->generate_str_requests( $frequency, $curr['requests'], $prev['requests'] ),
			'sales_count'        => $this->generate_str_sales( $frequency, $curr['new_sales'], $prev['new_sales'] ),
			'income'             => $this->generate_str_income( $frequency, $curr['new_sales_value'], $prev['new_sales_value'] ),
			'utm'                => $this->generate_str_utm( $curr['utm'] ),
			'most_acessed_pages' => $this->generate_str_most_accessed( $curr['most_viewed_pages'] ),
			'most_viewed_prods'  => $this->generate_str_most_viewed( $curr['most_viewed_prods'], $prev['most_viewed_prods'], $frequency ),
			'most_sold_prods'    => $this->generate_str_most_sold( $curr['most_sold_prods'], $prev['most_sold_prods'], $frequency ),
		);

		$lines = array_filter(
			$lines,
			function ( $value, $key ) use ( $topics ) {
				$has_data       = null !== $value;
				$should_display = ( ! is_null( $topics ) ) && in_array( $key, $topics, true );

				return $has_data && $should_display;
			},
			ARRAY_FILTER_USE_BOTH
		);

		$message = implode( "\n\n", $lines );

		if ( '' === $message ) {
			return null;
		}

		return rtrim( $message, "\n\n" );
	}

	/**
	 * Calculate values to be used in the `get_statistics` method.
	 *
	 * @param string $in_start Start of the period.
	 * @param string $in_end End of the period.
	 */
	private function calc_stats( $in_start, $in_end ) {
		$date_start_utc = $this->utils->timestring_to_utc( $in_start );
		$date_end_utc   = $this->utils->timestring_to_utc( $in_end );
		$logs           = $this->access_logs->get_access_logs( $date_start_utc, $date_end_utc );
		$logs_unique    = $this->utils->filter_unique_accesses( $logs );

		$stat_new_sales = $this->analytics->sales_in_period( $in_start, $in_end, null );

		return array(
			'requests'          => $this->analytics->count_requests( $logs_unique ),
			'new_sales'         => $stat_new_sales['count'],
			'new_sales_value'   => $stat_new_sales['value'],
			'utm'               => $this->access_logs->calc_utm_overview( $logs_unique ),
			'most_viewed_pages' => $this->access_logs->calc_page_counts( $logs_unique, false ),
			'most_viewed_prods' => $this->analytics->products_by_views( $logs_unique ),
			'most_sold_prods'   => $this->analytics->products_by_sales_in_period( $in_start, $in_end ),
		);
	}

	/**
	 * Get previous period descriptor
	 *
	 * @param string $frequency The frequency.
	 */
	private function get_previous_desc( $frequency ) {
		if ( 'hourly' === $frequency ) {
			return 'na hora anterior';
		} elseif ( 'daily' === $frequency ) {
			return 'anteontem';
		} elseif ( 'every3days' === $frequency ) {
			return 'nos 3 dias anteriores';
		} elseif ( 'weekly' === $frequency ) {
			return 'na semana anterior';
		} else { // should not happen.
			return 'no último período';
		}
	}

	/**
	 * Get alternative previous period descriptor
	 *
	 * @param string $frequency The frequency.
	 */
	private function get_previous_desc_alt( $frequency ) {
		if ( 'hourly' === $frequency ) {
			return 'à hora anterior';
		} elseif ( 'daily' === $frequency ) {
			return 'a anteontem';
		} elseif ( 'every3days' === $frequency ) {
			return 'aos 3 dias anteriores';
		} elseif ( 'weekly' === $frequency ) {
			return 'à semana anterior';
		} else { // should not happen.
			return 'ao último período';
		}
	}

	/**
	 * Generate sales string.
	 *
	 * @param string $frequency The frequency.
	 * @param mixed  $current Data from this period.
	 * @param mixed  $previous Data from the previous period.
	 */
	private function generate_str_requests( $frequency, $current, $previous ) {
		if ( 0 === $current ) {
			return '🌐 Não houve acessos :(';
		}

		$previous_desc     = $this->get_previous_desc( $frequency );
		$previous_desc_alt = $this->get_previous_desc_alt( $frequency );

		if ( $previous === $current ) {
			$str = $this->utils->apply_values(
				'🌐 A sua loja teve <current> acessos únicos, igual <previous_desc_alt>',
				array(
					'<current>'           => $current,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);
		} elseif ( $previous > 0 ) {
			$str = $this->utils->apply_values(
				'🌐 A sua loja teve <current> acessos únicos, <change>% em relação <previous_desc_alt> (<previous> acessos <previous_desc>)',
				array(
					'<current>'           => $current,
					'<previous>'          => $previous,
					'<change>'            => sprintf( '%+d', round( $this->utils->calculate_percentage_difference( $previous, $current ) ) ),
					'<previous_desc>'     => $previous_desc,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);

			return $str;
		} else {
			$str = $this->utils->apply_values(
				'🌐 A sua loja teve <current> acessos (<previous> acessos <previous_desc>)',
				array(
					'<current>'       => $current,
					'<previous>'      => $previous,
					'<previous_desc>' => $previous_desc,
				)
			);

			return $str;
		}
	}

	/**
	 * Generate requests string.
	 *
	 * @param string $frequency The frequency.
	 * @param mixed  $current Data from this period.
	 * @param mixed  $previous Data from the previous period.
	 */
	private function generate_str_sales( $frequency, $current, $previous ) {
		if ( 0 === $current ) {
			return '🛒 Nenhuma venda foi realizada :(';
		}

		$previous_desc     = $this->get_previous_desc( $frequency );
		$previous_desc_alt = $this->get_previous_desc_alt( $frequency );

		if ( $previous === $current ) {
			$str = $this->utils->apply_values(
				'🛒 Foram feitas <current> novas vendas, igual <previous_desc_alt>',
				array(
					'<current>'           => $current,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);
		} elseif ( $previous > 0 ) {
			$str = $this->utils->apply_values(
				'🛒 Foram feitas <current> novas vendas, <change>% em relação <previous_desc_alt> (<previous> novas vendas <previous_desc>)',
				array(
					'<current>'           => $current,
					'<previous>'          => $previous,
					'<change>'            => sprintf( '%+d', round( $this->utils->calculate_percentage_difference( $previous, $current ) ) ),
					'<previous_desc>'     => $previous_desc,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);

			return $str;
		} else {
			$str = $this->utils->apply_values(
				'🛒 Foram feitas <current> novas vendas (<previous> novas vendas <previous_desc>)',
				array(
					'<current>'       => $current,
					'<previous>'      => $previous,
					'<previous_desc>' => $previous_desc,
				)
			);

			return $str;
		}
	}

	/**
	 * Generate completed sales string.
	 *
	 * @param string $frequency The frequency.
	 * @param mixed  $current Data from this period.
	 * @param mixed  $previous Data from the previous period.
	 */
	private function generate_str_completed_sales( $frequency, $current, $previous ) {
		$previous_desc     = $this->get_previous_desc( $frequency );
		$previous_desc_alt = $this->get_previous_desc_alt( $frequency );

		if ( $previous === $current ) {
			$str = $this->utils->apply_values(
				'📦 <current> vendas foram marcadas como completas, igual <previous_desc_alt>',
				array(
					'<current>'           => $current,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);
		} elseif ( $previous > 0 ) {
			$str = $this->utils->apply_values(
				'📦 <current> vendas foram marcadas como completas, <change>% em relação <previous_desc_alt> (<previous> <previous_desc>)',
				array(
					'<current>'           => $current,
					'<previous>'          => $previous,
					'<change>'            => sprintf( '%+d', round( $this->utils->calculate_percentage_difference( $previous, $current ) ) ),
					'<previous_desc>'     => $previous_desc,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);

			return $str;
		} else {
			$str = $this->utils->apply_values(
				'📦 <current> vendas foram marcadas como completas (<previous> <previous_desc>)',
				array(
					'<current>'       => $current,
					'<previous>'      => $previous,
					'<previous_desc>' => $previous_desc,
				)
			);

			return $str;
		}
	}

	/**
	 * Generate income string.
	 *
	 * @param string $frequency The frequency.
	 * @param mixed  $current Data from this period.
	 * @param mixed  $previous Data from the previous period.
	 */
	private function generate_str_income( $frequency, $current, $previous ) {
		if ( ! $this->utils->is_woocommerce_active() ) {
			return null;
		}

		if ( 0.0 === $current ) {
			return null;
		}

		$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol() );

		$previous_desc     = $this->get_previous_desc( $frequency );
		$previous_desc_alt = $this->get_previous_desc_alt( $frequency );

		if ( $previous === $current ) {
			$str = $this->utils->apply_values(
				'💵 A loja faturou <current>, igual <previous_desc_alt>',
				array(
					'<current>'           => $current,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);
		} elseif ( $previous > 0 ) {
			$str = $this->utils->apply_values(
				'💵 A loja faturou <current>, <change>% em relação <previous_desc_alt> (<previous> faturado <previous_desc>)',
				array(
					'<current>'           => $currency_symbol . ' ' . $current,
					'<previous>'          => $currency_symbol . ' ' . $previous,
					'<change>'            => sprintf( '%+d', round( $this->utils->calculate_percentage_difference( $previous, $current ) ) ),
					'<previous_desc>'     => $previous_desc,
					'<previous_desc_alt>' => $previous_desc_alt,
				)
			);

			return $str;
		} else {
			$str = $this->utils->apply_values(
				'💵 A loja faturou <current> (<previous> faturado <previous_desc>)',
				array(
					'<current>'       => $currency_symbol . ' ' . $current,
					'<previous>'      => $currency_symbol . ' ' . $previous,
					'<previous_desc>' => $previous_desc,
				)
			);

			return $str;
		}
	}

	/**
	 * Generate UTM overview string.
	 *
	 * @param array $current The UTM data overview for the current period.
	 */
	private function generate_str_utm( $current ) {
		$message = '';

		if ( ! $this->is_utm_category_empty( $current, 'source' ) ) {
			$message .= '🔗 Suas maiores fontes de tráfego foram:';
			$message .= $this->generate_str_list( $current['source'], 'source' );

			$message .= "\n\n";
		}

		if ( ! $this->is_utm_category_empty( $current, 'campaign' ) ) {
			$message .= '📢 As campanhas que mais trouxeram tráfego foram:';
			$message .= $this->generate_str_list( $current['campaign'], 'campaign' );
		}

		if ( '' === $message ) {
			return null;
		} else {
			return $message;
		}
	}

	/**
	 * Generate a list of an associative array in the format item => amount.
	 *
	 * @param array  $assoc_array The array.
	 * @param string $title The title of the array.
	 * @param array  $assoc_array_previous Optional. Equivalent to the array, but for the previous period.
	 */
	private function generate_str_list( $assoc_array, $title, $assoc_array_previous = null ) {
		$message     = '';
		$max_visible = 3;
		$counter     = 0;

		foreach ( $assoc_array as $tag => $count ) {
			if ( 'No ' . $title === $tag ) {
				continue;
			}

			$percentage_change = '';
			if ( null !== $assoc_array_previous && isset( $assoc_array_previous[ $tag ] ) ) {
				$previous_count = $assoc_array_previous[ $tag ];

				if ( $previous_count > 0 ) {
					$change            = ( $count - $previous_count ) / $previous_count * 100;
					$percentage_change = sprintf( ' (%.2f%% change)', $change );
				} else {
					$percentage_change = ' (novo item)';
				}
			}

			if ( $counter < $max_visible ) {
					$message .= "\n- " . ucfirst( $tag ) . ' (' . $count . ')' . $percentage_change . ';';
					++$counter;
			} else {
					break; // Stop adding more tags when maximum visible limit is reached.
			}
		}

		$remaining = count( $assoc_array ) - $max_visible;

		if ( $remaining > 0 ) {
			$message .= "\ne " . $remaining . ' outros';
		}

		$message = rtrim( $message, '; ' ); // Remove the previous semicolon and space.

		return $message;
	}

	/**
	 * Checks if the utm category is empty.
	 *
	 * @param array  $assoc_array The array.
	 * @param string $category_name The category name.
	 */
	private function is_utm_category_empty( $assoc_array, $category_name ) {
		if ( empty( $assoc_array[ $category_name ] ) ) {
			return true;
		}

		if ( count( $assoc_array[ $category_name ] ) === 1 ) {
			if ( isset( $assoc_array[ $category_name ][ 'No ' . $category_name ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate most accessed pages string.
	 *
	 * @param mixed $current The current data.
	 */
	private function generate_str_most_accessed( $current ) {
		if ( count( $current ) === 0 ) {
			return null;
		}

		$max_visible = 5;

		$message = '📄 As páginas mais visitadas da loja foram:';
		$counter = 0;

		foreach ( $current as $page => $count ) {
			if ( $counter < $max_visible ) {
				$message .= "\n- " . $page . ' (' . $count . ' visitas)';
				++$counter;
			} else {
				break;
			}
		}

		if ( count( $current ) > $max_visible ) {
			$remaining = count( $current ) - $max_visible;
			$message  .= "\ne " . $remaining . ' outras páginas';
		}

		return $message;
	}

	/**
	 * Generate most sold products string.
	 *
	 * @param mixed  $current The current data.
	 * @param mixed  $previous Data from previous period.
	 * @param string $frequency The frequency.
	 */
	private function generate_str_most_sold( $current, $previous, $frequency ) {
		if ( ! $this->utils->is_woocommerce_active() ) {
			return null;
		}

		if ( count( $current ) === 0 ) {
			return null;
		}

		$previous_desc_alt = $this->get_previous_desc_alt( $frequency );

		$max_visible = 5;

		$message = '📦 Os produtos mais vendidos foram:';
		$counter = 0;

		foreach ( $current as $product => $count ) {
			if ( $counter < $max_visible ) {
				try {
					$name = wc_get_product( $product )->get_name();
				} catch ( Exception $e ) {
					$name = '';
				}

				if ( isset( $previous[ $product ] ) && 0 !== $previous[ $product ] ) {
					if ( $previous[ $product ] === $count ) {
						$message .= $this->utils->apply_values(
							"\n- [#<product_id>] <product_name>: <count> vendas, igual <previous_desc_alt>",
							array(
								'<product_id>'        => $product,
								'<product_name>'      => $name,
								'<count>'             => $count,
								'<previous_desc_alt>' => $previous_desc_alt,
							)
						);
					} else {
						$message .= $this->utils->apply_values(
							"\n- [#<product_id>] <product_name>: <count> vendas, <change>% em relação <previous_desc_alt> (<previous_count>)",
							array(
								'<product_id>'        => $product,
								'<product_name>'      => $name,
								'<count>'             => $count,
								'<change>'            => sprintf( '%+d', round( $this->utils->calculate_percentage_difference( $previous[ $product ], $count ) ) ),
								'<previous_desc_alt>' => $previous_desc_alt,
								'<previous_count>'    => $previous[ $product ],
							)
						);
					}
				} else {
					$message .= $this->utils->apply_values(
						"\n- [#<product_id>] <product_name>: <count> vendas",
						array(
							'<product_id>'   => $product,
							'<product_name>' => $name,
							'<count>'        => $count,
						)
					);
				}

				++$counter;
			} else {
				break;
			}
		}

		if ( count( $current ) > $max_visible ) {
			$remaining = count( $current ) - $max_visible;
			$message  .= "\ne " . $remaining . ' outros produtos';
		}

		return $message;
	}

	/**
	 * Generate most viewed products string.
	 *
	 * @param mixed  $current The current data.
	 * @param mixed  $previous Data from previous period.
	 * @param string $frequency The frequency.
	 */
	private function generate_str_most_viewed( $current, $previous, $frequency ) {
		if ( ! $this->utils->is_woocommerce_active() ) {
			return null;
		}

		if ( count( $current ) === 0 ) {
			return null;
		}

		$previous_desc_alt = $this->get_previous_desc_alt( $frequency );

		$max_visible = 5;

		$message = '👀 Os produtos mais visualizados foram:';
		$counter = 0;

		foreach ( $current as $product => $count ) {
			if ( $counter < $max_visible ) {
				try {
					$name = wc_get_product( $product )->get_name();
				} catch ( Exception $e ) {
					$name = '';
				}

				if ( isset( $previous[ $product ] ) && 0 !== $previous[ $product ] ) {
					if ( $previous[ $product ] === $count ) {
						$message .= $this->utils->apply_values(
							"\n- [#<product_id>] <product_name>: <count> visualizações, igual <previous_desc_alt>",
							array(
								'<product_id>'        => $product,
								'<product_name>'      => $name,
								'<count>'             => $count,
								'<previous_desc_alt>' => $previous_desc_alt,
							)
						);
					} else {
						$message .= $this->utils->apply_values(
							"\n- [#<product_id>] <product_name>: <count> visualizações, <change>% em relação <previous_desc_alt> (<previous_count>)",
							array(
								'<product_id>'        => $product,
								'<product_name>'      => $name,
								'<count>'             => $count,
								'<change>'            => sprintf( '%+d', round( $this->utils->calculate_percentage_difference( $previous[ $product ], $count ) ) ),
								'<previous_desc_alt>' => $previous_desc_alt,
								'<previous_count>'    => $previous[ $product ],
							)
						);
					}
				} else {
					$message .= $this->utils->apply_values(
						"\n- [#<product_id>] <product_name>: <count> visualizações",
						array(
							'<product_id>'   => $product,
							'<product_name>' => $name,
							'<count>'        => $count,
						)
					);
				}

				++$counter;
			} else {
				break;
			}
		}

		if ( count( $current ) > $max_visible ) {
			$remaining = count( $current ) - $max_visible;
			$message  .= "\ne " . $remaining . ' outros produtos';
		}

		return $message;
	}
}
