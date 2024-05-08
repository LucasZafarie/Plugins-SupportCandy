<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_License' ) ) :

	final class WPSC_RP_License {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// license body.
			add_action( 'wpsc_licenses', array( __CLASS__, 'print_license_body' ) );

			// license activate and deactivate.
			add_action( 'wp_ajax_wpsc_rp_license_activate', array( __CLASS__, 'activate' ) );
			add_action( 'wp_ajax_wpsc_rp_license_deactivate', array( __CLASS__, 'deactivate' ) );

			// license checker.
			add_action( 'wpsc_license_checker', array( __CLASS__, 'license_checker' ) );

			// load scripts.
			add_action( 'admin_footer', array( __CLASS__, 'load_scripts' ) );
		}

		/**
		 * Print license body for this add-on
		 *
		 * @return void
		 */
		public static function print_license_body() {

			$licenses = get_option( 'wpsc-licenses', array() );
			$license  = isset( $licenses['reports'] ) ? $licenses['reports'] : array();

			$license_key = isset( $license['key'] ) ? $license['key'] : '';
			$expiry_date = isset( $license['expiry'] ) ? $license['expiry'] : '';?>

			<div class="license-container">

				<img src="<?php echo esc_url( WPSC_PLUGIN_URL . '/asset/images/reports.png' ); ?>" alt="">

				<input type="text" value="<?php echo esc_attr( $license_key ) ? esc_attr( $license_key ) : ''; ?>" <?php echo esc_attr( $license_key ) ? 'disabled' : ''; ?> autocomplete="off"/>
				<?php

				if ( $license_key ) :
					?>
					<button class="wpsc-button small primary" onclick="wpsc_rp_license_deactivate(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_license_deactivate' ) ); ?>');"><?php echo esc_attr( wpsc__( 'Deactivate' ) ); ?></button>
					<?php
					if ( $expiry_date == 'lifetime' ) :
						?>
						<p><?php echo esc_attr( wpsc_translate_common_strings( 'license-activated' ) ); ?></p>
						<?php
					else :

						$now    = new DateTime();
						$expiry = new DateTime( $expiry_date );

						if ( $now < $expiry ) :
							?>
							<p>
								<?php
								/* translators: %1$s: license expiry date */
								printf(
									esc_attr( wpsc_translate_common_strings( 'license-expires' ) ),
									esc_attr( $expiry->format( 'F d, Y' ) )
								);
								?>
							</p>
							<?php

						else :
							?>
							<p class="expired">
								<?php
								/* translators: %1$s: license expiry date */
								printf(
									esc_attr( wpsc_translate_common_strings( 'license-expired' ) ),
									esc_attr( $expiry->format( 'F d, Y' ) )
								);
								?>
							</p>
							<?php

						endif;

					endif;

				else :
					?>
					<button class="wpsc-button small primary" onclick="wpsc_rp_license_activate(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_license_activate' ) ); ?>');"><?php echo esc_attr( wpsc__( 'Activate' ) ); ?></button>
					<p><?php echo esc_attr( wpsc_translate_common_strings( 'activate-license' ) ); ?></p>
					<?php
				endif;
				?>
			</div>
			<?php
		}

		/**
		 * Activate license
		 *
		 * @return void
		 */
		public static function activate() {

			if ( check_ajax_referer( 'wpsc_rp_license_activate', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
			if ( ! $license_key ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$licenses = get_option( 'wpsc-licenses', array() );

			$api_params   = array(
				'edd_action' => 'activate_license',
				'license'    => $license_key,
				'item_id'    => WPSC_RP_STORE_ID,
				'url'        => site_url(),
			);
			$response     = wp_remote_post(
				WPSC_STORE_URL,
				array(
					'body'      => $api_params,
					'timeout'   => 15,
					'sslverify' => false,
				)
			);
			$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

			$license_message = array();
			if ( isset( $license_data['success'] ) && $license_data['success'] ) {

				$license_message = array(
					'success' => true,
					'message' => '',
				);
				$licenses['reports'] = array(
					'key'    => $license_key,
					'expiry' => $license_data['expires'],
				);
				update_option( 'wpsc-licenses', $licenses );
			} else {
				$license_message = array(
					'success' => false,
					'message' => isset( $license_data['error'] ) ? wpsc_licence_errors( $license_data['error'] ) : esc_attr( 'Could not activate license' ),
				);
			}

			wp_send_json( $license_message );
		}

		/**
		 * Deactivate license
		 *
		 * @return void
		 */
		public static function deactivate() {

			if ( check_ajax_referer( 'wpsc_rp_license_deactivate', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
			if ( ! $license_key ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$licenses = get_option( 'wpsc-licenses', array() );

			$api_params   = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license_key,
				'item_id'    => WPSC_RP_STORE_ID,
				'url'        => site_url(),
			);
			$response     = wp_remote_post(
				WPSC_STORE_URL,
				array(
					'body'      => $api_params,
					'timeout'   => 15,
					'sslverify' => false,
				)
			);
			$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

			unset( $licenses['reports'] );
			update_option( 'wpsc-licenses', $licenses );

			wp_die();
		}

		/**
		 * License sycronization with server
		 *
		 * @return void
		 */
		public static function license_checker() {

			$licenses = get_option( 'wpsc-licenses', array() );
			$license  = isset( $licenses['reports'] ) ? $licenses['reports'] : array();

			if ( $license ) {
				$api_params   = array(
					'edd_action' => 'check_license',
					'license'    => $license['key'],
					'item_id'    => WPSC_RP_STORE_ID,
					'url'        => site_url(),
				);
				$response     = wp_remote_post(
					WPSC_STORE_URL,
					array(
						'body'      => $api_params,
						'timeout'   => 15,
						'sslverify' => false,
					)
				);
				if ( ! ( is_wp_error( $response ) || $response['response']['code'] !== 200 ) ) {
					$license_data = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( isset( $license_data['success'] ) && $license_data['success'] ) {
						if ( $license_data['expires'] != $license['expiry'] ) {
							$license['expiry']   = $license_data['expires'];
							$licenses['reports'] = $license;
							update_option( 'wpsc-licenses', $licenses );
						}
					}
				}
			}
		}

		/**
		 * Load styles and scripts
		 *
		 * @return void
		 */
		public static function load_scripts() {
			?>

			<script>
				/**
				 * Activate edd license
				 */
				function wpsc_rp_license_activate(el, nonce) {

					var licenseKey = jQuery(el).prev().val().trim();
					if (!licenseKey) return;

					jQuery(el).text(supportcandy.translations.please_wait);
					const data = { 
						action: 'wpsc_rp_license_activate',
						license_key: licenseKey,
						_ajax_nonce: nonce
					};
					jQuery.post(supportcandy.ajax_url, data, function (response) {
						if (!response.success) {
							alert( response.message );
						}
						window.location.reload();
					});
				}

				/**
				 * Deactivate license
				 */
				function wpsc_rp_license_deactivate(el, nonce) {

					var licenseKey = jQuery(el).prev().val().trim();
					if (!licenseKey) return;

					jQuery(el).text(supportcandy.translations.please_wait);
					const data = { 
						action: 'wpsc_rp_license_deactivate',
						license_key: licenseKey,
						_ajax_nonce: nonce
					};
					jQuery.post(supportcandy.ajax_url, data, function (response) {
						window.location.reload();
					});
				}
			</script>
			<?php
		}
	}
endif;

WPSC_RP_License::init();
