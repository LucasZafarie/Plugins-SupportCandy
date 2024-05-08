<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_EMT_Actions' ) ) :

	final class WPSC_EMT_Actions {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Add schema to customer class.
			add_filter( 'wpsc_customer_schema', array( __CLASS__, 'apply_schema' ) );

			// print field on registration form.
			add_action( 'wpsc_registration_form', array( __CLASS__, 'print_register_field' ) );
			add_filter( 'wpsc_register_user_data', array( __CLASS__, 'register_user_data' ) );
			add_action( 'wpsc_after_user_registration', array( __CLASS__, 'after_user_registration' ), 10, 2 );

			// print field on registration form.
			add_action( 'wpsc_print_tff', array( __CLASS__, 'print_tff' ) );
			add_action( 'wpsc_create_new_ticket', array( __CLASS__, 'after_ticket_create' ) );
			add_action( 'wpsc_js_create_ticket_formdata', array( __CLASS__, 'js_create_ticket_formdata' ) );

			// handle create as change event.
			add_action( 'wpsc_js_after_change_create_as', array( __CLASS__, 'toggel_subscribe_option_js' ) );
			add_action( 'wp_ajax_wpsc_emt_toggel_subscribe_option', array( __CLASS__, 'check_customer_status' ) );
			add_action( 'wp_ajax_nopriv_wpsc_emt_toggel_subscribe_option', array( __CLASS__, 'check_customer_status' ) );
		}

		/**
		 * Apply schema for customer model
		 *
		 * @param array $schema - schema.
		 * @return array
		 */
		public static function apply_schema( $schema ) {

			$schema['subscribed'] = array(
				'has_ref'          => false,
				'ref_class'        => '',
				'has_multiple_val' => false,
			);
			return $schema;
		}

		/**
		 * Print newsletter checkbox.
		 *
		 * @return void
		 */
		public static function print_register_field() {

			$general = get_option( 'wpsc-emt-general-settings' );
			if ( in_array( 'registration', $general['subscribe-form'] ) ) {
				?>
				<div class="" style="margin-bottom: 5px;">
					<input name="wpsc-newsletter" type="checkbox" value="1"/>
					<label for="wpsc-newsletter"><?php esc_attr_e( 'Subscribe to the newsletter', 'wpsc-emt' ); ?></label>
				</div>
				<?php
			}
		}

		/**
		 * Get newsletter data
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param array $data - user registration data.
		 * @return array
		 */
		public static function register_user_data( $data ) {

			$data['newsletter'] = isset( $_POST['wpsc-newsletter'] ) ? intval( $_POST['wpsc-newsletter'] ) : 0; // phpcs:ignore
			return $data;
		}

		/**
		 * Add user to list after user registration
		 *
		 * @param WP_User $user - user object.
		 * @param array   $data - user data.
		 * @return bool
		 */
		public static function after_user_registration( $user, $data ) {

			$general = get_option( 'wpsc-emt-general-settings' );

			if ( ! $data->newsletter ) {
				return;
			}

			$user_data = array(
				'email_address' => $user->user_email,
				'first_name'    => $user->first_name,
				'last_name'     => $user->last_name,
			);
			if ( $general['connection'] == 'mailchimp' ) {

				WPSC_EMT_Settings_Mailchimp::add_subscriber_to_mailchimp( $user_data );
			}
			if ( $general['connection'] == 'sendinblue' ) {

				WPSC_EMT_Settings_SendinBlue::add_subscriber_to_sendinblue( $user_data );
			}
			if ( $general['connection'] == 'getresponse' ) {

				WPSC_EMT_Settings_GetResponse::add_subscriber_to_getresponse( $user_data );
			}
		}

		/**
		 * Add user to  list after creating a ticket
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function after_ticket_create( $ticket ) {

			$newsletter = isset( $_POST['newsletter'] ) ? intval( $_POST['newsletter'] ) : 0; // phpcs:ignore
			if ( ! $newsletter ) {
				return;
			}

			$general = get_option( 'wpsc-emt-general-settings' );

			$name = explode( ' ', $ticket->customer->name );
			$first_name = isset( $name[0] ) ? $name[0] : '';
			$last_name = isset( $name[1] ) ? $name[1] : '';

			$user_data = array(
				'email_address' => $ticket->customer->email,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
			);

			if ( $general['connection'] == 'mailchimp' ) {

				WPSC_EMT_Settings_Mailchimp::add_subscriber_to_mailchimp( $user_data );
			}
			if ( $general['connection'] == 'sendinblue' ) {

				WPSC_EMT_Settings_SendinBlue::add_subscriber_to_sendinblue( $user_data );
			}
			if ( $general['connection'] == 'getresponse' ) {

				WPSC_EMT_Settings_GetResponse::add_subscriber_to_getresponse( $user_data );
			}
		}

		/**
		 * Print ticket form field
		 *
		 * @return void
		 */
		public static function print_tff() {

			$current_user = WPSC_Current_User::$current_user;
			$general = get_option( 'wpsc-emt-general-settings' );
			if ( ( ! $current_user->customer->subscribed ) && in_array( 'create-ticket', $general['subscribe-form'] ) ) {
				?>
				<div class="wpsc-tff wpsc-suscribe-mail wpsc-xs-12 wpsc-sm-12 wpsc-md-12 wpsc-lg-12 wpsc-visible" data-cft="emt">
					<div class="checkbox-container">
						<input id="wpsc-newsletter" type="checkbox"/>
						<label for="wpsc-newsletter"><?php esc_attr_e( 'Subscribe to the newsletter', 'wpsc-emt' ); ?></label>
					</div>
				</div>
				<?php
			}
		}

		/**
		 * Submit ticket FormData append
		 *
		 * @return void
		 */
		public static function js_create_ticket_formdata() {
			?>
			newsletter = 0;
			if ( jQuery('#wpsc-newsletter').is(":checked") ){
				newsletter = 1;
			}
			dataform.append('newsletter', newsletter );
			<?php
		}

		/**
		 * Toggle subscribe option
		 *
		 * @return void
		 */
		public static function toggel_subscribe_option_js() {
			echo 'wpsc_emt_toggel_subscribe_option();' . PHP_EOL;
		}

		/**
		 * Check new customer subscriber status
		 *
		 * @return void
		 */
		public static function check_customer_status() {

			if ( check_ajax_referer( 'general', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$id = isset( $_POST['cust_id'] ) ? intval( $_POST['cust_id'] ) : 0;
			if ( ! $id ) {
				wp_die();
			}

			$customer = new WPSC_Customer( $id );
			if ( ! $customer ) {
				wp_die();
			}

			$flag = $customer->subscribed ? 1 : 0;

			wp_send_json( array( 'subscribed' => $flag ) );
		}
	}
endif;

WPSC_EMT_Actions::init();
