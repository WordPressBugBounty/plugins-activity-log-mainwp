<?php
/**
 * Search extension.
 *
 * @package mwp-al-ext
 * @subpackage search
 */

namespace WSAL\MainWPExtension\Extensions;

use WSAL\MainWPExtension\Extensions\Search\Filters_Manager;
use WSAL\MainWPExtension\Views\AuditLogView;
use function WSAL\MainWPExtension\mwpal_extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Search extension class.
 */
class Search {

	/**
	 * Search instance.
	 *
	 * @var \WSAL\MainWPExtension\Extensions\Search
	 */
	protected static $instance;

	/**
	 * Filters manager.
	 *
	 * @var object
	 */
	public $filters;

	/**
	 * Returns the instance of this module.
	 *
	 * @return \WSAL\MainWPExtension\Extensions\Search
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define search constants.
	 */
	private function define_constants() {
		define( 'MWPAL_SEARCH_URL', MWPAL_BASE_URL . 'extensions/search/' );
		define( 'MWPAL_SEARCH_DIR', trailingslashit( __DIR__ ) );
	}

	/**
	 * Include files.
	 */
	private function includes() {
		require_once trailingslashit( __DIR__ ) . 'includes/search-functions.php';
		\WSAL\MainWPExtension\Autoload\mwpal_autoload( trailingslashit( __DIR__ ) . 'includes/filters' );
		require_once trailingslashit( __DIR__ ) . 'includes/class-filters-manager.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'mwpal_init', array( $this, 'search_init' ) );
	}

	/**
	 * Initialize the extension.
	 */
	public function search_init() {
		$this->filters = new Filters_Manager();
		if ( isset( $_REQUEST['mwpal-site-id'] ) && isset( $_REQUEST['get-events'] ) && 'live' === sanitize_text_field( wp_unslash( $_REQUEST['get-events'] ) ) ) { // phpcs:ignore
			add_filter( 'mwpal_query_events', array( $this, 'query_events'), 10, 4 );
		}
	}

	/**
	 * Query Events from WSAL DB.
	 *
	 * @param array $events
	 * @param AuditLogView $view
	 * @param int $per_page
	 * @param int $offset
	 *
	 * @return array
	 */
	public function query_events( $events, $view, $per_page = 25, $offset = 0 ) {
		$mwpal_extension = mwpal_extension();

		$filters    = array();
		$query_args = (array) $view->get_query_args();

		if ( ! empty( $query_args['search_filters'] ) ) {
			foreach ( $query_args['search_filters'] as $filter ) {
				$filter = explode( ':', $filter, 2 );

				if ( isset( $filter[1] ) ) {
					$filters[ $filter[0] ][] = $filter[1];
				}
			}

			$query_args['search_filters'] = $filters;
		}

		$post_data = array(
			'action'        => 'get_events',
			'events_count'  => $mwpal_extension->settings->get_child_site_events(),
			'events_offset' => $offset,
			'query_args'    => $query_args,
		);

		$response = $mwpal_extension->alerts->fetch_site_events( $query_args['site_id'], false, $post_data );

		if ( ! empty( $response['events'] ) ) {
			foreach ( $response['events'] as $event_id => $event ) {
				$events[] = $this->get_model()->LoadData( $event );

				// Set item meta.
				$view->set_item_meta( $event_id, $event['meta_data'] );
			}
		} elseif ( ! empty( $response->events ) ) {
			foreach ( $response->events as $event_id => $event ) {
				$events[] = $this->get_model()->LoadData( $event );

				// Set item meta.
				$view->set_item_meta( $event_id, $event->meta_data );
			}
		}

		if ( is_array( $response ) ) {
			return array(
				'total_items' => isset( $response['total_items'] ) ? $response['total_items'] : false,
				'per_page'    => $per_page,
				'items'       => ! empty( $response['events'] ) ? $events : array(),
			);
		} else {
			return array(
				'total_items' => isset( $response->total_items ) ? $response->total_items : false,
				'per_page'    => $per_page,
				'items'       => ! empty( $response->events ) ? $events : array(),
			);
		}

	}

	/**
	 * Returns the model class for event occurrence.
	 *
	 * @return \WSAL\MainWPExtension\Models\Occurrence
	 */
	public function get_model() {
		return new \WSAL\MainWPExtension\Models\Occurrence();
	}
}
