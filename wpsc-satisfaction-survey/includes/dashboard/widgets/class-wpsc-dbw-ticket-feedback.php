<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DBW_Ticket_Feedback' ) ) :

	final class WPSC_DBW_Ticket_Feedback {

		/**
		 * Widget slug
		 *
		 * @var string
		 */
		public static $widget = 'ticket-feedback';

		/**
		 * Initialize this class
		 */
		public static function init() {

				// Get ticket feedback.
				add_action( 'wp_ajax_wpsc_dash_get_ticket_feedback', array( __CLASS__, 'get_ticket_feedback' ) );
				add_action( 'wp_ajax_nopriv_wpsc_dash_get_ticket_feedback', array( __CLASS__, 'get_ticket_feedback' ) );
		}

		/**
		 * Ticket feedback
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
							Avaliação da ocorrência
						</span>
					</div>
					<?php
					if ( WPSC_Functions::is_site_admin() ) {
						?>
						<div class="wpsc-dash-widget-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpsc-customer-feedback' ) ); ?>"><?php echo esc_attr__( 'Ver todos', 'supportcandy' ); ?></a>
						</div>
						<?php
					}
					?>
				</div>
				<div class="wpsc-dash-widget-content wpsc-dbw-info" id="wpsc-dash-ticket-feedback"></div>
			</div>
			<script>
				wpsc_dash_get_ticket_feedback();
				function wpsc_dash_get_ticket_feedback() {
					jQuery('#wpsc-dash-ticket-feedback').html( supportcandy.loader_html );
					var data = { action: 'wpsc_dash_get_ticket_feedback', view: supportcandy.is_frontend, _ajax_nonce: supportcandy.nonce };
					jQuery.post(
						supportcandy.ajax_url,
						data,
						function (response) {
							jQuery('#wpsc-dash-ticket-feedback').html(response.html);
						}
					);
				}
			</script>
			<?php
		}

		/**
		 * Get ticket feedback.
		 *
		 * @return void
		 */
		public static function get_ticket_feedback() {

			if ( check_ajax_referer( 'general', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$view = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : '0';

			$current_user = WPSC_Current_User::$current_user;
			$widgets = get_option( 'wpsc-dashboard-widgets', array() );
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widgets[ self::$widget ]['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$ratings = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
			$rating_ids = array();

			foreach ( $ratings as $rating ) {
				$rating_ids[] = $rating->id;
			}

			$filters = array();
			// tickets.
			$response = WPSC_Ticket::find(
				array(
					'items_per_page' => 10,
					'system_query'   => $current_user->get_tl_system_query( $filters ),
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'rating',
							'compare' => 'IN',
							'val'     => $rating_ids,
						),
					),
				)
			);

			$tickets = $response['results'];

			$url = '';
			ob_start();
			?>
			<table class="wpsc-db-user-feedback">
				<thead>
					<tr>
						<th><?php echo esc_attr__( 'Ocorrência', 'supportcandy' ); ?></th>
						<th><?php echo esc_attr__( 'Assunto', 'supportcandy' ); ?></th>
						<th><?php echo esc_attr__( 'Usuário', 'supportcandy' ); ?></th>
						<th><?php echo esc_attr__( 'Avaliação', 'supportcandy' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $tickets as $ticket ) {
					$url = WPSC_Functions::get_ticket_url( $ticket->id, $view );
					?>
					<tr>
						<td><?php echo '<a href="' . esc_attr( $url ) . '" target="_blank">#' . esc_attr( $ticket->id ) . '</a>'; ?></td>
						<td><?php echo esc_attr( $ticket->subject ); ?></td>
						<td><?php echo esc_attr( $ticket->customer->name ); ?></td>
						<td><?php echo '<span class="wpsc-tag" style="color:' . esc_attr( $ticket->rating->color ) . ';background-color:' . esc_attr( $ticket->rating->bg_color ) . ';">' . esc_attr( $ticket->rating->name ) . '</span>'; ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<script>
					jQuery('table.wpsc-db-user-feedback').DataTable({
						ordering: false,
						pageLength: 10,
						searching: false,
						bLengthChange: false,
						info: false,
						paging: false,
						columnDefs: [
							{ targets: '_all', className: 'dt-left' },
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
WPSC_DBW_Ticket_Feedback::init();
