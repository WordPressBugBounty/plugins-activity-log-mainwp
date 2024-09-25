<?php
/**
 * Class WSAL_Rep_AbstractReportGenerator
 *
 * @package wsal/report
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use WSAL\MainWPExtension\Utilities\DateTimeFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract class for different report formats.
 *
 * @since 1.7.0
 */
abstract class Abstract_Report_Generator {

	/**
	 * Date format.
	 *
	 * @var string
	 */
	protected $date_format = null;

	/**
	 * Method: Constructor.
	 *
	 * @param string $date_format - Date format.
	 */
	public function __construct( $date_format ) {
		$this->date_format = $date_format;
	}

	/**
	 * Formats date for the presentation layer.
	 *
	 * @param int $timestamp Timestamp.
	 *
	 * @return string Formatted date.
	 * @since 1.7.0
	 */
	function getFormattedDate( $timestamp ) {
		return DateTimeFormatter::instance()->getFormattedDateTime( $timestamp, 'date' );
	}
}
