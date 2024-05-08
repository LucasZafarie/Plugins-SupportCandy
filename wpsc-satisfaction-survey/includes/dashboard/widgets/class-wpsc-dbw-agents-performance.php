<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DBW_Agents_Performance' ) ) :

	final class WPSC_DBW_Agents_Performance {

		/**
		 * Widget slug
		 *
		 * @var string
		 */
		public static $widget = 'agents-performance';

		/**
		 * Initialize this class
		 */
		public static function init() {

			// Get agents performance.
			add_action( 'wp_ajax_wpsc_dash_get_agents_performance', array( __CLASS__, 'get_agents_performance' ) );
			add_action( 'wp_ajax_nopriv_wpsc_dash_get_agents_performance', array( __CLASS__, 'get_agents_performance' ) );
		}

		/**
		 * Agent performance report
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
			?>
			<div class="wpsc-dash-widget wpsc-dash-widget-mid wpsc-<?php echo esc_attr( $slug ); ?>">
				<div class="wpsc-dash-widget-header">
					<div class="wpsc-dashboard-widget-icon-header">
						<?php WPSC_Icons::get( 'list' ); ?>
						<span>
							Avaliação do agente
						</span>
					</div>
					<div class="wpsc-dash-widget-actions">
						<select name="" id="date_wise_agents_performance_report" onchange="wpsc_dash_get_agents_performance();" style="max-height: 18px !important;line-height: 15px !important;font-size: 12px !important;">
							<option value="last_7"><?php esc_attr_e( 'Últimos 7 dias', 'supportcandy' ); ?></option>
							<option value="last_week"><?php esc_attr_e( 'Última semana', 'supportcandy' ); ?></option>
							<option value="last_30"><?php esc_attr_e( 'Últimos 30 dias', 'supportcandy' ); ?></option>
							<option value="last_month"><?php esc_attr_e( 'Último mês', 'supportcandy' ); ?></option>
						</select>
						<input type="text" id="wpsc-db-ap-search" placeholder="<?php esc_attr_e( 'Buscar...', 'supportcandy' ); ?>">
					</div>
				</div>
				<div class="wpsc-dash-widget-content wpsc-dbw-info" id="wpsc-dash-agents-performance"></div>
			</div>
			<script>
				wpsc_dash_get_agents_performance();
				function wpsc_dash_get_agents_performance() {
					jQuery('#wpsc-dash-agents-performance').html( supportcandy.loader_html );
					var date_range = jQuery('#date_wise_agents_performance_report').val();
					var data = { action: 'wpsc_dash_get_agents_performance', date_range, _ajax_nonce: supportcandy.nonce };
					jQuery.post(
						supportcandy.ajax_url,
						data,
						function (response) {
							jQuery('#wpsc-dash-agents-performance').html(response.html);
						}
					);
				}

				jQuery(document).ready(function() {
					jQuery('#wpsc-db-ap-search').on('keyup', function() {
							var searchTerm = jQuery(this).val();
							jQuery('table.wpsc-db-agent-performance').DataTable().search(searchTerm).draw();
					});
				})
			</script>
			<style>
				#wpsc-dash-agents-performance .dataTables_filter{
					display: none;
				}
			</style>
			<?php
		}

		/**
		 * Get agents performance.
		 *
		 * @return void
		 */
		public static function get_agents_performance() {

			if ( check_ajax_referer( 'general', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$range = isset( $_POST['date_range'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range'] ) ) : '';
			if ( ! $range ) {
				wp_send_json_error( 'Something went wrong', 400 );
			}

			$current_user = WPSC_Current_User::$current_user;
			$widgets = get_option( 'wpsc-dashboard-widgets', array() );
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widgets[ self::$widget ]['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			// calculate date range.
			$date_range = WPSC_Functions::get_dashboard_date_range( $range );
			$ratings = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];

			$args = array(
				'items_per_page' => 0,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'slug'    => 'is_active',
						'compare' => '=',
						'val'     => 1,
					),
					array(
						'slug'    => 'is_agentgroup',
						'compare' => '=',
						'val'     => 0,
					),
				),
			);
			$agents    = WPSC_Agent::find( $args )['results'];
			ob_start();
			?>
			<table class="wpsc-db-agent-performance">
				<thead>
					<tr>
						<th><?php echo esc_attr__( 'Agent', 'supportcandy' ); ?></th>
						<?php
						foreach ( $ratings as $rating ) {
							?>
							<th><?php echo esc_attr( $rating->name ); ?></th>
							<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( $agents ) :
						foreach ( $agents as $agent ) {
							?>
							<tr>
								<td><?php echo esc_attr( $agent->name ); ?></td>
								<?php
								$total_count = 0;
								foreach ( $ratings as $rating ) :
									if ( $rating->id && $agent->id ) :
										$count = WPSC_Ticket::find(
											array(
												'items_per_page' => 0,
												'meta_query'     => array(
													'relation' => 'AND',
													array(
														'slug'    => 'assigned_agent',
														'compare' => 'IN',
														'val'     => array( $agent->id ),
													),
													array(
														'slug'    => 'rating',
														'compare' => '=',
														'val'     => $rating->id,
													),
													array(
														'slug'    => 'sf_date',
														'compare' => 'BETWEEN',
														'val'     => array(
															'operand_val_1' => $date_range[0],
															'operand_val_2' => $date_range[1],
														),
													),
												),
											)
										)['total_items'];
										?>
										<td><?php echo intval( $count ); ?></td>
										<?php
									endif;
								endforeach;
								?>
							</tr>
							<?php
						}
					endif;
					?>
				</tbody>
			</table>
			<script>
				jQuery('table.wpsc-db-agent-performance').DataTable({
					ordering: true,
					pageLength: 10,
					searching: true,
					bLengthChange: false,
					columnDefs: [
						{ targets: '_all', className: 'dt-left' },
						{
							"targets": [0], // First column
							"searchable": true,
							"orderable": true,
						},
						{
							"targets": '_all', // All other columns
							"searchable": false,
							"orderable": true
						}
					],
					language: supportcandy.translations.datatables
				});
			</script>
			<?php
			$table = ob_get_clean();
			wp_send_json( array( 'html' => $table ) );
		}
	}
endif;
WPSC_DBW_Agents_Performance::init();
