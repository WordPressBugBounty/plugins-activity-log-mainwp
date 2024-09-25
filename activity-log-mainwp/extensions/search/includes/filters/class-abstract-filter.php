<?php
/**
 * Abstract filter.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

use \WSAL\MainWPExtension\Models\OccurrenceQuery as OccurrenceQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract filter class.
 */
abstract class Abstract_Filter {

	/**
	 * List of filter prefixes (the text before the colon).
	 *
	 * @return array
	 */
	abstract public function get_prefixes();

	/**
	 * List of widgets to be used in UI.
	 *
	 * @return array
	 */
	abstract public function get_widgets();

	/**
	 * Filter name (used in UI).
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Allow this filter to change the DB query according to the search value.
	 *
	 * @param OccurrenceQuery $query  - Database query for selecting occurrences.
	 * @param string          $prefix - The filter name (filter string prefix).
	 * @param string          $value  - The filter value (filter string suffix).
	 * @throws Exception Thrown when filter is unsupported.
	 */
	abstract public function modify_query( $query, $prefix, $value );

	/**
	 * Renders filter widgets.
	 */
	public function render() {
		foreach ( $this->get_widgets() as $widget ) :
			?>
			<tr class="mwpal-as-filter-widget">
				<?php $widget->render(); ?>
			</tr>
			<?php
		endforeach;
	}

	/**
	 * Generates a widget name.
	 *
	 * @return string
	 */
	public function get_safe_name() {
		return strtolower( get_class( $this ) );
	}
}
