<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Actions' ) ) :

	final class WPSC_SF_Actions {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_filter( 'wpsc_macros', array( __CLASS__, 'add_macros' ) );
			add_filter( 'wpsc_replace_macros', array( __CLASS__, 'replace_macro' ), 10, 3 );

			// trigger sf when customer close ticket.
			add_action( 'wpsc_js_after_close_ticket', array( __CLASS__, 'js_after_close_ticket' ) );
			add_action( 'wp_ajax_wpsc_trigger_customer_survey', array( __CLASS__, 'trigger_survey' ) );
			add_action( 'wp_ajax_nopriv_wpsc_trigger_customer_survey', array( __CLASS__, 'trigger_survey' ) );

			// add in reports.
			add_filter( 'wpsc_default_reports', array( __CLASS__, 'default_reports' ) );
		}

		/**
		 * Add survey link macro.
		 *
		 * @param array $macro - macro tag.
		 * @return array
		 */
		public static function add_macros( $macro ) {

			$macro[] = array(
				'tag'        => '{{satisfaction_survey_links}}',
				'title'      => esc_attr__( 'Satisfaction survey links', 'wpsc-sf' ),
				'extra-info' => '',
			);
			$macro[] = array(
				'tag'        => '{{satisfaction_survey_url}}',
				'title'      => esc_attr__( 'Satisfaction survey url', 'wpsc-sf' ),
				'extra-info' => '',
			);
			return $macro;
		}

		/**
		 * Replace macros
		 *
		 * @param string      $str - string.
		 * @param WPSC_Ticket $ticket - ticket info.
		 * @param string      $macro - macro.
		 * @return string
		 */
		public static function replace_macro( $str, $ticket, $macro ) {

			if ( $macro == 'satisfaction_survey_links' ) {

				$ratings     = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
				$settings    = get_option( 'wpsc-sf-general-setting' );
				$sf_page_url = $settings['survey-page'] ? get_permalink( $settings['survey-page'] ) : '';

				$survey_links = '<table><tr><td><div>';
				foreach ( $ratings as $rating ) {
					$link          = add_query_arg(
						array(
							'ticket_id' => $ticket->id,
							'sf_id'     => $rating->id,
						),
						$sf_page_url
					);
					$survey_links .= '<table align="left" border="0" cellspacing="0" cellpadding="0" style="margin:10px 0;"><tr><td align="left" valign="middle" style="word-break: break-word;border-collapse: collapse !important;vertical-align: top"><a href="' . $link . '" target="_blank" class="wpsc-rating-item" style="color:' . $rating->color . ';background-color:' . $rating->bg_color . ';padding: 8px 10px;font-size: 14px;margin: 0 10px;text-decoration: none;">' . $rating->name . '</a></td></tr></table>';
				}
				$survey_links .= '</div></td></tr></table>';
				$str = str_replace(
					'{{satisfaction_survey_links}}',
					$survey_links,
					$str
				);
			}

			if ( $macro == 'satisfaction_survey_url' ) {

				$settings = get_option( 'wpsc-sf-general-setting' );
				$page_url = $settings['survey-page'] ? add_query_arg( array( 'ticket_id' => $ticket->id ), get_permalink( $settings['survey-page'] ) ) : '';
				$str      = str_replace(
					'{{satisfaction_survey_url}}',
					$page_url,
					$str
				);
			}
			return $str;
		}

		/**
		 * JS after close ticket
		 *
		 * @return void
		 */
		public static function js_after_close_ticket() {

			echo "wpsc_trigger_customer_survey(ticket_id,'" . esc_attr( wp_create_nonce( 'wpsc_it_get_edit_rating' ) ) . "');" . PHP_EOL;
		}

		/**
		 * Trigger survey after customer close ticket
		 *
		 * @return void
		 */
		public static function trigger_survey() {

			if ( check_ajax_referer( 'wpsc_it_get_edit_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			WPSC_Individual_Ticket::load_current_ticket();
			$current_user = WPSC_Current_User::$current_user;
			$ticket       = WPSC_Individual_Ticket::$ticket;
			$gs           = get_option( 'wpsc-gs-general' );
			$sf_settings  = get_option( 'wpsc-sf-general-setting' );

			$flag = false;
			if (
				$sf_settings['customer-trigger'] &&
				$ticket->status->id == $gs['close-ticket-status'] &&
				$ticket->customer->email == $current_user->customer->email
			) {
				$flag = true;
			}

			$nonce = wp_create_nonce( 'wpsc_it_get_edit_rating' );
			wp_send_json(
				array(
					'trigger'   => $flag,
					'ticket_id' => $ticket->id,
					'nonce'     => $nonce,
				),
				200
			);
		}

		/**
		 * Add rating in default reports setting
		 *
		 * @param array $reports - list of reports.
		 * @return array
		 */
		public static function default_reports( $reports ) {

			$reports[] = 'rating';
			return $reports;
		}
	}
endif;

WPSC_SF_Actions::init();
