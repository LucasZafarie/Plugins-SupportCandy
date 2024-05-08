<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Ticket_List' ) ) :

	final class WPSC_UG_Ticket_List {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// ticket filters system query.
			add_filter( 'wpsc_tl_current_user_system_query', array( __CLASS__, 'user_system_query' ), 10, 3 );

			// customers allowed for supervisor.
			add_filter( 'wpsc_non_agent_user_customers_allowed', array( __CLASS__, 'supervisor_customers_allwed' ), 10, 2 );

			// ticket access allowed for supervisor.
			add_filter( 'wpsc_non_agent_ticket_customers_allowed', array( __CLASS__, 'ticket_supervisors_allowed' ), 10, 2 );

			// allow supervisor to close ticket.
			add_filter( 'wpsc_it_action_close_flag', array( __CLASS__, 'it_action_close_flag' ), 10, 2 );
		}

		/**
		 * Enable tickets for usergroup supervisor
		 *
		 * @param array             $system_query - system query.
		 * @param array             $filters - filters.
		 * @param WPSC_Current_User $current_user - current user.
		 * @return array
		 */
		public static function user_system_query( $system_query, $filters, $current_user ) {

			if ( $current_user->is_agent || ! $current_user->is_customer ) {
				return $system_query;
			}

			$ug_ids = array_filter(
				array_map(
					fn( $usergroup ) => $usergroup->id,
					self::get_supervisor_groups( $current_user )
				)
			);

			if ( ! $ug_ids ) {
				return $system_query;
			}

			$system_query[] = array(
				'slug'    => 'usergroups',
				'compare' => 'IN',
				'val'     => $ug_ids,
			);
			return $system_query;
		}

		/**
		 * Get usergroups where given current user is supervisor
		 *
		 * @param WPSC_Current_User $current_user - current user object.
		 * @return array
		 */
		public static function get_supervisor_groups( $current_user ) {

			$usergroups = WPSC_Usergroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'supervisors',
							'compare' => 'IN',
							'val'     => array( $current_user->customer->id ),
						),
					),
				)
			)['results'];

			return $usergroups;
		}

		/**
		 * Customers allowed for supervisor
		 *
		 * @param array         $allowed_customers - array od ids of customers.
		 * @param WPSC_Customer $customer - customer object to check for other customers.
		 * @return array
		 */
		public static function supervisor_customers_allwed( $allowed_customers, $customer ) {

			$usergroups = WPSC_Usergroup::get_by_customer( $customer );

			foreach ( $usergroups as $usergroup ) {
				$allowed_customers = array_merge(
					$allowed_customers,
					array_map(
						fn( $obj ) => $obj->id,
						$usergroup->supervisors
					)
				);
			}

			return array_unique( $allowed_customers );
		}

		/**
		 * Add allowed supervisor for the ticket if applicable.
		 *
		 * @param array       $allowed_customers - array of customer ids.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return array
		 */
		public static function ticket_supervisors_allowed( $allowed_customers, $ticket ) {

			foreach ( $ticket->usergroups as $usergroup ) {
				$allowed_customers = array_merge(
					$allowed_customers,
					array_map(
						fn( $obj ) => $obj->id,
						$usergroup->supervisors
					)
				);
			}

			return array_unique( $allowed_customers );
		}

		/**
		 * Allow supervisor to close ticket
		 *
		 * @param boolean     $close_flag - closed flag.
		 * @param WPSC_Ticket $ticket - tickct info.
		 * @return flag
		 */
		public static function it_action_close_flag( $close_flag, $ticket ) {

			$general = get_option( 'wpsc-ug-general-settings' );
			if ( ! $general['allow-sup-close-ticket'] ) {
				return $close_flag;
			}

			$gs           = get_option( 'wpsc-gs-general' );
			$current_user = WPSC_Current_User::$current_user;

			if ( ! $current_user->is_agent && in_array( 'customer', $gs['allow-close-ticket'] ) ) {
				$usergroups = WPSC_Usergroup::find(
					array(
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'slug'    => 'supervisors',
								'compare' => 'IN',
								'val'     => array( $current_user->customer->id ),
							),
							array(
								'slug'    => 'members',
								'compare' => 'IN',
								'val'     => array( $ticket->customer->id ),
							),
						),

					)
				)['results'];

				if ( $usergroups ) {
					$close_flag = true;
				}
			}

			return $close_flag;
		}
	}
endif;

WPSC_UG_Ticket_List::init();
