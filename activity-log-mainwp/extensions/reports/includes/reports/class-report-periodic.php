<?php
/**
 * Periodic Report.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use WSAL\MainWPExtension as MWPAL_Extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Periodic report class.
 */
class Report_Periodic extends Abstract_Report {

	/**
	 * Type of report.
	 *
	 * @var string
	 */
	private $report_type = 'periodic';

	/**
	 * Current report.
	 *
	 * @var null|stdClass
	 */
	private $current_report = null;

	/**
	 * Generate report now flag.
	 *
	 * @var boolean
	 */
	public $generate_now = false;

	/**
	 * Report filters.
	 *
	 * Used during generating immediate report.
	 *
	 * @var array
	 */
	private $filters = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( isset( $_GET['_wpnonce'] ) ) {
			$report_name = isset( $_GET['report'] ) ? sanitize_text_field( wp_unslash( $_GET['report'] ) ) : false; // phpcs:ignore
			$action      = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : false; // phpcs:ignore

			check_admin_referer( $report_name . '-' . $action );

			if ( 'modify' === $action ) {
				$this->current_report = get_report( $report_name );
			}
		}

		$deleted = isset( $_GET['deleted'] ) ? sanitize_text_field( wp_unslash( $_GET['deleted'] ) ) : false; // phpcs:ignore

		if ( $deleted && 'true' === $deleted ) {
			$this->add_notice( 'success', __( 'Periodic report deleted successfully.', 'mwp-al-ext' ) );
		} elseif ( $deleted && 'false' === $deleted ) {
			$this->add_notice( 'error', __( 'Periodic report name not found.', 'mwp-al-ext' ) );
		}
	}

	/**
	 * Returns the type of report.
	 *
	 * @return string
	 */
	public function get_report_type() {
		return $this->report_type;
	}

	/**
	 * Outputs the reports type section.
	 */
	protected function type_section() {
		$users   = '';
		$roles   = '';
		$ips     = '';
		$checked = array();

		if ( ! is_null( $this->current_report ) ) {
			$users   = isset( $this->current_report->users ) ? implode( ',', $this->current_report->users ) : '';
			$roles   = isset( $this->current_report->roles ) ? implode( ',', $this->current_report->roles ) : '';
			$ips     = isset( $this->current_report->ip_addresses ) ? implode( ',', $this->current_report->ip_addresses ) : '';
			$checked = isset( $this->current_report->view_state ) ? $this->current_report->view_state : array();
		}
		?>
		<div id="mwpal-contentbox-reports-type" class="postbox">
			<h2 class="hndle ui-sortable-handle"><span><i class="fa fa-cog"></i> <?php esc_html_e( 'Step 1: Select the type of report', 'mwp-al-ext' ); ?></span></h2>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'By Site(s)', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<label for="mwpal-rb-sites-1">
									<input type="radio" name="mwpal-rb-sites" id="mwpal-rb-sites-1" value="1" checked="checked" />
									<?php esc_html_e( 'All sites', 'mwp-al-ext' ); ?>
								</label>
								<br>
								<label for="mwpal-rb-sites-2">
									<input type="radio" name="mwpal-rb-sites" id="mwpal-rb-sites-2" value="2" />
									<?php esc_html_e( 'Specify sites', 'mwp-al-ext' ); ?>
									<input type="hidden" name="mwpal-rep-sites" id="mwpal-rep-sites" />
								</label>
							</fieldset>
						</td>
					</tr>
					<!-- By site(s) -->
					<tr>
						<th scope="row"><label><?php esc_html_e( 'By User(s)', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<label for="mwpal-rb-users-1">
									<input type="radio" name="mwpal-rb-users" id="mwpal-rb-users-1" value="1" checked="checked" />
									<?php esc_html_e( 'All users', 'mwp-al-ext' ); ?>
								</label>
								<br>
								<label for="mwpal-rb-users-2">
									<input type="radio" name="mwpal-rb-users" id="mwpal-rb-users-2" value="2" <?php checked( (bool) $users ); ?> />
									<?php esc_html_e( 'Specify users', 'mwp-al-ext' ); ?>
									<input type="text" name="mwpal-rep-users" id="mwpal-rep-users" value="<?php echo esc_attr( $users ); ?>" />
								</label>
							</fieldset>
						</td>
					</tr>
					<!-- By user(s) -->
					<tr>
						<th scope="row"><label><?php esc_html_e( 'By Role(s)', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<label for="mwpal-rb-roles-1">
									<input type="radio" name="mwpal-rb-roles" id="mwpal-rb-roles-1" value="1" checked="checked" />
									<?php esc_html_e( 'All roles', 'mwp-al-ext' ); ?>
								</label>
								<br>
								<label for="mwpal-rb-roles-2">
									<input type="radio" name="mwpal-rb-roles" id="mwpal-rb-roles-2" value="2" <?php checked( (bool) $roles ); ?> />
									<?php esc_html_e( 'Specify roles', 'mwp-al-ext' ); ?>
									<input type="text" name="mwpal-rep-roles" id="mwpal-rep-roles" value="<?php echo esc_attr( $roles ); ?>" />
								</label>
							</fieldset>
						</td>
					</tr>
					<!-- By role(s) -->
					<tr>
						<th scope="row"><label><?php esc_html_e( 'By IP Address(es)', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<label for="mwpal-rb-ip-addresses-1">
									<input type="radio" name="mwpal-rb-ip-addresses" id="mwpal-rb-ip-addresses-1" value="1" checked="checked" />
									<?php esc_html_e( 'All IP addresses', 'mwp-al-ext' ); ?>
								</label>
								<br>
								<label for="mwpal-rb-ip-addresses-2">
									<input type="radio" name="mwpal-rb-ip-addresses" id="mwpal-rb-ip-addresses-2" value="2" <?php checked( (bool) $ips ); ?> />
									<?php esc_html_e( 'Specify IP addresses', 'mwp-al-ext' ); ?>
									<input type="text" name="mwpal-rep-ip-addresses" id="mwpal-rep-ip-addresses" value="<?php echo esc_attr( $ips ); ?>" />
								</label>
							</fieldset>
						</td>
					</tr>
					<!-- By IP address(es) -->
					<tr>
						<th scope="row"><label><?php esc_html_e( 'By Event Code(s)', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset id="mwpal-rb-alert-groups">
								<?php
								$event_cgs = get_event_categories();
								$count_cgs = count( $event_cgs );

								echo '<label for="mwpal-rb-groups">';
								echo '<input type="radio" name="mwpal-rb-groups" id="mwpal-rb-groups" value="0"';
								echo ( empty( $checked ) || count( $checked ) === $count_cgs ) ? ' checked>' : ' >';
								esc_html_e( 'Select All', 'mwp-al-ext' );
								echo '</label>';
								echo '<br>';

								foreach ( $event_cgs as $index => $category ) {
									$id    = 'mwpal-rb-alert-' . $index;
									$class = 'mwpal-rb-alert-' . str_replace( ' ', '-', strtolower( $category ) );

									echo '<label for="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '">';
									echo '<input type="checkbox" name="mwpal-rb-alerts[]" id="' . esc_attr( $id ) . '" class="mwpal-alert-groups" value="' . esc_attr( $category ) . '"';
									echo ( in_array( $category, $checked, true ) && count( $checked ) < $count_cgs ) ? ' checked >' : '>';
									echo esc_html( $category );
									echo '</label>';
									echo '<br>';
								}

								echo '<label for="mwpal-rb-alert-codes" class="mwpal-rb-alert-codes-label">';
								echo '<input type="checkbox" name="mwpal-rb-alert-codes" id="mwpal-rb-alert-codes" />';
								esc_html_e( 'Specify Event Codes', 'mwp-al-ext' );
								echo '<br><input type="hidden" name="mwpal-rep-alert-codes" id="mwpal-rep-alert-codes" />';
								echo '</label>';
								?>
							</fieldset>
						</td>
					</tr>
					<!-- By event code(s) -->
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the date range section.
	 */
	protected function date_section() {
		$date_format = get_date_format();
		?>
		<div id="mwpal-contentbox-reports-date-range" class="postbox">
			<h2 class="hndle ui-sortable-handle"><span><i class="fa fa-cog"></i> <?php esc_html_e( 'Step 2: Select the date range', 'mwp-al-ext' ); ?></span></h2>
			<div class="mainwp-postbox-actions-top"><p class="description"><?php esc_html_e( 'Note: Do not specify any dates if you are creating a scheduled report or if you want to generate a report from when you started the audit trail.', 'mwp-al-ext' ); ?></p></div>
			<div class="inside">
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Start Date', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<input type="text" class="date-range" id="mwpal-rep-start-date" name="mwpal-start-date" placeholder="<?php esc_attr_e( 'Select start date', 'mwp-al-ext' ); ?>" />
								<span class="description"> (<?php echo esc_html( $date_format ); ?>)</span>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'End Date', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<input type="text" class="date-range" id="mwpal-rep-end-date" name="mwpal-end-date" placeholder="<?php esc_attr_e( 'Select end date', 'mwp-al-ext' ); ?>" />
								<span class="description"> (<?php echo esc_html( $date_format ); ?>)</span>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the date format section.
	 */
	protected function format_section() {
		$format = 'html';

		if ( ! is_null( $this->current_report ) ) {
			$format = isset( $this->current_report->type ) ? $this->current_report->type : $format;
		}
		?>
		<div id="mwpal-contentbox-reports-format" class="postbox">
			<h2 class="hndle ui-sortable-handle"><span><i class="fa fa-cog"></i> <?php esc_html_e( 'Step 3: Select the report format', 'mwp-al-ext' ); ?></span></h2>
			<div class="inside">
				<table class="form-table">
					<tr>
						<td>
							<fieldset>
								<label for="mwpal-rb-report-type-html">
									<input type="radio" id="mwpal-rb-report-type-html" name="mwpal-rb-report-type" value="html" <?php checked( $format, 'html' ); ?> /><?php esc_html_e( 'HTML', 'mwp-al-ext' ); ?>
								</label>
								<br>
								<label for="mwpal-rb-report-type-csv">
									<input type="radio" id="mwpal-rb-report-type-csv" name="mwpal-rb-report-type" value="csv" <?php checked( $format, 'csv' ); ?> /><?php esc_html_e( 'CSV', 'mwp-al-ext' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the generate section.
	 */
	protected function generate_section() {
		$email       = '';
		$report_name = '';

		if ( ! is_null( $this->current_report ) ) {
			$email       = isset( $this->current_report->email ) ? $this->current_report->email : $email;
			$report_name = isset( $this->current_report->title ) ? $this->current_report->title : $report_name;
		}
		?>
		<div id="mwpal-contentbox-reports-generate" class="postbox">
			<h2 class="hndle ui-sortable-handle"><span><i class="fa fa-cog"></i> <?php esc_html_e( 'Step 4: Generate Report Now or Configure Periodic Reports', 'mwp-al-ext' ); ?></span></h2>
			<div class="inside">
				<?php submit_button( __( 'Generate Report Now', 'mwp-al-ext' ), 'primary', 'mwpal-generate-report' ); ?>
				<p class="description"><?php esc_html_e( 'Use the buttons below to use the above criteria for a daily, weekly and monthly summary report which is sent automatically via email.', 'mwp-al-ext' ); ?></p>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Email Address(es)', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<input type="text" name="mwpal-notif-email" id="mwpal-notif-email" placeholder="<?php esc_attr_e( 'Email', 'mwp-al-ext' ); ?> *" value="<?php echo esc_attr( $email ); ?>" />
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Report Name', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<input type="text" name="mwpal-notif-name" id="mwpal-notif-name" placeholder="<?php esc_attr_e( 'Name', 'mwp-al-ext' ); ?>" value="<?php echo esc_attr( $report_name ); ?>" />
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Frequency', 'mwp-al-ext' ); ?></label></th>
						<td>
							<fieldset>
								<?php
								submit_button( __( 'Daily', 'mwp-al-ext' ), 'primary', 'mwpal-periodic', false );
								echo '&nbsp;';
								submit_button( __( 'Weekly', 'mwp-al-ext' ), 'primary', 'mwpal-periodic', false );
								echo '&nbsp;';
								submit_button( __( 'Monthly', 'mwp-al-ext' ), 'primary', 'mwpal-periodic', false );
								echo '&nbsp;';
								submit_button( __( 'Quarterly', 'mwp-al-ext' ), 'primary', 'mwpal-periodic', false );
								?>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the configured reports section.
	 */
	protected function configured_section() {
		// Get periodic reports.
		$reports = get_reports();

		if ( ! is_array( $reports ) ) {
			return;
		}
		?>
		<div id="mwpal-contentbox-reports-generated" class="postbox">
			<h2 class="hndle ui-sortable-handle"><span><i class="fa fa-cog"></i> <?php esc_html_e( 'Configured Periodic Reports', 'mwp-al-ext' ); ?></span></h2>
			<div class="inside">
				<p class="description"><?php esc_html_e( 'Below is the list of configured periodic reports. Click on Modify to load the criteria and configure it above. To save the new criteria as a new report change the report name and save it. Do not change the report name to overwrite the existing periodic report.', 'mwp-al-ext' ); ?></p>
				<p class="description"><?php esc_html_e( 'Note: Use the Send Now button to generate a report with data from the last 90 days if a quarterly report is configured, 30 days if monthly report is configured and 7 days if weekly report is configured.', 'mwp-al-ext' ); ?></p>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'mwp-al-ext' ); ?></th>
							<th><?php esc_html_e( 'Email address(es)', 'mwp-al-ext' ); ?></th>
							<th><?php esc_html_e( 'Frequency', 'mwp-al-ext' ); ?></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $reports as $report_name => $report ) :
							$emails      = explode( ',', $report->email );
							$report_name = str_replace( array( MWPAL_OPT_PREFIX, MWPAL_PREPORT_PREFIX ), '', $report_name );
							$report_link = add_query_arg( 'report', $report_name );
							$modify      = add_query_arg( 'action', 'modify', $report_link );
							$delete      = add_query_arg( 'action', 'delete', $report_link );

							if ( 'quarterly' === $report->frequency ) {
								$frequency = __( 'Quarterly', 'mwp-al-ext' );
							} elseif ( 'monthly' === $report->frequency ) {
								$frequency = __( 'Monthly', 'mwp-al-ext' );
							} elseif ( 'weekly' === $report->frequency ) {
								$frequency = __( 'Weekly', 'mwp-al-ext' );
							} else {
								$frequency = __( 'Daily', 'mwp-al-ext' );
							}
							?>
							<tr>
								<td><?php echo esc_html( $report->title ); ?></td>
								<td>
									<?php
									foreach ( $emails as $email ) {
										echo esc_html( $email ) . '<br>';
									}
									?>
								</td>
								<td><?php echo esc_html( $frequency ); ?></td>
								<td><input type="button" class="report-send-now button-secondary" data-report-name="<?php echo esc_attr( $report_name ); ?>" value="<?php esc_attr_e( 'Send Now', 'mwp-al-ext' ); ?>"></td>
								<td><a href="<?php echo esc_url( wp_nonce_url( $modify, $report_name . '-modify' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Modify', 'mwp-al-ext' ); ?></a></td>
								<td><a href="<?php echo esc_url( wp_nonce_url( $delete, $report_name . '-delete' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Delete', 'mwp-al-ext' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Localized script data.
	 */
	public function localized_script_data() {
		$selected_events = '';
		if ( ! is_null( $this->current_report ) && ! empty( $this->current_report->view_state ) ) {
			foreach ( $this->current_report->view_state as $key => $state ) {
				if ( 'codes' === $state ) {
					$selected_events = $this->current_report->triggers[ $key ]['alert-id'];
				}
			}
		}

		$script_data = array(
			'reportType'      => $this->report_type,
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'security'        => wp_create_nonce( 'mwpal-report-security' ),
			'sites'           => wp_json_encode( get_sites_for_select2() ),
			'selectSites'     => __( 'Select site(s)', 'mwp-al-ext' ),
			'events'          => json_encode( MWPAL_Extension\Helpers\DataHelper::get_events_for_select2() ),
			'selectEvents'    => __( 'Select event code(s)', 'mwp-al-ext' ),
			'dateFormat'      => get_date_format(),
			'selectedSites'   => ( ! is_null( $this->current_report ) && ! empty( $this->current_report->sites ) ) ? $this->current_report->sites : '',
			'selectedEvents'  => $selected_events,
			'siteRequired'    => __( 'Please specify at least one site.', 'mwp-al-ext' ),
			'userRequired'    => __( 'Please specify at least one user.', 'mwp-al-ext' ),
			'roleRequired'    => __( 'Please specify at least one role.', 'mwp-al-ext' ),
			'ipRequired'      => __( 'Please specify at least one IP address.', 'mwp-al-ext' ),
			'eventRequired'   => __( 'Please specify at least one event group or specify an event code.', 'mwp-al-ext' ),
			'processComplete' => __( 'Process completed.', 'mwp-al-ext' ),
			'noMatchEvents'   => __( 'There are no events that match your filtering criteria.', 'mwp-al-ext' ),
			'sendingReport'   => __( 'Sending...', 'mwp-al-ext' ),
			'reportSent'      => __( 'Email sent', 'mwp-al-ext' ),
			'generateNow'     => $this->generate_now,
			'generateFilters' => wp_json_encode( $this->filters ),
			'reportsLimit'    => apply_filters( 'mwpal_reports_limit', 100 ),
		);
		wp_localize_script( 'mwpal-reports-script', 'reportsData', $script_data );
	}

	/**
	 * Save periodic report.
	 */
	public function save() {
		check_admin_referer( 'mwpal-reports-nonce' );

		// The final filter array to use to filter alerts.
		$filters = array(
			// Option #1 - By Site(s).
			'sites'         => array(), // By default, all sites.

			// Option #2 - By user(s).
			'users'         => array(), // By default, all users.

			// Option #3 - By Role(s).
			'roles'         => array(), // By default, all roles.

			// Option #4 - By IP Address(es).
			'ip-addresses'  => array(), // By default, all IPs.

			// Option #5 - By Alert Code(s).
			'alert-codes'   => array(
				'groups' => array(),
				'alerts' => array(),
			),

			// Option #6 - Date range.
			'date-range'    => array(
				'start' => null,
				'end'   => null,
			),

			// Option #7 - Report format (HTML || CSV).
			'report-format' => 'html',
		);

		// The default error message to display if the form is not valid.
		$form_not_valid = __( 'Invalid Request. Please refresh the page and try again.', 'mwp-al-ext' );

		// Region >>>> By Site(s).
		if ( isset( $_POST['mwpal-rb-sites'] ) ) {
			$rbs = intval( $_POST['mwpal-rb-sites'] );

			if ( 2 === $rbs ) {
				// The textbox must be here and have values - these will be validated later on.
				if ( empty( $_POST['mwpal-rep-sites'] ) ) {
					$this->add_notice( 'error', __( 'Error: Please select SITES.', 'mwp-al-ext' ) );
				} else {
					$filters['sites'] = explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-rep-sites'] ) ) );
				}
			}
		} else {
			$this->add_notice( 'error', $form_not_valid );
			return;
		}

		// Region >>>> By User(s).
		if ( isset( $_POST['mwpal-rb-users'] ) ) {
			$rbs = intval( $_POST['mwpal-rb-users'] );

			if ( 2 === $rbs ) {
				// The textbox must be here and have values - these will be validated later on.
				if ( empty( $_POST['mwpal-rep-users'] ) ) {
					$this->add_notice( 'error', __( 'Error: Please provide at least one username.', 'mwp-al-ext' ) );
				} else {
					$filters['users'] = explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-rep-users'] ) ) );
				}
			}
		} else {
			$this->add_notice( 'error', $form_not_valid );
			return;
		}

		// Region >>>> By Role(s).
		if ( isset( $_POST['mwpal-rb-roles'] ) ) {
			$rbs = intval( $_POST['mwpal-rb-roles'] );

			if ( 2 === $rbs ) {
				// The textbox must be here and have values - these will be validated later on.
				if ( empty( $_POST['mwpal-rep-roles'] ) ) {
					$this->add_notice( 'error', __( 'Error: Please provide at least one user role.', 'mwp-al-ext' ) );
				} else {
					$user_roles   = explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-rep-roles'] ) ) );
					$filter_roles = array();

					if ( ! empty( $user_roles ) ) {
						foreach ( $user_roles as $role ) {
							$role           = strtolower( $role );
							$role           = str_replace( ' ', '', $role );
							$filter_roles[] = $role;
						}
					}
					$filters['roles'] = $filter_roles;
				}
			}
		} else {
			$this->add_notice( 'error', $form_not_valid );
			return;
		}

		// Region >>>> By IP address(es).
		if ( isset( $_POST['mwpal-rb-ip-addresses'] ) ) {
			$rbs = intval( $_POST['mwpal-rb-ip-addresses'] );

			if ( 2 === $rbs ) {
				// The textbox must be here and have values - these will be validated later on.
				if ( empty( $_POST['mwpal-rep-ip-addresses'] ) ) {
					$this->add_notice( 'error', __( 'Error: Please provide at least one IP address.', 'mwp-al-ext' ) );
				} else {
					$filters['ip-addresses'] = explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-rep-ip-addresses'] ) ) );
				}
			}
		} else {
			$this->add_notice( 'error', $form_not_valid );
			return;
		}

		// Region >>>> By Alert Code(s).
		$select_all_groups = isset( $_POST['mwpal-rb-groups'] ) ? true : false;

		if ( $select_all_groups ) {
			$filters['alert-codes']['groups'] = get_event_categories();
		} else {
			// Check for selected alert groups.
			if ( ! empty( $_POST['mwpal-rb-alerts'] ) ) {
				$filters['alert-codes']['groups'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['mwpal-rb-alerts'] ) );
			}

			// Check for individual alerts.
			if ( isset( $_POST['mwpal-rb-alert-codes'] ) && ! empty( $_POST['mwpal-rep-alert-codes'] ) ) {
				$filters['alert-codes']['alerts'] = explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-rep-alert-codes'] ) ) );
			}
		}

		// Region >>>> By Date Range(s).
		if ( isset( $_POST['mwpal-start-date'] ) ) {
			$filters['date-range']['start'] = trim( sanitize_text_field( wp_unslash( $_POST['mwpal-start-date'] ) ) );
		}

		if ( isset( $_POST['mwpal-end-date'] ) ) {
			$filters['date-range']['end'] = trim( sanitize_text_field( wp_unslash( $_POST['mwpal-end-date'] ) ) );
		}

		// Region >>>> Reporting Format.
		if ( isset( $_POST['mwpal-rb-report-type'] ) ) {
			$report_type = sanitize_text_field( wp_unslash( $_POST['mwpal-rb-report-type'] ) );

			if ( in_array( $report_type, array( 'html', 'csv' ), true ) ) {
				$filters['report-format'] = $report_type;
			} else {
				$this->add_notice( 'error', __( 'Please select the report format.', 'mwp-al-ext' ) );
			}
		} else {
			$this->add_notice( 'error', $form_not_valid );
			return;
		}

		if ( isset( $_POST['mwpal-periodic'] ) ) {
			// Report frequency.
			$frequency = sanitize_text_field( wp_unslash( $_POST['mwpal-periodic'] ) );

			if ( __( 'Daily', 'mwp-al-ext' ) === $frequency ) {
				$filters['frequency'] = 'daily';
			} elseif ( __( 'Weekly', 'mwp-al-ext' ) === $frequency ) {
				$filters['frequency'] = 'weekly';
			} elseif ( __( 'Monthly', 'mwp-al-ext' ) === $frequency ) {
				$filters['frequency'] = 'monthly';
			} elseif ( __( 'Quarterly', 'mwp-al-ext' ) === $frequency ) {
				$filters['frequency'] = 'quarterly';
			}

			if ( isset( $_POST['mwpal-notif-email'] ) && isset( $_POST['mwpal-notif-name'] ) ) {
				$emails           = explode( ',', sanitize_text_field( wp_unslash( $_POST['mwpal-notif-email'] ) ) );
				$filters['email'] = '';

				foreach ( $emails as $email ) {
					$filters['email'] .= filter_var( trim( $email ), FILTER_SANITIZE_EMAIL ) . ',';
				}

				$filters['email'] = rtrim( $filters['email'], ',' );
				$filters['name']  = trim( sanitize_text_field( wp_unslash( $_POST['mwpal-notif-name'] ) ) );

				$this->save_report( $filters );
				$this->add_notice( 'success', __( 'Periodic Report successfully saved.', 'mwp-al-ext' ) );
			}
		} elseif ( isset( $_POST['mwpal-generate-report'] ) ) {
			$this->generate_now = true;
			$this->filters      = $filters;
		}
	}

	/**
	 * Save report.
	 *
	 * @param array $report_data - Data of the report.
	 */
	private function save_report( $report_data ) {
		if ( ! $report_data ) {
			return;
		}

		// Report name.
		$report_name = strtolower( str_replace( array( ' ', '_' ), '-', $report_data['name'] ) );

		$report            = new \stdClass();
		$report->title     = $report_data['name'];
		$report->email     = $report_data['email'];
		$report->type      = $report_data['report-format'];
		$report->frequency = $report_data['frequency'];
		$report->sites     = array();

		if ( ! empty( $report_data['sites'] ) ) {
			$report->sites = $report_data['sites'];
		}

		if ( ! empty( $report_data['users'] ) ) {
			$report->users = $report_data['users'];
		}

		if ( ! empty( $report_data['roles'] ) ) {
			$report->roles = $report_data['roles'];
		}

		if ( ! empty( $report_data['ip-addresses'] ) ) {
			$report->ip_addresses = $report_data['ip-addresses'];
		}

		$report->owner      = get_current_user_id();
		$report->date_added = time();
		$report->status     = 1;
		$report->view_state = array();
		$report->triggers   = array();

		if ( ! empty( $report_data['alert-codes']['alerts'] ) ) {
			$report->view_state[] = 'codes';
			$report->triggers[]   = array( 'alert-id' => $report_data['alert-codes']['alerts'] );
		}

		if ( ! empty( $report_data['alert-codes']['groups'] ) ) {
			foreach ( $report_data['alert-codes']['groups'] as $group ) {
				$codes                = get_events_by_group( $group );
				$report->view_state[] = $group;
				$report->triggers[]   = array( 'alert-id' => $codes );
			}
		}

		save_report( $report_name, $report );
	}

	/**
	 * Delete report.
	 *
	 * @param string $report_name - Report name.
	 */
	public function delete_report( $report_name ) {
		if ( $report_name ) {
			delete_report( $report_name );
			$reports_url = add_query_arg( 'deleted', 'true' );
		} else {
			$reports_url = add_query_arg( 'deleted', 'false' );
		}

		$reports_url = remove_query_arg( array( 'report', 'action', '_wpnonce' ), $reports_url );
		wp_safe_redirect( $reports_url );
		die();
	}
}
