/**
 * Get agentgroups list
 */
function wpsc_get_agentgroups(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.agentgroups, .wpsc-humbargar-menu-item.agentgroups' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.agentgroups );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-support-agents&section=agentgroups' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_get_agentgroups' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-body' ).html( response );
			wpsc_reset_responsive_style();
			wpsc_get_agentgroup_list();
		}
	);
}

/**
 * Get agentgroup list
 */
function wpsc_get_agentgroup_list() {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_agentgroup_list' };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 * Set add agentgroup
 */
function wpsc_set_add_agentgroup(el) {

	var form     = jQuery( '.frm-add-agentgroup' )[0];
	var dataform = new FormData( form );

	var name        = dataform.get( 'name' );
	var agents      = dataform.getAll( 'agents[]' );
	var supervisors = dataform.getAll( 'supervisors[]' );
	if ( ! (name && agents.length && supervisors.length)) {
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
			wpsc_get_agentgroup_list();
		}
	);
}

/**
 * Get edit agentgroup
 */
function wpsc_get_edit_agentgroup(id, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_edit_agentgroup', agentgroup_id: id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 * Set edit agentgroup
 */
function wpsc_set_edit_agentgroup(el) {

	var form     = jQuery( '.frm-edit-agentgroup' )[0];
	var dataform = new FormData( form );

	var name        = dataform.get( 'name' );
	var agents      = dataform.getAll( 'agents[]' );
	var supervisors = dataform.getAll( 'supervisors[]' );
	if ( ! (name && agents.length && supervisors.length)) {
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
			wpsc_get_agentgroup_list();
		}
	);
}

/**
 * Delete agentgroup
 */
function wpsc_delete_agentgroup(id, nonce) {

	var flag = confirm( supportcandy.translations.confirm );
	if ( ! flag) {
		return;
	}

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_delete_agentgroup', agentgroup_id: id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_agentgroup_list();
		}
	);
}
