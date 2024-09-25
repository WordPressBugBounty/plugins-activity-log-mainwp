<?php
/**
 * This file will inspect the request and if it's valid
 * it will start the download for the specified report file.
 *
 * @package wsal/reports
 */

// #! No  cache
if ( ! headers_sent() ) {
	header( 'Expires: Mon, 26 Jul 1990 05:00:00 GMT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );
}

/**
 * Locate and load wp-load.php to load bootstrap WordPress in the file.
 * This is mandatory.
 */
$root = realpath( $_SERVER['DOCUMENT_ROOT'] );

if ( file_exists( $root . '/wp-load.php' ) ) {
	@require_once $root . '/wp-load.php';
} else {
	// WordPress root directory (assuming wp-content is at default location).
	$base_load = dirname( __FILE__ ) . '/../../../../../wp-load.php';

	if ( file_exists( $base_load ) ) {
		@require_once $base_load;
	} else {
		$directories = array_filter( glob( $root . '/*' ), 'is_dir' );

		foreach ( $directories as $dir ) {
			if ( file_exists( $dir . '/wp-load.php' ) ) {
				@require_once $dir . '/wp-load.php';
				break;
			}
		}
	}
}

// Check if WordPress exists.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Internal Error: Could not locate the root directory.' );
}

function mwpal_prepare_report_download() {
	ob_start();
	$strm = '[MWPAL Reporting Plugin] Requesting download.php';

	// Optional.
	@include_once ABSPATH . WPINC . '/pluggable.php';

	// Validate nonce.
	if ( ! isset( $_GET['mwpal_report_download'] ) || ! wp_verify_nonce( $_GET['mwpal_report_download'], 'mwpal_reporting_security' ) ) {
		error_log( $strm . ' with a missing or invalid nonce [code: 1000]' );
	}

	// Missing f param from url.
	if ( ! isset( $_GET['f'] ) ) {
		error_log( $strm . ' without the "f" parameter [code: 2000]' );
		throw new Exception( 'Invalid Request', 2000 );
	}

	// Missing ctype param from url.
	if ( ! isset( $_GET['ctype'] ) ) {
		error_log( $strm . ' without the "ctype" parameter [code: 3000]' );
		throw new Exception( 'Invalid Request', 3000 );
	}

	// Invalid fn provided in the url.
	$fn = base64_decode( $_GET['f'] );

	if ( false === $fn ) {
		error_log( $strm . ' without a valid base64 encoded file name [code: 4000]' );
		throw new Exception( 'Invalid Request', 4000 );
	}

	// Make sure this is a file we created.
	if ( ! preg_match( '/^wsal_report_/i', $fn ) ) {
		error_log( $strm . ' with an invalid file name (' . $fn . ') [code: 5000]' );
		throw new Exception( 'Invalid Request', 5000 );
	}

	$upload_dir = wp_upload_dir();
	$dir        = trailingslashit( $upload_dir['basedir'] );
	$file_path  = $dir . 'activity-log-for-mainwp/reports/' . $fn;

	// Directory traversal attacks won't work here.
	if ( preg_match( '/\.\./', $file_path ) ) {
		error_log( $strm . ' with an invalid file name (' . $fn . ') [code: 6000]' );
		throw new Exception( 'Invalid Request', 6000 );
	}

	if ( ! is_file( $file_path ) ) {
		error_log( $strm . ' with an invalid file name (' . $fn . ') [code: 7000]' );
		throw new Exception( 'Invalid Request', 7000 );
	}

	if ( 'html' === $_GET['ctype'] ) {
		$ctype = 'text/html';
	} elseif ( 'csv' === $_GET['ctype'] ) {
		$ctype = 'application/csv';
	} else { // Content type is not valid.
		error_log( $strm . ' with an invalid content type [code: 7000]' );
		throw new Exception( 'Invalid request', 8000 );
	}

	$file_size = filesize( $file_path );
	$file      = fopen( $file_path, 'rb' );

	// - turn off compression on the server - that is, if we can...
	ini_set( 'zlib.output_compression', 'Off' );
	// set the headers, prevent caching + IE fixes.
	header( 'Pragma: public' );
	header( 'Expires: -1' );
	header( 'Cache-Control: public, must-revalidate, post-check=0, pre-check=0' );
	header( 'Content-Disposition: attachment; filename="' . $fn . '"' );
	header( "Content-Length: $file_size" );
	header( "Content-Type: {$ctype}" );
	set_time_limit( 0 );
	while ( ! feof( $file ) ) {
		print( fread( $file, 1024 * 8 ) );
		ob_flush();
		flush();
		if ( connection_status() != 0 ) {
			fclose( $file );
			exit;
		}
	}

	// File save was a success.
	fclose( $file );
	exit;
}

// Validate the request.
$rm = strtoupper( $_SERVER['REQUEST_METHOD'] );

if ( 'GET' === $rm ) {
	try {
		mwpal_prepare_report_download();
	} catch ( Exception $e ) {
		$msg = $e->getMessage() . ' [code: ' . $e->getCode() . ']';
		exit( $msg );
	}
}

exit( 'Invalid Request [code 9000]' ); // Invalid request method.
