<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Settings' ) ) :

	final class WPSC_RP_Settings {

		/**
		 * List of allowed custom field types in report
		 *
		 * @var array
		 */
		public static $allowed_cft = array();

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'init', array( __CLASS__, 'allowed_cft' ) );

			// settings.
			add_filter( 'wpsc_icons', array( __CLASS__, 'add_icons' ) );
			add_filter( 'wpsc_settings_page_sections', array( __CLASS__, 'add_settings_tab' ) );
			add_action( 'wp_ajax_wpsc_get_report_settings', array( __CLASS__, 'get_report_settings' ) );
			add_action( 'wp_ajax_wpsc_set_report_settings', array( __CLASS__, 'set_report_settings' ) );
			add_action( 'wp_ajax_wpsc_reset_report_settings', array( __CLASS__, 'reset_report_settings' ) );

			// reports menu access.
			add_action( 'wpsc_add_agent_role_other_permissions', array( __CLASS__, 'add_agent_role_other_permissions' ) );
			add_filter( 'wpsc_set_add_agent_role', array( __CLASS__, 'set_add_agent_role_other_permission' ), 10, 2 );
			add_action( 'wpsc_edit_agent_role_other_permissions', array( __CLASS__, 'edit_agent_role_other_permissions' ) );
			add_filter( 'wpsc_set_edit_agent_role', array( __CLASS__, 'set_edit_agent_role_other_permission' ), 10, 3 );
		}

		/**
		 * Reset settings
		 *
		 * @return void
		 */
		public static function reset() {

			$default_reports = apply_filters( 'wpsc_default_reports', array( 'category', 'priority' ) );
			$ids = array();
			foreach ( $default_reports as $value ) {
				$cf = WPSC_Custom_Field::get_cf_by_slug( $value );
				if ( $cf ) {
					$ids[] = $cf->id;
				}
			}

			// reports settings.
			update_option(
				'wpsc-rp-settings',
				array(
					'default-duration' => 'last-30-days',
					'cf-reports'       => $ids,
				)
			);
		}

		/**
		 * List of allowed custom field types in reports
		 *
		 * @return void
		 */
		public static function allowed_cft() {

			self::$allowed_cft = apply_filters(
				'allowed_cft_reports',
				array(
					'df_category',
					'df_priority',
					'cf_single_select',
					'cf_multi_select',
					'cf_checkbox',
					'cf_radio_button',
				)
			);
		}

		/**
		 * Add icons to library
		 *
		 * @param array $icons - list of icons.
		 * @return array
		 */
		public static function add_icons( $icons ) {

			$icons['chart-line']    = file_get_contents( WPSC_RP_ABSPATH . 'asset/icons/chart-line-solid.svg' ); // phpcs:ignore
			$icons['people-arrows'] = file_get_contents( WPSC_RP_ABSPATH . 'asset/icons/people-arrows-solid.svg' ); // phpcs:ignore
			$icons['chart-bar']     = file_get_contents( WPSC_RP_ABSPATH . 'asset/icons/chart-bar-solid.svg' ); // phpcs:ignore

			return $icons;
		}

		/**
		 * Settings tab
		 *
		 * @param array $sections - settings.
		 * @return array
		 */
		public static function add_settings_tab( $sections ) {

			$sections['reports'] = array(
				'slug'     => 'reports',
				'icon'     => 'chart-line',
				'label'    => esc_attr__( 'Reports', 'wpsc-reports' ),
				'callback' => 'wpsc_get_report_settings',
			);
			return $sections;
		}

		/**
		 * Get report settings
		 *
		 * @return void
		 */
		public static function get_report_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$settings = get_option( 'wpsc-rp-settings', array() );
			if ( ! $settings ) {
				// in some cases option value is null and it gives fatal error.
				self::reset();
				$settings = get_option( 'wpsc-rp-settings' );
			}
			$duration      = $settings['default-duration'];
			$custom_fields = WPSC_Custom_Field::$custom_fields;?>

			<div class="wpsc-setting-header"><h2><?php esc_attr_e( 'Report Settings', 'wpsc-reports' ); ?></h2></div>
			<div class="wpsc-setting-section-body">
				<form action="#" onsubmit="return false;" class="wpsc-frm-report-settings">
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Default Duration', 'wpsc-reports' ); ?></label>
						</div>
						<select name="default-duration">
							<option <?php selected( $duration, 'today' ); ?> value="today"><?php esc_attr_e( 'Today', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'yesterday' ); ?> value="yesterday"><?php esc_attr_e( 'Yesterday', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'this-week' ); ?> value="this-week"><?php esc_attr_e( 'This Week', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'last-week' ); ?> value="last-week"><?php esc_attr_e( 'Last Week', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'last-30-days' ); ?> value="last-30-days"><?php esc_attr_e( 'Last 30 Days', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'this-month' ); ?> value="this-month"><?php esc_attr_e( 'This Month', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'last-month' ); ?> value="last-month"><?php esc_attr_e( 'Last Month', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'this-quarter' ); ?> value="this-quarter"><?php esc_attr_e( 'This Quarter', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'last-quarter' ); ?> value="last-quarter"><?php esc_attr_e( 'Last Quarter', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'this-year' ); ?> value="this-year"><?php esc_attr_e( 'This Year', 'wpsc-reports' ); ?></option>
							<option <?php selected( $duration, 'last-year' ); ?> value="last-year"><?php esc_attr_e( 'Last Year', 'wpsc-reports' ); ?></option>
						</select>
					</div>

					<div class="wpsc-input-group">
						<div class="label-container">
							<label for=""><?php esc_attr_e( 'Generate reports for custom fields', 'wpsc-reports' ); ?></label>
						</div>
						<select multiple id="wpsc-select-report-items" name="cf_id[]">
							<?php
							foreach ( $custom_fields as $cf ) :
								if ( in_array( $cf->type::$slug, self::$allowed_cft ) && $cf->field != 'customer' && $cf->field != 'usergroup' ) :
									$selected = in_array( $cf->id, $settings['cf-reports'] ) ? 'selected="selected"' : '';
									?>
									<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $cf->id ); ?>"><?php echo esc_attr( $cf->name ); ?></option>
									<?php
								endif;
							endforeach;
							?>
						</select>
						<script>
							jQuery('#wpsc-select-report-items').selectWoo({
								allowClear: false,
								placeholder: ""
							});
						</script>
					</div>
					<input type="hidden" name="action" value="wpsc_set_report_settings">
					<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_report_settings' ) ); ?>">
				</form>
				<div class="setting-footer-actions">
					<button 
						class="wpsc-button normal primary margin-right"
						onclick="wpsc_set_report_settings(this);">
						<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
					<button 
						class="wpsc-button normal secondary"
						onclick="wpsc_reset_report_settings(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_reset_report_settings' ) ); ?>');">
						<?php echo esc_attr( wpsc__( 'Reset default', 'supportcandy' ) ); ?></button>
				</div>
			</div>
			<?php
			wp_die();
		}

		/**
		 * Set report settings
		 *
		 * @return void
		 */
		public static function set_report_settings() {

			if ( check_ajax_referer( 'wpsc_set_report_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$default_duration = isset( $_POST['default-duration'] ) ? sanitize_text_field( wp_unslash( $_POST['default-duration'] ) ) : '';
			if ( ! $default_duration ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$ids = isset( $_POST['cf_id'] ) ? array_filter( array_map( 'intval', $_POST['cf_id'] ) ) : array();

			$settings                     = get_option( 'wpsc-rp-settings' );
			$settings['default-duration'] = $default_duration;
			$settings['cf-reports']       = $ids;

			update_option( 'wpsc-rp-settings', $settings );
			wp_die();
		}

		/**
		 * Reset report settings
		 *
		 * @return void
		 */
		public static function reset_report_settings() {

			if ( check_ajax_referer( 'wpsc_reset_report_settings', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			self::reset();
			wp_die();
		}

		/**
		 * Add permisstion settings to add agent role
		 *
		 * @return void
		 */
		public static function add_agent_role_other_permissions() {
			?>

			<div>
				<input name="caps[]" type="checkbox" value="view-reports">
				<span><?php esc_attr_e( 'View Reports', 'wpsc-reports' ); ?></span>
			</div>
			<?php
		}

		/**
		 * Set other permissions for this filter
		 *
		 * @param array  $args - agent capabilities.
		 * @param string $caps  - agent capabilities.
		 * @return array
		 */
		public static function set_add_agent_role_other_permission( $args, $caps ) {

			$args['caps']['view-reports'] = in_array( 'view-reports', $caps ) ? true : false;
			return $args;
		}

		/**
		 * Edit permisstion settings to add agent role
		 *
		 * @param array $role - agent capabilities.
		 * @return void
		 */
		public static function edit_agent_role_other_permissions( $role ) {
			?>
			<div>
				<input name="caps[]" type="checkbox" <?php checked( $role['caps']['view-reports'], 1 ); ?> value="view-reports">
				<span><?php esc_attr_e( 'View Reports', 'wpsc-reports' ); ?></span>
			</div>
			<?php
		}

		/**
		 * Set edit agent role
		 *
		 * @param array $new - changed value.
		 * @param array $prev - existing value.
		 * @param array $caps - capabilities.
		 * @return array
		 */
		public static function set_edit_agent_role_other_permission( $new, $prev, $caps ) {

			$new['caps']['view-reports'] = in_array( 'view-reports', $caps ) ? true : false;
			return $new;
		}
	}
endif;

WPSC_RP_Settings::init();
