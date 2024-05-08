<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_EMT_Settings' ) ) :

	final class WPSC_EMT_Settings {

		/**
		 * Tabs for this section
		 *
		 * @var array
		 */
		private static $tabs;

		/**
		 * Current tab
		 *
		 * @var string
		 */
		public static $current_tab;

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add settings section.
			add_filter( 'wpsc_settings_page_sections', array( __CLASS__, 'add_settings_tab' ) );

			// add icon.
			add_filter( 'wpsc_icons', array( __CLASS__, 'add_icons' ) );

			// Load tabs for this section.
			add_action( 'admin_init', array( __CLASS__, 'load_tabs' ) );

			// Add current tab to admin localization data.
			add_filter( 'wpsc_admin_localizations', array( __CLASS__, 'localizations' ) );

			// Load section tab layout.
			add_action( 'wp_ajax_wpsc_get_emt_settings', array( __CLASS__, 'get_emt_settings' ) );
		}

		/**
		 * Settings tab
		 *
		 * @param array $sections - section name array.
		 * @return array
		 */
		public static function add_settings_tab( $sections ) {

			$sections['emt'] = array(
				'slug'     => 'emt',
				'icon'     => 'emailmarketing',
				'label'    => esc_attr__( 'Email Marketing Tools', 'wpsc-emt' ),
				'callback' => 'wpsc_get_emt_settings',
			);
			return $sections;
		}


		/**
		 * Load tabs for this section
		 */
		public static function load_tabs() {

			self::$tabs        = apply_filters(
				'wpsc_ep_tabs',
				array(
					'general'     => array(
						'slug'     => 'general',
						'label'    => esc_attr( wpsc__( 'General', 'supportcandy' ) ),
						'callback' => 'wpsc_emt_get_general_settings',
					),
					'mailchimp'   => array(
						'slug'     => 'mailchimp',
						'label'    => esc_attr__( 'Mailchimp', 'wpsc-ep' ),
						'callback' => 'wpsc_get_mailchimp_settings',
					),
					'getresponse' => array(
						'slug'     => 'getresponse',
						'label'    => 'GetResponse',
						'callback' => 'wpsc_get_getresponse_settings',
					),
					'sendinblue'  => array(
						'slug'     => 'brevo-sendinblue',
						'label'    => 'Brevo(SendinBlue)',
						'callback' => 'wpsc_get_sendinblue_settings',
					),
				)
			);
			self::$current_tab = isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'general'; //phpcs:ignore
		}

		/**
		 * Add icons to library
		 *
		 * @param array $icons - icon array.
		 * @return array
		 */
		public static function add_icons( $icons ) {

			$icons['emailmarketing'] = file_get_contents( WPSC_EMT_ABSPATH . 'assets/icons/email-marketing-icon.svg' ); //phpcs:ignore
			return $icons;
		}

		/**
		 * Add localizations to local JS
		 *
		 * @param array $localizations - localizations.
		 * @return array
		 */
		public static function localizations( $localizations ) {

			if ( ! ( WPSC_Settings::$is_current_page && WPSC_Settings::$current_section === 'emt' ) ) {
				return $localizations;
			}

			// Current section.
			$localizations['current_tab'] = self::$current_tab;

			return $localizations;
		}

		/**
		 * General setion body layout
		 *
		 * @return void
		 */
		public static function get_emt_settings() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}?>

			<div class="wpsc-setting-tab-container">
				<?php
				foreach ( self::$tabs as $key => $tab ) :
					$active = self::$current_tab === $key ? 'active' : ''
					?>
					<button 
						class="<?php echo esc_attr( $key ) . ' ' . esc_attr( $active ); ?>"
						onclick="<?php echo esc_attr( $tab['callback'] ) . '();'; ?>">
						<?php echo esc_attr( $tab['label'] ); ?>
						</button>
					<?php
				endforeach;
				?>
			</div>
			<div class="wpsc-setting-section-body"></div>
			<?php
			wp_die();
		}
	}
endif;

WPSC_EMT_Settings::init();
