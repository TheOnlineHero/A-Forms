<?php
final class AFormSection {

  public static function array_validation_rules() {
    return array(
      "section_name" => "required"
    );
  }

	public static function update() {
    if (tom_validate_form(AFormSection::array_validation_rules())) {
      
      $valid = tom_update_record_by_id("a_form_sections", 
        tom_get_form_query_strings("a_form_sections", array("created_at", "updated_at"), array("updated_at" => gmdate( 'Y-m-d H:i:s'))), "ID", $_POST["ID"]);

      if ($valid) {
        if ($_POST["sub_action"] == "Update") {
          $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Update Complete&a_form_page=section&action=edit&id=".$_POST["ID"]."";
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
      $section_count = count(tom_get_results("a_form_sections", array("ID"), "", array()));
      $valid = tom_insert_record("a_form_sections", 
        tom_get_form_query_strings("a_form_sections", array("ID", "created_at", "updated_at", "section_order"), array("created_at" => $current_datetime, "section_order" => ($section_count * 2))));

      if ($valid) {
        $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&message=Record Created&action=edit&id=".$_POST["form_id"]."";
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
      $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&action=edit&id=".$form_id."&message=Record Deleted";
      
      tom_javascript_redirect_to($url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
    }
    exit;
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
    <input type="hidden" name="action" value="<?php echo($action); ?>" />
    <p><input type="submit" name="sub_action" value="<?php echo($action); ?>" /> <?php if ($instance != null) { ?><input type="submit" name="sub_action" value="Save and Finish" /><?php } ?></p>
    <?php
  }
}

?>