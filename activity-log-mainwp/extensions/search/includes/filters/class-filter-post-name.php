<?php
/**
 * Post name filter.
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
 * Post name filter class.
 */
class Filter_Post_Name extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'Post Name', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'postname' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		return array( new Widget_Post_Name( 'postname', esc_html__( 'Post Name', 'mwp-al-ext' ) ) );
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

		// Post name search condition.
		$sql   = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='PostTitle' AND ( ";
		$value = array_map( array( $this, 'add_string_wildcards' ), $value );

		// Get the last post name.
		$last_name = end( $value );

		foreach ( $value as $post_name ) {
			if ( $last_name === $post_name ) {
				continue;
			} else {
				$sql .= "( (meta.value LIKE '$post_name') > 0 ) OR ";
			}
		}

		// Add placeholder for the last post id.
		$sql .= "( (meta.value LIKE '%s') > 0 ) ) )";

		// Check prefix.
		switch ( $prefix ) {
			case 'postname':
				$query->addORCondition( array( $sql => $last_name ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new \Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}

	/**
	 * Modify post name values to include MySQL wildcards.
	 *
	 * @param string $search_value – Searched post name.
	 * @return string
	 */
	private function add_string_wildcards( $search_value ) {
		return '%' . $search_value . '%';
	}
}
