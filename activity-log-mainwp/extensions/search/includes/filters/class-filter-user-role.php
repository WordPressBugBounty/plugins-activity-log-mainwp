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
class Filter_User_Role extends Abstract_Filter {

	/**
	 * Get name.
	 */
	public function get_name() {
		return esc_html__( 'User Role', 'mwp-al-ext' );
	}

	/**
	 * Get prefixes.
	 */
	public function get_prefixes() {
		return array( 'userrole' );
	}

	/**
	 * Get widgets.
	 */
	public function get_widgets() {
		// Intialize single select widget class.
		$widget = new Widget_Select_Single( 'userrole', esc_html__( 'User Role', 'mwp-al-ext' ), __( 'Select a User Role to filter by' ) );

		// Get WP user roles.
		$wp_user_roles = $this->get_wp_user_roles();
		$user_roles    = array();
		foreach ( $wp_user_roles as $role => $details ) {
			$user_roles[ $role ] = translate_user_role( $details['name'] );
		}

		// Add select options to widget.
		foreach ( $user_roles as $key => $role ) {
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

		// User role search condition.
		$sql   = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='CurrentUserRoles' AND replace(replace(replace(meta.value, ']', ''), '[', ''), '\\'', '') REGEXP %s )";
		$value = implode( '|', $value );
		switch ( $prefix ) {
			case 'userrole':
				$query->addORCondition( array( $sql => $value ) );
				break;
			default:
				/* Translators: %s: Filter prefix. */
				throw new \Exception( sprintf( __( 'Unsupported filter %s.', 'mwp-al-ext' ), $prefix ) );
		}
	}

	/**
	 * Get WP user roles.
	 *
	 * @return array
	 */
	private function get_wp_user_roles() {
		$wp_user_roles = '';

		// Check if function `wp_roles` exists.
		if ( function_exists( 'wp_roles' ) ) {
			// Get WP user roles.
			$wp_user_roles = wp_roles()->roles;
		} else { // WP Version is below 4.3.0
			// Get global wp roles variable.
			global $wp_roles;

			// If it is not set then initiate WP_Roles class object.
			if ( ! isset( $wp_roles ) ) {
				$new_wp_roles = new WP_Roles(); // Don't override the original global variable.
			}

			// Get WP user roles.
			$wp_user_roles = $new_wp_roles->roles;
		}
		return $wp_user_roles;
	}
}
