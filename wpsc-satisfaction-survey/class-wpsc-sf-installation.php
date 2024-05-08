<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Installation' ) ) :

	final class WPSC_SF_Installation {

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

				define( 'WPSC_SF_INSTALLING', true );

				// Do not allow parallel process to run.
				if ( 'yes' === get_transient( 'wpsc_sf_installing' ) ) {
					return;
				}

				// Set transient.
				set_transient( 'wpsc_sf_installing', 'yes', MINUTE_IN_SECONDS * 10 );

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
				delete_transient( 'wpsc_sf_installing' );
			}

			// activation functionality.
			register_activation_hook( WPSC_SF_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

			// Deactivate functionality.
			register_deactivation_hook( WPSC_SF_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Check version
		 */
		public static function get_current_version() {

			self::$current_version = get_option( 'wpsc_sf_current_version', 0 );
		}

		/**
		 * Check for upgrade
		 */
		public static function check_upgrade() {

			if ( self::$current_version != WPSC_SF_VERSION ) {
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
				CREATE TABLE {$wpdb->prefix}psmsc_sf_ratings (
					id BIGINT NOT NULL AUTO_INCREMENT,
					name VARCHAR(200) NOT NULL,
					color VARCHAR(50) NOT NULL,
					bg_color VARCHAR(50) NOT NULL,
					confirmation_text LONGTEXT NOT NULL,
					load_order INT NOT NULL DEFAULT 1,
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

			// Custom field types.
			$name = esc_attr__( 'Rating', 'wpsc-sf' );
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_custom_fields',
				array(
					'name'  => $name,
					'slug'  => 'rating',
					'field' => 'ticket',
					'type'  => 'df_sf_rating',
				)
			);
			$string_translations[ 'wpsc-cf-name-' . $wpdb->insert_id ] = $name;

			$name = esc_attr__( 'Feedback', 'wpsc-sf' );
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_custom_fields',
				array(
					'name'  => $name,
					'slug'  => 'sf_feedback',
					'field' => 'ticket',
					'type'  => 'df_sf_feedback',
				)
			);
			$string_translations[ 'wpsc-cf-name-' . $wpdb->insert_id ] = $name;

			$name = esc_attr__( 'Feedback Date', 'wpsc-sf' );
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_custom_fields',
				array(
					'name'  => $name,
					'slug'  => 'sf_date',
					'field' => 'ticket',
					'type'  => 'df_sf_date',
				)
			);
			$string_translations[ 'wpsc-cf-name-' . $wpdb->insert_id ] = $name;

			// add ticket colomns for custom field types.
			$sql  = "ALTER TABLE {$wpdb->prefix}psmsc_tickets ";
			$sql .= 'ADD rating INT NULL DEFAULT NULL, ';
			$sql .= 'ADD sf_feedback TEXT NULL DEFAULT NULL, ';
			$sql .= 'ADD sf_date DATETIME NULL DEFAULT NULL';
			$wpdb->query( $sql );

			// default ratings.
			$name = esc_attr__( 'Terrible', 'wpsc-sf' );
			$confirmation_text = 'Thank you for bringing this to our attention. We are sorry you had a bad experience. We will strive to do better.';
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_sf_ratings',
				array(
					'name'              => $name,
					'color'             => '#FFFFFF',
					'bg_color'          => '#FF0000',
					'confirmation_text' => $confirmation_text,
					'load_order'        => 1,
				)
			);
			$string_translations[ 'wpsc-rating-name-' . $wpdb->insert_id ] = $name;
			$string_translations[ 'wpsc-rating-ct-' . $wpdb->insert_id ] = $confirmation_text;

			$name = esc_attr__( 'Bad', 'wpsc-sf' );
			$confirmation_text = 'We are so sorry that your experience did not match your expectations. This is on us.';
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_sf_ratings',
				array(
					'name'              => $name,
					'color'             => '#FFFFFF',
					'bg_color'          => '#E35213',
					'confirmation_text' => $confirmation_text,
					'load_order'        => 2,
				)
			);
			$string_translations[ 'wpsc-rating-name-' . $wpdb->insert_id ] = $name;
			$string_translations[ 'wpsc-rating-ct-' . $wpdb->insert_id ] = $confirmation_text;

			$name = esc_attr__( 'Okay', 'wpsc-sf' );
			$confirmation_text = 'Thank you for your feedback. We will strive to do better.';
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_sf_ratings',
				array(
					'name'              => $name,
					'color'             => '#FFFFFF',
					'bg_color'          => '#969B3A',
					'confirmation_text' => $confirmation_text,
					'load_order'        => 3,
				)
			);
			$string_translations[ 'wpsc-rating-name-' . $wpdb->insert_id ] = $name;
			$string_translations[ 'wpsc-rating-ct-' . $wpdb->insert_id ] = $confirmation_text;

			$name = esc_attr__( 'Good', 'wpsc-sf' );
			$confirmation_text = 'We are so happy you loved the experience.';
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_sf_ratings',
				array(
					'name'              => $name,
					'color'             => '#FFFFFF',
					'bg_color'          => '#81d742',
					'confirmation_text' => $confirmation_text,
					'load_order'        => 4,
				)
			);
			$string_translations[ 'wpsc-rating-name-' . $wpdb->insert_id ] = $name;
			$string_translations[ 'wpsc-rating-ct-' . $wpdb->insert_id ] = $confirmation_text;

			$name = esc_attr__( 'Excellent', 'wpsc-sf' );
			$confirmation_text = 'We are thrilled! Thank you for your feedback.';
			$wpdb->insert(
				$wpdb->prefix . 'psmsc_sf_ratings',
				array(
					'name'              => $name,
					'color'             => '#ffffff',
					'bg_color'          => '#54B42D',
					'confirmation_text' => $confirmation_text,
					'load_order'        => 5,
				)
			);
			$string_translations[ 'wpsc-rating-name-' . $wpdb->insert_id ] = $name;
			$string_translations[ 'wpsc-rating-ct-' . $wpdb->insert_id ] = $confirmation_text;

			// general settings.
			update_option(
				'wpsc-sf-general-setting',
				array(
					'survey-page'      => 0,
					'customer-trigger' => 1,
					'statuses-enabled' => array( 4 ),
				)
			);

			// default email templates.
			$subject = 'How was your experience?';
			$body = '<p>Dear {{customer_first_name}},</p><p>Please tell us about your experience with the support given in ticket #{{ticket_id}}. Your feedback helps us to create a better experience for yourself and other customers.</p><p>Click one of the links below and we will be immediately notified of your choice!</p><p>{{satisfaction_survey_links}}</p>';
			update_option(
				'wpsc-sf-et',
				array(
					array(
						'title'      => 'Default template 1',
						'days-after' => 1,
						'subject'    => $subject,
						'body'       => $body,
						'editor'     => 'html',
					),
					array(
						'title'      => 'Default template 2',
						'days-after' => 3,
						'subject'    => $subject,
						'body'       => $body,
						'editor'     => 'html',
					),
				)
			);
			$string_translations['wpsc-sf-et-subject-0'] = $subject;
			$string_translations['wpsc-sf-et-body-0'] = $body;

			// update string translations.
			update_option( 'wpsc-string-translation', $string_translations );

			// widget.
			self::install_widget();

			// dashboard card / widget.
			self::install_db_cards_widgets();
		}

		/**
		 * Upgrade the version
		 */
		public static function upgrade() {

			if ( version_compare( self::$current_version, '3.1.0', '<' ) ) {

				// dashboard card / widget.
				self::install_db_cards_widgets();
			}

			self::set_upgrade_complete();
		}

		/**
		 * Mark upgrade as complete
		 */
		public static function set_upgrade_complete() {

			update_option( 'wpsc_sf_current_version', WPSC_SF_VERSION );
			self::$current_version = WPSC_SF_VERSION;
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
			self::install_db_cards_widgets();
			do_action( 'wpsc_sf_activate' );
		}

		/**
		 * Actions to perform after plugin deactivated
		 *
		 * @return void
		 */
		public static function deactivate() {

			do_action( 'wpsc_sf_deactivate' );
		}

		/**
		 * Install widget if not already installed
		 *
		 * @return void
		 */
		public static function install_widget() {

			$widgets = get_option( 'wpsc-ticket-widget', array() );
			if ( ! isset( $widgets['rating'] ) ) {

				$agent_roles = array_keys( get_option( 'wpsc-agent-roles', array() ) );
				$label = esc_attr__( 'Rating', 'wpsc-sf' );
				$widgets['rating'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'allow-customer'      => 1,
					'allowed-agent-roles' => $agent_roles,
					'callback'            => 'wpsc_get_tw_rating()',
					'class'               => 'WPSC_TW_Rating',
				);
				update_option( 'wpsc-ticket-widget', $widgets );

				// string translations.
				$string_translations = get_option( 'wpsc-string-translation' );
				$string_translations['wpsc-twt-rating'] = $label;
				update_option( 'wpsc-string-translation', $string_translations );
			}
		}

		/**
		 * Install database cards or widget if not already installed
		 *
		 * @return void
		 */
		public static function install_db_cards_widgets() {

			// SF dashboard widget.
			$widgets = get_option( 'wpsc-dashboard-widgets', array() );
			$string_translations = get_option( 'wpsc-string-translation', array() );

			if ( ! isset( $widgets['ticket-feedback'] ) ) {

				$label = esc_attr__( 'Avaliação da ocorrência', 'supportcandy' );
				$string_translations['wpsc-dashboard-widget-ticket-feedback'] = $label;
				$widgets['ticket-feedback'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'allowed-agent-roles' => array( 1, 2 ),
					'callback'            => 'wpsc_dbw_ticket_feedback()',
					'class'               => 'WPSC_DBW_Ticket_Feedback',
					'type'                => 'default',
					'chart-type'          => '',
				);
			}

			if ( ! isset( $widgets['agents-performance'] ) ) {

				$label = esc_attr__( 'Agent Ratings', 'supportcandy' );
				$string_translations['wpsc-dashboard-widget-agents-performance'] = $label;
				$widgets['agents-performance'] = array(
					'title'               => $label,
					'is_enable'           => 1,
					'allowed-agent-roles' => array( 1 ),
					'callback'            => 'wpsc_dbw_agents_performance()',
					'class'               => 'WPSC_DBW_Agents_Performance',
					'type'                => 'default',
					'chart-type'          => '',
				);
			}

			update_option( 'wpsc-dashboard-widgets', $widgets );
			update_option( 'wpsc-string-translation', $string_translations );
		}
	}
endif;

WPSC_SF_Installation::init();
