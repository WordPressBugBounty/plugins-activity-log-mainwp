<?php
/**
 * Utility Class
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use \WSAL\MainWPExtension\Models\Meta as Meta;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utility_Meta
 */
class Utility_Meta extends Meta {

	/**
	 * Returns Meta Table name.
	 *
	 * @return string
	 */
	public function GetTableName() {
		return $this->getConnector()->getAdapter( 'Meta' )->GetTable();
	}
}
