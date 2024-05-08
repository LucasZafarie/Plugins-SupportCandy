/**
 * Load emt section
 */
function wpsc_get_emt_settings(is_humbargar = false) {
  if (is_humbargar) {
    wpsc_toggle_humbargar();
  }

  jQuery(".wpsc-setting-nav, .wpsc-humbargar-menu-item").removeClass("active");
  jQuery(".wpsc-setting-nav.emt, .wpsc-humbargar-menu-item.emt").addClass(
    "active"
  );
  jQuery(".wpsc-humbargar-title").html(
    supportcandy.humbargar_titles.email_piping
  );

  if (supportcandy.current_section !== "emt") {
    supportcandy.current_section = "emt";
    supportcandy.current_tab = "general";
  }

  window.history.replaceState(
    {},
    null,
    "admin.php?page=wpsc-settings&section=" +
      supportcandy.current_section +
      "&tab=" +
      supportcandy.current_tab
  );
  jQuery(".wpsc-setting-body").html(supportcandy.loader_html);

  wpsc_scroll_top();

  var data = {
    action: "wpsc_get_emt_settings",
    tab: supportcandy.current_tab,
  };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    jQuery(".wpsc-setting-body").html(response);
    wpsc_reset_responsive_style();
    jQuery(
      ".wpsc-setting-tab-container button." + supportcandy.current_tab
    ).trigger("click");
  });
}

/**
 * Load general tab ui
 */
function wpsc_emt_get_general_settings() {
  supportcandy.current_tab = "general";
  jQuery(".wpsc-setting-tab-container button").removeClass("active");
  jQuery(
    ".wpsc-setting-tab-container button." + supportcandy.current_tab
  ).addClass("active");

  window.history.replaceState(
    {},
    null,
    "admin.php?page=wpsc-settings&section=" +
      supportcandy.current_section +
      "&tab=" +
      supportcandy.current_tab
  );
  jQuery(".wpsc-setting-section-body").html(supportcandy.loader_html);

  wpsc_scroll_top();

  var data = { action: "wpsc_emt_get_general_settings" };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    jQuery(".wpsc-setting-section-body").html(response);
    wpsc_reset_responsive_style();
  });
}

/**
 * Set general settings
 */
function wpsc_emt_set_general_settings(el) {
  var form = jQuery(".wpsc-emt-general-settings")[0];
  var dataform = new FormData(form);
  jQuery(el).text(supportcandy.translations.please_wait);
  jQuery
    .ajax({
      url: supportcandy.ajax_url,
      type: "POST",
      data: dataform,
      processData: false,
      contentType: false,
    })
    .done(function (res) {
      wpsc_emt_get_general_settings();
    });
}

/**
 * Reset general settings
 */
function wpsc_emt_reset_general_settings(el, nonce) {
  jQuery(el).text(supportcandy.translations.please_wait);
  var data = { action: "wpsc_emt_reset_general_settings", _ajax_nonce: nonce };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    wpsc_emt_get_general_settings();
  });
}

/**
 *  Get mailchimp settings
 */
function wpsc_get_mailchimp_settings() {
  supportcandy.current_tab = "mailchimp";
  jQuery(".wpsc-setting-tab-container button").removeClass("active");
  jQuery(
    ".wpsc-setting-tab-container button." + supportcandy.current_tab
  ).addClass("active");

  window.history.replaceState(
    {},
    null,
    "admin.php?page=wpsc-settings&section=" +
      supportcandy.current_section +
      "&tab=" +
      supportcandy.current_tab
  );
  jQuery(".wpsc-setting-section-body").html(supportcandy.loader_html);

  wpsc_scroll_top();

  var data = { action: "wpsc_get_mailchimp_settings" };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    jQuery(".wpsc-setting-section-body").html(response);
    wpsc_reset_responsive_style();
  });
}

/**
 * Set mailchimp settings
 */
function wpsc_set_mailchimp_setting(el) {
  var form = jQuery(".wpsc-mailchimp-setting")[0];
  var dataform = new FormData(form);
  var api_key = dataform.get("api-key");
  var audience = dataform.get("audience");
  if (!api_key || !audience) {
    alert(supportcandy.translations.req_fields_missing);
    return;
  }

  // audience name.
  var name = jQuery("#wpsc-audience").text().trim();
  dataform.append("audience_name", name);

  var tags = {};
  var selected_tags = jQuery("#subscriber-tags").selectWoo("data");
  for (var i = 0; i < selected_tags.length; i++) {
    tags[selected_tags[i].id] = selected_tags[i].text;
  }
  dataform.append("tags", JSON.stringify(tags));

  jQuery(el).text(supportcandy.translations.please_wait);
  jQuery
    .ajax({
      url: supportcandy.ajax_url,
      type: "POST",
      data: dataform,
      processData: false,
      contentType: false,
    })
    .done(function (res) {
      wpsc_get_mailchimp_settings();
    });
}

/**
 * Reset mailchimp settings
 */
function wpsc_reset_mailchimp_setting(el, nonce) {
  jQuery(".wpsc-modal-footer button").attr("disabled", true);
  jQuery(el).text(supportcandy.translations.please_wait);
  var data = { action: "wpsc_reset_mailchimp_setting", _ajax_nonce: nonce };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    wpsc_get_mailchimp_settings();
  });
}

/**
 *  Get getresponse settings
 */
function wpsc_get_getresponse_settings() {
  supportcandy.current_tab = "getresponse";
  jQuery(".wpsc-setting-tab-container button").removeClass("active");
  jQuery(
    ".wpsc-setting-tab-container button." + supportcandy.current_tab
  ).addClass("active");

  window.history.replaceState(
    {},
    null,
    "admin.php?page=wpsc-settings&section=" +
      supportcandy.current_section +
      "&tab=" +
      supportcandy.current_tab
  );
  jQuery(".wpsc-setting-section-body").html(supportcandy.loader_html);

  wpsc_scroll_top();

  var data = { action: "wpsc_get_getresponse_settings" };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    jQuery(".wpsc-setting-section-body").html(response);
    wpsc_reset_responsive_style();
  });
}

/**
 * Set getresponse settings
 */
function wpsc_set_getresponse_setting(el) {
  var form = jQuery(".wpsc-getresponse-setting")[0];
  var dataform = new FormData(form);
  var api_key = dataform.get("api-key");
  var audience = dataform.get("audience");
  if (!api_key || !audience) {
    alert(supportcandy.translations.req_fields_missing);
    return;
  }

  // audience name.
  var name = jQuery("#wpsc-audience").text().trim();
  dataform.append("audience_name", name);

  var tags = {};
  var selected_tags = jQuery("#wpsc-subscriber-tags").selectWoo("data");
  for (var i = 0; i < selected_tags.length; i++) {
    tags[selected_tags[i].id] = selected_tags[i].text;
  }
  dataform.append("tags", JSON.stringify(tags));

  jQuery(el).text(supportcandy.translations.please_wait);
  jQuery
    .ajax({
      url: supportcandy.ajax_url,
      type: "POST",
      data: dataform,
      processData: false,
      contentType: false,
    })
    .done(function (res) {
      wpsc_get_getresponse_settings();
    });
}

/**
 * Reset getresponse settings
 */
function wpsc_reset_getresponse_setting(el, nonce) {
  jQuery(".wpsc-modal-footer button").attr("disabled", true);
  jQuery(el).text(supportcandy.translations.please_wait);
  var data = { action: "wpsc_reset_getresponse_setting", _ajax_nonce: nonce };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    wpsc_get_getresponse_settings();
  });
}

/**
 *  Get sendinblue settings
 */
function wpsc_get_sendinblue_settings() {
  supportcandy.current_tab = "sendinblue";
  jQuery(".wpsc-setting-tab-container button").removeClass("active");
  jQuery(
    ".wpsc-setting-tab-container button." + supportcandy.current_tab
  ).addClass("active");

  window.history.replaceState(
    {},
    null,
    "admin.php?page=wpsc-settings&section=" +
      supportcandy.current_section +
      "&tab=" +
      supportcandy.current_tab
  );
  jQuery(".wpsc-setting-section-body").html(supportcandy.loader_html);

  wpsc_scroll_top();

  var data = { action: "wpsc_get_sendinblue_settings" };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    jQuery(".wpsc-setting-section-body").html(response);
    wpsc_reset_responsive_style();
  });
}

/**
 * Set sendinblue settings
 */
function wpsc_set_sendinblue_setting(el) {
  var form = jQuery(".wpsc-sendinblue-setting")[0];
  var dataform = new FormData(form);
  var api_key = dataform.get("api-key");
  var audience = dataform.get("audience");
  if (!api_key || !audience) {
    alert(supportcandy.translations.req_fields_missing);
    return;
  }

  // audience name.
  var name = jQuery("#wpsc-audience").text().trim();
  dataform.append("audience_name", name);

  jQuery(el).text(supportcandy.translations.please_wait);
  jQuery
    .ajax({
      url: supportcandy.ajax_url,
      type: "POST",
      data: dataform,
      processData: false,
      contentType: false,
    })
    .done(function (res) {
      wpsc_get_sendinblue_settings();
    });
}

/**
 * Reset sendinblue settings
 */
function wpsc_reset_sendinblue_setting(el, nonce) {
  jQuery(".wpsc-modal-footer button").attr("disabled", true);
  jQuery(el).text(supportcandy.translations.please_wait);
  var data = { action: "wpsc_reset_sendinblue_setting", _ajax_nonce: nonce };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    wpsc_get_sendinblue_settings();
  });
}

/**
 * Show/hide subscribe option
 */
function wpsc_emt_toggel_subscribe_option() {
  cust_id = jQuery("select.create-as").val();
  var data = {
    action: "wpsc_emt_toggel_subscribe_option",
    cust_id: cust_id,
    _ajax_nonce: supportcandy.nonce,
  };
  jQuery.post(supportcandy.ajax_url, data, function (response) {
    if (response.subscribed) {
      jQuery(".wpsc-suscribe-mail").hide();
    } else {
      jQuery(".wpsc-suscribe-mail").show();
    }
  });
}
