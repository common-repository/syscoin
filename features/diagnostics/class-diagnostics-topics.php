<?php // phpcs:ignore

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the Diagnostics class which provides functions related to the diagnostics features of the plugin.
 *
 * @package syscoin
 */

/**
 * This class handles diagnostics-related functionality.
 */
class Syscoin_Diagnostics_Topics {
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
	 * Echoes a row for the diagnostics table.
	 *
	 * @param array  $topics The topic.
	 * @param string $id The topic identifier.
	 */
	public function echo_row( $topics, $id ) {
		if ( ! isset( $topics[ $id ] ) ) {
			return;
		}

		$topic = $topics[ $id ];

		if ( isset( $topic['details'] ) ) {
			$details = $topic['details'];
		} else {
			$details = null;
		}

		static $counter = 0;
		++$counter;

		$status = $topic['status'];
		$text   = '';
		$class  = '';

		if ( 'OK' === $status ) {
			$text  = esc_html__( 'DIAG_TOPIC_OK', 'syscoin' );
			$class = 'syscoin-bg-green';
		} elseif ( 'WARNING' === $status ) {
			$text  = esc_html__( 'DIAG_TOPIC_WARNING', 'syscoin' );
			$class = 'syscoin-bg-yellow';
		} elseif ( 'CRITICAL' === $status ) {
			$text  = esc_html__( 'DIAG_TOPIC_CRITICAL', 'syscoin' );
			$class = 'syscoin-bg-red';
		}

		$placeholder_id = 'topic' . $counter;

		$expand_svg = plugin_dir_url( __FILE__ ) . '../../assets/syscoin-expand-down.svg';

		?>
		<!-- Clickable Topic Row -->
		<tr class="topic-row" data-target="<?php echo esc_attr( $placeholder_id ); ?>" style="cursor: pointer;">
			<td style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding: 20px;"> 
				<div style="font-size: 15px;">
					<?php echo esc_html( $this->get_title( $id ) ); ?>
				</div>

				<div style="display: flex; align-items: center;">
					<div style="width: min-content; padding: 5px 0; font-weight: 500;">
						<?php echo esc_html( $text ); ?>
					</div>

					<div class="<?php echo esc_attr( $class ); ?>" style="width: 15px; height: 15px; border-radius: 50%; margin-left: 10px;"></div>

					<img id="expand-<?php echo esc_html( $counter ); ?>" src="<?php echo esc_url( $expand_svg ); ?>" class="toggle-arrow" style="margin-left: 10px;">
				</div>
			</td>
		</tr>

		<!-- Hidden Content -->
		<tr id="<?php echo esc_attr( $placeholder_id ); ?>" class="placeholder-row" style="display: none;">
			<td style="padding: 10px; background-color: #f9f9f9; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
				<div style="margin: 5px 10px;">
					<?php echo $this->get_message( $id, $topic['status'], $details ); // phpcs:ignore ?> 
				</div>

				<?php if ( 'OK' !== $status ) { ?>
				<div style="margin-left: 20px;">
					<button class="button syscoin-button syscoin-diagnostics-fix" data-target="<?php echo esc_attr( $placeholder_id ); ?>"> 
						<?php esc_html_e( 'FIX_THIS_ISSUE', 'syscoin' ); ?> 
					</button>
				</div>
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Gets the title for a given topic id.
	 *
	 * @param string $id The topic's id.
	 */
	private function get_title( $id ) {
		switch ( $id ) {
			case 'version_plugin':
				return __( 'DIAG_TOPIC_OUR_PLUGIN_VERSION', 'syscoin' );

			case 'version_php':
				return __( 'DIAG_TOPIC_PHP_VERSION', 'syscoin' );

			case 'version_wp':
				return __( 'DIAG_TOPIC_WP_VERSION', 'syscoin' );

			case 'using_ssl':
				return __( 'DIAG_TOPIC_SSL', 'syscoin' );

			case 'fo_issues':
				return __( 'DIAG_TOPIC_FILE_OWNERSHIP', 'syscoin' );

			case 'se_allowed':
				return __( 'DIAG_TOPIC_SE_VISIBILITY', 'syscoin' );

			case 'themes_inactive':
				return __( 'DIAG_TOPIC_UNUSED_THEMES', 'syscoin' );

			case 'theme_outdated':
				return __( 'DIAG_TOPIC_THEME_VERSION', 'syscoin' );

			case 'plugins_inactive':
				return __( 'DIAG_TOPIC_UNUSED_PLUGINS', 'syscoin' );

			case 'plugins_outdated':
				return __( 'DIAG_TOPIC_OUTDATED_PLUGINS', 'syscoin' );

			case 'admin_count':
				return __( 'DIAG_TOPIC_ADMIN_COUNT', 'syscoin' );

			case 'admin_usernames':
				return __( 'DIAG_TOPIC_ADMIN_USERNAMES', 'syscoin' );

			case 'using_smtp':
				return __( 'DIAG_TOPIC_SMTP', 'syscoin' );

			case 'caching':
				return __( 'DIAG_TOPIC_CACHING', 'syscoin' );

			case 'error_logs':
				return __( 'DIAG_TOPIC_ERROR_LOGS', 'syscoin' );

			case 'cron':
				return __( 'DIAG_TOPIC_CRON', 'syscoin' );

			case 'open_dirs':
				return __( 'DIAG_TOPIC_OPEN_DIRS', 'syscoin' );

			default:
				return $id;
		}
	}

		/**
		 * Gets the message for a given topic id.
		 *
		 * @param string $id The topic's id.
		 * @param string $status The status of the topic. 'OK', 'WARNING' or 'CRITICAL'.
		 * @param array  $details The details of the topic.
		 */
	private function get_message( $id, $status, $details ) {
		switch ( $id ) {
			case 'version_plugin':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_OUR_PLUGIN_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_OUR_PLUGIN_WARNING', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-CURRENT-' => $details['current'],
						'-NEWEST-'  => $details['newest'],
					)
				);

			case 'version_php':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_PHP_VERSION_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_PHP_VERSION_WARNING', 'syscoin' );
						break;

					case 'CRITICAL':
						$message = __( 'DIAG_PHP_VERSION_CRITICAL', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-CURRENT-' => $details['current'],
						'-NEWEST-'  => $details['newest'],
					)
				);

			case 'version_wp':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_WP_VERSION_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_WP_VERSION_WARNING', 'syscoin' );
						break;

					case 'CRITICAL':
						$message = __( 'DIAG_WP_VERSION_CRITICAL', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-CURRENT-' => $details['current'],
						'-NEWEST-'  => $details['newest'],
					)
				);

			case 'using_ssl':
				switch ( $status ) {
					case 'OK':
						return __( 'DIAG_SSL_OK', 'syscoin' );
					case 'WARNING':
						return __( 'DIAG_SSL_WARNING', 'syscoin' );
				}

				break;

			case 'fo_issues':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_FILE_OWNERSHIP_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_FILE_OWNERSHIP_WARNING', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-DIRECTORIES_LIST-' => $details['issues'] ?? null,
					)
				);

			case 'se_allowed':
				switch ( $status ) {
					case 'OK':
						return __( 'DIAG_SE_VISIBILITY_OK', 'syscoin' );
					case 'WARNING':
						return __( 'DIAG_SE_VISIBILITY_WARNING', 'syscoin' );
				}

				break;

			case 'themes_inactive':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_UNUSED_THEMES_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_UNUSED_THEMES_WARNING', 'syscoin' );
						break;
				}

				$formatted_list = isset( $details['list_unused'] ) ? array_map(
					function ( $item ) {
						return "<b>{$item}</b>";
					},
					$details['list_unused']
				) : array();

				return $this->utils->apply_values(
					$message,
					array(
						'-COUNT-'              => $details['count_unused'],
						'-UNUSED_THEMES_LIST-' => $this->utils->array_to_html_list( $formatted_list ?? null ) ?? null,
					)
				);

			case 'theme_outdated':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_THEME_VERSION_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_THEME_VERSION_WARNING', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-THEME-'   => $details['theme'],
						'-CURRENT-' => $details['current'],
						'-NEWEST-'  => $details['newest'] ?? null,
					)
				);

			case 'plugins_inactive':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_UNUSED_PLUGINS_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_UNUSED_PLUGINS_WARNING', 'syscoin' );
						break;
				}

				$formatted_list = isset( $details['list_unused'] ) ? array_map(
					function ( $item ) {
						return "<b>{$item}</b>";
					},
					$details['list_unused']
				) : array();

				return $this->utils->apply_values(
					$message,
					array(
						'-COUNT-'       => $details['count_unused'],
						'-LIST_UNUSED-' => $this->utils->array_to_html_list( $formatted_list ?? null ) ?? null,
					)
				);

			case 'plugins_outdated':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_OUTDATED_PLUGINS_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_OUTDATED_PLUGINS_WARNING', 'syscoin' );
						break;
				}

				$list_outdated = isset( $details['list_outdated'] ) ? array_map(
					function ( $plugin ) {
						return "<b>{$plugin['name']}</b> ({$plugin['current_version']} ⟶ {$plugin['newest_version']})";
					},
					$details['list_outdated'],
				) : array();

				return $this->utils->apply_values(
					$message,
					array(
						'-COUNT_ENABLED-'  => $details['count_enabled'],
						'-COUNT_OUTDATED-' => $details['count_outdated'] ?? null,
						'-LIST_OUTDATED-'  => $this->utils->array_to_html_list( $list_outdated ?? null ) ?? null,
					)
				);

			case 'admin_count':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_ADMIN_COUNT_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_ADMIN_COUNT_WARNING', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-COUNT-' => $details['count'],
					)
				);

			case 'admin_usernames':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_ADMIN_USERNAMES_OK', 'syscoin' );
						break;

					case 'CRITICAL':
						$message = __( 'DIAG_ADMIN_USERNAMES_CRITICAL', 'syscoin' );
						break;
				}

				$formatted_list = isset( $details['insecure_users'] ) ? array_map(
					function ( $item ) {
						return "<b>{$item}</b>";
					},
					$details['insecure_users']
				) : array();

				return $this->utils->apply_values(
					$message,
					array(
						'-LIST_BAD_NAMES-' => $this->utils->array_to_html_list( $formatted_list ?? null ),
					)
				);

			case 'using_smtp':
				switch ( $status ) {
					case 'OK':
						return __( 'DIAG_SMTP_OK', 'syscoin' );
					case 'WARNING':
						return __( 'DIAG_SMTP_WARNING', 'syscoin' );
				}

				break;

			case 'caching':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_CACHING_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_CACHING_WARNING', 'syscoin' );
						break;

					case 'CRITICAL':
						$message = __( 'DIAG_CACHING_CRITICAL', 'syscoin' );
						break;
				}

				$caching_config = array( // TODO: improve localization.
					'<b>Cache headers:</b> ' . ( ( $details['headers'] ?? null ) ? __( 'YES', 'syscoin' ) : __( 'NO', 'syscoin' ) ),
					'<b>Object cache:</b> ' . ( ( $details['object'] ?? null ) ? __( 'YES', 'syscoin' ) : __( 'NO', 'syscoin' ) ),
					'<b>Cache Plugins:</b> ' . $details['plugins'] ?? null,
				);

				return $this->utils->apply_values(
					$message,
					array(
						'-CACHING_SITUATION-' => $this->utils->array_to_html_list( $caching_config ),
					)
				);

			case 'error_logs':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_ERROR_LOGS_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_ERROR_LOGS_WARNING', 'syscoin' );
						break;

					case 'CRITICAL':
						$message = __( 'DIAG_ERROR_LOGS_CRITICAL', 'syscoin' );
						break;
				}

				$message .=
					'<br> <br> <a class="button syscoin-button" href="/wp-admin/admin.php?page=syscoin-logs-viewer">'
					.
						__( 'ERROR_VIEWER', 'syscoin' )
					.
					'</a>';

				return $this->utils->apply_values(
					$message,
					array(
						'-WARNING_COUNT-' => $details['count_warning'],
						'-FATAL_COUNT-'   => $details['count_fatal'],
					)
				);

			case 'cron':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_CRON_OK', 'syscoin' );
						break;

					case 'WARNING':
						$message = __( 'DIAG_CRON_WARNING', 'syscoin' );
						break;
				}

				$list_overdue = isset( $details['list_overdue'] ) ? array_map(
					function ( $job ) {
						return "<b> {$job['hook']} </b> ({$job['due_in']})";
					},
					$details['list_overdue'],
				) : array();

				return $this->utils->apply_values(
					$message,
					array(
						'-OVERDUE_COUNT-' => $details['count_overdue'] ?? null,
						'-OVERDUE_LIST-'  => $this->utils->array_to_html_list( $list_overdue ?? null ),
					)
				);

			case 'open_dirs':
				switch ( $status ) {
					case 'OK':
						$message = __( 'DIAG_OPEN_DIRS_OK', 'syscoin' );
						break;

					case 'CRITICAL':
						$message = __( 'DIAG_OPEN_DIRS_CRITICAL', 'syscoin' );
						break;
				}

				return $this->utils->apply_values(
					$message,
					array(
						'-DIRS_COUNT-' => count( $details['open_dirs'] ?? array() ),
						'-DIRS_LIST-'  => $this->utils->array_to_html_list( $details['open_dirs'] ?? null ),
					)
				);
		}
	}
}
