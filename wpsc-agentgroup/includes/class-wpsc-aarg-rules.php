<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_AARG_Rules' ) ) :

	final class WPSC_AARG_Rules {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Get add assign agent rules.
			add_action( 'wpsc_arr_get_edit_rule', array( __CLASS__, 'get_edit_rule' ) );

			// Update assign agent rules.
			add_filter( 'wpsc_set_edit_rule_data', array( __CLASS__, 'set_edit_rule' ) );

			// Get agentgroup.
			add_filter( 'wpsc_arr_assign_agent_list', array( __CLASS__, 'assign_agentgroups' ), 10, 3 );
		}

		/**
		 * Get edit assign agent rules
		 *
		 * @param array $rule - assign agent rule.
		 * @return void
		 */
		public static function get_edit_rule( $rule ) {

			if ( check_ajax_referer( 'wpsc_aar_get_edit_rule', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}
			?>
			<div class="wpsc-input-group">
				<div class="label-container">
					<label for="">
						<?php esc_attr_e( 'Agentgroups', 'wpsc-ag' ); ?>
					</label>
				</div>
				<?php
				$agentgroups = isset( $rule['agentgroups'] ) && strlen( $rule['agentgroups'] ) ? explode( '|', $rule['agentgroups'] ) : array();
				?>
				<div class="aar_edit_agent_opt">
					<?php
						esc_attr_e( 'Select Groups', 'wpsc-ag' )
					?>
					<select name="agentgroups[]" class="agentgroups" multiple>
						<?php
						foreach ( $agentgroups as $group_id ) {
							$agentgroup = new WPSC_Agent( $group_id );
							?>
							<option selected value="<?php echo esc_attr( $agentgroup->id ); ?>"><?php echo esc_attr( $agentgroup->name ); ?></option>
							<?php
						}
						?>
					</select>
					<?php
					$assign_group_method = isset( $rule['assign_group_method'] ) ? esc_attr( $rule['assign_group_method'] ) : 'assign_all_groups';
					?>
					<div class="aar_edit_ag_radio">
						<div class="radio-container">
							<input type="radio" <?php checked( $assign_group_method, 'assign_all_groups' ); ?> id="assign_all_groups" name="assign_group_method" value="assign_all_groups">
							<label for="assign_all_groups"><?php esc_attr_e( 'Assign group only', 'wpsc-ag' ); ?></label>
						</div>
						<div class="radio-container">
							<?php
							$checked = in_array( $assign_group_method, array( 'cwh_include_supervisor', 'mw_include_supervisor' ) ) ? 'checked' : '';
							?>
							<input type="radio" <?php echo esc_attr( $checked ); ?> id="assign_group_include_supervisor" name="assign_group_method" value="assign_group_include_supervisors">
							<label for="assign_group_include_supervisor"><?php esc_attr_e( 'Assign group and one of its members including supervisors', 'wpsc-ag' ); ?></label>
						</div>
						<div class="radio-container radio-sub-option">
							<input type="radio" <?php checked( $assign_group_method, 'cwh_include_supervisor' ); ?> id="cwh_include_supervisor" name="assign_group_is_cwh_mw" value="cwh_include_supervisor">
							<label for="cwh_include_supervisor"><?php esc_attr_e( 'Closest working hours', 'wpsc-ag' ); ?></label>
						</div>
						<div class="radio-container radio-sub-option">
							<input type="radio" <?php checked( $assign_group_method, 'mw_include_supervisor' ); ?> id="mw_include_supervisor" name="assign_group_is_cwh_mw" value="mw_include_supervisor">
							<label for="mw_include_supervisor"><?php esc_attr_e( 'Minimum workload', 'wpsc-ag' ); ?></label>
						</div>
						<div class="radio-container">
							<?php
							$checked = in_array( $assign_group_method, array( 'cwh_exclude_supervisor', 'mw_exclude_supervisor' ) ) ? 'checked' : ''
							?>
							<input type="radio" <?php echo esc_attr( $checked ); ?> id="assign_group_exclude_supervisor" name="assign_group_method" value="assign_group_exclude_supervisors">
							<label for="assign_group_exclude_supervisor"><?php esc_attr_e( 'Assign group and one of its members excluding supervisors', 'wpsc-ag' ); ?></label>
						</div>
						<div class="radio-container radio-sub-option">
							<input type="radio" <?php checked( $assign_group_method, 'cwh_exclude_supervisor' ); ?> id="cwh_exclude_supervisor" name="assign_group_es_cwh_mw" value="cwh_exclude_supervisor">
							<label for="cwh_exclude_supervisor"><?php esc_attr_e( 'Closest working hours', 'wpsc-ag' ); ?></label>
						</div>
						<div class="radio-container radio-sub-option">
							<input type="radio" <?php checked( $assign_group_method, 'mw_exclude_supervisor' ); ?> id="mw_exclude_supervisor" name="assign_group_es_cwh_mw" value="mw_exclude_supervisor">
							<label for="mw_exclude_supervisor"><?php esc_attr_e( 'Minimum workload', 'wpsc-ag' ); ?></label>
						</div>
					</div>
					<script>
						jQuery('.aar_edit_ag_radio :radio').on('change', function(){
							selected_value = jQuery(this).val();
							if(selected_value == 'cwh_include_supervisor' || selected_value == 'mw_include_supervisor'){
								jQuery('#cwh_exclude_supervisor').prop("checked", false);
								jQuery('#mw_exclude_supervisor').prop("checked", false);
								jQuery('#assign_group_include_supervisor').prop("checked", true);
							}else if(selected_value == 'cwh_exclude_supervisor' || selected_value == 'mw_exclude_supervisor'){
								jQuery('#cwh_include_supervisor').prop("checked", false);
								jQuery('#mw_include_supervisor').prop("checked", false);
								jQuery('#assign_group_exclude_supervisor').prop("checked", true);
							}else if(selected_value == 'assign_group_include_supervisors'){
								jQuery('#cwh_exclude_supervisor').prop("checked", false);
								jQuery('#mw_exclude_supervisor').prop("checked", false);
								jQuery('#cwh_include_supervisor').prop("checked", true);
							}else if(selected_value == 'assign_group_exclude_supervisors'){
								jQuery('#cwh_include_supervisor').prop("checked", false);
								jQuery('#mw_include_supervisor').prop("checked", false);
								jQuery('#cwh_exclude_supervisor').prop("checked", true);
							}else if(selected_value == 'assign_all_groups'){
								jQuery('#cwh_include_supervisor').prop("checked", false);
								jQuery('#mw_include_supervisor').prop("checked", false);
								jQuery('#cwh_exclude_supervisor').prop("checked", false);
								jQuery('#mw_exclude_supervisor').prop("checked", false);
							}
						});
						// agents autocomplete
						jQuery('select.agentgroups').selectWoo({
							ajax: {
								url: supportcandy.ajax_url,
								dataType: 'json',
								delay: 250,
								data: function (params) {
									return {
										q: params.term, // search term
										page: params.page,
										action: 'wpsc_agent_autocomplete_admin_access',
										_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_agent_autocomplete_admin_access' ) ); ?>',
										isMultiple: 1, // to avoid none
										isAgentgroup: 1
									};
								},
								processResults: function (data, params) {
									var terms = [];
									if ( data ) {
										jQuery.each( data, function( id, text ) {
											terms.push( { id: text.id, text: text.title } );
										});
									}
									return {
										results: terms
									};
								},
								cache: true
							},
							escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
							minimumInputLength: 0,
							allowClear: false,
						});
					</script>
				</div>
			</div>
			<?php
		}

		/**
		 * Set edit assign agent rules
		 *
		 * @param array $rule - assign agent rule.
		 * @return array
		 */
		public static function set_edit_rule( $rule ) {

			if ( check_ajax_referer( 'wpsc_aar_set_edit_rule', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$groups = isset( $_POST['agentgroups'] ) ? array_filter( array_map( 'intval', $_POST['agentgroups'] ) ) : array();
			$rule['agentgroups'] = $groups ? implode( '|', $groups ) : '';

			$assign_group_method = isset( $_POST['assign_group_method'] ) ? sanitize_text_field( wp_unslash( $_POST['assign_group_method'] ) ) : '';

			if ( $assign_group_method === 'assign_group_include_supervisors' ) {

				$assign_group_method = isset( $_POST['assign_group_is_cwh_mw'] ) ? sanitize_text_field( wp_unslash( $_POST['assign_group_is_cwh_mw'] ) ) : '';

			} elseif ( $assign_group_method === 'assign_group_exclude_supervisors' ) {

				$assign_group_method = isset( $_POST['assign_group_es_cwh_mw'] ) ? sanitize_text_field( wp_unslash( $_POST['assign_group_es_cwh_mw'] ) ) : '';
			}

			if ( ! $assign_group_method ) {
				return $rule;
			}

			$rule['assign_group_method'] = $assign_group_method;

			return $rule;
		}

		/**
		 *  Assign agentgroups to tickets
		 *
		 * @param array       $agents - Agents selected to assign to the ticket.
		 * @param array       $rule - Assign agent rule.
		 * @param WPSC_Ticket $ticket - Ticket object.
		 * @return array
		 */
		public static function assign_agentgroups( $agents, $rule, $ticket ) {

			if ( ! ( isset( $rule['agentgroups'] ) && $rule['agentgroups'] ) ) {
				return $agents;
			}

			$tz           = wp_timezone();
			$date_created = ( $ticket->date_created )->setTimezone( $tz );
			$agentgroups  = array_filter(
				array_map(
					function ( $id ) {
						$agentgroup = WPSC_Agentgroup::get_by_agent_id( $id );
						return $agentgroup ? $id : false;
					},
					explode( '|', $rule['agentgroups'] )
				)
			);

			$agents = array_merge( $agents, $agentgroups );

			$temp_agents = array();
			foreach ( $agentgroups as $agent_id ) {

				$agentgroup = WPSC_Agentgroup::get_by_agent_id( $agent_id );

				// get all agents of the group.
				$all_agents = array();
				foreach ( $agentgroup->agents as $agent ) {
					$all_agents[] = $agent->id;
				}

				// get all supervisors of the group.
				$supervisors = array();
				foreach ( $agentgroup->supervisors as $agent ) {
					$supervisors[] = $agent->id;
				}

				// extract only agents from group (not supervisors).
				$only_agents = array_diff( $all_agents, $supervisors );
				$only_agents = array_values( $only_agents );

				switch ( $rule['assign_group_method'] ) {

					case 'cwh_include_supervisor':
						$temp_agents[] = WPSC_Assign_Agent::get_one_agent_with_cwh( $all_agents, $date_created );
						break;

					case 'mw_include_supervisor':
						$temp_agents[] = WPSC_Assign_Agent::get_one_agent_with_mw( $all_agents, $date_created );
						break;

					case 'cwh_exclude_supervisor':
						$temp_agents[] = WPSC_Assign_Agent::get_one_agent_with_cwh( $only_agents, $date_created );
						break;

					case 'mw_exclude_supervisor':
						$temp_agents[] = WPSC_Assign_Agent::get_one_agent_with_mw( $only_agents, $date_created );
						break;
				}
			}

			$agents = array_merge( $agents, $temp_agents );

			return $agents;
		}
	}
endif;

WPSC_AARG_Rules::init();
