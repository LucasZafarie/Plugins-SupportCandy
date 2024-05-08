<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_EMT_Settings_General' ) ) :

	final class WPSC_EMT_Settings_General {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// setting actions.
			add_action( 'wp_ajax_wpsc_emt_get_general_settings', array( __CLASS__, 'get_general_settings' ) );
			add_action( 'wp_ajax_wpsc_emt_set_general_settings', array( __CLASS__, 'set_general_settings' ) );
			add_action( 'wp_ajax_wpsc_emt_reset_general_settings', array( __CLASS__, 'reset_general_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$settings = array(
				'connection'     => '',
				'subscribe-form' => array( 'registration', 'create-ticket' ),
			);
			update_option( 'wpsc-emt-general-settings', $settings );
		}

		/**
		 * Get general settings
		 *
		 * @return void
		 */
		public static function get_general_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}
			$general = get_option( 'wpsc-emt-general-settings' );
			?>
			<form action="#" onsubmit="return false;" class="wpsc-emt-general-settings">
				<div class="wpsc-dock-container">
					<?php
					printf(
						/* translators: Click here to see the documentation */
						esc_attr__( '%s to see the documentation!', 'supportcandy' ),
						'<a href="https://supportcandy.net/docs/email-marketing-tool-integration/" target="_blank">' . esc_attr__( 'Click here', 'supportcandy' ) . '</a>'
					);
					?>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Connection', 'supportcandy' ); ?></label>
					</div>
					<select class="wpsc-emt-options" name="connection">
						<option value=""></option>
						<option <?php isset( $general['connection'] ) && selected( $general['connection'], 'mailchimp' ); ?> value="mailchimp"><?php esc_attr_e( 'Mailchimp', 'wpsc-emt' ); ?></option>
						<option <?php isset( $general['connection'] ) && selected( $general['connection'], 'getresponse' ); ?> value="getresponse"><?php esc_attr_e( 'GetResponse', 'wpsc-emt' ); ?></option>
						<option <?php isset( $general['connection'] ) && selected( $general['connection'], 'sendinblue' ); ?> value="sendinblue"><?php esc_attr_e( 'Brevo - SendinBlue', 'wpsc-emt' ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Subscribe option', 'wpsc-emt' ); ?></label>
						</div>
						<select id="subscribe-form" multiple name="subscribe-form[]">
							<?php
							$selected = isset( $general['subscribe-form'] ) && in_array( 'registration', $general['subscribe-form'] ) ? 'selected' : '';
							?>
							<option <?php echo esc_attr( $selected ); ?> value="registration"><?php echo esc_attr__( 'Registation', 'wpsc-emt' ); ?></option>
							<?php
							$selected = isset( $general['subscribe-form'] ) && in_array( 'create-ticket', $general['subscribe-form'] ) ? 'selected' : '';
							?>
							<option <?php echo esc_attr( $selected ); ?> value="create-ticket"><?php echo esc_attr__( 'Create Ticket', 'wpsc-emt' ); ?></option>
						</select>
						<script>jQuery('#subscribe-form').selectWoo();</script>
					</div>

				<?php do_action( 'wpsc_emt_get_general_settings' ); ?>

				<input type="hidden" name="action" value="wpsc_emt_set_general_settings">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_emt_set_general_settings' ) ); ?>">
			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_emt_set_general_settings(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_emt_reset_general_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_emt_reset_general_settings' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Set general settings
		 *
		 * @return void
		 */
		public static function set_general_settings() {

			if ( check_ajax_referer( 'wpsc_emt_set_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$setting = apply_filters(
				'wpsc_emt_set_general_settings',
				array(
					'connection'     => isset( $_POST['connection'] ) ? sanitize_text_field( wp_unslash( $_POST['connection'] ) ) : '',
					'subscribe-form' => isset( $_POST['subscribe-form'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['subscribe-form'] ) ) ) : array( 'registration' ),
				)
			);
			update_option( 'wpsc-emt-general-settings', $setting );
			wp_die();
		}

		/**
		 * Reset general settings
		 *
		 * @return void
		 */
		public static function reset_general_settings() {

			if ( check_ajax_referer( 'wpsc_emt_reset_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			self::reset();

			do_action( 'wpsc_emt_reset_general_settings' );

			wp_die();
		}
	}
endif;

WPSC_EMT_Settings_General::init();
