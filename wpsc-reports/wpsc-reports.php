<?php // phpcs:ignore
/**
 * Plugin Name: SupportCandy - Reports
 * Plugin URI: https://supportcandy.net/
 * Description: Reports for SupportCandy
 * Version: 3.0.9
 * Author: SupportCandy
 * Author URI: https://supportcandy.net/
 * Requires at least: 5.6
 * Tested up to: 6.0
 * Text Domain: wpsc-reports
 * Domain Path: /i18n
 */

if ( ! class_exists( 'PSM_Support_Candy' ) ) {
	return;
}

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

// exit if core plugin is installing.
if ( defined( 'WPSC_INSTALLING' ) ) {
	return;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}


if ( ! class_exists( 'WPSC_RP' ) ) :

	final class WPSC_RP {

		/**
		 * Current version of the add-on
		 *
		 * @var string
		 */
		public static $version = '3.0.9';

		/**
		 * Constructor for main class
		 */
		public static function init() {

			self::define_constants();
			add_action( 'init', array( __CLASS__, 'load_textdomain' ), 1 );
			self::load_files();

			// Return if installation is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) || defined( 'WPSC_RP_INSTALLING' ) ) {
				return;
			}

			add_action( 'admin_init', array( __CLASS__, 'plugin_updator' ) );
		}

		/**
		 * Defines global constants that can be availabel anywhere in WordPress
		 *
		 * @return void
		 */
		public static function define_constants() {

			self::define( 'WPSC_RP_PLUGIN_FILE', __FILE__ );
			self::define( 'WPSC_RP_ABSPATH', __DIR__ . '/' );
			self::define( 'WPSC_RP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			self::define( 'WPSC_RP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			self::define( 'WPSC_RP_STORE_ID', 3550 );
			self::define( 'WPSC_RP_VERSION', self::$version );
		}

		/**
		 * Loads internationalization strings
		 *
		 * @return void
		 */
		public static function load_textdomain() {

			$locale = apply_filters( 'plugin_locale', get_locale(), 'wpsc-reports' );
			load_textdomain( 'wpsc-reports', WP_LANG_DIR . '/supportcandy/wpsc-reports-' . $locale . '.mo' );
			load_plugin_textdomain( 'wpsc-reports', false, plugin_basename( __DIR__ ) . '/i18n' );
		}

		/**
		 * Load all classes
		 *
		 * @return void
		 */
		private static function load_files() {

			// Load installation.
			include_once WPSC_RP_ABSPATH . 'class-wpsc-rp-installation.php';

			// Return if installation is in progress.
			if ( defined( 'WPSC_DB_UPGRADING' ) || defined( 'WPSC_RP_INSTALLING' ) ) {
				return;
			}

			// Load common classes.
			foreach ( glob( WPSC_RP_ABSPATH . 'includes/*.php' ) as $filename ) {
				include_once $filename;
			}
		}

		/**
		 * Define constants
		 *
		 * @param string $name - name of global constant.
		 * @param string $value - value of constant.
		 * @return void
		 */
		private static function define( $name, $value ) {

			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Plugin updator
		 *
		 * @return void
		 */
		public static function plugin_updator() {

			$licenses = get_option( 'wpsc-licenses', array() );
			$license  = isset( $licenses['reports'] ) ? $licenses['reports'] : array();
			if ( $license ) {
				$edd_updater = new WPSC_EDD_SL_Plugin_Updater(
					WPSC_STORE_URL,
					__FILE__,
					array(
						'version' => WPSC_RP_VERSION,
						'license' => $license['key'],
						'item_id' => WPSC_RP_STORE_ID,
						'author'  => 'Pradeep Makone',
						'url'     => home_url(),
					)
				);
			}
		}
	}
endif;

WPSC_RP::init();
