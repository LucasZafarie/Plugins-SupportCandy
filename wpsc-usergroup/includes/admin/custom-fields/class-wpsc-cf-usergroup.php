<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_CF_USERGROUP' ) ) :

	final class WPSC_CF_USERGROUP {

		/**
		 * Allowed custom field properties
		 *
		 * @var array
		 */
		public static $allowed_properties = array(
			'extra_info',
			'placeholder_text',
			'char_limit',
			'date_format',
			'date_range',
			'start_range',
			'end_range',
			'time_format',
		);

		/**
		 * Initialize this class
		 */
		public static function init() {

			// Register this field category (ticket).
			add_filter( 'wpsc_custom_field_categories', array( __CLASS__, 'register_field' ) );

			// Add setting menu item.
			add_filter( 'wpsc_ticket_form_page_sections', array( __CLASS__, 'add_submenu' ) );

			// List.
			add_action( 'wp_ajax_wpsc_get_usergroup_fields', array( __CLASS__, 'get_usergroup_fields' ) );

			// Filter custom field types for new custom field.
			add_filter( 'wpsc_add_new_custom_field_cf_types', array( __CLASS__, 'filter_cf_types' ), 10, 2 );

			// create table column for ug fields.
			add_filter( 'wpsc_add_cf_table_column', array( __CLASS__, 'add_ug_cf_column' ), 10, 2 );

			// delete column after cf is deleted.
			add_action( 'wpsc_custom_field_before_destroy', array( __CLASS__, 'delete_ug_cf_column' ) );
		}

		/**
		 * Register field category
		 *
		 * @param array $fields - field name.
		 * @return array
		 */
		public static function register_field( $fields ) {

			$fields['usergroup'] = __CLASS__;
			return $fields;
		}

		/**
		 * Add usergroup menu in custom fields
		 *
		 * @param array $menu - menu name.
		 * @return array
		 */
		public static function add_submenu( $menu ) {

			$menu['usergroup-fields'] = array(
				'slug'     => 'usergroup_fields',
				'icon'     => 'users',
				'label'    => esc_attr__( 'Usergroup Fields', 'wpsc-usergroup' ),
				'callback' => 'wpsc_get_usergroup_fields',
			);

			return $menu;
		}

		/**
		 * Get usergroup fields
		 *
		 * @return void
		 */
		public static function get_usergroup_fields() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}?>

			<div class="wpsc-setting-header">
				<h2><?php esc_attr_e( 'Usergroup Fields', 'wpsc-usergroup' ); ?></h2>
			</div>
			<div class="wpsc-setting-section-body">
				<table class="ticket-fields wpsc-setting-tbl">
					<thead>
						<tr>
							<th><?php echo esc_attr( wpsc__( 'Field', 'supportcandy' ) ); ?></th>
							<th><?php echo esc_attr( wpsc__( 'Extra info', 'supportcandy' ) ); ?></th>
							<th><?php echo esc_attr( wpsc__( 'Actions', 'supportcandy' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {
							if ( $cf->field != 'usergroup' ) {
								continue;
							}
							?>
							<tr>
								<td><?php echo esc_attr( $cf->name ); ?></td>
								<td><?php echo esc_attr( $cf->extra_info ); ?></td>
								<td>
									<a href="javascript:wpsc_get_edit_custom_field(<?php echo esc_attr( $cf->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_edit_custom_field' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></a>
									<?php
									if ( ! $cf->type::$is_default ) {
										echo esc_attr( ' | ' );
										?>
										<a href="javascript:wpsc_delete_custom_field(<?php echo esc_attr( $cf->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_custom_field' ) ); ?>');" class="wpsc-link"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></a>
										<?php
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
					jQuery('table.ticket-fields').DataTable({
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
									wpsc_get_add_new_custom_field('usergroup', '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_add_new_custom_field' ) ); ?>');
								}
							}
						],
						language: supportcandy.translations.datatables
					});
				</script>
			</div>
			<?php
			wp_die();
		}

		/**
		 * Filter out unwanted custom field types from add new custom field section for customer fields
		 *
		 * @param array  $cf_types - custome field type array.
		 * @param string $field - custom field category (ticket, agentonly, customer, etc.).
		 * @return array
		 */
		public static function filter_cf_types( $cf_types, $field ) {

			if ( $field == 'usergroup' ) {
				$exclude_cf = array( 'cf_html', 'cf_file_attachment_single', 'cf_file_attachment_multiple', 'cf_woo_product', 'cf_woo_order', 'cf_edd_product', 'cf_edd_order', 'cf_tutor_lms', 'cf_tutor_order', 'cf_learnpress_lms', 'cf_learnpress_order', 'cf_lifter_lms', 'cf_lifter_order' );
				foreach ( $exclude_cf as $key ) {
					if ( in_array( $key, array_keys( $cf_types ) ) ) {
						unset( $cf_types[ $key ] );
					}
				}
			}

			return $cf_types;
		}

		/**
		 * Add column to usergroup table after usergroup custom field added
		 *
		 * @param boolean           $success - boolean value.
		 * @param WPSC_Custom_Field $cf - custome filed type.
		 * @return boolean
		 */
		public static function add_ug_cf_column( $success, $cf ) {

			global $wpdb;
			if ( $cf->field == 'usergroup' ) {
				$success = $wpdb->query( "ALTER TABLE {$wpdb->prefix}psmsc_usergroups ADD {$cf->slug} {$cf->type::$data_type}" );
			}
			return $success;
		}

		/**
		 * Delele column from usergroup table after usergroup custom field deleted
		 *
		 * @param WPSC_Custom_Field $cf - custome field.
		 * @return void
		 */
		public static function delete_ug_cf_column( $cf ) {

			global $wpdb;
			if ( $cf->field == 'usergroup' ) :
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}psmsc_usergroups DROP {$cf->slug}" );
			endif;
		}
	}
endif;

WPSC_CF_USERGROUP::init();
