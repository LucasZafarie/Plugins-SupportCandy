<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Ticket_Calculations' ) ) :

	final class WPSC_RP_Ticket_Calculations {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add ticket calcuation schema.
			add_action( 'wpsc_ticket_schema', array( __CLASS__, 'add_ticket_schema' ) );

			// calculation first response delay, average response delay.
			add_action( 'wpsc_post_reply', array( __CLASS__, 'post_reply' ) );

			// calculate closing delay.
			add_action( 'wpsc_change_ticket_status', array( __CLASS__, 'change_status' ), 10, 4 );
		}

		/**
		 * Add report calculation schema for ticket
		 *
		 * @param array $schema - ticket schema.
		 * @return array
		 */
		public static function add_ticket_schema( $schema ) {

			$calculation_schema = array(
				'frd' => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'ard' => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'cd'  => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
				'cg'  => array(
					'has_ref'          => false,
					'ref_class'        => '',
					'has_multiple_val' => false,
				),
			);

			return array_merge( $schema, $calculation_schema );
		}

		/**
		 * Calculate first response delay, average response delay and closing delay for ticket when reply added
		 *
		 * @param WPSC_Thread $thread - ticket thread.
		 * @return void
		 */
		public static function post_reply( $thread ) {

			$current_user = WPSC_Current_User::$current_user;
			$ticket       = WPSC_Individual_Ticket::$ticket;
			$threads      = $ticket->get_threads( 1, 0, array( 'report', 'reply' ), 'date_created', 'ASC' );

			if ( ! ( WPSC_Individual_Ticket::is_customer() || ! $current_user->is_agent ) ) {

				$count                  = 0;
				$delay                  = 0;
				$is_customer_record     = true;
				$is_agent_record        = false;
				$customer_response_time = $ticket->date_created;

				foreach ( $threads as $thread ) {

					if ( $thread->type == 'report' ) {
						continue;
					}

					$agent = WPSC_Agent::get_by_customer( $thread->customer );
					if ( $thread->customer == $ticket->customer || ! $agent->id ) {

						if ( $is_customer_record ) {
							continue;
						}
						$customer_response_time = $thread->date_created;
						$is_customer_record     = true;
						$is_agent_record        = false;

					} else {

						if ( $is_agent_record ) {
							continue;
						}
						$diff   = ceil( abs( $customer_response_time->getTimestamp() - ( $thread->date_created )->getTimestamp() ) / 60 );
						$delay += $diff;
						++$count;
						$is_agent_record    = true;
						$is_customer_record = false;

						// first response delay.
						if ( $count === 1 ) {
							$ticket->frd = $delay;
						}
					}
				}

				// average response delay.
				$ticket->ard = ceil( $delay / $count );

				// communication gap.
				$ticket->cg = count( $threads );

				// save! ticket.
				$ticket->save();
			}
		}

		/**
		 * Calculate closing delay
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param int         $prev - previous status id.
		 * @param int         $new - new status id.
		 * @param int         $customer_id - customer id.
		 * @return void
		 */
		public static function change_status( $ticket, $prev, $new, $customer_id ) {

			$gs          = get_option( 'wpsc-gs-general' );
			$tl_advanced = get_option( 'wpsc-tl-ms-advanced' );
			if ( $new == $gs['close-ticket-status'] || in_array( $new, $tl_advanced['closed-ticket-statuses'] ) ) {
				$diff       = ceil( abs( ( $ticket->date_created )->getTimestamp() - ( $ticket->date_closed )->getTimestamp() ) / 60 );
				$ticket->cd = $diff;
				$ticket->save();
			}
		}
	}
endif;

WPSC_RP_Ticket_Calculations::init();
