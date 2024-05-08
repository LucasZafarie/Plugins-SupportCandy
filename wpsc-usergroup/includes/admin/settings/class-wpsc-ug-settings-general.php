<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Settings_General' ) ) :

	final class WPSC_UG_Settings_General {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// setting actions.
			add_action( 'wp_ajax_wpsc_ug_get_general_settings', array( __CLASS__, 'get_general_settings' ) );
			add_action( 'wp_ajax_wpsc_ug_set_general_settings', array( __CLASS__, 'set_general_settings' ) );
			add_action( 'wp_ajax_wpsc_ug_reset_general_settings', array( __CLASS__, 'reset_general_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			update_option(
				'wpsc-ug-general-settings',
				array(
					'auto-assign'               => 1,
					'allow-customers-to-modify' => 0,
					'auto-fill'                 => 1,
					'allow-change-category'     => 1,
					'allow-sup-close-ticket'    => 0,
				)
			);
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

			$general = get_option( 'wpsc-ug-general-settings' );?>
			<form action="#" onsubmit="return false;" class="wpsc-ug-general-settings">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Auto-assign usergroups for new tickets', 'wpsc-usergroup' ); ?></label>
					</div>
					<select name="auto-assign">
						<option <?php selected( $general['auto-assign'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $general['auto-assign'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Allow customers to modify usergroups of tickets', 'wpsc-usergroup' ); ?></label>
					</div>
					<select name="allow-customers-to-modify">
						<option <?php selected( $general['allow-customers-to-modify'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $general['allow-customers-to-modify'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Auto-fill usergroups in the ticket form', 'wpsc-usergroup' ); ?></label>
					</div>
					<select name="auto-fill">
						<option <?php selected( $general['auto-fill'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $general['auto-fill'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Allow user to change category in ticket form', 'wpsc-usergroup' ); ?></label>
					</div>
					<select name="allow-change-category">
						<option <?php selected( $general['allow-change-category'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $general['allow-change-category'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Allow supervisor to close ticket', 'wpsc-usergroup' ); ?></label>
					</div>
					<select name="allow-sup-close-ticket">
						<option <?php selected( $general['allow-sup-close-ticket'], 1 ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $general['allow-sup-close-ticket'], 0 ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>

				<?php do_action( 'wpsc_ug_get_general_settings' ); ?>

				<input type="hidden" name="action" value="wpsc_ug_set_general_settings">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_ug_set_general_settings' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_ug_set_general_settings(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_ug_reset_general_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_ug_reset_general_settings' ) ); ?>');">
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

			if ( check_ajax_referer( 'wpsc_ug_set_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			update_option(
				'wpsc-ug-general-settings',
				array(
					'auto-assign'               => isset( $_POST['auto-assign'] ) ? intval( $_POST['auto-assign'] ) : 1,
					'allow-customers-to-modify' => isset( $_POST['allow-customers-to-modify'] ) ? intval( $_POST['allow-customers-to-modify'] ) : 0,
					'auto-fill'                 => isset( $_POST['auto-fill'] ) ? intval( $_POST['auto-fill'] ) : 0,
					'allow-change-category'     => isset( $_POST['allow-change-category'] ) ? intval( $_POST['allow-change-category'] ) : 1,
					'allow-sup-close-ticket'    => isset( $_POST['allow-sup-close-ticket'] ) ? intval( $_POST['allow-sup-close-ticket'] ) : 0,
				)
			);

			do_action( 'wpsc_ug_set_general_settings' );

			wp_die();
		}

		/**
		 * Reset general settings
		 *
		 * @return void
		 */
		public static function reset_general_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			self::reset();

			do_action( 'wpsc_ug_reset_general_settings' );

			wp_die();
		}
	}
endif;

WPSC_UG_Settings_General::init();
