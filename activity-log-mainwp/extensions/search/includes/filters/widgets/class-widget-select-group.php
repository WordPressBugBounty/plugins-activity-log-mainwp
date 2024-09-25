<?php
/**
 * Group select widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Group select widget class.
 */
class Widget_Select_Group {

	/**
	 * Group items.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Add group item.
	 *
	 * @param string $text  - Group name.
	 * @param string $value - Group value.
	 */
	protected function add( $text, $value ) {
		$this->items[ $value ] = $text;
	}
}
