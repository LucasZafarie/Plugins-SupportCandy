<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_UG_Installation' ) ) :

	final class WPSC_UG_Installation {

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

				define( 'WPSC_UG_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_ug_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_ug_installing', 'yes', MINUTE_IN_SECONDS * 10 );

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
				delete_transient( 'wpsc_ug_installing' );
			}

			// activation functionality.
			register_activation_hook( WPSC_USERGROUP_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_USERGROUP_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_usergroup_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_USERGROUP_VERSION ) {
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
				CREATE TABLE {$wpdb->prefix}psmsc_usergroups (
					id BIGINT NOT NULL AUTO_INCREMENT,
					name VARCHAR(200) NOT NULL,
					description TEXT NULL,
					members TEXT NULL,
					supervisors TEXT NULL,
					category INT NULL,
					PRIMARY KEY (id)
				) $collate;
			";

			dbDelta( $tables );
		}

		/**
		 * First time installation
		 */
		public static function initial_setup() {

			global $wpdb;

			$string_translations = get_option( 'wpsc-string-translation' );

			// custom field type.
			$name = esc_attr__( 'Grupo de usuários', 'wpsc-usergroup' );
			$extra_info = esc_attr__( 'Please select usergroups', 'wpsc-usergroup' );
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_custom_fields',
				array(
					'name'       => $name,
					'extra_info' => $extra_info,
					'slug'       => 'usergroups',
					'field'      => 'ticket',
					'type'       => 'df_usergroups',
				)
			);
			$cf_id = $wpdb->insert_id;
			$string_translations[ 'wpsc-cf-name-' . $cf_id ] = $name;
			$string_translations[ 'wpsc-cf-exi-' . $cf_id ] = $extra_info;

			// add usergroups column to ticket table for additional recipients.
			$sql  = "ALTER TABLE {$wpdb->prefix}psmsc_tickets ";
			$sql .= 'ADD usergroups TINYTEXT NULL, ';
			$sql .= 'ADD ar_usergroups TINYTEXT NULL';
			$wpdb->query( $sql );

			// add usergroups to ticket form.
			$tff = get_option( 'wpsc-tff' );
			$tff['usergroups'] = array(
				'is-required' => 0,
				'width'       => 'full',
				'relation'    => 'AND',
				'visibility'  => '',
			);
			update_option( 'wpsc-tff', $tff );

			// agent role permission.
			$roles = get_option( 'wpsc-agent-roles', array() );
			foreach ( $roles as $index => $role ) {
				$roles[ $index ]['caps']['modify-ug-unassigned']      = true;
				$roles[ $index ]['caps']['modify-ug-assigned-me']     = true;
				$roles[ $index ]['caps']['modify-ug-assigned-others'] = true;
			}
			update_option( 'wpsc-agent-roles', $roles );

			// general settings.
			update_option(
				'wpsc-ug-general-settings',
				array(
					'auto-assign'               => 1,
					'allow-customers-to-modify' => 0,
					'auto-fill'                 => 1,
					'allow-change-category'     => 1,
					'allow-sup-close-ticket'    => 0,
				)
			);

			// update string translations.
			update_option( 'wpsc-string-translation', $string_translations );

			// install widget.
			self::install_widget();

			// dashboard card / widget.
			self::install_db_cards_widgets();
		}

		/**
		 * Upgrade the version
		 */
		public static function upgrade() {

			global $wpdb;

			if ( version_compare( self::$current_version, '3.0.2', '<' ) ) {

				// Remove from customer table and insert into ticket table.
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}psmsc_customers DROP usergroups" );
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}psmsc_tickets ADD usergroups TINYTEXT NULL" );

				// Modify cusotom field type.
				$string_translations = get_option( 'wpsc-string-translation' );
				$extra_info = esc_attr__( 'Please select usergroups', 'wpsc-usergroup' );
				$cf_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}psmsc_custom_fields WHERE slug='usergroups'" );
				$wpdb->update(
					$wpdb->prefix . 'psmsc_custom_fields',
					array(
						'extra_info' => $extra_info,
						'field'      => 'ticket',
					),
					array( 'id' => $cf_id )
				);
				$string_translations[ 'wpsc-cf-exi-' . $cf_id ] = $extra_info;
				update_option( 'wpsc-string-translation', $string_translations );

				// Add usergroups field in ticket form.
				$tff = get_option( 'wpsc-tff' );
				$tff['usergroups'] = array(
					'is-required' => 0,
					'width'       => 'full',
					'relation'    => 'AND',
					'visibility'  => '',
				);
				update_option( 'wpsc-tff', $tff );

				// agent role permission.
				$roles = get_option( 'wpsc-agent-roles', array() );
				foreach ( $roles as $index => $role ) {
					$role['caps']['modify-ug-unassigned']      = true;
					$role['caps']['modify-ug-assigned-me']     = true;
					$role['caps']['modify-ug-assigned-others'] = true;
					$roles[ $index ] = $role;
				}
				update_option( 'wpsc-agent-roles', $roles );

				// update settings.
				$settings = get_option( 'wpsc-ug-general-settings' );
				$settings['auto-assign'] = 1;
				$settings['allow-customers-to-modify'] = 0;
				$settings['auto-fill'] = 1;
				update_option( 'wpsc-ug-general-settings', $settings );

				// allow widget for customers.
				$widgets = get_option( 'wpsc-ticket-widget', array() );
				if ( isset( $widgets['usergroups'] ) ) {
					$widgets['usergroups']['allow-customer'] = 1;
					update_option( 'wpsc-ticket-widget', $widgets );
				}

				// add scheduled task for ticket data upgrade.
				$wpdb->insert(
					$wpdb->prefix . 'psmsc_scheduled_tasks',
					array(
						'class'             => 'WPSC_UG_Upgrade',
						'method'            => 'update_ticket_usergroups',
						'is_manual'         => 1,
						'warning_text'      => 'SupportCandy - Usergroups: Database upgrade needed.',
						'warning_link_text' => 'Upgrade Now',
						'progressbar_text'  => 'Updating usergroups for the tickets',
					)
				);
			}

			if ( version_compare( self::$current_version, '3.1.1', '<' ) ) {

				// dashboard card / widget.
				self::install_db_cards_widgets();
			}

			self::set_upgrade_complete();
		}

		/**
		 * Mark upgrade as complete
		 */
		public static function set_upgrade_complete() {

			update_option( 'wpsc_usergroup_version', WPSC_USERGROUP_VERSION );
			self::$current_version = WPSC_USERGROUP_VERSION;
			self::$is_upgrade      = false;
		}

		/**
		 * Actions to perform after plugin activated
		 *
		 * @return void
		 */
		public static function activate() {

			// Widget might not be installed as a result of race condition while upgrade.
			// There is an option for administrator to deactivate and then activate the plugin.
			self::install_widget();

			// dashboard card / widget.
			self::install_db_cards_widgets();
			do_action( 'wpsc_usergroup_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_usergroup_deactivate' );
		}

		/**
		 * Install widget if not already installed
		 *
		 * @return void
		 */
		public static function install_widget() {

			$widgets = get_option( 'wpsc-ticket-widget', array() );
			if ( ! isset( $widgets['usergroups'] ) ) {

				$agent_roles = array_keys( get_option( 'wpsc-agent-roles', array() ) );
				$label = esc_attr__( 'Grupo de usuários', 'wpsc-usergroup' );
				$widgets['usergroups'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'show-members'        => 1,
					'allow-customer'      => 1,
					'allowed-agent-roles' => $agent_roles,
					'callback'            => 'wpsc_get_tw_usergroups()',
					'class'               => 'WPSC_ITW_Usergroups',
				);
				update_option( 'wpsc-ticket-widget', $widgets );

				// string translations.
				$string_translations = get_option( 'wpsc-string-translation' );
				$string_translations['wpsc-twt-usergroups'] = $label;
				update_option( 'wpsc-string-translation', $string_translations );
			}
		}

		/**
		 * Install dashboard card or widgets if not already installed
		 *
		 * @return void
		 */
		public static function install_db_cards_widgets() {

			// UG dashboard widget.
			$widgets = get_option( 'wpsc-dashboard-widgets', array() );
			$string_translations = get_option( 'wpsc-string-translation' );

			if ( ! isset( $widgets['usergroups'] ) ) {

				$label = esc_attr__( 'Usergroups', 'supportcandy' );
				$string_translations['wpsc-dashboard-widget-usergroups'] = $label;
				$widgets['usergroups'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'allowed-agent-roles' => array( 1 ),
					'callback'            => 'wpsc_dbw_usergroups()',
					'class'               => 'WPSC_DBW_Usergroups',
					'type'                => 'default',
					'chart-type'          => '',
				);
			}

			update_option( 'wpsc-dashboard-widgets', $widgets );
			update_option( 'wpsc-string-translation', $string_translations );
		}
	}
endif;

WPSC_UG_Installation::init();
