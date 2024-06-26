<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ATC_Cron' ) ) :

	final class WPSC_ATC_Cron {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// schedule cron jobs.
			add_action( 'init', array( __CLASS__, 'schedule_events' ) );

			// execute schedulers.
			add_action( 'wpsc_atc_warning_emails', array( __CLASS__, 'send_warning_emails' ) );
			add_action( 'wpsc_atc_close_tickets', array( __CLASS__, 'close_tickets' ) );
		}

		/**
		 * Schedule cron job events for SupportCandy
		 *
		 * @return void
		 */
		public static function schedule_events() {

			// warning emails scheduler.
			if ( ! wp_next_scheduled( 'wpsc_atc_warning_emails' ) ) {
				wp_schedule_event(
					time(),
					'hourly',
					'wpsc_atc_warning_emails'
				);
			}

			// close ticket scheduler.
			if ( ! wp_next_scheduled( 'wpsc_atc_close_tickets' ) ) {
				wp_schedule_event(
					time(),
					'hourly',
					'wpsc_atc_close_tickets'
				);
			}
		}

		/**
		 * Send automatic close warning emails
		 *
		 * @return void
		 */
		public static function send_warning_emails() {

			$tz = wp_timezone();
			$today = new DateTime( 'now', $tz );
			$transient_label = 'wpsc_atc_warning_emails_cron_' . $today->format( 'Y-m-d' );

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

			$general_settings = get_option( 'wpsc-gs-general' );
			$settings = get_option( 'wpsc-atc-settings' );
			$email_templates = get_option( 'wpsc-atc-et' );
			$en_general = get_option( 'wpsc-en-general' );
			$is_valid_email_settings = $en_general['from-name'] && $en_general['from-email'] ? true : false;

			$max_days_before = 0;
			foreach ( $email_templates as $et ) {

				// continue if days before closing is greater than close ticket age.
				if ( $settings['age'] < $et['days-before'] ) {
					continue;
				}

				$max_days_before = $et['days-before'] > $max_days_before ? $et['days-before'] : $max_days_before;
			}

			$close_status = new WPSC_Status( $settings['close-status'] );

			// return if not valid.
			if (
				$settings['age'] === 0 ||
				! $settings['statuses-enabled'] ||
				! $close_status->id ||
				! $is_valid_email_settings ||
				$max_days_before === 0
			) {
				return;
			}

			// Get applicable tickets.
			$age = ( clone $today )->sub( new DateInterval( 'P' . ( $settings['age'] - 1 ) . 'D' ) );
			$max_before = ( clone $today )->sub( new DateInterval( 'P' . ( $settings['age'] - $max_days_before ) . 'D' ) );

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
							'slug'    => 'date_updated',
							'compare' => 'BETWEEN',
							'val'     => array(
								'operand_val_1' => $age->format( 'Y-m-d' ),
								'operand_val_2' => $max_before->format( 'Y-m-d' ),
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

			// send email notifications for applicable tickets.
			if ( $tickets['total_items'] > 0 ) {

				foreach ( $tickets['results'] as $ticket ) {

					$date_updated = $ticket->date_updated->setTimezone( $tz );

					// Check if To email is blocked or forwarding email.
					if ( in_array( $ticket->customer->email, WPSC_Email_Notifications::$block_emails ) ) {
						continue;
					}

					foreach ( $email_templates as $et ) {

						// continue if days before closing is greater than close ticket age.
						if ( $settings['age'] < $et['days-before'] ) {
							continue;
						}

						// check whether template is valid.
						if ( ! ( $et['days-before'] && $et['subject'] && $et['body'] ) ) {
							continue;
						}

						// day difference should match with today.
						$days_before_date = ( clone $today )->sub( new DateInterval( 'P' . ( $settings['age'] - $et['days-before'] ) . 'D' ) );
						if ( $days_before_date->format( 'Y-m-d' ) != $date_updated->format( 'Y-m-d' ) ) {
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
								'priority'   => 2,
							)
						);
					}
				}
			}
		}

		/**
		 * Close tickets after given inactive age
		 *
		 * @return void
		 */
		public static function close_tickets() {

			$tz = wp_timezone();
			$today = new DateTime( 'now', $tz );
			$transient_label = 'wpsc_atc_close_tickets_cron_' . $today->format( 'Y-m-d' );

			$cron_status = get_transient( $transient_label );
			if ( false === $cron_status ) {
				$cron_status = 'active';
			}

			// return if today's tickets finished checking.
			if ( $cron_status == 'finished' ) {
				return;
			}

			$settings = get_option( 'wpsc-atc-settings' );

			// return if not valid.
			$close_status = new WPSC_Status( $settings['close-status'] );
			if ( $settings['age'] === 0 || ! $settings['statuses-enabled'] || ! $close_status->id ) {
				return;
			}

			// Get applicable tickets.
			$age = ( clone $today )->sub( new DateInterval( 'P' . ( $settings['age'] - 1 ) . 'D' ) );
			$tickets = WPSC_Ticket::find(
				array(
					'items_per_page' => 20,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'slug'    => 'status',
							'compare' => 'IN',
							'val'     => $settings['statuses-enabled'],
						),
						array(
							'slug'    => 'date_updated',
							'compare' => '<',
							'val'     => $age->format( 'Y-m-d' ),
						),
					),
				)
			);

			// update cron status.
			delete_transient( $transient_label );
			$cron_status = $tickets['has_next_page'] ? 'active' : 'finished';
			set_transient( $transient_label, $cron_status, MINUTE_IN_SECONDS * 60 * 24 );

			// close applicable tickets.
			if ( $tickets['total_items'] > 0 ) {

				foreach ( $tickets['results'] as $ticket ) {

					WPSC_Individual_Ticket::$ticket = $ticket;
					WPSC_Individual_Ticket::change_status( $ticket->status->id, $settings['close-status'], 0 );
				}
			}
		}
	}
endif;

WPSC_ATC_Cron::init();
