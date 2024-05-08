<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ITW_AR_UG' ) ) :

	final class WPSC_ITW_AR_UG {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add usergroup schema.
			add_action( 'wpsc_ticket_schema', array( __CLASS__, 'add_ticket_schema' ) );

			// add usergroups in additional recipients.
			add_action( 'wpsc_it_edit_additional_recipients', array( __CLASS__, 'get_additional_recipients' ) );
			add_action( 'wpsc_before_change_add_recipients', array( __CLASS__, 'set_additional_recipients' ), 10, 4 );
			add_action( 'wpsc_itw_additional_recipients', array( __CLASS__, 'itw_additional_recipients' ) );

			// email notifications.
			add_filter( 'wpsc_en_to_addresses', array( __CLASS__, 'en_to_addresses' ), 10, 2 );
			add_filter( 'wpsc_en_cc_addresses', array( __CLASS__, 'en_cc_addresses' ), 10, 2 );
			add_filter( 'wpsc_en_bcc_addresses', array( __CLASS__, 'en_bcc_addresses' ), 10, 2 );
		}

		/**
		 * Add report calculation schema for ticket
		 *
		 * @param array $schema - schecma.
		 * @return array
		 */
		public static function add_ticket_schema( $schema ) {

			$usergroup_schema = array(
				'ar_usergroups' => array(
					'has_ref'          => true,
					'ref_class'        => 'wpsc_usergroup',
					'has_multiple_val' => true,
				),
			);

			return array_merge( $schema, $usergroup_schema );
		}

		/**
		 * Get usergroups in additional recipients widget
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function get_additional_recipients( $ticket ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! $current_user->is_agent ) {
				return;
			}

			$usergroups = WPSC_Usergroup::find( array( 'items_per_page' => 0 ) )['results'];

			$ar_usergroups = array();
			foreach ( $ticket->ar_usergroups as $tg ) {
				$ar_usergroups[] = $tg->id;
			}?>
			<div class="wpsc-input-group members" style="padding-bottom: 10px;">
				<div class="label-container">
					<label for="">
						<?php esc_attr_e( 'Grupo de usuários', 'wpsc-usergroup' ); ?>
					</label>
				</div>
				<select name="ar_usergroups[]" class="usergroups" multiple>
					<?php
					foreach ( $usergroups as $group ) {
						$selected = in_array( $group->id, $ar_usergroups ) ? 'selected' : '';
						?>
						<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $group->id ); ?>"><?php echo esc_attr( $group->name ); ?></option>
						<?php
					}
					?>
				</select>
			</div>
			<script>
				jQuery('select.usergroups').selectWoo({
					allowClear: false,
					placeholder: ""
				});
			</script>
			<?php
		}

		/**
		 * Set usergroups in additional recipients widget
		 * Ignore phpcs nonce issue as we already checked where it is called from.
		 *
		 * @param WPSC_Ticket $ticket - ticket.
		 * @param array       $prev - prev.
		 * @param array       $new - new.
		 * @param int         $id - id.
		 * @return void
		 */
		public static function set_additional_recipients( $ticket, $prev, $new, $id ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! $current_user->is_agent ) {
				return;
			}

			$add_recipients        = isset( $_POST['ar_usergroups'] ) ? array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['ar_usergroups'] ) ) ) : array(); // phpcs:ignore
			$ticket->ar_usergroups = $add_recipients ? $add_recipients : array();
			$ticket->date_updated  = new DateTime();
			$ticket->save();
		}

		/**
		 * Show usergroups in additional recipients widget
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function itw_additional_recipients( $ticket ) {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! $current_user->is_agent ) {
				return;
			}
			?>
			<div class="info-list-item">
				<div class="info-label"><?php echo esc_attr__( 'Grupo de usuários', 'wpsc-usergroup' ); ?>:</div>
				<div class="info-val fullwidth">
					<?php
					if ( $ticket->ar_usergroups ) {
						foreach ( $ticket->ar_usergroups as $group ) {
							?>
							<div class="wpsc-widget-default"><?php echo esc_attr( $group->name ); ?></div>
							<?php
						}
					} else {
						?>
						<?php echo esc_attr( wpsc__( 'Não aplicado', 'supportcandy' ) ); ?>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Add usergroup members in To addresses as additional recipient
		 *
		 * @param array                    $to - mail to.
		 * @param WPSC_Email_Notifications $email - email to.
		 * @return array
		 */
		public static function en_to_addresses( $to, $email ) {

			$et                 = $email->template;
			$general_recipients = $et['to']['general-recipients'];

			if ( in_array( 'add-recipients', $general_recipients ) ) {

				foreach ( $email->ticket->ar_usergroups as $usergroup ) {
					foreach ( $usergroup->members as $member ) {
						$to[] = $member->email;
					}
				}
			}

			return $to;
		}

		/**
		 * Add usergroup members in cc addresses as additional recipient
		 *
		 * @param array                    $cc - mail cc.
		 * @param WPSC_Email_Notifications $email - email to.
		 * @return array
		 */
		public static function en_cc_addresses( $cc, $email ) {

			$et                 = $email->template;
			$general_recipients = $et['cc']['general-recipients'];

			if ( in_array( 'add-recipients', $general_recipients ) ) {

				foreach ( $email->ticket->ar_usergroups as $usergroup ) {
					foreach ( $usergroup->members as $member ) {
						$cc[] = $member->email;
					}
				}
			}

			return $cc;
		}

		/**
		 * Add usergroup members in bcc addresess as additional recipient
		 *
		 * @param array                    $bcc - bcc.
		 * @param WPSC_Email_Notifications $email - email to.
		 * @return array
		 */
		public static function en_bcc_addresses( $bcc, $email ) {

			$et                 = $email->template;
			$general_recipients = $et['bcc']['general-recipients'];

			if ( in_array( 'add-recipients', $general_recipients ) ) {

				foreach ( $email->ticket->ar_usergroups as $usergroup ) {
					foreach ( $usergroup->members as $member ) {
						$bcc[] = $member->email;
					}
				}
			}

			return $bcc;
		}
	}
endif;

WPSC_ITW_AR_UG::init();
