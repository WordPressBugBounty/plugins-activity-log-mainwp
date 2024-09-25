<?php
/**
 * Initialize extensions.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extensions class.
 *
 * @since 1.1
 */
class Extensions {

	/**
	 * Array of available extensions.
	 *
	 * @var array
	 */
	public $extensions = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->include();
		$this->extensions_init();
	}

	/**
	 * Include extensions.
	 */
	private function include() {
		require_once trailingslashit( __DIR__ ) . 'search/class-search.php'; // Search.
		require_once trailingslashit( __DIR__ ) . 'reports/class-reports.php'; // Search.
	}

	/**
	 * Extensions init.
	 */
	public function extensions_init() {
		$this->extensions['search']  = \WSAL\MainWPExtension\Extensions\Search::get_instance();
		$this->extensions['reports'] = \WSAL\MainWPExtension\Extensions\Reports::get_instance();
	}
}

new Extensions();
