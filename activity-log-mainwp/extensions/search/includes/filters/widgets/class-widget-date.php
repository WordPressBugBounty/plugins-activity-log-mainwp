<?php
/**
 * Date widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

use \WSAL\MainWPExtension as MWPAL_Extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Date widget class.
 */
class Widget_Date extends Abstract_Widget {

	/**
	 * Render widget.
	 */
	protected function render_field() {
		$date_format = get_date_format();
		?>
		<div class="mwpal-widget-container dashicons-left-input">
			<span class="dashicons dashicons-calendar-alt"></span>
			<input type="text"
				class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				placeholder="<?php echo esc_attr( $date_format ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>"
			/>
			<button type="button" id="<?php echo esc_attr( "mwpal-add-$this->prefix-filter" ); ?>" class="mwpal-add-button almwp-button almwp-filter-add-button"><?php esc_html_e( 'Add this filter', 'mwp-al-ext' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render widget error.
	 */
	protected function render_error() {
		?>
		<span class="mwpal-widget-error"><?php echo esc_html__( '* Invalid Date', 'mwp-al-ext' ); ?></span>
		<?php
	}
}
