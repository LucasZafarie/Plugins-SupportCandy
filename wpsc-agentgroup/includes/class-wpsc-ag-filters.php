<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_AG_Filters' ) ) :

	final class WPSC_AG_Filters {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// assignee filters.
			add_filter( 'wpsc_assignee_filter_by', array( __CLASS__, 'assignee_filters' ) );

			// agent autocomplete filters.
			add_filter( 'wpsc_agent_autocomplete_filters', array( __CLASS__, 'agent_autocomplete' ), 10, 2 );

			// agent orderby string.
			add_filter( 'wpsc_agent_orderby_string', array( __CLASS__, 'change_orderby_str' ) );

			// ticket filters system query.
			add_filter( 'wpsc_tl_current_user_system_query', array( __CLASS__, 'user_system_query' ), 10, 3 );

			// unresolved count system query.
			add_filter( 'wpsc_reset_agent_unresolved_system_query', array( __CLASS__, 'unresolved_count_system_query' ), 10, 2 );

			// individual ticket capabilities.
			add_filter( 'wpsc_it_has_ticket_cap', array( __CLASS__, 'has_ticket_cap' ), 10, 3 );

			// reset unresolved count for agentgroup supervisors.
			add_action( 'wpsc_create_new_ticket', array( __CLASS__, 'create_new_ticket' ), 200 );
			add_action( 'wpsc_change_ticket_status', array( __CLASS__, 'change_status' ), 200, 4 );
			add_action( 'wpsc_change_assignee', array( __CLASS__, 'change_assignee' ), 200, 4 );
			add_action( 'wpsc_change_raised_by', array( __CLASS__, 'change_raised_by' ), 200, 4 );
			add_action( 'wpsc_delete_ticket', array( __CLASS__, 'delete_ticket' ), 200, 1 );
			add_action( 'wpsc_ticket_restore', array( __CLASS__, 'restore_ticket' ), 200, 1 );
		}

		/**
		 * Add agentgroups as assignee filters
		 *
		 * @param array $filters - ticket filters array.
		 * @return array
		 */
		public static function assignee_filters( $filters ) {

			$agentgroups = WPSC_Agentgroup::find()['results'];
			foreach ( $agentgroups as $agentgroup ) {
				$filters[ $agentgroup->id ] = stripslashes( $agentgroup->name );
			}
			return $filters;
		}

		/**
		 * Apply agentgroup filter if set to agent autocomplete filter
		 *
		 * @param array  $args - agent autocomplete meta query conditions.
		 * @param string $filter_by - filter by for agent autocomplete filter.
		 * @return array
		 */
		public static function agent_autocomplete( $args, $filter_by ) {

			if ( is_numeric( $filter_by ) ) {

				$agentgroup = new WPSC_Agentgroup( intval( $filter_by ) );
				if ( ! $agentgroup->id ) {
					return $args;
				}

				$agents = array();
				foreach ( $agentgroup->agents as $agent ) {
					$agents[] = $agent->id;
				}

				$args['meta_query'][] = array(
					'slug'    => 'id',
					'compare' => 'IN',
					'val'     => $agents,
				);

				$args['meta_query'][] = array(
					'slug'    => 'is_agentgroup',
					'compare' => '=',
					'val'     => 0,
				);
			}

			return $args;
		}

		/**
		 * Change orderby string. In case of workload is set, agentgroups should appear at bottom
		 *
		 * @param string $str - agent find order by type.
		 * @return string
		 */
		public static function change_orderby_str( $str ) {

			if ( $str == 'workload' ) {
				return 'workload IS NULL, workload';
			}
			return $str;
		}

		/**
		 * Enable tickets for agentgroup supervisor
		 *
		 * @param array $system_query - ticket filters system query.
		 * @param array $filters - ticket list filters.
		 * @param array $current_user - current user object.
		 * @return array
		 */
		public static function user_system_query( $system_query, $filters, $current_user ) {

			if ( $current_user->is_agent ) {

				$agent_ids = self::get_supervisor_group_agent_ids( $current_user->agent->id );
				if ( ! $agent_ids ) {
					return $system_query;
				}

				$system_query[] = array(
					'slug'    => 'assigned_agent',
					'compare' => 'IN',
					'val'     => $agent_ids,
				);
			}

			return $system_query;
		}

		/**
		 * Agentgroup compatibility for unresolved count
		 *
		 * @param array      $system_query - ticket filters system query.
		 * @param WPSC_Agent $agent - agent object.
		 * @return array
		 */
		public static function unresolved_count_system_query( $system_query, $agent ) {

			$agent_ids = self::get_supervisor_group_agent_ids( $agent->id );
			if ( ! $agent_ids ) {
				return $system_query;
			}

			$system_query[] = array(
				'slug'    => 'assigned_agent',
				'compare' => 'IN',
				'val'     => $agent_ids,
			);

			return $system_query;
		}

		/**
		 * Set ticket capability for supervisor
		 *
		 * @param boolean     $flag - permission flag for ticket capability.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param string      $cap - ticket capability name.
		 * @return boolean
		 */
		public static function has_ticket_cap( $flag, $ticket, $cap ) {

			$current_user = WPSC_Current_User::$current_user;

			if (
				! $ticket->assigned_agent ||
				! $current_user->agent->has_cap( $cap . '-assigned-me' )
			) {
				return $flag;
			}

			$assign_agent_ids = array();
			foreach ( $ticket->assigned_agent as $agent ) {
				$assign_agent_ids[] = $agent->id;
			}

			$agent_ids = self::get_supervisor_group_agent_ids( $current_user->agent->id );
			if ( ! $agent_ids ) {
				return $flag;
			}

			foreach ( $agent_ids as $agent_id ) {
				if ( in_array( $agent_id, $assign_agent_ids ) ) {
					$flag = true;
					break;
				}
			}

			return $flag;
		}

		/**
		 * Get agent ids of group where given agent_id is supervisor
		 *
		 * @param integer $agent_id  - supervisor id.
		 * @return array
		 */
		public static function get_supervisor_group_agent_ids( $agent_id ) {

			$agentgroups = WPSC_Agentgroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'supervisors',
							'compare' => 'IN',
							'val'     => array( $agent_id ),
						),
					),
				)
			)['results'];

			$agent_ids = array();
			foreach ( $agentgroups as $agentgroup ) {
				$agent_ids[] = $agentgroup->agent_id;
			}

			return $agent_ids;
		}

		/**
		 * Reset unresolved count for agentgroup superviors
		 *
		 * @param array $assigned_agent - assigned agents to ticket.
		 * @return void
		 */
		public static function reset_agentgroup_unresolved_count( $assigned_agent ) {

			if ( ! $assigned_agent ) {
				return;
			}

			foreach ( $assigned_agent as $agent ) {

				if ( ! $agent->is_agentgroup ) {
					continue;
				}

				$agentgroup = WPSC_Agentgroup::get_by_agent_id( $agent->id );
				foreach ( $agentgroup->supervisors as $agent ) {

					if ( in_array( $agent, $assigned_agent ) ) {
						continue;
					}
					$agent->reset_unresolved_count();
				}
			}
		}

		/**
		 * Reset unresolved count for agentgroup supervisors
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function create_new_ticket( $ticket ) {

			self::reset_agentgroup_unresolved_count( $ticket->assigned_agent );
		}

		/**
		 * Reset unresolved count for agentgroup supervisors
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param integer     $prev - previous ticket status.
		 * @param integer     $new - new ticket status.
		 * @param integer     $customer_id - customer id.
		 * @return void
		 */
		public static function change_status( $ticket, $prev, $new, $customer_id ) {

			self::reset_agentgroup_unresolved_count( $ticket->assigned_agent );
		}

		/**
		 * Reset unresolved count for agentgroup supervisors
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $prev - previous ticket agents.
		 * @param array       $new - new ticket agents.
		 * @param integer     $customer_id - customer id.
		 * @return void
		 */
		public static function change_assignee( $ticket, $prev, $new, $customer_id ) {

			$assigned_agent = $prev;
			foreach ( $new as $agent ) {
				if ( ! in_array( $agent, $assigned_agent ) ) {
					$assigned_agent[] = $agent;
				}
			}
			self::reset_agentgroup_unresolved_count( $assigned_agent );
		}

		/**
		 * Reset unresolved count for agentgroup supervisors
		 *
		 * @param WPSC_Ticket   $ticket - ticket object.
		 * @param WPSC_Customer $prev - previous ticket customer.
		 * @param WPSC_Customer $new - new ticket customer.
		 * @param integer       $customer_id - customer id.
		 * @return void
		 */
		public static function change_raised_by( $ticket, $prev, $new, $customer_id ) {

			self::reset_agentgroup_unresolved_count( $ticket->assigned_agent );
		}

		/**
		 * Reset unresolved count for agentgroup supervisors
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function delete_ticket( $ticket ) {

			self::reset_agentgroup_unresolved_count( $ticket->assigned_agent );
		}

		/**
		 * Reset unresolved count for agentgroup supervisors
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function restore_ticket( $ticket ) {

			self::reset_agentgroup_unresolved_count( $ticket->assigned_agent );
		}
	}
endif;

WPSC_AG_Filters::init();
