<?php
/**
 * CSV Report Generator.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Reports;

use WSAL\MainWPExtension\Utilities\DateTimeFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CSV report generator class.
 */
class CSV_Report_Generator extends Abstract_Report_Generator {

	/**
	 * Generate the CSV of the Report.
	 *
	 * @param array  $data    - Data.
	 * @param array  $filters - Filters.
	 * @param string $delim   - (Optional) Delimiter.
	 * @return int|string
	 */
	public function generate( array $data, array $filters, $delim = ',' ) {
		if ( empty( $data ) ) {
			return 0;
		}

		// Split data by blog so we can display an organized report.
		$temp_data = array();

		foreach ( $data as $entry ) {
			$blog_name = $entry['blog_name'];

			if ( isset( $entry['site_id'] ) && ! $entry['site_id'] ) {
				$user = get_user_by( 'login', $entry['user_name'] );

				if ( ! empty( $user ) ) {
					$entry['user_firstname'] = $user->first_name;
					$entry['user_lastname']  = $user->last_name;
				} else {
					$entry['user_firstname'] = '';
					$entry['user_lastname']  = '';
				}
			} else {
				if ( isset( $entry['user_data'] ) && $entry['user_data'] ) {
					$entry['user_firstname'] = isset( $entry['user_data']['first_name'] ) ? $entry['user_data']['first_name'] : false;
					$entry['user_lastname']  = isset( $entry['user_data']['last_name'] ) ? $entry['user_data']['last_name'] : false;
				} else {
					$entry['user_firstname'] = '';
					$entry['user_lastname']  = '';
				}
			}

			if ( ! isset( $temp_data[ $blog_name ] ) ) {
				$temp_data[ $blog_name ] = array();
			}

			array_push( $temp_data[ $blog_name ], $entry );
		}

		if ( empty( $temp_data ) ) {
			return 0;
		}

		// Check directory once more.
		$uploads_dir_path = MWPAL_REPORTS_UPLOAD_PATH;
		if ( ! is_dir( $uploads_dir_path ) || ! is_readable( $uploads_dir_path ) || ! is_writable( $uploads_dir_path ) ) {
			return 1;
		}

		$filename = 'wsal_report_' . generate_random_string() . '.csv';
		$filepath = str_replace( MWPAL_UPLOADS_DIR, '', MWPAL_REPORTS_UPLOAD_PATH ) . $filename;
		$file     = '';

		// Add columns.
		$columns = array(
			array(
				__( 'Blog Name', 'mwp-al-ext' ),
				__( 'Code', 'mwp-al-ext' ),
				__( 'Type', 'mwp-al-ext' ),
				__( 'Date', 'mwp-al-ext' ),
				__( 'Username', 'mwp-al-ext' ),
				__( 'User', 'mwp-al-ext' ),
				__( 'Role', 'mwp-al-ext' ),
				__( 'Source IP', 'mwp-al-ext' ),
				__( 'Message', 'mwp-al-ext' ),
			),
		);

		$out = '';
		foreach ( $columns as $row ) {
			$quoted_data = array_map( array( $this, 'quote' ), $row );
			$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
		}
		$file .= $out;

		foreach ( $temp_data as $blog_name => $entry ) {
			// Add rows.
			foreach ( $entry as $alert ) {
				// Date Format compatible with Excel.
				$alert_date = DateTimeFormatter::instance()->getFormattedDateTime( $alert['timestamp'], 'datetime', true, false, false );
				$values     = array(
					array(
						$alert['blog_name'],
						$alert['alert_id'],
						$alert['code'],
						$alert_date,
						$alert['user_name'],
						$alert['user_firstname'] . ' ' . $alert['user_lastname'],
						$alert['role'],
						$alert['user_ip'],
						$alert['message'],
					),
				);

				$out = '';
				foreach ( $values as $row ) {
					$quoted_data = array_map( array( $this, 'quote' ), $row );
					$out         .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
				}

				$file .= $out;
			}
		}

		\WSAL\MainWPExtension\write_to_extension_upload( $filepath, $file, true );
		return $filename;
	}

	/**
	 * Utility method to quote the given item
	 *
	 * @internal
	 * @param mixed $data - Data.
	 * @return string
	 */
	final public function quote( $data ) {
		$data = preg_replace( '/"(.+)"/', '""$1""', $data );
		return sprintf( '"%s"', $data );
	}
}
