/**
 * Get report settigns
 */
function wpsc_get_report_settings(is_humbargar = false) {

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.reports, .wpsc-humbargar-menu-item.reports' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.reports );

	if (supportcandy.current_section !== 'reports') {
		supportcandy.current_section = 'reports';
	}

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-settings&section=' + supportcandy.current_section );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	wpsc_scroll_top();

	var data = {
		action: 'wpsc_get_report_settings'
	};
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
 * Set report settings
 */
function wpsc_set_report_settings(el) {

	var form     = jQuery( '.wpsc-frm-report-settings' )[0];
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
			wpsc_get_report_settings();
		}
	);
}

/**
 * Reset report settings
 */
function wpsc_reset_report_settings(el, nonce) {

	jQuery( el ).text( supportcandy.translations.please_wait );
	const data = { action: 'wpsc_reset_report_settings', _ajax_nonce: nonce };
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_get_report_settings();
		}
	);
}

/**
 * Change filter duration
 */
function wpsc_change_filter_duration(el) {

	let duration = jQuery( el ).val();
	if (duration == 'custom') {
		jQuery( '.setting-filter-item.from-date' ).find( 'input' ).flatpickr( { defaultDate: new Date() } );
		jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( '' );
		jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( '' );
		jQuery( '.setting-filter-item.from-date, .setting-filter-item.to-date' ).show();
	} else {
		jQuery( '.setting-filter-item.from-date, .setting-filter-item.to-date' ).hide();
		wpsc_set_filter_duration_dates( duration );
	}
}

/**
 * Set filter from and to dates based on duration slug
 */
function wpsc_set_filter_duration_dates(duration) {

	let dateStr = '';
	let date    = new Date();
	let quarter;
	switch (duration) {

		case 'today':
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date, .setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'yesterday':
			date.setDate( date.getDate() - 1 );
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date, .setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'this-week':
			if (date.getDay() == 0) {
				date.setDate( date.getDate() - 6 );
			} else {
				date.setDate( date.getDate() - date.getDay() + 1 );
			}
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			date.setDate( date.getDate() + 6 );
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'last-week':
			if (date.getDay() == 0) {
				date.setDate( date.getDate() - 13 );
			} else {
				date.setDate( date.getDate() - date.getDay() - 6 );
			}
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			date.setDate( date.getDate() + 6 );
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'last-30-days':
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			date.setDate( date.getDate() - 29 );
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			break;

		case 'this-month':
			dateStr = new Date( date.getFullYear(), date.getMonth(), 1 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			dateStr = new Date( date.getFullYear(), date.getMonth() + 1, 0 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'last-month':
			date.setMonth( date.getMonth(), 0 );
			dateStr = date.toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			dateStr = new Date( date.getFullYear(), date.getMonth(), 1 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			break;

		case 'this-quarter':
			quarter = Math.floor( (date.getMonth() + 3) / 3 );
			dateStr = new Date( date.getFullYear(), (quarter - 1) * 3, 1 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			if (quarter == 4) {
				dateStr = new Date( date.getFullYear(), 11, 31 ).toISOString().split('T')[0];
			} else {
				dateStr = new Date( date.getFullYear(), quarter * 3, 0 ).toISOString().split('T')[0];
			}
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'last-quarter':
			quarter = Math.floor( (date.getMonth() + 3) / 3 );
			if (quarter == 1) {
				dateStr = new Date( date.getFullYear() - 1, 9, 1 ).toISOString().split('T')[0];
				jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
				dateStr = new Date( date.getFullYear() - 1, 11, 31 ).toISOString().split('T')[0];
				jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			} else {
				dateStr = new Date( date.getFullYear(), (quarter - 2) * 3, 1 ).toISOString().split('T')[0];
				jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
				dateStr = new Date( date.getFullYear(), (quarter - 1) * 3, 0 ).toISOString().split('T')[0];
				jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			}
			break;

		case 'this-year':
			dateStr = new Date( date.getFullYear(), 0, 1 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			dateStr = new Date( date.getFullYear(), 11, 31 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;

		case 'last-year':
			dateStr = new Date( date.getFullYear() - 1, 0, 1 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val( dateStr );
			dateStr = new Date( date.getFullYear() - 1, 11, 31 ).toISOString().split('T')[0];
			jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val( dateStr );
			break;
	}
}

/**
 * Cancel custom filter
 */
function wpsc_rp_close_custom_filter_modal() {

	wpsc_close_modal();
	jQuery( 'form.wpsc-report-filters' ).find( 'select[name=filter]' ).val( '' ).trigger( 'change' );
}

/**
 * Change ticket stat filter
 */
function wpsc_rp_change_filter(el, nonce) {

	let filter    = jQuery( el ).val();
	var act_nonce = jQuery( '.fil_act_nonce' ).val();

	// get filter actions.
	jQuery( 'div.wpsc-filter-actions' ).empty();
	var data = {
		action: 'wpsc_rp_get_filter_actions',
		filter,
		report: supportcandy.currentReportSlug,
		_ajax_nonce: act_nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			jQuery( 'div.wpsc-filter-actions' ).html( response );
		}
	);

	// apply filter.
	if (filter === 'custom') { // get custom filter modal window.

		wpsc_show_modal();
		var data = { action: 'wpsc_rp_get_custom_filter', _ajax_nonce: nonce };
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

	} else {

		wpsc_rp_run( supportcandy.currentReportRunFuntion );
	}
}

/**
 * Apply custom filter for ticket statistics
 */
function wpsc_rp_apply_custom_filter(el) {

	var filters = wpsc_get_condition_json( 'report_filters' );
	if ( filters.length === 0 || ( filters.length === 1 && filters[0].length === 0 )  ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}

	// add filters to local object.
	supportcandy.RpFilters = filters;
	wpsc_close_modal();

	var filter = jQuery( 'form.wpsc-report-filters' ).find( 'select[name=filter]' ).val();
	if (filter != 'custom') {
		jQuery( 'form.wpsc-report-filters' ).find( 'select[name=filter]' ).val( 'custom' );

		var act_nonce = jQuery( '.fil_act_nonce' ).val();
		// get filter actions.
		jQuery( 'div.wpsc-filter-actions' ).empty();
		var data = {
			action: 'wpsc_rp_get_filter_actions',
			filter: 'custom',
			report: supportcandy.currentReportSlug,
			_ajax_nonce: act_nonce
		};
		jQuery.post(
			supportcandy.ajax_url,
			data,
			function (response) {
				jQuery( 'div.wpsc-filter-actions' ).html( response );
			}
		);
	}

	wpsc_rp_run( supportcandy.currentReportRunFuntion );
}

/**
 * Add saved filter
 */
function wpsc_rp_add_saved_filter(el) {

	var filters = wpsc_get_condition_json( 'report_filters' );
	if ( filters.length === 0 || ( filters.length === 1 && filters[0].length === 0 )  ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}
	supportcandy.RpFilters = filters;
	wpsc_close_modal();

	// open label modal.
	setTimeout(
		function () {
			wpsc_show_modal();
			var data = {
				action: 'wpsc_rp_get_add_saved_filter'
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
		},
		500
	);
}

/**
 * Set add saved filter
 */
function wpsc_rp_set_add_saved_filter(el, nonce) {

	var form     = jQuery( '.wpsc-rp-ts-add-saved-filter' )[0];
	var dataform = new FormData( form );
	if ( ! dataform.get( 'label' )) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}
	dataform.append( 'filters', JSON.stringify( supportcandy.RpFilters ) );
	dataform.append( '_ajax_nonce', nonce );

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
			jQuery( 'form.wpsc-report-filters' ).find( 'select[name=filter]' ).html( res ).trigger( 'change' );
		}
	);
}

/**
 * Edit ticket stat user filter
 */
function wpsc_rp_get_edit_custom_filter(nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_rp_get_custom_filter',
		filters: JSON.stringify( supportcandy.RpFilters ),
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
 * Edit ticket stat user filter
 */
function wpsc_rp_get_edit_user_filter(id, nonce) {

	wpsc_show_modal();
	var data = {
		action: 'wpsc_rp_get_custom_filter',
		filter_id: id,
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
 * Update user filter
 */
function wpsc_rp_update_saved_filter(el, id, nonce) {

	var filters = wpsc_get_condition_json( 'report_filters' );
	if ( filters.length === 0 || ( filters.length === 1 && filters[0].length === 0 )  ) {
		alert( supportcandy.translations.req_fields_missing );
		return;
	}
	jQuery( el ).text( supportcandy.translations.please_wait );
	var data = {
		action: 'wpsc_rp_update_saved_filter',
		filter_id: id,
		filters: JSON.stringify( filters ),
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (response) {
			wpsc_close_modal();
			jQuery( 'form.wpsc-report-filters' ).find( 'select[name=filter]' ).trigger( 'change' );
		}
	);
}

/**
 * Delete saved filter
 */
function wpsc_rp_delete_user_filter(id, nonce) {

	var flag = confirm( supportcandy.translations.confirm );
	if ( ! flag) {
		return;
	}

	var data = {
		action: 'wpsc_rp_delete_user_filter',
		filter_id: id,
		_ajax_nonce: nonce
	};
	jQuery.post(
		supportcandy.ajax_url,
		data,
		function (res) {
			jQuery( 'form.wpsc-report-filters' ).find( 'select[name=filter]' ).html( res ).trigger( 'change' );
		}
	);
}

/**
 * Get ticket statistics
 */
function wpsc_rp_get_ticket_statistics(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.ticket-statistics, .wpsc-humbargar-menu-item.ticket-statistics' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.ticket_statistics );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=ticket-statistics' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set current report.
	supportcandy.currentReportSlug       = 'ticket-statistics';
	supportcandy.currentReportRunFuntion = 'ticket_statistics';

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_ticket_statistics' };
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
 * Run ticket statistics report
 */
async function wpsc_rp_run_ts_report(nonce) {

	supportcandy.temp.isReportProgress = true;

	jQuery( '#wpscTicketStatisticsCanvas' ).remove();
	jQuery( 'table.wpsc-rp-tbl' ).hide();
	jQuery( '.wpscPrograssLoaderContainer' ).show();
	jQuery( '.wpscPrograssLoader' ).circleProgress(
		{
			startAngle: -Math.PI / 2,
			size: 100,
			value: 0.0,
			lineCap: 'round',
			fill: { gradient: ['#ff1e41', '#ff5f43'] },
			animation: { duration: 0, easing: "circleProgressEasing" }
		}
	).on(
		'circle-animation-progress',
		function (event, progress, stepValue) {
			var percentage = Math.round( stepValue * 100 );
			jQuery( this ).find( 'strong' ).html( percentage + '<small>%</small>' );
		}
	);

	// get filters (if any).
	let form     = jQuery( '.wpsc-report-filters' )[0];
	var dataform = new FormData( form );
	let filter   = dataform.get( 'filter' );
	var filters  = {};
	if (filter == 'custom') {
		filters = supportcandy.RpFilters;
	}
	filters = JSON.stringify( filters );

	let fromDate = new Date( jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val() );
	let toDate   = new Date( jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val() );

	let report = {
		batches: wpsc_rp_get_baches(fromDate, toDate),
		labels: [],
		created: [],
		closed: []
	}

	var batchesLength = report.batches.length;
	for (let i = 0; i < batchesLength; i++) {

		var promises = [];

		var batchLength = report.batches[i].length;
		for (let j = 0; j < batchLength; j++) {

			var duration = report.batches[i][j];
			promises.push(
				new Promise(
					function (resolve, reject) {

						dataform = new FormData( form );
						dataform.append( 'action', 'wpsc_rp_run_ts_report' );
						dataform.append( 'filters', filters );
						dataform.append( 'from_date', duration.fromDate );
						dataform.append( 'to_date', duration.toDate );
						dataform.append( 'duration_type', duration.durationType );
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
								resolve( res );
							}
						).fail(
							function () {
								reject( new Error() );
							}
						);
					}
				)
			);
		}

		var isValidResults = true;
		var results        = await Promise.all( promises.map( p => p.catch( e => e ) ) );
		jQuery.each(
			results,
			function (index, response) {
				if (response instanceof Error) {
					isValidResults = false;
					return false;
				}
				report.labels.push( response.label );
				report.created.push( parseInt( response.created ) );
				report.closed.push( parseInt( response.closed ) );
			}
		);

		if ( ! isValidResults) {
			supportcandy.temp.isReportProgress = false;
			jQuery( '.wpscPrograssLoaderContainer' ).hide();
			jQuery( '.wpsc-setting-section-body' ).text( 'Something went wrong!' );
			return;
		}

		// update progrss.
		var progrssVal = (i + 1) / report.batches.length;
		jQuery( '.wpscPrograssLoader' ).circleProgress( 'value', progrssVal );
	}

	jQuery( '.wpscPrograssLoaderContainer' ).hide();
	jQuery( '.wpsc-setting-section-body' ).children().last().before( '<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>' );

	var data   = {
		labels: report.labels,
		datasets: [
			{
				label: 'Ocorrências criadas',
				backgroundColor: '#e74c3c',
				borderColor: '#e74c3c',
				data: report.created
			},
			{
				label: 'Ocorrências fechadas',
				backgroundColor: '#2980b9',
				borderColor: '#2980b9',
				data: report.closed
			}
		]
	};
	var config = {
		type: 'line',
		data,
		options: {
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	};
	new Chart(
		document.getElementById( 'wpscTicketStatisticsCanvas' ),
		config
	);

	jQuery( 'td.tickets-created' ).text( report.created.reduce( function (acc, val) { return acc + val; }, 0 ) );
	jQuery( 'td.tickets-closed' ).text( report.closed.reduce( function (acc, val) { return acc + val; }, 0 ) );
	jQuery( 'table.wpsc-rp-tbl' ).show();

	supportcandy.temp.isReportProgress = false;
}

/**
 * Get response delay
 */
function wpsc_rp_get_response_delay(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.response-delay, .wpsc-humbargar-menu-item.response-delay' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.response_delay );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=response-delay' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = 'response-delay';
	supportcandy.currentReportRunFuntion = 'response_delay';

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_response_delay' };
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
 * Run response delay reports
 */
async function wpsc_rp_run_rd_report(nonce) {

	supportcandy.temp.isReportProgress = true;

	jQuery( '#wpscTicketStatisticsCanvas' ).remove();
	jQuery( 'table.wpsc-rp-tbl' ).hide();
	jQuery( '.wpscPrograssLoaderContainer' ).show();
	jQuery( '.wpscPrograssLoader' ).circleProgress(
		{
			startAngle: -Math.PI / 2,
			size: 100,
			value: 0.0,
			lineCap: 'round',
			fill: { gradient: ['#ff1e41', '#ff5f43'] },
			animation: { duration: 0, easing: "circleProgressEasing" }
		}
	).on(
		'circle-animation-progress',
		function (event, progress, stepValue) {
			var percentage = Math.round( stepValue * 100 );
			jQuery( this ).find( 'strong' ).html( percentage + '<small>%</small>' );
		}
	);

	// get filters (if any).
	let form     = jQuery( '.wpsc-report-filters' )[0];
	var dataform = new FormData( form );
	let filter   = dataform.get( 'filter' );
	var filters  = {};
	if (filter == 'custom') {
		filters = supportcandy.RpFilters;
	}
	filters = JSON.stringify( filters );

	let fromDate = new Date( jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val() );
	let toDate   = new Date( jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val() );

	let report = {
		batches: wpsc_rp_get_baches(fromDate, toDate),
		labels: [],
		frd: [],
		totalFrDelay: [],
		ard: [],
		totalArDelay: [],
		count: []
	}

	var batchesLength = report.batches.length;
	for (let i = 0; i < batchesLength; i++) {

		var promises = [];

		var batchLength = report.batches[i].length;
		for (let j = 0; j < batchLength; j++) {

			var duration = report.batches[i][j];
			promises.push(
				new Promise(
					function (resolve, reject) {

						dataform = new FormData( form );
						dataform.append( 'action', 'wpsc_rp_run_rd_report' );
						dataform.append( 'filters', filters );
						dataform.append( 'from_date', duration.fromDate );
						dataform.append( 'to_date', duration.toDate );
						dataform.append( 'duration_type', duration.durationType );
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
								resolve( res );
							}
						).fail(
							function () {
								reject( new Error() );
							}
						);
					}
				)
			);
		}

		var isValidResults = true;
		var results        = await Promise.all( promises.map( p => p.catch( e => e ) ) );
		jQuery.each(
			results,
			function (index, response) {
				if (response instanceof Error) {
					isValidResults = false;
					return false;
				}
				report.labels.push( response.label );
				report.frd.push( response.frd );
				report.totalFrDelay.push( response.totalFrDelay );
				report.ard.push( response.ard );
				report.totalArDelay.push( response.totalArDelay );
				report.count.push( response.count );
			}
		);

		if ( ! isValidResults) {
			supportcandy.temp.isReportProgress = false;
			jQuery( '.wpscPrograssLoaderContainer' ).hide();
			jQuery( '.wpsc-setting-section-body' ).text( 'Something went wrong!' );
			return;
		}

		// update progrss.
		var progrssVal = (i + 1) / report.batches.length;
		jQuery( '.wpscPrograssLoader' ).circleProgress( 'value', progrssVal );
	}

	jQuery( '.wpscPrograssLoaderContainer' ).hide();
	jQuery( '.wpsc-setting-section-body' ).children().last().before( '<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>' );

	var data   = {
		labels: report.labels,
		datasets: [
			{
				label: 'Atraso na primeira resposta',
				backgroundColor: '#e74c3c',
				borderColor: '#e74c3c',
				data: report.frd
		},
			{
				label: 'Atraso médio de resposta',
				backgroundColor: '#2980b9',
				borderColor: '#2980b9',
				data: report.ard
		}
		]
	};
	var config = {
		type: 'line',
		data,
		options: {
			scales: {
				y: {
					beginAtZero: true,
					title: {
						display: true,
						'text': 'Hours'
					}
				}
			}
		}
	};
	new Chart(
		document.getElementById( 'wpscTicketStatisticsCanvas' ),
		config
	);

	// calculate total response delay averages.
	var totalCount   = report.count.reduce( function (acc, val) { return acc + val; }, 0 );
	var totalFrDelay = report.totalFrDelay.reduce( function (acc, val) { return acc + val; }, 0 );
	var totalArDelay = report.totalArDelay.reduce( function (acc, val) { return acc + val; }, 0 );
	var frDelay      = parseFloat( totalFrDelay / totalCount ).toFixed( 2 );
	var frDelay 	 = ! isNaN( frDelay ) ? frDelay : 0;
	var arDelay      = parseFloat( totalArDelay / totalCount ).toFixed( 2 );
	var arDelay 	 = ! isNaN( arDelay ) ? arDelay : 0;

	jQuery( 'td.frd' ).text( frDelay );
	jQuery( 'td.ard' ).text( arDelay );
	jQuery( 'table.wpsc-rp-tbl' ).show();

	supportcandy.temp.isReportProgress = false;
}

/**
 * Get closing delay
 */
function wpsc_rp_get_closing_delay(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.closing-delay, .wpsc-humbargar-menu-item.closing-delay' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.closing_delay );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=closing-delay' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = 'closing-delay';
	supportcandy.currentReportRunFuntion = 'closing_delay';

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_closing_delay' };
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
 * Run response delay reports
 */
async function wpsc_rp_run_cd_report(nonce) {

	supportcandy.temp.isReportProgress = true;

	jQuery( '#wpscTicketStatisticsCanvas' ).remove();
	jQuery( 'table.wpsc-rp-tbl' ).hide();
	jQuery( '.wpscPrograssLoaderContainer' ).show();
	jQuery( '.wpscPrograssLoader' ).circleProgress(
		{
			startAngle: -Math.PI / 2,
			size: 100,
			value: 0.0,
			lineCap: 'round',
			fill: { gradient: ['#ff1e41', '#ff5f43'] },
			animation: { duration: 0, easing: "circleProgressEasing" }
		}
	).on(
		'circle-animation-progress',
		function (event, progress, stepValue) {
			var percentage = Math.round( stepValue * 100 );
			jQuery( this ).find( 'strong' ).html( percentage + '<small>%</small>' );
		}
	);

	// get filters (if any).
	let form     = jQuery( '.wpsc-report-filters' )[0];
	var dataform = new FormData( form );
	let filter   = dataform.get( 'filter' );
	var filters  = {};
	if (filter == 'custom') {
		filters = supportcandy.RpFilters;
	}
	filters = JSON.stringify( filters );
	
	let fromDate = new Date( jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val() );
	let toDate   = new Date( jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val() );
	
	let report = {
		batches: wpsc_rp_get_baches(fromDate, toDate),
		labels: [],
		cd: [],
		totalClosingDelay: [],
		count: []
	}

	var batchesLength = report.batches.length;
	for (let i = 0; i < batchesLength; i++) {

		var promises    = [];
		var batchLength = report.batches[i].length;

		for (let j = 0; j < batchLength; j++) {

			var duration = report.batches[i][j];
			promises.push(
				new Promise(
					function (resolve, reject) {

						dataform = new FormData( form );
						dataform.append( 'action', 'wpsc_rp_run_cd_report' );
						dataform.append( 'filters', filters );
						dataform.append( 'from_date', duration.fromDate );
						dataform.append( 'to_date', duration.toDate );
						dataform.append( 'duration_type', duration.durationType );
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
								resolve( res );
							}
						).fail(
							function () {
								reject( new Error() );
							}
						);
					}
				)
			);
		}

		var isValidResults = true;
		var results        = await Promise.all( promises.map( p => p.catch( e => e ) ) );
		jQuery.each(
			results,
			function (index, response) {
				if (response instanceof Error) {
					isValidResults = false;
					return false;
				}
				report.labels.push( response.label );
				report.cd.push( response.cd );
				report.totalClosingDelay.push( response.totalClosingDelay );
				report.count.push( response.count );
			}
		);

		if ( ! isValidResults) {
			supportcandy.temp.isReportProgress = false;
			jQuery( '.wpscPrograssLoaderContainer' ).hide();
			jQuery( '.wpsc-setting-section-body' ).text( 'Something went wrong!' );
			return;
		}

		// update progrss.
		var progrssVal = (i + 1) / report.batches.length;
		jQuery( '.wpscPrograssLoader' ).circleProgress( 'value', progrssVal );
	}

	jQuery( '.wpscPrograssLoaderContainer' ).hide();
	jQuery( '.wpsc-setting-section-body' ).children().last().before( '<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>' );

	var data   = {
		labels: report.labels,
		datasets: [
			{
				label: 'Atraso no fechamento do ticket',
				backgroundColor: '#e74c3c',
				borderColor: '#e74c3c',
				data: report.cd
		}
		]
	};
	var config = {
		type: 'line',
		data,
		options: {
			scales: {
				y: {
					beginAtZero: true,
					title: {
						display: true,
						'text': 'Days'
					}
				}
			}
		}
	};
	new Chart(
		document.getElementById( 'wpscTicketStatisticsCanvas' ),
		config
	);

	// calculate total delay averages.
	var totalCount        = report.count.reduce( function (acc, val) { return acc + val; }, 0 );
	var totalClosingDelay = report.totalClosingDelay.reduce( function (acc, val) { return acc + val; }, 0 );
	var closingDelay      = parseFloat( totalClosingDelay / totalCount ).toFixed( 2 );

	jQuery( 'td.cd' ).text( closingDelay );
	jQuery( 'table.wpsc-rp-tbl' ).show();

	supportcandy.temp.isReportProgress = false;
}

/**
 * Get communication gap
 */
function wpsc_rp_get_communication_gap(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.communication-gap, .wpsc-humbargar-menu-item.communication-gap' ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.communication_gap );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=communication-gap' );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = 'communication-gap';
	supportcandy.currentReportRunFuntion = 'communication_gap';

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_communication_gap' };
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
 * Run communication gap reports
 */
async function wpsc_rp_run_cg_report(nonce) {

	supportcandy.temp.isReportProgress = true;

	jQuery( '#wpscTicketStatisticsCanvas' ).remove();
	jQuery( 'table.wpsc-rp-tbl' ).hide();
	jQuery( '.wpscPrograssLoaderContainer' ).show();
	jQuery( '.wpscPrograssLoader' ).circleProgress(
		{
			startAngle: -Math.PI / 2,
			size: 100,
			value: 0.0,
			lineCap: 'round',
			fill: { gradient: ['#ff1e41', '#ff5f43'] },
			animation: { duration: 0, easing: "circleProgressEasing" }
		}
	).on(
		'circle-animation-progress',
		function (event, progress, stepValue) {
			var percentage = Math.round( stepValue * 100 );
			jQuery( this ).find( 'strong' ).html( percentage + '<small>%</small>' );
		}
	);

	// get filters (if any).
	let form     = jQuery( '.wpsc-report-filters' )[0];
	var dataform = new FormData( form );
	let filter   = dataform.get( 'filter' );
	var filters  = {};
	if (filter == 'custom') {
		filters = supportcandy.RpFilters;
	}
	filters = JSON.stringify( filters );

	let fromDate = new Date( jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val() );
	let toDate   = new Date( jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val() );

	let report = {
		batches: wpsc_rp_get_baches(fromDate, toDate),
		labels: [],
		communicationGap: [],
		totalCommunicationGap: [],
		count: []
	}

	var batchesLength = report.batches.length;
	for (let i = 0; i < batchesLength; i++) {

		var promises    = [];
		var batchLength = report.batches[i].length;

		for (let j = 0; j < batchLength; j++) {

			var duration = report.batches[i][j];
			promises.push(
				new Promise(
					function (resolve, reject) {

						dataform = new FormData( form );
						dataform.append( 'action', 'wpsc_rp_run_cg_report' );
						dataform.append( 'filters', filters );
						dataform.append( 'from_date', duration.fromDate );
						dataform.append( 'to_date', duration.toDate );
						dataform.append( 'duration_type', duration.durationType );
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
								resolve( res );
							}
						).fail(
							function () {
								reject( new Error() );
							}
						);
					}
				)
			);
		}

		var isValidResults = true;
		var results        = await Promise.all( promises.map( p => p.catch( e => e ) ) );
		jQuery.each(
			results,
			function (index, response) {
				if (response instanceof Error) {
					isValidResults = false;
					return false;
				}
				report.labels.push( response.label );
				report.communicationGap.push( response.communicationGap );
				report.totalCommunicationGap.push( response.totalCommunicationGap );
				report.count.push( response.count );
			}
		);

		if ( ! isValidResults) {
			supportcandy.temp.isReportProgress = false;
			jQuery( '.wpscPrograssLoaderContainer' ).hide();
			jQuery( '.wpsc-setting-section-body' ).text( 'Something went wrong!' );
			return;
		}

		// update progrss.
		var progrssVal = (i + 1) / report.batches.length;
		jQuery( '.wpscPrograssLoader' ).circleProgress( 'value', progrssVal );
	}

	jQuery( '.wpscPrograssLoaderContainer' ).hide();
	jQuery( '.wpsc-setting-section-body' ).children().last().before( '<canvas id="wpscTicketStatisticsCanvas" class="wpscRpCanvas"></canvas>' );

	var data   = {
		labels: report.labels,
		datasets: [
			{
				label: 'Lacuna de comunicação',
				backgroundColor: '#e74c3c',
				borderColor: '#e74c3c',
				data: report.communicationGap
		}
		]
	};
	var config = {
		type: 'line',
		data,
		options: {
			scales: {
				y: {
					beginAtZero: true,
					title: {
						display: true,
						'text': 'Number of threads'
					}
				}
			}
		}
	};
	new Chart(
		document.getElementById( 'wpscTicketStatisticsCanvas' ),
		config
	);

	// calculate total delay averages.
	var totalCount            = report.count.reduce( function (acc, val) { return acc + val; }, 0 );
	var totalCommunicationGap = report.totalCommunicationGap.reduce( function (acc, val) { return acc + val; }, 0 );
	var communicationGap      = parseFloat( totalCommunicationGap / totalCount ).toFixed( 2 );

	jQuery( 'td.cg' ).text( communicationGap );
	jQuery( 'table.wpsc-rp-tbl' ).show();

	supportcandy.temp.isReportProgress = false;
}

/**
 * Get single select field report
 */
function wpsc_rp_get_cf_single_select(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.cf_slug );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_single_select', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run single select reports
 */
function wpsc_rp_run_cfss_report(nonce) {

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
	dataform.append( 'action', 'wpsc_rp_run_cfss_report' );
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

			height = labels.length * 30;

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
								'text': 'Número de ocorrência'
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

/**
 * Get multi select field report
 */
function wpsc_rp_get_cf_multi_select(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.cf_slug );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_multi_select', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run multi select reports
 */
function wpsc_rp_run_cfms_report(nonce) {

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
	dataform.append( 'action', 'wpsc_rp_run_cfms_report' );
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

			height = labels.length * 30;

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
								'text': 'Número de ocorrência'
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

/**
 * Get radio select field report
 */
function wpsc_rp_get_cf_radio_select(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.cf_slug );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_radio_select', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run radio select reports
 */
function wpsc_rp_run_cfrs_report(nonce) {

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
	dataform.append( 'action', 'wpsc_rp_run_cfrs_report' );
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

			height = labels.length * 30;

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
								'text': 'Número de ocorrência'
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

/**
 * Get checkbox field report
 */
function wpsc_rp_get_cf_checkbox(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.cf_slug );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_checkbox', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run checkbox reports
 */
function wpsc_rp_run_cfcheck_report(nonce) {

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
	dataform.append( 'action', 'wpsc_rp_run_cfcheck_report' );
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

			height = labels.length * 30;

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
								'text': 'Número de ocorrência'
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

/**
 * Get category field report
 */
function wpsc_rp_get_category(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.category );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_category', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run category reports
 */
function wpsc_rp_run_category_report(nonce) {

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
	dataform.append( 'action', 'wpsc_rp_run_category_report' );
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

			height = labels.length * 30;

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
								'text': 'Número de ocorrência'
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

/**
 * Get priority report
 */
function wpsc_rp_get_priority(cf_slug, nonce, is_humbargar = false) {

	// do not call if other report is still loading.
	if (supportcandy.temp.isReportProgress) {
		return;
	}

	if (is_humbargar) {
		wpsc_toggle_humbargar();
	}

	jQuery( '.wpsc-setting-nav, .wpsc-humbargar-menu-item' ).removeClass( 'active' );
	jQuery( '.wpsc-setting-nav.' + cf_slug + ', .wpsc-humbargar-menu-item.' + cf_slug ).addClass( 'active' );
	jQuery( '.wpsc-humbargar-title' ).html( supportcandy.humbargar_titles.priority );

	window.history.replaceState( {}, null, 'admin.php?page=wpsc-reports&section=' + cf_slug );
	jQuery( '.wpsc-setting-body' ).html( supportcandy.loader_html );

	// set run function.
	supportcandy.currentReportSlug       = cf_slug;
	supportcandy.currentReportRunFuntion = cf_slug;

	wpsc_scroll_top();

	var data = { action: 'wpsc_rp_get_priority', 'cf_slug': cf_slug, _ajax_nonce: nonce };
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
 * Run priority reports
 */
function wpsc_rp_run_priority_report(nonce) {

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
	dataform.append( 'action', 'wpsc_rp_run_priority_report' );
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

			height = labels.length * 30;

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
								'text': 'Número de ocorrência'
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

/**
 * Print report common function
 */
function wpsc_print_report() {
	
	var canvas		= document.getElementById( 'wpscTicketStatisticsCanvas' );
	var start_date	= jQuery( '.setting-filter-item.from-date' ).find( 'input' ).val();
	var end_date	= jQuery( '.setting-filter-item.to-date' ).find( 'input' ).val();
	var duration	= 'Report duration : ' + start_date + '  to  ' + end_date;
    var print_window = window.open( '', '', 'width=500,height=400' );

	print_window.document.write( "<div style='text-align:center; color:#808080;'><h2>" + jQuery( '.wpsc-setting-header h2' ).html() + "</h2></div>" );
	print_window.document.write( "<div style='text-align:center; color:#808080;'><small>" + duration +"</small></div>" );
	print_window.document.write( "<br><img src='" + canvas.toDataURL() + "'/>" );
    print_window.document.close();
	print_window.print();
	
}