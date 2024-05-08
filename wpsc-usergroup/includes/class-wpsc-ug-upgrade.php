<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Upgrade' ) ) :

	final class WPSC_UG_Upgrade {

		/**
		 * Update usergroups for ticket
		 *
		 * @param WPSC_Scheduled_Task $task - task object.
		 * @version 3.0.2
		 * @return void
		 */
		public static function update_ticket_usergroups( $task ) {

			$customer_ids = get_transient( 'wpsc_ug_members' );
			if ( false === $customer_ids ) {

				$usergroups = WPSC_Usergroup::find();
				if ( $usergroups['total_items'] === 0 ) {
					WPSC_Scheduled_Task::destroy( $task );
					return;
				}

				$customers = array();
				foreach ( $usergroups['results'] as $usergroup ) {
					$customers = array_merge( $customers, $usergroup->members );
				}

				$customer_ids = array_unique(
					array_filter(
						array_map(
							fn( $customer ) => $customer->id ? $customer->id : false,
							$customers
						)
					)
				);

				set_transient( 'wpsc_ug_members', $customer_ids, MINUTE_IN_SECONDS * 60 * 48 );
			}

			if ( count( $customer_ids ) === 0 ) {
				WPSC_Scheduled_Task::destroy( $task );
				delete_transient( 'wpsc_ug_members' );
				return;
			}

			$tickets = WPSC_Ticket::find(
				array(
					'items_per_page' => 20,
					'orderby'        => 'date_updated',
					'order'          => 'DESC',
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'usergroups',
							'compare' => 'IN',
							'val'     => array( '0' ),
						),
						array(
							'slug'    => 'customer',
							'compare' => 'IN',
							'val'     => $customer_ids,
						),
					),
				)
			);

			if ( $tickets['total_items'] === 0 ) {
				WPSC_Scheduled_Task::destroy( $task );
				return;
			}

			// save remaining pages of the task. it will be used in ui section.
			$task->pages = $tickets['total_pages'] - 1;
			$task->save();

			foreach ( $tickets['results'] as $ticket ) {

				$ticket->usergroups = WPSC_Usergroup::get_by_customer( $ticket->customer );
				$ticket->save();
			}
		}
	}
endif;
