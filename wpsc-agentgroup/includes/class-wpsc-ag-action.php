<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_AG_Action' ) ) :

	final class WPSC_AG_Action {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Delete user from agentgroup.
			add_action( 'wpsc_delete_agent', array( __CLASS__, 'delete_user_from_agentgroup' ) );
		}

		/**
		 * Delete user from agentgroup
		 *
		 * @param WPSC_Agent $agent - agent object.
		 * @return void
		 */
		public static function delete_user_from_agentgroup( $agent ) {

			$agentgroups = WPSC_Agentgroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'agents',
							'compare' => 'IN',
							'val'     => array( $agent->id ),
						),
					),
				)
			)['results'];

			foreach ( $agentgroups as $agentgroup ) {

				$agents = $agentgroup->agents;
				$index  = array_search( $agent, $agents );
				if ( $index !== false ) {
					unset( $agents[ $index ] );
					if ( $agents ) {
						$agentgroup->agents = $agents;
						$agentgroup->save();
					} else {
						WPSC_Agentgroup::destroy( $agentgroup );
						continue;
					}
				}

				$supervisors = $agentgroup->supervisors;
				$index       = array_search( $agent, $supervisors );
				if ( $index !== false ) {
					unset( $supervisors[ $index ] );
					$agentgroup->supervisors = $supervisors;
					$agentgroup->save();
				}
			}
		}
	}
endif;
WPSC_AG_Action::init();
