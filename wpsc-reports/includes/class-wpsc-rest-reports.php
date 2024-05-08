<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_REST_Reports' ) ) :

	final class WPSC_REST_Reports {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_filter( 'wpsc_rest_prevent_ticket_data', array( __CLASS__, 'prevent_ticket_data' ) );
		}

		/**
		 * Modify ticket response for usergroup fields
		 *
		 * @param array $data - ticket slug array.
		 * @return array
		 */
		public static function prevent_ticket_data( $data ) {

			$data = array_merge( $data, array( 'frd', 'ard', 'cd', 'cg' ) );
			return $data;
		}
	}
endif;

WPSC_REST_Reports::init();
