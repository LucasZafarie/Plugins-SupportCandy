/**
 * Load automatic close ticket settings
 */
function wpsc_get_atc_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.atc, .wpsc-humbargar-menu-item.atc' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.atc );

	if (supportcandy.current_section !== 'atc') {
		supportcandy.current_section = 'atc';
	}

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_get_atc_settings' };
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
 * Set automatic close ticket settings
 */
function wpsc_set_atc_settings(el) {

	var form     = jQuery( '.wpsc-frm-atc-settings' )[0];
	var dataform = new FormData( form );

	var statuses = dataform.getAll( 'statuses-enabled[]' );
	if ( ! statuses.length ) {
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
			wpsc_get_atc_settings();
		}
	);
}

/**
 * Reset automatic close ticket
 */
function wpsc_reset_atc_settings(el, nonce) {

	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_reset_atc_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_atc_settings();
		}
	);
}

/**
 * Automatic close tickets email templates get
 */
function wpsc_get_atc_email_templates(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.atc, .wpsc-humbargar-menu-item.atc' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.atc );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-email-notifications&section=atc' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_get_atc_email_templates' };
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
 * Set add new email template
 */
function wpsc_set_add_atc_et(el) {

	var form     = jQuery( '.wpsc-frm-atc-et' )[0];
	var dataform = new FormData( form );

	if ( dataform.get( 'title' ).trim() == '' ) {
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
			wpsc_get_edit_atc_et( res.index, res.nonce );
		}
	);
}

/**
 * Edit email template
 */
function wpsc_get_edit_atc_et(index, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_edit_atc_et', index, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 * Set edit email template
 */
function wpsc_set_edit_atc_et(el) {

	var form     = jQuery( '.wpsc-frm-atc-et' )[0];
	var dataform = new FormData( form );

	var is_tinymce = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();
	var body       = is_tinymce ? tinyMCE.get( 'wpsc-en-body' ).getContent().trim() : dataform.get( 'body' ).trim();
	var title = jQuery( 'input[name=title]' ).val();
	var subject = jQuery( 'input[name=subject]' ).val();
	if ( ! title || ! subject || ! body ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

	dataform.append( 'body', body );

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
			wpsc_get_atc_email_templates();
		}
	);
}

/**
 * Delete email template
 */
function wpsc_delete_atc_et(index, nonce) {

	var flag = confirm( supportcandy.translations.confirm );
	if ( ! flag) {
		return;
	}

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_delete_atc_et', index, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_atc_email_templates();
		}
	);
}

/**
 * Clone email template
 */
function wpsc_clone_atc_et(index, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_clone_atc_et', index, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (res) {
			wpsc_get_edit_atc_et( res.index, res.nonce );
		}
	);
}
