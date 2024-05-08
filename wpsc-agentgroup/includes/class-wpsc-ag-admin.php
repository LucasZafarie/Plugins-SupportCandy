<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_AG_Admin' ) ) :

	final class WPSC_AG_Admin {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// Load scripts & styles.
			add_action( 'wpsc_js_backend', array( __CLASS__, 'backend_scripts' ) );
			add_action( 'wpsc_css_backend', array( __CLASS__, 'backend_styles' ) );

			// Add recepients in email notification.
			add_filter( 'wpsc_en_general_recipients', array( __CLASS__, 'add_recipients' ) );

			// Add agentgroup memebers or supervisors in email notification.
			add_filter( 'wpsc_en_get_to_addresses', array( __CLASS__, 'add_agentgroup_emails' ), 10, 3 );
			add_filter( 'wpsc_en_get_cc_addresses', array( __CLASS__, 'add_agentgroup_emails' ), 10, 3 );
			add_filter( 'wpsc_en_get_bcc_addresses', array( __CLASS__, 'add_agentgroup_emails' ), 10, 3 );
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_scripts() {

			echo file_get_contents( WPSC_AG_ABSPATH . 'asset/js/admin.js' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
		}

		/**
		 * Backend scripts
		 *
		 * @return void
		 */
		public static function backend_styles() {

			if ( is_rtl() ) {
				echo file_get_contents( WPSC_AG_ABSPATH . 'asset/css/admin-rtl.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			} else {
				echo file_get_contents( WPSC_AG_ABSPATH . 'asset/css/admin.css' ) . PHP_EOL . PHP_EOL; // phpcs:ignore
			}
		}

		/**
		 * Add agentgroups in ticket notification recipient list
		 *
		 * @param array $gereral_recipients - Email notification recipients.
		 * @return array
		 */
		public static function add_recipients( $gereral_recipients ) {

			$gereral_recipients['agentgroup-members']     = esc_attr__( 'Agentgroup Members', 'wpsc-ag' );
			$gereral_recipients['agentgroup-supervisors'] = esc_attr__( 'Agentgroup Supervisors', 'wpsc-ag' );

			return $gereral_recipients;
		}

		/**
		 * Add agentgroup memebers or agentgroup supervisor in email notification
		 *
		 * @param array                    $general_recipients - Email notification recipients.
		 * @param string                   $recipient - Email notification recipient name.
		 * @param WPSC_Email_Notifications $en - WPSC_Email_Notifications object.
		 * @return array
		 */
		public static function add_agentgroup_emails( $general_recipients, $recipient, $en ) {

			switch ( $recipient ) {

				case 'agentgroup-members':
					foreach ( $en->ticket->assigned_agent as $agent ) :

						if ( $agent->is_agentgroup ) :

							$agentgroup = WPSC_Agentgroup::get_by_agent_id( $agent->id );
							foreach ( $agentgroup->agents as $agent ) {

								if ( ! $agent->is_active ) {
									continue;
								}
								$general_recipients[] = $agent->customer->email;
							}

						endif;
					endforeach;
					break;

				case 'assignee':
				case 'agentgroup-supervisors':
					foreach ( $en->ticket->assigned_agent as $agent ) :

						if ( $agent->is_agentgroup ) :

							$agentgroup = WPSC_Agentgroup::get_by_agent_id( $agent->id );
							foreach ( $agentgroup->supervisors as $agent ) {

								if ( ! $agent->is_active ) {
									continue;
								}
								$general_recipients[] = $agent->customer->email;
							}

						endif;
					endforeach;
					break;
			}

			return $general_recipients;
		}
	}
endif;

WPSC_AG_Admin::init();
