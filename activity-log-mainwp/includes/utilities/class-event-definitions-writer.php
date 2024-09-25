<?php

/**
 * Writer for the PHP file bootstrapping the dynamic event definitions.
 *
 * @package    mwp-al-ext
 * @subpackage utilities
 *
 * @since      2.0.0
 */

namespace WSAL\MainWPExtension\Utilities;

// Exit if accessed directly.
use function WSAL\MainWPExtension\mwpal_extension;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writer for the PHP file bootstrapping the dynamic event definitions.
 *
 * @since 2.0.0
 */
class EventDefinitionsWriter {

	/**
	 * Write PHP code for registering given events definitions, event types and objects.
	 *
	 * @param array  $definitions Definitions data.
	 * @param string $filename    Name of the file to write to.
	 */
	public static function write_definitions( $definitions, $filename ) {

		// Regenerate the PHP file as well.
		$result = <<<'SECTION1'
		<?php
		/**
		 * Dynamic events file.
		 *
		 * Events are defined in this file.
		 *
		 * @package mwp-al-ext
		 * @since   2.0.0
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
		
		/**
		 * Define Dynamic Alerts.
		 *
		 * Defines dynamic alerts for the plugin.
		 */
		function mwpal_dynamic_events_init() {
			$activity_log = \WSAL\MainWPExtension\mwpal_extension();
			$activity_log->alerts->RegisterGroup(
				array(
								
		SECTION1;

		$result .= self::build_events_code( $definitions['events'] );

		$result .= <<<'SECTION2'
		
				)
			);

			add_filter( 'wsal_event_objects', 'mwpal_dynamic_objects_init', 10, 1 );
			add_action( 'wsal_event_type_data', 'mwpal_dynamic_event_types_init', 10, 1 );
		}
		
		function mwpal_dynamic_objects_init( $objects ) {
			return array_merge(
				$objects,
				array(
				
		SECTION2;

		$result .= self::build_assoc_array_code( $definitions['objects'] );

		$result .= <<<'SECTION3'
				)
			);
		}
		
		function mwpal_dynamic_event_types_init( $types ) {
			return array_unique(
				array_merge(
					$types,
					array(
					
		SECTION3;

		$result .= self::build_assoc_array_code( $definitions['types'] );
		$result .= <<<'SECTION4'
					)
				)
			);
		}
		
		add_action( 'init', 'mwpal_dynamic_events_init', 20 );
		
		SECTION4;

		file_put_contents( $filename, $result );
	}

	/**
	 * Builds PHP code to register given events.
	 *
	 * @param array $events Categorized events data.
	 *
	 * @return string
	 */
	private static function build_events_code( $events ) {
		$result = '';
		foreach ( $events as $category => $subcategories ) {
			$result .= '\'' . wp_slash( $category ) . '\' => array(' . PHP_EOL;
			foreach ( $subcategories as $subcategory => $events ) {
				$result .= '\'' . wp_slash( $subcategory ) . '\' => array(' . PHP_EOL;
				foreach ( $events as $event ) {
					$code = intval( $event['code'] );

					$severity_constant = mwpal_extension()->constants->GetConstantBy( 'value', $event['severity'], null );
					if ( ! is_null( $severity_constant ) ) {
						$severity = $severity_constant->name;
					}

					$description = wp_slash( $event['desc'] );
					$object      = $event['object'];
					$event_type  = $event['event_type'];
					$message     = wp_slash( $event['mesg'] );

					if ( ! empty( $event['metadata'] ) ) {
						foreach ( $event['metadata'] as $meta_key => $meta_value ) {
							$message .= ' %LineBreak% ' . $meta_key . ': ' . $meta_value;
						}
					}

					if ( ! empty( $event['links'] ) ) {
						foreach ( $event['links'] as $link_key => $link_value ) {
							if ( is_string( $link_key ) ) {
								$message .= ' %LineBreak% ' . $link_key . ': ' . $link_value;
							} else {
								$message .= ' %LineBreak% ' . $link_value;
							}
						}
					}

					$result .= <<<EVENT
						array(
							$code,
							$severity,
							'$description',
							'$message',
							'$object',
							'$event_type',
						),
					EVENT;
				}
				$result .= '),' . PHP_EOL;
			}
			$result .= '),' . PHP_EOL;
		}

		return $result;
	}

	/**
	 * Builds PHP code that prints out an associative array.
	 *
	 * @param array $data Associative array to print out.
	 *
	 * @return string
	 */
	private static function build_assoc_array_code( $data ) {
		$result = '';
		foreach ( $data as $key => $value ) {
			$result .= '\'' . wp_slash( $key ) . '\' => \'' . wp_slash( $value ) . '\',' . PHP_EOL;
		}

		return $result;
	}
}
