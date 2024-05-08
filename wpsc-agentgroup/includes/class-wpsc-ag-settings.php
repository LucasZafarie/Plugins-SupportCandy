<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_AG_Settings' ) ) :

	final class WPSC_AG_Settings {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// load settings.
			add_filter( 'wpsc_support_agents_sections', array( __CLASS__, 'load_agentgroup_tab' ) );
			add_action( 'wp_ajax_wpsc_get_agentgroups', array( __CLASS__, 'load_agentgroups' ) );

			// get agentgroup list.
			add_action( 'wp_ajax_wpsc_get_agentgroup_list', array( __CLASS__, 'get_agentgroup_list' ) );

			// add new agentgroup.
			add_action( 'wp_ajax_wpsc_get_add_agentgroup', array( __CLASS__, 'get_add_agentgroup' ) );
			add_action( 'wp_ajax_wpsc_set_add_agentgroup', array( __CLASS__, 'set_add_agentgroup' ) );

			// edit agentgroup.
			add_action( 'wp_ajax_wpsc_get_edit_agentgroup', array( __CLASS__, 'get_edit_agentgroup' ) );
			add_action( 'wp_ajax_wpsc_set_edit_agentgroup', array( __CLASS__, 'set_edit_agentgroup' ) );

			// delete agentgroup.
			add_action( 'wp_ajax_wpsc_delete_agentgroup', array( __CLASS__, 'delete_agentgroup' ) );
		}

		/**
		 * Load agentgroup setting tab
		 *
		 * @param array $tabs - array of agent setting tabs.
		 * @return array
		 */
		public static function load_agentgroup_tab( $tabs ) {

			$tabs['agentgroups'] = array(
				'slug'     => 'agentgroups',
				'icon'     => 'users',
				'label'    => esc_attr__( 'Agentgroups', 'wpsc-ag' ),
				'callback' => 'wpsc_get_agentgroups',
			);
			return $tabs;
		}

		/**
		 * Load agentgroups settings
		 *
		 * @return void
		 */
		public static function load_agentgroups() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access', 'supportcandy' ), 401 );
			}?>

			<div class="wpsc-setting-header">
				<h2><?php esc_attr_e( 'Agentgroups', 'wpsc-ag' ); ?></h2>
			</div>
			<div class="wpsc-setting-section-body"></div>
			<?php
			wp_die();
		}

		/**
		 * Get agentgroup list
		 *
		 * @return void
		 */
		public static function get_agentgroup_list() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access', 'supportcandy' ), 401 );
			}

			$agentgroups = WPSC_Agentgroup::find()['results'];
			?>
			<div class="wpsc-setting-cards-container">
				<div class="wpsc_ags_crud" style="width: 100%; ">
					<table id="wpsc_agentgroup_list" class="wpsc-setting-tbl">
						<thead>
							<tr>
								<th><?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?></th>
								<th><?php esc_attr_e( 'Members', 'wpsc-ag' ); ?></th>
								<th><?php esc_attr_e( 'Supervisors', 'wpsc-ag' ); ?></th>
								<th><?php echo esc_attr( wpsc__( 'Actions', 'wpsc-ag' ) ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $agentgroups as $key => $agentgroup ) {
								?>
								<tr>
									<td><?php echo esc_attr( $agentgroup->name ); ?></td>
									<td>
										<?php
										foreach ( $agentgroup->agents as $user ) :
											echo esc_attr( $user->name ) . '<br>';
										endforeach;
										?>
									</td>
									<td>
										<?php
										foreach ( $agentgroup->supervisors as $user ) :
											echo esc_attr( $user->name ) . '<br>';
										endforeach;
										?>
									</td>
									<td>
										<a href="#" onclick="wpsc_get_edit_agentgroup(<?php echo esc_attr( $agentgroup->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_edit_agentgroup' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></a> |
										<a href="#" onclick="wpsc_delete_agentgroup(<?php echo esc_attr( $agentgroup->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_agentgroup' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></a>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
			</div>

			<script>
				jQuery('#wpsc_agentgroup_list').DataTable({
					ordering: false,
					pageLength: 20,
					bLengthChange: false,
					columnDefs: [ 
						{ targets: -1, searchable: false },
						{ targets: '_all', className: 'dt-left' }
					],
					dom: 'Bfrtip',
					buttons: [
						{
							text: '<?php echo esc_attr( wpsc__( 'Add new', 'supportcandy' ) ); ?>',
							className: 'wpsc-button small primary',
							action: function ( e, dt, node, config ) {
								jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
								var data = { action: 'wpsc_get_add_agentgroup', _ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_add_agentgroup' ) ); ?>' };
								jQuery.post(
									supportcandy.ajax_url,
									data,
									function (response) {
										jQuery( '.wpsc-setting-section-body' ).html( response );
									}
								);
							}
						}
					],
					language: supportcandy.translations.datatables
				});
			</script>
			<?php
			wp_die();
		}

		/**
		 * Get add agentgroup setting
		 *
		 * @return void
		 */
		public static function get_add_agentgroup() {

			if ( check_ajax_referer( 'wpsc_get_add_agentgroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			?>

			<form action="#" onsubmit="return false;" class="frm-add-agentgroup">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="name" autocomplete="off">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Agents', 'wpsc-ag' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select name="agents[]" class="agents" multiple></select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Supervisors', 'wpsc-ag' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select name="supervisors[]" class="supervisors" multiple></select>
				</div>

				<input type="hidden" name="action" value="wpsc_set_add_agentgroup">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_add_agentgroup' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button class="wpsc-button normal primary margin-right" onclick="wpsc_set_add_agentgroup(this);"><?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button class="wpsc-button normal secondary" onclick="wpsc_get_agentgroup_list();"><?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>

			<script>
				// agents autocomplete
				jQuery('select.agents').selectWoo({
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
								isAgentgroup: 0
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

				// selectWoo init for supervisors
				jQuery('select.supervisors').selectWoo();

				// change supervisor options depending on agents change
				jQuery('select.agents').change(function(){
					var agents = jQuery(this).val();
					var supervisors = jQuery('select.supervisors').val();
					jQuery('select.supervisors option').remove();
					jQuery.each(agents, function(index, value){
						var text = jQuery('select.agents').find('option[value='+value+']').text();
						jQuery('select.supervisors').append(new Option(text, value)).trigger('change');
					});
					jQuery('select.supervisors').val(supervisors);
				});
			</script>
			<?php
			wp_die();
		}

		/**
		 * Set add agentgroup
		 *
		 * @return void
		 */
		public static function set_add_agentgroup() {

			if ( check_ajax_referer( 'wpsc_set_add_agentgroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access', 'supportcandy' ), 401 );
			}

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( ! $name ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$agents = isset( $_POST['agents'] ) ? array_filter( array_map( 'intval', $_POST['agents'] ) ) : array();
			if ( ! $agents ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$supervisors = isset( $_POST['supervisors'] ) ? array_filter( array_map( 'intval', $_POST['supervisors'] ) ) : array();
			if ( ! $supervisors ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			// create agent record.
			$agent = WPSC_Agent::insert(
				array(
					'name'          => $name,
					'is_agentgroup' => 1,
					'is_active'     => 1,
				)
			);

			// create agentgroup record.
			$agentgroup = WPSC_Agentgroup::insert(
				array(
					'agent_id'    => $agent->id,
					'name'        => $name,
					'agents'      => implode( '|', $agents ),
					'supervisors' => implode( '|', $supervisors ),
				)
			);

			wp_die();
		}

		/**
		 * Get edit agentgroup
		 *
		 * @return void
		 */
		public static function get_edit_agentgroup() {

			if ( check_ajax_referer( 'wpsc_get_edit_agentgroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access', 'supportcandy' ), 401 );
			}

			$agentgroup_id = isset( $_POST['agentgroup_id'] ) ? intval( $_POST['agentgroup_id'] ) : 0;
			if ( ! $agentgroup_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$agentgroup = new WPSC_Agentgroup( $agentgroup_id );
			if ( ! $agentgroup->id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$super_ids = implode( ',', array_map( fn( $agent ) => $agent->id, $agentgroup->supervisors ) );
			?>

			<form action="#" onsubmit="return false;" class="frm-edit-agentgroup">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="name" value="<?php echo esc_attr( $agentgroup->name ); ?>" autocomplete="off">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Agents', 'wpsc-ag' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select name="agents[]" class="agents" multiple>
					<?php
					foreach ( $agentgroup->agents as $agent ) {
						?>
						<option selected value="<?php echo esc_attr( $agent->id ); ?>"><?php echo esc_attr( $agent->name ); ?></option>
						<?php
					}
					?>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Supervisors', 'wpsc-ag' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select name="supervisors[]" class="supervisors" multiple>
						<?php
						$supervisor_ids = array();
						foreach ( $agentgroup->supervisors as $agent ) :
							$supervisor_ids[] = $agent->id;
							?>
							<option selected value="<?php echo esc_attr( $agent->id ); ?>"><?php echo esc_attr( $agent->name ); ?></option>
							<?php
						endforeach;
						?>
					</select>
					<?php $super_ids = implode( ',', $supervisor_ids ); ?>
				</div>

				<input type="hidden" name="action" value="wpsc_set_edit_agentgroup">
				<input type="hidden" name="agentgroup_id" value="<?php echo esc_attr( $agentgroup_id ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_edit_agentgroup' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button class="wpsc-button normal primary margin-right" onclick="wpsc_set_edit_agentgroup(this);"><?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button class="wpsc-button normal secondary" onclick="wpsc_get_agentgroup_list();"><?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>

			<script>
				// agents autocomplete
				jQuery('select.agents').selectWoo({
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
								isAgentgroup: 0
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

				// selectWoo init for supervisors
				jQuery('select.supervisors').selectWoo();

				// change supervisor options depending on agents change
				jQuery('select.agents').change(function(){
					var agents = jQuery(this).val();
					var supervisors = jQuery('select.supervisors').val();
					jQuery('select.supervisors option').remove();
					jQuery.each(agents, function(index, value){
						var text = jQuery('select.agents').find('option[value='+value+']').text();
						jQuery('select.supervisors').append(new Option(text, value)).trigger('change');
					});
					jQuery('select.supervisors').val(supervisors);
				});

				jQuery('select.agents').trigger('change');
				jQuery('select.supervisors').val([<?php echo esc_attr( $super_ids ); ?>]).trigger('change');
			</script>
			<?php
			wp_die();
		}

		/**
		 * Set edit agentgroup
		 *
		 * @return void
		 */
		public static function set_edit_agentgroup() {

			if ( check_ajax_referer( 'wpsc_set_edit_agentgroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access', 'supportcandy' ), 401 );
			}

			$agentgroup_id = isset( $_POST['agentgroup_id'] ) ? intval( $_POST['agentgroup_id'] ) : 0;
			if ( ! $agentgroup_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$agentgroup = new WPSC_Agentgroup( $agentgroup_id );
			if ( ! $agentgroup->id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$agent_obj = new WPSC_Agent( $agentgroup->agent_id );

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( ! $name ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$agentgroup->name = $name;
			$agent_obj->name  = $name;

			$agents = isset( $_POST['agents'] ) ? array_filter( array_map( 'intval', $_POST['agents'] ) ) : array();
			if ( ! $agents ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$agentgroup->agents = $agents;

			$prev_supervisors = $agentgroup->supervisors;
			$supervisors      = isset( $_POST['supervisors'] ) ? array_filter( array_map( 'intval', $_POST['supervisors'] ) ) : array();
			if ( ! $supervisors ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$agentgroup->supervisors = $supervisors;

			$agentgroup->save();
			$agent_obj->save();

			// reset supervisor unresolved count.
			$agents = array_unique(
				array_merge(
					$prev_supervisors,
					$agentgroup->supervisors
				),
				SORT_REGULAR
			);

			foreach ( $agents as $agent ) {
				$agent->reset_unresolved_count();
			}

			wp_die();
		}

		/**
		 * Delete agentgroup
		 *
		 * @return void
		 */
		public static function delete_agentgroup() {

			if ( check_ajax_referer( 'wpsc_delete_agentgroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			global $wpdb;

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access', 'supportcandy' ), 401 );
			}

			$agentgroup_id = isset( $_POST['agentgroup_id'] ) ? intval( $_POST['agentgroup_id'] ) : 0;
			if ( ! $agentgroup_id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$agentgroup = new WPSC_Agentgroup( $agentgroup_id );
			if ( ! $agentgroup->id ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$agent_obj = new WPSC_Agent( $agentgroup->agent_id );

			// remove agentgroup from assigned agent from all tickets.
			$tickets = WPSC_Ticket::find(
				array(
					'items_per_page' => 0,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'assigned_agent',
							'compare' => '=',
							'val'     => $agentgroup->agent_id,
						),
					),
				)
			)['results'];
			foreach ( $tickets as $ticket ) {
				if ( ! $ticket->assigned_agent ) {
					continue;
				}
				if ( in_array( $agent_obj, $ticket->assigned_agent ) ) {
					$assigned_agent = $ticket->assigned_agent;
					$index          = array_search( $agent_obj, $assigned_agent );
					unset( $assigned_agent[ $index ] );
					$ticket->assigned_agent = $assigned_agent;
					$ticket->save();
				}
			}

			// reset unresolved count for supervisors.
			foreach ( $agentgroup->supervisors as $agent ) {
				$agent->reset_unresolved_count();
			}

			// delete an agent record for this group.
			$wpdb->delete(
				$wpdb->prefix . 'psmsc_agents',
				array( 'id' => $agentgroup->agent_id )
			);

			// now delete agentgroup.
			WPSC_Agentgroup::destroy( $agentgroup );

			wp_die();
		}
	}
endif;

WPSC_AG_Settings::init();
