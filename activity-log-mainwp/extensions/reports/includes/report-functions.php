<?php
/**
 * Report functions.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use WSAL\MainWPExtension as MWPAL_Extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Return sites for reports field.
 *
 * @return array
 */
function get_sites_for_select2() {
	$mwpal_extension  = MWPAL_Extension\mwpal_extension();
	$mwp_child_sites  = $mwpal_extension->settings->get_mwp_child_sites();
	$wsal_child_sites = array_filter(
		$mwpal_extension->settings->get_wsal_child_sites(),
		function ( $site_data ) {
			return array_key_exists( 'is_premium', (array) $site_data ) && true === $site_data['is_premium'];
		}
	);
	$wsal_site_ids    = array_keys( $wsal_child_sites );
	$wsal_sites       = array();

	// Add MainWP dashboard to the sites.
	$wsal_sites[] = (object) array(
		'id'   => 'dashboard',
		'text' => __( 'MainWP Dashboard', 'mwp-al-ext' ),
	);

	if ( $mwp_child_sites && is_array( $mwp_child_sites ) ) {
		foreach ( $mwp_child_sites as $site ) {
			$site_id = (int) $site['id'];

			if ( in_array( $site_id, $wsal_site_ids, true ) ) {
				$wsal_sites[] = (object) array(
					'id'   => $site_id,
					'text' => $site['name'],
				);
			}
		}
	}

	return $wsal_sites;
}

/**
 * Return event categories array.
 *
 * @return array
 */
function get_event_categories() {
	return array_keys( MWPAL_Extension\Helpers\DataHelper::get_categorized_events() );
}

/**
 * Get events by group.
 *
 * @param string $group - Group name.
 * @return array
 */
function get_events_by_group( $group ) {
	$all_events = MWPAL_Extension\Helpers\DataHelper::get_categorized_events();
	$events     = array();

	if ( isset( $all_events[ $group ] ) ) {
		$group_events = $all_events[ $group ];

		foreach ( $group_events as $event ) {
			array_push( $events, $event->type );
		}
	}

	return $events;
}

/**
 * Date format for date filters.
 */
function get_date_format() {
	return 'YYYY-MM-DD';
}

/**
 * Get reports from the options table.
 *
 * @return array
 */
function get_reports() {
	global $wpdb;

	$reports       = get_transient( MWPAL_PERIODIC_REPORTS );
	$stored_count  = (int) get_transient( MWPAL_PREPORTS_COUNT );
	$queried_count = $wpdb->query( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name LIKE %s LIMIT 50", '%mwpal-periodic-report-%' ) );

	if ( $stored_count !== $queried_count ) {
		$reports         = array();
		$queried_reports = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name LIKE %s LIMIT 50", '%mwpal-periodic-report-%' ) );

		if ( is_array( $queried_reports ) ) {
			foreach ( $queried_reports as $report ) {
				$reports[ $report->option_name ] = maybe_unserialize( $report->option_value );
			}
		}

		set_transient( MWPAL_PERIODIC_REPORTS, $reports, DAY_IN_SECONDS );
		set_transient( MWPAL_PREPORTS_COUNT, $queried_count, DAY_IN_SECONDS );
	}

	return $reports;
}

/**
 * Save report in the options table.
 *
 * @param string   $name   - Report name.
 * @param stdClass $report - Report object.
 */
function save_report( $name, $report ) {
	delete_transient( MWPAL_PERIODIC_REPORTS );
	delete_transient( MWPAL_PREPORTS_COUNT );
	MWPAL_Extension\mwpal_extension()->settings->update_option( MWPAL_PREPORT_PREFIX . $name, $report );
}

/**
 * Delete report from the options table.
 *
 * @param string $name - Report name.
 */
function delete_report( $name ) {
	delete_transient( MWPAL_PERIODIC_REPORTS );
	delete_transient( MWPAL_PREPORTS_COUNT );
	MWPAL_Extension\mwpal_extension()->settings->delete_option( MWPAL_PREPORT_PREFIX . $name );
}

/**
 * Get report from the options table.
 *
 * @param string $name - Report name (without options and reports prefix).
 * @return bool|stdClass False if report does not exist otherwise the report object.
 */
function get_report( $name ) {
	$reports = get_transient( MWPAL_PERIODIC_REPORTS );

	if ( false === $reports ) {
		$reports = get_reports();
	}

	if ( ! isset( $reports[ MWPAL_OPT_PREFIX . MWPAL_PREPORT_PREFIX . $name ] ) ) {
		return false;
	}

	return $reports[ MWPAL_OPT_PREFIX . MWPAL_PREPORT_PREFIX . $name ];
}

/**
 * Returns the instance of reports common class.
 *
 * @return Reports_Common
 */
function get_reports_common() {
	return Reports_Common::get_instance();
}

/**
 * Creates an unique random number
 *
 * @param int $size - The length of the number to generate. Defaults to 25.
 * @return string
 */
function generate_random_string( $size = 25 ) {
	$str = 'n0pqN865_3OUVristu47D_vwx012F_GH34_PQRST569_abcde753lm_yzAB109s_CfghD_E9h8sIJYZ_ijkKLM78WX';
	$str = str_shuffle( str_shuffle( str_shuffle( $str ) ) );
	$str = date( 'mdYHis' ) . '_' . substr( str_shuffle( $str ), 0, $size );
	return $str;
}
