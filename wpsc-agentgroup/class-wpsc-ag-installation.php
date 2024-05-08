<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_AG_Installation' ) ) :

	final class WPSC_AG_Installation {

		/**
		 * Currently installed version
		 *
		 * @var integer
		 */
		public static $current_version;

		/**
		 * For checking whether upgrade available or not
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

				define( 'WPSC_AG_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_ag_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_ag_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				// Create database tables.
				self::create_db_tables();

				// Run installation.
				if ( self::$current_version == 0 ) {

					add_action( 'init', array( __CLASS__, 'initial_setup' ), 1 );
					add_action( 'init', array( __CLASS__, 'set_upgrade_complete' ), 1 );

				} else {

					add_action( 'init', array( __CLASS__, 'upgrade' ), 1 );
				}

				// Delete transient.
				delete_transient( 'wpsc_ag_installing' );
			}

			// Activation functionality.
			register_activation_hook( WPSC_AG_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_AG_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_ag_current_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_AG_VERSION ) {
				self::$is_upgrade = true;
			}
		}

		/**
		 * DB upgrade addon installer hook callback
		 *
		 * @return void
		 */
		public static function upgrade_install() {

			self::create_db_tables();
			self::initial_setup();
			self::set_upgrade_complete();
		}

		/**
		 * Create database tables
		 */
		public static function create_db_tables() {

			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$tables = "
				CREATE TABLE {$wpdb->prefix}psmsc_agentgroups (
					id INT NOT NULL AUTO_INCREMENT,
					agent_id BIGINT NOT NULL,
					name VARCHAR(200) NOT NULL,
					agents TEXT NOT NULL,
					supervisors TEXT NOT NULL,
					PRIMARY KEY (id)
				) $collate;
			";

			dbDelta( $tables );
		}

		/**
		 * First time installation
		 */
		public static function initial_setup() {
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

			update_option( 'wpsc_ag_current_version', WPSC_AG_VERSION );
			self::$current_version = WPSC_AG_VERSION;
			self::$is_upgrade      = false;
		}

		/**
		 * Actions to perform after plugin activated
		 *
		 * @return void
		 */
		public static function activate() {

			do_action( 'wpsc_agentgroup_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_agentgroup_deactivate' );
		}
	}
endif;

WPSC_AG_Installation::init();
