<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Email_Templates' ) ) :

	final class WPSC_SF_Email_Templates {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			// email notifications submenu.
			add_filter( 'wpsc_email_notification_page_sections', array( __CLASS__, 'email_templates' ) );

			// CRUD.
			add_action( 'wp_ajax_wpsc_get_sf_email_templates', array( __CLASS__, 'get_sf_email_templates' ) );
			add_action( 'wp_ajax_wpsc_get_add_sf_et', array( __CLASS__, 'get_add_sf_et' ) );
			add_action( 'wp_ajax_wpsc_set_add_sf_et', array( __CLASS__, 'set_add_sf_et' ) );
			add_action( 'wp_ajax_wpsc_get_edit_sf_et', array( __CLASS__, 'get_edit_sf_et' ) );
			add_action( 'wp_ajax_wpsc_set_edit_sf_et', array( __CLASS__, 'set_edit_sf_et' ) );
			add_action( 'wp_ajax_wpsc_delete_sf_et', array( __CLASS__, 'delete_sf_et' ) );
			add_action( 'wp_ajax_wpsc_clone_sf_et', array( __CLASS__, 'clone_sf_et' ) );
		}

		/**
		 * Email templates for survey email notifications
		 *
		 * @param array $sections - section name.
		 * @return array
		 */
		public static function email_templates( $sections ) {

			$sections['sf'] = array(
				'slug'     => 'sf',
				'icon'     => 'star',
				'label'    => esc_attr__( 'Satisfaction Survey', 'wpsc-sf' ),
				'callback' => 'wpsc_get_sf_email_templates',
			);
			return $sections;
		}

		/**
		 * Get setting layout
		 *
		 * @return void
		 */
		public static function get_sf_email_templates() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$email_templates = get_option( 'wpsc-sf-et' );?>

			<div class="wpsc-setting-header">
				<h2><?php esc_attr_e( 'Satisfaction Survey', 'wpsc-sf' ); ?></h2>
			</div>

			<div class="wpsc-setting-section-body">

				<table class="emailTemplates wpsc-setting-tbl">
					<thead>
						<tr>
							<th><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></th>
							<th><?php esc_attr_e( 'Days after closing', 'wpsc-sf' ); ?></th>
							<th><?php echo esc_attr( wpsc__( 'Actions', 'supportcandy' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( $email_templates ) {
							foreach ( $email_templates as $index => $et ) {
								?>
								<tr>
									<td><?php echo esc_attr( $et['title'] ); ?></td>
									<td><?php echo esc_attr( $et['days-after'] ); ?></td>
									<td>
										<a href="#" onclick="wpsc_clone_sf_et(<?php echo esc_attr( $index ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_clone_sf_et' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Clone', 'supportcandy' ) ); ?></a> |
										<a href="#" onclick="wpsc_get_edit_sf_et(<?php echo esc_attr( $index ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_edit_sf_et' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Edit', 'supportcandy' ) ); ?></a> |
										<a href="#" onclick="wpsc_delete_sf_et(<?php echo esc_attr( $index ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_delete_sf_et' ) ); ?>')"><?php echo esc_attr( wpsc__( 'Delete', 'supportcandy' ) ); ?></a>
									</td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
				<script>
					jQuery('table.emailTemplates').DataTable({
						order: [[1, "desc"]],
						pageLength: 20,
						bLengthChange: false,
						columnDefs: [ 
							{ targets: [0, -1], orderable: false },
							{ targets: -1, searchable: false },
							{ targets: '_all', className: 'dt-left' }
						],
						dom: 'Bfrtip',
						buttons: [
							{
								text: '<?php echo esc_attr( wpsc__( 'Add new', 'supportcandy' ) ); ?>',
								className: 'wpsc-button small primary',
								action: function ( e, dt, node, config ) {

									jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
									var data = { action: 'wpsc_get_add_sf_et' };
									jQuery.post(
										supportcandy.ajax_url,
										data,
										function (response) {
											jQuery( '.wpsc-setting-section-body' ).html( response );
										}
									);
								}
							}
						],
						language: supportcandy.translations.datatables
					});
				</script>

			</div>
			<?php

			wp_die();
		}

		/**
		 * Add new email template
		 *
		 * @return void
		 */
		public static function get_add_sf_et() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			?>
			<form action="#" onsubmit="return false;" class="wpsc-frm-sf-et">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input type="text" name="title" value="">
				</div>

				<input type="hidden" name="action" value="wpsc_set_add_sf_et">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_add_sf_et' ) ); ?>">
			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_add_sf_et(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_get_sf_email_templates();">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Set add new email template
		 *
		 * @return void
		 */
		public static function set_add_sf_et() {

			if ( check_ajax_referer( 'wpsc_set_add_sf_et', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			if ( ! $title ) {
				wp_send_json_error( 'Title not given', 400 );
			}

			$email_templates   = get_option( 'wpsc-sf-et' );
			$email_templates[] = array(
				'title'      => $title,
				'days-after' => 1,
				'subject'    => '',
				'body'       => '',
				'editor'     => 'html',
			);
			update_option( 'wpsc-sf-et', $email_templates );

			$nonce = wp_create_nonce( 'wpsc_get_edit_sf_et' );
			wp_send_json(
				array(
					'index' => array_key_last( $email_templates ),
					'nonce' => $nonce,
				)
			);
		}

		/**
		 * Edit email template
		 *
		 * @return void
		 */
		public static function get_edit_sf_et() {

			if ( check_ajax_referer( 'wpsc_get_edit_sf_et', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : null;
			if ( $index === null ) {
				wp_send_json_error( 'Index not given', 400 );
			}

			$email_templates = get_option( 'wpsc-sf-et' );
			if ( ! isset( $email_templates[ $index ] ) ) {
				wp_send_json_error( 'Incorrect index', 400 );
			}

			$et = $email_templates[ $index ];
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-sf-et">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Title', 'supportcandy' ) ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input type="text" name="title" value="<?php echo esc_attr( $et['title'] ); ?>">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Days after closing', 'wpsc-sf' ); ?></label>
						<span class="required-char">*</span>
					</div>
					<input type="number" name="days-after" value="<?php echo intval( $et['days-after'] ); ?>">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Subject', 'supportcandy' ) ); ?></label>
						<span class="required-char">*</span>
					</div>
					<?php
					$et_subject = $et['subject'] ? WPSC_Translations::get( 'wpsc-sf-et-subject-' . $index, stripslashes( $et['subject'] ) ) : stripslashes( $et['subject'] );
					?>
					<input type="text" name="subject" value="<?php echo esc_attr( $et_subject ); ?>">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php echo esc_attr( wpsc__( 'Body', 'supportcandy' ) ); ?></label>
						<span class="required-char">*</span>
					</div>
					<div class="textarea-container">
						<div class = "wpsc_tinymce_editor_btns">
							<div class="inner-container">
								<button class="visual wpsc-switch-editor <?php echo esc_attr( $et['editor'] ) == 'html' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_tinymce(this, 'wpsc-en-body', 'wpsc_en_body');"><?php echo esc_attr( wpsc__( 'Visual', 'supportcandy' ) ); ?></button>
								<button class="text wpsc-switch-editor <?php echo esc_attr( $et['editor'] ) == 'text' ? 'active' : ''; ?>" type="button" onclick="wpsc_get_textarea(this, 'wpsc-en-body')"><?php echo esc_attr( wpsc__( 'Text', 'supportcandy' ) ); ?></button>
							</div>
						</div>
						<?php
						$et_body = $et['body'] ? WPSC_Translations::get( 'wpsc-sf-et-body-' . $index, stripslashes( $et['body'] ) ) : stripslashes( $et['body'] );
						?>
						<textarea id="wpsc-en-body" name="body" class="wpsc_textarea"><?php echo wp_kses_post( $et_body ); ?></textarea>
						<div class="wpsc-it-editor-action-container">
							<div class="actions">
								<div class="wpsc-editor-actions">
									<span class="wpsc-link" onclick="wpsc_get_macros()"><?php echo esc_attr( wpsc__( 'Insert Macro', 'supportcandy' ) ); ?></span>
								</div>
							</div>
						</div>
					</div>
					<script>
						<?php
						if ( $et['editor'] == 'html' ) {
							?>
							jQuery('.wpsc-switch-editor.visual').trigger('click');
							<?php
						}
						?>

						function wpsc_get_tinymce(el, selector, body_id){
							jQuery(el).parent().find('.text').removeClass('active');
							jQuery(el).addClass('active');
							tinymce.remove('#'+selector);
							tinymce.init({ 
								selector:'#'+selector,
								body_id: body_id,
								menubar: false,
								statusbar: false,
								height : '200',
								plugins: [
								'lists link image directionality'
								],
								image_advtab: true,
								toolbar: 'bold italic underline blockquote | alignleft aligncenter alignright | bullist numlist | rtl | link image',
								directionality: '<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>',
								branding: false,
								autoresize_bottom_margin: 20,
								browser_spellcheck : true,
								relative_urls : false,
								remove_script_host : false,
								convert_urls : true,
								setup: function (editor) {
								}
							});
							jQuery('#editor').val('html');
						}

						function wpsc_get_textarea(el, selector){
							jQuery(el).parent().find('.visual').removeClass('active');
							jQuery(el).addClass('active');
							tinymce.remove('#'+selector);
							jQuery('#editor').val('text');
						}
					</script>
				</div>

				<input type="hidden" id="editor" name="editor" value="<?php echo esc_attr( $et['editor'] ); ?>">
				<input type="hidden" name="action" value="wpsc_set_edit_sf_et">
				<input type="hidden" name="index" value="<?php echo esc_attr( $index ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_edit_sf_et' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_edit_sf_et(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="wpsc_get_sf_email_templates();">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Set edit email template
		 *
		 * @return void
		 */
		public static function set_edit_sf_et() {

			if ( check_ajax_referer( 'wpsc_set_edit_sf_et', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : null;
			if ( $index === null ) {
				wp_send_json_error( 'Index not given', 400 );
			}

			$email_templates = get_option( 'wpsc-sf-et' );
			if ( ! isset( $email_templates[ $index ] ) ) {
				wp_send_json_error( 'Incorrect index', 400 );
			}

			$et = $email_templates[ $index ];

			$et['title']      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : $et['title'];
			$et['days-after'] = isset( $_POST['days-after'] ) ? intval( $_POST['days-after'] ) : 0;
			$et['subject']    = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : $et['subject'];
			$et['body']       = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : $et['body'];
			$et['editor']     = isset( $_POST['editor'] ) ? sanitize_text_field( wp_unslash( $_POST['editor'] ) ) : $et['editor'];

			$email_templates[ $index ] = $et;
			update_option( 'wpsc-sf-et', $email_templates );

			// remove string translations.
			WPSC_Translations::remove( 'wpsc-sf-et-subject-' . $index );
			WPSC_Translations::remove( 'wpsc-sf-et-body-' . $index );

			// add string translations.
			WPSC_Translations::add( 'wpsc-sf-et-subject-' . $index, stripslashes( $et['subject'] ) );
			WPSC_Translations::add( 'wpsc-sf-et-body-' . $index, stripslashes( $et['body'] ) );

			wp_die();
		}

		/**
		 * Delete email template
		 *
		 * @return void
		 */
		public static function delete_sf_et() {

			if ( check_ajax_referer( 'wpsc_delete_sf_et', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : null;
			if ( $index === null ) {
				wp_send_json_error( 'Index not given', 400 );
			}

			$email_templates = get_option( 'wpsc-sf-et' );
			if ( ! isset( $email_templates[ $index ] ) ) {
				wp_send_json_error( 'Incorrect index', 400 );
			}

			unset( $email_templates[ $index ] );
			update_option( 'wpsc-sf-et', $email_templates );

			// remove string translations.
			WPSC_Translations::remove( 'wpsc-sf-et-subject-' . $index );
			WPSC_Translations::remove( 'wpsc-sf-et-body-' . $index );

			wp_die();
		}

		/**
		 * Clone email template
		 *
		 * @return void
		 */
		public static function clone_sf_et() {

			if ( check_ajax_referer( 'wpsc_clone_sf_et', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$index = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : null;
			if ( $index === null ) {
				wp_send_json_error( 'Index not given', 400 );
			}

			$email_templates = get_option( 'wpsc-sf-et' );
			if ( ! isset( $email_templates[ $index ] ) ) {
				wp_send_json_error( 'Incorrect index', 400 );
			}

			$et          = $email_templates[ $index ];
			$et['title'] = $et['title'] . ' clone';

			$email_templates[] = $et;

			WPSC_Translations::add( 'wpsc-sf-et-subject-' . array_key_last( $email_templates ), stripslashes( $et['subject'] ) );
			WPSC_Translations::add( 'wpsc-sf-et-body-' . array_key_last( $email_templates ), stripslashes( $et['body'] ) );

			update_option( 'wpsc-sf-et', $email_templates );

			$nonce = wp_create_nonce( 'wpsc_get_edit_sf_et' );
			wp_send_json(
				array(
					'index' => array_key_last( $email_templates ),
					'nonce' => $nonce,
				)
			);
		}
	}
endif;

WPSC_SF_Email_Templates::init();
