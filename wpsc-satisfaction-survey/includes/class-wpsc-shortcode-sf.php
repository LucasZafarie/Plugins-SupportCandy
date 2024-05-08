<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_Shortcode_SF' ) ) :

	final class WPSC_Shortcode_SF {

		/**
		 * Initialize this class
		 */
		public static function init() {

			// register shortcode.
			add_shortcode( 'wpsc_sf', array( __CLASS__, 'layout' ) );

			// save additional feedback.
			add_action( 'wp_ajax_wpsc_set_sf_add_feedback', array( __CLASS__, 'set_add_feedback' ) );
			add_action( 'wp_ajax_nopriv_wpsc_set_sf_add_feedback', array( __CLASS__, 'set_add_feedback' ) );
		}

		/**
		 * Layout for this shortcode
		 *
		 * @param array $attrs - shortcode attributes.
		 * @return void
		 */
		public static function layout( $attrs ) {

			$current_user = WPSC_Current_User::$current_user;

			$ticket_id = isset( $_REQUEST['ticket_id'] ) ? intval( $_REQUEST['ticket_id'] ) : 0; //phpcs:ignore
			if ( ! $ticket_id ) {
				return;
			}

			$ticket = new WPSC_Ticket( $ticket_id );
			if ( ! $ticket ) {
				return;
			}

			ob_start();

			$sf_settings = get_option( 'wpsc-sf-general-setting' );
			if ( $current_user->customer && ! ( in_array( $ticket->status->id, $sf_settings['statuses-enabled'] ) && $ticket->customer->email == $current_user->customer->email ) ) {
				?>
				<div>
					<p><?php esc_attr_e( 'Access denied!', 'wpsc-sf' ); ?></p>
					<?php esc_attr_e( 'You do not have permission to rate this ticket because of the following reasons:', 'wpsc-sf' ); ?>
					<ul style="font-size: 14px;">
						<li><?php esc_attr_e( 'You are not a creator of the ticket.', 'wpsc-sf' ); ?></li>
						<li><?php esc_attr_e( 'The ticket is not closed.', 'wpsc-sf' ); ?></li>
					</ul>
				</div>
				<?php
				return;
			}
			?>
			<div id="wpsc-container" style="display:none;">
				<div class="wpsc-shortcode-container" style="border: none !important;">
				<?php
				if ( $current_user->customer ) {
					$rating_id = isset( $_REQUEST['sf_id'] ) ? intval( $_REQUEST['sf_id'] ) : 0; //phpcs:ignore
					$rating_id = ! $rating_id && $ticket->rating ? $ticket->rating->id : $rating_id;
					$settings  = get_option( 'wpsc-ap-general' );
					$ratings   = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
					?>
					<form action="#" onsubmit="return false;" class="wpsc-frm-add-feedback" style="padding: 20px;">
						<div class="wpsc-sf-info-header-body">
							<div class="wpsc-sf-info-header"><?php esc_attr_e( 'This survey is related to the following ticket', 'wpsc-sf' ); ?>:</div>
							<div class="wpsc-sf-info-header">
								<?php echo '#' . esc_attr( $ticket->id ) . '&nbsp;-&nbsp;' . esc_attr( $ticket->subject ); ?>
							</div>
						</div>
						<div id="wpsc-sf-ratings">
							<?php
							foreach ( $ratings as $rating ) {
								$active = $rating->id == $rating_id ? 'active' : '';
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
							<textarea id="wpsc-sf-feedback" name="wpsc-sf-feedback" class="wpsc_textarea"><?php echo esc_attr( $ticket->sf_feedback ); ?></textarea>
						</div>
						<input type="hidden" id="rating" name="rating" value="<?php echo esc_attr( $rating_id ); ?>">
						<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>" />
						<input type="hidden" name="action" value="wpsc_set_sf_add_feedback">
						<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_sf_add_feedback' ) ); ?>">

						<div class="setting-footer-actions">
							<button 
								class="wpsc-button normal primary margin-right"
								onclick="wpsc_set_sf_add_feedback(this);">
								<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
						</div>
						<style>
							.wpsc-rating-item.active{ 
								border: 2px solid <?php echo esc_attr( $settings['primary-color'] ); ?>;
							}
						</style>
					</form>
					<?php
				} else {

					WPSC_Frontend::load_authentication_screen();
				}
				?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Save feedback
		 *
		 * @return void
		 */
		public static function set_add_feedback() {

			if ( check_ajax_referer( 'wpsc_set_sf_add_feedback', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			$ticket_id = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
			$rating_id = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;
			$feedback  = isset( $_POST ) && isset( $_POST['wpsc-sf-feedback'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpsc-sf-feedback'] ) ) : '';

			if ( ! ( $ticket_id && $rating_id ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$ticket = new WPSC_Ticket( $ticket_id );
			if ( ! $ticket ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$rating = new WPSC_SF_Rating( $rating_id );
			if ( ! $rating ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			$current_user = WPSC_Current_User::$current_user;

			$sf_settings = get_option( 'wpsc-sf-general-setting' );
			if ( ! ( in_array( $ticket->status->id, $sf_settings['statuses-enabled'] ) && $ticket->customer->email == $current_user->customer->email ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			// old rating id.
			$prev = $ticket->rating ? $ticket->rating->id : '';

			$ticket->rating      = $rating_id;
			$ticket->sf_feedback = $feedback;
			$ticket->sf_date     = new DateTime();
			$ticket->save();

			// new rating id.
			$new = $ticket->rating->id;

			do_action( 'wpsc_change_ticket_rating', $ticket, $prev, $new, $current_user->customer->id );

			$responce = array( 'msg' => $rating->confirmation_text );
			wp_send_json( $responce, 200 );
		}
	}
endif;

WPSC_Shortcode_SF::init();
