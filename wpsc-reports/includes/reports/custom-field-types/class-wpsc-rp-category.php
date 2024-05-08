<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Category' ) ) :

	final class WPSC_RP_Category {

		/**
		 * Initialize this class
		 */
		public static function init() {

			add_filter( 'wpsc_reports_sections', array( __CLASS__, 'add_section' ) );
			add_action( 'wp_ajax_wpsc_rp_get_category', array( __CLASS__, 'layout' ) );
			add_action( 'wp_ajax_wpsc_rp_run_category_report', array( __CLASS__, 'run_category_reports' ) );
		}

		/**
		 * Add category menu in reports
		 *
		 * @param array $sections - reports menu.
		 * @return array
		 */
		public static function add_section( $sections ) {

			$settings = get_option( 'wpsc-rp-settings' );
			foreach ( $settings['cf-reports'] as $id ) {
				$cf = new WPSC_Custom_Field( $id );
				if ( $cf->id && class_exists( $cf->type ) && $cf->type::$slug == 'df_category' ) {
					$sections[ $cf->slug ] = array(
						'slug'     => $cf->slug,
						'icon'     => 'chart-bar',
						'label'    => $cf->name,
						'callback' => 'wpsc_rp_get_category',
						'run'      => array(
							'shortname' => 'cfcat',
							'function'  => 'wpsc_rp_run_category_report',
						),
					);
				}
			}

			return $sections;
		}

		/**
		 * Print category report layout
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

			$cf_slug = isset( $_POST['cf_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_slug'] ) ) : '';
			if ( ! $cf_slug ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
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
		 * Run category report
		 *
		 * @return void
		 */
		public static function run_category_reports() {

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

			$categories = WPSC_Category::find( array( 'items_per_page' => 0 ) )['results'];

			$records = array();
			foreach ( $categories as $key => $category ) {
				$records[ $category->name ] = 0;
			}

			if ( $results['total_items'] ) {
				foreach ( $results['results'] as $ticket ) {
					if ( $ticket->$cf_slug ) {
						$records[ $ticket->$cf_slug->name ] = $records[ $ticket->$cf_slug->name ] + 1;
					}
				}
			}

			wp_send_json( $records, 200 );
		}
	}
endif;

WPSC_RP_Category::init();
