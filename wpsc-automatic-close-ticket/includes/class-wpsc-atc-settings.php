<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ATC_Settings' ) ) :

	final class WPSC_ATC_Settings {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add settings section.
			add_filter( 'wpsc_settings_page_sections', array( __CLASS__, 'add_settings_tab' ) );

			// setting callbacks.
			add_action( 'wp_ajax_wpsc_get_atc_settings', array( __CLASS__, 'get_atc_settings' ) );
			add_action( 'wp_ajax_wpsc_set_atc_settings', array( __CLASS__, 'set_atc_settings' ) );
			add_action( 'wp_ajax_wpsc_reset_atc_settings', array( __CLASS__, 'reset_atc_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			update_option(
				'wpsc-atc-settings',
				array(
					'statuses-enabled' => array(),
					'age'              => 10,
					'close-status'     => 0,
				)
			);
		}

		/**
		 * Settings tab
		 *
		 * @param array $sections - setting menus.
		 * @return array
		 */
		public static function add_settings_tab( $sections ) {

			$sections['atc'] = array(
				'slug'     => 'atc',
				'icon'     => 'checked',
				'label'    => esc_attr__( 'Automatic Close Tickets', 'wpsc-atc' ),
				'callback' => 'wpsc_get_atc_settings',
			);
			return $sections;
		}

		/**
		 * Load settings ui
		 *
		 * @return void
		 */
		public static function get_atc_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$tl_ms_advanced = get_option( 'wpsc-tl-ms-advanced', array() );
			$statuses       = WPSC_Status::find( array( 'items_per_page' => 0 ) )['results'];
			$settings       = get_option( 'wpsc-atc-settings' )?>

			<div class="wpsc-setting-header">
				<h2><?php esc_attr_e( 'Automatic Close Tickets', 'wpsc-atc' ); ?></h2>
			</div>

			<div class="wpsc-setting-section-body">

				<form action="#" onsubmit="return false;" class="wpsc-frm-atc-settings">

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Ticket status to check for auto-close', 'wpsc-atc' ); ?></label>
							<span class="required-char">*</span>
						</div>
						<select class="statuses-enabled" name="statuses-enabled[]" multiple>
							<?php
							foreach ( $statuses as $status ) {
								if ( in_array( $status->id, $tl_ms_advanced['closed-ticket-statuses'] ) ) {
									continue;
								}
								$selected = $settings['statuses-enabled'] && in_array( $status->id, $settings['statuses-enabled'] ) ? 'selected' : '';
								?>
								<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $status->id ); ?>"><?php echo esc_attr( $status->name ); ?></option>
								<?php
							}
							?>
						</select>
						<script>jQuery('select.statuses-enabled').selectWoo();</script>
					</div>

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Age (days)', 'wpsc-atc' ); ?></label>
						</div>
						<input type="number" name="age" value="<?php echo esc_attr( $settings['age'] ); ?>">
					</div>

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Close status', 'wpsc-atc' ); ?></label>
						</div>
						<select name="close-status">
							<?php
							foreach ( $statuses as $status ) {
								if ( ! in_array( $status->id, $tl_ms_advanced['closed-ticket-statuses'] ) ) {
									continue;
								}
								?>
								<option <?php selected( $status->id, $settings['close-status'] ); ?> value="<?php echo esc_attr( $status->id ); ?>"><?php echo esc_attr( $status->name ); ?></option>
								<?php
							}
							?>
						</select>
					</div>

					<input type="hidden" name="action" value="wpsc_set_atc_settings">
					<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_atc_settings' ) ); ?>">

				</form>

				<div class="setting-footer-actions">
					<button 
						class="wpsc-button normal primary margin-right"
						onclick="wpsc_set_atc_settings(this);">
						<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
					<button 
						class="wpsc-button normal secondary"
						onclick="wpsc_reset_atc_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_reset_atc_settings' ) ); ?>');">
						<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
				</div>

			</div>
			<?php

			wp_die();
		}

		/**
		 * Update settings
		 *
		 * @return void
		 */
		public static function set_atc_settings() {

			if ( check_ajax_referer( 'wpsc_set_atc_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$statuses_enabled = isset( $_POST['statuses-enabled'] ) ? array_filter( array_map( 'intval', $_POST['statuses-enabled'] ) ) : array();
			if ( ! $statuses_enabled ) {
				wp_send_json_error( 'Statuses enabled should not be empty', 400 );
			}

			$age = isset( $_POST['age'] ) ? intval( $_POST['age'] ) : 0;

			$close_status = isset( $_POST['close-status'] ) ? intval( $_POST['close-status'] ) : 0;
			if ( ! $close_status ) {
				wp_send_json_error( 'Close status should not be empty', 400 );
			}

			update_option(
				'wpsc-atc-settings',
				array(
					'statuses-enabled' => $statuses_enabled,
					'age'              => $age,
					'close-status'     => $close_status,
				)
			);

			wp_die();
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset_atc_settings() {

			if ( check_ajax_referer( 'wpsc_reset_atc_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			self::reset();
			wp_die();
		}
	}
endif;

WPSC_ATC_Settings::init();
