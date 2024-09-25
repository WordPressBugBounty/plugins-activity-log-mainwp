<?php
/**
 * Filters manager.
 *
 * @package mwp-al-ext
 */

namespace WSAL\MainWPExtension\Extensions\Search;

use \WSAL\MainWPExtension as MWPAL_Extension;
use \WSAL\MainWPExtension\Views\AuditLogListView as AuditLogListView;
use \WSAL\MainWPExtension\Models\OccurrenceQuery as OccurrenceQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Filters manager class.
 */
class Filters_Manager {

	/**
	 * Array of filters.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Widget cache.
	 *
	 * @var array
	 */
	protected $widgets = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_filters();
		$this->init_hooks();
	}

	/**
	 * Load filters.
	 */
	private function load_filters() {
		foreach ( glob( dirname( __FILE__ ) . '/filters/*.php' ) as $file ) {
			$this->add_from_file( $file );
		}
	}

	/**
	 * Initialize search filter hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_search_assets' ), 10, 1 );
		add_action( 'mwpal_auditlog_before_view', array( $this, 'render_before_auditlog_view' ), 10, 1 );
		add_action( 'admin_print_footer_scripts', array( $this, 'print_footer_js' ) );
		add_filter( 'mwpal_auditlog_query', array( $this, 'modify_auditlog_query' ), 10, 1 );
	}

	/**
	 * Add new filter from file inside autoloader path.
	 *
	 * @param string $file - Path to file.
	 */
	private function add_from_file( $file ) {
		$this->add_from_class( $this->get_class_name( $file ) );
	}

	/**
	 * Add new filter given class name.
	 *
	 * @param string $class - Class name.
	 */
	private function add_from_class( $class ) {
		if ( $class ) {
			$this->filters[] = new $class();

			if ( empty( $this->widgets ) ) {
				$this->widgets = null;
			}
		}
	}

	/**
	 * Get class from file.
	 *
	 * @param string $file - File path.
	 * @return string|bool
	 */
	private function get_class_name( $file ) {
		$filename = basename( $file, '.php' );

		if ( false !== strpos( $filename, 'class-filter-' ) ) {
			$class = str_replace( 'class-', '', $filename );
			$class = str_replace( '-', ' ', $class );
			$class = ucwords( $class );
			$class = str_replace( ' ', '_', $class );
			$class = '\WSAL\MainWPExtension\Extensions\Search\\' . $class;
			return $class;
		}

		return false;
	}

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Gets widgets grouped in arrays with widget class as key.
	 *
	 * @return array
	 */
	public function get_widgets() {
		if ( null === $this->widgets ) {
			$this->widgets = array();

			foreach ( $this->filters as $filter ) {
				foreach ( $filter->get_widgets() as $widget ) {
					$class = get_class( $widget );

					if ( ! isset( $this->widgets[ $class ] ) ) {
						$this->widgets[ $class ] = array();
					}

					$this->widgets[ $class ][] = $widget;
				}
			}
		}

		return $this->widgets;
	}

	/**
	 * Find widget given filter and widget name.
	 *
	 * @param string $filter_name - Filter name.
	 * @param string $widget_name - Widget name.
	 * @return Abstract_Widget|null
	 */
	public function find_widget( $filter_name, $widget_name ) {
		foreach ( $this->filters as $filter ) {
			if ( $filter->get_safe_name() === $filter_name ) {
				foreach ( $filter->get_widgets() as $widget ) {
					if ( $widget->get_safe_name() === $widget_name ) {
						return $widget;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Find a filter given a supported prefix.
	 *
	 * @param string $prefix - Filter prefix.
	 * @return Abstract_Filter|null
	 */
	public function find_filter_by_prefix( $prefix ) {
		foreach ( $this->filters as $filter ) {
			if ( in_array( $prefix, $filter->get_prefixes(), true ) ) {
				return $filter;
			}
		}
		return null;
	}

	/**
	 * Enqueue search assets.
	 *
	 * @param string $hook_suffix - Admin page hook.
	 */
	public function enqueue_search_assets( $hook_suffix ) {
		$extension_hook = str_replace( 'mainwp_page_', '', $hook_suffix );
		$current_tab    = MWPAL_Extension\mwpal_extension()->extension_view->get_current_tab();

		if ( MWPAL_EXTENSION_NAME === $extension_hook && 'activity-log' === $current_tab ) {
			// Datapicker styles.
			wp_enqueue_style(
				'mwpal-daterangepicker-styles',
				MWPAL_BASE_URL . 'assets/js/dist/search/daterangepicker.css',
				array(),
				'3.1.0'
			);

			// Search styles.
			wp_enqueue_style(
				'mwpal-search-styles',
				MWPAL_SEARCH_URL . 'assets/css/search-styles.css',
				array(),
				filemtime( MWPAL_SEARCH_DIR . 'assets/css/search-styles.css' )
			);

			wp_enqueue_script( 'jquery-ui-dialog' );

			wp_enqueue_script(
				'mwpal-moment-script',
				MWPAL_BASE_URL . 'assets/js/dist/search/moment.min.js',
				array(),
				'2.22.1',
				true
			);

			wp_enqueue_script(
				'mwpal-daterangepicker-script',
				MWPAL_BASE_URL . 'assets/js/dist/search/daterangepicker.js',
				array( 'jquery' ),
				'3.1.0',
				true
			);

			wp_register_script(
				'mwpal-search-script',
				MWPAL_BASE_URL . 'assets/js/dist/search/build.search.js',
				array( 'jquery', 'jquery-ui-dialog', 'mwpal-moment-script', 'mwpal-daterangepicker-script' ),
				filemtime( MWPAL_BASE_DIR . 'assets/js/dist/search/build.search.js' ),
				true
			);

			wp_localize_script(
				'mwpal-search-script',
				'searchScriptData',
				array(
					'remove'            => __( 'Remove', 'mwp-al-ext' ),
					'filtersPopupTitle' => __( 'Search Filters', 'mwp-al-ext' ),
					'dateFormat'        => get_date_format(),
					'extensionName'     => MWPAL_EXTENSION_NAME,
					'security'          => wp_create_nonce( 'search-script-nonce' ),
					'adminAjax'         => admin_url( 'admin-ajax.php' ),
					'filterBtnOpen'     => __( 'Close Filters', 'mwp-al-ext' ),
					'filterBtnClose'    => __( 'Filter View', 'mwp-al-ext' ),
					'filterChangeMsg'   => sprintf(
						/* translators: both placeholders are html formatting strings for itallics */
						__( 'Click the %1$sSearch%2$s or %1$sClear Search Results%2$s button to apply the new filters or reset all filters.', 'mwp-al-ext' ),
						'<i>',
						'</i>'
					),
				)
			);

			wp_enqueue_script( 'mwpal-search-script' );
		}
	}

	/**
	 * Display search filters.
	 *
	 * @param MWPAL_Extension\Views\AuditLogView $audit_log_view   Audit log view.
	 * @param bool                               $search_available True is search is available for selected site.
	 * @param string                             $site_name        Site name.
	 */
	public function render_search_filters( $audit_log_view, $search_available, $site_name ) {
		if ( ! MWPAL_Extension\mwpal_extension()->settings->is_current_extension_page() ) {
			return;
		}

		if ( ! $search_available ) {
			return;
		}

		// Get current tab.
		$view_class  = MWPAL_Extension\mwpal_extension()->extension_view;
		$current_tab = $view_class->get_current_tab();

		if ( 'activity-log' !== $current_tab ) {
			return;
		}

		?>
		<div class="wsal-as-filter-list no-filters"></div>
		<!-- Filters List -->
		<?php

		/*
		 * This is a notice which shows when the filters have been changed.
		 *
		 * Check if the user has permanently disabled it.
		 */
		$notice_type = 'search-filters-changed';
		if ( ! get_user_meta( get_current_user_id(), "mwpal-is-notice-dismissed-{$notice_type}", true ) ) {
			?>
			<div class="almwp-filter-notice-zone" style="display:none;" data-notice-type="<?php echo esc_attr( $notice_type ); ?>">
				<p><span class="almwp-notice-message"></span> <a id="almwp-filter-notice-permanant-dismiss" href="javascript:;"><?php esc_html_e( 'Do not show this message again', 'mwp-al-ext' ); ?></a></p>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'mwp-al-ext' ); ?></span></button>
			</div>
			<!-- Filters Notices -->
			<?php
		}
		?>
		<div class="wsal-button-grouping">
			<div class="filter-results-button">
				<button id="filter-container-toggle" class="almwp-button dashicons-before dashicons-filter" type="button"><?php esc_html_e( 'Filter View', 'mwp-al-ext' ); ?></button>
			</div>
		</div>
		<!-- Save Search & Filters Container -->
		<div id="almwp-filters-container" style="display:none">
			<div class="filter-col">
				<?php
				// Add event code filter widget.
				$filter = $this->find_filter_by_prefix( 'event' );

				// If filter is found, then add to container.
				if ( $filter ) {
					?>
					<div class="filter-wrap">
						<?php $filter->Render(); ?>
						<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/" target="_blank" rel="nofollow noopener">list of Event IDs</a> for reference.', 'mwp-al-ext' ), $this->get_allowed_html_tags() ); ?></p>
					</div>
					<?php
				}
				// Add object filter widget.
				$object = $this->find_filter_by_prefix( 'object' );

				// If object filter is found, then add to container.
				if ( $object ) {
					?>
					<div class="filter-wrap">
						<?php $object->Render(); ?>
						<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/"  target="_blank" rel="nofollow noopener">metadata in the activity log</a> for reference.', 'mwp-al-ext' ), $this->get_allowed_html_tags() ); ?></p>
					</div>
					<?php
				}
				// Add event type filter widget.
				$event_type = $this->find_filter_by_prefix( 'event-type' );

				// If event type filter is found, then add to container.
				if ( $event_type ) {
					?>
					<div class="filter-wrap">
						<?php $event_type->Render(); ?>
						<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://www.wpactivitylog.com/support/kb/severity-levels-wordpress-activity-log/" target="_blank" rel="nofollow noopener">severity levels in the activity log</a> for reference.', 'mwp-al-ext' ), $this->get_allowed_html_tags() ); ?></p>
					</div>
					<?php
				}

				// Add code (Severity) filter widget.
				$code = $this->find_filter_by_prefix( 'severity' );

				// If code filter is found, then add to container.
				if ( $code ) {
					?>
					<div class="filter-wrap">
						<?php $code->Render(); ?>
						<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/" target="_blank" rel="nofollow noopener">list of Event IDs</a> for reference.', 'mwp-al-ext' ), $this->get_allowed_html_tags() ); ?></p>
					</div>
					<?php
				}
				?>
			</div>
			<div class="filter-col">
				<?php
				// Data for generating and redering users filters with.
				$user_filters = array(
					'username'  => array(
						'display'     => __( 'Username', 'mwp-al-ext' ),
						'description' => __( 'Filter by username', 'mwp-al-ext' ),
					),
					'firstname' => array(
						'display'     => __( 'First Name', 'mwp-al-ext' ),
						'description' => __( 'Filter by user first name', 'mwp-al-ext' ),
					),
					'lastname'  => array(
						'display'     => __( 'Last Name', 'mwp-al-ext' ),
						'description' => __( 'Filter by user last name', 'mwp-al-ext' ),
					),
					'userrole'  => array(
						'display'     => __( 'User Role', 'mwp-al-ext' ),
						'description' => __( 'Filter by user roles', 'mwp-al-ext' ),
					),
				);
				$this->render_filter_groups( __( 'User Filters', 'mwp-al-ext' ), 'user', $user_filters );
				// The data for fetching and rendering posts filters with.
				$post_filters = array(
					'poststatus' => array(
						'display'     => __( 'Post Status', 'mwp-al-ext' ),
						'description' => __( 'Filter by post status', 'mwp-al-ext' ),
					),
					'posttype'   => array(
						'display'     => __( 'Post Type', 'mwp-al-ext' ),
						'description' => __( 'Filter by post type', 'mwp-al-ext' ),
					),
					'postid'     => array(
						'display'     => __( 'Post ID', 'mwp-al-ext' ),
						'description' => __( 'Filter by post ID', 'mwp-al-ext' ),
					),
					'postname'   => array(
						'display'     => __( 'Post Name', 'mwp-al-ext' ),
						'description' => __( 'Filter by post name', 'mwp-al-ext' ),
					),
				);
				$this->render_filter_groups( __( 'Post Filters', 'mwp-al-ext' ), 'post', $post_filters );

				// Show site alerts widget.
				// NOTE: this is shown when the filter is NOT true.
				if ( is_multisite() && get_current_blog_id() == 1 && ! apply_filters( 'search_extensition_active', false ) ) {

					$curr = WpSecurityAuditLog::GetInstance()->settings->get_view_site_id();
					?>
					<div class="filter-wrap">
						<label for="wsal-ssas"><?php esc_html_e( 'Select Site to view', 'mwp-al-ext' ); ?></label>
						<div class="wsal-widget-container">
							<?php
							if ( $this->get_site_count() > 15 ) {
								$curr = $curr ? get_blog_details( $curr ) : null;
								$curr = $curr ? ( $curr->blogname . ' (' . $curr->domain . ')' ) : 'All Sites';
								?>
								<input type="text" class="wsal-ssas" value="<?php echo esc_attr( $curr ); ?>"/>
								<?php
							} else {
								?>
								<select class="wsal-ssas" name="wsal-ssas" onchange="WsalSsasChange(value);">
									<option value="0"><?php esc_html_e( 'All Sites', 'mwp-al-ext' ); ?></option>
									<?php foreach ( $this->get_sites() as $info ) : ?>
										<option value="<?php echo esc_attr( $info->blog_id ); ?>" <?php echo ( $info->blog_id === $curr ) ? 'selected="selected"' : false; ?>>
											<?php echo esc_html( $info->blogname ) . ' (' . esc_html( $info->domain ) . ')'; ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php
							}
							?>
						</div>
						<p class="description"><?php echo wp_kses( __( 'Select A Specific Site from the Network', 'mwp-al-ext' ), $this->get_allowed_html_tags() ); ?></p>
					</div>
					<?php
				}
				?>
			</div>
			<div class="filter-col filter-dates-col">
				<?php
				// Add date filter widget.
				$date = $this->find_filter_by_prefix( 'from' );

				// If from date filter is found, then add to container.
				if ( $date ) {
					$date->Render();
				}
				// Add ip filter widget.
				$ip = $this->find_filter_by_prefix( 'ip' );

				// If ip filter is found, then add to container.
				if ( $ip ) {
					?>
					<div class="filter-wrap">
						<?php $ip->Render(); ?>
						<p class="description"><?php echo wp_kses( __( 'Enter an IP address to filter', 'mwp-al-ext' ), $this->get_allowed_html_tags() ); ?></p>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display search field.
	 *
	 * @param MWPAL_Extension\Views\AuditLogView $audit_log_view   Audit log view.
	 * @param bool                               $search_available True is search is available for selected site.
	 * @param string                             $site_name        Site name.
	 */
	public function display_search_field( $audit_log_view, $search_available, $site_name ) {

		// Setup the search button based on the site we have selected.
		if ( isset( $_REQUEST['mwpal-site-id'] ) && is_numeric( $_REQUEST['mwpal-site-id'] ) ) {
			$search_button_label = __( 'Search in ', 'mwp-al-ext' ) . $site_name;
		} else {
			$search_button_label = __( 'Search in MainWP DB', 'mwp-al-ext' );
		}

		$audit_log_view->search_box_extended( $search_button_label, 'mwpal-search-box', ! $search_available, $site_name );

		$search_filters = ( isset( $_REQUEST['filters'] ) && is_array( $_REQUEST['filters'] ) ) ? array_map( 'sanitize_text_field', $_REQUEST['filters'] ) : false; // phpcs:ignore

		if ( ! empty( $search_filters ) && is_array( $search_filters ) ) :
			?>
			<script type="text/javascript">
				jQuery(document).ready( function() {
					window.mwpalSearch.attach( function() {
						document.getElementById( mwpalSearch.list.substr( 1 ) ).innerHTML = '';
						<?php foreach ( $search_filters as $filter ) : ?>
							mwpalSearch.addFilter(<?php echo wp_json_encode( $filter ); ?>);
						<?php endforeach; ?>
					});
				});
			</script>
			<?php
		endif;
	}

	/**
	 * Renders an entire group of filters in a single area that is paired with
	 * a select box and some javascript show/hide.
	 *
	 * @method render_filter_groups
	 * @since  1.4.0
	 * @param  string $title Title to use as a lable above select box.
	 * @param  string $slug  The slug to use for identifying groups.
	 * @param  array  $group An array containing all the group data. An array with a handle containing an array of strings - `display` and `description`.
	 */
	public function render_filter_groups( $title = '', $slug = '', $group = array() ) {
		?>
		<div class="alm-filters-group">
			<div class="almwp-filter-group-select">
				<label for="almwp-<?php echo esc_attr( $slug ); ?>-filters-select"><?php echo esc_html( $title ); ?></label>
				<select id="almwp-<?php echo esc_attr( $slug ); ?>-filters-select">
					<?php
					foreach ( $group as $handle => $strings ) {
						// Render item.
						echo '<option value="' . esc_attr( $handle ) . '">' . esc_html( $strings['display'] ) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="almwp-filter-group-inputs">
				<?php
				foreach ( $group as $handle => $strings ) {
					// Add username filter widget.
					$filter = $this->find_filter_by_prefix( $handle );

					// If username filter is found, then add to container.
					if ( $filter ) {
						?>
						<div class="filter-wrap almwp-filter-wrap-<?php echo sanitize_html_class( $handle ); ?>">
							<?php $filter->Render(); ?>
							<?php
							if ( isset( $strings['description'] ) && '' !== $strings['description'] ) {
								?>
								<p class="description"><?php echo wp_kses( $strings['description'], $this->get_allowed_html_tags() ); ?></p>
								<?php
							}
							?>
						</div>
						<?php
					}
				}
				?>
			</div>
			<div class="clearfix"></div>
		</div>
		<?php
	}

	/**
	 * Print search widgets extra JS.
	 */
	public function print_footer_js() {
		if ( ! MWPAL_Extension\mwpal_extension()->settings->is_current_extension_page() ) {
			return;
		}

		foreach ( $this->get_widgets() as $widgets ) {
			foreach ( $widgets as $widget ) {
				$widget->footer_js();
			}
		}
	}

	/**
	 * Filter the query.
	 *
	 * @param OccurrenceQuery $query - Audit log query.
	 * @return OccurrenceQuery
	 */
	public function modify_auditlog_query( $query ) {
		// @codingStandardsIgnoreStart
		$search_term      = ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) ? trim( sanitize_text_field( $_REQUEST['s'] ) ) : false;
		$search_filters   = ( isset( $_REQUEST['filters'] ) && is_array( $_REQUEST['filters'] ) ) ? array_map( 'sanitize_text_field', $_REQUEST['filters'] ) : false;
		// @codingStandardsIgnoreEnd

		// Handle text search.
		if ( $search_term ) {
			// Handle free text search.
			$query->addSearchCondition( $search_term );
		}
		// else {
		// fixes #4 (@see WP_List_Table::search_box).
		// $_REQUEST['s'] = ' ';
		// }
		// Handle filter search.
		$filters_arr = array();
		if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
			foreach ( $search_filters as $filter ) {
				$filter = explode( ':', $filter, 2 );

				if ( isset( $filter[1] ) ) {
					// Group the filter by type.
					$filters_arr[ $filter[0] ][] = $filter[1];
				}
			}

			foreach ( $filters_arr as $prefix => $value ) {
				$the_filter = $this->find_filter_by_prefix( $prefix );
				$the_filter->modify_query( $query, $prefix, $value );
			}
		}

		return $query;
	}

	/**
	 * Ajax handler to dismiss live search notice.
	 */
	public function dismiss_live_search_notice() {
		check_admin_referer( 'search-script-nonce', 'security' );
		MWPAL_Extension\mwpal_extension()->settings->update_option( 'dismiss-live-search-notice', true );
		echo true;
		die();
	}

	public function get_allowed_html_tags() {
		return array(
			'a'      => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
			'br'     => array(),
			'code'   => array(),
			'em'     => array(),
			'strong' => array(),
			'p'      => array(
				'class' => array(),
			),
		);
	}

	/**
	 * @param MWPAL_Extension\Views\AuditLogView $audit_log_view Audit log view.
	 */
	public function render_before_auditlog_view( $audit_log_view ) {

		$search_available = true;
		$site_name        = '';
		if ( isset( $_REQUEST['mwpal-site-id'] ) && is_numeric( $_REQUEST['mwpal-site-id'] ) ) {
			$wsal_child_sites = MWPAL_Extension\mwpal_extension()->settings->get_mwp_child_sites();
			$site_names       = array_column( $wsal_child_sites, 'name', 'id' );
			$current_site_id  = (int) $_REQUEST['mwpal-site-id'];

			// Figure out if the search button should be disabled.
			$search_available = false;
			$mwp_sites        = MWPAL_Extension\mwpal_extension()->settings->get_wsal_child_sites();
			if ( is_array( $mwp_sites ) && array_key_exists( $current_site_id, $mwp_sites )
				&& is_array( $mwp_sites[ $current_site_id ] )
				&& true === $mwp_sites[ $current_site_id ]['wsal_installed']
				&& true === $mwp_sites[ $current_site_id ]['is_premium']
			) {
				$search_available = true;
			}

			$site_name = $site_names[ $current_site_id ];
		}
		$this->display_search_field( $audit_log_view, $search_available, $site_name );
		$this->render_search_filters( $audit_log_view, $search_available, $site_name );
	}
}
