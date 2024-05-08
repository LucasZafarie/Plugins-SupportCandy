<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_SF_Triggers' ) ) :

	final class WPSC_WF_SF_Triggers {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_change_ticket_rating', array( __CLASS__, 'feedback_added' ), 999, 4 );
		}

		/**
		 * Process workflows for feedback added trigger event
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param string      $prev - previous rating.
		 * @param string      $new -  new rating.
		 * @param int         $customer_id - customer id.
		 *
		 * @return void
		 */
		public static function feedback_added( $ticket, $prev, $new, $customer_id ) {

			WPSC_Individual_Ticket::$ticket = $ticket;
			WPSC_Individual_Ticket::$reply_profile = 'agent';

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			foreach ( $workflows as $workflow ) {

				if (
					! $workflow['status'] ||
					$workflow['trigger'] != 'ticket-feedback' ||
					! WPSC_Ticket_Conditions::is_valid( $workflow['conditions'], $ticket )
				) {
					continue;
				}

				WPSC_WF_Actions::execute( $workflow['actions'], $ticket );
			}
		}
	}

endif;

WPSC_WF_SF_Triggers::init();
