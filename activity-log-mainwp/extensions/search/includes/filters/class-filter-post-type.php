<?php
/**
 * Post type filter.
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
 * Post type filter class.
 */
class Filter_Post_Type extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'Post Type', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'posttype' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		// Intialize single select widget class.
		$widget = new Widget_Select_Single( 'posttype', esc_html__( 'Post Type', 'mwp-al-ext' ), __( 'Select a Post Type to filter by' ) );

		// Get the post types.
		$args       = array( 'public' => true );
		$output     = 'names'; // Names or objects, note names is the default.
		$operator   = 'and'; // Boolean conditions.
		$post_types = \get_post_types( $args, $output, $operator );

		// Search and remove attachment type.
		$key = array_search( 'attachment', $post_types, true );
		if ( false !== $key ) {
			unset( $post_types[ $key ] );
		}

		// Add select options to widget.
		foreach ( $post_types as $post_type ) {
			$widget->add( ucwords( $post_type ), $post_type );
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

		// Post type search condition.
		$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='PostType' AND ( ";

		// Get the last post type.
		$last_type = end( $value );

		foreach ( $value as $post_type ) {
			if ( $last_type === $post_type ) {
				continue;
			} else {
				$sql .= "meta.value='$post_type' OR ";
			}
		}

		// Add placeholder for the last post type.
		$sql .= "meta.value='%s' ) )";

		// Check prefix.
		switch ( $prefix ) {
			case 'posttype':
				$query->addORCondition( array( $sql => $last_type ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new \Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
