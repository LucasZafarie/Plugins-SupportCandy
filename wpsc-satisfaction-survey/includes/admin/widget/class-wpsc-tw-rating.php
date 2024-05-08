<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_TW_Rating' ) ) :

	final class WPSC_TW_Rating {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// get edit rating.
			add_action( 'wp_ajax_wpsc_it_get_edit_rating', array( __CLASS__, 'it_get_edit_rating' ) );
			add_action( 'wp_ajax_nopriv_wpsc_it_get_edit_rating', array( __CLASS__, 'it_get_edit_rating' ) );
			add_action( 'wp_ajax_wpsc_it_set_edit_rating', array( __CLASS__, 'it_set_edit_rating' ) );
			add_action( 'wp_ajax_nopriv_wpsc_it_set_edit_rating', array( __CLASS__, 'it_set_edit_rating' ) );

			// edit widget settings.
			add_action( 'wp_ajax_wpsc_get_tw_rating', array( __CLASS__, 'get_tw_rating' ) );
			add_action( 'wp_ajax_wpsc_set_tw_rating', array( __CLASS__, 'set_tw_rating' ) );
		}

		/**
		 * Prints body of current widget
		 *
		 * @param object $ticket - ticket object.
		 * @param array  $settings - setting array.
		 * @return void
		 */
		public static function print_widget( $ticket, $settings ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! (
				(
					(
						WPSC_Individual_Ticket::$view_profile == 'customer' ||
						$ticket->customer->id == $current_user->customer->id
					) &&
					$settings['allow-customer']
				) ||
				( WPSC_Individual_Ticket::$view_profile == 'agent' && in_array( $current_user->agent->role, $settings['allowed-agent-roles'] ) )
			) ) {
				return;
			}

			$gs = get_option( 'wpsc-gs-general', array() );

			if ( $gs['close-ticket-status'] == $ticket->status->id ) {
				?>            
				<div class="wpsc-it-widget wpsc-itw-rating">
					<div class="wpsc-widget-header">
						<h2><?php echo esc_attr( $settings['title'] ); ?></h2>
						<?php
						if ( ! $current_user->is_guest && $ticket->customer->email == $current_user->customer->email ) {
							?>
							<span onclick="wpsc_it_get_edit_rating(<?php echo esc_attr( $ticket->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_it_get_edit_rating' ) ); ?>')"><?php WPSC_Icons::get( 'edit' ); ?></span>
							<?php
						}
						?>
						<span class="wpsc-itw-toggle" data-widget="wpsc-itw-rating"><?php WPSC_Icons::get( 'chevron-up' ); ?></span>
					</div>
					<div class="wpsc-widget-body">
						<?php
						if ( is_object( $ticket->rating ) ) {

							?>
							<div class="wpsc-tag" style="background-color:<?php echo esc_attr( $ticket->rating->bg_color ); ?>;color:<?php echo esc_attr( $ticket->rating->color ); ?>; margin-bottom: 10px;">
								<?php echo esc_attr( $ticket->rating->name ); ?>
							</div>
							<?php
							if ( $ticket->sf_feedback ) {

								?>
								<div style="font-size: 12px; font-style:italic; margin-bottom: 10px;">
									<?php
									echo wp_kses_post( str_replace( PHP_EOL, '<br/>', wp_strip_all_tags( $ticket->sf_feedback ) ) );
									?>
								</div>
								<?php
							}
						} else {
							?>
							<div class="wpsc-widget-default"><?php echo esc_attr( wpsc__( 'Not Applicable', 'supportcandy' ) ); ?></div>
							<?php
						}
						do_action( 'wpsc_tw_rating', $ticket );
						?>
					</div>
				</div>
				<?php
			}
		}

		/**
		 * Get edit widget settings
		 *
		 * @return void
		 */
		public static function get_tw_rating() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ticket_widgets = get_option( 'wpsc-ticket-widget', array() );
			$rating         = $ticket_widgets['rating'];
			$title          = $rating['title'];
			$roles          = get_option( 'wpsc-agent-roles', array() );
			ob_start();
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-rating">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
					</div>
					<input name="label" type="text" value="<?php echo esc_attr( $rating['title'] ); ?>" autocomplete="off">
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Enable', 'supportcandy' ) ); ?></label>
					</div>
					<select name="is_enable">
						<option <?php selected( $rating['is_enable'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $rating['is_enable'], '0' ); ?>  value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Allowed for customer', 'wpsc-sf' ); ?></label>
					</div>
					<select id="allow-customer" name="allow-customer">
						<option <?php selected( $rating['allow-customer'], '1' ); ?> value="1"><?php echo esc_attr( wpsc__( 'Yes', 'supportcandy' ) ); ?></option>
						<option <?php selected( $rating['allow-customer'], '0' ); ?> value="0"><?php echo esc_attr( wpsc__( 'No', 'supportcandy' ) ); ?></option>
					</select>
				</div>
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Allowed agent roles', 'supportcandy' ) ); ?></label>
					</div>
					<select multiple id="wpsc-select-agents" name="agents[]" placeholder="">
						<?php
						foreach ( $roles as $key => $role ) {
							$selected = in_array( $key, $rating['allowed-agent-roles'] ) ? 'selected' : ''
							?>
							<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $role['label'] ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<script>
					jQuery('#wpsc-select-agents').selectWoo({
						allowClear: false,
						placeholder: ""
					});
				</script>
				<?php do_action( 'wpsc_get_woo_body' ); ?>
				<input type="hidden" name="action" value="wpsc_set_tw_rating">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_tw_rating' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_tw_rating(this);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?>
			</button>
			<?php
			do_action( 'wpsc_get_tw_rating_widget_footer' );
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);
			wp_send_json( $response );
		}

		/**
		 * Set edit widget settings
		 *
		 * @return void
		 */
		public static function set_tw_rating() {

			if ( check_ajax_referer( 'wpsc_set_tw_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
			if ( ! $label ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$is_enable          = isset( $_POST['is_enable'] ) ? intval( $_POST['is_enable'] ) : 1;
			$allow_for_customer = isset( $_POST['allow-customer'] ) ? intval( $_POST['allow-customer'] ) : 0;
			$agents             = isset( $_POST['agents'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['agents'] ) ) ) : array();

			$ticket_widgets                                  = get_option( 'wpsc-ticket-widget', array() );
			$ticket_widgets['rating']['title']               = $label;
			$ticket_widgets['rating']['is_enable']           = $is_enable;
			$ticket_widgets['rating']['allow-customer']      = $allow_for_customer;
			$ticket_widgets['rating']['allowed-agent-roles'] = $agents;
			update_option( 'wpsc-ticket-widget', $ticket_widgets );
			wp_die();
		}

		/**
		 * Get edit rating
		 *
		 * @return void
		 */
		public static function it_get_edit_rating() {

			if ( check_ajax_referer( 'wpsc_it_get_edit_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();

			$current_user = WPSC_Current_User::$current_user;

			$ticket = WPSC_Individual_Ticket::$ticket;

			$sf_settings = get_option( 'wpsc-sf-general-setting' );
			if ( ! ( in_array( $ticket->status->id, $sf_settings['statuses-enabled'] ) && $ticket->customer->email == $current_user->customer->email ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$is_trigger = isset( $_POST['isTrigger'] ) ? intval( $_POST['isTrigger'] ) : 0;

			$widgets = get_option( 'wpsc-ticket-widget' );
			$title   = $widgets['rating']['title'];

			$settings = get_option( 'wpsc-ap-general' );
			$ratings  = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];

			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-frm-add-feedback">
				<div id="wpsc-sf-ratings">
					<?php
					foreach ( $ratings as $rating ) {
						$active = is_object( $ticket->rating ) && $rating->id == $ticket->rating->id ? 'active' : '';
						?>
						<div class="wpsc-rating-item <?php echo esc_attr( $active ); ?>" 
							data-id="<?php echo esc_attr( $rating->id ); ?>">
							<span class="wpsc-tag" style="color:<?php echo esc_attr( $rating->color ); ?>;background-color:<?php echo esc_attr( $rating->bg_color ); ?>;" 
								onclick="wpsc_change_sf_rating(this);">
								<?php echo esc_attr( $rating->name ); ?>
							</span>
						</div>
						<?php
					}
					?>
				</div>

				<div class="wpsc-input-group">
					<div class="label-container"><label for=""><?php esc_attr_e( 'Feedback (Optional)', 'wpsc-sf' ); ?></label></div>
					<textarea id="wpsc-sf-feedback" name="wpsc-sf-feedback" class="wpsc_textarea"><?php echo esc_attr( wp_strip_all_tags( $ticket->sf_feedback ) ); ?></textarea>
				</div>
				<?php $ticket_rating_id = is_object( $ticket->rating ) ? $ticket->rating->id : 0; ?>
				<input type="hidden" id="rating" name="rating" value="<?php echo esc_attr( $ticket_rating_id ); ?>">
				<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>" />
				<input type="hidden" name="action" value="wpsc_it_set_edit_rating">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_it_set_edit_rating' ) ); ?>">
				<style>
					.wpsc-rating-item.active{ 
						border: 2px solid <?php echo esc_attr( $settings['primary-color'] ); ?>;
					}
				</style>
			</form>
			<?php
			do_action( 'wpsc_get_edit_rating_footer' );
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_it_set_edit_rating(this, <?php echo esc_attr( $ticket->id ); ?>, <?php echo $is_trigger ? 'true' : 'false'; ?>);">
				<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?>
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
			wp_send_json( $response );

			wp_die();
		}

		/**
		 * Save rating
		 *
		 * @return void
		 */
		public static function it_set_edit_rating() {

			if ( check_ajax_referer( 'wpsc_it_set_edit_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();

			$current_user = WPSC_Current_User::$current_user;

			$ticket = WPSC_Individual_Ticket::$ticket;
			if ( ! $ticket->is_active ) {
				wp_send_json_error( 'Something went wrong!', 400 );
			}

			$rating_id = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;

			if ( ! ( $ticket && $rating_id ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$sf_settings = get_option( 'wpsc-sf-general-setting' );
			if ( ! ( in_array( $ticket->status->id, $sf_settings['statuses-enabled'] ) && $ticket->customer->email == $current_user->customer->email ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$feedback = isset( $_POST['wpsc-sf-feedback'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpsc-sf-feedback'] ) ) : '';

			// old rating id.
			$prev = $ticket->rating ? $ticket->rating->id : '';

			$ticket->rating      = $rating_id;
			$ticket->sf_feedback = $feedback;
			$ticket->sf_date     = new DateTime();
			$ticket->save();

			// new rating id.
			$new = $ticket->rating->id;

			// Exit if no change.
			if ( $new == $prev ) {
				wp_die();
			}

			do_action( 'wpsc_change_ticket_rating', $ticket, $prev, $new, $current_user->customer->id );
		}
	}
endif;

WPSC_TW_Rating::init();
