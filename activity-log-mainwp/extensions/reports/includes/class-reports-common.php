<?php
/**
 * Common reports class.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use WSAL\MainWPExtension as MWPAL_Extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Common reports class.
 */
class Reports_Common {

	/**
	 * Single instance of this class.
	 *
	 * @var Reports_Common
	 */
	private static $instance = null;

	/**
	 * Date format.
	 *
	 * @var string
	 */
	private $date_format;

	/**
	 * Datetime format.
	 *
	 * @var string
	 */
	private $datetime_format;

	/**
	 * MainWP Child Sites
	 *
	 * @var array
	 */
	private $mwp_child_sites;

	/**
	 * Schedule hook name.
	 *
	 * @var string
	 */
	private static $schedule_hook = 'mwpal_summary_email_reports';

	/**
	 * Daily report frequency.
	 *
	 * @var string
	 */
	public static $report_daily = 'daily';

	/**
	 * Weekly report frequency.
	 *
	 * @var string
	 */
	public static $report_weekly = 'weekly';

	/**
	 * Monthly report frequency.
	 *
	 * @var string
	 */
	public static $report_monthly = 'monthly';

	/**
	 * Quarterly report frequency.
	 *
	 * @var string
	 */
	public static $report_quarterly = 'quarterly';

	/**
	 * Frequency daily hour
	 * For testing change hour here [01 to 23]
	 *
	 * @var string
	 */
	private static $daily_hour = '08';

	/**
	 * Frequency montly date
	 * For testing change date here [01 to 31]
	 *
	 * @var string
	 */
	private static $monthly_day = '01';

	/**
	 * Frequency weekly date
	 * For testing change date here [1 (for Monday) through 7 (for Sunday)]
	 *
	 * @var string
	 */
	private static $weekly_day = '1';

	/**
	 * Get instance of Reports_Common.
	 *
	 * @return Reports_Common
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->date_format = 'Y-m-d';

		// Reports cron job.
		add_action( self::$schedule_hook, array( $this, 'send_scheduled_reports' ) );

		if ( ! wp_next_scheduled( self::$schedule_hook ) ) {
			wp_schedule_event( time(), 'hourly', self::$schedule_hook );
		}
	}

	/**
	 * Returns the date format.
	 *
	 * @return string
	 */
	public function get_date_format() {
		return $this->date_format;
	}

	/**
	 * Returns the date time format.
	 *
	 * @return string
	 */
	public function get_date_time_format() {
		$this->datetime_format = ! $this->datetime_format ? MWPAL_Extension\mwpal_extension()->settings->get_date_time_format( false ) : $this->datetime_format;
		return $this->datetime_format;
	}

	/**
	 * Returns MainWP child sites.
	 *
	 * @return array
	 */
	public function get_mwp_child_sites() {
		$this->mwp_child_sites = empty( $this->mwp_child_sites ) ? MWPAL_Extension\mwpal_extension()->settings->get_mwp_child_sites() : $this->mwp_child_sites;
		return $this->mwp_child_sites;
	}

	/**
	 * Generate report matching the filter passed.
	 *
	 * @param array $filters  - Filters.
	 * @param bool  $validate - (Optional) Validation.
	 * @return array $data_and_filters
	 */
	public function generate_report( array $filters, $validate = true ) {
		// Begin filters validation.
		if ( $validate ) {
			if ( ! isset( $filters['sites'] ) ) {
				return false;
			}

			if ( ! isset( $filters['users'] ) ) {
				return false;
			}

			if ( ! isset( $filters['roles'] ) ) {
				return false;
			}

			if ( ! isset( $filters['ip-addresses'] ) ) {
				return false;
			}

			if ( ! isset( $filters['alert-codes'] ) ) {
				return false;
			}

			if ( ! isset( $filters['alert-codes']['groups'] ) ) {
				return false;
			}

			if ( ! isset( $filters['alert-codes']['alerts'] ) ) {
				return false;
			}

			if ( ! isset( $filters['date-range'] ) ) {
				return false;
			}

			if ( ! isset( $filters['date-range']['start'] ) ) {
				return false;
			}

			if ( ! isset( $filters['date-range']['end'] ) ) {
				return false;
			}

			if ( ! isset( $filters['report-format'] ) ) {
				return false;
			}
		}

		// Filters.
		$sites         = empty( $filters['sites'] ) ? null : $filters['sites'];
		$users         = empty( $filters['users'] ) ? array() : $filters['users'];
		$roles         = empty( $filters['roles'] ) ? null : $filters['roles'];
		$ip_addresses  = empty( $filters['ip-addresses'] ) ? null : $filters['ip-addresses'];
		$alert_groups  = empty( $filters['alert-codes']['groups'] ) ? null : $filters['alert-codes']['groups'];
		$alert_codes   = empty( $filters['alert-codes']['alerts'] ) ? null : $filters['alert-codes']['alerts'];
		$date_start    = empty( $filters['date-range']['start'] ) ? null : $filters['date-range']['start'];
		$date_end      = empty( $filters['date-range']['end'] ) ? null : $filters['date-range']['end'];
		$report_format = empty( $filters['report-format'] ) ? 'html' : 'csv';

		$next_date = empty( $filters['nextDate'] ) ? null : $filters['nextDate'];
		$limit     = empty( $filters['limit'] ) ? 0 : $filters['limit'];

		if ( empty( $alert_groups ) && empty( $alert_codes ) ) {
			return false;
		}

		if ( ! in_array( $report_format, array( 'csv', 'html' ), true ) ) {
			return false;
		}

		// Check alert codes and post types.
		$codes = $this->get_codes_by_groups( $alert_groups, $alert_codes );
		if ( ! $codes ) {
			return false;
		}

		// Check for MainWP dashboard site id and replace it with 0.
		if ( $sites && is_array( $sites ) && in_array( 'dashboard', $sites, true ) ) {
			$key = array_search( 'dashboard', $sites, true );

			if ( false !== $key ) {
				$sites[ $key ] = 0;
			}
		}

		/**
		 * -- @userId: COMMA-SEPARATED-LIST WordPress user id
		 * -- @siteId: COMMA-SEPARATED-LIST WordPress site id
		 * -- @postType: COMMA-SEPARATED-LIST WordPress post types
		 * -- @postStatus: COMMA-SEPARATED-LIST WordPress post statuses
		 * -- @roleName: REGEXP (must be quoted from PHP)
		 * -- @alertCode: COMMA-SEPARATED-LIST of numeric alert codes
		 * -- @startTimestamp: UNIX_TIMESTAMP
		 * -- @endTimestamp: UNIX_TIMESTAMP
		 *
		 * Usage:
		 * --------------------------
		 * set @siteId = null; -- '1,2,3,4....';
		 * set @userId = null;
		 * set @postType = null; -- 'post,page';
		 * set @postStatus = null; -- 'publish,draft';
		 * set @roleName = null; -- '(administrator)|(editor)';
		 * set @alertCode = null; -- '1000,1002';
		 * set @startTimestamp = null;
		 * set @endTimestamp = null;
		 */
		$site_ids   = $sites ? "'" . implode( ',', $sites ) . "'" : 'null';
		$user_ids   = $this->get_user_ids( $users );
		$user_names = array();

		if ( is_array( $users ) && ! empty( $users ) ) {
			foreach ( $users as $username ) {
				$user_names[] = '"' . trim( $username ) . '"';
			}

			$usernames = implode( ', ', $user_names );
		} else {
			$usernames = 'null';
		}

		$role_names      = 'null';
		$start_timestamp = 'null';
		$end_timestamp   = 'null';

		if ( $roles ) {
			$role_names = array();

			foreach ( $roles as $role ) {
				array_push( $role_names, esc_sql( '(' . preg_quote( $role ) . ')' ) );
			}

			$role_names = "'" . implode( '|', $role_names ) . "'";
		}

		$ip_address  = $ip_addresses ? "'" . implode( ',', $ip_addresses ) . "'" : 'null';
		$alert_codes = ! empty( $codes ) ? "'" . implode( ',', $codes ) . "'" : 'null';

		if ( $date_start ) {
			$datetime        = \DateTime::createFromFormat( $this->get_date_format() . ' H:i:s', $date_start . ' 00:00:00' );
			$start_timestamp = $datetime->format( 'U' );
		}

		if ( $date_end ) {
			$datetime      = \DateTime::createFromFormat( $this->get_date_format() . ' H:i:s', $date_end . ' 23:59:59' );
			$end_timestamp = $datetime->format( 'U' );
		}

		$last_date = null;
		$results = MWPAL_Extension\mwpal_extension()->get_connector()->getAdapter( 'Occurrence' )->GetReporting( $site_ids, $user_ids, $role_names, $alert_codes, $start_timestamp, $end_timestamp, $next_date, $limit, $usernames );

		if ( ! empty( $results['lastDate'] ) ) {
			$last_date = $results['lastDate'];
			unset( $results['lastDate'] );
		}

		if ( empty( $results ) ) {
			return false;
		}

		$data             = array();
		$data_and_filters = array();

		// Get alert details.
		foreach ( $results as $entry ) {
			$ip    = esc_html( $entry->ip );
			$ua    = esc_html( $entry->ua );
			$roles = maybe_unserialize( $entry->roles );

			if ( is_array( $roles ) ) {
				$roles = implode( ', ', $roles );
			} else {
				$roles = '';
			}

			if ( 9999 === (int) $entry->alert_id ) {
				continue;
			}

			$t = $this->get_alert_details( $entry->id, $entry->alert_id, $entry->site_id, $entry->created_on, $entry->user_id, $roles, $ip, $ua );
			array_push( $data, $t );
		}

		if ( empty( $data ) ) {
			return false;
		}

		$data_and_filters['data']     = $data;
		$data_and_filters['filters']  = $filters;
		$data_and_filters['lastDate'] = $last_date;

		return $data_and_filters;
	}

	/**
	 * Get codes by groups.
	 *
	 * If we have alert groups, we need to retrieve all alert codes for those groups
	 * and add them to a final alert of alert codes that will be sent to db in the select query
	 * the same goes for individual alert codes.
	 *
	 * @param array $event_groups - Event groups.
	 * @param array $event_codes  - Event codes.
	 * @param bool  $show_error   - (Optional) False if errors do not need to be displayed.
	 */
	private function get_codes_by_groups( $event_groups, $event_codes, $show_error = true ) {
		$_codes           = array();
		$has_event_groups = empty( $event_groups ) ? false : true;
		$has_event_codes  = empty( $event_codes ) ? false : true;

		if ( $has_event_codes ) {
			// Add the specified alerts to the final array.
			$_codes = $event_codes;
		}

		if ( $has_event_groups ) {
			// Get categorized alerts.
			$cat_alerts = MWPAL_Extension\Helpers\DataHelper::get_categorized_events();
			if ( empty( $cat_alerts ) ) {
				return false;
			}

			// Make sure that all specified alert categories are valid.
			foreach ( $event_groups as $category ) {
				// get alerts from the category and add them to the final array
				// #! only if the specified category is valid, otherwise skip it.
				if ( isset( $cat_alerts[ $category ] ) ) {
					// If this is the "System Activity" category...some of those alert needs to be padded.
					if ( __( 'System Activity', 'mwp-al-ext' ) === $category ) {
						foreach ( $cat_alerts[ $category ] as $alert ) {
							$aid = $alert->type;

							if ( 1 === strlen( $aid ) ) {
								$aid = $this->pad_key( $aid );
							}

							array_push( $_codes, $aid );
						}
					} else {
						foreach ( $cat_alerts[ $category ] as $alert ) {
							array_push( $_codes, $alert->type );
						}
					}
				}
			}
		}

		if ( empty( $_codes ) ) {
			return false;
		}

		return $_codes;
	}

	/**
	 * Key padding.
	 *
	 * @internal
	 * @param string $key - The key to pad.
	 * @return string
	 */
	private function pad_key( $key ) {
		return 1 === strlen( $key ) ? str_pad( $key, 4, '0', STR_PAD_LEFT ) : $key;
	}

	/**
	 * Get alert details.
	 *
	 * @param int          $entry_id   - Entry ID.
	 * @param int          $alert_id   - Alert ID.
	 * @param int          $site_id    - Site ID.
	 * @param string       $created_on - Alert generation time.
	 * @param string       $username   - Username.
	 * @param string|array $roles      - User roles.
	 * @param string       $ip         - IP address of the user.
	 * @param string       $ua         - User agent.
	 * @return array|false details
	 */
	private function get_alert_details( $entry_id, $alert_id, $site_id, $created_on, $username = null, $roles = null, $ip = '', $ua = '' ) {
		// Must be a new instance every time, otherwise the alert message is not retrieved properly.
		$this->ko = new Utility_Occurrence();

		// Get alert details.
		$code  = MWPAL_Extension\mwpal_extension()->alerts->GetAlert( $alert_id );
		$code  = $code ? $code->code : 0;
		$const = (object) array(
			'name'        => 'E_UNKNOWN',
			'value'       => 0,
			'description' => __( 'Unknown error code.', 'mwp-al-ext' ),
		);
		$const = MWPAL_Extension\mwpal_extension()->constants->GetConstantBy( 'value', $code, $const );

		$site_id    = (string) $site_id;
		$site_index = array_search( $site_id, array_column( $this->get_mwp_child_sites(), 'id' ), true );

		// Blog details.
		$blog_url  = '';

		if ( false !== $site_index && isset( $this->get_mwp_child_sites()[ $site_index ] ) ) {
			$blog_name = $this->get_mwp_child_sites()[ $site_index ]['name'];
			$blog_url  = $this->get_mwp_child_sites()[ $site_index ]['url'];
		} else {
			$blog_name = __( 'MainWP Dashboard', 'mwp-al-ext' );
		}

		// Get the alert message - properly.
		$this->ko->id         = $entry_id;
		$this->ko->site_id    = $site_id;
		$this->ko->alert_id   = $alert_id;
		$this->ko->created_on = $created_on;

		if ( $this->ko->is_migrated ) {
			$this->ko->_cachedmessage = $this->ko->GetMetaValue( 'MigratedMesg', false );
		}

		if ( ! $this->ko->is_migrated || ! $this->ko->_cachedmessage ) {
			$this->ko->_cachedmessage = $this->ko->GetAlert()->mesg;
		}

		if ( ! $username ) {
			$username = __( 'System', 'mwp-al-ext' );
			$roles    = '';
		}

		$formattedDate = MWPAL_Extension\Utilities\DateTimeFormatter::instance()->getFormattedDateTime( $created_on, 'datetime', true, false, false, false );

		// Meta details.
		$out = array(
			'site_id'    => $site_id,
			'blog_name'  => $blog_name,
			'blog_url'   => $blog_url,
			'alert_id'   => $alert_id,
			'date'       => $formattedDate,
			//  We need to keep the timestamp to be able to group entries by dates etc. The "date" field is not suitable
			//  as it is already translated, thus difficult to parse and process.
			'timestamp'  => $created_on,
			'code'       => $const->name,
			'message'    => $this->ko->GetAlert()->GetMessage( $this->ko->GetMetaArray(), array( \WSAL\MainWPExtension\mwpal_extension()->settings, 'meta_formatter' ), $this->ko->_cachedmessage ),
			'user_name'  => $username,
			'user_data'  => $this->ko->GetMetaValue( 'UserData', false ),
			'role'       => $roles,
			'user_ip'    => $ip,
			'user_agent' => $ua,
		);
		return $out;
	}

	/**
	 * Appending the report data to the content of the json file.
	 *
	 * @param string $report - Report data.
	 */
	public function generate_report_json_file( $report ) {
		global $wp_filesystem;
		WP_Filesystem();
		$this->check_reports_directory();

		$filename = MWPAL_REPORTS_UPLOAD_PATH . 'report-user' . get_current_user_id() . '.json';

		if ( file_exists( $filename ) ) {
			$file_contents = $wp_filesystem->get_contents( $filename );
			$data          = json_decode( $file_contents, true );

			if ( ! empty( $data ) && ! empty( $report ) ) {
				foreach ( $report['data'] as $value ) {
					array_push( $data['data'], $value );
				}
				$wp_filesystem->put_contents( $filename, wp_json_encode( $data ) );
			}
		} elseif ( ! empty( $report ) ) {
			$wp_filesystem->put_contents( $filename, wp_json_encode( $report ) );
		}
	}

	/**
	 * Create reports directory if it does not exist.
	 */
	private function check_reports_directory() {
		$reports_dir = str_replace( MWPAL_UPLOADS_DIR, '', MWPAL_REPORTS_UPLOAD_PATH );

		if ( ! is_dir( MWPAL_REPORTS_UPLOAD_PATH ) ) {
			MWPAL_Extension\create_htaccess_file( $reports_dir );
			MWPAL_Extension\create_index_file( $reports_dir );
		}
	}

	/**
	 * Generate the file on download it.
	 *
	 * @return string Download report file URL.
	 */
	public function download_report_file() {
		global $wp_filesystem;

		$download_page_url = null;
		$filename          = MWPAL_REPORTS_UPLOAD_PATH . 'report-user' . get_current_user_id() . '.json';

		if ( file_exists( $filename ) ) {
			WP_Filesystem();

			$data   = json_decode( $wp_filesystem->get_contents( $filename ), true );
			$result = $this->file_generator( $data['data'], $data['filters'] );

			if ( ! empty( $result ) ) {
				$e                 = '&f=' . base64_encode( $result ) . '&ctype=' . $data['filters']['report-format'];
				$download_page_url = wp_nonce_url( MWPAL_REPORTS_URL . 'download.php', 'mwpal_reporting_security', 'mwpal_report_download' ) . $e;
			}
		}

		@unlink( $filename );
		return $download_page_url;
	}

	/**
	 * Generate the file of the report (HTML or CSV).
	 *
	 * @param array $data - Data.
	 * @param array $filters - Filters.
	 * @return string|bool - Filename or false.
	 */
	private function file_generator( $data, $filters ) {
		$report_format = ! empty( $filters['report-format'] ) ? $filters['report-format'] : 'html';
		if ( 'html' === $report_format ) {
			$html_report = new HTML_Report_Generator( $this->get_date_format() );
			$result      = $html_report->generate( $data, $filters, get_event_categories() );
			if ( 0 === $result || 1 === $result ) {
				$result = false;
			}

			return $result;
		}

		$csv_report = new CSV_Report_Generator( MWPAL_Extension\mwpal_extension()->settings->get_date_format() );
		$result = $csv_report->generate( $data, $filters );
		if ( 0 === $result || 1 === $result ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Get user ids for reports.
	 *
	 * @param array $usernames - Array of usernames.
	 * @return string
	 */
	public function get_user_ids( $usernames ) {
		global $wpdb;

		if ( empty( $usernames ) ) {
			return 'null';
		}

		$user_ids = 'null';
		$sql      = 'SELECT ID FROM ' . $wpdb->users . ' WHERE';
		$last     = end( $usernames );

		foreach ( $usernames as $username ) {
			if ( $last === $username ) {
				$sql .= " user_login = '$username'";
			} else {
				$sql .= " user_login = '$username' OR";
			}
		}

		// Get MainWP dashboard user ids.
		$result = $wpdb->get_results( $sql, ARRAY_A );

		// Get child site user details.
		$wsal_child_users = MWPAL_Extension\mwpal_extension()->settings->get_option( 'wsal-child-users', array() );

		$child_user_ids = array();
		if ( ! empty( $wsal_child_users ) ) {
			foreach ( $wsal_child_users as $users ) {
				foreach ( $usernames as $username ) {
					if ( isset( $users[ $username ] ) ) {
						$child_user_ids[] = $users[ $username ]->ID;
					}
				}
			}
		}

		if ( ! empty( $result ) || ! empty( $child_user_ids ) ) {
			foreach ( $result as $item ) {
				$child_user_ids[] = $item['ID'];
			}

			$users    = array_unique( $child_user_ids );
			$user_ids = "'" . implode( ',', $users ) . "'";
		}

		return $user_ids;
	}

	/**
	 * Generate report live from child sites.
	 *
	 * @param array $filters - Array of filters.
	 * @return array
	 */
	public function generate_live_report( array $filters ) {
		// Get wsal child sites.
		$wsal_sites = MWPAL_Extension\mwpal_extension()->settings->get_wsal_child_sites();
		if ( empty( $wsal_sites ) ) {
			return array();
		}

		// Site reports.
		$site_reports      = array();
		$report            = array();
		$report['data']    = array();
		$report['filters'] = $filters;

		// Only include the MainWP events when user has selected all sites for report.
		if ( empty( $filters['sites'] ) || ( isset( $filters['sites'] ) && in_array( 'dashboard', $filters['sites'], true ) ) ) {
			// Dashboard filters.
			$mwp_filters            = $filters;
			$mwp_filters['sites'][] = 'dashboard';
			$mwp_events             = $this->generate_report( $mwp_filters, false );

			if ( ! empty( $mwp_events['data'] ) ) {
				$site_reports['mainwp']       = new \stdClass();
				$site_reports['mainwp']->data = $mwp_events['data'];
			}
		}

		// Translate list of event groups into alert IDs because some child sites may not recognize certain groups.
		if ( array_key_exists( 'alert-codes', $filters ) && array_key_exists( 'groups', $filters['alert-codes'] ) ) {
			$filters['alert-codes']['alerts'] = array();
			foreach ( $filters['alert-codes']['groups'] as $group ) {
				$filters['alert-codes']['alerts'] = array_merge( $filters['alert-codes']['alerts'], get_events_by_group( $group ) );
			}
			unset( $filters['alert-codes']['groups'] );
		}

		// The sites cannot be passed to the child site otherwise it would treat it as a multisite site parameter.
		if ( array_key_exists( 'sites', $filters ) ) {
			unset( $filters['sites'] );
		}

		foreach ( $wsal_sites as $site_id => $site ) {
			// Fetch events report by site.
			if ( empty( $filters['sites'] ) || in_array( $site_id, $filters['sites'] ) ) {
				$site_reports[ $site_id ] = $this->fetch_site_reports( $site_id, $filters );
			}
		}

		if ( ! empty( $site_reports ) ) {
			foreach ( $site_reports as $site_report ) {
				if ( is_array( $site_report ) && isset( $site_report['data'] ) ) {
					$report['data'] = array_merge( $site_report['data'], $report['data'] );
				} elseif ( isset( $site_report->data ) ) {
					$report['data'] = array_merge( $site_report->data, $report['data'] );
				}
			}
		}

		return $report;
	}

	/**
	 * Fetch reports from sites.
	 *
	 * @param integer $site_id     - Site id.
	 * @param array   $filters     - Array of filters.
	 * @param string  $report_type - Type of report.
	 * @return array
	 */
	private function fetch_site_reports( $site_id, $filters, $report_type = '' ) {
		// Post data array.
		$post_data = array(
			'filters' => $filters,
		);

		if ( $report_type ) {
			$post_data['report_type'] = $report_type;
		}

		// Call to child sites to fetch WSAL events.
		return MWPAL_Extension\mwpal_extension()->make_api_call( $site_id, 'get_report', $post_data );
	}

	/**
	 * Send the scheduled reports.
	 *
	 * @param bool $test_send - (Optional) Send now.
	 */
	public function send_scheduled_reports( $test_send = false ) {
		$limit            = 100;
		$periodic_reports = get_reports();

		if ( ! empty( $periodic_reports ) ) {
			foreach ( $periodic_reports as $name => $report ) {
				$sites     = $report->sites;
				$type      = $report->type;
				$frequency = $report->frequency;
				$send      = $this->check_report_date( $frequency );

				if ( $send || $test_send ) {
					if ( ! empty( $report ) ) {
						$next_date     = null;
						$alerts_arr    = array();
						$post_types    = array();
						$post_statuses = array();

						$users        = ! empty( $report->users ) ? $report->users : array();
						$roles        = ! empty( $report->roles ) ? $report->roles : array();
						$ip_addresses = ! empty( $report->ip_addresses ) ? $report->ip_addresses : array();

						if ( ! empty( $report->triggers ) ) {
							foreach ( $report->triggers as $key => $value ) {
								if ( isset( $value['alert-id'] ) && is_array( $value['alert-id'] ) ) {
									foreach ( $value['alert-id'] as $alert_id ) {
										array_push( $alerts_arr, $alert_id );
									}
								} elseif ( isset( $value['alert-id'] ) ) {
									array_push( $alerts_arr, $value['alert-id'] );
								}
							}

							$alerts_arr = array_unique( $alerts_arr );

							do {
								$next_date = $this->build_attachment( $name, $alerts_arr, $type, $frequency, $sites, $users, $roles, $ip_addresses, $next_date, $limit, $post_types, $post_statuses );
								$last_date = $next_date;
							} while ( null !== $last_date );

							if ( null === $last_date ) {
								$this->send_summary_email( $name, $alerts_arr );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Check the cron job frequency.
	 *
	 * @param string $frequency - Frequency.
	 * @return bool - Send email or Not.
	 */
	private function check_report_date( $frequency ) {
		$send = false;

		switch ( $frequency ) {
			case self::$report_daily:
				$send = ( self::$daily_hour === $this->calculate_daily_hour() ) ? true : false;
				break;

			case self::$report_weekly:
				$weekly_day = $this->calculate_weekly_day();
				if ( empty( $weekly_day ) ) {
					$send = false;
				} else {
					$send = ( $weekly_day === self::$weekly_day ) ? true : false;
				}
				break;

			case self::$report_monthly:
				$str_date = $this->calculate_monthly_day();
				if ( empty( $str_date ) ) {
					$send = false;
				} else {
					$send = ( date( 'Y-m-d' ) == $str_date ) ? true : false;
				}
				break;

			case self::$report_quarterly:
				$send = $this->check_quarter();
				break;
		}

		return $send;
	}

	/**
	 * Calculate and return hour of the day based on WordPress timezone.
	 *
	 * @return string - Hour of the day.
	 */
	private function calculate_daily_hour() {
		return date( 'H', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
	}

	/**
	 * Calculate and return day of the week based on WordPress timezone.
	 *
	 * @return string|bool - Day of the week or false.
	 */
	private function calculate_weekly_day() {
		if ( self::$daily_hour === $this->calculate_daily_hour() ) {
			return date( 'w' );
		}
		return false;
	}

	/**
	 * Calculate and return day of the month based on WordPress timezone.
	 *
	 * @return string|bool - Day of the week or false.
	 */
	private function calculate_monthly_day() {
		if ( self::$daily_hour === $this->calculate_daily_hour() ) {
			return date( 'Y-m-' ) . self::$monthly_day;
		}
		return false;
	}

	/**
	 * Check Quarter of the year in the cron job.
	 *
	 * @return bool
	 */
	private function check_quarter() {
		$hour  = date( 'H', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
		$month = date( 'n', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
		$day   = date( 'j', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
		$send  = false;

		if ( '1' === $day && self::$daily_hour === $hour ) {
			switch ( $month ) {
				case '1':
				case '4':
				case '7':
				case '10':
					$send = true;
					break;

				default:
					break;
			}
		}

		return $send;
	}

	/**
	 * Get Start Quarter of the year.
	 *
	 * @return string $start_date
	 */
	private function start_quarter() {
		$month = date( 'n', time() );
		$year  = date( 'Y', time() );

		if ( $month >= 1 && $month <= 3 ) {
			$start_date = date( $this->get_date_format(), strtotime( $year . '-01-01' ) );
		} elseif ( $month >= 4 && $month <= 6 ) {
			$start_date = date( $this->get_date_format(), strtotime( $year . '-04-01' ) );
		} elseif ( $month >= 7 && $month <= 9 ) {
			$start_date = date( $this->get_date_format(), strtotime( $year . '-07-01' ) );
		} elseif ( $month >= 10 && $month <= 12 ) {
			$start_date = date( $this->get_date_format(), strtotime( $year . '-10-01' ) );
		}

		return $start_date;
	}

	/**
	 * Get quarter of the year.
	 *
	 * @return string
	 */
	private function which_quarter() {
		$month = date( 'n', time() );

		if ( $month >= 1 && $month <= 3 ) {
			return 'Q1';
		} elseif ( $month >= 4 && $month <= 6 ) {
			return 'Q2';
		} elseif ( $month >= 7 && $month <= 9 ) {
			return 'Q3';
		} elseif ( $month >= 10 && $month <= 12 ) {
			return 'Q4';
		}
	}

	/**
	 * Create the report appending in a json file.
	 *
	 * @param string  $report_name  - Report name.
	 * @param array   $alerts_arr   - Array of event ids.
	 * @param string  $type         - Type of report.
	 * @param string  $frequency    - Frequency.
	 * @param array   $sites        - Array of sites.
	 * @param array   $users        - Array of users.
	 * @param array   $roles        - Array of roles.
	 * @param array   $ip_addresses - Array of IPs.
	 * @param string  $next_date    - Next date.
	 * @param integer $limit        - Limit of events.
	 * @return string - Last date.
	 */
	public function build_attachment( $report_name, $alerts_arr, $type, $frequency, $sites, $users, $roles, $ip_addresses, $next_date, $limit ) {
		@ini_set( 'max_execution_time', '300' ); // Set execution time to 300.

		$last_date = null;
		$result    = $this->get_list_events( $alerts_arr, $type, $frequency, $sites, $users, $roles, $ip_addresses, $next_date, $limit );

		if ( ! empty( $result['lastDate'] ) ) {
			$last_date = $result['lastDate'];
		}

		$filename = 'result_' . $report_name . '-user' . get_current_user_id() . '.json';
		$filepath = str_replace( MWPAL_UPLOADS_DIR, '', MWPAL_REPORTS_UPLOAD_PATH ) . $filename;

		if ( file_exists( MWPAL_REPORTS_UPLOAD_PATH . $filename ) ) {
			$data = json_decode( MWPAL_Extension\get_upload_file_contents( $filepath ), true );

			if ( ! empty( $data ) ) {
				if ( ! empty( $result ) ) {
					foreach ( $result['data'] as $value ) {
						array_push( $data['data'], $value );
					}
				}

				$data['lastDate'] = $last_date;
				MWPAL_Extension\write_to_extension_upload( $filepath, wp_json_encode( $data ), true );
			}
		} else {
			if ( ! empty( $result ) ) {
				MWPAL_Extension\write_to_extension_upload( $filepath, wp_json_encode( $result ), true );
			}
		}

		return $last_date;
	}

	/**
	 * Generate the file of the report (HTML or CSV).
	 *
	 * @param array   $alerts_arr   - Array of event ids.
	 * @param string  $type         - Type of report.
	 * @param string  $frequency    - Frequency.
	 * @param array   $sites        - Array of sites.
	 * @param array   $users        - Array of users.
	 * @param array   $roles        - Array of roles.
	 * @param array   $ip_addresses - Array of IPs.
	 * @param string  $next_date    - Next date.
	 * @param integer $limit        - Limit of events.
	 * @return string|bool filename or false
	 */
	private function get_list_events( $alerts_arr, $type, $frequency, $sites, $users, $roles, $ip_addresses, $next_date, $limit ) {
		switch ( $frequency ) {
			case self::$report_daily:
				$start_date = date( $this->get_date_format(), strtotime( '00:00:00' ) );
				break;
			case self::$report_weekly:
				$start_date = date( $this->get_date_format(), strtotime( '-1 week' ) );
				break;
			case self::$report_monthly:
				$start_date = date( $this->get_date_format(), strtotime( '-1 month' ) );
				break;
			case self::$report_quarterly:
				$start_date = $this->start_quarter();
				break;
		}

		$filters['sites']                 = $sites;
		$filters['users']                 = $users;
		$filters['roles']                 = $roles;
		$filters['ip-addresses']          = $ip_addresses;
		$filters['alert-codes']['groups'] = array();
		$filters['alert-codes']['alerts'] = $alerts_arr;
		$filters['date-range']['start']   = $start_date;
		$filters['date-range']['end']     = date( $this->get_date_format(), time() );
		$filters['report-format']         = $type;
		$filters['nextDate']              = $next_date;
		$filters['limit']                 = $limit;
		$result                           = $this->generate_live_report( $filters );

		return $result;
	}

	/**
	 * Send the summary email.
	 *
	 * @param string $name - Report name.
	 * @return bool $result
	 */
	public function send_summary_email( $name ) {
		$result        = null;
		$report_name   = str_replace( array( MWPAL_OPT_PREFIX, MWPAL_PREPORT_PREFIX ), '', $name );
		$notifications = get_report( $report_name );

		if ( ! empty( $notifications ) ) {
			$email     = $notifications->email;
			$frequency = $notifications->frequency;
			$sites     = $notifications->sites;
			$title     = $notifications->title;

			switch ( $frequency ) {
				case self::$report_daily:
					$pre_subject = sprintf( __( '%1$s — Website %2$s', 'mwp-al-ext' ), date( $this->get_date_format(), time() ), get_bloginfo( 'name' ) );
					break;
				case self::$report_weekly:
					$pre_subject = sprintf( __( 'Week number %1$s — Website %2$s', 'mwp-al-ext' ), date( 'W', strtotime( '-1 week' ) ), get_bloginfo( 'name' ) );
					break;
				case self::$report_monthly:
					$pre_subject = sprintf( __( 'Month %1$s %2$s — Website %3$s', 'mwp-al-ext' ), date( 'F', strtotime( '-1 month' ) ), date( 'Y', strtotime( '-1 month' ) ), get_bloginfo( 'name' ) );
					break;
				case self::$report_quarterly:
					$pre_subject = sprintf( __( 'Quarter %1$s — Website %2$s', 'mwp-al-ext' ), $this->which_quarter(), get_bloginfo( 'name' ) );
					break;
			}

			$attachments = $this->get_attachment( $name );
			if ( ! empty( $attachments ) ) {
				$subject = $pre_subject . sprintf( __( ' — %s Email Report', 'mwp-al-ext' ), $title );
				$content = '<p>The report ' . $title . ' from website ' . get_bloginfo( 'name' ) . ' for';

				switch ( $frequency ) {
					case self::$report_daily:
						$content .= ' ' . date( $this->get_date_format(), time() );
						break;
					case self::$report_weekly:
						$content .= ' week ' . date( 'W', strtotime( '-1 week' ) );
						break;
					case self::$report_monthly:
						$content .= ' the month of ' . date( 'F', strtotime( '-1 month' ) ) . ' ' . date( 'Y', strtotime( '-1 month' ) );
						break;
					case self::$report_quarterly:
						$content .= ' the quarter ' . $this->which_quarter();
						break;
				}

				$content .= ' is attached.</p>';

				add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
				// add_filter( 'wp_mail_from', array( $this, 'custom_wp_mail_from' ) );
				// add_filter( 'wp_mail_from_name', array( $this, 'custom_wp_mail_from_name' ) );
				// Email the report.
				$result = wp_mail( $email, $subject, $content, '', $attachments );

				remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
				// remove_filter( 'wp_mail_from', array( $this, 'custom_wp_mail_from' ) );
				// remove_filter( 'wp_mail_from_name', array( $this, 'custom_wp_mail_from_name' ) );
			}

			return $result;
		}

		return $result;
	}

	/**
	 * Generate the file (HTML or CSV) from the json file.
	 *
	 * @param string $report_id - Report id or name.
	 *
	 * @return string $result path of the file
	 */
	private function get_attachment( $report_id ) {
		$result   = null;
		$filename = 'result_' . $report_id . '-user' . get_current_user_id() . '.json';
		$filepath = str_replace( MWPAL_UPLOADS_DIR, '', MWPAL_REPORTS_UPLOAD_PATH ) . $filename;

		if ( file_exists( MWPAL_REPORTS_UPLOAD_PATH . $filename ) ) {
			$data = json_decode( MWPAL_Extension\get_upload_file_contents( $filepath ), true );
			$result = $this->file_generator( $data['data'], $data['filters'] );
			$result = MWPAL_REPORTS_UPLOAD_PATH . $result;
		}

		@unlink( MWPAL_REPORTS_UPLOAD_PATH . $filename );
		return $result;
	}

	/**
	 * Send periodic report.
	 *
	 * @param string $report_name - Report name.
	 * @param string $next_date   - Next date of report.
	 * @param int    $limit       - Limit.
	 * @return string
	 */
	public function send_periodic_now( $report_name, $next_date = null, $limit = 100 ) {
		$report    = get_report( $report_name );
		$last_date = null;

		if ( ! empty( $report ) ) {
			$alerts_arr = array();
			$sites      = $report->sites;
			$type       = $report->type;
			$frequency  = $report->frequency;

			$users        = ! empty( $report->users ) ? $report->users : array();
			$roles        = ! empty( $report->roles ) ? $report->roles : array();
			$ip_addresses = ! empty( $report->ip_addresses ) ? $report->ip_addresses : array();

			if ( ! empty( $report->triggers ) ) {
				foreach ( $report->triggers as $key => $value ) {
					if ( isset( $value['alert-id'] ) && is_array( $value['alert-id'] ) ) {
						foreach ( $value['alert-id'] as $alert_id ) {
							array_push( $alerts_arr, $alert_id );
						}
					} elseif ( isset( $value['alert-id'] ) ) {
						array_push( $alerts_arr, $value['alert-id'] );
					}
				}

				$alerts_arr = array_unique( $alerts_arr );
				$next_date  = $this->build_attachment( $report_name, $alerts_arr, $type, $frequency, $sites, $users, $roles, $ip_addresses, $next_date, $limit );
				$last_date  = $next_date;

				if ( null === $last_date ) {
					$this->send_summary_email( $report_name );
				}
			}
		}

		return $last_date;
	}

	/**
	 * Filter the mail content type.
	 */
	public function set_html_content_type() {
		return 'text/html';
	}
}

// Initialize the singleton object of this class. This is done in order to schedule the crop job of reports.
get_reports_common();
