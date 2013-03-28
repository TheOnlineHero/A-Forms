<?php
final class AFormFields {

  public static function array_validation_rules() {
    return array(
      "field_type" => "required"
    );
  }

	public static function update() {
    if (tom_validate_form(AFormFields::array_validation_rules())) {
      $field_valid = true;
      $fields = $_POST["FID"];
      $index = 0;
      
      foreach ($fields as $field) {
        if ($_POST["FID"][$index] != "") {
          $test_valid = tom_update_record_by_id("a_form_fields", 
            array(
              "section_id" => $_POST["section_id"][$index],
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
      return $field_valid;
    } 
    return false;
	}

	public static function delete() {
    // Delete record by id.
    $url = "";
    if (isset($_GET["fid"])) {
      tom_update_record_by_id("a_form_forms", array("field_name_id" => ""), "field_name_id", $_GET["fid"]);
      tom_update_record_by_id("a_form_forms", array("field_email_id" => ""), "field_email_id", $_GET["fid"]);
      tom_update_record_by_id("a_form_forms", array("field_subject_id" => ""), "field_subject_id", $_GET["fid"]);
      tom_delete_record_by_id("a_form_fields", "FID", $_GET["fid"]);
      $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&message=Record Deleted&id=".$_GET["form_id"]."";

      tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
    }
    exit;
	}

  public static function render_admin_a_form_fields_row($instance, $index) {
    $placeholder = "";
    if ($instance == null) {
      $placeholder = "ph_";
    }
    tom_add_form_field($instance, "hidden", "FID", $placeholder."FID", $placeholder."FID", array(), "span", array(), array(), $index);  
    tom_add_form_field($instance, "hidden", "Section ID", $placeholder."section_id", $placeholder."section_id", array("class" => "section_id"), "span", array(), array(), $index);  
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

    <a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=delete&a_form_page=fields&fid=<?php echo($instance->FID); ?>&form_id=<?php echo($instance->form_id); ?>" class="delete">Delete</a>
    <?php
  }

  
}

?>