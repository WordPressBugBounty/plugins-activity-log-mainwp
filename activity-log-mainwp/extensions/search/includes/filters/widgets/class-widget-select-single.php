<?php
/**
 * Single select widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Single select widget class.
 */
class Widget_Select_Single extends Abstract_Widget {

	/**
	 * Select items.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Holds the string for the first option.
	 *
	 * @var string
	 */
	protected $first_option = '';

	public function __construct( $prefix, $title = '', $first_option = '' ) {
		parent::__construct( $prefix, $title );
		$this->first_option = ( '' !== $first_option ) ? $first_option : __( 'Select an option', 'mwp-al-ext' );
	}

	/**
	 * Render widget field.
	 */
	protected function render_field() {
		?>
		<div class="mwpal-widget-container">
			<select class="<?php echo esc_attr( $this->get_safe_name() ); ?>"
				id="<?php echo esc_attr( $this->id ); ?>"
				data-prefix="<?php echo esc_attr( $this->prefix ); ?>">
				<option value=""><?php echo esc_html( $this->first_option ); ?></option>
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

	/**
	 * Add select item.
	 *
	 * @param string $text  - Item name.
	 * @param string $value - Item value.
	 */
	public function add( $text, $value ) {
		$this->items[ $value ] = $text;
	}

	/**
	 * Add select group.
	 *
	 * @param string $name - Group name.
	 * @return Widget_Select_Group
	 */
	public function add_group( $name ) {
		$this->items[ $name ] = new Widget_Select_Group();
		return $this->items[ $name ];
	}
}
