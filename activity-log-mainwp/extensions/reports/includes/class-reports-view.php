<?php
/**
 * Reports view.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use \WSAL\MainWPExtension as MWPAL_Extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reports view class.
 */
class Reports_View {

	/**
	 * Current report object.
	 *
	 * @var object
	 */
	private $current_report;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'mwpal_extension_tabs', array( $this, 'reports_extension_tab' ), 10, 2 );
		add_filter( 'mwpal_left_submenu_dropdown', array( $this, 'reports_left_submenu_dropdown' ), 10, 1 );
		add_action( 'mainwp_pageheader_extensions', array( $this, 'enqueue_styles' ), 20 );
		add_action( 'mainwp_pagefooter_extensions', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_init', array( $this, 'delete_periodic_report' ), 30 );
		add_action( 'wp_ajax_generate_periodic_report', array( $this, 'generate_periodic_report' ) );
		add_action( 'wp_ajax_mwpal_send_periodic_report', array( $this, 'send_periodic_report' ) );

		// For MainWP v4 or later.
		add_filter( 'mwpal_page_navigation', array( $this, 'reports_nav_tab' ), 10, 1 );
		add_filter( 'mwpal_main_menu_submenu', array( $this, 'reports_left_submenu_dropdown' ), 10, 1 );

		if ( \version_compare( MWPAL_Extension\get_mainwp_version(), '4.0-beta', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		} else {
			add_action( 'mainwp_after_header', array( $this, 'display_admin_notices' ) );
		}
	}

	/**
	 * Add reports tab to the extension tabs.
	 *
	 * @param array $extension_tabs - Extension tabs array.
	 * @param array $extension_url -  URL of the extension.
	 * @return array
	 */
	public function reports_extension_tab( $extension_tabs, $extension_url ) {
		$extension_tabs['reports'] = array(
			'name'   => __( 'Reports', 'mwp-al-ext' ),
			'link'   => add_query_arg( 'tab', 'reports', $extension_url ),
			'render' => array( $this, 'tab_reports' ),
			'save'   => array( $this, 'tab_reports_save' ),
		);

		return $extension_tabs;
	}

	/**
	 * Add reports to the left submenu of extension.
	 *
	 * @param array $submenu - Left submenu of the extension.
	 * @return array
	 */
	public function reports_left_submenu_dropdown( $submenu ) {
		$submenu[] = array(
			__( 'Reports', 'mwp-al-ext' ),
			'admin.php?page=' . MWPAL_EXTENSION_NAME . '&tab=reports',
			'',
		);

		return $submenu;
	}

	/**
	 * Add reports tab to the extension tabs for MainWP v4 or later.
	 *
	 * @param array $mwpal_tabs - Extension tabs array.
	 * @return array
	 */
	public function reports_nav_tab( $mwpal_tabs ) {
		$extension_url = add_query_arg( 'page', MWPAL_EXTENSION_NAME, admin_url( 'admin.php' ) );
		$mwpal_tabs[]  = array(
			'title'  => __( 'Reports', 'mwp-al-ext' ),
			'href'   => add_query_arg( 'tab', 'reports', $extension_url ),
			'active' => 'reports' === MWPAL_Extension\mwpal_extension()->extension_view->get_current_tab(),
		);
		return $mwpal_tabs;
	}

	/**
	 * Reports tab of the extension.
	 */
	public function tab_reports() {

		// Reports page URL arguments.
		$reports_args = array(
			'page' => MWPAL_EXTENSION_NAME,
			'tab'  => 'reports',
		);

		$action_url   = add_query_arg( $reports_args, admin_url( 'admin.php' ) );
		?>
		<h2><?php esc_html_e( 'Generate a report', 'mwp-al-ext' ); ?></h2>
		<div class="metabox-holder columns-1">
			<form method="post" id="mwpal-reports" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( MWPAL_EXTENSION_NAME ); ?>">
				<input type="hidden" name="submit" value="submit">
				<?php wp_nonce_field( 'mwpal-reports-nonce' ); ?>
				<div class="meta-box-sortables ui-sortable">
					<?php
					// Get current report object.
					$report = $this->get_current_report();

					$report->get_section( 'type' );
					$report->get_section( 'date' );
					$report->get_section( 'format' );
					$report->get_section( 'generate' );
					$report->get_section( 'configured' );
					?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Save function for the reports tab.
	 */
	public function tab_reports_save() {
		if ( isset( $_POST['submit'] ) ) { // phpcs:ignore
			$this->get_current_report()->save();
		}
	}

	/**
	 * Returns the current report object.
	 *
	 * @return object
	 */
	private function get_current_report() {
		if ( ! $this->current_report ) {
			$this->current_report = new Report_Periodic();
		}

		return $this->current_report;
	}

	/**
	 * Enqueue styles.
	 */
	public function enqueue_styles() {
		// Extension page check.
		if ( ! MWPAL_Extension\mwpal_extension()->settings->is_current_extension_page() ) {
			return;
		}

		// Reports page check.
		if ( 'reports' !== MWPAL_Extension\mwpal_extension()->extension_view->get_current_tab() ) {
			return;
		}

		// Select2 styles.
		wp_enqueue_style(
			'mwpal-select2-css',
			trailingslashit( MWPAL_BASE_URL ) . 'assets/js/dist/select2/select2.css',
			array(),
			'3.5.1'
		);

		wp_enqueue_style(
			'mwpal-select2-bootstrap-css',
			trailingslashit( MWPAL_BASE_URL ) . 'assets/js/dist/select2/select2-bootstrap.css',
			array(),
			'3.5.1'
		);

		// Datepicker styles.
		wp_enqueue_style(
			'mwpal-daterangepicker-styles',
			MWPAL_BASE_URL . 'assets/js/dist/search/daterangepicker.css',
			array(),
			'3.0.5'
		);

		// Report styles.
		wp_enqueue_style(
			'mwpal-report-styles',
			trailingslashit( MWPAL_REPORTS_URL ) . 'assets/css/report-styles.css',
			array(),
			filemtime( trailingslashit( MWPAL_REPORTS_DIR ) . 'assets/css/report-styles.css' )
		);
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		// Extension page check.
		if ( ! MWPAL_Extension\mwpal_extension()->settings->is_current_extension_page() ) {
			return;
		}

		// Reports page check.
		if ( 'reports' !== MWPAL_Extension\mwpal_extension()->extension_view->get_current_tab() ) {
			return;
		}

		// Select2 script.
		wp_enqueue_script(
			'mwpal-select2-js',
			trailingslashit( MWPAL_BASE_URL ) . 'assets/js/dist/select2/select2.min.js',
			array( 'jquery' ),
			'3.5.1',
			true
		);

		wp_enqueue_script(
			'mwpal-moment-script',
			MWPAL_BASE_URL . 'assets/js/dist/search/moment.min.js',
			array(),
			'2.9.0',
			true
		);

		wp_enqueue_script(
			'mwpal-daterangepicker-script',
			MWPAL_BASE_URL . 'assets/js/dist/search/daterangepicker.js',
			array( 'jquery' ),
			'3.0.5',
			true
		);

		wp_register_script(
			'mwpal-reports-script',
			trailingslashit( MWPAL_BASE_URL ) . 'assets/js/dist/reports/build.reports.js',
			array( 'jquery', 'mwpal-select2-js', 'mwpal-moment-script', 'mwpal-daterangepicker-script' ),
			filemtime( trailingslashit( MWPAL_BASE_DIR ) . 'assets/js/dist/reports/build.reports.js' ),
			false
		);
		$this->get_current_report()->localized_script_data();
		wp_enqueue_script( 'mwpal-reports-script' );
	}

	/**
	 * Delete periodic report.
	 */
	public function delete_periodic_report() {
		if ( ! MWPAL_Extension\mwpal_extension()->settings->is_current_extension_page() ) {
			return;
		}

		$current_tab = MWPAL_Extension\mwpal_extension()->extension_view->get_current_tab();

		if ( 'reports' !== $current_tab ) {
			return;
		}

		if ( isset( $_GET['_wpnonce'] ) ) {
			$report_name = isset( $_GET['report'] ) ? sanitize_text_field( wp_unslash( $_GET['report'] ) ) : false; // phpcs:ignore
			$action      = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : false; // phpcs:ignore

			check_admin_referer( $report_name . '-' . $action );

			if ( $report_name && 'delete' === $action ) {
				$this->get_current_report()->delete_report( $report_name );
			}
		}
	}

	/**
	 * Display admin notices (if any).
	 */
	public function display_admin_notices() {
		if ( ! MWPAL_Extension\mwpal_extension()->settings->is_current_extension_page() ) {
			return;
		}

		$current_tab = MWPAL_Extension\mwpal_extension()->extension_view->get_current_tab();

		if ( 'reports' !== $current_tab ) {
			return;
		}

		// Get notices.
		$admin_notices = $this->get_current_report()->get_notices();

		if ( is_array( $admin_notices ) ) {
			foreach ( $admin_notices as $type => $message ) {
				printf( '<div class="notice notice-%1$s"><p>%2$s</p></div>', esc_attr( $type ), esc_html( $message ) );
			}
		}

		$security = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : false; // phpcs:ignore

		if ( wp_verify_nonce( $security, 'mwpal-reports-nonce' ) && isset( $_POST['mwpal-generate-report'] ) ) :
			?>
			<div id="mwpal-extend-report" class="notice notice-info" style="display:none">
				<p><?php esc_html_e( 'By default the reports are generated from the events imported in the MainWP extension.', 'mwp-al-ext' ); ?></p>
				<p><?php esc_html_e( 'Do you want to also include events from the child sites’ activity logs in the report? If you include events from child sites the plugin will take a bit longer to generate the report.', 'mwp-al-ext' ); ?></p>
				<p>
					<input class="button-primary" type="button" data-extend="true" value="<?php esc_attr_e( 'Yes, extend the report', 'mwp-al-ext' ); ?>">
					<input class="button-primary" type="button" data-extend="false" value="<?php esc_attr_e( 'No', 'mwp-al-ext' ); ?>">
				</p>
			</div>
			<div id="mwpal-report-generate-response" class="notice notice-success" style="display:none">
				<div class="mwpal-lds-dual-ring"></div>
				<p><?php esc_html_e( ' Generating report. Please do not close this window.', 'mwp-al-ext' ); ?><span></span></p>
			</div>
			<?php
			// Check selected sites for report.
			if ( isset( $_POST['mwpal-rb-sites'] ) ) {
				$rbs   = intval( $_POST['mwpal-rb-sites'] );
				$sites = ! empty( $_POST['mwpal-rep-sites'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-rep-sites'] ) ) ) : array();

				if ( 2 === $rbs && 1 === count( $sites ) && in_array( 'dashboard', $sites, true ) ) :
					?>
					<script>
						window.onload = function( event ) {
							var extendNoticeInputs = document.querySelectorAll( '#mwpal-extend-report input[data-extend=false]' );
							extendNoticeInputs.forEach( function( item ) { item.click() });
						}
					</script>
					<?php
				endif;
			}
		endif;
	}

	/**
	 * Ajax handler for generating periodic report.
	 */
	public function generate_periodic_report() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'mwpal-report-security', 'security' );

		$filters             = isset( $_POST['filters'] ) ? wp_unslash( $_POST['filters'] ) : false;
		$filters['nextDate'] = isset( $_POST['nextDate'] ) ? sanitize_text_field( wp_unslash( $_POST['nextDate'] ) ) : false;
		$filters['limit']    = isset( $_POST['limit'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['limit'] ) ) : false;

		// Check for live report.
		$fetch_live = isset( $_POST['liveReport'] ) ? sanitize_text_field( wp_unslash( $_POST['liveReport'] ) ) : false;

		// Generate report.
		if ( 'false' === $fetch_live ) {
			$report = get_reports_common()->generate_report( $filters, false );
		} else {
			$report = get_reports_common()->generate_live_report( $filters );
		}

		// Append to the JSON file.
		get_reports_common()->generate_report_json_file( $report );

		$response[0] = ! empty( $report['lastDate'] ) ? $report['lastDate'] : 0;

		if ( ! $response[0] ) {
			$response[1] = get_reports_common()->download_report_file();
		}

		echo wp_json_encode( $response );
		exit;
	}

	/**
	 * Send periodic report manually.
	 */
	public function send_periodic_report() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'mwpal-report-security', 'security' );

		$report_name = isset( $_POST['reportName'] ) ? sanitize_text_field( wp_unslash( $_POST['reportName'] ) ) : false;
		$next_date   = isset( $_POST['nextDate'] ) ? sanitize_text_field( wp_unslash( $_POST['nextDate'] ) ) : false;
		$limit       = isset( $_POST['limit'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['limit'] ) ) : false;
		$last_date   = get_reports_common()->send_periodic_now( $report_name, $next_date, $limit );
		$response    = $last_date ? $last_date : 0;

		echo wp_json_encode( $response );
		exit();
	}
}

new Reports_View();
