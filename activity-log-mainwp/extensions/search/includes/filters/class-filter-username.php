<?php
/**
 * Username search filter.
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
 * Username search filter class.
 */
class Filter_Username extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'User', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'username' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		return array( new Widget_Username( 'username', 'Username' ) );
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

		// User ids array.
		$user_ids = array();

		// Search for MainWP site for user ids.
		foreach ( $value as $username ) {
			$user = get_user_by( 'login', $username );

			if ( ! $user ) {
				$user = get_user_by( 'slug', $username );
			}

			if ( $user ) {
				$user_ids[] = $user->ID;
			}
		}

		// Get child site users.
		$site_users = MWPAL_Extension\get_child_site_users();

		// Search for child site user ids.
		foreach ( $site_users as $users ) {
			foreach ( $value as $username ) {
				if ( isset( $users[ $username ] ) ) {
					$user_ids[] = $users[ $username ]['ID'];
				}
			}
		}

		// Eliminate duplicate user ids.
		$user_ids = array_unique( $user_ids );

		switch ( $prefix ) {
			case 'username':
				// Search query.
				$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE ";

				if ( ! empty( $user_ids ) ) {
					$last_userid = end( $user_ids );
					$sql        .= "( meta.name='CurrentUserID' AND ( ";

					foreach ( $user_ids as $user_id ) {
						if ( $last_userid === $user_id ) {
							$sql .= "meta.value='$user_id'";
						} else {
							$sql .= "meta.value='$user_id' OR ";
						}
					}

					$sql .= ' ) )';
					$sql .= ' OR ';
				}

				if ( ! empty( $value ) ) {
					$last_username = end( $value );
					$sql          .= "( meta.name='Username' AND ( ";

					foreach ( $value as $username ) {
						if ( $last_username === $username ) {
							$sql .= "meta.value='%s'";
						} else {
							$sql .= "meta.value='$username' OR ";
						}
					}

					$sql .= ' ) )';
				}

				$sql       .= ' )';
				$user_count = count( $value );
				$query->addORCondition( array( $sql => $value[ $user_count - 1 ] ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}
}
