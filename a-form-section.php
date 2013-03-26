<?php
final class AFormSection {

  public static function array_validation_rules() {
    return array(
      "section_name" => "required",
      "field_type" => "required"
    );
  }

	public static function update() {
    if (tom_validate_form(AFormSection::array_validation_rules())) {
      
      $valid = tom_update_record_by_id("a_form_sections", 
        tom_get_form_query_strings("a_form_sections", array("created_at", "updated_at"), array("updated_at" => gmdate( 'Y-m-d H:i:s'))), "ID", $_POST["ID"]);

      $field_valid = true;
      $fields = $_POST["FID"];
      $index = 0;
      
      foreach ($fields as $field) {
        if ($_POST["FID"][$index] != "") {
          $test_valid = tom_update_record_by_id("a_form_fields", 
            array(
              "field_label" => $_POST["field_label"][$index],
              "field_type" => $_POST["field_type"][$index],
              "validation" => $_POST["validation_0"][$index]." ".$_POST["validation_1"][$index],
              "value_options" => $_POST["value_options"][$index],
              "file_ext_allowed" => tom_get_query_string_value("file_ext_allowed", $index)
            ),
            "FID",
            $_POST["FID"][$index]
          );
          
          if (!$test_valid) {
            // If one of the records fails, then fail the lot.
            $field_valid = false;
          }
        }
        $index++;
      }

      if ($valid && $field_valid) {
        if ($_POST["sub_action"] == "Update") {
          $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Update Complete&a_form_page=section&action=edit&id=".$_POST["ID"];
        } else {
          $form = tom_get_row_by_id("a_form_forms", "*", "ID", $_POST["ID"]);
          $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&id=".$form->ID."&message=Update Complete";
        }
        
        tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
        exit;
      }

    }
	}
	public static function create() {

    if (tom_validate_form(array("section_name" => "required"))) {
      $current_datetime = gmdate( 'Y-m-d H:i:s');
      $valid = tom_insert_record("a_form_sections", 
        tom_get_form_query_strings("a_form_sections", array("ID", "created_at", "updated_at"), array("created_at" => $current_datetime)));

      if ($valid) {
        $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Record Created&action=edit&id=".$_POST["form_id"];
        tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
        exit;
      }
    }
	}
	public static function delete() {
    // Delete record by id.
    $url = "";
    if (isset($_GET["id"])) {
      $section = tom_get_row_by_id("a_form_sections", "*", "ID", $_GET["id"]);
      $form_id = $section->form_id;
      tom_delete_record_by_id("a_form_sections", "ID", $_GET["id"]);
      tom_delete_record_by_id("a_form_fields", "section_id", $_GET["id"]);
      $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&message=Record Deleted&id=".$form_id;
    }
    if (isset($_GET["fid"])) {
      tom_update_record_by_id("a_form_forms", array("field_name_id" => ""), "field_name_id", $_GET["fid"]);
      tom_update_record_by_id("a_form_forms", array("field_email_id" => ""), "field_email_id", $_GET["fid"]);
      tom_update_record_by_id("a_form_forms", array("field_subject_id" => ""), "field_subject_id", $_GET["fid"]);
      tom_delete_record_by_id("a_form_fields", "FID", $_GET["fid"]);
      $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&a_form_page=section&action=edit&message=Record Deleted&id=".$_GET["section_id"];
    }
    tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
    exit;
	}

  public static function render_admin_a_form_fields_row($instance, $index) {
    $placeholder = "";
    if ($instance == null) {
      $placeholder = "ph_";
    }
    tom_add_form_field($instance, "hidden", "FID", $placeholder."FID", $placeholder."FID", array(), "span", array(), array(), $index);  
    tom_add_form_field($instance, "text", "Label", $placeholder."field_label", $placeholder."field_label", array("class" => "text"), "span", array(), array(), $index);  
    tom_add_form_field($instance, "select", "Field Type *", $placeholder."field_type", $placeholder."field_type", array("class" => "field-type text"), "span", array(), array("" => "", "text" => "text", "select" => "select", "textarea" => "textarea", "radio" => "radio", "checkbox" => "checkbox", "file" => "file"), $index);
    ?>
    <ul class="validation-controls">
      <?php tom_add_form_field($instance, "checkbox", "", $placeholder."validation", $placeholder."validation", array(), "li", array(), array("required" => "required", "email" => "email"), $index); ?>
    </ul>

    <?php
    tom_add_form_field($instance, "hidden", "", $placeholder."value_options", $placeholder."value_options", array("class" => "value-options"), "span", array(), array(), $index);  
    ?>
    <div class="value-option-controls">
      <ul>
        <li><strong class="label">Label</strong><strong class="value">Value</strong></li>
      </ul>
      <span class="actions"><a href='#' class='add value-option'>Add</a></span>
    </div>

    <div class="file-ext-controls">
      <ul>
        <li><strong>Restrict File Extension</strong></li>
        <?php tom_add_form_field($instance, "checkbox", "", $placeholder."file_ext_allowed", $placeholder."file_ext_allowed", array(), "li", array(), array(".jpg" => ".jpg", ".png" => ".png", ".pdf" => ".pdf", ".doc" => ".doc", ".txt" => ".txt"), $index); ?>
      </ul>
    </div>

    <a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=delete&a_form_page=section&fid=<?php echo($instance->FID); ?>&section_id=<?php echo($instance->section_id); ?>" class="delete">Delete</a>
    <?php
  }

  public static function render_admin_a_form_sections_form($instance, $action) { 
    if ($instance != null) {
      $form = tom_get_row_by_id("a_form_forms", "*", "ID", $instance->form_id);?>
      <h2><?php echo $form->form_name; ?> <a class="add-new-h2" href="<?php echo(get_option("siteurl")); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&id=<?php echo($instance->form_id); ?>">Edit Form</a></h2>
    <?php } ?>
    <input type="hidden" name="a_form_page" value="section" />
    <?php
    tom_add_form_field($instance, "hidden", "ID", "ID", "ID", array(), "span", array("class" => "hidden"));
    tom_add_form_field($instance, "hidden", "ID", "form_id", "form_id", array(), "span", array("class" => "hidden"));
    tom_add_form_field($instance, "text", "Name *", "section_name", "section_name", array("class" => "text"), "p", array());

    $fields = tom_get_results("a_form_fields", "*", "section_id=".$instance->ID, $order_array = array("field_order ASC"), $limit = "");
    $index = 0;
    ?>
    <ul id="fields_row_clone">
      <li class="shiftable">
        <?php
          AFormSection::render_admin_a_form_fields_row(null, "-1");
        ?>
      </li>
    </ul>
    <ul id="fields_sortable">
      <?php
        foreach ($fields as $field) { ?>
          <li id="<?php echo($field->FID); ?>" class="shiftable">
            <?php
              AFormSection::render_admin_a_form_fields_row($field, $index);
              $index++;
            ?>
          </li>
        <?php }
      ?>
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