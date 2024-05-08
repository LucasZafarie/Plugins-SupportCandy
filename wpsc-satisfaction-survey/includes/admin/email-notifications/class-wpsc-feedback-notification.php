<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_Feedback_Notification' ) ) :

	final class WPSC_Feedback_Notification extends WPSC_Email_Notifications {

		/**
		 * Slug for this event (must be unique)
		 *
		 * @var string
		 */
		private static $slug = 'ticket-feedback';

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// load trigger.
			add_filter( 'wpsc_triggers', array( __CLASS__, 'load_trigger' ) );

			// process event.
			add_action( 'wpsc_change_ticket_rating', array( __CLASS__, 'process_event' ), 200, 4 );
		}

		/**
		 * Load this event for email notifications
		 *
		 * @param array $triggers - array of trigger names.
		 * @return array
		 */
		public static function load_trigger( $triggers ) {

			$triggers[ self::$slug ] = esc_attr__( 'Feedback added', 'wpsc-sf' );
			return $triggers;
		}

		/**
		 * Process emails for this event
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param string      $prev - previous rating.
		 * @param string      $new -  new rating.
		 * @param int         $customer_id - customer id.
		 *
		 * @return void
		 */
		public static function process_event( $ticket, $prev, $new, $customer_id ) {

			$gs              = get_option( 'wpsc-en-general' );
			$email_templates = get_option( 'wpsc-email-templates', array() );

			foreach ( $email_templates as $key => $et ) {

				if ( $et['event'] != self::$slug ) {
					continue;
				}

				// email notification object.
				$en = new self();

				// set properties.
				$en->ticket = $ticket;

				// set template.
				$en->template = $et;
				$en->template_key = $key;

				// check whether conditions matches (if any).
				if ( ! $en->is_valid() ) {
					continue;
				}

				$en = apply_filters( 'wpsc_en_before_sending', $en );

				// send an email.
				WPSC_Background_Email::insert(
					array(
						'from_name'  => $en->from_name,
						'from_email' => $en->from_email,
						'reply_to'   => $gs['reply-to'],
						'subject'    => $en->subject,
						'body'       => $en->body,
						'to_email'   => implode( '|', $en->to ),
						'cc_email'   => implode( '|', $en->cc ),
						'bcc_email'  => implode( '|', $en->bcc ),
						'priority'   => 3,
					)
				);
			}
		}
	}
endif;

WPSC_Feedback_Notification::init();
