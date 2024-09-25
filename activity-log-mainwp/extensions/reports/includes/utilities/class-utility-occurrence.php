<?php
/**
 * Class: Utility Class
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use \WSAL\MainWPExtension\Models\Occurrence as Occurrence;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utility_Occurrence
 */
class Utility_Occurrence extends Occurrence {

	/**
	 * Returns Occurrence Table name.
	 *
	 * @return string
	 */
	public function GetTableName() {
		return $this->getConnector()->getAdapter( 'Occurrence' )->GetTable();
	}
}
