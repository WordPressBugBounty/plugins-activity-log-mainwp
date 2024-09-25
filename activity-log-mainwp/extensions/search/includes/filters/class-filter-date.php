<?php
/**
 * Date filter.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

use \WSAL\MainWPExtension as MWPAL_Extension;
use \WSAL\MainWPExtension\Models\OccurrenceQuery as OccurrenceQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Date filter class.
 */
class Filter_Date extends Abstract_Filter {

	/**
	 * Get Name.
	 */
	public function get_name() {
		return __( 'Date', 'mwp-al-ext' );
	}

	/**
	 * Get Prefixes.
	 */
	public function get_prefixes() {
		return array( 'from', 'to', 'on' );
	}

	/**
	 * Get Widgets.
	 */
	public function get_widgets() {
		return array(
			new Widget_Date( 'from', esc_html__( 'Later than', 'mwp-al-ext' ) ),
			new Widget_Date( 'to', esc_html__( 'Earlier than', 'mwp-al-ext' ) ),
			new Widget_Date( 'on', esc_html__( 'On this day', 'mwp-al-ext' ) ),
		);
	}

	/**
	 * Allow this filter to change the DB query according to the search value.
	 *
	 * @param OccurrenceQuery $query  - Database query for selecting occurrences.
	 * @param string          $prefix - The filter name (filter string prefix).
	 * @param array           $value  - The filter value.
	 * @throws Exception Thrown when filter is unsupported.
	 */
	public function modify_query( $query, $prefix, $value ) {
		$date_format = 'Y-m-d';
		$date        = \DateTime::createFromFormat( $date_format, $value[0] );
		$date->setTime( 0, 0 ); // Reset time to 00:00:00.
		$date_string = $date->format( 'U' );

		switch ( $prefix ) {
			case 'from':
				$query->addCondition( 'created_on >= %s', $date_string );
				break;
			case 'to':
				$query->addCondition( 'created_on <= %s', strtotime( '+1 day -1 minute', $date_string ) );
				break;
			case 'on':
				/**
				 * We need to create a date range for events on a particular
				 * date.
				 *   1. From the hour 00:00:01
				 *   2. To the hour 23:59:59
				 */
				$query->addCondition( 'created_on >= %s', strtotime( '-1 day +1 day +1 second', $date_string ) ); // From the hour 00:00:01.
				$query->addCondition( 'created_on <= %s', strtotime( '+1 day -1 second', $date_string ) ); // To the hour 23:59:59.
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
