<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_General_Setting' ) ) :

	final class WPSC_SF_General_Setting {

		/**
		 * Initialization
		 *
		 * @return void
		 */
		public static function init() {

			// setting options.
			add_action( 'wp_ajax_wpsc_sf_general_setting', array( __CLASS__, 'general_setting' ) );
			add_action( 'wp_ajax_wpsc_sf_set_general_setting', array( __CLASS__, 'save_settings' ) );
			add_action( 'wp_ajax_wpsc_sf_reset_general_settings', array( __CLASS__, 'reset_settings' ) );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$general_settings = apply_filters(
				'wpsc_sf_general_setting',
				array(
					'survey-page'      => 0,
					'customer-trigger' => 1,
					'statuses-enabled' => array( 4 ),
				)
			);
			update_option( 'wpsc-sf-general-setting', $general_settings );
		}

		/**
		 * Satisfaction survey general setting
		 *
		 * @return void
		 */
		public static function general_setting() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( 'Unauthorized!', 401 );
			}

			$statuses       = WPSC_Status::find( array( 'items_per_page' => 0 ) )['results'];
			$tl_ms_advanced = get_option( 'wpsc-tl-ms-advanced', array() );
			$settings       = get_option( 'wpsc-sf-general-setting', array() );?>

			<form action="#" onsubmit="return false;" class="wpsc-sf-general">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Survey page', 'wpsc-sf' ); ?></label>
					</div>
					<select id="wpsc-survey-page" class="wpsc-select-wp-page" name="survey-page">
						<?php
						if ( $settings['survey-page'] ) {
							$page = get_post( $settings['survey-page'] )
							?>
							<option value="<?php echo esc_attr( $page->ID ); ?>"><?php echo esc_attr( $page->post_title ); ?></option>
							<?php
						} else {
							?>
							<option value="0"></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Trigger survey when customer close ticket', 'wpsc-sf' ); ?></label>
					</div>
					<select name="customer-trigger">
						<option <?php selected( $settings['customer-trigger'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $settings['customer-trigger'], '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Closed statuses to enable survey', 'wpsc-sf' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<select class="statuses-enabled" name="statuses-enabled[]" multiple>
						<?php
						foreach ( $statuses as $status ) {
							if ( ! in_array( $status->id, $tl_ms_advanced['closed-ticket-statuses'] ) ) {
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
				<?php do_action( 'wpsc_sf_gs' ); ?>
				<script>
					jQuery('.wpsc-select-wp-page').selectWoo({
						ajax: {
							url: supportcandy.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function (params) {
								return {
									q: params.term, // search term
									page: params.page,
									action: 'wpsc_search_wp_pages',
									_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'wpsc_search_wp_pages' ) ); ?>'
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
						minimumInputLength: 1,
						allowClear: true,
						placeholder: ""
					});
				</script>
				<input type="hidden" name="action" value="wpsc_sf_set_general_setting">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_sf_set_general_setting' ) ); ?>">
			</form>
			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_sf_set_general_setting(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_sf_reset_general_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_sf_reset_general_settings' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
			</div>
			<?php
			wp_die();
		}

		/**
		 * Save settings
		 *
		 * @return void
		 */
		public static function save_settings() {

			if ( check_ajax_referer( 'wpsc_sf_set_general_setting', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$statuses_enabled = isset( $_POST['statuses-enabled'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['statuses-enabled'] ) ) ) : array();
			if ( ! $statuses_enabled ) {
				wp_send_json_error( 'Statuses enabled should not be empty', 400 );
			}

			$sf_gs = apply_filters(
				'wpsc_set_sf_general_setting',
				array(
					'survey-page'      => isset( $_POST['survey-page'] ) ? intval( $_POST['survey-page'] ) : 0,
					'customer-trigger' => isset( $_POST['customer-trigger'] ) ? intval( $_POST['customer-trigger'] ) : 1,
					'statuses-enabled' => $statuses_enabled,
				)
			);
			update_option( 'wpsc-sf-general-setting', $sf_gs );
			wp_die();
		}

		/**
		 * Reset settings to default
		 *
		 * @return void
		 */
		public static function reset_settings() {

			if ( check_ajax_referer( 'wpsc_sf_reset_general_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			self::reset();
			wp_die();
		}
	}
endif;

WPSC_SF_General_Setting::init();
