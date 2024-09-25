<?php
/**
 * User roles filter.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

use \WSAL\MainWPExtension as MWPAL_Extension;
use \WSAL\MainWPExtension\Models\OccurrenceQuery as OccurrenceQuery;
use \WSAL\MainWPExtension\Adapters\MySQL\Meta as Meta;
use \WSAL\MainWPExtension\Adapters\MySQL\Occurrence as Occurrence;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User roles filter class.
 */
class Filter_EventType extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'Event Type', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'event-type' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		// Intialize single select widget class.
		$widget = new Widget_Select_Single( 'event-type', esc_html__( 'Event Type', 'mwp-al-ext' ), __( 'Select an Object to filter', 'mwp-al-ext' ) );
		// Get event objects.
		$event_objects = MWPAL_Extension\mwpal_extension()->alerts->get_event_type_data();

		// Add select options to widget.
		foreach ( $event_objects as $key => $event ) {
			$widget->Add( $event, $key );
		}

		return array( $widget );
	}

	/**
	 * Allow this filter to change the DB query according to the search value.
	 *
	 * @param OccurrenceQuery $query  - Database query for selecting occurrences.
	 * @param string          $prefix - The filter name (filter string prefix).
	 * @param array           $value  - The filter value.
	 * @throws \Exception Thrown when filter is unsupported.
	 */
	public function modify_query( $query, $prefix, $value ) {
		// Get DB connection array.
		$connection = MWPAL_Extension\mwpal_extension()->get_connector()->getAdapter( 'Occurrence' )->get_connection();
		$connection->set_charset( $connection->dbh, 'utf8mb4', 'utf8mb4_general_ci' );

		// Tables.
		$meta       = new Meta( $connection );
		$table_meta = $meta->GetTable(); // Metadata.
		$occurrence = new Occurrence( $connection );
		$table_occ  = $occurrence->GetTable(); // Occurrences.

		// Object search condition.
		$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='EventType' AND ( ";

		// Get the last event type.
		$last_value = end( $value );

		foreach ( $value as $event_type ) {
			if ( $last_value === $event_type ) {
				continue;
			} else {
				$sql .= "meta.value='$event_type' OR ";
			}
		}

		// Add placeholder for the last event type.
		$sql .= "meta.value='%s' ) )";

		// Check prefix.
		switch ( $prefix ) {
			case 'event-type':
				$query->addORCondition( array( $sql => $last_value ) );
				break;
			default:
				throw new \Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
