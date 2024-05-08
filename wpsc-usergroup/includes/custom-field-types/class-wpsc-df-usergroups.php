<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DF_Usergroups' ) ) :

	final class WPSC_DF_Usergroups {

		/**
		 * Slug for this custom field type
		 *
		 * @var string
		 */
		public static $slug = 'df_usergroups';

		/**
		 * Set whether this custom field type is of type date
		 *
		 * @var boolean
		 */
		public static $is_date = false;

		/**
		 * Set whether this custom field type has applicable to date range
		 *
		 * @var boolean
		 */
		public static $has_date_range = false;

		/**
		 * Set whether this custom field type has multiple values
		 *
		 * @var boolean
		 */
		public static $has_multiple_val = true;

		/**
		 * Data type for column created in tickets table
		 *
		 * @var string
		 */
		public static $data_type = 'TEXT NULL DEFAULT NULL';

		/**
		 * Set whether this custom field type has reference to other class
		 *
		 * @var boolean
		 */
		public static $has_ref = true;

		/**
		 * Reference class for this custom field type so that its value(s) return with object or array of objects automatically. Empty string indicate no reference.
		 *
		 * @var string
		 */
		public static $ref_class = 'wpsc_usergroup';

		/**
		 * Set whether this custom field field type is system default (no fields can be created from it).
		 *
		 * @var boolean
		 */
		public static $is_default = true;

		/**
		 * Set whether this field type has extra information that can be used in ticket form, edit custom fields, etc.
		 *
		 * @var boolean
		 */
		public static $has_extra_info = false;

		/**
		 * Set whether this custom field type can accept personal info.
		 *
		 * @var boolean
		 */
		public static $has_personal_info = false;

		/**
		 * Set whether fields created from this custom field type is allowed in create ticket form
		 *
		 * @var boolean
		 */
		public static $is_ctf = true;

		/**
		 * Set whether fields created from this custom field type is allowed in ticket list
		 *
		 * @var boolean
		 */
		public static $is_list = true;

		/**
		 * Set whether fields created from this custom field type is allowed in ticket filter
		 *
		 * @var boolean
		 */
		public static $is_filter = true;

		/**
		 * Set whether fields created from this custom field type can be given character limits
		 *
		 * @var boolean
		 */
		public static $has_char_limit = false;

		/**
		 * Set whether fields created from this custom field type has custom options set in options table
		 *
		 * @var boolean
		 */
		public static $has_options = false;

		/**
		 * Set whether fields created from this custom field type can be auto-filled
		 *
		 * @var boolean
		 */
		public static $is_auto_fill = false;

		/**
		 * Set whether fields created from this custom field type can be available for ticket list sorting
		 *
		 * @var boolean
		 */
		public static $is_sort = false;

		/**
		 * Set whether fields created from this custom field type can have placeholder
		 *
		 * @var boolean
		 */
		public static $is_placeholder = false;

		/**
		 * Set whether fields created from this custom field type is applicable for visibility conditions in create ticket form
		 *
		 * @var boolean
		 */
		public static $is_visibility_conditions = true;

		/**
		 * Set whether fields created from this custom field type is applicable for macros
		 *
		 * @var boolean
		 */
		public static $has_macro = true;

		/**
		 * Set whether fields of this custom field type is applicalbe for search on ticket list page.
		 *
		 * @var boolean
		 */
		public static $is_search = false;

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Get object of this class.
			add_filter( 'wpsc_load_ref_classes', array( __CLASS__, 'load_ref_class' ) );

			// visibility condition on usergroup.
			add_action( 'wpsc_print_tff', array( __CLASS__, 'add_hidden_tf_field' ) );

			// TFF!
			add_action( 'wpsc_js_validate_ticket_form', array( __CLASS__, 'js_validate_ticket_form' ) );
			add_action( 'wpsc_create_ticket_data', array( __CLASS__, 'set_create_ticket_data' ), 10, 3 );

			// Assign default usergroups if enabled.
			add_action( 'wpsc_create_new_ticket', array( __CLASS__, 'assign_default_usergroups' ), 1 );

			// Usergroup filter autocomplete.
			add_action( 'wp_ajax_wpsc_usergroup_autocomplete_filter', array( __CLASS__, 'usergroup_autocomplete_filter' ) );

			// disable usergroups in shedule tickets.
			add_filter( 'wpsc_st_ignore_cft', array( __CLASS__, 'disable_in_shedule_ticket' ) );
		}

		/**
		 * Load current class to reference classes
		 *
		 * @param array $classes - Associative array of class names indexed by its slug.
		 * @return array
		 */
		public static function load_ref_class( $classes ) {

			$classes[ self::$slug ] = array(
				'class'    => __CLASS__,
				'save-key' => 'id',
			);
			return $classes;
		}

		/**
		 * Print edit custom field properties
		 *
		 * @param WPSC_Custom_Fields $cf - custom field object.
		 * @param string             $field_class - class name of field category.
		 * @return void
		 */
		public static function get_edit_custom_field_properties( $cf, $field_class ) {

			if ( in_array( 'extra_info', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="textfield" data-required="false" class="wpsc-input-group extra-info">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Extra info', 'supportcandy' ); ?></label>
					</div>
					<input name="extra_info" type="text" value="<?php echo esc_attr( $cf->extra_info ); ?>" autocomplete="off" />
				</div>
				<?php
			endif;

			if ( in_array( 'tl_width', $field_class::$allowed_properties ) ) :
				?>
				<div data-type="number" data-required="false" class="wpsc-input-group tl_width">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Ticket list width (pixels)', 'supportcandy' ) ); ?>
						</label>
					</div>
					<input type="number" name="tl_width" value="<?php echo intval( $cf->tl_width ); ?>" autocomplete="off">
				</div>
				<?php
			endif;
		}

		/**
		 * Set custom field properties. Can be used by add/edit custom field.
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param string            $field_class - class of field category.
		 * @return void
		 */
		public static function set_cf_properties( $cf, $field_class ) {

			// extra info.
			if ( in_array( 'extra_info', $field_class::$allowed_properties ) ) {
				$cf->extra_info = isset( $_POST['extra_info'] ) ? sanitize_text_field( wp_unslash( $_POST['extra_info'] ) ) : ''; // phpcs:ignore
			}

			// tl_width!
			if ( in_array( 'tl_width', $field_class::$allowed_properties ) ) {
				$tl_width     = isset( $_POST['tl_width'] ) ? intval( $_POST['tl_width'] ) : 0; // phpcs:ignore
				$cf->tl_width = $tl_width ? $tl_width : 100;
			}

			// save!
			$cf->save();
		}

		/**
		 * Print operators for ticket form filter
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param array             $filter - Existing filters (if any).
		 * @return void
		 */
		public static function get_operators( $cf, $filter = array() ) {
			?>

			<div class="item conditional">
				<select class="operator" onchange="wpsc_tc_get_operand(this, '<?php echo esc_attr( $cf->slug ); ?>', '<?php echo esc_attr( wp_create_nonce( 'wpsc_tc_get_operand' ) ); ?>');">
					<option value=""><?php echo esc_attr( wpsc__( 'Compare As', 'supportcandy' ) ); ?></option>
					<option <?php isset( $filter['operator'] ) && selected( $filter['operator'], '=' ); ?> value="="><?php echo esc_attr( wpsc__( 'Equals', 'supportcandy' ) ); ?></option>
					<option <?php isset( $filter['operator'] ) && selected( $filter['operator'], 'IN' ); ?> value="IN"><?php echo esc_attr( wpsc__( 'Matches', 'supportcandy' ) ); ?></option>
					<option <?php isset( $filter['operator'] ) && selected( $filter['operator'], 'NOT IN' ); ?> value="NOT IN"><?php echo esc_attr( wpsc__( 'Not Matches', 'supportcandy' ) ); ?></option>
				</select>
			</div>
			<?php
		}

		/**
		 * Print operators for ticket form filter
		 *
		 * @param string            $operator - condition operator on which operands should be returned.
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param array             $filter - Exising functions (if any).
		 * @return void
		 */
		public static function get_operands( $operator, $cf, $filter = array() ) {

			$is_multiple = $operator !== '=' ? true : false;
			$usergroups  = WPSC_Usergroup::find( array( 'items_per_page' => 0 ) )['results'];
			$unique_id   = uniqid( 'wpsc_' );
			?>
			<div class="item conditional operand single">
				<select class="operand_val_1 <?php echo esc_attr( $unique_id ); ?>" <?php echo $is_multiple ? 'multiple' : ''; ?>>
					<?php

					if ( $is_multiple && isset( $filter['operand_val_1'] ) ) {

						foreach ( $filter['operand_val_1'] as $ug_id ) {
							if ( $ug_id == '0' ) {
								?>
								<option selected="selected" value="0"><?php echo esc_attr( wpsc__( 'None', 'supportcandy' ) ); ?></option>
								<?php
							} else {
								$usergroup = new WPSC_Usergroup( intval( $ug_id ) )
								?>
								<option selected="selected" value="<?php echo esc_attr( $usergroup->id ); ?>"><?php echo esc_attr( $usergroup->name ); ?></option>
								<?php
							}
						}
					}

					if ( ! $is_multiple && isset( $filter['operand_val_1'] ) ) :

						if ( $filter['operand_val_1'] == '0' ) {
							?>
							<option selected="selected" value="0"><?php echo esc_attr( wpsc__( 'None', 'supportcandy' ) ); ?></option>
							<?php
						} else {
							$usergroup = new WPSC_Usergroup( intval( $filter['operand_val_1'] ) )
							?>
							<option selected="selected" value="<?php echo esc_attr( $usergroup->id ); ?>"><?php echo esc_attr( $usergroup->name ); ?></option>
							<?php
						}
					endif;
					?>
				</select>
			</div>
			<script>
				jQuery('.operand_val_1.<?php echo esc_attr( $unique_id ); ?>').selectWoo({
					ajax: {
						url: supportcandy.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								q: params.term, // search term
								page: params.page,
								action: 'wpsc_usergroup_autocomplete_filter',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_usergroup_autocomplete_filter' ) ); ?>'
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
					minimumInputLength: 0
				});
			</script>
			<?php
		}

		/**
		 * Check ticket condition
		 *
		 * @param array             $condition - array with condition data.
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return boolean
		 */
		public static function is_valid_ticket_condition( $condition, $cf, $ticket ) {

			$flag = true;
			$usergroups = array_filter(
				array_map(
					fn( $group ) => $group->id,
					$ticket->usergroups
				)
			);

			switch ( $condition['operator'] ) {

				case '=':
					if ( $condition['operand_val_1'] == 0 ) {
						$flag = ! $usergroups ? true : false;
					} else {
						$flag = in_array( $condition['operand_val_1'], $usergroups ) ? true : false;
					}
					break;

				case 'IN':
					if ( ! $usergroups ) {
						$flag = in_array( '0', $condition['operand_val_1'] ) ? true : false;
					} else {

						$flag = false;
						foreach ( $usergroups as $id ) {
							if ( in_array( $id, $condition['operand_val_1'] ) ) {
								$flag = true;
								break;
							}
						}
					}
					break;

				case 'NOT IN':
					if ( ! $usergroups ) {
						$flag = ! in_array( '0', $condition['operand_val_1'] ) ? true : false;
					} else {

						foreach ( $usergroups as $id ) {
							if ( in_array( $id, $condition['operand_val_1'] ) ) {
								$flag = false;
								break;
							}
						}
					}
					break;

				default:
					$flag = true;
			}

			return $flag;
		}

		/**
		 * Return val field for meta query of this type of custom field
		 *
		 * @param array $condition - condition data.
		 * @return mixed
		 */
		public static function get_meta_value( $condition ) {

			$operator = $condition['operator'];
			switch ( $operator ) {

				case '=':
				case 'IN':
				case 'NOT IN':
					return $condition['operand_val_1'];
			}
			return false;
		}

		/**
		 * Parse filter and return sql query to be merged in ticket model query builder
		 *
		 * @param WPSC_Custom_Field $cf - custom field of this type.
		 * @param mixed             $compare - comparison operator.
		 * @param mixed             $val - value to compare.
		 * @return string
		 */
		public static function parse_filter( $cf, $compare, $val ) {

			$str = '';

			switch ( $compare ) {

				case '=':
					if ( $val == '' || $val == '0' ) {
						$val = '^$';
					}
					$str = 't.' . $cf->slug . ' RLIKE \'(^|[|])' . esc_sql( $val ) . '($|[|])\'';
					break;

				case 'IN':
					if ( in_array( '', $val ) || in_array( '0', $val ) ) {
						$str = '( t.' . $cf->slug . ' IS NULL OR t.' . $cf->slug . '=\'\' )';
					} else {
						$str = 't.' . $cf->slug . ' RLIKE \'(^|[|])(' . implode( '|', esc_sql( $val ) ) . ')($|[|])\'';
					}
					break;

				case 'NOT IN':
					if ( in_array( '', $val ) || in_array( '0', $val ) ) {
						$str = '( t.' . $cf->slug . ' IS NOT NULL OR t.' . $cf->slug . '<>\'\' )';
					} else {
						$str = 't.' . $cf->slug . ' NOT RLIKE \'(^|[|])(' . implode( '|', esc_sql( $val ) ) . ')($|[|])\'';
					}
					break;

				default:
					$str = '1=1';
			}

			return $str;
		}

		/**
		 * Return slug string to be used in where condition of ticket model for this type of field
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @return string
		 */
		public static function get_sql_slug( $cf ) {

			return 't.usergroups';
		}

		/**
		 * Return custom field value in $_POST
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param string $slug - Custom field slug.
		 * @param mixed  $cf - Custom field object or false.
		 * @return mixed
		 */
		public static function get_tff_value( $slug, $cf = false ) {

			$value = isset( $_POST[ $slug ] ) ? sanitize_text_field( wp_unslash( $_POST[ $slug ] ) ) : ''; // phpcs:ignore
			return $value ? array_filter( array_map( 'intval', explode( '|', $value ) ) ) : array();
		}

		/**
		 * Check condition for this type
		 *
		 * @param array             $condition - condition data.
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param mixed             $options - value to compare.
		 * @return boolean
		 */
		public static function is_valid( $condition, $cf, $options ) {

			$response = false;
			switch ( $condition['operator'] ) {

				case '=':
					if ( $condition['operand_val_1'] == 0 ) {
						$response = ! $options ? true : false;
					} else {
						$response = in_array( $condition['operand_val_1'], $options );
					}
					break;

				case 'IN':
					$flag = false;
					foreach ( $condition['operand_val_1'] as $option ) {

						if ( ( $option == 0 && ! $options ) ||
							in_array( $option, $options )
						) {
							$flag = true;
							break;
						}
					}
					$response = $flag;
					break;

				case 'NOT IN':
					$flag = true;
					foreach ( $condition['operand_val_1'] as $option ) {

						if ( ( $option == 0 && ! $options ) ||
							in_array( $option, $options )
						) {
							$flag = false;
							break;
						}
					}
					$response = $flag;
					break;
			}
			return $response;
		}

		/**
		 * Print ticket form field
		 *
		 * @param WPSC_Custom_Field $cf - Custom field object.
		 * @param array             $tff - Array of ticket form field settings for this field.
		 * @return string
		 */
		public static function print_tff( $cf, $tff ) {

			$current_user = WPSC_Current_User::$current_user;
			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			$unique_id = uniqid( 'wpsc_' );
			$usergroups = WPSC_Usergroup::get_by_customer( $current_user->customer );
			$auto_fill = true;
			if ( ! ( $current_user->is_agent || $current_user->is_guest ) ) {
				if ( ! $ug_settings['auto-assign'] && $ug_settings['allow-customers-to-modify'] && ! $ug_settings['auto-fill'] ) {
					$auto_fill = false;
				}
			}
			$is_hidden = false;
			if ( $ug_settings['auto-assign'] || ! ( $ug_settings['allow-customers-to-modify'] && $usergroups ) ) {
				$is_hidden = true;
			}
			$classes = WPSC_Functions::get_tff_classes( $cf, $tff );
			$classes = strtr(
				$classes,
				array(
					'wpsc-visible' => '',
					'wpsc-hidden'  => '',
				)
			);
			$classes .= $is_hidden ? 'wpsc-hidden' : 'wpsc-visible';
			ob_start();
			?>
			<div class="<?php echo esc_attr( $classes ); ?>" data-cft="<?php echo esc_attr( self::$slug ); ?>">

				<div class="wpsc-tff-label">
					<span class="name"><?php echo esc_attr( $cf->name ); ?></span>

					<?php
					if ( $tff['is-required'] ) {
						?>
						<span class="required-indicator">*</span>
						<?php
					}
					?>

				</div>

				<span class="extra-info"><?php echo esc_attr( $cf->extra_info ); ?></span>

				<select class="<?php echo esc_attr( $unique_id ); ?>" onchange="wpsc_change_usergroups(this);" multiple>
					<?php
					if ( $is_hidden === false ) {
						foreach ( $usergroups as $usergroup ) {
							$selected = $auto_fill ? 'selected' : '';
							?>
							<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $usergroup->id ); ?>"><?php echo esc_attr( $usergroup->name ); ?></option>
							<?php
						}
					}
					?>
				</select>
				<script>jQuery('select.<?php echo esc_attr( $unique_id ); ?>').selectWoo();</script>

			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Add hidden field for current user usergroup
		 *
		 * @return void
		 */
		public static function add_hidden_tf_field() {

			$ids = '';
			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent || $current_user->is_guest ) ) {
				if ( ! ( ! $ug_settings['auto-assign'] && $ug_settings['allow-customers-to-modify'] && ! $ug_settings['auto-fill'] ) ) {
					$ids = implode(
						'|',
						array_filter(
							array_map(
								fn( $usergroup ) => $usergroup->id,
								WPSC_Usergroup::get_by_customer( $current_user->customer )
							)
						)
					);
				}
			}
			?>
			<input type="hidden" name="usergroups" id="wpsc-usergroups" value="<?php echo esc_attr( $ids ); ?>">
			<?php
		}

		/**
		 * Validate this type field in create ticket
		 *
		 * @return void
		 */
		public static function js_validate_ticket_form() {
			?>
			case '<?php echo esc_attr( self::$slug ); ?>':
				var val = customField.find('select').first().val();
				if ( customField.hasClass('required') && val.length === 0 ) {
					isValid = false;
					alert(supportcandy.translations.req_fields_missing);
				}
				break;
			<?php
			echo PHP_EOL;
		}

		/**
		 * Returns printable ticket value for custom field. Can be used in export tickets, replace macros etc.
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @param string            $module - module name.
		 * @return string
		 */
		public static function get_ticket_field_val( $cf, $ticket, $module = '' ) {

			$usergroups = array_filter(
				array_map(
					fn( $usergroup ) => $usergroup->id ? $usergroup->name : '',
					$ticket->usergroups
				)
			);
			$value = $usergroups ? implode( ', ', $usergroups ) : esc_attr__( 'None', 'supportcandy' );
			return apply_filters( 'wpsc_ticket_field_val_usergroups', $value, $cf, $ticket, $module );
		}

		/**
		 * Print customer value for given custom field on ticket list
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param WPSC_Ticket       $ticket - ticket object.
		 * @return void
		 */
		public static function print_tl_ticket_field_val( $cf, $ticket ) {

			echo esc_attr( self::get_ticket_field_val( $cf, $ticket ) );
		}

		/**
		 * Check and return custom field value for new ticket to be created.
		 * This function is used by filter for set create ticket form and called directly by my-profile for each applicable custom fields.
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param array   $data - Array of values to to stored in ticket in an insert function.
		 * @param array   $custom_fields - Array containing all applicable custom fields indexed by unique custom field types.
		 * @param boolean $is_my_profile - Whether it or not it is created from my-profile. This function is used by create ticket as well as my-profile. Due to customer fields handling is done same way, this flag gives apportunity to identify where it being called.
		 * @return array
		 */
		public static function set_create_ticket_data( $data, $custom_fields, $is_my_profile ) {

			if ( $is_my_profile ) {
				return;
			}

			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			if ( $ug_settings['auto-assign'] || ! $ug_settings['allow-customers-to-modify'] ) {
				return $data;
			}

			$value = self::get_tff_value( 'usergroups' );
			$data['usergroups'] = $value ? implode( '|', $value ) : '';
			return $data;
		}

		/**
		 * Assign default usergroups if enabled
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function assign_default_usergroups( $ticket ) {

			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			if ( $ticket->usergroups || ! $ug_settings['auto-assign'] ) {
				return;
			}

			$usergroups = WPSC_Usergroup::get_by_customer( $ticket->customer );
			if ( ! $usergroups ) {
				return;
			}

			$ticket->usergroups = $usergroups;
			$ticket->save();
		}

		/**
		 * Customer autocomplete callback
		 */
		public static function usergroup_autocomplete_filter() {

			if ( check_ajax_referer( 'wpsc_usergroup_autocomplete_filter', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_agent ) {
				$filter_items = get_option( 'wpsc-atl-filter-items', array() );
			} elseif ( $current_user->is_customer && ! $current_user->is_agent ) {
				$filter_items = get_option( 'wpsc-ctl-filter-items', array() );
			}

			if ( ! ( in_array( 'usergroups', $filter_items ) || WPSC_Functions::is_site_admin() ) ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$response = WPSC_Usergroup::usergroup_autocomplete( $term );
			wp_send_json( $response );
		}

		/**
		 * Disable usergroups in schedule tickets
		 *
		 * @param array $ignore_cft - ignore custom field types.
		 * @return array
		 */
		public static function disable_in_shedule_ticket( $ignore_cft ) {

			$ignore_cft[] = self::$slug;
			return $ignore_cft;
		}

		/**
		 * Print given value for custom field
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param mixed             $val - value to convert and print.
		 * @return void
		 */
		public static function print_val( $cf, $val ) {

			$val = is_array( $val ) ? $val : array_filter( explode( '|', $val ) );
			$usergroups = array_filter(
				array_map(
					function ( $usergroup ) {
						if ( is_object( $usergroup ) ) {
							$usergroup->id = isset( $usergroup->id ) ? $usergroup->id : '';
							return $usergroup->id ? $usergroup : '';
						} elseif ( $usergroup ) {
							$usergroup = new WPSC_Usergroup( $usergroup );
							$usergroup->id = isset( $usergroup->id ) ? $usergroup->id : '';
							return $usergroup->id ? $usergroup : '';
						} else {
							return '';
						}
					},
					$val
				)
			);
			$usergroup_names = array_filter(
				array_map(
					fn( $usergroup ) => $usergroup->id ? $usergroup->name : '',
					$usergroups
				)
			);
			echo $usergroup_names ? esc_attr( implode( ', ', $usergroup_names ) ) : esc_attr__( 'None', 'supportcandy' );
		}


		/**
		 * Return printable value for history log macro
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param mixed             $val - value to convert and return.
		 * @return string
		 */
		public static function get_history_log_val( $cf, $val ) {

			ob_start();
			self::print_val( $cf, $val );
			return ob_get_clean();
		}

		/**
		 * Return given value for custom field
		 *
		 * @param WPSC_Custom_Field $cf - custom field object.
		 * @param mixed             $val - value to convert and print.
		 * @return string
		 */
		public static function get_field_value( $cf, $val ) {

			$val = is_array( $val ) ? $val : array_filter( explode( '|', $val ) );
			$usergroups = array_filter(
				array_map(
					function ( $usergroup ) {
						if ( is_object( $usergroup ) ) {
							$usergroup->id = isset( $usergroup->id ) ? $usergroup->id : '';
							return $usergroup->id ? $usergroup : '';
						} elseif ( $usergroup ) {
							$usergroup = new WPSC_Usergroup( $usergroup );
							$usergroup->id = isset( $usergroup->id ) ? $usergroup->id : '';
							return $usergroup->id ? $usergroup : '';
						} else {
							return '';
						}
					},
					$val
				)
			);
			$usergroup_names = array_filter(
				array_map(
					fn( $usergroup ) => $usergroup->id ? $usergroup->name : '',
					$usergroups
				)
			);
			return $usergroup_names ? esc_attr( implode( ', ', $usergroup_names ) ) : esc_attr__( 'None', 'supportcandy' );
		}
	}
endif;

WPSC_DF_Usergroups::init();
