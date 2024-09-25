<?php
/**
 * Events file.
 *
 * Events are defined in this file.
 *
 * @package mwp-al-ext
 * @since   1.0.0
 *
 * @phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If not included correctly, then return.
if ( ! class_exists( '\WSAL\MainWPExtension\Activity_Log' ) ) {
	exit();
}

// Define custom / new PHP constants.
defined( 'E_CRITICAL' ) || define( 'E_CRITICAL', 'E_CRITICAL' );
defined( 'E_DEBUG' ) || define( 'E_DEBUG', 'E_DEBUG' );
defined( 'E_RECOVERABLE_ERROR' ) || define( 'E_RECOVERABLE_ERROR', 'E_RECOVERABLE_ERROR' );
defined( 'E_DEPRECATED' ) || define( 'E_DEPRECATED', 'E_DEPRECATED' );
defined( 'E_USER_DEPRECATED' ) || define( 'E_USER_DEPRECATED', 'E_USER_DEPRECATED' );

/**
 * Define Default Alerts.
 *
 * Define default alerts for the plugin.
 */
function mwpal_defaults_init() {
	$activity_log = \WSAL\MainWPExtension\mwpal_extension();

	if ( ! isset( $activity_log->constants ) ) {
		$activity_log->constants = new \WSAL\MainWPExtension\ConstantManager( $activity_log );
	}

	if ( ! isset( $activity_log->alerts ) ) {
		$activity_log->alerts = new \WSAL\MainWPExtension\AlertManager( $activity_log );
	}

	$activity_log->constants->UseConstants(
		array(
			// Default PHP constants.
			array(
				'name'        => 'E_ERROR',
				'description' => esc_html__( 'Fatal run-time error.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_WARNING',
				'description' => esc_html__( 'Run-time warning (non-fatal error).', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_PARSE',
				'description' => esc_html__( 'Compile-time parse error.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_NOTICE',
				'description' => esc_html__( 'Run-time notice.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_CORE_ERROR',
				'description' => esc_html__( 'Fatal error that occurred during startup.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_CORE_WARNING',
				'description' => esc_html__( 'Warnings that occurred during startup.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_COMPILE_ERROR',
				'description' => esc_html__( 'Fatal compile-time error.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_COMPILE_WARNING',
				'description' => esc_html__( 'Compile-time warning.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_USER_ERROR',
				'description' => esc_html__( 'User-generated error message.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_USER_WARNING',
				'description' => esc_html__( 'User-generated warning message.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_USER_NOTICE',
				'description' => esc_html__( 'User-generated notice message.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_STRICT',
				'description' => esc_html__( 'Non-standard/optimal code warning.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_RECOVERABLE_ERROR',
				'description' => esc_html__( 'Catchable fatal error.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_DEPRECATED',
				'description' => esc_html__( 'Run-time deprecation notices.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_USER_DEPRECATED',
				'description' => esc_html__( 'Run-time user deprecation notices.', 'mwp-al-ext' ),
			),
			// Custom constants.
			array(
				'name'        => 'E_CRITICAL',
				'description' => esc_html__( 'Critical, high-impact messages.', 'mwp-al-ext' ),
			),
			array(
				'name'        => 'E_DEBUG',
				'description' => esc_html__( 'Debug informational messages.', 'mwp-al-ext' ),
			),
		)
	);

	/*
	 * severity is based on monolog log levels
	 * @see https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md#log-levels
	 */
	// ALERT (550): Action must be taken immediately.
	$activity_log->constants->AddConstant( 'WSAL_CRITICAL', 500, esc_html__( 'Critical severity events.', 'mwp-al-ext' ) );
	// ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
	$activity_log->constants->AddConstant( 'WSAL_HIGH', 400, esc_html__( 'High severity events.', 'mwp-al-ext' ) );
	// WARNING (300): Exceptional occurrences that are not errors.
	$activity_log->constants->AddConstant( 'WSAL_MEDIUM', 300, esc_html__( 'Medium severity events.', 'mwp-al-ext' ) );
	// NOTICE (250): Normal but significant events.
	$activity_log->constants->AddConstant( 'WSAL_LOW', 250, esc_html__( 'Low severity events.', 'mwp-al-ext' ) );
	// INFO (200): Interesting events.
	$activity_log->constants->AddConstant( 'WSAL_INFORMATIONAL', 200, esc_html__( 'Informational events.', 'mwp-al-ext' ) );

	// Create list of default alerts.
	$activity_log->alerts->RegisterGroup(
		array(
			esc_html__( 'MainWP', 'mwp-al-ext' ) => array(
				esc_html__( 'MainWP', 'mwp-al-ext' ) => array(
					array(
						7700,
						WSAL_CRITICAL,
						esc_html__( 'User added the child site', 'mwp-al-ext' ),
						esc_html__( 'The child site %friendly_name% %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'child-site',
						'added',
					),
					array(
						7701,
						WSAL_CRITICAL,
						esc_html__( 'User removed the child site', 'mwp-al-ext' ),
						esc_html__( 'The child site %friendly_name% %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'child-site',
						'removed',
					),
					array(
						7702,
						WSAL_MEDIUM,
						esc_html__( 'User edited the child site', 'mwp-al-ext' ),
						esc_html__( 'The child site %friendly_name% %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'child-site',
						'modified',
					),
					array(
						7703,
						WSAL_INFORMATIONAL,
						esc_html__( 'User synced data with the child site', 'mwp-al-ext' ),
						esc_html__( 'Synced data with the child %friendly_name% %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'mainwp',
						'synced',
					),
					array(
						7704,
						WSAL_INFORMATIONAL,
						esc_html__( 'User synced data with all the child sites', 'mwp-al-ext' ),
						esc_html__( 'Synced data with all the child sites', 'mwp-al-ext' ),
						'mainwp',
						'synced',
					),
					array(
						7705,
						WSAL_CRITICAL,
						esc_html__( 'User installed the extension', 'mwp-al-ext' ),
						esc_html__( 'The extension %extension_name%', 'mwp-al-ext' ),
						'extension',
						'installed',
					),
					array(
						7706,
						WSAL_HIGH,
						esc_html__( 'User activated the extension', 'mwp-al-ext' ),
						esc_html__( 'The extension %extension_name%', 'mwp-al-ext' ),
						'extension',
						'activated',
					),
					array(
						7707,
						WSAL_HIGH,
						esc_html__( 'User deactivated the extension', 'mwp-al-ext' ),
						esc_html__( 'The extension %extension_name%', 'mwp-al-ext' ),
						'extension',
						'deactivated',
					),
					array(
						7708,
						WSAL_CRITICAL,
						esc_html__( 'User uninstalled the extension', 'mwp-al-ext' ),
						esc_html__( 'The extension %extension_name%', 'mwp-al-ext' ),
						'extension',
						'uninstalled',
					),
					array(
						7709,
						WSAL_INFORMATIONAL,
						esc_html__( 'User added/removed extension to/from the menu', 'mwp-al-ext' ),
						esc_html__( 'The extension %extension% %option% the MainWP menu', 'mwp-al-ext' ),
						'mainwp',
						'updated',
					),
					array(
						7710,
						WSAL_LOW,
						esc_html__( 'Extension failed to retrieve the activity log of a child site', 'mwp-al-ext' ),
						esc_html__( 'Failed to retrieve the activity log of the child site %friendly_name% %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'activity-logs',
						'failed',
					),
					array(
						7711,
						WSAL_INFORMATIONAL,
						esc_html__( 'Extension started retrieving activity logs from the child sites', 'mwp-al-ext' ),
						esc_html__( 'Retrieving activity logs from child sites', 'mwp-al-ext' ),
						'activity-logs',
						'started',
					),
					array(
						7712,
						WSAL_INFORMATIONAL,
						esc_html__( 'Extension is ready retrieving activity logs from the child sites', 'mwp-al-ext' ),
						esc_html__( 'Extension is ready retrieving activity logs from child sites', 'mwp-al-ext' ),
						'activity-logs',
						'finished',
					),
					array(
						7713,
						WSAL_MEDIUM,
						esc_html__( 'Changed the enforcement settings of the Child sites activity log settings', 'mwp-al-ext' ),
						esc_html__( 'The status of the <strong>Child sites activity log settings</strong> %LineBreak% Previous status: %old_status% %LineBreak% New status: %new_status%', 'mwp-al-ext' ),
						'activity-logs',
						'modified',
					),
					array(
						7714,
						WSAL_MEDIUM,
						esc_html__( 'Added or removed a child site from the Child sites activity log settings', 'mwp-al-ext' ),
						esc_html__( 'A child site to / from the <strong>Child sites activity log settings</strong> %LineBreak% Site name: %friendly_name% %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'activity-logs',
						'added',
					),
					array(
						7715,
						WSAL_MEDIUM,
						esc_html__( 'Modified the Child sites activity log settings that are propagated to the child sites', 'mwp-al-ext' ),
						esc_html__( 'The <strong>child sites activity log settings</strong> that are propagated to the child sites', 'mwp-al-ext' ),
						'activity-logs',
						'modified',
					),
					array(
						7716,
						WSAL_MEDIUM,
						esc_html__( 'Started or finished propagating the configured Child sites activity log settings to the child sites', 'mwp-al-ext' ),
						esc_html__( 'Propagating the configured <strong>Child sites activity log settings</strong>', 'mwp-al-ext' ),
						'activity-logs',
						'started',
					),
					array(
						7717,
						WSAL_HIGH,
						esc_html__( 'The propagation of the Child sites activity log settings failed on a child site site', 'mwp-al-ext' ),
						esc_html__( 'The propagation of the <strong>Child sites activity log settings</strong> failed on this site %LineBreak% Site name: %friendly_name% %LineBreak% URL: %site_url% %LineBreak% Error message: %message%', 'mwp-al-ext' ),
						'activity-logs',
						'failed',
					),
					array(
						7750,
						WSAL_INFORMATIONAL,
						esc_html__( 'User added a monitor for site', 'mwp-al-ext' ),
						esc_html__( 'A monitor for the site %friendly_name% in Advanced Uptime Monitor extension %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'uptime-monitor',
						'added',
					),
					array(
						7751,
						WSAL_MEDIUM,
						esc_html__( 'User deleted a monitor for site', 'mwp-al-ext' ),
						esc_html__( 'The monitor for the site %friendly_name% in Advanced Uptime Monitor extension %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'uptime-monitor',
						'deleted',
					),
					array(
						7752,
						WSAL_INFORMATIONAL,
						esc_html__( 'User started the monitor for the site', 'mwp-al-ext' ),
						esc_html__( 'The monitor for the site %friendly_name% in Advanced Uptime Monitor extension %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'uptime-monitor',
						'started',
					),
					array(
						7753,
						WSAL_MEDIUM,
						esc_html__( 'User stopped the monitor for the site', 'mwp-al-ext' ),
						esc_html__( 'Paused the monitor for the site %friendly_name% in Advanced Uptime Monitor extension %LineBreak% URL: %site_url%', 'mwp-al-ext' ),
						'uptime-monitor',
						'stopped',
					),
					array(
						7754,
						WSAL_INFORMATIONAL,
						esc_html__( 'User created monitors for all child sites', 'mwp-al-ext' ),
						esc_html__( 'Created monitors for all child sites', 'mwp-al-ext' ),
						'uptime-monitor',
						'created',
					),
				),
			),
		)
	);
}

add_action( 'init', 'mwpal_defaults_init' );
