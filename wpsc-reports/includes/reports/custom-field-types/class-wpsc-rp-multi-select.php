<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Multi_Select' ) ) :

	final class WPSC_RP_Multi_Select {

		/**
		 * Initialize this class
		 */
		public static function init() {

			add_filter( 'wpsc_reports_sections', array( __CLASS__, 'add_section' ) );
			add_action( 'wp_ajax_wpsc_rp_get_multi_select', array( __CLASS__, 'layout' ) );
			add_action( 'wp_ajax_wpsc_rp_run_cfms_report', array( __CLASS__, 'run_cfms_reports' ) );
		}

		/**
		 * Add custom field menu in reports
		 *
		 * @param array $sections - reports menu.
		 * @return array
		 */
		public static function add_section( $sections ) {

			$settings = get_option( 'wpsc-rp-settings' );
			foreach ( $settings['cf-reports'] as $id ) {
				$cf = new WPSC_Custom_Field( $id );
				if ( $cf->id && class_exists( $cf->type ) && $cf->type::$slug == 'cf_multi_select' ) {
					$sections[ $cf->slug ] = array(
						'slug'     => $cf->slug,
						'icon'     => 'chart-bar',
						'label'    => $cf->name,
						'callback' => 'wpsc_rp_get_cf_multi_select',
						'run'      => array(
							'shortname' => 'cfms',
							'function'  => 'wpsc_rp_run_cfms_report',
						),
					);
				}
			}

			return $sections;
		}

		/**
		 * Print single select type custom field report layout
		 *
		 * @return void
		 */
		public static function layout() {

			$cf_slug = isset( $_POST['cf_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_slug'] ) ) : '';
			if ( ! $cf_slug ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			if ( check_ajax_referer( $cf_slug, '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_die();
			}

			$cf = WPSC_Custom_Field::get_cf_by_slug( $cf_slug );
			if ( ! $cf ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}?>

			<div class="wpsc-setting-header">
				<h2><?php echo esc_attr( $cf->name ); ?></h2>
			</div>
			<div class="wpsc-setting-filter-container">
				<?php WPSC_RP_Filters::get_durations(); ?>
				<div class="setting-filter-item from-date" style="display: none;">
					<span class="label"><?php esc_attr_e( 'From Date', 'wpsc-reports' ); ?></span>
					<input type="text" name="from-date" value="">
				</div>
				<div class="setting-filter-item to-date" style="display: none;">
					<span class="label"><?php esc_attr_e( 'To Date', 'wpsc-reports' ); ?></span>
					<input type="text" name="to-date" value="">
				</div>
				<script>
					jQuery('select[name=duration]').trigger('change');
					jQuery('.setting-filter-item.from-date').find('input').flatpickr();
					jQuery('.setting-filter-item.from-date').find('input').change(function(){
						let minDate = jQuery(this).val();
						jQuery('.setting-filter-item.to-date').find('input').flatpickr({
							minDate,
							defaultDate: minDate
						});
					});
				</script>
			</div>
			<?php WPSC_RP_Filters::layout( $cf->slug ); ?>
			<div class="wpsc-setting-section-body">
				<div class="wpscPrograssLoaderContainer"></div>
				<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>
			</div>
			<script>
				jQuery('form.wpsc-report-filters').find('select[name=filter]').val('').trigger('change');
			</script>
			<?php
			wp_die();
		}

		/**
		 * Run multi select report
		 *
		 * @return void
		 */
		public static function run_cfms_reports() {

			$cf_slug = isset( $_POST['cf_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_slug'] ) ) : '';
			if ( ! $cf_slug ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			if ( check_ajax_referer( $cf_slug, '_ajax_nonce', false ) !== 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_send_json_error( __( 'Unauthorized', 'supportcandy' ), 401 );
			}

			$from_date = isset( $_POST['from_date'] ) ? sanitize_text_field( wp_unslash( $_POST['from_date'] ) ) : '';
			if (
				! $from_date ||
				! preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $from_date )
			) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$to_date = isset( $_POST['to_date'] ) ? sanitize_text_field( wp_unslash( $_POST['to_date'] ) ) : '';
			if (
				! $to_date ||
				! preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $to_date )
			) {
				wp_send_json_error( 'Bad request', 400 );
			}

			$cf = WPSC_Custom_Field::get_cf_by_slug( $cf_slug );
			if ( ! $cf ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			// current filter (default 'All').
			$filter = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '';

			// custom filters.
			$filters = isset( $_POST['filters'] ) ? stripslashes( sanitize_textarea_field( wp_unslash( $_POST['filters'] ) ) ) : '';

			// filter arguments.
			$args = array(
				'is_active'      => 1,
				'items_per_page' => 0,
			);

			// meta query.
			$meta_query = array( 'relation' => 'AND' );

			// custom filters (if any).
			if ( $filter == 'custom' ) {
				if ( ! $filters ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$meta_query = array_merge( $meta_query, WPSC_Ticket_Conditions::get_meta_query( $filters ) );
			}

			// saved filter (if applied).
			if ( is_numeric( $filter ) ) {
				$saved_filters = get_user_meta( $current_user->user->ID, get_current_blog_id() . '-wpsc-rp-saved-filters', true );
				if ( ! isset( $saved_filters[ intval( $filter ) ] ) ) {
					wp_send_json_error( 'Bad Request', 400 );
				}
				$filter_str  = $saved_filters[ intval( $filter ) ]['filters'];
				$filter_str  = str_replace( '^^', '\n', $filter_str );
				$meta_query  = array_merge( $meta_query, WPSC_Ticket_Conditions::get_meta_query( $filter_str ) );
			}

			$response = array();
			$filters = array();

			// created.
			$created_meta_query = array(
				array(
					'slug'    => 'date_created',
					'compare' => 'BETWEEN',
					'val'     => array(
						'operand_val_1' => ( new DateTime( $from_date ) )->format( 'Y-m-d H:i:s' ),
						'operand_val_2' => ( new DateTime( $to_date ) )->format( 'Y-m-d H:i:s' ),
					),
				),
			);
			$args['system_query'] = $current_user->get_tl_system_query( $filters );
			$args['meta_query'] = array_merge( $meta_query, $created_meta_query );
			$results            = WPSC_Ticket::find( $args );

			$records = array();
			foreach ( $cf->get_options() as $key => $option ) {
				$records[ $option->name ] = 0;
			}

			if ( $results['total_items'] ) {
				foreach ( $results['results'] as $ticket ) {
					if ( $ticket->$cf_slug ) {
						foreach ( $ticket->$cf_slug as $key => $opt ) {
							$records[ $opt->name ] = $records[ $opt->name ] + 1;
						}
					}
				}
			}

			wp_send_json( $records, 200 );
		}

		/**
		 * Multi select report.
		 *
		 * @param $slug   $slug - slug name.
		 * @param $widget $widget - widget array.
		 * @return void
		 */
		public static function print_dashboard_widget( $slug, $widget ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widget['allowed-agent-roles'] ) )
			) {
				return;
			}

			$cf = WPSC_Custom_Field::get_cf_by_slug( $slug );
			if ( ! $cf->id ) {
				return;
			}
			?>
			<div class="wpsc-dash-widget wpsc-dash-widget-mid wpsc-<?php echo esc_attr( $slug ); ?>">
				<div class="wpsc-dash-widget-header">
					<div class="wpsc-dashboard-widget-icon-header">
						<?php WPSC_Icons::get( 'pie-chart' ); ?>
						<span>
							<?php
							$title = $widget['title'] ? WPSC_Translations::get( 'wpsc-dashboard-widget-' . $slug, stripslashes( htmlspecialchars( $widget['title'] ) ) ) : stripslashes( htmlspecialchars( $widget['title'] ) );
							echo esc_attr( $title );
							?>
						</span>
					</div>
					<div class="wpsc-dash-widget-actions">
					</div>
				</div>
				<div class="wpsc-dash-widget-content" id="wpsc-dash-cf-<?php echo esc_attr( $slug ); ?>">
					<canvas id="wpsc-dbw-cf-<?php echo esc_attr( $slug ); ?>"></canvas>
				</div>
			</div>
			<script>
				wpsc_get_dbw_rp_checkbox();
				function wpsc_get_dbw_rp_checkbox() {
					jQuery('#wpsc-dash-cf-<?php echo esc_attr( $slug ); ?>').html( supportcandy.loader_html );
					var dates = wpsc_db_set_filter_duration_dates('this-week');
					var startDate = dates.from + ' 00:00:00';
					var endDate = dates.to + ' 23:59:59';

					dataform = new FormData();
					dataform.append( 'action', 'wpsc_rp_run_cfms_report' );
					dataform.append( 'from_date', startDate );
					dataform.append( 'to_date', endDate );
					dataform.append( 'cf_slug', '<?php echo esc_attr( $slug ); ?>' );
					dataform.append( '_ajax_nonce', '<?php echo esc_attr( wp_create_nonce( $slug ) ); ?>' );

					jQuery.ajax(
						{
							url: supportcandy.ajax_url,
							type: 'POST',
							data: dataform,
							processData: false,
							contentType: false
						}
					).done(
						function (res) {
							labels = [];
							count = [];
							color = [];
							for (var key in res) {
								labels.push( key );
								count.push( res[key] );
								color.push( wpsc_generate_random_color() );
							}
							if (count.some(function (value) {
								return value !== 0;
							})) {
								<?php
								if ( $widget['chart-type'] == 'pie' ) {
									?>
									var data = {
										labels: labels,
										datasets: [{
											data: count,
											backgroundColor: color
										}]
									};
									var config = {
										type: 'pie',
										data,
										options: {
											responsive: true,
											maintainAspectRatio: false
										}
									};
									<?php
								} elseif ( $widget['chart-type'] == 'doughnut' ) {
									?>
									var data = {
										labels: labels,
										datasets: [{
											data: count,
											backgroundColor: color
										}]
									};
									var config = {
										type: 'doughnut',
										data,
										options: {
											responsive: true,
											maintainAspectRatio: false
										}
									};
									<?php
								} elseif ( $widget['chart-type'] == 'horizontal-bar' ) {
									?>
									var data = {
										labels: labels,
										datasets: [{
											label: '',
											backgroundColor: color,
											borderColor: color,
											borderWidth: 1,
											data: count
										}]
									};
									var config = {
										type: 'bar',
										data,
										options: {
											plugins: {
												legend: {
													display: false
												}
											},
											indexAxis: 'y',
											responsive: true,
											maintainAspectRatio: false,
											scales: {
												x: {
													beginAtZero: true,
												}
											}
										}
									};
									<?php
								} elseif ( $widget['chart-type'] == 'vertical-bar' ) {
									?>
									var data = {
										labels: labels,
										datasets: [{
											label: '',
											backgroundColor: color,
											borderColor: color,
											borderWidth: 1,
											data: count
										}]
									};
									var config = {
										type: 'bar',
										data,
										options: {
											plugins: {
												legend: {
													display: false
												}
											},
											responsive: true,
											maintainAspectRatio: false,
											scales: {
												y: {
													beginAtZero: true,
												}
											}
										}
									};
									<?php
								}
								?>
								jQuery('#wpsc-dash-cf-<?php echo esc_attr( $slug ); ?>').html( '<canvas id="wpsc-dbw-cf-<?php echo esc_attr( $slug ); ?>" style="height: 350px;"></canvas>' );
								new Chart(
									document.getElementById( 'wpsc-dbw-cf-<?php echo esc_attr( $slug ); ?>' ),
									config
								);
							} else {
								jQuery('#wpsc-dash-cf-<?php echo esc_attr( $slug ); ?>').html('<?php echo esc_attr__( 'Record not found!', 'supportcandy' ); ?>');
							}
						}
					);
				}
			</script>
			<?php
		}

		/**
		 * Get edit dashboard widget values
		 *
		 * @param Array $card - card array.
		 * @return void
		 */
		public static function get_edit_dbw_properties( $card ) {

			?>
			<div class="wpsc-input-group">
				<div class="label-container">
					<label for=""><?php esc_attr_e( 'Chart Type', 'supportcandy' ); ?></label>
				</div>
				<select class="wpsc-chart-type" name="chart-type">
					<option <?php selected( $card['chart-type'], 'pie' ); ?> value="pie"><?php esc_attr_e( 'Pie', 'supportcandy' ); ?></option>
					<option <?php selected( $card['chart-type'], 'doughnut' ); ?> value="doughnut"><?php esc_attr_e( 'Doughnut', 'supportcandy' ); ?></option>
					<option <?php selected( $card['chart-type'], 'horizontal-bar' ); ?> value="horizontal-bar"><?php esc_attr_e( 'Horizontal Bar', 'supportcandy' ); ?></option>
					<option <?php selected( $card['chart-type'], 'vertical-bar' ); ?> value="vertical-bar"><?php esc_attr_e( 'Vertical Bar', 'supportcandy' ); ?></option>
				</select>
			</div>
			<?php
		}
	}
endif;

WPSC_RP_Multi_Select::init();
