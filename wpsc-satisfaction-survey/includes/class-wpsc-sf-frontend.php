<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Frontend' ) ) :

	final class WPSC_SF_Frontend {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// scripts and styles.
			add_action( 'wpsc_js_frontend', array( __CLASS__, 'frontend_scripts' ) );
			add_action( 'wpsc_css_frontend', array( __CLASS__, 'frontend_styles' ) );

			// Localization.
			add_filter( 'wpsc_admin_localizations', array( __CLASS__, 'localizations' ) );
			add_filter( 'wpsc_frontend_localizations', array( __CLASS__, 'localizations' ) );
		}

		/**
		 * Frontend scripts
		 *
		 * @return void
		 */
		public static function frontend_scripts() {

			echo file_get_contents( WPSC_SF_ABSPATH . 'assets/js/public.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Frontend styles
		 *
		 * @return void
		 */
		public static function frontend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_SF_ABSPATH . 'assets/css/public-rtl.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			} else {
				echo file_get_contents( WPSC_SF_ABSPATH . 'assets/css/public.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			}
		}

		/**
		 * Localizations for framework
		 *
		 * @param array $localizations - localization list.
		 * @return array
		 */
		public static function localizations( $localizations ) {

			$localizations['translations']['req_rating'] = esc_attr__( 'Please select a rating before submitting!', 'wpsc-sf' );
			return $localizations;
		}
	}
endif;

WPSC_SF_Frontend::init();
