<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DBW_Communication_Gap' ) ) :

	final class WPSC_DBW_Communication_Gap {

		/**
		 * Widget slug
		 *
		 * @var string
		 */
		public static $widget = 'communication-gap';

		/**
		 * Initialize this class
		 */
		public static function init() {
		}

		/**
		 * Communication Gap report
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
			?>
			<div class="wpsc-dash-widget wpsc-dash-widget-mid wpsc-<?php echo esc_attr( $slug ); ?>">
				<div class="wpsc-dash-widget-header">
					<div class="wpsc-dashboard-widget-icon-header">
						<?php WPSC_Icons::get( 'line-graph' ); ?>
						<span>
						Intervalo da Comunicação
						</span>
					</div>
					<div class="wpsc-dash-widget-actions">
					</div>
				</div>
				<div class="wpsc-dash-widget-content wpsc-dbw-line-graph" id="wpsc-dash-communication-gap">
					<canvas id="wpsc-dbw-communication-gap"></canvas>
				</div>
			</div>
			<script>
				wpsc_get_dbw_communication_gap();
				async function wpsc_get_dbw_communication_gap() {
					jQuery('#wpsc-dash-communication-gap').html( supportcandy.loader_html );
					var dates = wpsc_db_set_filter_duration_dates('this-week');
					var startDate = new Date(dates.from);
					var endDate = new Date(dates.to);
					let report = {
						batches: wpsc_rp_get_baches(startDate, endDate),
						labels: [],
						communicationGap: [],
						totalCommunicationGap: [],
						count: []
					}

					var batchesLength = report.batches.length;
					for (let i = 0; i < batchesLength; i++) {

						var promises    = [];
						var batchLength = report.batches[i].length;

						for (let j = 0; j < batchLength; j++) {

							var duration = report.batches[i][j];
							promises.push(
								new Promise(
									function (resolve, reject) {

										dataform = new FormData();
										dataform.append( 'action', 'wpsc_rp_run_cg_report' );
										dataform.append( 'from_date', duration.fromDate );
										dataform.append( 'to_date', duration.toDate );
										dataform.append( 'duration_type', duration.durationType );
										dataform.append( '_ajax_nonce', '<?php echo esc_attr( wp_create_nonce( 'communication_gap' ) ); ?>' );
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
								report.communicationGap.push( response.communicationGap );
								report.totalCommunicationGap.push( response.totalCommunicationGap );
								report.count.push( response.count );
							}
						);

						if ( ! isValidResults) {
							jQuery( '#wpsc-dash-communication-gap' ).text( 'Something went wrong!' );
							return;
						}
					}

					var data   = {
						labels: report.labels,
						datasets: [
							{
								label: 'Intervalo da Comunicação',
								backgroundColor: '#e74c3c',
								borderColor: '#e74c3c',
								data: report.communicationGap
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
										'text': 'Número de processos'
									}
								}
							}
						}
					};
					jQuery('#wpsc-dash-communication-gap').html( '<canvas id="wpsc-dbw-communication-gap"></canvas>' );
					new Chart(
						document.getElementById( 'wpsc-dbw-communication-gap' ),
						config
					);
				}
			</script>
			<?php
		}
	}
endif;
WPSC_DBW_Communication_Gap::init();
