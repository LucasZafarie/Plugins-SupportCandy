<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Closing_Delay' ) ) :

	final class WPSC_RP_Closing_Delay {

		/**
		 * Initialize this class
		 */
		public static function init() {

			add_action( 'wp_ajax_wpsc_rp_get_closing_delay', array( __CLASS__, 'layout' ) );
			add_action( 'wp_ajax_wpsc_rp_run_cd_report', array( __CLASS__, 'run_cd_reports' ) );
		}

		/**
		 * Print closing delay report layout
		 *
		 * @return void
		 */
		public static function layout() {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				wp_die();
			}?>

			<div class="wpsc-setting-header">
				<h2><?php esc_attr_e( 'Atraso no Fechamento do Ticket', 'wpsc-reports' ); ?></h2>
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
			<?php WPSC_RP_Filters::layout( 'closing_delay' ); ?>
			<div class="wpsc-setting-section-body">
				<div class="wpscPrograssLoaderContainer">
					<div class="wpscPrograssLoader">
						<strong>0<small>%</small></strong>
					</div>
				</div>
				<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>
				<table class="wpsc-rp-tbl">
					<tr>
						<th><?php esc_attr_e( 'Average Closing Delay (Days)', 'wpsc-reports' ); ?></th>
					</tr>
					<tr>
						<td class="cd"></td>
					</tr>
				</table>
			</div>
			<script>jQuery('form.wpsc-report-filters').find('select[name=filter]').val('').trigger('change');</script>
			<?php
			wp_die();
		}

		/**
		 * Run closing delay reports
		 *
		 * @return void
		 */
		public static function run_cd_reports() {

			if ( check_ajax_referer( 'closing_delay', '_ajax_nonce', false ) !== 1 ) {
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

			$duration_time = isset( $_POST['duration_type'] ) ? sanitize_text_field( wp_unslash( $_POST['duration_type'] ) ) : '';
			if (
				! $duration_time ||
				! in_array( $duration_time, array( 'day', 'days', 'weeks', 'months', 'years' ) )
			) {
				wp_send_json_error( 'Bad request', 400 );
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

			// label.
			switch ( $duration_time ) {

				case 'day':
					$response['label'] = sprintf(
						'%1$s - %2$s',
						( new DateTime( $from_date ) )->format( 'H:i' ),
						( new DateTime( $to_date ) )->format( 'H:i' )
					);
					break;

				case 'days':
					$response['label'] = ( new DateTime( $from_date ) )->format( 'Y-m-d' );
					break;

				case 'weeks':
					$response['label'] = sprintf(
						'%1$s - %2$s',
						( new DateTime( $from_date ) )->format( 'M d' ),
						( new DateTime( $to_date ) )->format( 'M d' )
					);
					break;

				case 'months':
					$response['label'] = ( new DateTime( $from_date ) )->format( 'F Y' );
					break;

				case 'years':
					$response['label'] = ( new DateTime( $from_date ) )->format( 'Y' );
					break;
			}

			// tickets closed.
			$closed_meta_query  = array(
				array(
					'slug'    => 'date_closed',
					'compare' => 'BETWEEN',
					'val'     => array(
						'operand_val_1' => ( new DateTime( $from_date ) )->format( 'Y-m-d H:i:s' ),
						'operand_val_2' => ( new DateTime( $to_date ) )->format( 'Y-m-d H:i:s' ),
					),
				),
			);
			$args['system_query'] = $current_user->get_tl_system_query( $filters );
			$args['meta_query'] = array_merge( $meta_query, $closed_meta_query );
			$results            = WPSC_Ticket::find( $args )['results'];

			// calculate closing delay and average closing delay.
			$closing_delay       = 0;
			$total_closing_delay = 0;
			$count               = 0;
			foreach ( $results as $ticket ) {

				$closing_delay += intval( $ticket->cd );
				++$count;
			}

			// total closing delays (days).
			if ( $closing_delay ) {

				$temp_int            = (int) ( $closing_delay / 1440 );
				$temp_decimal        = $closing_delay % 1440;
				$total_closing_delay = $temp_int + ( $temp_decimal / 1440 );
			}

			// average for current iteration (days).
			if ( $count ) {

				$closing_delay = ceil( $closing_delay / $count );
				$temp_int      = (int) ( $closing_delay / 1440 );
				$temp_decimal  = $closing_delay % 1440;
				$closing_delay = $temp_int + ( $temp_decimal / 1440 );
			}

			$response['cd']                = round( $closing_delay, 2 );
			$response['totalClosingDelay'] = round( $total_closing_delay, 2 );
			$response['count']             = $count;
			wp_send_json( $response, 200 );
		}
	}
endif;

WPSC_RP_Closing_Delay::init();
