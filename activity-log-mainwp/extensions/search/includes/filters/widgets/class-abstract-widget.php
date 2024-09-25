<?php
/**
 * Abstract widget.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract widget class.
 */
abstract class Abstract_Widget {

	/**
	 * Widget id.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Widget title/label.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Value prefix for the filter.
	 *
	 * @var string
	 */
	public $prefix;

	/**
	 * Data loader callback.
	 *
	 * @var callable
	 */
	protected $data_loader_func;

	/**
	 * Loader data.
	 *
	 * @var [type]
	 */
	protected $data_loader_data;

	/**
	 * Data loaded.
	 *
	 * @var boolean
	 */
	protected $data_loaded = false;

	/**
	 * Constructor.
	 *
	 * @param string $prefix - Widget prefix.
	 * @param string $title  - Widget title.
	 */
	public function __construct( $prefix, $title = '' ) {
		$this->prefix = $prefix;
		$this->id     = 'mwpal_search_widget_' . $this->prefix;
		$this->title  = $title;
	}

	/**
	 * Set data loading callback.
	 *
	 * @param callable $ldr - A callback that will receive this widget as first parameter and is supposed to populate this widget.
	 * @param mixed    $usr - Some data to be passed to callback as 2nd parameter.
	 */
	public function set_data_loader( $ldr, $usr = null ) {
		$this->data_loader_func = $ldr;
		$this->data_loader_data = $usr;
	}

	/**
	 * Called when widget needs to be populated.
	 *
	 * @param bool $force_load - Force (re)loading data.
	 */
	public function load_data( $force_load = false ) {
		// Avoid loading data multiple times.
		if ( ( ! $this->data_loaded || $force_load ) && $this->data_loader_func ) {
			call_user_func( $this->data_loader_func, $this, $this->data_loader_data );
			$this->data_loaded = true;
		}
	}

	/**
	 * Renders widget HTML.
	 */
	public function render() {
		$this->load_data();
		echo '<th scope="row">';
		$this->render_label();
		$this->render_error();
		echo '</th>';
		echo '<td>';
		$this->render_field();
		echo '</td>';
	}

	/**
	 * Renders widget label (left).
	 */
	protected function render_label() {
		?>
		<label for="<?php echo esc_attr( $this->id ); ?>"><?php echo esc_html( $this->title ); ?></label>
		<?php
	}

	/**
	 * Render widget error.
	 */
	protected function render_error() {
		?>
		<span class="mwpal-widget-error"><?php echo esc_html__( '* Invalid', 'mwp-al-ext' ) . ' ' . esc_html( $this->title ); ?></span>
		<?php
	}

	/**
	 * Renders widget field (right).
	 */
	protected function render_field() {
		?>
		<input type="text" id="<?php echo esc_attr( $this->id ); ?>" data-prefix="<?php echo esc_attr( $this->prefix ); ?>"/>
		<?php
	}

	/**
	 * Generates a widget name.
	 *
	 * @return string
	 */
	public function get_safe_name() {
		return 'mwpal_' . strtolower( str_replace( 'WSAL\MainWPExtension\Extensions\Search\\', '', get_class( $this ) ) );
	}

	/**
	 * Render JS in footer relevant to the widget.
	 */
	public function footer_js() {}
}
