<?php // phpcs:ignore
/**
 * Syscoin Plugin main file
 *
 * This file is part of the Syscoin Plugin for WordPress.
 * It includes the main initialization and configuration of the plugin.
 *
 * @package syscoin
 */

/*
 * Plugin Name: Syscoin - Support, Checkup, Optimization, AI, Reports & Analytics
 * Description: Keep your website healthy and efficient with Syscoin.
 * Version: 1.3.1
 * Author: Syscoin
 * License: GPL v2
 * Domain Path: /languages
 */

/**
 * Syscoin Plugin for WordPress
 * Copyright (c) 2024 Syscoin
 *
 * Released under the GPL v2 license.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SYSCOIN_PLUGIN_FILE' ) ) {
	define( 'SYSCOIN_PLUGIN_FILE', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . 'utils/class-environment.php';
require_once plugin_dir_path( __FILE__ ) . 'utils/class-backend.php';
require_once plugin_dir_path( __FILE__ ) . 'options/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'options/class-account.php';
require_once plugin_dir_path( __FILE__ ) . 'features/settings-page/class-settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'features/product-metabox/class-product-metabox.php';
require_once plugin_dir_path( __FILE__ ) . 'features/admin-script/class-admin-script.php';
require_once plugin_dir_path( __FILE__ ) . 'features/analytics/class-analytics.php';
require_once plugin_dir_path( __FILE__ ) . 'features/reports/class-reports.php';
require_once plugin_dir_path( __FILE__ ) . 'features/agency-footer/class-agency-footer.php';
require_once plugin_dir_path( __FILE__ ) . 'features/api/class-api.php';
require_once plugin_dir_path( __FILE__ ) . 'features/diagnostics/class-diagnostics.php';
require_once plugin_dir_path( __FILE__ ) . 'features/logs-viewer/class-logs-viewer.php';

/**
 * Enqueues the plugin styles.
 */
function syscoin_enqueue_plugin_styles() {
	global $syscoin_utils;

	$ver = $syscoin_utils->get_plugin_version();

	wp_enqueue_style( 'syscoin-global-styles', plugins_url( './styles.css', __FILE__ ), array(), $ver );
}

add_action( 'admin_enqueue_scripts', 'syscoin_enqueue_plugin_styles' );

/**
 * Load the text domain for the plugin.
 */
function rad_plugin_load_text_domain() {
	load_plugin_textdomain( 'syscoin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'rad_plugin_load_text_domain' );

/**
 * Register the plugin's activation in our servers.
 */
function plugin_activation_function() {
	global $syscoin_account;

	$syscoin_account->login_anon();
}

register_activation_hook( __FILE__, 'plugin_activation_function' );

/**
 * Register menu items for the plugin.
 */
function register_menu_items() {
	global $syscoin_env;
	global $syscoin_diagnostics;
	global $syscoin_analytics;
	global $syscoin_settings_page;
	global $syscoin_logs_viewer;
	global $syscoin_reports;

	$agency_formatted = $syscoin_env['AGENCY_F'];

	$parent_slug = 'syscoin';

	add_menu_page(
		$agency_formatted . ' Plugin',
		$agency_formatted,
		'manage_options',
		$parent_slug,
		'',
		plugins_url( 'assets/syscoin-icon-mini.png', __FILE__ ),
		30
	);

	add_submenu_page(
		$parent_slug,
		esc_html__( 'DIAGNOSTICS', 'syscoin' ),
		esc_html__( 'DIAGNOSTICS', 'syscoin' ),
		'manage_options',
		'syscoin-diagnostics',
		array( $syscoin_diagnostics, 'diagnostics_page_callback' )
	);

	add_submenu_page(
		$parent_slug,
		esc_html__( 'ANALYTICS', 'syscoin' ),
		esc_html__( 'ANALYTICS', 'syscoin' ),
		'manage_options',
		'syscoin-analytics-overview',
		array( $syscoin_analytics, 'analytics_page_callback' )
	);

	add_submenu_page(
		$parent_slug,
		esc_html__( 'ERROR_VIEWER', 'syscoin' ),
		esc_html__( 'ERROR_VIEWER', 'syscoin' ),
		'manage_options',
		'syscoin-logs-viewer',
		array( $syscoin_logs_viewer, 'logs_viewer_page_callback' ),
	);

	add_submenu_page(
		$parent_slug,
		esc_html__( 'Relatórios' ),
		esc_html__( 'Relatórios' ),
		'manage_options',
		'syscoin-reports',
		array( $syscoin_reports, 'reports_callback' ),
	);

	add_submenu_page(
		$parent_slug,
		esc_html__( 'SETTINGS', 'syscoin' ),
		esc_html__( 'SETTINGS', 'syscoin' ),
		'manage_options',
		'syscoin-settings',
		array( $syscoin_settings_page, 'settings_callback' ),
	);

	remove_submenu_page( $parent_slug, $parent_slug );
}

add_action( 'admin_menu', 'register_menu_items' );
