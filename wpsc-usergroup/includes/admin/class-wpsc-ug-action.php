<?php

if ( ! class_exists( 'WPSC_UG_Action' ) ) :

	final class WPSC_UG_Action {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Delete user from usergroup.
			add_action( 'delete_user', array( __CLASS__, 'delete_user_from_usergroup' ), 9, 3 );

			// Delete agent from usergroup.
			add_action( 'after_set_add_agent', array( __CLASS__, 'delete_agent_from_usergroup' ) );

			// change usergroup on changing rise by user.
			add_action( 'wpsc_change_raised_by', array( __CLASS__, 'change_raised_by_usergroup' ), 200, 4 );

			// Remove customer from usergroup.
			add_action( 'wpsc_delete_customer', array( __CLASS__, 'remove_usergroup_customer' ) );
		}

		/**
		 * Delete user from usergroup
		 *
		 * @param int    $user_id - user id.
		 * @param string $reassign - reassign.
		 * @param object $user - user info.
		 * @return void
		 */
		public static function delete_user_from_usergroup( $user_id, $reassign, $user ) {

			$customer = WPSC_Customer::get_by_user_id( $user_id );

			$usergroups = WPSC_Usergroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'members',
							'compare' => 'IN',
							'val'     => array( $customer->id ),
						),
					),
				)
			)['results'];

			foreach ( $usergroups as $usergroup ) {

				$members = array();
				foreach ( $usergroup->members as $member ) :
					if ( $member->user->ID == $user_id ) {
						continue;
					}
					$members[] = $member->id;
				endforeach;

				if ( $members ) {
					$usergroup->members = $members;
				} else {
					WPSC_Usergroup::destroy( $usergroup );
				}

				$supervisors = array();
				foreach ( $usergroup->supervisors as $supervisor ) :
					if ( $supervisor->user->ID == $user_id ) {
						continue;
					}
					$supervisors[] = $supervisor->id;
				endforeach;
				$usergroup->supervisors = $supervisors;

				$usergroup->save();
			}
		}

		/**
		 * Delete agent from usergroup
		 *
		 * @param object $agent - agent object.
		 * @return void
		 */
		public static function delete_agent_from_usergroup( $agent ) {

			$customer = WPSC_Customer::get_by_user_id( $agent->user->ID );

			$usergroups = WPSC_Usergroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'members',
							'compare' => 'IN',
							'val'     => array( $customer->id ),
						),
					),
				)
			)['results'];

			foreach ( $usergroups as $usergroup ) {

				$members = array();
				foreach ( $usergroup->members as $member ) :
					if ( $member->user->ID == $agent->user->ID ) {
						continue;
					}
					$members[] = $member->id;
				endforeach;

				if ( $members ) {
					$usergroup->members = $members;
				} else {
					WPSC_Usergroup::destroy( $usergroup );
				}

				$supervisors = array();
				foreach ( $usergroup->supervisors as $supervisor ) :
					if ( $supervisor->user->ID == $agent->user->ID ) {
						continue;
					}
					$supervisors[] = $supervisor->id;
				endforeach;
				$usergroup->supervisors = $supervisors;

				$usergroup->save();
			}
		}

		/**
		 * Change usergroup when changes raise by user in Individual ticket
		 *
		 * @param WPSC_ticket   $ticket - ticket object.
		 * @param WPSC_Customer $prev - previous rised by.
		 * @param WPSC_Customer $new -  new rised by.
		 * @param int           $customer_id - cust id.
		 *
		 * @return void
		 */
		public static function change_raised_by_usergroup( $ticket, $prev, $new, $customer_id ) {

			$ug = array();
			$usergroups = WPSC_Usergroup::get_by_customer( $new );
			foreach ( $usergroups as $usergroup ) {
				$ug[] = $usergroup->id;
			}
			$ticket->usergroups = $ug;
			$ticket->save();
		}

		/**
		 * Remove customer if exists in usergroup's member or supervisor
		 *
		 * @param WPSC_Customer $customer - customer object.
		 * @return void
		 */
		public static function remove_usergroup_customer( $customer ) {

			$usergroups = WPSC_Usergroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'members',
							'compare' => 'IN',
							'val'     => array( $customer->id ),
						),
					),
				)
			)['results'];

			foreach ( $usergroups as $usergroup ) {

				// remove from usergroup members.
				$members = array();
				foreach ( $usergroup->members as $member ) :
					if ( $member->id == $customer->id ) {
						continue;
					}
					$members[] = $member->id;
				endforeach;

				$usergroup->members = $members;

				// remove from usergroup supervisor.
				$supervisors = array();
				foreach ( $usergroup->supervisors as $supervisor ) :
					if ( $supervisor->id == $customer->id ) {
						continue;
					}
					$supervisors[] = $supervisor->id;
				endforeach;
				$usergroup->supervisors = $supervisors;

				$usergroup->save();
			}
		}
	}
endif;
WPSC_UG_Action::init();
