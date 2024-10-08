<?php
/**
 * Adapter: Active Record.
 *
 * MySQL database ActiveRecord class.
 *
 * @package mwp-al-ext
 * @since 1.0.0
 */

namespace WSAL\MainWPExtension\Adapters\MySQL;

use \WSAL\MainWPExtension\Adapters\ActiveRecordInterface as ActiveRecordInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MySQL database ActiveRecord class.
 *
 * MySQL generic table used for Save, Read, Create or Delete
 * elements in the Database.
 * There are also the functions used in the Report Add-On to get the reports.
 *
 * @package mwp-al-ext
 */
#[\AllowDynamicProperties]
class ActiveRecord implements ActiveRecordInterface {

	/**
	 * DB Connection
	 *
	 * @var array
	 */
	protected $connection;

	/**
	 * Contains the table name
	 *
	 * @var string
	 */
	protected $_table;

	/**
	 * Contains primary key column name, override as required.
	 *
	 * @var string
	 */
	protected $_idkey = '';

	/**
	 * Method: Constructor.
	 *
	 * @param object $conn - DB connection object.
	 */
	public function __construct( $conn ) {
		$this->connection = $conn;
	}

	/**
	 * Method: Get connection.
	 *
	 * @return object – DB connection object.
	 */
	public function get_connection() {
		return $this->connection;
	}

	/**
	 * Returns the model class for adapter.
	 *
	 * @return \WSAL\MainWPExtension\Models\ActiveRecord
	 */
	public function GetModel() {
		return new \WSAL\MainWPExtension\Models\ActiveRecord();
	}

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function GetTable() {
		$_wpdb = $this->connection;
		return $_wpdb->base_prefix . $this->_table;
	}

	/**
	 * Used for WordPress prefix
	 *
	 * @return string Returns table name of WordPress.
	 */
	public function GetWPTable() {
		global $wpdb;
		return $wpdb->base_prefix . $this->_table;
	}

	/**
	 * SQL table options (constraints, foreign keys, indexes etc).
	 *
	 * @return string
	 */
	protected function GetTableOptions() {
		return '    PRIMARY KEY  (' . $this->_idkey . ')';
	}

	/**
	 * Returns this records' columns.
	 *
	 * @return array
	 */
	public function GetColumns() {
		$model = $this->GetModel();

		if ( ! isset( $this->_column_cache ) ) {
			$this->_column_cache = array();
			foreach ( array_keys( get_object_vars( $model ) ) as $col ) {
				if ( trim( $col ) && $col[0] != '_' ) {
					$this->_column_cache[] = $col;
				}
			}
		}
		return $this->_column_cache;
	}

	/**
	 * Returns whether table structure is installed or not.
	 *
	 * @deprecated
	 * @return boolean
	 */
	public function IsInstalled() {
		$_wpdb = $this->connection;
		$sql   = 'SHOW TABLES LIKE "' . $this->GetTable() . '"';

		// Table transient.
		$wsal_table_transient = 'wsal_' . strtolower( $this->GetTable() ) . '_status';
		$wsal_db_table_status = get_transient( $wsal_table_transient );

		// If transient does not exist, then run SQL query.
		if ( ! $wsal_db_table_status ) {
			$wsal_db_table_status = strtolower( $_wpdb->get_var( $sql ) ) == strtolower( $this->GetTable() );
			set_transient( $wsal_table_transient, $wsal_db_table_status, DAY_IN_SECONDS );
		}
		return $wsal_db_table_status;
	}

	/**
	 * Install this ActiveRecord structure into DB.
	 */
	public function Install() {
		$_wpdb = $this->connection;
		$_wpdb->query( $this->_GetInstallQuery() );
	}

	/**
	 * Install this ActiveRecord structure into DB WordPress.
	 */
	public function InstallOriginal() {
		global $wpdb;
		$wpdb->query( $this->_GetInstallQuery( true ) );
	}

	/**
	 * Remove this ActiveRecord structure from DB.
	 */
	public function Uninstall() {
		$_wpdb = $this->connection;

		// Check if table exists.
		if ( $this->table_exists() ) {
			$_wpdb->query( $this->_GetUninstallQuery() );
		}
	}

	/**
	 * Check if table exists.
	 *
	 * @return bool – True if exists, false if not.
	 */
	public function table_exists() {
		$_wpdb = $this->connection;

		// Query table exists.
		$table_exists_query = 'SHOW TABLES LIKE "' . $this->GetTable() . '"';
		$result             = $_wpdb->query( $table_exists_query );
		return $result;
	}

	/**
	 * Save an active record into DB.
	 *
	 * @param object $active_record - ActiveRecord object.
	 * @return integer|boolean - Either the number of modified/inserted rows or false on failure.
	 */
	public function Save( $active_record ) {
		$_wpdb  = $this->connection;
		$copy   = $active_record;
		$data   = array();
		$format = array();

		foreach ( $this->GetColumns() as $index => $key ) {
			if ( $key == $this->_idkey ) {
				$_id_index = $index;
			}

			$val    = $copy->$key;
			$deffmt = '%s';
			if ( is_int( $copy->$key ) ) {
				$deffmt = '%d';
			}
			if ( is_float( $copy->$key ) ) {
				$deffmt = '%f';
			}
			if ( is_array( $copy->$key ) || is_object( $copy->$key ) ) {
				$data[ $key ] = \WSAL\MainWPExtension\Helpers\DataHelper::JsonEncode( $val );
			} else {
				$data[ $key ] = $val;
			}
			$format[] = $deffmt;
		}

		if ( isset( $data[ $this->_idkey ] ) && empty( $data[ $this->_idkey ] ) ) {
			unset( $data[ $this->_idkey ] );
			unset( $format[ $_id_index ] );
		}

		$result = $_wpdb->replace( $this->GetTable(), $data, $format );

		if ( false !== $result ) {
			if ( $_wpdb->insert_id ) {
				$copy->setId( $_wpdb->insert_id );
			}
		}
		return $result;
	}

	/**
	 * Load record from DB (Single row).
	 *
	 * @param string $cond - (Optional) Load condition.
	 * @param array  $args - (Optional) Load condition arguments.
	 */
	public function Load( $cond = '%d', $args = array( 1 ) ) {
		$_wpdb = $this->connection;
		$sql   = $_wpdb->prepare( 'SELECT * FROM ' . $this->GetTable() . ' WHERE ' . $cond, $args );
		$data  = $_wpdb->get_row( $sql, ARRAY_A );
		return $data;
	}

	/**
	 * Load records from DB (Multi rows).
	 *
	 * @param string $cond Load condition.
	 * @param array  $args (Optional) Load condition arguments.
	 */
	public function LoadArray( $cond, $args = array() ) {
		$_wpdb  = $this->connection;
		$result = array();
		$sql    = $_wpdb->prepare( 'SELECT * FROM ' . $this->GetTable() . ' WHERE ' . $cond, $args );
		foreach ( $_wpdb->get_results( $sql, ARRAY_A ) as $data ) {
			$result[] = $this->getModel()->LoadData( $data );
		}
		return $result;
	}

	/**
	 * Delete DB record.
	 *
	 * @param object $active_record - ActiveRecord object.
	 * @return int|boolean - Either the amount of deleted rows or False on error.
	 */
	public function Delete( $active_record ) {
		$_wpdb  = $this->connection;
		$result = $_wpdb->delete(
			$this->GetTable(),
			$active_record->getId()
		);
		return $result;
	}

	/**
	 * Delete records in DB matching a query.
	 *
	 * @param string $query Full SQL query.
	 * @param array  $args (Optional) Query arguments.
	 */
	public function DeleteQuery( $query, $args = array() ) {
		$_wpdb  = $this->connection;
		$sql    = count( $args ) ? $_wpdb->prepare( $query, $args ) : $query;
		$result = $_wpdb->query( $sql );
		return $result;
	}

	/**
	 * Load multiple records from DB.
	 *
	 * @param string $cond (Optional) Load condition (eg: 'some_id = %d' ).
	 * @param array  $args (Optional) Load condition arguments (rg: array(45) ).
	 * @return self[] List of loaded records.
	 */
	public function LoadMulti( $cond, $args = array() ) {
		$_wpdb  = $this->connection;
		$result = array();
		$sql    = ( ! is_array( $args ) || ! count( $args ) ) // Do we really need to prepare() or not?
			? ( $cond )
			: $_wpdb->prepare( $cond, $args );
		foreach ( $_wpdb->get_results( $sql, ARRAY_A ) as $data ) {
			$result[] = $this->getModel()->LoadData( $data );
		}
		return $result;
	}

	/**
	 * Load multiple records from DB and call a callback for each record.
	 * This function is very memory-efficient, it doesn't load records in bulk.
	 *
	 * @param callable $callback The callback to invoke.
	 * @param string   $cond (Optional) Load condition.
	 * @param array    $args (Optional) Load condition arguments.
	 */
	public function LoadAndCallForEach( $callback, $cond = '%d', $args = array( 1 ) ) {
		$_wpdb = $this->connection;
		$class = get_called_class();
		$sql   = $_wpdb->prepare( 'SELECT * FROM ' . $this->GetTable() . ' WHERE ' . $cond, $args );
		foreach ( $_wpdb->get_results( $sql, ARRAY_A ) as $data ) {
			call_user_func( $callback, new $class( $data ) );
		}
	}

	/**
	 * Count records in the DB matching a condition.
	 * If no parameters are given, this counts the number of records in the DB table.
	 *
	 * @param string $cond (Optional) Query condition.
	 * @param array  $args (Optional) Condition arguments.
	 * @return int Number of matching records.
	 */
	public function Count( $cond = '%d', $args = array( 1 ) ) {
		$_wpdb = $this->connection;
		$sql   = $_wpdb->prepare( 'SELECT COUNT(*) FROM ' . $this->GetTable() . ' WHERE ' . $cond, $args );
		return (int) $_wpdb->get_var( $sql );
	}

	/**
	 * Count records in the DB matching a query.
	 *
	 * @param string $query Full SQL query.
	 * @param array  $args (Optional) Query arguments.
	 * @return int Number of matching records.
	 */
	public function CountQuery( $query, $args = array() ) {
		$_wpdb = $this->connection;
		$sql   = count( $args ) ? $_wpdb->prepare( $query, $args ) : $query;
		return (int) $_wpdb->get_var( $sql );
	}

	/**
	 * Similar to LoadMulti but allows the use of a full SQL query.
	 *
	 * @param string $query Full SQL query.
	 * @param array  $args (Optional) Query arguments.
	 * @return self[] List of loaded records.
	 */
	public function LoadMultiQuery( $query, $args = array() ) {
		$_wpdb  = $this->connection;
		$result = array();
		$sql    = count( $args ) ? $_wpdb->prepare( $query, $args ) : $query;
		foreach ( $_wpdb->get_results( $sql, ARRAY_A ) as $data ) {
			$result[] = $this->getModel()->LoadData( $data );
		}
		return $result;
	}

	/**
	 * Table install query.
	 *
	 * @param string $prefix - (Optional) Table prefix.
	 * @return string - Must return SQL for creating table.
	 */
	protected function _GetInstallQuery( $prefix = false ) {
		$_wpdb      = $this->connection;
		$class      = get_class( $this );
		$copy       = new $class( $this->connection );
		$table_name = ( $prefix ) ? $this->GetWPTable() : $this->GetTable();
		$sql        = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (' . PHP_EOL;

		foreach ( $this->GetColumns() as $key ) {
			$sql .= '    ';
			switch ( true ) {
				case $key == $copy->_idkey:
					$sql .= $key . ' BIGINT NOT NULL AUTO_INCREMENT,' . PHP_EOL;
					break;
				case is_integer( $copy->$key ):
					$sql .= $key . ' BIGINT NOT NULL,' . PHP_EOL;
					break;
				case is_float( $copy->$key ):
					$sql .= $key . ' DOUBLE NOT NULL,' . PHP_EOL;
					break;
				case is_string( $copy->$key ):
					$maxlength = $key . '_maxlength';
					if ( property_exists( $class, $maxlength ) ) {
						$sql .= $key . ' VARCHAR(' . intval( $class::$$maxlength ) . ') NOT NULL,' . PHP_EOL;
					} else {
						$sql .= $key . ' LONGTEXT NOT NULL,' . PHP_EOL;
					}
					break;
				case is_bool( $copy->$key ):
					$sql .= $key . ' BIT NOT NULL,' . PHP_EOL;
					break;
				case is_array( $copy->$key ):
				case is_object( $copy->$key ):
					$sql .= $key . ' LONGTEXT NOT NULL,' . PHP_EOL;
					break;
			}
		}

		$sql .= $this->GetTableOptions() . PHP_EOL;

		$sql .= ')';

		if ( ! empty( $_wpdb->charset ) ) {
			$sql .= ' DEFAULT CHARACTER SET ' . $_wpdb->charset;
		}

		return $sql;
	}

	/**
	 * Update `option_value` column of the Options table
	 * of WSAL to LONGTEXT.
	 *
	 * @since 3.2.3
	 */
	public function update_value_column() {
		global $wpdb;
		$sql  = 'ALTER TABLE ' . $this->GetTable();
		$sql .= ' MODIFY COLUMN option_value LONGTEXT NOT NULL';
		$wpdb->query( $sql );
	}

	/**
	 * Must return SQL for removing table (at a minimum, it should be ` 'DROP TABLE ' . $this->_table `).
	 *
	 * @return string
	 */
	protected function _GetUninstallQuery() {
		return 'DROP TABLE ' . $this->GetTable();
	}

	/**
	 * Get Users user_login.
	 *
	 * @param int $_user_id - User ID.
	 * @return string comma separated users login
	 */
	private function GetUserNames( $_user_id ) {
		global $wpdb;

		$user_names = '0';
		if ( ! empty( $_user_id ) && 'null' != $_user_id ) {
			$sql = 'SELECT user_login FROM ' . $wpdb->users . ' WHERE find_in_set(ID, @userId) > 0';
			$wpdb->query( "SET @userId = $_user_id" );
			$result      = $wpdb->get_results( $sql, ARRAY_A );
			$users_array = array();
			foreach ( $result as $item ) {
				$users_array[] = '"' . $item['user_login'] . '"';
			}
			$user_names = implode( ', ', $users_array );
		}
		return $user_names;
	}

	/**
	 * Function used in WSAL reporting extension.
	 *
	 * @param int    $_site_id         - Site ID.
	 * @param int    $_user_id         - User ID.
	 * @param string $_role_name       - User role.
	 * @param int    $_alert_code      - Alert code.
	 * @param string $_start_timestamp - From created_on.
	 * @param string $_end_timestamp   - To created_on.
	 * @param string $_next_date       - (Optional) Created on >.
	 * @param int    $_limit           - (Optional) Limit.
	 * @param int    $usernames        - (Optional) Usernames.
	 * @return array Report results
	 */
	public function GetReporting( $_site_id, $_user_id, $_role_name, $_alert_code, $_start_timestamp, $_end_timestamp, $_next_date = null, $_limit = 0, $usernames = 'null' ) {
		global $wpdb;

		$_wpdb = $this->connection;
		$_wpdb->set_charset( $_wpdb->dbh, 'utf8mb4', 'utf8mb4_general_ci' );

		// Tables.
		$meta       = new \WSAL\MainWPExtension\Adapters\MySQL\Meta( $this->connection );
		$table_meta = $meta->GetTable(); // Metadata.
		$occurrence = new \WSAL\MainWPExtension\Adapters\MySQL\Occurrence( $this->connection );
		$table_occ  = $occurrence->GetTable(); // Occurrences.

		$condition_date = ! empty( $_next_date ) ? ' AND occ.created_on < ' . $_next_date : '';

		$sql = "SELECT DISTINCT
			occ.id,
			occ.alert_id,
			occ.site_id,
			occ.created_on,
			replace(replace(replace((SELECT t1.value FROM $table_meta AS t1 WHERE t1.name = 'CurrentUserRoles' AND t1.occurrence_id = occ.id LIMIT 1), '[', ''), ']', ''), '\\'', '') AS roles,
			(SELECT replace(t2.value, '\"','') FROM $table_meta as t2 WHERE t2.name = 'ClientIP' AND t2.occurrence_id = occ.id LIMIT 1) AS ip,
			(SELECT replace(t3.value, '\"', '') FROM $table_meta as t3 WHERE t3.name = 'UserAgent' AND t3.occurrence_id = occ.id LIMIT 1) AS ua,
			COALESCE(
				(SELECT replace(t4.value, '\"', '') FROM $table_meta as t4 WHERE t4.name = 'Username' AND t4.occurrence_id = occ.id LIMIT 1),
				(SELECT replace(t5.value, '\"', '') FROM $table_meta as t5 WHERE t5.name = 'CurrentUserID' AND t5.occurrence_id = occ.id LIMIT 1)
			) as user_id
			FROM $table_occ AS occ
			JOIN $table_meta AS meta ON meta.occurrence_id = occ.id
			WHERE
				(@siteId is NULL OR find_in_set(occ.site_id, @siteId) > 0)
				AND (
					@userId is NULL
					OR (
						(meta.name = 'CurrentUserID' AND find_in_set(meta.value, @userId) > 0)
						OR
						(meta.name = 'Username' AND replace(meta.value, '\"', '') IN ($usernames))
					)
				)
				AND (
					@roleName is NULL
					OR (
						meta.name = 'CurrentUserRoles'
						AND
						replace(replace(replace(meta.value, ']', ''), '[', ''), '\\'', '') REGEXP @roleName
					)
				)
				AND (@alertCode is NULL OR find_in_set(occ.alert_id, @alertCode) > 0)
				AND (@startTimestamp is NULL OR occ.created_on >= @startTimestamp)
				AND (@endTimestamp is NULL OR occ.created_on <= @endTimestamp)
				{$condition_date}
			ORDER BY
				created_on DESC
		";
		$_wpdb->query( "SET @siteId = $_site_id" );
		$_wpdb->query( "SET @userId = $_user_id" );
		$_wpdb->query( "SET @roleName = $_role_name" );
		$_wpdb->query( "SET @alertCode = $_alert_code" );
		$_wpdb->query( "SET @startTimestamp = $_start_timestamp" );
		$_wpdb->query( "SET @endTimestamp = $_end_timestamp" );

		if ( ! empty( $_limit ) ) {
			$sql .= " LIMIT {$_limit}";
		}
		$results = $_wpdb->get_results( $sql );

		if ( ! empty( $results ) ) {
			$last_event          = end( $results );
			$results['lastDate'] = $last_event->created_on;
		}

		return $results;
	}

	/**
	 * Function used in WSAL reporting extension.
	 * Check if criteria are matching in the DB.
	 *
	 * @param mixed $criteria - Query conditions.
	 * @return int count of distinct values
	 */
	public function CheckMatchReportCriteria( $criteria ) {
		$_site_id         = $criteria['siteId'];
		$_user_id         = $criteria['userId'];
		$_post_types      = $criteria['post_types'];
		$_post_statuses   = $criteria['post_statuses'];
		$_role_name       = $criteria['roleName'];
		$_alert_code      = $criteria['alertCode'];
		$_start_timestamp = $criteria['startTimestamp'];
		$_end_timestamp   = $criteria['endTimestamp'];
		$_ip_address      = $criteria['ipAddress'];

		$_wpdb = $this->connection;
		$_wpdb->set_charset( $_wpdb->dbh, 'utf8mb4', 'utf8mb4_general_ci' );
		// Tables.
		$meta       = new \WSAL\MainWPExtension\Adapters\MySQL\Meta( $this->connection );
		$table_meta = $meta->GetTable(); // Metadata.
		$occurrence = new \WSAL\MainWPExtension\Adapters\MySQL\Occurrence( $this->connection );
		$table_occ  = $occurrence->GetTable(); // Occurrences.

		$user_names = $this->GetUserNames( $_user_id );

		if ( 'null' === $_post_types && 'null' === $_post_statuses ) {
			$sql = "SELECT COUNT(DISTINCT occ.id) FROM $table_occ AS occ
				JOIN $table_meta AS meta ON meta.occurrence_id = occ.id
				WHERE
					(@siteId is NULL OR find_in_set(occ.site_id, @siteId) > 0)
					AND (@userId is NULL OR (
						(meta.name = 'CurrentUserID' AND find_in_set(meta.value, @userId) > 0)
					OR (meta.name = 'Username' AND replace(meta.value, '\"', '') IN ($user_names))
					))
					AND (@roleName is NULL OR (meta.name = 'CurrentUserRoles'
					AND replace(replace(replace(meta.value, ']', ''), '[', ''), '\\'', '') REGEXP @roleName
					))
					AND (@alertCode is NULL OR find_in_set(occ.alert_id, @alertCode) > 0)
					AND (@startTimestamp is NULL OR occ.created_on >= @startTimestamp)
					AND (@endTimestamp is NULL OR occ.created_on <= @endTimestamp)
					AND (@ipAddress is NULL OR (meta.name = 'ClientIP' AND find_in_set(meta.value, @ipAddress) > 0))
				";
		} else {
			$sql = "SELECT COUNT(DISTINCT occ.id)
				FROM $table_occ AS occ
				WHERE
					(@siteId is NULL OR find_in_set(occ.site_id, @siteId) > 0)
					AND (
						@userId is NULL
						OR (
							EXISTS(SELECT 1 FROM $table_meta as meta WHERE meta.occurrence_id = occ.id AND meta.name='CurrentUserID' AND find_in_set(meta.value, @userId) > 0)
							OR
							EXISTS(SELECT 1 FROM $table_meta as meta WHERE meta.occurrence_id = occ.id AND meta.name='Username' AND replace(meta.value, '\"', '') IN ($user_names))
						)
					)
					AND (
						@roleName is NULL
						OR
						EXISTS(SELECT 1 FROM $table_meta as meta WHERE meta.occurrence_id = occ.id AND meta.name='CurrentUserRoles' AND replace(replace(replace(meta.value, ']', ''), '[', ''), '\\'', '') REGEXP @roleName)
					)
					AND (@alertCode is NULL OR find_in_set(occ.alert_id, @alertCode) > 0)
					AND (@startTimestamp is NULL OR occ.created_on >= @startTimestamp)
					AND (@endTimestamp is NULL OR occ.created_on <= @endTimestamp)
					AND (
						@ipAddress is NULL
						OR
						EXISTS(SELECT 1 FROM $table_meta as meta WHERE meta.occurrence_id = occ.id AND meta.name='ClientIP' AND find_in_set(meta.value, @ipAddress) > 0)
					)
					AND (
						@postType is NULL
						OR
						EXISTS(SELECT 1 FROM $table_meta as meta WHERE meta.occurrence_id = occ.id AND meta.name='PostType' AND find_in_set(meta.value, @postType) > 0)
					)
					AND (
						@postStatus is NULL
						OR
						EXISTS(SELECT 1 FROM $table_meta as meta WHERE meta.occurrence_id = occ.id AND meta.name='PostStatus' AND find_in_set(meta.value, @postStatus) > 0)
					)
				";
		}

		$_wpdb->query( "SET @siteId = $_site_id" );
		$_wpdb->query( "SET @userId = $_user_id" );
		$_wpdb->query( "SET @postType = $_post_types" );
		$_wpdb->query( "SET @postStatus = $_post_statuses" );
		$_wpdb->query( "SET @roleName = $_role_name" );
		$_wpdb->query( "SET @alertCode = $_alert_code" );
		$_wpdb->query( "SET @startTimestamp = $_start_timestamp" );
		$_wpdb->query( "SET @endTimestamp = $_end_timestamp" );
		$_wpdb->query( "SET @ipAddress = $_ip_address" );

		return (int) $_wpdb->get_var( $sql );
	}

	/**
	 * DELETE from table `tmp_users` and populate with users.
	 * It is used in the query of the above function.
	 *
	 * @param string $table_users - Table name.
	 */
	private function TempUsers( $table_users ) {
		$_wpdb = $this->connection;
		$sql   = "DELETE FROM $table_users";
		$_wpdb->query( $sql );

		$sql   = "INSERT INTO $table_users (ID, user_login) VALUES ";
		$users = get_users(
			array(
				'fields' => array( 'ID', 'user_login' ),
			)
		);
		foreach ( $users as $user ) {
			$sql .= '(' . $user->ID . ', \'' . $user->user_login . '\'), ';
		}
		$sql = rtrim( $sql, ', ' );
		$_wpdb->query( $sql );
	}
}
