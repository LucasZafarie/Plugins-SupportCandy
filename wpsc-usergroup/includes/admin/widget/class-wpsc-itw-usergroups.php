<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ITW_Usergroups' ) ) :

	final class WPSC_ITW_Usergroups {

		/**
		 * Ignore ticket custom field types for agentonly fields
		 *
		 * @var array
		 */
		public static $ignore_cft = array();

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// edit widget settings.
			add_action( 'wp_ajax_wpsc_get_tw_usergroups', array( __CLASS__, 'get_tw_usergroups' ) );
			add_action( 'wp_ajax_wpsc_set_tw_usergroups', array( __CLASS__, 'set_tw_usergroups' ) );

			// usergroup view members.
			add_action( 'wp_ajax_wpsc_ug_members_info', array( __CLASS__, 'get_members_info' ) );
			add_action( 'wp_ajax_nopriv_wpsc_ug_members_info', array( __CLASS__, 'get_members_info' ) );

			// usergroup view details.
			add_action( 'wp_ajax_wpsc_ug_view_details', array( __CLASS__, 'get_ug_view_details' ) );
			add_action( 'wp_ajax_nopriv_wpsc_ug_view_details', array( __CLASS__, 'get_ug_view_details' ) );

			// usergroup all tickets.
			add_action( 'wp_ajax_wpsc_ug_all_tickets', array( __CLASS__, 'get_ug_all_tickets' ) );
			add_action( 'wp_ajax_nopriv_wpsc_ug_all_tickets', array( __CLASS__, 'get_ug_all_tickets' ) );

			// change ticket usergroups.
			add_action( 'wp_ajax_wpsc_it_get_edit_ug', array( __CLASS__, 'get_edit_ticket_usergroups' ) );
			add_action( 'wp_ajax_nopriv_wpsc_it_get_edit_ug', array( __CLASS__, 'get_edit_ticket_usergroups' ) );
			add_action( 'wp_ajax_wpsc_it_set_edit_ug', array( __CLASS__, 'set_edit_ticket_usergroups' ) );
			add_action( 'wp_ajax_nopriv_wpsc_it_set_edit_ug', array( __CLASS__, 'set_edit_ticket_usergroups' ) );
		}

		/**
		 * Prints body of current widget
		 *
		 * @param object $ticket - ticket object.
		 * @param array  $settings - settings array.
		 * @return void
		 */
		public static function print_widget( $ticket, $settings ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_guest || ! (
					(
						(
							WPSC_Individual_Ticket::$view_profile == 'customer' ||
							$ticket->customer->id == $current_user->customer->id
						) &&
						$settings['allow-customer']
					) ||
					( WPSC_Individual_Ticket::$view_profile == 'agent' && in_array( $current_user->agent->role, $settings['allowed-agent-roles'] ) )
				)
			) {
				return;
			}

			$usergroups = $ticket->usergroups;
			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			?>
			<div class="wpsc-it-widget wpsc-itw-usergroups">
				<div class="wpsc-widget-header">
					<h2><?php echo esc_attr( $settings['title'] ); ?></h2>
					<?php
					if ( $ticket->is_active &&
						(
							( $ticket->customer->id == $current_user->customer->id && $ug_settings['allow-customers-to-modify'] ) ||
							( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'modify-ug' ) )
						)
					) {
						?>
						<span onclick="wpsc_it_get_edit_ug(<?php echo esc_attr( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_it_get_edit_ug' ) ); ?>')"><?php WPSC_Icons::get( 'edit' ); ?></span>
						<?php
					}
					?>
					<span class="wpsc-itw-toggle" data-widget="wpsc-itw-usergroups"><?php WPSC_Icons::get( 'chevron-up' ); ?></span>
				</div>
				<div class="wpsc-widget-body">
					<?php
					if ( $usergroups ) {
						foreach ( $usergroups as $usergroup ) {
							?>
							<div class="info-list-item">
								<div class="info-val fullwidth"><?php echo esc_attr( $usergroup->name ); ?></div>
								<div class="wpsc-filter-actions">
									<div class="wpsc-link" onclick="wpsc_ug_members_info(<?php echo intval( $usergroup->id ); ?>, <?php echo intval( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_ug_members_info' ) ); ?>')"><?php echo esc_attr__( 'Members', 'wpsc-usergroup' ); ?></div>
									<div class="action-devider"></div>
									<div class="wpsc-link" onclick="wpsc_ug_view_details(<?php echo intval( $usergroup->id ); ?>, <?php echo intval( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_ug_view_details' ) ); ?>')"><?php echo esc_attr__( 'Details', 'wpsc-usergroup' ); ?></div>
									<?php
									if ( $current_user->is_agent || WPSC_UG_Actions::customer_is_supervisor( $ticket->customer->id ) ) {
										?>
										<div class="action-devider"></div>
										<div class="wpsc-link" onclick="wpsc_ug_all_tickets(<?php echo intval( $usergroup->id ); ?>, <?php echo intval( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_ug_all_tickets' ) ); ?>')"><?php echo esc_attr__( 'Tickets', 'wpsc-usergroup' ); ?></div>
										<?php
									}
									?>
								</div>
							</div>
							<?php
						}
					} else {

						?>
						<div class="wpsc-widget-default"><?php esc_attr_e( 'Não aplicado', 'supportcandy' ); ?></div>
						<?php
					}
					do_action( 'wpsc_itw_usergroups', $ticket )
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Get edit widget settings
		 *
		 * @return void
		 */
		public static function get_tw_usergroups() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ticket_widgets = get_option( 'wpsc-ticket-widget', array() );
			$usergroups     = $ticket_widgets['usergroups'];
			$title          = $usergroups['title'];
			$roles          = get_option( 'wpsc-agent-roles', array() );
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-usergroups">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
					</div>
					<input name="label" type="text" value="<?php echo esc_attr( $usergroups['title'] ); ?>" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Enable', 'supportcandy' ) ); ?></label>
					</div>
					<select name="is_enable">
						<option <?php selected( $usergroups['is_enable'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $usergroups['is_enable'], '0' ); ?>  value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Show members', 'wpsc-usergroup' ); ?></label>
					</div>
					<select name="show-members">
						<option <?php selected( $usergroups['show-members'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $usergroups['show-members'], '0' ); ?>  value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Allowed for customer', 'supportcandy' ); ?></label>
					</div>
					<select id="allow-customer" name="allow-customer">
						<option <?php selected( $usergroups['allow-customer'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $usergroups['allow-customer'], '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Allowed agent roles', 'supportcandy' ) ); ?></label>
					</div>
					<select multiple id="wpsc-select-agents" name="agents[]" placeholder="">
						<?php
						foreach ( $roles as $key => $role ) {
							$selected = in_array( $key, $usergroups['allowed-agent-roles'] ) ? 'selected' : ''
							?>
							<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $role['label'] ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<script>
					jQuery('#wpsc-select-agents').selectWoo({
						allowClear: false,
						placeholder: ""
					});
				</script>
				<?php do_action( 'wpsc_get_woo_body' ); ?>
				<input type="hidden" name="action" value="wpsc_set_tw_usergroups">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_tw_usergroups' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_tw_usergroups(this);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php
			do_action( 'wpsc_get_tw_usergroups_widget_footer' );
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Set edit widget settings
		 *
		 * @return void
		 */
		public static function set_tw_usergroups() {

			if ( check_ajax_referer( 'wpsc_set_tw_usergroups', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $label ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$is_enable          = isset( $_POST['is_enable'] ) ? intval( $_POST['is_enable'] ) : 0;
			$show_members       = isset( $_POST['show-members'] ) ? intval( $_POST['show-members'] ) : 1;
			$allow_for_customer = isset( $_POST['allow-customer'] ) ? intval( $_POST['allow-customer'] ) : 0;
			$agents             = isset( $_POST['agents'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['agents'] ) ) ) : array();

			$ticket_widgets                                      = get_option( 'wpsc-ticket-widget', array() );
			$ticket_widgets['usergroups']['title']               = $label;
			$ticket_widgets['usergroups']['is_enable']           = $is_enable;
			$ticket_widgets['usergroups']['show-members']        = $show_members;
			$ticket_widgets['usergroups']['allow-customer']      = $allow_for_customer;
			$ticket_widgets['usergroups']['allowed-agent-roles'] = $agents;
			update_option( 'wpsc-ticket-widget', $ticket_widgets );
			wp_die();
		}

		/**
		 * Get members information in ticket widget
		 *
		 * @return void
		 */
		public static function get_members_info() {

			if ( check_ajax_referer( 'wpsc_ug_members_info', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$current_user = WPSC_Current_User::$current_user;
			$ticket    = WPSC_Individual_Ticket::$ticket;

			// do not allow if agent does not have read permission of ticket or ticket does not beong to current user.
			if ( ! (
				( WPSC_Individual_Ticket::$view_profile == 'customer' || $ticket->customer->id == $current_user->customer->id ) ||
				( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'view' ) )
			) ) {
				wp_send_json_error( 'Something went wrong!', 400 );
			}

			$ug_id = isset( $_POST['ug_id'] ) ? intval( $_POST['ug_id'] ) : 0;
			if ( ! $ug_id ) {
				wp_send_json_error( 'Invalid request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $ug_id );
			if ( ! $usergroup->id ) {
				wp_send_json_error( 'Invalid request!', 400 );
			}

			$supervisor_ids = array_map(
				fn( $customer ) => $customer->id,
				$usergroup->supervisors
			);

			$unique_id = uniqid();
			ob_start();
			?>
			<div class="wpsc-thread-info">

				<div style="width: 100%;">

					<table class="wpsc-setting-tbl <?php echo esc_attr( $unique_id ); ?>" style="margin-bottom: 15px;">
						<thead>
							<tr>
								<th><?php esc_attr_e( 'Members', 'wpsc-usergroup' ); ?></th>
								<th><?php esc_attr_e( 'Is Supervisor', 'wpsc-usergroup' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $usergroup->members as $customer ) {
								?>
								<tr>
									<td><?php echo esc_attr( $customer->name ); ?></td>
									<td>
										<?php
										if ( in_array( $customer->id, $supervisor_ids ) ) {
											esc_attr_e( 'Yes', 'supportcandy' );
										} else {
											esc_attr_e( 'No', 'supportcandy' );
										}
										?>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<script>
						jQuery('.<?php echo esc_attr( $unique_id ); ?>').DataTable({
							order: [ 1, 'desc' ],
							pageLength: 20,
							bLengthChange: false,
							columnDefs: [ 
								{ targets: -1, searchable: false },
								{ targets: '_all', className: 'dt-left' }
							],
							language: supportcandy.translations.datatables
						});
					</script>
				</div>
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Close', 'supportcandy' ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $usergroup->name,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}


		/**
		 * Get members information in ticket widget
		 *
		 * @return void
		 */
		public static function get_ug_view_details() {

			if ( check_ajax_referer( 'wpsc_ug_view_details', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$current_user = WPSC_Current_User::$current_user;
			$ticket    = WPSC_Individual_Ticket::$ticket;

			// do not allow if agent does not have read permission of ticket or ticket does not beong to current user.
			if ( ! (
				( WPSC_Individual_Ticket::$view_profile == 'customer' || $ticket->customer->id == $current_user->customer->id ) ||
				( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'view' ) )
			) ) {
				wp_send_json_error( 'Something went wrong!', 400 );
			}

			$ug_id = isset( $_POST['ug_id'] ) ? intval( $_POST['ug_id'] ) : 0;
			if ( ! $ug_id ) {
				wp_send_json_error( 'Invalid request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $ug_id );
			if ( ! $usergroup->id ) {
				wp_send_json_error( 'Invalid request!', 400 );
			}

			$unique_id = uniqid();
			ob_start();
			?>
			<div class="wpsc-thread-info">
				<div style="width: 100%;">
					<div style="margin-bottom: 10px;">
						<?php
						if ( $usergroup->description ) {
							echo esc_attr( $usergroup->description );
						}
						?>
					</div>

					<table class="wpsc-setting-tbl <?php echo esc_attr( $unique_id ); ?>">
						<thead>
							<tr>
								<th><?php esc_attr_e( 'Field', 'wpsc-usergroup' ); ?></th>
								<th><?php esc_attr_e( 'Value', 'wpsc-usergroup' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {
								if ( $cf->field == 'usergroup' ) {
									?>
									<tr>
										<td><?php echo esc_attr( $cf->name ); ?></td>
										<td><?php $cf->type::print_widget_ticket_field_val( $cf, $usergroup ); ?></td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
					<script>
						jQuery('.<?php echo esc_attr( $unique_id ); ?>').DataTable({
							order: [ 1, 'desc' ],
							paging: false,
							ordering: false,
							searching: false,
							info: false,
							language: supportcandy.translations.datatables
						});
					</script>

				</div>
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Close', 'supportcandy' ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $usergroup->name,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Get all tickets in ticket widget
		 *
		 * @return void
		 */
		public static function get_ug_all_tickets() {

			if ( check_ajax_referer( 'wpsc_ug_all_tickets', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$current_user = WPSC_Current_User::$current_user;
			$ticket    = WPSC_Individual_Ticket::$ticket;

			// do not allow if agent does not have read permission of ticket or ticket does not beong to current user.
			if ( ! (
				( WPSC_Individual_Ticket::$view_profile == 'customer' || $ticket->customer->id == $current_user->customer->id ) ||
				( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'view' ) )
			) ) {
				wp_send_json_error( 'Something went wrong!', 400 );
			}

			$ug_id = isset( $_POST['ug_id'] ) ? intval( $_POST['ug_id'] ) : 0;
			if ( ! $ug_id ) {
				wp_send_json_error( 'Invalid request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $ug_id );
			if ( ! $usergroup->id ) {
				wp_send_json_error( 'Invalid request!', 400 );
			}

			$tickets = WPSC_Ticket::find(
				array(
					'items_per_page' => 0,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'usergroups',
							'compare' => '=',
							'val'     => $usergroup->id,
						),
						array(
							'slug'    => 'is_active',
							'compare' => '=',
							'val'     => 1,
						),
					),
				)
			)['results'];

			$list_items = get_option( 'wpsc-atl-list-items' );

			ob_start();
			?>
			<div class="wpsc-thread-info">
				<div style="width: 100%;">
					<table class="wpsc-ug-all-tickets wpsc-setting-tbl">
						<thead>
							<tr>
								<?php
								foreach ( $list_items as $slug ) :
									$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
									if ( ! $cf ) {
										continue;
									}
									?>
									<th style="min-width: <?php echo esc_attr( $cf->tl_width ); ?>px;"><?php echo esc_attr( $cf->name ); ?></th>
									<?php
								endforeach;
								?>
							</tr>
						</thead>	
						<tbody>
							<?php
							foreach ( $tickets as $ticket ) :
								?>
								<tr id="wpsc-ug-all-tickets" onclick="if(link) wpsc_open_customer_ticket(<?php echo esc_attr( $ticket->id ); ?>)">
									<?php
									foreach ( $list_items as $slug ) :
										$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
										if ( ! $cf ) {
											continue;
										}
										?>
										<td onmouseover="link=true;">
											<?php
											if ( in_array( $cf->field, array( 'ticket', 'agentonly' ) ) ) {
												$cf->type::print_tl_ticket_field_val( $cf, $ticket );
											} else {
												$cf->type::print_tl_customer_field_val( $cf, $ticket->customer );
											}
											?>
										</td>
										<?php
									endforeach;
									?>
								</tr>
								<?php
							endforeach;
							?>
						</tbody>
					</table>
					<script>
						jQuery('table.wpsc-ug-all-tickets').DataTable({
							ordering: false,
							pageLength: 20,
							bLengthChange: false,
							columnDefs: [ 
								{ targets: -1, searchable: false },
								{ targets: '_all', className: 'dt-left' }
							],
							language: supportcandy.translations.datatables
						});

						function wpsc_open_customer_ticket( id ) {

							if ( wpsc_is_description_text() ) {
								if ( confirm( supportcandy.translations.warning_message ) ) {
									wpsc_close_modal(); 
									ticket_id = jQuery('#wpsc-current-ticket').val();
									wpsc_clear_saved_draft_reply( ticket_id );
									wpsc_get_individual_ticket( id );
								} else {
									return;
								}
							}else{
								wpsc_close_modal(); 
								wpsc_get_individual_ticket( id );
							}
						}
					</script>
				</div>	
			</div>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Close', 'supportcandy' ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $usergroup->name,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Get edit ticket usergroups from individual ticket
		 *
		 * @return void
		 */
		public static function get_edit_ticket_usergroups() {

			if ( check_ajax_referer( 'wpsc_it_get_edit_ug', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$current_user = WPSC_Current_User::$current_user;
			$ticket = WPSC_Individual_Ticket::$ticket;
			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			$usergroups = WPSC_Usergroup::get_by_customer( $ticket->customer );
			$ticket_ug_ids = array_map(
				fn( $usergroup ) => $usergroup->id,
				$ticket->usergroups
			);

			if ( ! (
				$ticket->is_active &&
				(
					( $ticket->customer->id == $current_user->customer->id && $ug_settings['allow-customers-to-modify'] ) ||
					( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'modify-ug' ) )
				)
			) ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			ob_start();
			$unique_id = uniqid()
			?>
			<form action="#" onsubmit="return false;" class="change-usergroups <?php echo esc_attr( $unique_id ); ?>">
				<div class="wpsc-input-group">
					<select class="<?php echo esc_attr( $unique_id ); ?>" multiple name="usergroups[]">
						<?php
						foreach ( $usergroups as $usergroup ) {
							$selected = in_array( $usergroup->id, $ticket_ug_ids ) ? 'selected' : '';
							?>
							<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $usergroup->id ); ?>"><?php echo esc_attr( $usergroup->name ); ?></option>
							<?php
						}
						?>
					</select>
					<script>
						jQuery('select.<?php echo esc_attr( $unique_id ); ?>').selectWoo();
					</script>
				</div>
				<input type="hidden" name="action" value="wpsc_it_set_edit_ug">
				<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_it_set_edit_ug' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_it_set_edit_ug(this, <?php echo esc_attr( $ticket->id ); ?>, '<?php echo esc_attr( $unique_id ); ?>');">
				<?php esc_attr_e( 'Submit', 'supportcandy' ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Cancel', 'supportcandy' ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => __( 'Change Usergroups', 'wpsc-usergroup' ),
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Set edit ticket usergroup in an individual ticket
		 *
		 * @return void
		 */
		public static function set_edit_ticket_usergroups() {

			if ( check_ajax_referer( 'wpsc_it_set_edit_ug', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$current_user = WPSC_Current_User::$current_user;
			$ticket = WPSC_Individual_Ticket::$ticket;
			$prev = $ticket->usergroups;
			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			$cust_ug_ids = array_filter(
				array_map(
					fn( $usergroup ) => $usergroup->id,
					WPSC_Usergroup::get_by_customer( $ticket->customer )
				)
			);

			if ( ! (
				$ticket->is_active &&
				(
					( $ticket->customer->id == $current_user->customer->id && $ug_settings['allow-customers-to-modify'] ) ||
					( WPSC_Individual_Ticket::$view_profile == 'agent' && WPSC_Individual_Ticket::has_ticket_cap( 'modify-ug' ) )
				)
			) ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$ug_ids = isset( $_POST['usergroups'] ) ? array_map( 'intval', $_POST['usergroups'] ) : array();
			$usergroups = array_filter(
				array_map(
					function ( $id ) use ( $cust_ug_ids ) {
						$usergroup = new WPSC_Usergroup( $id );
						return $usergroup->id && in_array( $id, $cust_ug_ids ) ? $usergroup : false;
					},
					$ug_ids
				)
			);

			$ticket->usergroups = $usergroups;
			$ticket->save();
			do_action( 'wpsc_change_usergroup', $ticket, $prev, $ticket->usergroups, $current_user->customer->id );
			wp_die();
		}
	}
endif;

WPSC_ITW_Usergroups::init();
