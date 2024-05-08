<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_SF_Rating_Setting' ) ) :

	final class WPSC_SF_Rating_Setting {

		/**
		 * Initialization
		 *
		 * @return void
		 */
		public static function init() {

			// listing.
			add_action( 'wp_ajax_wpsc_sf_rating_setting', array( __CLASS__, 'rating_setting' ) );

			// Add new.
			add_action( 'wp_ajax_wpsc_get_add_new_rating', array( __CLASS__, 'get_add_new_rating' ) );
			add_action( 'wp_ajax_wpsc_set_add_rating', array( __CLASS__, 'set_add_rating' ) );

			// Edit.
			add_action( 'wp_ajax_wpsc_get_edit_rating', array( __CLASS__, 'get_edit_rating' ) );
			add_action( 'wp_ajax_wpsc_set_edit_rating', array( __CLASS__, 'set_edit_rating' ) );

			// Delete.
			add_action( 'wp_ajax_wpsc_get_delete_rating', array( __CLASS__, 'get_delete_rating' ) );
			add_action( 'wp_ajax_wpsc_set_delete_rating', array( __CLASS__, 'set_delete_rating' ) );

			// Sort.
			add_action( 'wp_ajax_wpsc_set_rating_load_order', array( __CLASS__, 'set_rating_load_order' ) );
		}

		/**
		 * Satisfaction survey rating setting
		 *
		 * @return void
		 */
		public static function rating_setting() {

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ratings = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];?>

			<div class="wpsc-setting-cards-container ui-sortable">
				<?php foreach ( $ratings as $rating ) : ?>
				<div class="wpsc-setting-card" data-id="<?php echo esc_attr( $rating->id ); ?>" style="background-color:<?php echo esc_attr( $rating->bg_color ); ?>;color:<?php echo esc_attr( $rating->color ); ?>">
					<span class="wpsc-sort-handle action-btn"><?php WPSC_Icons::get( 'sort' ); ?></span>
					<span class="title"><?php echo esc_attr( $rating->name ); ?></span>
					<div class="actions">
						<span class="action-btn" onclick="wpsc_get_edit_rating(<?php echo esc_attr( $rating->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_edit_rating' ) ); ?>');"><?php WPSC_Icons::get( 'edit' ); ?></span>
						<span class="action-btn" onclick="wpsc_get_delete_rating(<?php echo esc_attr( $rating->id ); ?>, '<?php echo esc_attr( wp_create_nonce( 'wpsc_get_delete_rating' ) ); ?>');"><?php WPSC_Icons::get( 'trash-alt' ); ?></span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<div class="setting-footer-actions">
				<button class="wpsc-button small primary" onclick="wpsc_get_add_new_rating('<?php echo esc_attr( wp_create_nonce( 'wpsc_get_add_new_rating' ) ); ?>');"><?php echo esc_attr( wpsc__( 'Add new', 'supportcandy' ) ); ?></button>
				<button class="wpsc-button normal secondary wpsc-save-sort-order"><?php echo esc_attr( wpsc__( 'Save Order', 'supportcandy' ) ); ?></button>
			</div>
			<script>
				var items = jQuery( ".wpsc-setting-cards-container" ).sortable({ handle: '.wpsc-sort-handle' });
				jQuery(".wpsc-save-sort-order").click(function(){
					var ids = items.sortable( "toArray", {attribute: 'data-id'} );
					wpsc_set_rating_load_order(ids, '<?php echo esc_attr( wp_create_nonce( 'wpsc_set_rating_load_order' ) ); ?>');
				});
			</script>
			<?php
			wp_die();
		}

		/**
		 * Get add new rating
		 *
		 * @return void
		 */
		public static function get_add_new_rating() {

			if ( check_ajax_referer( 'wpsc_get_add_new_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 401 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-add-rating">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input name="name" type="text" autocomplete="off">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Color', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input class="wpsc-color-picker" name="color" value="#ffffff" />
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Background color', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input class="wpsc-color-picker" name="bg-color" value="#1E90FF" />
				</div>

				<script>jQuery('.wpsc-color-picker').wpColorPicker();</script>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Confirmation text', 'wpsc-sf' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input name="confirmation_text" type="text" autocomplete="off">
				</div>

				<?php do_action( 'wpsc_get_add_rating_body' ); ?>

				<input type="hidden" name="action" value="wpsc_set_add_rating">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_add_rating' ) ); ?>">

			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_add_rating(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="jQuery('.wpsc-setting-nav.active').trigger('click');">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			wp_die();
		}

		/**
		 * Save new rating
		 *
		 * @return void
		 */
		public static function set_add_rating() {

			if ( check_ajax_referer( 'wpsc_set_add_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$data = array();

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( ! $name ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$data['name'] = $name;

			$color = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '';
			if ( ! $color ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$data['color'] = $color;

			$bgcolor = isset( $_POST['bg-color'] ) ? sanitize_text_field( wp_unslash( $_POST['bg-color'] ) ) : '';
			if ( ! $bgcolor ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$data['bg_color'] = $bgcolor;

			$message = isset( $_POST['confirmation_text'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation_text'] ) ) : '';
			if ( ! $message ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$data['confirmation_text'] = $message;

			$rating = WPSC_SF_Rating::insert( $data );

			do_action( 'wpsc_set_add_new_rating', $rating );

			wp_die();
		}

		/**
		 * Get edit rating
		 *
		 * @return void
		 */
		public static function get_edit_rating() {

			if ( check_ajax_referer( 'wpsc_get_edit_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$rating = new WPSC_SF_Rating( $id );
			if ( ! $rating ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}
			?>

			<form action="#" onsubmit="return false;" class="wpsc-frm-edit-rating">

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Name', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input name="name" type="text" value="<?php echo esc_attr( $rating->name ); ?>" autocomplete="off">
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Color', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input class="wpsc-color-picker" name="color" value="<?php echo esc_attr( $rating->color ); ?>" />
				</div>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php echo esc_attr( wpsc__( 'Background color', 'supportcandy' ) ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input class="wpsc-color-picker" name="bg-color" value="<?php echo esc_attr( $rating->bg_color ); ?>" />
				</div>

				<script>jQuery('.wpsc-color-picker').wpColorPicker();</script>

				<div class="wpsc-input-group">
					<div class="label-container">
						<label for="">
							<?php esc_attr_e( 'Confirmation text', 'wpsc-sf' ); ?>
							<span class="required-char">*</span>
						</label>
					</div>
					<input name="confirmation_text" type="text" value="<?php echo esc_attr( $rating->confirmation_text ); ?>" autocomplete="off">
				</div>

				<?php do_action( 'wpsc_get_edit_rating_body' ); ?>

				<input type="hidden" name="action" value="wpsc_set_edit_rating">
				<input type="hidden" name= "id" value="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_edit_rating' ) ); ?>">
			</form>

			<div class="setting-footer-actions">
				<button 
					class="wpsc-button normal primary margin-right"
					onclick="wpsc_set_edit_rating(this);">
					<?php echo esc_attr( wpsc__( 'Submit', 'supportcandy' ) ); ?></button>
				<button 
					class="wpsc-button normal secondary"
					onclick="jQuery('.wpsc-setting-nav.active').trigger('click');">
					<?php echo esc_attr( wpsc__( 'Cancel', 'supportcandy' ) ); ?></button>
			</div>
			<?php

			do_action( 'wpsc_get_edit_rating' );

			wp_die();
		}

		/**
		 * Update rating
		 *
		 * @return void
		 */
		public static function set_edit_rating() {

			if ( check_ajax_referer( 'wpsc_set_edit_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$rating = new WPSC_SF_Rating( $id );
			if ( ! $rating ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( ! $name ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rating->name = $name;

			$color = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '';
			if ( ! $color ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rating->color = $color;

			$bgcolor = isset( $_POST['bg-color'] ) ? sanitize_text_field( wp_unslash( $_POST['bg-color'] ) ) : '';
			if ( ! $bgcolor ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rating->bg_color = $bgcolor;

			$message = isset( $_POST['confirmation_text'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation_text'] ) ) : '';
			if ( ! $message ) {
				wp_send_json_error( 'Bad Request', 400 );
			}
			$rating->confirmation_text = $message;

			$rating->save();
			wp_die();
		}

		/**
		 * Delete rating
		 *
		 * @return void
		 */
		public static function get_delete_rating() {

			if ( check_ajax_referer( 'wpsc_get_delete_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$rating = new WPSC_SF_Rating( $id );
			if ( ! $rating->id ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$title = esc_attr__( 'Delete rating', 'supportcandy' );

			$ratings = WPSC_SF_Rating::find( array( 'items_per_page' => 0 ) )['results'];
			ob_start();
			?>
			<form action="#" onsubmit="return false;" class="wpsc-frm-delete-rating">
				<div class="wpsc-input-group">
					<div class="label-container">
						<label for=""><?php esc_attr_e( 'Replace with', 'supportcandy' ); ?></label>
					</div>
					<select name="replace_id">
						<?php
						foreach ( $ratings as $sf_rat ) {
							if ( $sf_rat->id == $rating->id ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $sf_rat->id ); ?>"><?php echo esc_attr( $sf_rat->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<input type="hidden" name="id" value="<?php echo esc_attr( $rating->id ); ?>">
				<input type="hidden" name="action" value="wpsc_set_delete_rating">
				<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpsc_set_delete_rating' ) ); ?>">
			</form>
			<?php
			$body = ob_get_clean();

			ob_start();
			?>
			<button class="wpsc-button small primary" onclick="wpsc_set_delete_rating(this);">
				<?php esc_attr_e( 'Submit', 'supportcandy' ); ?>
			</button>
			<button class="wpsc-button small secondary" onclick="wpsc_close_modal();">
				<?php esc_attr_e( 'Cancel', 'supportcandy' ); ?>
			</button>
			<?php
			$footer = ob_get_clean();

			$response = array(
				'title'  => $title,
				'body'   => $body,
				'footer' => $footer,
			);

			wp_send_json( $response );
		}

		/**
		 * Delete rating
		 */
		public static function set_delete_rating() {

			global $wpdb;

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			if ( check_ajax_referer( 'wpsc_set_delete_rating', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorised request!', 401 );
			}

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$rating = new WPSC_SF_Rating( $id );
			if ( ! $rating->id ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$replace_id = isset( $_POST['replace_id'] ) ? intval( $_POST['replace_id'] ) : 0;
			if ( ! $replace_id ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			$replace = new WPSC_SF_Rating( $replace_id );
			if ( ! $replace->id ) {
				wp_send_json_error( __( 'Bad request!', 'supportcandy' ), 400 );
			}

			// replace in ticket table.
			$success = $wpdb->update(
				$wpdb->prefix . 'psmsc_tickets',
				array( 'rating' => $replace->id ),
				array( 'rating' => $rating->id )
			);

			// replace in logs.
			$results = $wpdb->get_results(
				"SELECT * FROM wp_psmsc_threads WHERE 
					(type = 'log') AND 
					JSON_VALID(body) AND 
					JSON_EXTRACT(body, '$.slug') = 'rating' AND
					(
						JSON_UNQUOTE(JSON_EXTRACT(body, '$.new')) = '" . $rating->id . "' OR
						JSON_UNQUOTE(JSON_EXTRACT(body, '$.prev')) = '" . $rating->id . "'
					)"
			);
			foreach ( $results as $log ) {
				$body       = json_decode( $log->body );
				$body->prev = $body->prev == $rating->id ? $replace->id : $body->prev;
				$body->new  = $body->new == $rating->id ? $replace->id : $body->new;
				$body       = wp_json_encode( $body );
				$wpdb->update(
					$wpdb->prefix . 'psmsc_threads',
					array( 'body' => $body ),
					array( 'id' => $log->id )
				);
			}

			$rating->destroy( $rating );

			wp_die();
		}

		/**
		 * Sort ratings
		 *
		 * @return void
		 */
		public static function set_rating_load_order() {

			if ( check_ajax_referer( 'wpsc_set_rating_load_order', '_ajax_nonce', false ) != 1 ) {
				wp_send_json_error( 'Unauthorized request!', 400 );
			}

			if ( ! WPSC_Functions::is_site_admin() ) {
				wp_send_json_error( __( 'Unauthorized access!', 'supportcandy' ), 401 );
			}

			$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'intval', wp_unslash( $_POST['ids'] ) ) ) : array();
			if ( ! $ids ) {
				wp_send_json_error( 'Incorrect request!', 400 );
			}

			$count = 1;
			foreach ( $ids as $id ) {

				$rating = new WPSC_SF_Rating( $id );
				if ( ! $rating->id ) {
					wp_send_json_error( 'Incorrect request!', 400 );
				}

				$rating->load_order = $count++;
				$rating->save();
			}
			wp_die();
		}
	}
endif;

WPSC_SF_Rating_Setting::init();
