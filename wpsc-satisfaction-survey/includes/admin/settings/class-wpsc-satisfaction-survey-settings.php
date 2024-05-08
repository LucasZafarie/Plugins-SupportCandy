<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_Satisfaction_Survey_Settings' ) ) :

	final class WPSC_Satisfaction_Survey_Settings {

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
		 */
		public static function init() {

			// add settings section.
			add_filter( 'wpsc_icons', array( __CLASS__, 'add_icons' ) );
			add_filter( 'wpsc_settings_page_sections', array( __CLASS__, 'add_settings_tab' ) );

			// Load tabs for this section.
			add_action( 'admin_init', array( __CLASS__, 'load_tabs' ) );

			// Add current tab to admin localization data.
			add_filter( 'wpsc_admin_localizations', array( __CLASS__, 'localizations' ) );

			// Load section tab layout.
			add_action( 'wp_ajax_wpsc_satisfaction_survey_setting', array( __CLASS__, 'satisfaction_survey_setting' ) );
		}

		/**
		 * Add icons to library
		 *
		 * @param array $icons - icon name.
		 * @return array
		 */
		public static function add_icons( $icons ) {

			$icons['star'] = file_get_contents( WPSC_SF_ABSPATH . 'assets/icons/star-solid.svg' ); //phpcs:ignore
			return $icons;
		}

		/**
		 * Settings tab
		 *
		 * @param array $sections - section name.
		 * @return array
		 */
		public static function add_settings_tab( $sections ) {

			$sections['satisfaction-survey'] = array(
				'slug'     => 'satisfaction_survey',
				'icon'     => 'star',
				'label'    => esc_attr__( 'Satisfaction Survey', 'wpsc-sf' ),
				'callback' => 'wpsc_satisfaction_survey_setting',
			);
			return $sections;
		}

		/**
		 * Load tabs for this section
		 */
		public static function load_tabs() {

			self::$tabs        = apply_filters(
				'wpsc_sf_tabs',
				array(
					'general' => array(
						'slug'     => 'general',
						'label'    => esc_attr( wpsc__( 'General', 'supportcandy' ) ),
						'callback' => 'wpsc_sf_general_setting',
					),
					'rating'  => array(
						'slug'     => 'rating',
						'label'    => esc_attr__( 'Ratings', 'wpsc-sf' ),
						'callback' => 'wpsc_sf_rating_setting',
					),
				)
			);
			self::$current_tab = isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'general'; //phpcs:ignore
		}

		/**
		 * Add localizations to local JS
		 *
		 * @param array $localizations - localizations.
		 * @return array
		 */
		public static function localizations( $localizations ) {

			if ( ! ( WPSC_Settings::$is_current_page && WPSC_Settings::$current_section === 'satisfaction-survey' ) ) {
				return $localizations;
			}

			// Current section.
			$localizations['current_tab'] = self::$current_tab;

			return $localizations;
		}

		/**
		 * Satisfaction survey setting
		 *
		 * @return void
		 */
		public static function satisfaction_survey_setting() {

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

WPSC_Satisfaction_Survey_Settings::init();
