<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Submenu' ) ) :

	final class WPSC_SF_Submenu {

		/**
		 * Initialize the class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_before_setting_admin_menu', array( __CLASS__, 'load_admin_menu' ) );
			add_action( 'wp_ajax_wpsc_get_customer_feedbacks', array( __CLASS__, 'get_customer_feedbacks' ) );

			// export feedbacks.
			add_action( 'wp_ajax_wpsc_get_export_feedbacks', array( __CLASS__, 'export_feedbacks' ) );
			add_action( 'init', array( __CLASS__, 'download_rating_export_file' ) );
		}

		/**
		 * Load admin submenu
		 *
		 * @return void
		 */
		public static function load_admin_menu() {

			add_submenu_page(
				'wpsc-tickets',
				esc_attr__( 'Customer Feedback', 'wpsc-sf' ),
				esc_attr__( 'Customer Feedback', 'wpsc-sf' ),
				'manage_options',
				'wpsc-customer-feedback',
				array( __CLASS__, 'layout' )
			);
		}

		/**
		 * Customer feedback admin submenu layout
		 *
		 * @return void
		 */
		public static function layout() {
			?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div id="wpsc-container">
					<div class="wpsc-setting-header">
						<h2><?php esc_attr_e( 'Customer Feedback', 'wpsc-sf' ); ?></h2>
					</div>

					<div class="wpsc-setting-section-body">
						
						<div class="wpsc-feedback-filter-container">
							<div class="wpsc-filter-container">
								<div class="wpsc-filter-item" style="min-width: 200px;">
									<select id="wpsc-input-sort-feedback" class="wpsc-input-sort-feedback" name="sort-feedback">
										<option value="0"><?php esc_attr_e( 'All ratings', 'wpsc-sf' ); ?></option>
										<?php
										$ratings = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
										foreach ( $ratings as $rating ) {
											?>
											<option value="<?php echo esc_attr( $rating->id ); ?>"><?php echo esc_attr( $rating->name ); ?></option>
											<?php
										}
										?>
									</select>
								</div>
								<div class="wpsc-filter-item" style="min-width: 250px;">
									<div class="wpsc-input-close-group">
										<input 
												type="text" 
												id="wpsc-sf-date-picker" 
												name="wpsc-sf-date-picker"
												placeholder="<?php esc_attr_e( 'Start To End', 'wpsc-sf' ); ?>"
												value="" 
												autocomplete="off"
												style="text-align: center;"/>
										<span onclick="wpsc_clear_date(this);"><?php WPSC_Icons::get( 'times' ); ?></span>
									</div>
								</div>
								<div class="wpsc-filter-submit">
									<button type="button" class="wpsc-button normal primary" onclick="wpsc_filter_customer_feedbacks()"><?php esc_attr_e( 'Apply', 'wpsc-sf' ); ?></button>
									<button style="margin-left: 5px;"
										class="wpsc-button normal primary" 
										onclick="wpsc_get_export_feedbacks('<?php echo esc_attr( wp_create_nonce( 'wpsc_get_export_feedbacks' ) ); ?>');">
										<?php esc_attr_e( 'Export', 'wpsc-sf' ); ?>
									</button>
									<div class="wpsc-filter-actions" style="margin-left: 5px;">
									<span class="wpsc-link">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsc-customer-feedback' ) ); ?>"><?php echo esc_attr__( 'Reset', 'supportcandy' ); ?></a>
									</span>
									</div>
								</div>
							</div>
						</div>
						<div class="wpsc-cf-count-card" id="wpsc-cf-count-card"></div>

						<table class="wpsc-sf-feedback wpsc-setting-tbl">
							<thead>
								<tr>
									<th><?php esc_attr_e( 'Ticket', 'supportcandy' ); ?></th>
									<th><?php echo esc_attr( wpsc__( 'Customer', 'supportcandy' ) ); ?></th>
									<th><?php esc_attr_e( 'Rating', 'wpsc-sf' ); ?></th>
									<th><?php esc_attr_e( 'Feedback', 'wpsc-sf' ); ?></th>
									<th><?php echo esc_attr( wpsc__( 'Time ago' ) ); ?></th>
								</tr>
							</thead>
						</table>

					</div>

					<script>
						jQuery(document).ready(function() {

							jQuery('select.wpsc-input-sort-feedback').selectWoo({});

							jQuery('#wpsc-sf-date-picker').flatpickr({
								mode: "range",
								maxDate: "today",
								dateFormat: "Y-m-d",
							});

							wpsc_filter_customer_feedbacks();
						});

						function wpsc_filter_customer_feedbacks() {

							date_range = jQuery('#wpsc-sf-date-picker').val();
							rating = jQuery('#wpsc-input-sort-feedback').val();
							jQuery('.wpsc-sf-feedback').dataTable({
									processing: true,
									serverSide: true,
									serverMethod: 'post',
									ajax: { 
										url: supportcandy.ajax_url,
										data: {
												'action': 'wpsc_get_customer_feedbacks',
												date_range,
												rating,
												'_ajax_nonce': '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_customer_feedbacks' ) ); ?>'
											}									
									},
									'columns': [
										{ data: 'ticket' },
										{ data: 'customer' },
										{ data: 'rating' },
										{ data: 'feedback' },
										{ data: 'date' },
									],
									'bDestroy': true,
									'ordering': false,
									'searching': false,
									'bLengthChange': false,
									'pageLength': 20,
									columnDefs: [ 
										{ targets: '_all', className: 'dt-left' },
										{ targets: 3, width: 500 }
									],
									"initComplete":function( settings, json){
											jQuery('#wpsc-cf-count-card').html(json.rating_counts);
									},
									language: supportcandy.translations.datatables
								});
						}
					</script>
				</div>
			</div>
			<?php
		}

		/**
		 * Get custom filter feedbacks function
		 *
		 * @return void
		 */
		public static function get_customer_feedbacks() {

			if ( check_ajax_referer( 'wpsc_get_customer_feedbacks', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$draw       = isset( $_POST['draw'] ) ? intval( $_POST['draw'] ) : 1;
			$start      = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 1;
			$rowperpage = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : 20;
			$page_no    = ( $start / $rowperpage ) + 1;
			$sid        = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;

			$date_range = isset( $_POST['date_range'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range'] ) ) : array();
			$date_range = ! empty( $date_range ) ? explode( 'to', $date_range ) : array();

			$ratings    = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
			$rating_ids = array();
			if ( $sid ) {
				$rating_ids = array( $sid );
			} else {
				foreach ( $ratings as $rating ) {
					$rating_ids[] = $rating->id;
				}
			}

			$filters = array(
				'parent-filter'  => 'all',
				'orderby'        => 'sf_date',
				'order'          => 'DESC',
				'page_no'        => $page_no,
				'items_per_page' => $rowperpage,
			);

			$filters['meta_query'] = array(
				'relation' => 'AND',
				array(
					'slug'    => 'rating',
					'compare' => 'IN',
					'val'     => $rating_ids,
				),
			);

			$date_range_array = array();
			if ( $date_range ) {
				$from_date = $date_range[0];
				$to_date = isset( $date_range[1] ) ? $date_range[1] : $date_range[0];

				if ( preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $from_date ) ) {
					$from_date_range = $from_date;
					$to_date_range = isset( $date_range[1] ) ? $to_date : $from_date;
				} else {
					$from_date_range = $from_date . ' 00:00:00';
					$to_date_range = isset( $date_range[1] ) ? $to_date . ' 23:59:59' : $from_date . ' 23:59:59';
				}

				$date_range_array = array(
					'slug'    => 'sf_date',
					'compare' => 'BETWEEN',
					'val'     => array(
						'operand_val_1' => ( new DateTime( $from_date_range ) )->format( 'Y-m-d H:i:s' ),
						'operand_val_2' => ( new DateTime( $to_date_range ) )->format( 'Y-m-d H:i:s' ),
					),
				);
			}
			if ( ! empty( $date_range_array ) ) {
				$filters['meta_query'][] = $date_range_array;
			}
			$tickets = WPSC_Ticket::find( $filters );

			$data = array();
			$now  = new DateTime();
			foreach ( $tickets['results'] as $ticket ) {

				$rating = '<span class="wpsc-tag" style="color:' . $ticket->rating->color . ';background-color:' . $ticket->rating->bg_color . ';">' . $ticket->rating->name . '</span>';

				$date_str = '';
				if ( $ticket->sf_date && $ticket->sf_date != '0000-00-00 00:00:00' ) {
					$date_str = WPSC_Functions::date_interval_highest_unit_ago( $ticket->sf_date->diff( $now ) );
				}

				$url       = admin_url( 'admin.php?page=wpsc-tickets&section=ticket-list&id=' . $ticket->id );
				$ticket_id = '<a class="wpsc-link" href="' . $url . '" target="__blank">#' . $ticket->id . '</a>';

				$data[] = array(
					'ticket'   => $ticket_id,
					'customer' => stripslashes( $ticket->customer->name ),
					'rating'   => $rating,
					'feedback' => nl2br( stripslashes( $ticket->sf_feedback ) ),
					'date'     => $date_str,
				);
			}

			// get rating counts.
			$filters = array(
				'parent-filter'  => 'all',
				'items_per_page' => 0,
			);
			$filters['meta_query'] = array(
				'relation' => 'AND',
				array(
					'slug'    => 'rating',
					'compare' => 'IN',
					'val'     => $rating_ids,
				),
			);

			$date_range_array = array();
			if ( $date_range ) {
				$from_date = $date_range[0];
				$to_date = isset( $date_range[1] ) ? $date_range[1] : $date_range[0];

				if ( preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $from_date ) ) {
					$from_date_range = $from_date;
					$to_date_range = isset( $date_range[1] ) ? $to_date : $from_date;
				} else {
					$from_date_range = $from_date . ' 00:00:00';
					$to_date_range = isset( $date_range[1] ) ? $to_date . ' 23:59:59' : $from_date . ' 23:59:59';
				}

				$date_range_array = array(
					'slug'    => 'sf_date',
					'compare' => 'BETWEEN',
					'val'     => array(
						'operand_val_1' => ( new DateTime( $from_date_range ) )->format( 'Y-m-d H:i:s' ),
						'operand_val_2' => ( new DateTime( $to_date_range ) )->format( 'Y-m-d H:i:s' ),
					),
				);
			}
			if ( ! empty( $date_range_array ) ) {
				$filters['meta_query'][] = $date_range_array;
			}
			$tickets_ratings = WPSC_Ticket::find( $filters )['results'];

			$ids = array();
			$counts = array();
			foreach ( $tickets_ratings as $ticket ) {
				$counts[] = $ticket->rating->id;
			}
			$counts = array_count_values( $counts );

			$rating_counts = '';
			foreach ( $ratings as $key => $rating ) {
				$count = isset( $counts[ $rating->id ] ) ? $counts[ $rating->id ] : 0;
				$rating_counts .= '<div class="wpsc-cf-card-item" style="color: ' . $rating->bg_color . ';border:1px solid ' . $rating->bg_color . '">' . $rating->name . ' : ' . $count . '</div>';
			}
			$rating_counts .= '<div class="wpsc-cf-card-item" style="background-color: #c4c4c4;color: #fff;">Total : ' . array_sum( $counts ) . '</div>';

			$response = array(
				'draw'                 => intval( $draw ),
				'iTotalRecords'        => $tickets['total_items'],
				'iTotalDisplayRecords' => $tickets['total_items'],
				'data'                 => $data,
				'rating_counts'        => $rating_counts,
			);
			wp_send_json( $response );
		}

		/**
		 * Export feedbacks
		 *
		 * @return void
		 */
		public static function export_feedbacks() {

			if ( check_ajax_referer( 'wpsc_get_export_feedbacks', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			$date_range = isset( $_POST['date_range'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range'] ) ) : array();
			$date_range = ! empty( $date_range ) ? explode( 'to', $date_range ) : array();

			$sid = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;

			$ratings    = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
			$rating_ids = array();
			if ( $sid ) {
				$rating_ids = array( $sid );
			} else {
				foreach ( $ratings as $rating ) {
					$rating_ids[] = $rating->id;
				}
			}

			$current_user = WPSC_Current_User::$current_user;

			$unique_id      = wp_rand( 111111111, 999999999 );
			$file_name      = $unique_id . '.csv';
			$path_to_export = get_temp_dir() . $file_name;

			$url_to_export = add_query_arg(
				array(
					'download-rating-export' => $unique_id,
					'_ajax_nonce'            => wp_create_nonce( 'download-rating-export' ),
				),
				get_home_url()
			);

			$fp = fopen( $path_to_export, 'w' ); // phpcs:ignore
			$cf_subject  = WPSC_Custom_Field::get_cf_by_slug( 'subject' );
			$cf_category = WPSC_Custom_Field::get_cf_by_slug( 'category' );
			$cf_assigned_agent = WPSC_Custom_Field::get_cf_by_slug( 'assigned_agent' );

			$column_name = array( 'Ticket', $cf_subject->name, $cf_category->name, $cf_assigned_agent->name, 'Rating', 'Feedback', 'Date' );
			fputcsv( $fp, $column_name );

			$filters = array(
				'parent-filter'  => 'all',
				'orderby'        => 'sf_date',
				'order'          => 'DESC',
				'items_per_page' => 0,
			);

			$filters['meta_query'] = array(
				'relation' => 'AND',
				array(
					'slug'    => 'rating',
					'compare' => 'IN',
					'val'     => $rating_ids,
				),
			);

			$date_range_array = array();
			if ( $date_range ) {
				$from_date = $date_range[0];
				$to_date = isset( $date_range[1] ) ? $date_range[1] : $date_range[0];

				if ( preg_match( '/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $from_date ) ) {
					$from_date_range = $from_date;
					$to_date_range = isset( $date_range[1] ) ? $to_date : $from_date;
				} else {
					$from_date_range = $from_date . ' 00:00:00';
					$to_date_range = isset( $date_range[1] ) ? $to_date . ' 23:59:59' : $from_date . ' 23:59:59';
				}

				$date_range_array = array(
					'slug'    => 'sf_date',
					'compare' => 'BETWEEN',
					'val'     => array(
						'operand_val_1' => ( new DateTime( $from_date_range ) )->format( 'Y-m-d H:i:s' ),
						'operand_val_2' => ( new DateTime( $to_date_range ) )->format( 'Y-m-d H:i:s' ),
					),
				);
			}
			if ( ! empty( $date_range_array ) ) {
				$filters['meta_query'][] = $date_range_array;
			}
			$tickets = WPSC_Ticket::find( $filters )['results'];

			$column_value = array();
			foreach ( $tickets as $ticket ) {
				$agents = '';
				$agents = array();
				foreach ( $ticket->assigned_agent as $assigned_agent ) {
					$agents [] = $assigned_agent->name;
				}

				$date_str = '';
				if ( $ticket->sf_date && $ticket->sf_date != '0000-00-00 00:00:00' ) {
					$tz   = wp_timezone();
					$date = $ticket->sf_date;
					$date->setTimezone( $tz );
					$date_str = $date->format( 'Y-m-d H:i:s' );
				}
				$agents = implode( ',', $agents );
				$column_value = array(
					'Ticket'                 => $ticket->id,
					$cf_subject->name        => $ticket->subject,
					$cf_category->name       => $ticket->category->name,
					$cf_assigned_agent->name => $agents,
					'Rating'                 => $ticket->rating->name,
					'Feedback'               => $ticket->sf_feedback,
					'Date'                   => $date_str,
				);
				fputcsv( $fp, $column_value );
			}
			fclose( $fp ); // phpcs:ignore

			echo '{"url_to_export":"' . esc_url_raw( $url_to_export ) . '"}';

			wp_die();
		}

		/**
		 * Download rating export file
		 *
		 * @return void
		 */
		public static function download_rating_export_file() {

			if ( isset( $_GET['download-rating-export'] ) && isset( $_GET['_ajax_nonce'] ) ) {

				$nonce = sanitize_text_field( wp_unslash( $_GET['_ajax_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'download-rating-export' ) ) {
					exit( 0 );
				}

				$file_name = intval( $_GET['download-rating-export'] );
				if ( ! $file_name ) {
					exit( 0 );
				}

				$file_name      = $file_name . '.csv';
				$path_to_export = get_temp_dir() . $file_name;

				header( 'Content-Type: application/force-download' );
				header( 'Content-Description: File Transfer' );
				header( 'Cache-Control: public' );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Content-Disposition: attachment;filename="' . $file_name . '"' );
				header( 'Content-Length: ' . filesize( $path_to_export ) );
				flush();
				readfile( $path_to_export ); // phpcs:ignore

				$fd = wp_delete_file( $path_to_export );

				exit( 0 );
			}
		}
	}
endif;

WPSC_SF_Submenu::init();
