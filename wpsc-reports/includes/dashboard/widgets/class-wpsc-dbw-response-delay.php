<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DBW_Response_Delay' ) ) :

	final class WPSC_DBW_Response_Delay {

		/**
		 * Widget slug
		 *
		 * @var string
		 */
		public static $widget = 'response-delay';

		/**
		 * Initialize this class
		 */
		public static function init() {
		}

		/**
		 * Response delay report
		 *
		 * @param $slug   $slug - slug name.
		 * @param $widget $widget - widget array.
		 * @return void
		 */
		public static function print_dashboard_widget( $slug, $widget ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widget['allowed-agent-roles'] )
						&& $current_user->agent->has_cap( 'view-reports' ) )
			) {
				return;
			}
			$db_gs = get_option( 'wpsc-db-gs-settings' );
			?>
			<div class="wpsc-dash-widget wpsc-dash-widget-mid wpsc-<?php echo esc_attr( $slug ); ?>">
				<div class="wpsc-dash-widget-header">
					<div class="wpsc-dashboard-widget-icon-header">
						<?php WPSC_Icons::get( 'line-graph' ); ?>
						<span>
							Tempo de resposta
						</span>
					</div>
					<div class="wpsc-dash-widget-actions">
					</div>
				</div>
				<div class="wpsc-dash-widget-content wpsc-dbw-line-graph" id="wpsc-dash-response-delay">
					<canvas id="wpsc-dbw-response-delay"></canvas>
				</div>
			</div>
			<script>
				wpsc_get_dbw_response_delay();
				async function wpsc_get_dbw_response_delay() { 
					jQuery('#wpsc-dash-response-delay').html( supportcandy.loader_html );
					var dates = wpsc_db_set_filter_duration_dates('<?php echo esc_attr( $db_gs['default-date-range'] ); ?>');
					var startDate = new Date(dates.from);
					var endDate = new Date(dates.to);
					let report = {
						batches: wpsc_rp_get_baches(startDate, endDate),
						labels: [],
						frd: [],
						totalFrDelay: [],
						ard: [],
						totalArDelay: [],
						count: []
					}
					var batchesLength = report.batches.length;
					for (let i = 0; i < batchesLength; i++) {

						var promises = [];

						var batchLength = report.batches[i].length;
						for (let j = 0; j < batchLength; j++) {

							var duration = report.batches[i][j];
							promises.push(
								new Promise(
									function (resolve, reject) {

										dataform = new FormData();
										dataform.append( 'action', 'wpsc_rp_run_rd_report' );
										dataform.append( 'from_date', duration.fromDate );
										dataform.append( 'to_date', duration.toDate );
										dataform.append( 'duration_type', duration.durationType );
										dataform.append( '_ajax_nonce', '<?php echo esc_attr( wp_create_nonce( 'response_delay' ) ); ?>' );
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
												resolve( res );
											}
										).fail(
											function () {
												reject( new Error() );
											}
										);
									}
								)
							);
						}

						var isValidResults = true;
						var results        = await Promise.all( promises.map( p => p.catch( e => e ) ) );
						jQuery.each(
							results,
							function (index, response) {
								if (response instanceof Error) {
									isValidResults = false;
									return false;
								}
								report.labels.push( response.label );
								report.frd.push( response.frd );
								report.totalFrDelay.push( response.totalFrDelay );
								report.ard.push( response.ard );
								report.totalArDelay.push( response.totalArDelay );
								report.count.push( response.count );
							}
						);

						if ( ! isValidResults) {
							jQuery( '#wpsc-dash-response-delay' ).text( 'Something went wrong!' );
							return;
						}
					}

					var data   = {
						labels: report.labels,
						datasets: [
							{
								label: 'Atraso na Primeira Resposta',
								backgroundColor: '#e74c3c',
								borderColor: '#e74c3c',
								data: report.frd
						},
							{
								label: 'Atraso Médio das Respostas',
								backgroundColor: '#2980b9',
								borderColor: '#2980b9',
								data: report.ard
						}
						]
					};
					var config = {
						type: 'line',
						data,
						options: {
							scales: {
								y: {
									beginAtZero: true,
									title: {
										display: true,
										'text': 'Horas'
									}
								}
							}
						}
					};
					jQuery('#wpsc-dash-response-delay').html( '<canvas id="wpsc-dbw-response-delay"></canvas>' );
					new Chart(
						document.getElementById( 'wpsc-dbw-response-delay' ),
						config
					);
				}
			</script>
			<?php
		}
	}
endif;
WPSC_DBW_Response_Delay::init();
