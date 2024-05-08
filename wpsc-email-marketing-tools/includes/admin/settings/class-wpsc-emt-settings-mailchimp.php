<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_EMT_Settings_Mailchimp' ) ) :

	final class WPSC_EMT_Settings_Mailchimp {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wp_ajax_wpsc_get_mailchimp_settings', array( __CLASS__, 'get_mailchimp_settings' ) );
			add_action( 'wp_ajax_wpsc_set_mailchimp_setting', array( __CLASS__, 'set_mailchimp_setting' ) );
			add_action( 'wp_ajax_wpsc_reset_mailchimp_setting', array( __CLASS__, 'reset_mailchimp_setting' ) );

			add_action( 'wp_ajax_wpsc_get_mailchimp_audience', array( __CLASS__, 'audience_autocomplete' ) );

			add_action( 'wp_ajax_wpsc_get_mailchimp_tags', array( __CLASS__, 'audience_tags' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$settings = array(
				'api-key'         => '',
				'datacenter'      => '',
				'audience'        => '',
				'audience_name'   => '',
				'status'          => 'subscribed',
				'subscriber-tags' => array(),
			);
			update_option( 'wpsc-mailchimp-settings', $settings );
		}

		/**
		 * General setion body layout
		 *
		 * @return void
		 */
		public static function get_mailchimp_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			$setting = get_option( 'wpsc-mailchimp-settings', array() );
			?>
			<div class="wpsc-dock-container">
				<?php
				printf(
					/* translators: Click here to see the documentation */
					esc_attr__( '%s to see the documentation!', 'supportcandy' ),
					'<a href="https://supportcandy.net/docs/configure-mailchimp/" target="_blank">' . esc_attr__( 'Click here', 'supportcandy' ) . '</a>'
				);
				?>
			</div>
			<form action="#" onsubmit="return false;" class="wpsc-mailchimp-setting">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'API key', 'wpsc-mailchimp' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input type="text" id="api-key" name="api-key" value="<?php echo isset( $setting['api-key'] ) ? esc_attr( $setting['api-key'] ) : ''; ?>">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Audience', 'wpsc-mailchimp' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<select id="wpsc-audience" name="audience">
						<option value="<?php echo esc_attr( $setting['audience'] ); ?>"><?php echo esc_attr( $setting['audience_name'] ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Default subscriber status', 'wpsc-mailchimp' ); ?></label>
					</div>
					<select name="status">
						<option <?php isset( $setting['status'] ) && selected( $setting['status'], 'subscribed' ); ?> value="subscribed"><?php echo esc_attr__( 'Subscribed', 'wpsc-mailchimp' ); ?></option>
						<option <?php isset( $setting['status'] ) && selected( $setting['status'], 'pending' ); ?> value="pending"><?php echo esc_attr__( 'Pending', 'wpsc-mailchimp' ); ?></option>
						<option <?php isset( $setting['status'] ) && selected( $setting['status'], 'unsubscribed' ); ?> value="unsubscribed"><?php echo esc_attr__( 'Unsubscribed', 'wpsc-mailchimp' ); ?></option>
					</select>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Subscriber tags', 'wpsc-mailchimp' ); ?></label>
					</div>
					<select id="subscriber-tags" multiple name="subscriber-tags[]">
						<?php
						foreach ( $setting['subscriber-tags'] as $key => $tag ) {
							?>
							<option selected value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $tag ); ?></option>
							<?php
						}
						?>
					</select>
				</div>

				<input type="hidden" name="action" value="wpsc_set_mailchimp_setting">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_mailchimp_setting' ) ); ?>">
			</form>
			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_mailchimp_setting(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_reset_mailchimp_setting(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_reset_mailchimp_setting' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
			</div>
			<script>
				old_aip_key = '<?php echo esc_attr( $setting['api-key'] ); ?>';
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
								action: 'wpsc_get_mailchimp_audience',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_mailchimp_audience' ) ); ?>'
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

				jQuery('#subscriber-tags').selectWoo({
					ajax: {
						url: supportcandy.ajax_url,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								api_key: jQuery('#api-key').val(),
								audience: jQuery('#wpsc-audience').val(),
								action: 'wpsc_get_mailchimp_tags',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_mailchimp_tags' ) ); ?>',
								q: params.term,
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
		public static function set_mailchimp_setting() {

			if ( check_ajax_referer( 'wpsc_set_mailchimp_setting', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$api_key = isset( $_POST['api-key'] ) ? sanitize_text_field( wp_unslash( $_POST['api-key'] ) ) : '';
			if ( ! $api_key ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$data_center = self::get_mailchimp_datacenter( $api_key );
			if ( ! $data_center ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$audience = isset( $_POST['audience'] ) ? sanitize_text_field( wp_unslash( $_POST['audience'] ) ) : '';
			$name = isset( $_POST['audience_name'] ) ? sanitize_text_field( wp_unslash( $_POST['audience_name'] ) ) : '';

			$tags = isset( $_POST['tags'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['tags'] ) ), true ) : array();

			$setting = apply_filters(
				'wpsc_mailchimp_settings',
				array(
					'api-key'         => $api_key,
					'datacenter'      => $data_center,
					'audience'        => $audience,
					'audience_name'   => $name,
					'status'          => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'subscribed',
					'subscriber-tags' => $tags,
				)
			);
			update_option( 'wpsc-mailchimp-settings', $setting );

			wp_die();
		}

		/**
		 * Reset mailchimp settings to default
		 *
		 * @return void
		 */
		public static function reset_mailchimp_setting() {

			if ( check_ajax_referer( 'wpsc_reset_mailchimp_setting', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			self::reset();
			wp_die();
		}

		/**
		 * Get data center from api-key.
		 *
		 * @param string $api_key - api key.
		 * @return string
		 */
		public static function get_mailchimp_datacenter( $api_key ) {

			$datacenter = '';
			if ( strpos( $api_key, '-' ) !== false ) {
				$parts = explode( '-', $api_key );
				$datacenter = isset( $parts[1] ) ? $parts[1] : '';
			}

			return $datacenter;
		}

		/**
		 * Autocomplete for audience list
		 *
		 * @return void
		 */
		public static function audience_autocomplete() {

			if ( check_ajax_referer( 'wpsc_get_mailchimp_audience', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$api_key = isset( $_GET['api_key'] ) ? sanitize_text_field( wp_unslash( $_GET['api_key'] ) ) : '';
			if ( ! $api_key ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$data_center = self::get_mailchimp_datacenter( $api_key );
			if ( ! $data_center ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$settings = get_option( 'wpsc-mailchimp-settings', array() );
			$list = array();

			if ( ! ( $api_key && $data_center ) ) {
				wp_send_json( $list );
			}

			$url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists';
			$args = array(
				'method'  => 'GET',
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'username:' . $api_key ),
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				wp_send_json( $list );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			if ( $data->lists ) {
				foreach ( $data->lists as $lst ) {

					$list[] = array(
						'id'    => $lst->id,
						'title' => $lst->name,
					);
				}
			}
			wp_send_json( $list );
		}

		/**
		 * Autocomplete for audience tags
		 *
		 * @return void
		 */
		public static function audience_tags() {

			if ( check_ajax_referer( 'wpsc_get_mailchimp_tags', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$api_key = isset( $_GET['api_key'] ) ? sanitize_text_field( wp_unslash( $_GET['api_key'] ) ) : '';
			if ( ! $api_key ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$audience = isset( $_GET['audience'] ) ? sanitize_text_field( wp_unslash( $_GET['audience'] ) ) : '';
			if ( ! $audience ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$data_center = self::get_mailchimp_datacenter( $api_key );
			if ( ! $data_center ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$settings = get_option( 'wpsc-mailchimp-settings', array() );
			$tags = array();

			if ( ! ( $api_key && $data_center ) ) {
				wp_send_json( $tags );
			}

			$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

			$url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $audience . '/tag-search?name=' . $term;
			$args = array(
				'method'  => 'GET',
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'username:' . $api_key ),
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				wp_send_json( $tags );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			if ( $data->tags ) {
				foreach ( $data->tags as $tag ) {

					$tags[] = array(
						'id'    => $tag->id,
						'title' => $tag->name,
					);
				}
			}
			wp_send_json( $tags );
		}

		/**
		 * Add user to mailchimp list
		 *
		 * @param array $user_data - user data.
		 * @return bool
		 */
		public static function add_subscriber_to_mailchimp( $user_data ) {

			$customer = WPSC_Customer::get_by_email( $user_data['email_address'] );

			if ( $customer->subscribed ) {
					return true;
			}

			$settings = get_option( 'wpsc-mailchimp-settings', array() );

			$url = 'https://' . $settings['datacenter'] . '.api.mailchimp.com/3.0/lists/' . $settings['audience'] . '/members/' . md5( $user_data['email_address'] );
			$args = array(
				'method'  => 'PUT',
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'username:' . $settings['api-key'] ),
				),
				'body'    => wp_json_encode(
					array(
						'email_address' => $user_data['email_address'],
						'status'        => $settings['status'],
						'merge_fields'  => array(
							'FNAME' => $user_data['first_name'],
							'LNAME' => $user_data['last_name'],
						),
						'tags'          => array_values( $settings['subscriber-tags'] ),
					)
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );
			if ( isset( $data->id ) ) {
				$customer->subscribed = 1;
				$customer->save();
				return true;
			} else {
				return false;
			}
		}
	}
endif;

WPSC_EMT_Settings_Mailchimp::init();
