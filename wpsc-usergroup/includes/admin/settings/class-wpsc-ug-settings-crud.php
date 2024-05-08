<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Settings_CRUD' ) ) :

	final class WPSC_UG_Settings_CRUD {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// setting actions.
			add_action( 'wp_ajax_wpsc_ug_get_usergroups_settings', array( __CLASS__, 'get_usergroups_settings' ) );

			// add new usergroup.
			add_action( 'wp_ajax_wpsc_get_add_new_usergroup', array( __CLASS__, 'get_add_new_usergroup' ) );
			add_action( 'wp_ajax_wpsc_set_add_new_usergroup', array( __CLASS__, 'set_add_new_usergroup' ) );

			// edit usergroup.
			add_action( 'wp_ajax_wpsc_get_edit_usergroup', array( __CLASS__, 'get_edit_usergroup' ) );
			add_action( 'wp_ajax_wpsc_set_edit_usergroup', array( __CLASS__, 'set_edit_usergroup' ) );

			// clone usergroup.
			add_action( 'wp_ajax_wpsc_get_clone_usergroup', array( __CLASS__, 'get_clone_usergroup' ) );

			// delete usergroup.
			add_action( 'wp_ajax_wpsc_delete_usergroup', array( __CLASS__, 'delete_usergroup' ) );
			add_action( 'wp_ajax_wpsc_delete_ug_ticket_utility', array( __CLASS__, 'delete_usergroup_ticket_utility' ) );

			// WP user autocomplete.
			add_action( 'wp_ajax_wpsc_search_wp_users_exclude_agents', array( __CLASS__, 'search_wp_users_exclude_agents' ) );
		}

		/**
		 * Get general settings
		 *
		 * @return void
		 */
		public static function get_usergroups_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}
			$usergroups = WPSC_Usergroup::find( array( 'items_per_page' => 0 ) )['results'];?>

			<div class="wpsc_ugs_crud">
				<table id="wpsc_usergroup_list" class="wpsc-setting-tbl">
					<thead>
						<tr>
							<th><?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?></th>
							<th><?php esc_attr_e( 'Members', 'wpsc-usergroup' ); ?></th>
							<th><?php esc_attr_e( 'Supervisors', 'wpsc-usergroup' ); ?></th>
							<th><?php echo esc_attr( wpsc__( 'Actions', 'supportcandy' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $usergroups as $key => $usergroup ) {
							?>
							<tr>
								<td><?php echo esc_attr( $usergroup->name ); ?></td>
								<td>
								<?php
								foreach ( $usergroup->members as $user ) :
									echo esc_attr( $user->name ) . '<br>';
									endforeach;
								?>
								</td>
								<td>
								<?php
								foreach ( $usergroup->supervisors as $user ) :
									echo esc_attr( $user->name ) . '<br>';
									endforeach;
								?>
								</td>
								<td>
									<a href="#" onclick="wpsc_get_clone_usergroup(<?php echo esc_attr( $usergroup->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_clone_usergroup' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Clone', 'supportcandy' ) ); ?></a> |
									<a href="#" onclick="wpsc_get_edit_usergroup(<?php echo esc_attr( $usergroup->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_edit_usergroup' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></a> |
									<a href="#" onclick="wpsc_delete_usergroup(<?php echo esc_attr( $usergroup->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_usergroup' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></a>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
			<script>
				jQuery('#wpsc_usergroup_list').DataTable({
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
								var data = { 
									action: 'wpsc_get_add_new_usergroup', 
									_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_add_new_usergroup' ) ); ?>'
								};
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
		 * Get add new usergroup
		 *
		 * @return void
		 */
		public static function get_add_new_usergroup() {

			if ( check_ajax_referer( 'wpsc_get_add_new_usergroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			?>

			<form action="#" onsubmit="return false;" class="frm-add-new-usergroup">

				<div data-type="textfield" data-required="true" class="wpsc-input-group label">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Name', 'wpsc-usergroup' ) ); ?> 
							<span class="required-char">*</span>
						</label>
					</div>
					<input name="label" type="text" autocomplete="off"/>
				</div>

				<div data-type="textfield" data-required="false" class="wpsc-input-group description">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Description', 'wpsc-usergroup' ) ); ?>
						</label>
					</div>
					<input name="description" type="text" autocomplete="off"/>
				</div>

				<div data-type="multi-select" data-required="true" class="wpsc-input-group members">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Members', 'wpsc-usergroup' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select name="wpsc-ug-members[]" class="members" multiple></select>
				</div>

				<div data-type="multi-select" data-required="false" class="wpsc-input-group supervisors">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Supervisors', 'wpsc-usergroup' ); ?>
						</label>
					</div>
					<select name="wpsc-ug-supervisors[]" class="supervisors" multiple></select>
				</div>

				<div data-type="single-select" data-required="false" class="wpsc-input-group category">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Category', 'wpsc-usergroup' ); ?>
						</label>
					</div>
					<?php $categories = WPSC_Category::find( array( 'items_per_page' => 0 ) )['results']; ?>
					<select name="wpsc-ug-category">
						<option value=""></option>
						<?php
						foreach ( $categories as $category ) {
							?>
							<option value="<?php echo esc_attr( $category->id ); ?>"><?php echo esc_attr( $category->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<div class="wpsc-ug-custom-fields">
				<?php

				foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

					if (
						! class_exists( $cf->type ) ||
						$cf->field != 'usergroup'
					) {
						continue;
					}

					$cf->type::print_cf_input( $cf );
				}
				?>

				</div>
				<input type="hidden" name="action" value="wpsc_set_add_new_usergroup">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_add_new_usergroup' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_add_new_usergroup(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="jQuery('.wpsc-setting-nav.active').trigger('click');">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>

			<script>
				// members autocomplete.
				jQuery('select.members').selectWoo({
					ajax: {
						url: supportcandy.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								q: params.term, // search term.
								page: params.page,
								action: 'wpsc_search_wp_users_exclude_agents',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_search_wp_users_exclude_agents' ) ); ?>',
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
					escapeMarkup: function (markup) { return markup; }, // let our custom formatter work.
					minimumInputLength: 0,
					allowClear: false,
				});

				// selectWoo init for supervisors.
				jQuery('select.supervisors').selectWoo();

				// change supervisor options depending on members change.
				jQuery('select.members').change(function(){
					var members = jQuery(this).val();
					var supervisors = jQuery('select.supervisors').val();
					jQuery('select.supervisors option').remove();
					jQuery.each(members, function(index, value){
						var text = jQuery('select.members').find('option[value='+value+']').text();
						jQuery('select.supervisors').append(new Option(text, value)).trigger('change');
					});
					jQuery('select.supervisors').val(supervisors);
				});
			</script>
			<?php

			wp_die();
		}

		/**
		 * Set add new usergroup
		 *
		 * @return void
		 */
		public static function set_add_new_usergroup() {

			if ( check_ajax_referer( 'wpsc_set_add_new_usergroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$data = array();

			$name = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $name ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$data['name'] = $name;

			$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
			$data['description'] = $description;

			$members = isset( $_POST['wpsc-ug-members'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['wpsc-ug-members'] ) ) ) : array();
			if ( ! $members ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$supervisors = isset( $_POST['wpsc-ug-supervisors'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['wpsc-ug-supervisors'] ) ) ) : array();

			$ug_members     = array();
			$ug_supervisors = array();
			foreach ( $members as $member ) {

				$customer = WPSC_Customer::get_by_user_id( $member );
				$ug_members[] = $customer->id;
				if ( in_array( $member, $supervisors ) ) {
					$ug_supervisors[] = $customer->id;
				}
			}

			$data['members']     = implode( '|', $ug_members );
			$data['supervisors'] = implode( '|', $ug_supervisors );
			$data['category']    = isset( $_POST['wpsc-ug-category'] ) ? intval( $_POST['wpsc-ug-category'] ) : '';

			foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

				if (
					! class_exists( $cf->type ) ||
					$cf->field != 'usergroup'
				) {
					continue;
				}

				$data[ $cf->slug ] = $cf->type::get_cf_input_val( $cf );
			}

			$usergroup = WPSC_Usergroup::insert( $data );

			do_action( 'wpsc_set_add_new_usergroup', $usergroup );

			wp_die();
		}

		/**
		 * Get edit usergroup
		 *
		 * @return void
		 */
		public static function get_edit_usergroup() {

			if ( check_ajax_referer( 'wpsc_get_edit_usergroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $id );
			if ( ! $usergroup ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}
			?>

			<form action="#" onsubmit="return false;" class="frm-edit-usergroup">

				<div data-type="textfield" data-required="true" class="wpsc-input-group label">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Name', 'wpsc-usergroup' ) ); ?> 
							<span class="required-char">*</span>
						</label>
					</div>
					<input name="label" type="text" value="<?php echo esc_attr( $usergroup->name ); ?>" autocomplete="off"/>
				</div>

				<div data-type="textfield" data-required="true" class="wpsc-input-group description">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Description', 'wpsc-usergroup' ) ); ?> 
						</label>
					</div>
					<input name="description" type="text" value="<?php echo esc_attr( $usergroup->description ); ?>" autocomplete="off"/>
				</div>

				<div data-type="multi-select" data-required="true" class="wpsc-input-group members">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Members', 'wpsc-usergroup' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<select name="wpsc-ug-members[]" class="members" multiple>
						<?php
						foreach ( $usergroup->members as $user ) {
							?>
							<option selected value="<?php echo esc_attr( $user->user->ID ); ?>"><?php echo esc_attr( $user->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<div data-type="multi-select" data-required="true" class="wpsc-input-group supervisors">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Supervisors', 'wpsc-usergroup' ); ?>
						</label>
					</div>
					<select name="wpsc-ug-supervisors[]" class="supervisors" multiple>
						<?php
						$supervisor_ids = array();
						foreach ( $usergroup->supervisors as $user ) {
							$supervisor_ids[] = $user->user->ID;
							?>
							<option selected value="<?php echo esc_attr( $user->user->ID ); ?>"><?php echo esc_attr( $user->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<div data-type="single-select" data-required="false" class="wpsc-input-group category">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Category', 'wpsc-usergroup' ); ?>
						</label>
					</div>
					<?php $categories = WPSC_Category::find( array( 'items_per_page' => 0 ) )['results']; ?>
					<select name="wpsc-ug-category">
						<option value=""></option>
						<?php
						foreach ( $categories as $category ) {
							$selected = $usergroup->category && $category->id == $usergroup->category->id ? 'selected="selected"' : '';
							?>
							<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $category->id ); ?>"><?php echo esc_attr( $category->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<div class="wpsc-ug-custom-fields">
				<?php

				foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

					if (
						! class_exists( $cf->type ) ||
						$cf->field != 'usergroup'
					) {
						continue;
					}

					$cf->type::print_cf_input( $cf, $usergroup->{$cf->slug} );

				}
				?>

				</div>

				<input type="hidden" name="action" value="wpsc_set_edit_usergroup">
				<input type="hidden" name="ug_id" value="<?php echo esc_attr( $usergroup->id ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_edit_usergroup' ) ); ?>">
			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_edit_usergroup(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="jQuery('.wpsc-setting-nav.active').trigger('click');">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>

			<script>
				// members autocomplete.
				jQuery('select.members').selectWoo({
					ajax: {
						url: supportcandy.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								q: params.term, // search term.
								page: params.page,
								action: 'wpsc_search_wp_users_exclude_agents',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_search_wp_users_exclude_agents' ) ); ?>',
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
					escapeMarkup: function (markup) { return markup; }, // let our custom formatter work.
					minimumInputLength: 0,
					allowClear: false,
				});

				// selectWoo init for supervisors.
				jQuery('select.supervisors').selectWoo();

				// change supervisor options depending on members change.
				jQuery('select.members').change(function(){
					var members = jQuery(this).val();
					var supervisors = jQuery('select.supervisors').val();
					jQuery('select.supervisors option').remove();
					jQuery.each(members, function(index, value){
						var text = jQuery('select.members').find('option[value='+value+']').text();
						jQuery('select.supervisors').append(new Option(text, value)).trigger('change');
					});
					jQuery('select.supervisors').val(supervisors);
				});
				jQuery('select.members').trigger('change');
				<?php $supervisor_ids = implode( ',', $supervisor_ids ); ?>
				jQuery('select.supervisors').val([<?php echo esc_attr( $supervisor_ids ); ?>]).trigger('change');
			</script>
			<?php

			wp_die();
		}

		/**
		 * Update usergroup
		 *
		 * @return void
		 */
		public static function set_edit_usergroup() {

			if ( check_ajax_referer( 'wpsc_set_edit_usergroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['ug_id'] ) ? intval( $_POST['ug_id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $id );
			if ( ! $usergroup ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			// reserve old record for hook.
			$old_record = clone $usergroup;

			$data = array();

			$name = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $name ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$usergroup->name = $name;

			$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
			$usergroup->description = $description;

			$members = isset( $_POST['wpsc-ug-members'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['wpsc-ug-members'] ) ) ) : array();
			if ( ! $members ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$supervisors = isset( $_POST['wpsc-ug-supervisors'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['wpsc-ug-supervisors'] ) ) ) : array();

			$ug_members     = array();
			$ug_supervisors = array();
			foreach ( $members as $member ) {

				$customer = WPSC_Customer::get_by_user_id( $member );
				$ug_members[] = $customer->id;
				if ( in_array( $member, $supervisors ) ) {
					$ug_supervisors[] = $customer->id;
				}
			}

			$usergroup->members     = $ug_members;
			$usergroup->supervisors = $ug_supervisors;

			$category            = isset( $_POST['wpsc-ug-category'] ) ? intval( $_POST['wpsc-ug-category'] ) : '';
			$usergroup->category = $category;

			foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

				if (
					! class_exists( $cf->type ) ||
					$cf->field != 'usergroup'
				) {
					continue;
				}

				$value = $cf->type::get_cf_input_val( $cf );

				if ( $cf->type::$slug == 'cf_textfield' ) {

					$limit = $cf->char_limit ? $cf->char_limit : 255;
					$value = substr( $value, 0, $limit );
				}

				if ( $cf->type::$has_multiple_val ) {

					$usergroup->{$cf->slug} = $value ? explode( '|', $value ) : array();

				} else {

					$usergroup->{$cf->slug} = $value;
				}
			}

			$usergroup->save();

			do_action( 'wpsc_set_edit_usergroup', $old_record, $usergroup );
			wp_die();
		}

		/**
		 * Clone existing usergroup.
		 *
		 * @return void
		 */
		public static function get_clone_usergroup() {

			if ( check_ajax_referer( 'wpsc_get_clone_usergroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $id );
			if ( ! $usergroup ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$usergroup->clone();

			wp_die();
		}

		/**
		 * Delete usergroup
		 *
		 * @return void
		 */
		public static function delete_usergroup() {

			if ( check_ajax_referer( 'wpsc_delete_usergroup', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $id );
			if ( ! $usergroup->id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$tickets = WPSC_Ticket::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'usergroups',
							'compare' => '=',
							'val'     => $id,
						),
					),
				)
			);
			$total_pages = $tickets['total_pages'] > 0 ? $tickets['total_pages'] : 1;

			?>
			<div class="wpsc-pg-container">
				<i class="wpsc-pg-title"><?php esc_attr_e( 'Updating tickets', 'wpsc-usergroup' ); ?></i>
				<div class="wpsc-pg">
					<div class="wpsc-pg-label">0%</div>
				</div>
			</div>
			<script>
				var progressbar = jQuery( ".wpsc-pg" );
				var progressLabel = jQuery( ".wpsc-pg-label" );
				var progressbarTitle = jQuery( ".wpsc-pg-title" );
				progressbar.progressbar({ value: 0 });
				var totalPages = <?php echo intval( $total_pages ); ?>;
				supportcandy.temp.ug_id = <?php echo intval( $id ); ?>;
				supportcandy.temp.wpsc_delete_ug_ticket_utility_nonce = '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_ug_ticket_utility' ) ); ?>';
				wpsc_runner();
				async function wpsc_runner() {
					for ( page=1; page<=totalPages; page++ ) {
						var success = await wpsc_delete_ug_ticket_utility();
						if ( success ) {
							let percentage = Math.round((page/totalPages)*100);
							progressLabel.text( percentage + '%' );
							progressbar.progressbar({ value: percentage });
						} else {
							window.location.reload();
						}
					}
					wpsc_ug_get_usergroups_settings();
				}
			</script>
			<?php

			wp_die();
		}

		/**
		 * Clear usergroup reference from all tickets before deleting it
		 *
		 * @return void
		 */
		public static function delete_usergroup_ticket_utility() {

			if ( check_ajax_referer( 'wpsc_delete_ug_ticket_utility', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized access!', 401 );
			}

			$id = isset( $_POST['ug_id'] ) ? intval( $_POST['ug_id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$usergroup = new WPSC_Usergroup( $id );
			if ( ! $usergroup->id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$tickets = WPSC_Ticket::find(
				array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'slug'    => 'usergroups',
							'compare' => '=',
							'val'     => $id,
						),
					),
				)
			);

			if ( $tickets['total_items'] ) {
				foreach ( $tickets['results'] as $ticket ) {
					$ticket->usergroups = array_filter(
						array_map(
							fn( $ug ) => $ug->id != $usergroup->id ? $ug : false,
							$ticket->usergroups
						)
					);
					$ticket->save();
				}
			}

			if ( ! $tickets['has_next_page'] ) {
				WPSC_Usergroup::destroy( $usergroup );
			}

			wp_send_json( array( 'nonce' => wp_create_nonce( 'wpsc_delete_ug_ticket_utility' ) ) );
		}

		/**
		 * Search WordPress users (exclude agents) for autocomplete purposes
		 *
		 * @return void
		 */
		public static function search_wp_users_exclude_agents() {

			if ( check_ajax_referer( 'wpsc_search_wp_users_exclude_agents', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			global $wpdb;

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

			// get list of agents userid.
			$args           = array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'slug'    => 'is_active',
						'compare' => '=',
						'val'     => 1,
					),
					array(
						'slug'    => 'is_agentgroup',
						'compare' => '=',
						'val'     => 0,
					),
				),
			);
			$agents         = WPSC_Agent::find( $args )['results'];
			$agent_user_ids = array_map( fn( $agent ) => $agent->user->ID, $agents );

			// search in wp user table.
			$users = ( new WP_User_Query(
				array(
					'search'         => '*' . esc_attr( $term ) . '*',
					'search_columns' => array(
						'user_login',
						'user_nicename',
						'user_email',
						'display_name',
					),
					'number'         => 10,
				)
			) )->get_results();

			$response = array();
			foreach ( $users as $user ) {

				if ( in_array( $user->ID, $agent_user_ids ) ) {
					continue;
				}

				$response[] = array(
					'id'    => $user->ID,
					'title' => $user->display_name,
				);
			}

			wp_send_json( $response );
		}
	}
endif;

WPSC_UG_Settings_CRUD::init();
