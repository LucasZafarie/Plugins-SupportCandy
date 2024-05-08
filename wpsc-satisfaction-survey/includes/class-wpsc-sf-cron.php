<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Cron' ) ) :

	final class WPSC_SF_Cron {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Schedule cron jobs.
			add_action( 'init', array( __CLASS__, 'schedule_events' ) );

			// send survey emails.
			add_action( 'wpsc_sf_send_survey_emails', array( __CLASS__, 'send_survey_emails' ) );
		}

		/**
		 * Schedule cron job events for SupportCandy
		 *
		 * @return void
		 */
		public static function schedule_events() {

			// Send survey emails.
			if ( ! wp_next_scheduled( 'wpsc_sf_send_survey_emails' ) ) {
				wp_schedule_event(
					time(),
					'hourly',
					'wpsc_sf_send_survey_emails'
				);
			}
		}

		/**
		 * Send survey emails
		 *
		 * @return void
		 */
		public static function send_survey_emails() {

			$tz = wp_timezone();
			$today = new DateTime( 'now', $tz );
			$transient_label = 'wpsc_sf_survey_emails_cron_' . $today->format( 'Y-m-d' );

			$cron_status = get_transient( $transient_label );
			if ( false === $cron_status ) {
				$cron_status = array(
					'has_started'  => 0,
					'current_page' => 0,
					'total_pages'  => 0,
				);
			}

			// return if today's tickets finished checking.
			if ( $cron_status['has_started'] == 1 && $cron_status['current_page'] == $cron_status['total_pages'] ) {
				return;
			}

			$settings         = get_option( 'wpsc-sf-general-setting' );
			$en_general       = get_option( 'wpsc-en-general' );
			$email_templates  = get_option( 'wpsc-sf-et' );
			$general_settings = get_option( 'wpsc-gs-general' );

			if ( ! ( $settings['statuses-enabled'] && $email_templates && $en_general['from-name'] && $en_general['from-email'] ) ) {
				return;
			}

			$max_days_after = 0;
			$min_days_after = 1;
			foreach ( $email_templates as $et ) {
				$max_days_after = $et['days-after'] > $max_days_after ? $et['days-after'] : $max_days_after;
				$min_days_after = $et['days-after'] < $min_days_after ? $et['days-after'] : $min_days_after;
			}

			// get applicable tickets.
			$tickets = WPSC_Ticket::find(
				array(
					'items_per_page' => 20,
					'page_no'        => $cron_status['current_page'] + 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'status',
							'compare' => 'IN',
							'val'     => $settings['statuses-enabled'],
						),
						array(
							'slug'    => 'date_closed',
							'compare' => 'BETWEEN',
							'val'     => array(
								'operand_val_1' => ( clone $today )->sub( new DateInterval( 'P' . $max_days_after . 'D' ) )->format( 'Y-m-d' ),
								'operand_val_2' => ( clone $today )->sub( new DateInterval( 'P' . $min_days_after . 'D' ) )->format( 'Y-m-d' ),
							),
						),
						array(
							'relation' => 'OR',
							array(
								'slug'    => 'rating',
								'compare' => 'IS',
								'val'     => 'NULL',
							),
							array(
								'slug'    => 'rating',
								'compare' => '=',
								'val'     => '0',
							),
						),
					),
				)
			);

			// update cron status.
			delete_transient( $transient_label );
			$cron_status = array(
				'has_started'  => 1,
				'current_page' => $tickets['current_page'],
				'total_pages'  => $tickets['total_pages'] > 0 ? $tickets['total_pages'] : 1,
			);
			set_transient( $transient_label, $cron_status, MINUTE_IN_SECONDS * 60 * 24 );

			// register survey emails to send.
			if ( $tickets['total_items'] > 0 ) {

				foreach ( $tickets['results'] as $ticket ) {

					$date_closed = $ticket->date_closed->setTimezone( $tz );

					// Check if To email is blocked or forwarding email.
					if ( in_array( $ticket->customer->email, WPSC_Email_Notifications::$block_emails ) ) {
						continue;
					}

					foreach ( $email_templates as $et ) {

						// check whether template is valid.
						if ( ! ( $et['days-after'] && $et['subject'] && $et['body'] ) ) {
							continue;
						}

						// days-after should match with today.
						$days_after_date = ( clone $today )->sub( new DateInterval( 'P' . $et['days-after'] . 'D' ) );
						if ( $days_after_date->format( 'Y-m-d' ) != $date_closed->format( 'Y-m-d' ) ) {
							continue;
						}

						// register background email.
						WPSC_Background_Email::insert(
							array(
								'from_name'  => $en_general['from-name'],
								'from_email' => $en_general['from-email'],
								'reply_to'   => $en_general['reply-to'] ? $en_general['reply-to'] : $en_general['from-email'],
								'subject'    => '[' . $general_settings['ticket-alice'] . $ticket->id . '] ' . WPSC_Macros::replace( $et['subject'], $ticket ),
								'body'       => WPSC_Macros::replace( $et['body'], $ticket ),
								'to_email'   => $ticket->customer->email,
								'priority'   => 3,
							)
						);
					}
				}
			}
		}
	}
endif;

WPSC_SF_Cron::init();
