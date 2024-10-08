<?php
/**
 * Adapter: Meta data.
 *
 * MySQL database Metadata class.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Adapters\MySQL;

use \WSAL\MainWPExtension\Adapters\MySQL\ActiveRecord as ActiveRecord;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MySQL database TmpUser class.
 *
 * This class is used for create a temporary table to store the WP users
 * when the External DB Add-On is activated and the Alerts are stored on an external DB
 * because the query between plugin tables and the internal wp_uses table is not possible.
 */
#[\AllowDynamicProperties]
class TmpUser extends ActiveRecord {

	/**
	 * Contains the table name.
	 *
	 * @var string
	 */
	protected $_table = 'wsal_tmp_users';

	/**
	 * Returns the model class for adapter.
	 *
	 * @return \WSAL\MainWPExtension\Models\TmpUser
	 */
	public function GetModel() {
		return new \WSAL\MainWPExtension\Models\TmpUser();
	}

	/**
	 * Must return SQL for creating table.
	 *
	 * @param mixed $prefix - Prefix.
	 * @return string
	 */
	protected function _GetInstallQuery( $prefix = false ) {
		$_wpdb      = $this->connection;
		$table_name = ( $prefix ) ? $this->GetWPTable() : $this->GetTable();
		$sql        = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (' . PHP_EOL;
		$sql       .= 'ID BIGINT NOT NULL,' . PHP_EOL;
		$sql       .= 'user_login VARCHAR(60) NOT NULL,' . PHP_EOL;
		$sql       .= 'INDEX (ID)' . PHP_EOL;
		$sql       .= ')';
		if ( ! empty( $_wpdb->charset ) ) {
			$sql .= ' DEFAULT CHARACTER SET ' . $_wpdb->charset;
		}
		return $sql;
	}
}
