/**
 * Change rating on shortcode page
 */
function wpsc_change_sf_rating(el) {
	var id = jQuery( el ).parent().data( 'id' );
	jQuery( '.wpsc-rating-item' ).removeClass( 'active' );
	jQuery( el ).parent().addClass( "active" );
	jQuery( "#rating" ).val( id );
}

/**
 * Set additional feedback for ticket shortcode
 */
function wpsc_set_sf_add_feedback(el) {
	var form     = jQuery( '.wpsc-frm-add-feedback' )[0];
	var dataform = new FormData( form );
	if ( !jQuery('#wpsc-sf-ratings .active').length ) {
		alert( supportcandy.translations.req_rating );
		return;
	}
	jQuery( el ).text( supportcandy.translations.please_wait );
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			jQuery( '.wpsc-frm-add-feedback' ).html( res.msg );
		}
	);
}

/**
 * Get rating modal UI
 */
function wpsc_it_get_edit_rating(ticket_id, nonce, isTrigger = false) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_it_get_edit_rating',
		ticket_id,
		isTrigger: isTrigger ? 1 : 0,
		_ajax_nonce: nonce
	};

	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			// Set to modal.
			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			// Display modal.

			wpsc_show_modal_inner_container();
		}
	);
}


/**
 * Update rating
 *
 * @param {*} el
 */
function wpsc_it_set_edit_rating(el, ticket_id, isTrigger) {
	var form     = jQuery( '.wpsc-frm-add-feedback' )[0];
	var dataform = new FormData( form );
	if ( !jQuery('#wpsc-sf-ratings .active').length ) {
		alert( supportcandy.translations.req_rating );
		return;
	}
	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );
	jQuery.ajax(
		{
			url: supportcandy.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		}
	).done(
		function (res) {
			wpsc_close_modal();
			if ( ! isTrigger) {
				wpsc_get_individual_ticket( ticket_id );
			}
			wpsc_run_ajax_background_process();
		}
	);
}

/**
 * Trigger edit rating widget when customer close ticket
 */
function wpsc_trigger_customer_survey(ticket_id, nonce) {

	const data = { action: 'wpsc_trigger_customer_survey', ticket_id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			if (response.trigger) {
				wpsc_it_get_edit_rating( response.ticket_id, nonce, true );
			}
		}
	);
}

/**
 * Export feedbacks
 */
function wpsc_get_export_feedbacks(nonce) {

	var data_table = jQuery('.wpsc-sf-feedback').DataTable();
	if ( data_table.rows().count() == 0) {
		return;
	}

	date_range = jQuery('#wpsc-sf-date-picker').val();
	rating = jQuery('#wpsc-input-sort-feedback').val();

	var data = { 
		action: 'wpsc_get_export_feedbacks',
		date_range,
		rating,
		 _ajax_nonce: nonce
		};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (res) {
			var obj = jQuery.parseJSON( res );
			var wpsc_export = document.createElement('a');
			wpsc_export.href = obj.url_to_export;
			wpsc_export.setAttribute('target', '_blank');
			wpsc_export.click();
			setTimeout(function() {
				wpsc_export.remove();
			}, 1000);
		}
	);
}