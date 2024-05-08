<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Create_Ticket_Form' ) ) :

	final class WPSC_UG_Create_Ticket_Form {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// default category.
			add_filter( 'wpsc_default_value_category', array( __CLASS__, 'default_value_category' ), 10, 2 );

			// category status in create form.
			add_filter( 'wpsc_category_field_disable', array( __CLASS__, 'category_field_disable' ), 10, 3 );
			add_action( 'wpsc_after_print_tff_category', array( __CLASS__, 'after_print_tff_category' ), 10, 2 );

			// visibiity conditions on usergroups.
			add_action( 'wpsc_js_after_change_create_as', array( __CLASS__, 'get_create_as_usergroups' ) );
			add_action( 'wp_ajax_wpsc_get_create_as_usergroups', array( __CLASS__, 'wpsc_get_create_as_usergroups' ) );
		}

		/**
		 * Set default category
		 *
		 * @param int               $default_value - default value.
		 * @param WPSC_Custom_Field $cf - custom field.
		 * @return string
		 */
		public static function default_value_category( $default_value, $cf ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! $current_user->is_customer || $current_user->is_agent ) {
				return $default_value;
			}

			$usergroups = WPSC_Usergroup::get_by_customer( $current_user->customer );
			if ( ! $usergroups ) {
				return $default_value;
			}

			$categories = array_filter(
				array_map(
					fn( $usergroup ) => $usergroup->category ? $usergroup->category->id : false,
					$usergroups
				)
			);

			return $categories ? $categories[0] : $default_value;
		}

		/**
		 * Enable or disable category field
		 *
		 * @param string            $is_disabled - check is disable.
		 * @param WPSC_Custom_Field $cf - custom field.
		 * @param array             $tff - ticket form field.
		 * @return bool
		 */
		public static function category_field_disable( $is_disabled, $cf, $tff ) {

			$current_user = WPSC_Current_User::$current_user;

			if ( ! ( $current_user->is_agent || $current_user->is_guest ) ) {

				$categories = array_filter(
					array_map(
						fn( $usergroup ) => $usergroup->category ? $usergroup->category : false,
						WPSC_Usergroup::get_by_customer( $current_user->customer )
					)
				);

				if ( $categories ) {

					$general     = get_option( 'wpsc-ug-general-settings' );
					$is_disabled = ! $general['allow-change-category'] ? true : $is_disabled;
				}
			}

			return $is_disabled;
		}

		/**
		 * Add a hidden field for disabled category field
		 *
		 * @param string            $val - value.
		 * @param WPSC_Custom_Field $cf - custom field.
		 * @return void
		 */
		public static function after_print_tff_category( $val, $cf ) {

			$current_user = WPSC_Current_User::$current_user;

			if ( ! ( $current_user->is_agent || $current_user->is_guest ) ) {

				$categories = array_filter(
					array_map(
						fn( $usergroup ) => $usergroup->category ? $usergroup->category : false,
						WPSC_Usergroup::get_by_customer( $current_user->customer )
					)
				);

				if ( $categories ) {

					$general = get_option( 'wpsc-ug-general-settings' );
					if ( $general['allow-change-category'] == 0 ) {
						?>
						<input type="hidden" name="<?php echo esc_attr( $cf->slug ); ?>" value="<?php echo esc_attr( $val ); ?>">
						<?php
					}
				}
			}
		}

		/**
		 *  Change customer usergroups while creating ticket create-as
		 *
		 * @return void
		 */
		public static function get_create_as_usergroups() {
			$nonce = esc_attr( wp_create_nonce( 'wpsc_change_customer_usergroups' ) );
			echo 'wpsc_change_customer_usergroups( "' . esc_attr( $nonce ) . '" );' . PHP_EOL;
		}

		/**
		 * Find customer usergroups while creating ticket create-as
		 *
		 * @return void
		 */
		public static function wpsc_get_create_as_usergroups() {

			if ( check_ajax_referer( 'wpsc_change_customer_usergroups', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'create-as' ) ) ) {
				wp_send_json_error( new WP_Error( '001', 'Unauthorized!' ), 400 );
			}

			$email = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
			if ( ! $email ) {
				wp_send_json_error( new WP_Error( '002', 'Something went wrong!' ), 400 );
			}

			$ids = '';
			$ug_settings = get_option( 'wpsc-ug-general-settings' );
			$current_user = WPSC_Current_User::change_current_user( $email );
			$usergroups = WPSC_Usergroup::get_by_customer( $current_user->customer );
			if ( ! ( $current_user->is_agent || $current_user->is_guest ) ) {
				if ( ! ( ! $ug_settings['auto-assign'] && $ug_settings['allow-customers-to-modify'] && ! $ug_settings['auto-fill'] ) ) {
					$ids = implode(
						'|',
						array_filter(
							array_map(
								fn( $usergroup ) => $usergroup->id,
								$usergroups
							)
						)
					);
				}
			}

			$usergroup_options = array();
			foreach ( $usergroups as $usergroup ) {
				$usergroup_options[] = array(
					'index' => $usergroup->id,
					'value' => $usergroup->name,
				);
			}

			$response = array(
				'auto_assign'           => $ug_settings['auto-assign'],
				'allow_customer_modify' => $ug_settings['allow-customers-to-modify'],
				'has_usergroups'        => $usergroups ? true : false,
				'auto_fill'             => $ids,
				'options'               => $usergroup_options,
			);

			wp_send_json( $response );
		}
	}
endif;

WPSC_UG_Create_Ticket_Form::init();
