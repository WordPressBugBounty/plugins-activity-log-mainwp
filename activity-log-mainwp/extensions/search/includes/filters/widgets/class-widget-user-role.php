<?php
/**
 * User role widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User role widget class.
 */
class Widget_User_Role extends Widget_Select_Single {

	/**
	 * Render widget field.
	 */
	protected function render_field() {
		?>
		<div class="mwpal-widget-container">
			<select class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>"
				>
				<option value=""><?php esc_html_e( 'Select an option', 'mwp-al-ext' ); ?></option>
				<?php
				foreach ( $this->items as $value => $text ) {
					if ( is_object( $text ) ) {
						// Render group (and items).
						echo '<optgroup label="' . esc_attr( $value ) . '">';
						foreach ( $text->items as $s_value => $s_text ) {
							echo '<option value="' . esc_attr( $s_value ) . '">' . esc_html( $s_text ) . '</option>';
						}
						echo '</optgroup>';
					} else {
						// Render item.
						echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $text ) . '</option>';
					}
				}
				?>
			</select>
		</div>
		<?php
	}
}
