/**
 * Satisfation survey general and rating setting
 */
function wpsc_satisfaction_survey_setting(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.satisfaction-survey, .wpsc-humbargar-menu-item.satisfaction-survey' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.satisfaction_survey );

	if (supportcandy.current_section !== 'satisfaction-survey') {
		supportcandy.current_section = 'satisfaction-survey'
		supportcandy.current_tab     = 'general';
	}

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();
	var data = {
		action: 'wpsc_satisfaction_survey_setting',
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
 * Load general setting
 */
function wpsc_sf_general_setting() {

	supportcandy.current_tab = 'general';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	wpsc_scroll_top();

	var data = { action: 'wpsc_sf_general_setting' };
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
 * Set SF general setting
 */
function wpsc_sf_set_general_setting(el) {
	var form     = jQuery( '.wpsc-sf-general' )[0];
	var dataform = new FormData( form );
	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );

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
			wpsc_sf_general_setting();
		}
	);
}

/**
 * Reset page settings
 */
function wpsc_sf_reset_general_settings(el, nonce) {

	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = { action: 'wpsc_sf_reset_general_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_sf_general_setting();
		}
	);
}

/**
 * Load rating setting
 */
function wpsc_sf_rating_setting() {

	supportcandy.current_tab = 'rating';
	jQuery( '.wpsc-setting-tab-container button' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-tab-container button.' + supportcandy.current_tab ).addClass( 'active' );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section + '&tab=' + supportcandy.current_tab );
	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	wpsc_scroll_top();

	var data = { action: 'wpsc_sf_rating_setting' };
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
 *  Get add new rating
 */
function wpsc_get_add_new_rating(nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_add_new_rating', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 *  Set add new rating
 */
function wpsc_set_add_rating(el) {

	var form     = jQuery( '.wpsc-frm-add-rating' );
	var dataform = new FormData( form[0] );

	var name     = dataform.get( 'name' );
	var color    = dataform.get( 'color' );
	var bg_color = dataform.get( 'bg-color' );
	var message  = dataform.get( 'confirmation_text' );

	if ( ! (name && color && bg_color && message)) {
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
			wpsc_sf_rating_setting();
		}
	);
}

/**
 * Get edit rating
 */
function wpsc_get_edit_rating(id, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_edit_rating', id, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( '.wpsc-setting-section-body' ).html( response );
		}
	);
}

/**
 * Update an rating
 */
function wpsc_set_edit_rating(el) {

	var form     = jQuery( '.wpsc-frm-edit-rating' );
	var dataform = new FormData( form[0] );

	var name     = dataform.get( 'name' );
	var color    = dataform.get( 'color' );
	var bg_color = dataform.get( 'bg-color' );
	var message  = dataform.get( 'confirmation_text' );

	if ( ! (name && color && bg_color && message)) {
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
			wpsc_sf_rating_setting();
		}
	);
}

/**
 * Delete rating modal
 */
function wpsc_get_delete_rating(id, nonce) {

	if ( ! confirm( supportcandy.translations.confirm )) {
		return;
	}

	wpsc_show_modal();
	var data = { action: 'wpsc_get_delete_rating', id, _ajax_nonce: nonce };
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
 * Delete rating
 */
function wpsc_set_delete_rating(el) {

	jQuery( '.wpsc-modal-footer button' ).attr( 'disabled', true );
	jQuery( el ).text( supportcandy.translations.please_wait );

	var form     = jQuery( '.wpsc-frm-delete-rating' )[0];
	var dataform = new FormData( form );
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
			wpsc_sf_rating_setting();
		}
	);
}

/**
 * Load rating order
 */
function wpsc_set_rating_load_order(ids, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_set_rating_load_order', ids, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (res) {
			wpsc_sf_rating_setting();
		}
	);
}

/**
 * SF email templates get
 */
function wpsc_get_sf_email_templates(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.sf, .wpsc-humbargar-menu-item.sf' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.sf );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-email-notifications&section=sf' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = { action: 'wpsc_get_sf_email_templates' };
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
function wpsc_set_add_sf_et(el) {

	var form     = jQuery( '.wpsc-frm-sf-et' )[0];
	var dataform = new FormData( form );

	if (dataform.get( 'title' ).trim() == '') {
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
			wpsc_get_edit_sf_et( res.index, res.nonce );
		}
	);
}

/**
 * Edit email template
 */
function wpsc_get_edit_sf_et(index, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_get_edit_sf_et', index, _ajax_nonce: nonce };
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
function wpsc_set_edit_sf_et(el) {

	var form     = jQuery( '.wpsc-frm-sf-et' )[0];
	var dataform = new FormData( form );

	var is_tinymce = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();
	var body       = is_tinymce ? tinyMCE.get( 'wpsc-en-body' ).getContent().trim() : dataform.get( 'body' ).trim();
	dataform.append( 'body', body );

	var title		= dataform.get( 'title' ).trim();
	var days_after	= dataform.get( 'days-after' ).trim();
	var subject		= dataform.get( 'subject' ).trim();

	if ( ! ( title && days_after && subject && body ) ) {
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
			wpsc_get_sf_email_templates();
		}
	);
}

/**
 * Delete email template
 */
function wpsc_delete_sf_et(index, nonce) {

	var flag = confirm( supportcandy.translations.confirm );
	if ( ! flag) {
		return;
	}

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_delete_sf_et', index, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_sf_email_templates();
		}
	);
}

/**
 * Clone email template
 */
function wpsc_clone_sf_et(index, nonce) {

	jQuery( '.wpsc-setting-section-body' ).html( supportcandy.loader_html );
	var data = { action: 'wpsc_clone_sf_et', index, _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (res) {
			wpsc_get_edit_sf_et( res.index, res.nonce );
		}
	);
}

/**
 * Get edit ticket widget
 */
function wpsc_get_tw_rating() {

	wpsc_show_modal();
	var data = { action: 'wpsc_get_tw_rating' };
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
 * Set edit ticket widget
 */
function wpsc_set_tw_rating(el) {
	
	var form     = jQuery( '.wpsc-frm-edit-rating' )[0];
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

/**
 * Get satisfaction survey report
 */
function wpsc_rp_get_sf_rating(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.rating );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_rating_report', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run rating reports
 */
function wpsc_rp_run_rating_report(nonce) {

	jQuery( '#wpscTicketStatisticsCanvas' ).remove();
	jQuery( '.wpscPrograssLoaderContainer' ).show();
	jQuery( '.wpscPrograssLoaderContainer' ).html( supportcandy.loader_html );

	// get filters (if any).
	let form     = jQuery( '.wpsc-report-filters' )[0];
	var dataform = new FormData( form );
	let filter   = dataform.get( 'filter' );
	var filters  = {};
	if (filter == 'custom') {
		filters = supportcandy.RpFilters;
	}
	filters = JSON.stringify( filters );

	let from = new Date( jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val() );
	let to   = new Date( jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val() );

	fromDate = from.toISOString().split('T')[0] + ' 00:00:00';
	toDate   = to.toISOString().split('T')[0] + ' 23:59:59';

	dataform = new FormData( form );
	dataform.append( 'action', 'wpsc_rp_run_rating_report' );
	dataform.append( 'filters', filters );
	dataform.append( 'from_date', fromDate );
	dataform.append( 'to_date', toDate );
	dataform.append( 'cf_slug', supportcandy.currentReportSlug );
	dataform.append( '_ajax_nonce', nonce );
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
			labels = [];
			data   = [];
			for (var key in res) {
				labels.push( key );
				data.push( res[key] );
			}

			height = (Math.ceil( labels.length / 10 ) * 100) + 500;

			jQuery( '.wpscPrograssLoaderContainer' ).hide();
			jQuery( '.wpsc-setting-section-body' ).children().last().before( '<canvas id="wpscTicketStatisticsCanvas" style="height:' + height + 'px !important;" class="wpscRpCanvas"></canvas>' );
			var data   = {
				labels: labels,
				datasets: [
					{
						label: 'Contagem',
						backgroundColor: '#e74c3c',
						borderColor: '#e74c3c',
						data: data
				}
				]
			};
			var config = {
				type: 'bar',
				data,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					indexAxis: 'y',
					scales: {
						x: {
							beginAtZero: true,
							title: {
								display: true,
								'text': 'Número de Ocorrências'
							}
						}
					}
				}
			};
			new Chart(
				document.getElementById( 'wpscTicketStatisticsCanvas' ),
				config
			);
		}
	);
}
