<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Frontend' ) ) :

	final class WPSC_RP_Frontend {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// scripts and styles.
			add_action( 'wpsc_js_frontend', array( __CLASS__, 'frontend_scripts' ) );
			add_action( 'wpsc_css_frontend', array( __CLASS__, 'frontend_styles' ) );
		}

		/**
		 * Frontend scripts
		 *
		 * @return void
		 */
		public static function frontend_scripts() {

			echo file_get_contents( WPSC_RP_ABSPATH . 'asset/js/public.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Frontend styles
		 *
		 * @return void
		 */
		public static function frontend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_RP_ABSPATH . 'asset/css/public-rtl.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			} else {
				echo file_get_contents( WPSC_RP_ABSPATH . 'asset/css/public.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			}
		}
	}
endif;

WPSC_RP_Frontend::init();
