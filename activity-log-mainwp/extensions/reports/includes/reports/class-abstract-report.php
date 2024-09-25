<?php
/**
 * Report abstract class.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract report class.
 */
abstract class Abstract_Report {

	/**
	 * Admin notices.
	 *
	 * @var array
	 */
	private $admin_notices = array();

	/**
	 * Outputs the mentioned section of a periodic report.
	 *
	 * @param string $section - Section name.
	 */
	public function get_section( $section ) {
		$report_section = $section . '_section';
		return $this->$report_section();
	}

	/**
	 * Add admin notice.
	 *
	 * @param string $type    - Type of notice.
	 * @param string $message - Message to appear in the notice.
	 */
	protected function add_notice( $type, $message ) {
		$this->admin_notices[ $type ] = $message;
	}

	/**
	 * Returns admin notices to display on the view.
	 *
	 * @return array
	 */
	public function get_notices() {
		return $this->admin_notices;
	}

	/**
	 * Save periodic report.
	 */
	abstract public function save();

	/**
	 * Localized script data.
	 */
	abstract public function localized_script_data();
}
