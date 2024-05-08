<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ATC_Installation' ) ) :

	final class WPSC_ATC_Installation {

		/**
		 * Currently installed version.
		 *
		 * @var integer
		 */
		public static $current_version;

		/**
		 * For checking whether upgrade available or not.
		 *
		 * @var boolean
		 */
		public static $is_upgrade = false;

		/**
		 * Initialize installation
		 */
		public static function init() {

			self::get_current_version();
			self::check_upgrade();

			// db upgrade addon installer hook.
			add_action( 'wpsc_upgrade_install_addons', array( __CLASS__, 'upgrade_install' ) );

			// Database upgrade is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) ) {
				return;
			}

			if ( self::$is_upgrade ) {

				define( 'WPSC_ATC_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_atc_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_atc_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				// Run installation.
				if ( self::$current_version == 0 ) {

					add_action( 'init', array( __CLASS__, 'initial_setup' ), 1 );
					add_action( 'init', array( __CLASS__, 'set_upgrade_complete' ), 1 );

				} else {

					add_action( 'init', array( __CLASS__, 'upgrade' ), 1 );
				}

				// Delete transient.
				delete_transient( 'wpsc_atc_installing' );
			}

			// activation functionality.
			register_activation_hook( WPSC_ATC_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_ATC_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_atc_current_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_ATC_VERSION ) {
				self::$is_upgrade = true;
			}
		}

		/**
		 * DB upgrade addon installer hook callback
		 *
		 * @return void
		 */
		public static function upgrade_install() {

			self::initial_setup();
			self::set_upgrade_complete();
		}

		/**
		 * First time installation
		 */
		public static function initial_setup() {

			$string_translations = get_option( 'wpsc-string-translation' );

			// settings.
			update_option(
				'wpsc-atc-settings',
				array(
					'statuses-enabled' => array(),
					'age'              => 10,
					'close-status'     => 0,
				)
			);

			// default templates.
			$translations = array(
				0 => array(
					'subject' => 'Your ticket will be closed for inactivity',
					'body'    => '<p>Dear {{customer_first_name}},</p><p>This is to inform you that your ticket #{{id}} will be closed within 2 days for your inactivity in the ticket. You can reply to the ticket on the link given below before it gets closed.</p><p>{{ticket_url}}</p>',
				),
				1 => array(
					'subject' => 'Your ticket will be closed for inactivity',
					'body'    => '<p>Dear {{customer_first_name}},</p><p>This is to inform you that your ticket #{{id}} will be closed tomorrow for your inactivity in the ticket. You can reply to the ticket on the link given below before it gets closed.</p><p>{{ticket_url}}</p>',
				),
			);
			foreach ( $translations as $index => $translation ) {
				$string_translations[ 'wpsc-act-subject-' . $index ] = $translation['subject'];
				$string_translations[ 'wpsc-act-body-' . $index ] = $translation['body'];
			}
			update_option(
				'wpsc-atc-et',
				array(
					array(
						'title'       => 'Default template 1',
						'days-before' => 2,
						'subject'     => $translations[0]['subject'],
						'body'        => $translations[0]['body'],
						'editor'      => 'html',
					),
					array(
						'title'       => 'Default template 2',
						'days-before' => 1,
						'subject'     => $translations[1]['subject'],
						'body'        => $translations[1]['body'],
						'editor'      => 'html',
					),
				)
			);

			// update string translations.
			update_option( 'wpsc-string-translation', $string_translations );
		}

		/**
		 * Upgrade the version
		 */
		public static function upgrade() {

			self::set_upgrade_complete();
		}

		/**
		 * Mark upgrade as complete
		 */
		public static function set_upgrade_complete() {

			update_option( 'wpsc_atc_current_version', WPSC_ATC_VERSION );
			self::$current_version = WPSC_ATC_VERSION;
			self::$is_upgrade      = false;
		}

		/**
		 * Actions to perform after plugin activated
		 *
		 * @return void
		 */
		public static function activate() {

			do_action( 'wpsc_atc_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_atc_deactivate' );
		}
	}
endif;

WPSC_ATC_Installation::init();
