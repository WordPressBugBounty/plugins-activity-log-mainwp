<?php
/**
 * Search functions.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get date format for search fields.
 *
 * @return string
 */
function get_date_format() {
	return 'YYYY-MM-DD';
}
