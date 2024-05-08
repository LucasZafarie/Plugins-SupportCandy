<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_DBW_Usergroups' ) ) :

	final class WPSC_DBW_Usergroups {

		/**
		 * Widget slug
		 *
		 * @var string
		 */
		public static $widget = 'usergroups';

		/**
		 * Initialize this class
		 */
		public static function init() {

			// Get list of agents.
			add_action( 'wp_ajax_wpsc_dash_get_usergroups_list', array( __CLASS__, 'get_usergroups_list' ) );
			add_action( 'wp_ajax_nopriv_wpsc_dash_get_usergroups_list', array( __CLASS__, 'get_usergroups_list' ) );

			// Set filter for the card.
			add_action( 'wp_ajax_wpsc_dash_usergroup_count_filter', array( __CLASS__, 'set_filter' ) );
			add_action( 'wp_ajax_nopriv_wpsc_dash_usergroup_count_filter', array( __CLASS__, 'set_filter' ) );
		}

		/**
		 * Show usergroups
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
						Grupo de usuários
						</span>
					</div>
					<div class="wpsc-dash-widget-actions">
					</div>
				</div>
				<div class="wpsc-dash-widget-content wpsc-dbw-info"  id="wpsc-dash-usergroups-list">
				</div>
			</div>
			<script>
				wpsc_dash_get_usergroups_list();
				function wpsc_dash_get_usergroups_list(){
					jQuery('#wpsc-dash-usergroups-list').html( supportcandy.loader_html );
					var data = { action: 'wpsc_dash_get_usergroups_list' };
					jQuery.post(
						supportcandy.ajax_url,
						data,
						function (response) {
							jQuery('#wpsc-dash-usergroups-list').html(response.html);
						}
					);
				}
			</script>
			<?php
		}

		/**
		 * Get agent list and ticket count.
		 *
		 * @return void
		 */
		public static function get_usergroups_list() {

			$current_user = WPSC_Current_User::$current_user;
			$widgets = get_option( 'wpsc-dashboard-widgets', array() );
			if ( $current_user->is_guest ||
				! ( $current_user->is_agent && in_array( $current_user->agent->role, $widgets[ self::$widget ]['allowed-agent-roles'] ) )
			) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$usergroups = WPSC_Usergroup::find()['results'];

			ob_start();
			?>
			<table class="wpsc-db-usergroups-list">
				<thead>
					<tr>
						<th><?php echo esc_attr__( 'Grupo de usuários', 'wpsc-usergroup' ); ?></th>
						<th><?php echo esc_attr__( 'No. of tickets', 'wpsc-usergroup' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				if ( $usergroups ) :
					$filters = array();
					foreach ( $usergroups as $usergroup ) :
						?>
						<tr>
							<td><?php echo esc_attr( $usergroup->name ); ?></td>
							<?php
							$total_count = WPSC_Ticket::find(
								array(
									'items_per_page' => 0,
									'system_query'   => $current_user->get_tl_system_query( $filters ),
									'meta_query'     => array(
										'relation' => 'AND',
										array(
											'slug'    => 'usergroups',
											'compare' => '=',
											'val'     => $usergroup->id,
										),
									),
								)
							)['total_items'];
							?>
							<td>
								<span class="wpsc-db-ug-ticket-count wpsc-link" data-uid="<?php echo intval( $usergroup->id ); ?>"><?php echo esc_attr( $total_count ); ?></span>
							</td>
						</tr>
						<?php
					endforeach;
				endif;
				?>
				</tbody>			
			</table>
			<script>
				var indexLastColumn = jQuery("table.wpsc-db-usergroups-list").find('tr')[0].cells.length-1;
				jQuery('table.wpsc-db-usergroups-list').DataTable({
					ordering: true,
					order: [[indexLastColumn, 'desc']],
					pageLength: 10,
					searching: false,
					paging: false,
					info: false,
					bLengthChange: false,
					columnDefs: [
						{ targets: '_all', className: 'dt-left' },
						{
							"targets": [0], // First column
							"searchable": false,
							"orderable": false,
						},
						{
							"targets": '_all', // All other columns
							"searchable": false,
							"orderable": true
						}
					],
					language: supportcandy.translations.datatables
				});

				jQuery('.wpsc-db-ug-ticket-count').on('click', function() {
					var uid = jQuery(this).data('uid');
					var data = { action: 'wpsc_dash_usergroup_count_filter', card : 'usergroups', view: supportcandy.is_frontend, uid:uid, _ajax_nonce: supportcandy.nonce };
					jQuery.post(
						supportcandy.ajax_url,
						data,
						function (response) {
							if( response.url ) {
								window.location = response.url;
							}
						}
					);
				});
			</script>
			<?php
			$table = ob_get_clean();
			wp_send_json( array( 'html' => $table ) );
		}

		/**
		 * Set filter for the card.
		 *
		 * @return void
		 */
		public static function set_filter() {

			if ( check_ajax_referer( 'general', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$card = isset( $_POST['card'] ) ? sanitize_text_field( wp_unslash( $_POST['card'] ) ) : '';
			if ( ! $card ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$view = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : '0';

			$current_user = WPSC_Current_User::$current_user;
			if ( ! $current_user->is_agent ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$uid = isset( $_POST['uid'] ) ? intval( $_POST['uid'] ) : 0;
			if ( ! $uid ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$page_settings = get_option( 'wpsc-gs-page-settings' );

			$custom_filters = array();

			if ( $card == 'usergroups' ) {

				$obj = new stdClass();
				$obj->slug = 'usergroups';
				$obj->operator = '=';
				$obj->operand_val_1 = $uid;

				$custom_filters[] = array( $obj );

			}

			$filters = array(
				'filterSlug'    => 'custom',
				'parent-filter' => 'all',
				'filters'       => wp_json_encode( $custom_filters ),
				'orderby'       => 'date_updated',
				'order'         => 'DESC',
				'page_no'       => 1,
				'search'        => '',
			);

			$filters = apply_filters( 'wpsc_dbw_set_filter', $filters, $custom_filters, $card );

			setcookie( 'wpsc-tl-filters', wp_json_encode( $filters ), time() + 3600 );

			$url = '';
			if ( $view === '0' ) {
				$url = admin_url( 'admin.php?page=wpsc-tickets&section=ticket-list' );
			} elseif ( $page_settings['ticket-url-page'] == 'support-page' && $page_settings['support-page'] ) {
				$url = get_permalink( $page_settings['support-page'] );
				$url = add_query_arg(
					array(
						'wpsc-section' => 'ticket-list',
					),
					$url
				);
			}

			wp_send_json( array( 'url' => $url ) );
		}
	}
endif;
WPSC_DBW_Usergroups::init();
