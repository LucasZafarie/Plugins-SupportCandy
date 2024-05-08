<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_EMT_Settings_GetResponse' ) ) :

	final class WPSC_EMT_Settings_GetResponse {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wp_ajax_wpsc_get_getresponse_settings', array( __CLASS__, 'get_getresponse_settings' ) );
			add_action( 'wp_ajax_wpsc_set_getresponse_setting', array( __CLASS__, 'set_getresponse_setting' ) );
			add_action( 'wp_ajax_wpsc_reset_getresponse_setting', array( __CLASS__, 'reset_getresponse_setting' ) );

			add_action( 'wp_ajax_wpsc_get_getresponse_audience', array( __CLASS__, 'audience_autocomplete' ) );
			add_action( 'wp_ajax_wpsc_get_getresponse_subscriber_tags', array( __CLASS__, 'subscriber_tags' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$settings = array(
				'api-key'         => '',
				'audience'        => '',
				'subscriber-tags' => array(),
			);
			update_option( 'wpsc-getresponse-settings', $settings );
		}

		/**
		 * General setion body layout
		 *
		 * @return void
		 */
		public static function get_getresponse_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$setting = get_option( 'wpsc-getresponse-settings', array() );
			?>
			<div class="wpsc-dock-container">
				<?php
				printf(
					/* translators: Click here to see the documentation */
					esc_attr__( '%s to see the documentation!', 'supportcandy' ),
					'<a href="https://supportcandy.net/docs/configure-getresponse/" target="_blank">' . esc_attr__( 'Click here', 'supportcandy' ) . '</a>'
				);
				?>
			</div>
			<form action="#" onsubmit="return false;" class="wpsc-getresponse-setting">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'API Key', 'wpsc-getresponse' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input type="text" id="api-key" name="api-key" value="<?php echo isset( $setting['api-key'] ) ? esc_attr( $setting['api-key'] ) : ''; ?>">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Audience', 'wpsc-getresponse' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<select id="wpsc-audience" name="audience">
						<option value="<?php echo esc_attr( $setting['audience'] ); ?>"><?php echo esc_attr( $setting['audience_name'] ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Subscriber Tags', 'wpsc-getresponse' ); ?></label>
					</div>
					<select id="wpsc-subscriber-tags" multiple name="subscriber-tags[]">
						<?php
						foreach ( $setting['subscriber-tags'] as $key => $tag ) {
							?>
							<option selected value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $tag ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<input type="hidden" name="action" value="wpsc_set_getresponse_setting">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_getresponse_setting' ) ); ?>">
			</form>
			
			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_getresponse_setting(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_reset_getresponse_setting(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_reset_getresponse_setting' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
			</div>
			
			<script>
				old_aip_key = '<?php echo isset( $setting['api-key'] ) ? esc_attr( $setting['api-key'] ) : ''; ?>';
				jQuery("#api-key").blur(function() {
					new_api_key = jQuery(this).val();
					if( old_aip_key != new_api_key ) {
						jQuery('#wpsc-audience').empty();
					}
					old_aip_key = new_api_key;
				});

				jQuery('#wpsc-audience').selectWoo({
					ajax: {
						url: supportcandy.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								api_key: jQuery('#api-key').val(),
								action: 'wpsc_get_getresponse_audience',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_getresponse_audience' ) ); ?>'
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
					minimumInputLength: 0,
					allowClear: true,
					placeholder: ""
				});

				jQuery('#wpsc-subscriber-tags').selectWoo({
					ajax: {
						url: supportcandy.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								api_key: jQuery('#api-key').val(),
								action: 'wpsc_get_getresponse_subscriber_tags',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_getresponse_subscriber_tags' ) ); ?>'
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
					minimumInputLength: 0,
					allowClear: true,
					placeholder: ""
				});
			</script>
			<?php
			wp_die();
		}

		/**
		 * Save settings
		 *
		 * @return void
		 */
		public static function set_getresponse_setting() {

			if ( check_ajax_referer( 'wpsc_set_getresponse_setting', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$api_key = isset( $_POST['api-key'] ) ? sanitize_text_field( wp_unslash( $_POST['api-key'] ) ) : '';
			if ( ! $api_key ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			$audience = isset( $_POST['audience'] ) ? sanitize_text_field( wp_unslash( $_POST['audience'] ) ) : '';
			$name = isset( $_POST['audience_name'] ) ? sanitize_text_field( wp_unslash( $_POST['audience_name'] ) ) : '';

			$tags = isset( $_POST['tags'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['tags'] ) ), true ) : array();

			$setting = apply_filters(
				'wpsc_getresponse_settings',
				array(
					'api-key'         => $api_key,
					'audience'        => $audience,
					'audience_name'   => $name,
					'subscriber-tags' => $tags,
				)
			);
			update_option( 'wpsc-getresponse-settings', $setting );

			wp_die();
		}


		/**
		 * Reset getresponse settings to default
		 *
		 * @return void
		 */
		public static function reset_getresponse_setting() {

			if ( check_ajax_referer( 'wpsc_reset_getresponse_setting', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			self::reset();
			wp_die();
		}

		/**
		 * Autocomplete for audience list
		 *
		 * @return void
		 */
		public static function audience_autocomplete() {

			if ( check_ajax_referer( 'wpsc_get_getresponse_audience', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$api_key = isset( $_GET['api_key'] ) ? sanitize_text_field( wp_unslash( $_GET['api_key'] ) ) : '';
			if ( ! $api_key ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$settings = get_option( 'wpsc-getresponse-settings', array() );
			$list = array();

			if ( ! ( $api_key ) ) {
				wp_send_json( $list );
			}

			$url = 'https://api.getresponse.com/v3/campaigns';
			$args = array(
				'method'  => 'GET',
				'headers' => array(
					'X-Auth-Token' => 'api-key ' . $api_key,
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				wp_send_json( $list );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			if ( $data ) {
				foreach ( $data as $lst ) {

					$list[] = array(
						'id'    => $lst->campaignId, //phpcs:ignore
						'title' => $lst->name,
					);
				}
			}
			wp_send_json( $list );
		}

		/**
		 * Autocomplete for audience list
		 *
		 * @return void
		 */
		public static function subscriber_tags() {

			if ( check_ajax_referer( 'wpsc_get_getresponse_subscriber_tags', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$api_key = isset( $_GET['api_key'] ) ? sanitize_text_field( wp_unslash( $_GET['api_key'] ) ) : '';
			if ( ! $api_key ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$settings = get_option( 'wpsc-getresponse-settings', array() );
			$tags = array();

			if ( ! ( $api_key ) ) {
				wp_send_json( $tags );
			}

			$url = 'https://api.getresponse.com/v3/tags';
			$args = array(
				'method'  => 'GET',
				'headers' => array(
					'X-Auth-Token' => 'api-key ' . $api_key,
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				wp_send_json( $tags );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			if ( $data ) {
				foreach ( $data as $lst ) {

					$tags[] = array(
						'id'    => $lst->tagId, //phpcs:ignore
						'title' => $lst->name,
					);
				}
			}
			wp_send_json( $tags );
		}

		/**
		 * Add user to getresponse list
		 *
		 * @param array $user_data - user data.
		 * @return bool
		 */
		public static function add_subscriber_to_getresponse( $user_data ) {

			$customer = WPSC_Customer::get_by_email( $user_data['email_address'] );
			if ( $customer->subscribed ) {
				return true;
			}

			$settings = get_option( 'wpsc-getresponse-settings', array() );

			$url = 'https://api.getresponse.com/v3/contacts';
			$args = array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
					'X-Auth-Token' => 'api-key ' . $settings['api-key'],
				),
				'body'    => wp_json_encode(
					array(
						'name'     => $user_data['first_name'] . $user_data['last_name'],
						'campaign' => array(
							'campaignId' => $settings['audience'],
						),
						'email'    => $user_data['email_address'],
						'tags'     => array_keys( $settings['subscriber-tags'] ),
					)
				),
			);

			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$code = wp_remote_retrieve_response_code( $response );

			if ( $code == '202' ) {
				$customer->subscribed = 1;
				$customer->save();
				return true;
			} else {
				return false;
			}
		}
	}
endif;
WPSC_EMT_Settings_GetResponse::init();
