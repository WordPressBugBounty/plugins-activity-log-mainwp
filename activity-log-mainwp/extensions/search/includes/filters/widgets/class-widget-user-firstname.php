<?php
/**
 * User first name widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User first name widget class.
 */
class Widget_User_Firstname extends Abstract_Widget {

	/**
	 * Function to render field.
	 */
	protected function render_field() {
		?>
		<div class="mwpal-widget-container">
			<input type="text"
				class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>"
				placeholder="<?php esc_attr_e( 'Enter a users first name to filter', 'mwp-al-ext' ); ?>"
			/>
			<button id="<?php echo esc_attr( "mwpal-add-$this->prefix-filter" ); ?>" class="mwpal-add-button almwp-button almwp-filter-add-button"><?php esc_html_e( 'Add this filter', 'mwp-al-ext' ); ?></button>
		</div>
		<?php
	}
}
