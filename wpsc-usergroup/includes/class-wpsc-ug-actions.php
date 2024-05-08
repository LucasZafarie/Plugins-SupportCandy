<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Actions' ) ) :

	final class WPSC_UG_Actions {

		/**
		 * Initializing class
		 *
		 * @return void
		 */
		public static function init() {

			// change usegrgoups access to the agent role.
			add_action( 'wpsc_add_agent_role_ticket_permissions', array( __CLASS__, 'add_agent_role_ticket_permissions' ) );
			add_filter( 'wpsc_set_add_agent_role', array( __CLASS__, 'set_add_agent_role_ticket_permission' ), 10, 2 );
			add_action( 'wpsc_edit_agent_role_ticket_permissions', array( __CLASS__, 'edit_agent_role_ticket_permissions' ) );
			add_filter( 'wpsc_set_edit_agent_role', array( __CLASS__, 'set_edit_agent_role_ticket_permission' ), 10, 3 );
		}

		/**
		 * Add permisstion settings to add agent role
		 *
		 * @return void
		 */
		public static function add_agent_role_ticket_permissions() {
			?>

			<tr>
				<td><label for=""><?php esc_attr_e( 'Modify Usergroups', 'wpsc-usergroup' ); ?></label></td>
				<td><input name="caps[]" type="checkbox" value="modify-ug-unassigned" class="wpsc-una"></td>
				<td><input name="caps[]" type="checkbox" value="modify-ug-assigned-me" class="wpsc-ame"></td>
				<td><input name="caps[]" type="checkbox" value="modify-ug-assigned-others" class="wpsc-ao"></td>
			</tr>			
			<?php
		}

		/**
		 * Set ticket permissions for this filter
		 *
		 * @param array  $args - arg name.
		 * @param string $caps - capabilities.
		 * @return array
		 */
		public static function set_add_agent_role_ticket_permission( $args, $caps ) {

			$args['caps']['modify-ug-unassigned']      = in_array( 'modify-ug-unassigned', $caps ) ? true : false;
			$args['caps']['modify-ug-assigned-me']     = in_array( 'modify-ug-assigned-me', $caps ) ? true : false;
			$args['caps']['modify-ug-assigned-others'] = in_array( 'modify-ug-assigned-others', $caps ) ? true : false;

			return $args;
		}

		/**
		 * Edit permisstion settings to add agent role
		 *
		 * @param string $role - capabilities role.
		 * @return void
		 */
		public static function edit_agent_role_ticket_permissions( $role ) {
			?>

			<tr>
				<td><label for=""><?php esc_attr_e( 'Modify Usergroups', 'wpsc-usergroup' ); ?></label></td>
				<td><input name="caps[]" type="checkbox" <?php checked( $role['caps']['modify-ug-unassigned'], 1 ); ?> value="modify-ug-unassigned" class="wpsc-una"></td>
				<td><input name="caps[]" type="checkbox" <?php checked( $role['caps']['modify-ug-assigned-me'], 1 ); ?> value="modify-ug-assigned-me" class="wpsc-ame"></td>
				<td><input name="caps[]" type="checkbox" <?php checked( $role['caps']['modify-ug-assigned-others'], 1 ); ?> value="modify-ug-assigned-others" class="wpsc-ao"></td>
			</tr>

			<?php
		}

		/**
		 * Set edit agent role
		 *
		 * @param array  $new - changed value.
		 * @param array  $prev - existing value.
		 * @param string $caps - capabilities.
		 * @return array
		 */
		public static function set_edit_agent_role_ticket_permission( $new, $prev, $caps ) {

			$new['caps']['modify-ug-unassigned']      = in_array( 'modify-ug-unassigned', $caps ) ? true : false;
			$new['caps']['modify-ug-assigned-me']     = in_array( 'modify-ug-assigned-me', $caps ) ? true : false;
			$new['caps']['modify-ug-assigned-others'] = in_array( 'modify-ug-assigned-others', $caps ) ? true : false;

			return $new;
		}

		/**
		 * Check whether customer is usergroup supervisor
		 *
		 * @param int $customer_id - customer id.
		 * @return boolean
		 */
		public static function customer_is_supervisor( $customer_id ) {

			$flag = false;
			$usergroups = WPSC_Usergroup::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'supervisors',
							'compare' => 'IN',
							'val'     => array( $customer_id ),
						),
						array(
							'slug'    => 'members',
							'compare' => 'IN',
							'val'     => array( $customer_id ),
						),
					),
				)
			)['results'];
			if ( $usergroups ) {
				$flag = true;
			}
			return $flag;
		}
	}
endif;

WPSC_UG_Actions::init();
