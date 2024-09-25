<?php
/**
 * Username widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Username widget class.
 */
class Widget_Username extends Abstract_Widget {

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
				placeholder="<?php esc_attr_e( 'Enter a user name filter', 'mwp-al-ext' ); ?>"
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

					var usernameValue = $( this ).val();
					var usernameAddBtn = $( '#mwpal-add-<?php echo esc_attr( $this->prefix ); ?>-filter' );
					var usernameError = $( 'label[for="mwpal_search_widget_<?php echo esc_attr( $this->prefix ); ?>"').parent().find( '.mwpal-widget-error' );

					usernameError.hide();
					usernameAddBtn.removeAttr( 'disabled' );

					var usernamePattern = /^[a-z0-9\s\_\.\\\-\@]+$/i;
					if ( usernameValue.length && ! usernamePattern.test( usernameValue ) ) {
						usernameError.show();
						usernameAddBtn.attr( 'disabled', true );
					}
				});
			});
		</script>
		<?php
	}
}
