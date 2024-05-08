<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_RP_Admin_Submenu' ) ) :

	final class WPSC_RP_Admin_Submenu {

		/**
		 * Set if current screen is agents page
		 *
		 * @var boolean
		 */
		public static $is_current_page;

		/**
		 * Sections for this view
		 *
		 * @var array
		 */
		public static $sections;

		/**
		 * Current section to load
		 *
		 * @var string
		 */
		private static $current_section;

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// add submenu.
			add_action( 'wpsc_before_setting_admin_menu', array( __CLASS__, 'add_reports_submenu' ) );

			// Load sections for this screen.
			add_action( 'admin_init', array( __CLASS__, 'load_sections' ) );

			// Add current section to admin localization data.
			add_filter( 'wpsc_admin_localizations', array( __CLASS__, 'localizations' ) );

			// Humbargar modal.
			add_action( 'admin_footer', array( __CLASS__, 'humbargar_menu' ) );

			// Register ready function.
			add_action( 'wpsc_js_ready', array( __CLASS__, 'register_js_ready_function' ) );

			// Run report.
			add_action( 'admin_footer', array( __CLASS__, 'wpsc_rp_run' ) );
		}

		/**
		 * Add reports submenu
		 *
		 * @return void
		 */
		public static function add_reports_submenu() {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				return;
			}

			add_submenu_page(
				'wpsc-tickets',
				esc_attr__( 'Reports', 'wpsc-reports' ),
				esc_attr__( 'Reports', 'wpsc-reports' ),
				'wpsc_agent',
				'wpsc-reports',
				array( __CLASS__, 'layout' )
			);
		}

		/**
		 * Load section (nav elements) for this screen
		 *
		 * @return void
		 */
		public static function load_sections() {

			self::$is_current_page = isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'wpsc-reports' ? true : false; // phpcs:ignore

			self::$sections = apply_filters(
				'wpsc_reports_sections',
				array(
					'ticket-statistics' => array(
						'slug'     => 'ticket_statistics',
						'icon'     => 'chart-line',
						'label'    => esc_attr__( 'Ticket Statistics', 'wpsc-reports' ),
						'callback' => 'wpsc_rp_get_ticket_statistics',
						'run'      => array(
							'shortname' => 'ts',
							'function'  => 'wpsc_rp_run_ts_report',
						),
					),
					'response-delay'    => array(
						'slug'     => 'response_delay',
						'icon'     => 'reply',
						'label'    => esc_attr__( 'Atraso da resposta', 'wpsc-reports' ),
						'callback' => 'wpsc_rp_get_response_delay',
						'run'      => array(
							'shortname' => 'rd',
							'function'  => 'wpsc_rp_run_rd_report',
						),
					),
					'closing-delay'     => array(
						'slug'     => 'closing_delay',
						'icon'     => 'checked',
						'label'    => esc_attr__( 'Atraso no Fechamento do Ticket', 'wpsc-reports' ),
						'callback' => 'wpsc_rp_get_closing_delay',
						'run'      => array(
							'shortname' => 'cd',
							'function'  => 'wpsc_rp_run_cd_report',
						),
					),
					'communication-gap' => array(
						'slug'     => 'communication_gap',
						'icon'     => 'people-arrows',
						'label'    => esc_attr__( 'Communication Gap', 'wpsc-reports' ),
						'callback' => 'wpsc_rp_get_communication_gap',
						'run'      => array(
							'shortname' => 'cg',
							'function'  => 'wpsc_rp_run_cg_report',
						),
					),
				)
			);

			if ( ! self::$is_current_page ) {
				return;
			}

			self::$current_section = isset( $_REQUEST['section'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['section'] ) ) : 'ticket-statistics'; // phpcs:ignore
		}

		/**
		 * Menu layout
		 *
		 * @return void
		 */
		public static function layout() {

			$current_user = WPSC_Current_User::$current_user;
			if ( ! ( $current_user->is_agent && $current_user->agent->has_cap( 'view-reports' ) ) ) {
				return;
			}?>

			<div class="wrap">
				<hr class="wp-header-end">
				<div id="wpsc-container" style="display:none;">
					<div class="wpsc-header wpsc-setting-header-xs wpsc-visible-xs">
						<div class="wpsc-humbargar-title">
							<?php WPSC_Icons::get( self::$sections[ self::$current_section ]['icon'] ); ?>
							<label><?php echo esc_attr( self::$sections[ self::$current_section ]['label'] ); ?></label>
						</div>
						<div class="wpsc-humbargar" onclick="wpsc_toggle_humbargar();">
							<?php WPSC_Icons::get( 'bars' ); ?>
						</div>
					</div>
					<div class="wpsc-settings-page">
						<div class="wpsc-setting-section-container wpsc-hidden-xs">
							<h2><?php esc_attr_e( 'Reports', 'wpsc-reports' ); ?></h2>
							<?php
							foreach ( self::$sections as $key => $section ) :

								$active = self::$current_section === $key ? 'active' : '';
								?>
								<div 
									class="wpsc-setting-nav <?php echo esc_attr( $key ) . ' ' . esc_attr( $active ); ?>"
									onclick="<?php echo esc_attr( $section['callback'] ) . "('" . esc_attr( $key ) . "','" . esc_attr( wp_create_nonce( $key ) ) . "');"; ?>">
									<?php WPSC_Icons::get( $section['icon'] ); ?>
									<label><?php echo esc_attr( $section['label'] ); ?></label>
								</div>
								<?php
							endforeach;
							?>
						</div>
						<div class="wpsc-setting-body"></div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Add localizations to local JS
		 *
		 * @param array $localizations - localizations.
		 * @return array
		 */
		public static function localizations( $localizations ) {

			if ( ! self::$is_current_page ) {
				return $localizations;
			}

			// Humbargar Titles.
			$localizations['humbargar_titles'] = self::get_humbargar_titles();

			// Current section.
			$localizations['current_section'] = self::$current_section;

			return $localizations;
		}

		/**
		 * Print humbargar menu in footer
		 *
		 * @return void
		 */
		public static function humbargar_menu() {

			if ( ! self::$is_current_page ) {
				return;
			}
			?>
			<div class="wpsc-humbargar-overlay" onclick="wpsc_toggle_humbargar();" style="display:none"></div>
			<div class="wpsc-humbargar-menu" style="display:none">
				<div class="box-inner">
					<div class="wpsc-humbargar-close" onclick="wpsc_toggle_humbargar();">
						<?php WPSC_Icons::get( 'times' ); ?>
					</div>
					<?php
					foreach ( self::$sections as $key => $section ) :

						$active = self::$current_section === $key ? 'active' : '';
						?>
						<div 
							class="wpsc-humbargar-menu-item <?php echo esc_attr( $key ) . ' ' . esc_attr( $active ); ?>"
							onclick="<?php echo esc_attr( $section['callback'] ) . "('" . esc_attr( $key ) . "','" . esc_attr( wp_create_nonce( $key ) ) . "');"; ?>">
							<?php WPSC_Icons::get( $section['icon'] ); ?>
							<label><?php echo esc_attr( $section['label'] ); ?></label>
						</div>
						<?php
					endforeach;
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Humbargar mobile titles to be used in localizations
		 *
		 * @return array
		 */
		private static function get_humbargar_titles() {

			$titles = array();
			foreach ( self::$sections as $section ) {

				ob_start();
				WPSC_Icons::get( $section['icon'] );
				echo '<label>' . esc_attr( $section['label'] ) . '</label>';
				$titles[ $section['slug'] ] = ob_get_clean();
			}
			return $titles;
		}

		/**
		 * Register JS functions to call on document ready
		 *
		 * @return void
		 */
		public static function register_js_ready_function() {

			if ( ! self::$is_current_page ) {
				return;
			}
			echo esc_attr( self::$sections[ self::$current_section ]['callback'] ) . "('" . esc_attr( self::$current_section ) . "','" . esc_attr( wp_create_nonce( self::$current_section ) ) . "');" . PHP_EOL;
		}

		/**
		 * Run report js function switcher for filters
		 *
		 * @return void
		 */
		public static function wpsc_rp_run() {

			if ( ! self::$is_current_page ) {
				return;
			}
			?>
			<script>
				/**
				 * Run report
				 */
				function wpsc_rp_run(report) {

					switch (report) { 
					<?php
					foreach ( self::$sections as $section ) :
						?>
						case '<?php echo esc_attr( $section['slug'] ); ?>': <?php echo esc_attr( $section['run']['function'] ) . '("' . esc_attr( wp_create_nonce( $section['slug'] ) ) . '");'; ?>
							break;
							<?php
					endforeach
					?>
					}
				}
			</script>
			<?php
		}
	}
endif;

WPSC_RP_Admin_Submenu::init();
