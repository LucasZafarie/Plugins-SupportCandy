<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_REST_Usergroups' ) ) :

	final class WPSC_REST_Usergroups {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// register routes.
			add_action( 'wpsc_rest_register_routes', array( __CLASS__, 'register_routes' ) );
		}

		/**
		 * Register routes
		 *
		 * @return void
		 */
		public static function register_routes() {
		}
	}
endif;

WPSC_REST_Usergroups::init();
