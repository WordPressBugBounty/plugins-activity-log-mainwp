<?php
/**
 * IP Widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * IP widget class.
 */
class Widget_Ip extends Abstract_Widget {

	/**
	 * Render widget field.
	 */
	protected function render_field() {
		?>
		<div class="mwpal-widget-container">
			<input type="text" autocomplete="off"
				class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				name="<?php echo esc_attr( $this->id ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>"
				placeholder="<?php esc_attr_e( '192.168.128.255', 'mwp-al-ext' ); ?>"
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

					var ipValue = $( this ).val();
					var ipAddBtn = $( '#mwpal-add-<?php echo esc_attr( $this->prefix ); ?>-filter' );
					var ipError = $( 'label[for="mwpal_search_widget_<?php echo esc_attr( $this->prefix ); ?>"').parent().find( '.mwpal-widget-error' );

					ipError.hide();
					ipAddBtn.removeAttr( 'disabled' );

					var ipPattern = /^(?!.*\.$)((1?\d?\d|25[0-5]|2[0-4]\d)(\.|$)){4}$/;
					if ( ipValue.length && ! ipPattern.test( ipValue ) ) {
						ipError.show();
						ipAddBtn.attr( 'disabled', true );
					}
				});
			});
		</script>
		<?php
	}
}
