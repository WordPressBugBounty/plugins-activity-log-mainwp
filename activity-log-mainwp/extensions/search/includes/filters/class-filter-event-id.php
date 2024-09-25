<?php
/**
 * Events filter.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

use \WSAL\MainWPExtension\Models\OccurrenceQuery as OccurrenceQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Events filter class.
 */
class Filter_Event_ID extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return __( 'Event ID', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'event' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		return array( new Widget_Event_ID( 'event', esc_html__( 'Event ID', 'mwp-al-ext' ) ) );
	}

	/**
	 * Allow this filter to change the DB query according to the search value.
	 *
	 * @param OccurrenceQuery $query  - Database query for selecting occurrences.
	 * @param string          $prefix - The filter name (filter string prefix).
	 * @param string          $value  - The filter value (filter string suffix).
	 * @throws Exception Thrown when filter is unsupported.
	 */
	public function modify_query( $query, $prefix, $value ) {
		switch ( $prefix ) {
			case 'event':
				$query->addORCondition( array( 'alert_id = %s' => $value ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
