<?php
/**
 * Post ID filter.
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
 * Post id filter class.
 */
class Filter_Post_Id extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return __( 'Post ID', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'postid' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		return array( new Widget_Post_Id( 'postid', esc_html__( 'Post ID', 'mwp-al-ext' ) ) );
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

		// Post id search condition.
		$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='PostID' AND ( ";

		// Get the last post id.
		$last_id = end( $value );

		foreach ( $value as $post_id ) {
			if ( $last_id === $post_id ) {
				continue;
			} else {
				$sql .= "meta.value='$post_id' OR ";
			}
		}

		// Add placeholder for the last post id.
		$sql .= "meta.value='%s' ) )";

		// Check prefix.
		switch ( $prefix ) {
			case 'postid':
				$query->addORCondition( array( $sql => $last_id ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
