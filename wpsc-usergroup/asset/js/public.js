/**
 * Change customer usergroups while creating ticket create-as
 */
 function wpsc_change_customer_usergroups(nonce) {

	var curCustomer = jQuery( '.wpsc-create-ticket input.email' ).val().trim();
	let isFieldExists = jQuery('.wpsc-tff.usergroups').length ? true : false;
	if ( isFieldExists ) {
		jQuery('.wpsc-tff.usergroups').removeClass('wpsc-hidden').removeClass('wpsc-visible').addClass('wpsc-hidden');
		let el = jQuery('.wpsc-tff.usergroups').find('select').first();
		el.empty();
	}

	var data = {
		action: 'wpsc_get_create_as_usergroups',
		email: curCustomer,
		_ajax_nonce: nonce
	};

	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (res) {
			jQuery( '#wpsc-usergroups' ).val( res.auto_fill );
			let selectedState = res.auto_fill ? true : false;
			if ( isFieldExists && res.has_usergroups && ! res.auto_assign && res.allow_customer_modify ) {
				let el = jQuery('.wpsc-tff.usergroups').find('select').first();
				el.empty();
				res.options.forEach( usergroup => {
					el.append( new Option( usergroup.value, usergroup.index, false, selectedState ) );
				});
				jQuery('.wpsc-tff.usergroups').removeClass('wpsc-hidden').addClass('wpsc-visible');
			}
			wpsc_check_tff_visibility();
		}
	);
}

/**
 * Change (modify) usergroups
 * @param {*} el 
 */
function wpsc_change_usergroups(el) {
	let value = jQuery(el).val();
	jQuery( '#wpsc-usergroups' ).val( value.join('|') );
	wpsc_check_tff_visibility();
}

/**
 * Get usergroup view member
 */
function wpsc_ug_members_info( ug_id, ticket_id, nonce ) {

	wpsc_show_modal();
	var data = { action: 'wpsc_ug_members_info', ug_id, ticket_id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Get usergroup view details
 */
function wpsc_ug_view_details( ug_id, ticket_id, nonce ) {

	wpsc_show_modal();
	var data = { action: 'wpsc_ug_view_details', ug_id, ticket_id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Get usergroup view details
 */
function wpsc_ug_all_tickets( ug_id, ticket_id, nonce ) {

	wpsc_show_modal();
	var data = { action: 'wpsc_ug_all_tickets', ug_id, ticket_id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Get edit ticket usergroups
 * @param {*} ticket_id 
 * @param {*} nonce 
 */
function wpsc_it_get_edit_ug( ticket_id, nonce ) {

	wpsc_show_modal();
	var data = { action: 'wpsc_it_get_edit_ug', ticket_id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {

			jQuery( '.wpsc-modal-header' ).text( response.title );
			jQuery( '.wpsc-modal-body' ).html( response.body );
			jQuery( '.wpsc-modal-footer' ).html( response.footer );
			wpsc_show_modal_inner_container();
		}
	);
}

/**
 * Set edit ticket usergroups
 * @param {*} el 
 * @param {*} ticket_id 
 * @param {*} uniqueId 
 */
function wpsc_it_set_edit_ug( el, ticket_id, uniqueId ) {

	if ( wpsc_is_description_text() ) {
		if ( confirm( supportcandy.translations.warning_message ) ) {
			wpsc_clear_saved_draft_reply( ticket_id );
		}else{
			return;
		}
	}

	var form     = jQuery( 'form.change-usergroups.' + uniqueId )[0];
	var dataform = new FormData( form );
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
			wpsc_get_individual_ticket( ticket_id );
		}
	);
}