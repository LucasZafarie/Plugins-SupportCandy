<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Filters' ) ) :

	final class WPSC_RP_Filters {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wp_ajax_wpsc_rp_get_filter_actions', array( __CLASS__, 'get_filter_actions' ) );
			add_action( 'wp_ajax_wpsc_rp_get_custom_filter', array( __CLASS__, 'get_custom_filter' ) );
			add_action( 'wp_ajax_wpsc_rp_get_add_saved_filter', array( __CLASS__, 'get_add_saved_filter' ) );
			add_action( 'wp_ajax_wpsc_rp_set_add_saved_filter', array( __CLASS__, 'set_add_saved_filter' ) );
			add_action( 'wp_ajax_wpsc_rp_update_saved_filter', array( __CLASS__, 'update_saved_filter' ) );
			add_action( 'wp_ajax_wpsc_rp_delete_user_filter', array( __CLASS__, 'delete_saved_filter' ) );
		}

		/**
		 * Get report durations
		 */
		public static function get_durations() {

			$settings = get_option( 'wpsc-rp-settings' );
			$duration = $settings['default-duration']?>

			<div class="setting-filter-item">
				<span class="label"><?php esc_attr_e( 'Duration', 'wpsc-reports' ); ?></span>
				<select name="duration" onchange="wpsc_change_filter_duration(this)">
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
					<option value="custom"><?php esc_attr_e( 'Custom', 'wpsc-reports' ); ?></option>
				</select>
			</div>
			<?php
		}

		/**
		 * Print filters form to reports
		 *
		 * @param string $report - report shortname.
		 */
		public static function layout( $report ) {

			$current_user = WPSC_Current_User::$current_user;

			// saved filters for this user.
			$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
			if ( ! $saved_filters ) {
				$saved_filters = array();
			}

			$allow_filters = apply_filters( 'wpsc_reports_allow_custom_filters', true, $report );
			?>

			<form onsubmit="return false;" class="wpsc-report-filters">
				<div class="wpsc-setting-filter-container">
					<div class="setting-filter-item" <?php echo $allow_filters ? '' : 'style = "display: none;"'; ?>>
						<span class="label"><?php echo esc_attr( wpsc__( 'Filter', 'supportcandy' ) ); ?></span>
						<select name="filter" onchange="wpsc_rp_change_filter(this,'<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_get_custom_filter' ) ); ?>');" style="width: 200px;">
							<option value=""><?php echo esc_attr( wpsc__( 'All', 'supportcandy' ) ); ?></option>
							<?php
							foreach ( $saved_filters as $key => $filter ) :
								?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $filter['label'] ); ?></option>
								<?php
							endforeach;
							?>
							<option value="custom"><?php esc_attr_e( 'Custom', 'wpsc-reports' ); ?></option>
						</select>
					</div>
					<div class="setting-filter-item wpsc-filter-submit" style="flex-direction: row !important;">
						<button class="wpsc-button small primary margin-right" onclick="wpsc_rp_run('<?php echo esc_attr( $report ); ?>')"><?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
						<button class="wpsc-button small secondary margin-right" onclick="wpsc_print_report()"><?php esc_attr_e( 'Print', 'supportcandy' ); ?></button>
						<div class="wpsc-filter-actions"></div>
					</div>

					<input type="hidden" class="fil_act_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_get_filter_actions' ) ); ?>">
				</div>
			</form>
			<?php
		}

		/**
		 * Get filter actions html
		 *
		 * @return void
		 */
		public static function get_filter_actions() {

			if ( check_ajax_referer( 'wpsc_rp_get_filter_actions', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$report = isset( $_POST['report'] ) ? sanitize_text_field( wp_unslash( $_POST['report'] ) ) : '';
			if ( ! $report ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$section = WPSC_RP_Admin_Submenu::$sections[ $report ];

			$filter = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '';
			if ( is_numeric( $filter ) ) {
				?>
				<span onclick="<?php echo esc_attr( $section['callback'] ); ?>('<?php echo esc_attr( $report ); ?>', '<?php echo esc_attr( wp_create_nonce( $report ) ); ?>');"><?php echo esc_attr( wpsc__( 'Reset', 'supportcandy' ) ); ?></span>
				<div class="action-devider"></div>
				<span onclick="wpsc_rp_get_edit_user_filter(<?php echo esc_attr( $filter ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_get_custom_filter' ) ); ?>');"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></span>
				<div class="action-devider"></div>
				<span onclick="wpsc_rp_delete_user_filter(<?php echo esc_attr( $filter ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_delete_user_filter' ) ); ?>');"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></span>
				<?php
			} elseif ( $filter === 'custom' ) {
				?>
				<span onclick="<?php echo esc_attr( $section['callback'] ); ?>('<?php echo esc_attr( $report ); ?>','<?php echo esc_attr( wp_create_nonce( $report ) ); ?>');"><?php echo esc_attr( wpsc__( 'Reset', 'supportcandy' ) ); ?></span>
				<div class="action-devider"></div>
				<span onclick="wpsc_rp_get_edit_custom_filter('<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_get_custom_filter' ) ); ?>');"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></span>
				<?php
			} else {
				?>
				<span onclick="<?php echo esc_attr( $section['callback'] ); ?>('<?php echo esc_attr( $report ); ?>', '<?php echo esc_attr( wp_create_nonce( $report ) ); ?>');"><?php echo esc_attr( wpsc__( 'Reset', 'supportcandy' ) ); ?></span>
				<?php
			}
			wp_die();
		}

		/**
		 * Get add custom filter modal windnow
		 *
		 * @return void
		 */
		public static function get_custom_filter() {

			if ( check_ajax_referer( 'wpsc_rp_get_custom_filter', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			// check if filters are provided.
			$filters = isset( $_POST['filters'] ) ? sanitize_textarea_field( wp_unslash( $_POST['filters'] ) ) : '';

			// saved filters for this user.
			$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
			if ( ! $saved_filters ) {
				$saved_filters = '';
			}

			// check if filter id is provided.
			$filter_id = isset( $_POST['filter_id'] ) ? intval( $_POST['filter_id'] ) : 0;
			$filter    = isset( $saved_filters[ $filter_id ] ) ? $saved_filters[ $filter_id ] : '';
			if ( $filter ) { // set filters if set.
				$filters = $filter['filters'];
			}

			$ignore_filters = apply_filters( 'wpsc_ignore_report_filters', array( 'id', 'status', 'date_created', 'date_updated', 'date_closed' ) );

			$title = esc_attr__( 'Custom Filter', 'wpsc-reports' );

			ob_start();
			?>
			<form onsubmit="return false;">
				<?php WPSC_Ticket_Conditions::print( 'report_filters', 'wpsc_report_filters', $filters, false, __( 'Filters', 'supportcandy' ) ); ?>
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_rp_apply_custom_filter(this);">
				<?php echo esc_attr( wpsc__( 'Apply', 'supportcandy' ) ); ?>
			</button>
			<?php

			if ( ! $filter_id ) :
				?>

				<button class="wpsc-button small secondary" onclick="wpsc_rp_add_saved_filter(this);">
					<?php echo esc_attr( wpsc__( 'Save & Apply', 'supportcandy' ) ); ?>
				</button>
				<?php

			else :
				?>

				<button class="wpsc-button small secondary" onclick="wpsc_rp_update_saved_filter(this, <?php echo esc_attr( $filter_id ); ?>,'<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_update_saved_filter' ) ); ?>');">
					<?php echo esc_attr( wpsc__( 'Save & Apply', 'supportcandy' ) ); ?>
				</button>
				<?php

			endif;
			?>

			<button class="wpsc-button small secondary" onclick="<?php echo esc_attr( $filters ) ? 'wpsc_close_modal()' : 'wpsc_rp_close_custom_filter_modal()'; ?>;">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response, 200 );
		}

		/**
		 * Saved filter label modal
		 *
		 * @return void
		 */
		public static function get_add_saved_filter() {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$title = esc_attr( wpsc__( 'Add new', 'supportcandy' ) );

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-rp-ts-add-saved-filter">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Label', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input type="text" name="label" autocomplete="off"/>
				</div>
				<input type="hidden" name="action" value="wpsc_rp_set_add_saved_filter">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_rp_set_add_saved_filter(this, '<?php echo esc_attr( wp_create_nonce( 'wpsc_rp_set_add_saved_filter' ) ); ?>');">
				<?php echo esc_attr( wpsc__( 'Save & Apply', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response, 200 );
		}

		/**
		 * Set add-new saved filter
		 *
		 * @return void
		 */
		public static function set_add_saved_filter() {

			if ( check_ajax_referer( 'wpsc_rp_set_add_saved_filter', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $label ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$filters = isset( $_POST['filters'] ) ? sanitize_textarea_field( wp_unslash( $_POST['filters'] ) ) : '';
			if ( ! $filters || $filters == '[]' || ! WPSC_Ticket_Conditions::is_valid_input_conditions( 'wpsc_report_filter_conditions', $filters ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$filters = str_replace( '\n', PHP_EOL, $filters );

			$index         = 1;
			$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
			if ( ! $saved_filters ) {
				$saved_filters = array();
			} else {
				end( $saved_filters );
				$last_index = key( $saved_filters );
				reset( $saved_filters );
				$index = intval( $last_index ) + 1;
			}
			$saved_filters[ $index ] = array(
				'label'   => $label,
				'filters' => $filters,
			);
			update_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', $saved_filters );
			?>

			<option value=""><?php echo esc_attr( wpsc__( 'All', 'supportcandy' ) ); ?></option>
			<?php
			foreach ( $saved_filters as $key => $filter ) :
				$selected = $key == $index ? 'selected' : ''
				?>
				<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $filter['label'] ); ?></option>
				<?php
			endforeach;
			?>
			<option value="custom"><?php esc_attr_e( 'Custom', 'wpsc-reports' ); ?></option>
			<?php
			wp_die();
		}

		/**
		 * Update existing filter
		 *
		 * @return void
		 */
		public static function update_saved_filter() {

			if ( check_ajax_referer( 'wpsc_rp_update_saved_filter', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
			if ( ! $saved_filters ) {
				$saved_filters = array();
			}

			$filter_id = isset( $_POST['filter_id'] ) ? intval( $_POST['filter_id'] ) : 0;
			if ( ! $filter_id ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			if ( ! isset( $saved_filters[ $filter_id ] ) ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$filters = isset( $_POST['filters'] ) ? sanitize_textarea_field( wp_unslash( $_POST['filters'] ) ) : '';
			if ( ! $filters ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$saved_filters[ $filter_id ]['filters'] = $filters;
			update_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', $saved_filters );
			wp_die();
		}

		/**
		 * Delete saved filter
		 *
		 * @return void
		 */
		public static function delete_saved_filter() {

			if ( check_ajax_referer( 'wpsc_rp_delete_user_filter', '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
			if ( ! $saved_filters ) {
				$saved_filters = array();
			}

			$filter_id = isset( $_POST['filter_id'] ) ? intval( $_POST['filter_id'] ) : 0;
			if ( ! $filter_id ) {
				wp_send_json_error( 'Bad request', 400 );
			}
			if ( ! isset( $saved_filters[ $filter_id ] ) ) {
				wp_send_json_error( 'Bad request', 400 );
			}

			unset( $saved_filters[ $filter_id ] );
			update_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', $saved_filters );
			?>

			<option selected value=""><?php echo esc_attr( wpsc__( 'All', 'supportcandy' ) ); ?></option>
			<?php
			foreach ( $saved_filters as $key => $filter ) :
				?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $filter['label'] ); ?></option>
				<?php
			endforeach;
			?>
			<option value="custom"><?php esc_attr_e( 'Custom', 'wpsc-reports' ); ?></option>
			<?php
			wp_die();
		}
	}
endif;

WPSC_RP_Filters::init();
