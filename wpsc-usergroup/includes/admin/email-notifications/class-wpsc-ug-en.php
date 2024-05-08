<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_EN' ) ) :

	final class WPSC_UG_EN {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add recepients in email notification.
			add_filter( 'wpsc_en_general_recipients', array( __CLASS__, 'add_recipients' ) );

			// add usergroup memebers or supervisors in email notification.
			add_filter( 'wpsc_en_get_to_addresses', array( __CLASS__, 'add_usergroup_emails' ), 10, 3 );
			add_filter( 'wpsc_en_get_cc_addresses', array( __CLASS__, 'add_usergroup_emails' ), 10, 3 );
			add_filter( 'wpsc_en_get_bcc_addresses', array( __CLASS__, 'add_usergroup_emails' ), 10, 3 );
		}

		/**
		 * Add usergroups in ticket notification recipient list
		 *
		 * @param array $gereral_recipients - gereral recipients.
		 * @return array
		 */
		public static function add_recipients( $gereral_recipients ) {

			$gereral_recipients['usergroup-members']     = esc_attr__( 'Usergroup Members', 'wpsc-usergroup' );
			$gereral_recipients['usergroup-supervisors'] = esc_attr__( 'Usergroup Supervisors', 'wpsc-usergroup' );

			return $gereral_recipients;
		}

		/**
		 * Add usergroup memebers or usergroup supervisor in email notification
		 *
		 * @param array                    $general_recipients - general recepients.
		 * @param string                   $recipient - recepients.
		 * @param WPSC_Email_Notifications $en - email notification object.
		 * @return array
		 */
		public static function add_usergroup_emails( $general_recipients, $recipient, $en ) {

			if ( $recipient == 'usergroup-members' || $recipient == 'usergroup-supervisors' ) {

				$usergroups = $en->ticket->usergroups;

				if ( $usergroups ) {

					foreach ( $usergroups as $usergroup ) {

						$members = array();
						if ( $recipient == 'usergroup-members' ) {
							$members = $usergroup->members;
						} else {
							$members = $usergroup->supervisors;
						}

						foreach ( $members as $user ) {
							$general_recipients[] = $user->email;
						}
					}
				}
			}

			return $general_recipients;
		}
	}
endif;

WPSC_UG_EN::init();
