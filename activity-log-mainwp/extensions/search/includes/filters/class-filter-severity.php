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
class Filter_Severity extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'Severity', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'severity' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		// Intialize single select widget class.
		$widget = new Widget_Select_Single( 'severity', esc_html__( 'Severity', 'mwp-al-ext' ), __( 'Select a Severity to filter', 'mwp-al-ext' ) );

		// Get WP user roles.
		$severities = $this->get_severities();

		// Add select options to widget.
		foreach ( $severities as $key => $role ) {
			$widget->add( $role, $key );
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

		// IP search condition.
		$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='Severity' AND ( ";

		$count = count( $value );
		foreach ( $value as $key => $code ) {
			$code          = $this->convert_to_code_int( $code );
			$value[ $key ] = $code;
			if ( $value[ $count - 1 ] === $code ) {
				$sql .= "meta.value='%s'";
			} else {
				$sql .= "meta.value='$code' OR ";
			}
		}

		$sql .= ' ) )';

		// Check prefix.
		switch ( $prefix ) {
			case 'severity':
				$query->addORCondition( array( $sql => $value[ $count - 1 ] ) );
				break;
			default:
				throw new \Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}

	private function get_severities() {
		return array(
			'critical'      => __( 'Critical', 'mwp-al-ext' ),
			'high'          => __( 'High', 'mwp-al-ext' ),
			'medium'        => __( 'Medium', 'mwp-al-ext' ),
			'low'           => __( 'Low', 'mwp-al-ext' ),
			'informational' => __( 'Info', 'mwp-al-ext' ),
		);
	}

	/**
	 * Tries to convert string error codes to integers that match the data in the DB.
	 *
	 * Defaults to return `200` which is the standard notice number.
	 *
	 * @method convert_to_code_int
	 * @since  1.4.0
	 * @param  string $code_in A string that should represent an error code.
	 * @return string
	 */
	private function convert_to_code_int( $code_in ) {
		$constants_manager = MWPAL_Extension\mwpal_extension()->constants;

		// Try the given string first (this should work for the legacy PHP error based severity codes).
		$constant = $constants_manager->GetConstantBy( 'name', $code_in );
		if ( null === $constant ) {
			// No match, let's try to prefix with "WSAL_" as this should match all the remaining cases.
			$constant = $constants_manager->GetConstantBy( 'name', 'WSAL_' . strtoupper( $code_in ) );
		}

		if ( null === $constant ) {
			// Fallback.
			$constant = $constants_manager->GetConstantBy( 'name', 'WSAL_INFORMATIONAL' );
		}

		if ( null === $constant ) {
			// Still nothing? default to INFO (200): Interesting events.
			return '200';
		}

		return (string) $constant->value;
	}
}
