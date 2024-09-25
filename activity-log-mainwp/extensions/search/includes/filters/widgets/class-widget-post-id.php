<?php
/**
 * Post id widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post id widget class.
 */
class Widget_Post_Id extends Abstract_Widget {

	/**
	 * Render widget field.
	 */
	protected function render_field() {
		?>
		<div class="mwpal-widget-container">
			<input type="number"
				class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>"
				placeholder="<?php esc_attr_e( 'Enter a post id to filter', 'mwp-al-ext' ); ?>"
			/>
			<button id="<?php echo esc_attr( "mwpal-add-$this->prefix-filter" ); ?>" class="mwpal-add-button almwp-button almwp-filter-add-button"><?php esc_html_e( 'Add this filter', 'mwp-al-ext' ); ?></button>
		</div>
		<?php
	}
}
