/**
 *  Get usergroup fields
 */
function wpsc_get_usergroup_fields(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.usergroup-fields, .wpsc-humbargar-menu-item.usergroup-fields' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.usergroup_fields );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-ticket-form&section=usergroup-fields' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_get_usergroup_fields' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Load usergroup settings
 */
function wpsc_get_ug_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.usergroups, .wpsc-humbargar-menu-item.usergroups' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.usergroups );

	if (supportcandy.current_section !== 'usergroups') {
		supportcandy.current_section = 'usergroups';
		supportcandy.current_tab     = 'general';
	}

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = {
		action: 'wpsc_get_ug_settings',
		tab: supportcandy.current_tab
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-body' ).html( response );
			wpsc_reset_responsive_style();
			jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).trigger( "click" );
		}
	);
}

/**
 * Load general tab ui
 */
function wpsc_ug_get_general_settings() {

	supportcandy.current_tab = 'general';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_ug_get_general_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Set general settings
 */
function wpsc_ug_set_general_settings(el) {

	var form     = jQuery( '.wpsc-ug-general-settings' )[0];
	var dataform = new FormData( form );
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
			wpsc_ug_get_general_settings();
		}
	);
}

/**
 * Set general settings
 */
function wpsc_ug_reset_general_settings(el, nonce) {

	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_ug_reset_general_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_ug_get_general_settings();
		}
	);
}

/**
 * Load usergroups
 */
function wpsc_ug_get_usergroups_settings() {

	supportcandy.current_tab = 'crud-settings';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_ug_get_usergroups_settings' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
			wpsc_reset_responsive_style();
		}
	);
}

/**
 * Get edit usergroup
 */
function wpsc_get_edit_usergroup(id, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_edit_usergroup', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 * Get clone usergroup
 */
function wpsc_get_clone_usergroup(id, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_clone_usergroup', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_ug_get_usergroups_settings();
		}
	);
}

/**
 * Delete usergroup
 */
function wpsc_delete_usergroup(id, nonce) {

	if ( ! confirm( supportcandy.translations.confirm )) {
		return;
	}

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_delete_usergroup', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html(response);
		}
	);
}

/**
 * Clear usergroup reference from all tickets before deleting it
 */
function wpsc_delete_ug_ticket_utility() {
	var data = {
		action: 'wpsc_delete_ug_ticket_utility',
		ug_id: supportcandy.temp.ug_id,
		_ajax_nonce: supportcandy.temp.wpsc_delete_ug_ticket_utility_nonce
	};
	return new Promise( resolve => {
		jQuery.post(supportcandy.ajax_url, data, function (response) {
			wpsc_delete_ug_ticket_utility_nonce = response.nonce;
			resolve(true);
		}).fail(function(){
			resolve(false);
		});
	});
}

/**
 * Set add new usergroup
 */
function wpsc_set_add_new_usergroup(el) {

	var form     = jQuery( '.frm-add-new-usergroup' );
	var dataform = new FormData( form[0] );

	var name        = dataform.get( 'label' );
	var members     = dataform.getAll( 'wpsc-ug-members[]' );
	if ( ! (name && members.length)) {
		alert( supportcandy.translations.req_fields_missing );
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
			wpsc_ug_get_usergroups_settings();
		}
	);
}

/**
 * Set edit usergroup
 */
function wpsc_set_edit_usergroup(el) {

	var form     = jQuery( '.frm-edit-usergroup' );
	var dataform = new FormData( form[0] );

	var name        = dataform.get( 'label' );
	var members     = dataform.getAll( 'wpsc-ug-members[]' );
	if ( ! (name && members.length)) {
		alert( supportcandy.translations.req_fields_missing );
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
			wpsc_ug_get_usergroups_settings();
		}
	);
}

/**
 * Get edit usergroup widget
 */
function wpsc_get_tw_usergroups() {

	wpsc_show_modal();
	var data = { action: 'wpsc_get_tw_usergroups' };
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
 * Set edit usergroup widget
 */
function wpsc_set_tw_usergroups(el) {

	var form     = jQuery( '.wpsc-frm-edit-usergroups' )[0];
	var dataform = new FormData( form );
	
	if (dataform.get( 'label' ).trim() == '') {
		alert( supportcandy.translations.req_fields_missing );
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
			wpsc_get_ticket_widget();
		}
	);
}
