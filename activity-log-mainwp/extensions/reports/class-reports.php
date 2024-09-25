<?php
/**
 * Reports extension.
 *
 * @package mwp-al-ext
 * @subpackage reports
 */

namespace WSAL\MainWPExtension\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reports extension class.
 */
class Reports {

	/**
	 * Reports instance.
	 *
	 * @var \WSAL\MainWPExtension\Extensions\Reports
	 */
	protected static $instance;

	/**
	 * Returns the instance of this module.
	 *
	 * @return \WSAL\MainWPExtension\Extensions\Reports
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define search constants.
	 */
	private function define_constants() {
		define( 'MWPAL_REPORTS_URL', MWPAL_BASE_URL . 'extensions/reports/' );
		define( 'MWPAL_REPORTS_DIR', trailingslashit( __DIR__ ) );
		define( 'MWPAL_PREPORT_PREFIX', 'periodic-report-' );
		define( 'MWPAL_PERIODIC_REPORTS', 'mwpal-periodic-reports' );
		define( 'MWPAL_PREPORTS_COUNT', 'mwpal-periodic-reports-count' );
		define( 'MWPAL_REPORTS_UPLOAD_PATH', MWPAL_UPLOADS_DIR . 'reports/' );
	}

	/**
	 * Include files.
	 */
	private function includes() {
		require_once MWPAL_REPORTS_DIR . 'includes/report-functions.php';
		\WSAL\MainWPExtension\Autoload\mwpal_autoload( trailingslashit( __DIR__ ) . 'includes' );
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'mwpal_init', array( $this, 'reports_init' ) );
	}

	/**
	 * Initialize the extension.
	 */
	public function reports_init() {}
}
