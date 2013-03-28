jQuery(function() {
  var optional_fields = ["select", "radio", "checkbox"];
  
  if (jQuery( "#fields_sortable" ).length > 0) {
    jQuery( "#fields_sortable" ).sortable({
      update: function( event, ui ) {
        make_fields_sortable_odd_and_even_rows();
        sort_fields();
      }
    });    
  }

  // if (jQuery("#sections_sortable tbody").length > 0) {
  //   jQuery( "#sections_sortable tbody" ).sortable({
  //     update: function( event, ui ) {
  //       jQuery("#sections_sortable tbody tr").each(function() {
  //         jQuery.ajax({
  //           type: 'POST',
  //           url: AFormsAjax.sort_section_url,
  //           data: {ID: jQuery.trim(jQuery(this).find("td.id").html()), section_order: jQuery(this).index()}
  //         });
  //       });
  //     }
  //   });   
  // }

  jQuery(document).delegate(".delete-option", "click", function() {
    jQuery(this).parent().addClass("deleted");
    new_options = [];
    jQuery(this).parents(".value-option-controls ul").find("li:not(.deleted)").each(function() {
      if (jQuery(this).find("input[name=key]").length > 0) {
        var key = jQuery(this).find("input[name=key]").val();
        var val = jQuery(this).find("input[name=value]").val();
        if (val != "") {
          if (new_options.indexOf(key + ":" + val) < 0) {
            console.log(key + ":" + val);
            new_options.push(key + ":" + val);
          }
        } else {
          if (new_options.indexOf(key + ":" + key) < 0) {
            new_options.push(key + ":" + key);
          }
        }
      }
    });
    jQuery(this).parent().parent().parent().parent().find(".value-options").val(new_options.join(","));
    jQuery(this).parent().remove();
    return false;
  });

  jQuery(document).delegate(".add.value-option", "click", function() {
    jQuery(this).parent().parent().find(".value-option-controls ul").append(create_option_value_row("", ""));
    return false;
  });

	jQuery("#new_form_row").click(function() {
		var row = jQuery("#fields_row_clone li").clone().appendTo('#fields_sortable');
		jQuery(row).find("input, select").each(function() {
			jQuery(this).attr("name", jQuery(this).attr("name").replace("ph_", "")+"[]");
      if (jQuery(this).attr("id")) {
        jQuery(this).attr("id", jQuery(this).attr("id").replace("ph_", "") + "_" + jQuery("#fields_sortable > li.shiftable").length);
      }
		});
		jQuery(row).find("label").each(function() {
      jQuery(this).attr("for", jQuery(this).attr("for").replace("ph_", "") + "_" + jQuery("#fields_sortable > li.shiftable").length);
		});

		jQuery.ajax({
        type: "post",
        url: AFormsAjax.ajax_url,
        data: {section_id: jQuery("#fields_sortable li.section-heading:last").attr("id").replace("section_id_", ""), field_order: jQuery("#fields_sortable > li.shiftable").length, action: "add_field_to_section"}
    }).success(function(data) {
      var tmp = data.split("::");
    	jQuery("#fields_sortable li:last").attr("id", tmp[1]);
      jQuery("#fields_sortable li input.section_id").val(tmp[0]);
      jQuery(row).find(".delete").attr("href", AFormsAjax.base_url + "&action=delete&a_form_page=section&fid="+tmp[1]+"&section_id="+tmp[0]);
      sort_fields();
    });
    jQuery("#fields_sortable > li:not(.shiftable)").remove();
    make_fields_sortable_odd_and_even_rows();
    row.find(".field-type").change();
		return false;
	});

  jQuery(document).delegate(".value-option-controls input", "change", function() {
    var new_options = [];

    jQuery(this).parent().parent().find("li").each(function() {
      if (jQuery(this).find("input[name=key]").length > 0) {
        var key = jQuery(this).find("input[name=key]").val();
        var val = jQuery(this).find("input[name=value]").val();
        if (val != "") {
          new_options.push(key + ":" + val);
        } else {
          new_options.push(key + ":" + key);
        }
      }
    });
    jQuery(this).parent().parent().parent().parent().find(".value-options").val(new_options.join(","));
  });

  jQuery(document).delegate("#fields_sortable li .field-type", "change", function() {
    var row = jQuery(this).parent().parent();
    if (optional_fields.indexOf(jQuery(this).val()) > -1) {
      row.find(".value-option-controls").show();
      row.find(".file-ext-controls").hide();
    } else if (jQuery(this).val() == "file") {
      row.find(".value-option-controls").hide();
      row.find(".file-ext-controls").show();
    } else {
      row.find(".value-option-controls").hide();
      row.find(".file-ext-controls").hide();
    }
  });

  jQuery("#css_file_selection").change(function() {
    jQuery.ajax({
      type: "post",url: AFormsAjax.ajax_url,data: {css_file_selection: jQuery("#css_file_selection").val(), action: "aform_css_file_selector"},
      success: function(response){
        jQuery("#css_content").html(response);
      }
    });
  });

  jQuery( "#sections_sortable tr" ).addClass("shiftable");

  jQuery("#fields_sortable li").each(function() {
    if (optional_fields.indexOf(jQuery(this).find(".field-type").val()) < 0) {
      jQuery(this).find(".value-option-controls").hide();
    }
  });

  jQuery("#fields_sortable .value-option-controls").each(function() {
    var this_control = jQuery(this);
    var val_options = jQuery(this).parent().find("input.value-options");
    if (val_options.val() != "") {
      var options = val_options.val().split(",");
      for(i=0;i<options.length;i++) {
        if (options[i].match(":")) {
          option_with_value = options[i].split(":");
          key = option_with_value[0];
          value = option_with_value[1];
          this_control.find("ul").append(create_option_value_row(key, value));
        } else {
          this_control.find("ul").append(create_option_value_row(options[i], options[i]));
        }
      }
    }
  });

  jQuery("#fields_sortable .file-ext-controls").each(function() {
    if (jQuery(this).parent().find("select.field-type").val() == "file") {
      jQuery(this).show();
    }
  });

  make_fields_sortable_odd_and_even_rows();

  jQuery("table tr:odd").addClass("odd");
  jQuery("table tr:even").addClass("even");

  jQuery("td.tracking-enabled").each(function() {
    if (jQuery.trim(jQuery(this).html()) == "1") {
      jQuery(this).html("Yes");
    } else {
      jQuery(this).html("No");
    }
  });

  jQuery("html,body").animate({
    scrollTop: (jQuery("span.error, #sections_heading").offset().top - 100)
  }, 2000);
});

function make_fields_sortable_odd_and_even_rows() {
  jQuery("#fields_sortable > li").removeClass("odd").removeClass("even");
  jQuery("#fields_sortable > li:odd").addClass("odd");
  jQuery("#fields_sortable > li:even").addClass("even");
}

function create_option_value_row(key_value, value_value) {
  return "<li><input type='text' name='key' value='"+key_value+"'/><span class='colon'>:</span><input type='text' name='value' value='"+value_value+"'/> <a href='#' class='delete-option'>Remove</a></li>";
}

function sort_fields() {
  var section_id = 0;
  var section_sort_order = 0;

  if (!jQuery("#fields_sortable li:first").hasClass("section-heading")) {
    jQuery("#fields_sortable li.section-heading:first").insertBefore(jQuery("#fields_sortable li:first"));
    make_fields_sortable_odd_and_even_rows();
  }

  jQuery("#fields_sortable > li.shiftable").each(function() {
    if (jQuery(this).hasClass("section-heading")) {
      section_id = jQuery(this).attr("id").replace("section_id_", "");
      jQuery.ajax({
        type: 'POST',
        url: AFormsAjax.sort_section_url,
        data: {ID: section_id, section_order: section_sort_order}
      });
      section_sort_order++;
    } 
    jQuery.ajax({
      type: 'POST',
      url: AFormsAjax.sort_field_url,
      data: {FID: jQuery(this).attr("id"), field_order: jQuery(this).index(), section_id: section_id}
    });
  });
}