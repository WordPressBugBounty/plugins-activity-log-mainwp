<?php
/**
 * Events widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Events widget class.
 */
class Widget_Event_ID extends Abstract_Widget {

	/**
	 * Function to render field.
	 */
	protected function render_field() {
		?>
		<div class="mwpal-widget-container">
			<input type="number"
				class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>"
				min="1000"
				max="9999"
				placeholder="<?php esc_attr_e( 'Enter an Event ID to filter - example: 1000', 'mwp-al-ext' ); ?>"
			/>
			<button id="<?php echo esc_attr( "mwpal-add-$this->prefix-filter" ); ?>" class="mwpal-add-button almwp-button almwp-filter-add-button"><?php esc_html_e( 'Add this filter', 'mwp-al-ext' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render JS in footer relevant to this widget.
	 */
	public function footer_js() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( 'input.<?php echo esc_attr( $this->get_safe_name() ); ?>' ).on( 'change keyup keydown paste', function( event ) {
					if ( event.keyCode === 13 ) {
						event.preventDefault();
					}

					var eventIdValue = $( this ).val();
					var eventIdAddBtn = $( '#mwpal-add-<?php echo esc_attr( $this->prefix ); ?>-filter' );
					var eventIdError = $( 'label[for="mwpal_search_widget_<?php echo esc_attr( $this->prefix ); ?>"').parent().find( '.mwpal-widget-error' );

					eventIdError.hide();
					eventIdAddBtn.removeAttr( 'disabled' );

					if ( eventIdValue.length && ( eventIdValue.length < 4 || eventIdValue.length > 4 ) ) {
						eventIdError.show();
						eventIdAddBtn.attr( 'disabled', true );
					}
				});
			});
		</script>
		<?php
	}
}
