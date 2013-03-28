<?php
final class AForm {

  public static function array_validation_rules() {
    return array(
      "form_name" => "required", 
      "to_email" => "required multi-emails", 
      "to_cc_email" => "multi-emails", 
      "to_bcc_email" => "multi-emails", 
      "subject" => "required"
    );
  }

	public static function update() {

    $send_confirmation_email_valid = true;
    if ($_POST["send_confirmation_email"] == "1" && $_POST["confirmation_from_email"] == "") {
        $_SESSION["confirmation_from_email_error"] = " must have a value. ";
        $send_confirmation_email_valid = false;
    } 
    $form_valid = tom_validate_form(AForm::array_validation_rules());

    $fields_valid = AFormFields::update();

		if ($send_confirmation_email_valid && $form_valid && $fields_valid) {

      $valid = tom_update_record_by_id("a_form_forms", 
      tom_get_form_query_strings("a_form_forms", array("created_at", "updated_at"), array("updated_at" => gmdate( 'Y-m-d H:i:s'))), "ID", $_POST["ID"]);
      
      if ($valid && $fields_valid) {
        if ($_POST["sub_action"] == "Update") {
          $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Update Complete&action=edit&id=".$_POST["ID"]."#sections_heading";
        } else {
          $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Update Complete#sections_heading";
        }
        
        tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
        exit;
      }
      
    }
	}
	public static function create() {

    $send_confirmation_email_valid = true;
    if ($_POST["send_confirmation_email"] == "1" && $_POST["confirmation_from_email"] == "") {
        $_SESSION["confirmation_from_email_error"] = " must have a value. ";
        $send_confirmation_email_valid = false;
    } 
    $form_valid = tom_validate_form(AForm::array_validation_rules());

    if ($send_confirmation_email_valid && $form_valid) {
      $current_datetime = gmdate( 'Y-m-d H:i:s');
      $valid = tom_insert_record("a_form_forms", 
        tom_get_form_query_strings("a_form_forms", array("ID", "created_at", "updated_at"), array("created_at" => $current_datetime)));
      
      if ($valid) {
        global $wpdb;
        $form_id = $wpdb->insert_id;
        tom_insert_record("a_form_sections", 
          array("section_name" => "Form Fields",
                "form_id" => $form_id, 
                "created_at" => $current_datetime
               )
        );
        $section_id = $wpdb->insert_id;
        
        tom_insert_record("a_form_fields", array("form_id" => $form_id, "validation" => "required", "field_label" => "Name", "field_type" => "text", "section_id" => $section_id, "created_at" => $current_datetime, "field_order" => 0));
				$from_name_id = $wpdb->insert_id;
        tom_insert_record("a_form_fields", array("form_id" => $form_id, "validation" => "required email", "field_label" => "Email", "field_type" => "text", "section_id" => $section_id, "created_at" => $current_datetime, "field_order" => 1));
				$from_email_id = $wpdb->insert_id;
        tom_insert_record("a_form_fields", array("form_id" => $form_id, "validation" => "required", "field_label" => "Subject", "field_type" => "text", "section_id" => $section_id, "created_at" => $current_datetime, "field_order" => 2));
        $from_subject_id = $wpdb->insert_id;
        tom_insert_record("a_form_fields", array("form_id" => $form_id, "validation" => "required", "field_label" => "Phone","field_type" => "text", "section_id" => $section_id, "created_at" => $current_datetime, "field_order" => 3));
				tom_insert_record("a_form_fields", array("form_id" => $form_id, "validation" => "required", "field_label" => "Message", "field_type" => "textarea", "section_id" => $section_id, "created_at" => $current_datetime, "field_order" => 4));

        $valid = tom_update_record_by_id("a_form_forms", array(
          "field_name_id" => $from_name_id,
          "field_email_id" => $from_email_id,
          "field_subject_id" => $from_subject_id
        ), "ID", $form_id);

        $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&id=".$form_id."&message=Record Created#sections_heading";
        tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
        exit;
      }

    }
	}
	public static function delete() {
	  // Delete record by id.
    tom_delete_record_by_id("a_form_forms", "ID", $_GET["id"]);
    tom_delete_record_by_id("a_form_sections", "form_id", $_GET["id"]);
    tom_delete_record_by_id("a_form_fields", "form_id", $_GET["id"]);
    $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Record Deleted#sections_heading";
    tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
    exit;
	}

	public static function render_admin_a_form_forms_form($instance, $action) { ?>
	  <?php
		  tom_add_form_field($instance, "hidden", "ID *", "ID", "ID", array(), "span", array("class" => "hidden"));
		  tom_add_form_field($instance, "text", "Name *", "form_name", "form_name", array("class" => "text"), "p", array());
		  
		  if ($instance != null && $instance->to_email == "") {
		  	$_POST["to_email"] = get_option("admin_email");
		  }

		  tom_add_form_field($instance, "text", "Send Emails To *", "to_email", "to_email", array("class" => "text"), "p", array());
      tom_add_form_field($instance, "text", "CC Emails To", "to_cc_email", "to_cc_email", array("class" => "text"), "p", array());
      tom_add_form_field($instance, "text", "BCC Emails To", "to_bcc_email", "to_bcc_email", array("class" => "text"), "p", array());
      tom_add_form_field($instance, "text", "Email Subject *", "subject", "subject", array("class" => "text"), "p", array());
      if ($instance != null) {
        $field_options = array("" => "");
        $fields = tom_get_results("a_form_fields", "*", "form_id=".$instance->ID);
        foreach ($fields as $field) {
          $field_options[$field->FID] = $field->field_label;
        }
        tom_add_form_field($instance, "select", "From Name Field", "field_name_id", "field_name_id", array(), "p", array(), $field_options);
        tom_add_form_field($instance, "select", "From Email Field", "field_email_id", "field_email_id", array(), "p", array(), $field_options);
        tom_add_form_field($instance, "select", "Include In Subject Field", "field_subject_id", "field_subject_id", array(), "p", array(), $field_options);

      }

      tom_add_form_field($instance, "checkbox", "Section Names", "show_section_names", "show_section_names", array(), "p", array(), array("1" => "Visible"));
      tom_add_form_field($instance, "radio", "Confirmation Emails", "send_confirmation_email", "send_confirmation_email", array(), "p", array(), array("1" => "Send", "0" => "Don't Send"));
      tom_add_form_field($instance, "text", "Confirmation From Email", "confirmation_from_email", "confirmation_from_email", array("class" => "text"), "p", array(), array());

      tom_add_form_field($instance, "textarea", "Successful Message", "success_message", "success_message", array(), "p", array(), array());
      tom_add_form_field($instance, "text", "Successful Redirect URL", "success_redirect_url", "success_redirect_url", array("class" => "text"), "p", array(), array());
      
      tom_add_form_field($instance, "checkbox", "Tracking", "tracking_enabled", "tracking_enabled", array(), "p", array(), array("1" => "Enabled"));

      tom_add_form_field($instance, "checkbox", "Captcha", "include_captcha", "include_captcha", array(), "p", array(), array("1" => "Include Before Send Button"));

	  ?>
    <input type="hidden" name="action" value="<?php echo($action); ?>" />
	  <p><input type="submit" name="sub_action" value="<?php echo($action); ?>" /> <?php if ($instance != null) { ?><input type="submit" name="sub_action" value="Save and Finish" /><?php } ?></p>

	  <?php if ($action == "Update") { ?>
	    <h2 id="sections_heading">Sections <a class="add-new-h2" href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=new&a_form_page=section&form_id=<?php echo($instance->ID); ?>">Add New</a></h2>
      <?php AForm::render_admin_a_form_fields_form($instance, $action);
    }
	}

  // Upload file.
  public static function upload_file($field_name, $extensions_allowed) {
    $filedest = "";
    $uploadfiles = $_FILES[$field_name];

    if (is_array($uploadfiles)) {

      foreach ($uploadfiles['name'] as $key => $value) {

        // look only for uploded files
        if ($uploadfiles['error'][$key] == 0) {

          $filetmp = $uploadfiles['tmp_name'][$key];

          //clean filename and extract extension
          $filename = $uploadfiles['name'][$key];

          // get file info
          // @fixme: wp checks the file extension....
          $filetype = wp_check_filetype( basename( $filename ), null );
          $filetitle = preg_replace('/\.[^.]+$/', '', basename( $filename ) );
          $filename = $filetitle . '.' . $filetype['ext'];
          $upload_dir = wp_upload_dir();
          // echo $upload_dir;
          /**
           * Check if the filename already exist in the directory and rename the
           * file if necessary
           */
          $i = 0;
          while ( file_exists( $upload_dir['path'] .'/' . $filename ) ) {
            $filename = $filetitle . '_' . $i . '.' . $filetype['ext'];
            $i++;
          }
          $filedest = $upload_dir['path']. '/' . $filename;

          /**
           * Check write permissions
           */
          if ( !is_writeable( $upload_dir['path'] ) ) {
            throw new Exception(' Unable to write to directory %s. Is this directory writable by the server? ');
            return;
          }

          // Check if extension allowed
          if ($extensions_allowed == "" || preg_match("/\.".$filetype['ext']."/", $extensions_allowed)) {
            /**
             * Extension allowed
             * Save temporary file to uploads dir
             */
            if ( !@move_uploaded_file($filetmp, $filedest) ){
              throw new Exception(" Error, the file $filetmp could not moved to : $filedest. ");
              continue;
            }
          } else {
            $extensions_allowed = preg_replace('/(\s)+/',' ', $extensions_allowed);
            $extensions_allowed = preg_replace('/(\s)+$/', '', $extensions_allowed);
            $extensions_allowed = preg_replace('/(\s)/', ', ', $extensions_allowed);
            $extensions_allowed = preg_replace('/ \.([a-z|A-Z])*$/', ' and $0', $extensions_allowed);
            $extensions_allowed = preg_replace('/,(\s)+and/', ' and', $extensions_allowed);
            if (preg_match("/ and /", $extensions_allowed)) {
              throw new Exception(" these are the only file extensions acceptable: ".trim($extensions_allowed).". ");  
            } else {
              throw new Exception(" ".trim($extensions_allowed)." is the only acceptable file type. ");
            }
            
          }
        }
      }   
    }

    return $filedest;
  }


  public static function render_admin_a_form_fields_form($instance, $action) { 
    if ($instance != null) { ?>
      <h2><?php echo $instance->form_name; ?></h2>
    <?php } ?>
    <input type="hidden" name="a_form_page" value="fields" />
    <?php
    tom_add_form_field($instance, "hidden", "ID", "ID", "ID", array(), "span", array("class" => "hidden"));
    ?>
    <ul id="fields_row_clone">
      <li class="shiftable">
        <?php
          AFormFields::render_admin_a_form_fields_row(null, "-1");
        ?>
      </li>
    </ul>
    <ul id="fields_sortable">
      <?php
        $sections = tom_get_results("a_form_sections", "*", "form_id=".$instance->ID, $order_array = array("section_order ASC"), $limit = "");
        $index = 0;
        foreach ($sections as $section) { ?>
          <li class='shiftable section-heading' id="section_id_<?php echo($section->ID); ?>">
            <h3><?php echo($section->section_name); ?>
              <a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&a_form_page=section&id=<?php echo($section->ID); ?>">Edit</a>
            </h3>
            <a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=delete&a_form_page=section&id=<?php echo($section->ID); ?>" class="delete">Delete</a>
          </li>
          <?php
          $fields = tom_get_results("a_form_fields", "*", "section_id=".$section->ID, $order_array = array("field_order ASC"), $limit = "");
          foreach ($fields as $field) { ?>
            <li id="<?php echo($field->FID); ?>" class="shiftable">
              <?php
                AFormFields::render_admin_a_form_fields_row($field, $index);
                $index++;
              ?>
            </li>
          <?php }
        } ?>

    </ul>
    <?php if ($instance != null) { ?>
      <p class="actions"><a href='#' id="new_form_row">New Field</a></p>
    <?php } ?>
    <input type="hidden" name="action" value="<?php echo($action); ?>" />
    <p><input type="submit" name="sub_action" value="<?php echo($action); ?>" /> <?php if ($instance != null) { ?><input type="submit" name="sub_action" value="Save and Finish" /><?php } ?></p>
    <?php
  }

}

?>