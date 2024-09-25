<?php
/**
 * User first name filter.
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
 * User first name filter.
 */
class Filter_User_Firstname extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'User' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'firstname' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		return array( new Widget_User_Firstname( 'firstname', 'First Name' ) );
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
		// Get DB connection array.
		$connection = MWPAL_Extension\mwpal_extension()->get_connector()->getAdapter( 'Occurrence' )->get_connection();
		$connection->set_charset( $connection->dbh, 'utf8mb4', 'utf8mb4_general_ci' );

		// Tables.
		$meta       = new Meta( $connection );
		$table_meta = $meta->GetTable(); // Metadata.
		$occurrence = new Occurrence( $connection );
		$table_occ  = $occurrence->GetTable(); // Occurrences.

		$sql   = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='FirstName' AND ( ";
		$count = count( $value );

		foreach ( $value as $ip ) {
			if ( $value[ $count - 1 ] === $ip ) {
				$sql .= "meta.value='%s'";
			} else {
				$sql .= "meta.value='$ip' OR ";
			}
		}

		$sql .= ' ) )';

		// Check prefix.
		switch ( $prefix ) {
			case 'firstname':
				$query->addORCondition( array( $sql => $value[ $count - 1 ] ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new \Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
