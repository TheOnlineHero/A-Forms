<?php
/*
Plugin Name: A Forms
Plugin URI: http://wordpress.org/extend/plugins/a-forms/
Description: Adds a contact form to your wordpress site.

Installation:

1) Install WordPress 3.5.2 or higher

2) Download the following file:

http://downloads.wordpress.org/plugin/a-forms.zip

3) Login to WordPress admin, click on Plugins / Add New / Upload, then upload the zip file you just downloaded.

4) Activate the plugin.

Version: 1.0
Author: TheOnlineHero - Tom Skroza
License: GPL2
*/

require_once("a-form.php");
require_once("a-form-section.php");
require_once("a-forms-path.php");

define(__DEFAULT_LIMIT__, "10");

function a_forms_activate() {
  global $wpdb;

  $a_form_forms_table = $wpdb->prefix . "a_form_forms";
  $sql = "CREATE TABLE $a_form_forms_table (
    ID mediumint(9) NOT NULL AUTO_INCREMENT, 
    form_name VARCHAR(255) DEFAULT '',
    to_email VARCHAR(255) DEFAULT '',
    to_cc_email VARCHAR(255) DEFAULT '',
    to_bcc_email VARCHAR(255) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    show_section_names tinyint(4) NOT NULL DEFAULT 1,
    field_name_id mediumint(9), 
    field_email_id mediumint(9), 
    field_subject_id mediumint(9), 
    send_confirmation_email tinyint(4) NOT NULL DEFAULT 0,
    confirmation_from_email VARCHAR(255) DEFAULT '',
    success_message longtext DEFAULT '',
    success_redirect_url VARCHAR(255) DEFAULT '',
    include_captcha tinyint(4) NOT NULL DEFAULT 0,
    tracking_enabled tinyint(4) NOT NULL DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY  (ID),
    UNIQUE (form_name)
  )";
  $wpdb->query($sql); 

  $a_form_sections_table = $wpdb->prefix . "a_form_sections";
  $sql = "CREATE TABLE $a_form_sections_table (
    ID mediumint(9) NOT NULL AUTO_INCREMENT, 
    section_name VARCHAR(255) DEFAULT '',
    section_order mediumint(9) NOT NULL DEFAULT 0, 
    form_id mediumint(9) NOT NULL, 
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY  (ID)
  )";
  $wpdb->query($sql); 

  $a_form_fields_table = $wpdb->prefix . "a_form_fields";
  $sql = "CREATE TABLE $a_form_fields_table (
    FID mediumint(9) NOT NULL AUTO_INCREMENT, 
    field_type VARCHAR(255) DEFAULT '',
    field_label VARCHAR(255) DEFAULT '', 
    value_options VARCHAR(255) DEFAULT '',
    field_order mediumint(9) NOT NULL DEFAULT 0, 
    validation VARCHAR(255) DEFAULT '',
    file_ext_allowed VARCHAR(255) DEFAULT '',
    form_id mediumint(9) NOT NULL,
    section_id mediumint(9) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY  (FID)
  )";
  $wpdb->query($sql);

  $a_form_tracks_table = $wpdb->prefix . "a_form_tracks";
  $sql = "CREATE TABLE $a_form_tracks_table (
    ID mediumint(9) NOT NULL AUTO_INCREMENT, 
    content longtext NOT NULL,
    track_type VARCHAR(255) DEFAULT '',
    form_id mediumint(9) NOT NULL,
    referrer_url VARCHAR(255) DEFAULT '',
    fields_array mediumtext DEFAULT '',
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY  (ID)
  )";
  $wpdb->query($sql);

  if (!is_dir(get_template_directory()."/aforms_css")) {
    aform_copy_directory(AFormsPath::normalize(dirname(__FILE__)."/css"), get_template_directory());  
  } else {
    add_option("aform_current_css_file", "default.css");
  }
}
register_activation_hook( __FILE__, 'a_forms_activate' );

//call register settings function
add_action( 'admin_init', 'register_a_forms_settings' );
function register_a_forms_settings() {
  register_setting( 'a-forms-settings-group', 'a_forms_mail_host' );
  register_setting( 'a-forms-settings-group', 'a_forms_smtp_auth' );
  register_setting( 'a-forms-settings-group', 'a_forms_smtp_port' );
  register_setting( 'a-forms-settings-group', 'a_forms_smtp_username' );
  register_setting( 'a-forms-settings-group', 'a_forms_smtp_password' );

  @check_a_forms_dependencies_are_active(
    "A Forms", 
    array(
      "Tom M8te" => array("plugin"=>"tom-m8te/tom-m8te.php", "url" => "http://downloads.wordpress.org/plugin/tom-m8te.zip", "version" => "1.3.4"))
  );
}

add_action('admin_menu', 'register_a_forms_page');
function register_a_forms_page() {
  add_menu_page('A Forms', 'A Forms', 'manage_options', 'a-forms/a-forms.php', 'a_form_initial_page');
  add_submenu_page('a-forms/a-forms.php', 'Settings', 'Settings', 'manage_options', 'a-forms/a-forms-settings.php', 'a_form_settings_page');
  add_submenu_page('a-forms/a-forms.php', 'Tracking', 'Tracking', 'manage_options', 'a-forms/a-forms-tracking.php', 'a_form_tracking_page');
  add_submenu_page('a-forms/a-forms.php', 'Styling', 'Styling', 'update_themes', 'a-forms/a-forms-styling.php');
}

add_action('wp_ajax_aform_css_file_selector', 'aform_css_file_selector');
function aform_css_file_selector() {
  update_option("aform_current_css_file", $_POST["css_file_selection"]);
  echo(@file_get_contents(AFormsPath::normalize(dirname(__FILE__)."../../../themes/".str_replace(" ", "", strtolower(get_current_theme()))."/aforms_css/".$_POST["css_file_selection"])));
  die();  
}

function a_form_initial_page() {
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui-sortable');

  wp_register_script("a-forms", plugins_url("/js/application.js", __FILE__));
  wp_enqueue_script("a-forms");

  wp_localize_script( 'a-forms', 'AFormsAjax', array(
    "base_url" => get_option('siteurl')."/wp-admin/admin.php?page=a-forms/a-forms.php",
    "sort_section_url" => get_option('siteurl')."/wp-admin/admin.php?page=a-forms/a-forms.php&a_form_page=section_section_sort",
    "sort_field_url" => get_option('siteurl')."/wp-admin/admin.php?page=a-forms/a-forms.php&a_form_page=section_field_sort",
    "create_field_url" => get_option('siteurl')."/wp-admin/admin.php?page=a-forms/a-forms.php&a_form_page=create_field"
  ));


  wp_register_style("a-forms", plugins_url("/css/style.css", __FILE__));
  wp_enqueue_style("a-forms");

  if (tom_get_query_string_value("a_form_page") == "section") {
    a_form_section_page();
  } else if (tom_get_query_string_value("a_form_page") == "section_section_sort") {
    tom_update_record_by_id("a_form_sections", array("section_order" => $_POST["section_order"]), "ID", $_POST["ID"]);
    exit;
  } else if (tom_get_query_string_value("a_form_page") == "section_field_sort") {
    tom_update_record_by_id("a_form_fields", array("field_order" => $_POST["field_order"]), "FID", $_POST["FID"]);
    exit;
  } else if (tom_get_query_string_value("a_form_page") == "create_field") {
    $section = tom_get_row_by_id("a_form_sections", "*", "ID", $_POST["section_id"]);
    tom_insert_record("a_form_fields", array("field_order" => $_POST["field_order"], "section_id" => $_POST["section_id"], "form_id" => $section->form_id));
    global $wpdb;
    echo $wpdb->insert_id;
    exit;
  } else {
    a_form_page();
  }
}

function a_form_page() {
  if (isset($_POST["action"])) {
    if ($_POST["action"] == "Update") {
      AForm::update();
    }
    if ($_POST["action"] == "Create") {
      AForm::create();
    }
  }
  if ($_GET["action"] == "delete") {
    AForm::delete();
  }
  ?>
  
  <div class="wrap a-form">
  <h2>A Forms <a class="add-new-h2" href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=new">Add New Form</a></h2>
  
  <?php

  if (isset($_GET["message"]) && $_GET["message"] != "") {
    echo("<div class='updated below-h2'><p>".$_GET["message"]."</p></div>");
  }

  if (isset($_GET["action"]) && $_GET["action"] != "delete") {
    if ($_GET["action"] == "edit") {
      // Display Edit Page
      $a_form = tom_get_row_by_id("a_form_forms", "*", "ID", $_GET["id"]); ?>
      <div class="postbox " style="display: block; ">
      <div class="inside">
        <form action="" method="post">
          <?php AForm::render_admin_a_form_forms_form($a_form, "Update"); ?>
        </form>
      </div>
      </div>
      </div>
    <?php }

    if ($_GET["action"] == "new") {
      // Display New Page
      ?>
      <div class="postbox " style="display: block; ">
      <div class="inside">
        <form action="" method="post">
          <?php 

          if (!isset($_POST["to_email"])) {
            $_POST["to_email"] = get_option("admin_email");
          }
          if (!isset($_POST["show_section_names"])) {
            $_POST["show_section_names"] = "1";
          }
          if (!isset($_POST["tracking_enabled"])) {
            $_POST["tracking_enabled"] = "1";
          }

          AForm::render_admin_a_form_forms_form(null, "Create"); ?>
        </form>
      </div>
      </div>
      </div>
    <?php }

  } else { ?>
    <div class="postbox " style="display: block; ">
    <div class="inside">
      <?php

      $forms = tom_get_results("a_form_forms", "*", "");
      if (count($forms) == 0) {
        $url = get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php&action=new";
        tom_javascript_redirect_to($url, "<p>Start by creating a form.</p>");
      } else {
        tom_generate_datatable("a_form_forms", array("ID", "form_name", "to_email", "tracking_enabled"), "ID", "", array("form_name ASC"), __DEFAULT_LIMIT__, get_option("siteurl")."/wp-admin/admin.php?page=a-forms/a-forms.php", false, true, true, true, true);   
      }
      ?>
    </div>
    </div>
    </div>
  <?php
  }
}

function a_form_section_page() {
  if (isset($_POST["action"])) {
    if ($_POST["action"] == "Update") {
      AFormSection::update();
    }
    if ($_POST["action"] == "Create") {
      AFormSection::create();
    }
  }
  if ($_GET["action"] == "delete") {
    AFormSection::delete();
  }

  ?>
  
  <div class="wrap a-form">
  <h2>A Forms <a class="add-new-h2" href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=a-forms/a-forms.php&action=new">Add New Form</a></h2>
  
  <?php

  if (isset($_GET["message"]) && $_GET["message"] != "") {
    echo("<div class='updated below-h2'><p>".$_GET["message"]."</p></div>");
  }

  if (isset($_GET["action"]) && $_GET["action"] != "delete") {
    if ($_GET["action"] == "edit") {
      // Display Edit Page
      $a_form = tom_get_row_by_id("a_form_sections", "*", "ID", $_GET["id"]); ?>
      <div class="postbox " style="display: block; ">
      <div class="inside">
        <form action="" method="post">
          <?php AFormSection::render_admin_a_form_sections_form($a_form, "Update"); ?>
        </form>
      </div>
      </div>
      </div>
    <?php }

    if ($_GET["action"] == "new") {
      // Display New Page
      ?>
      <div class="postbox " style="display: block; ">
      <div class="inside">
        <form action="" method="post">
          <?php AFormSection::render_admin_a_form_sections_form(null, "Create"); ?>
        </form>
      </div>
      </div>
      </div>
    <?php }
  }
}

function a_form_settings_page() { ?>
  <div class="wrap">
  <h2>Settings</h2>
  <div class="postbox " style="display: block; ">
  <div class="inside">
  <form method="post" action="options.php">
    <?php settings_fields( 'a-forms-settings-group' ); ?>
    <h3>SMTP Settings</h3>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">
            <label for="a_forms_mail_host">Mail Host:</label>
          </th>
          <td>
            <input type="text" id="a_forms_mail_host" name="a_forms_mail_host" value="<?php echo get_option('a_forms_mail_host'); ?>" />
            <span class="example">e.g: mail.yourdomain.com</span>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">
            <label for="a_forms_smtp_auth">Enable SMTP Authentication:</label>
          </th>
          <td>
            <input type="hidden" name="a_forms_smtp_auth" value="0">
            <input type="checkbox" id="a_forms_smtp_auth" name="a_forms_smtp_auth" value="1" <?php if (get_option('a_forms_smtp_auth')) {echo "checked";} ?> />
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">
            <label for="a_forms_smtp_port">SMTP Port:</label>
          </th>
          <td>
            <input type="text" id="a_forms_smtp_port" name="a_forms_smtp_port" value="<?php echo get_option('a_forms_smtp_port'); ?>" />
            <span class="example">e.g: 26</span>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">
            <label for="a_forms_smtp_username">SMTP Username:</label>
          </th>
          <td>
            <input type="text" id="a_forms_smtp_username" name="a_forms_smtp_username" value="<?php echo get_option('a_forms_smtp_username'); ?>" />
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">
            <label for="a_forms_smtp_password">SMTP Password:</label>
          </th>
          <td>
            <input type="password" id="a_forms_smtp_password" name="a_forms_smtp_password" value="<?php echo get_option('a_forms_smtp_password'); ?>" />
          </td>
        </tr>
    
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" name="Submit" value="Update Settings">
    </p>

  </form>
  </div>
  </div>
  </div>
<?php
}

function a_form_tracking_page() { 
  wp_enqueue_script('jquery');
  wp_register_script("a-forms", plugins_url("/js/application.js", __FILE__));
  wp_enqueue_script('jquery-ui-sortable');
  wp_enqueue_script("a-forms");
  wp_register_style("a-forms", plugins_url("/css/style.css", __FILE__));
  wp_enqueue_style("a-forms");

  ?>
  <div class="wrap">
  <h2>Tracking</h2>
  <?php 
    if (!isset($_GET["action"])) {
      tom_generate_datatable("a_form_forms", array("ID", "form_name"), "ID", "", array(), "30", "?page=a-forms/a-forms-tracking.php", true, false, false, false, true, "Y-m-d", array()); 
    } else if ($_GET["action"] == "show") {
      $limit_clause = "10";
      $total_tracks = count(tom_get_results("a_form_tracks", "*", "form_id=".$_GET["id"], array(), ""));
      $page_no = 0;
      if (isset($_GET["a_form_tracks_page"])) {
        $page_no = $_GET["a_form_tracks_page"];
      }
      $offset = $page_no * $limit_clause;
      $tracks = tom_get_results("a_form_tracks", "*", "form_id=".$_GET["id"], array(), "$limit_clause OFFSET $offset");
      $fields = tom_get_results("a_form_fields", "*", "form_id=".$_GET["id"], array());
      
      if ($total_tracks > 0) {
        tom_generate_datatable_pagination("a_form_tracks", $total_tracks, $limit_clause, $_GET["a_form_tracks_page"], "?page=a-forms/a-forms-tracking.php&action=show&id=".$_GET["id"], "ASC", "top");
      ?>
        <table>
          <thead>
            <tr>
              <?php           
                foreach ($fields as $field) {
                  echo("<th>".$field->field_label."</th>");
                }
              ?>
              <th>Referrer URL</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tracks as $track) {
              $fields_array = unserialize($track->fields_array);
              echo("<tr>");
              foreach ($fields as $field) {
                $content = $fields_array[str_replace(" ", "_", strtolower($field->field_label))];
                echo("<td>");
                if ($content != "" && $field->field_type == "file") {
                  echo("<a href='".get_option("siteurl")."/wp-content/plugins/tom-m8te/tom-download-file.php?file=".$content."'>download</a>");
                } else {
                  echo(preg_replace("/, $/", "", $content));
                }
                echo("</td>");
              }
              echo("<td>".$track->referrer_url."</td>");
              echo("</tr>");
            }?>
          </tbody>
        </table>
        <?php
        tom_generate_datatable_pagination("a_form_tracks", $total_tracks, $limit_clause, $_GET["a_form_tracks_page"], "?page=a-forms/a-forms-tracking.php&action=show&id=".$_GET["id"], "ASC", "bottom");
      } else {
        echo("<p>No records found!</p>");
      }
    }
    
  ?>
  </div>
  <?php
}

add_shortcode( 'a-form', 'a_form_shortcode' );

function a_form_shortcode($atts) {
  $return_content = "";
  $email_content = "";
  $validation_array = array();
  $from_email = get_option("admin_email");
  $current_datetime = gmdate( 'Y-m-d H:i:s');

  $form = tom_get_row_by_id("a_form_forms", "*", "ID", $atts["id"]);
  $sections = tom_get_results("a_form_sections", "*", "form_id='".$atts["id"]."'", array("section_order ASC"));

  $form_valid = true;
  $section_index = 0;

  if (isset($_POST["send_a_form_section"])) {
    $section_index = $_POST["send_a_form_section"];
  } else {
    $section_index = 0;
  }

  // Get this section.
  $section = $sections[$section_index];

  // Add validation for this section only.
  $fields = tom_get_results("a_form_fields", "*", "section_id='".$section->ID."'");
  foreach ($fields as $field) {
    $field_name = str_replace(" ", "_", strtolower($field->field_label));
    $validation_array["a_form_".$field_name] = $field->validation;
  }

  // User submits a form action.
  if (isset($_POST["send_a_form"])) {
    $captcha_valid = true;
    $form_valid = tom_validate_form($validation_array);

    $field_values = array();
    $attachment_urls = array();

    if (isset($_POST["a_form_attachment_urls"]) && $_POST["a_form_attachment_urls"] != "") {
      $attachment_urls = explode("::", $_POST["a_form_attachment_urls"]);
    }

    // Construct email content.
    $all_fields = tom_get_results("a_form_fields", "*", "form_id='".$atts["id"]."'");
    foreach ($all_fields as $field) {
      $field_name = str_replace(" ", "_", strtolower($field->field_label));

      if ($field->field_type == "checkbox") {
        $i = 0;
        $email_content .= $field->field_label.": ";
        $answers = "";
        foreach (explode(",", $field->value_options) as $key) {
          if ($_POST["a_form_".$field_name."_".$i] != "") {
            $answers .= $_POST["a_form_".$field_name."_".$i]. ", ";
          }
          $i++;
        }
        $email_content .= preg_replace("/, $/", "", $answers);
        $email_content .= "\n\n";
        $field_values[$field_name] = $answers;
      } else if ($field->field_type == "file") {
        // Upload file.

        try {
          $filedst = AForm::upload_file("a_form_".$field_name, $field->file_ext_allowed);
          array_push($attachment_urls, "a_form_".$field_name."=>".$filedst);
        } catch(Exception $ex) {
          $form_valid = false;
          $_SESSION["a_form_".$field_name."_error"] = $ex->getMessage();
        }
        
        if ($filedst != "") {
          $field_values[$field_name] = $filedst;
        } else {
          if ($_POST["a_form_attachment_urls"] != "") {
            $records = explode("::", $_POST["a_form_attachment_urls"]);
            foreach ($records as $record) {
              $key_value = explode("=>", $record);
              if ($key_value[0] == "a_form_".$field_name && $key_value[1] != "") {
                $field_values[$field_name] = $key_value[1];
              }
            }
          }
        }
        
      } else {
        $email_content .= $field->field_label.": ".$_POST["a_form_".$field_name]."\n\n";
        $field_values[$field_name] = $_POST["a_form_".$field_name];
      }
      
    }


    if ($_POST["action"] == "Send" && isset($_POST["a_form_captcha"]) && $form->include_captcha) {
      $captcha_valid = tom_check_captcha("a_form_captcha");
    }
    
    if ($form_valid && $captcha_valid) {
      if ($_POST["action"] == "Send") {
        $subject = $form->subject;
        $from_name = "";
        if ($form->field_name_id != "") {
          $row = tom_get_row_by_id("a_form_fields", "*", "FID", $form->field_name_id);
          $from_name = $_POST["a_form_".str_replace(" ", "_", strtolower($row->field_label))];
        }
        if ($form->field_email_id != "") {
          $row = tom_get_row_by_id("a_form_fields", "*", "FID", $form->field_email_id);
          $from_email = $_POST["a_form_".str_replace(" ", "_", strtolower($row->field_label))];
        }
        if ($form->field_subject_id != "") {
          $row = tom_get_row_by_id("a_form_fields", "*", "FID", $form->field_subject_id);
          if (isset($_POST["a_form_".str_replace(" ", "_", strtolower($row->field_label))])) {
            $subject .= " - ".$_POST["a_form_".str_replace(" ", "_", strtolower($row->field_label))];
          }
        }


        // Send Email.
        $cc_emails = $form->to_cc_email;
        if ($from_email != "" && $form->send_confirmation_email) {
          if ($cc_emails == "") {
            $cc_emails .= $from_email;
          } else {
            $cc_emails .= ", ".$from_email;
          }
        }

        // Rip up $attachment_urls so we're left with only the paths to the files uploaded.
        $smtp_attachment_urls = array();
        foreach ($attachment_urls as $attach_url) {
          $temp = explode("=>", $attach_url);
          array_push($smtp_attachment_urls, $temp[1]);
        }

        $mail_message = tom_send_email(false, $form->to_email, $cc_emails, $form->to_bcc_email, $from_email, $from_name, $subject, $email_content, "", $smtp_attachment_urls, get_option("a_forms_smtp_auth"), get_option("a_forms_mail_host"), get_option("a_forms_smtp_port"), get_option("a_forms_smtp_username"), get_option("a_forms_smtp_password"));        
        
        if ($mail_message == "<div class='success'>Message sent!</div>") {

          if ($form->success_message != "") {
            $mail_message = "<div class='success'>".$form->success_message."</div>";
          }

          if ($form->tracking_enabled) {
            tom_insert_record("a_form_tracks", array("created_at" => $current_datetime, "form_id" => $_POST["send_a_form"], "content" => $email_content, "track_type" => "Successful Email", "referrer_url" => $_SERVER["HTTP_REFERER"], "fields_array" => serialize($field_values)));  
          }        

          if ($form->success_redirect_url != "") {
            tom_javascript_redirect_to($form->success_redirect_url, "<p>Please <a href='$url'>Click Next</a> to continue.</p>");
          }

        } else {
          if ($form->tracking_enabled) {
            tom_insert_record("a_form_tracks", array("created_at" => $current_datetime, "form_id" => $_POST["send_a_form"], "content" => "Error Message: ".$mail_message.".\n\nContent: ".$email_content, "track_type" => "Failed Email", "referrer_url" => $_SERVER["HTTP_REFERER"], "fields_array" => serialize($field_values)));
          }
        }          

        $return_content .= $mail_message;
      }
    } else {
      $form_valid = false;
    }

  }
  
  $return_content .= "<form action='' id='".str_replace(" ", "_", strtolower($form->form_name))."' method='post' class='a-form' enctype='multipart/form-data'>";
  
  // Get next section
  if ($_POST["action"] == "Next") {
    if ($form_valid) {
      $section_index++;
    } 
  }

  // Get previous section.
  if ($_POST["action"] == "Back") {
    $section_index--;
  }

  $section = $sections[$section_index];

  // Navigate through all the other sections and make all fields hidden.
  $hidden_fields = tom_get_results("a_form_fields", "*", "form_id = '".$atts["id"]."' AND section_id <> '".$section->ID."'");
  foreach ($hidden_fields as $field) {
    $field_name = str_replace(" ", "_", strtolower($field->field_label));
    ob_start();
    
    if ($field->field_type == "checkbox") {
      $i = 0;
      foreach (explode(",", $field->value_options) as $key) {
        tom_add_form_field(null, "hidden", $field->field_label, "a_form_".$field_name."_".$i, "a_form_".$field_name."_".$i, array(), "p", array(), array());  
        $i++;
      }
    } else {

      tom_add_form_field(null, "hidden", $field->field_label, "a_form_".$field_name, "a_form_".$field_name, array(), "p", array(), array());
      
    }
    
    $return_content .= ob_get_contents();
    ob_end_clean();
  }

  $input_attachment_urls = "";

  if (count($attachment_urls) > 0) {
    $attachment_urls = array_filter( $attachment_urls, 'strlen' );
    $input_attachment_urls = implode("::", str_replace("\\\\", '\\', $attachment_urls));
  }

  $return_content .= "<input type='hidden' name='a_form_attachment_urls' value='".$input_attachment_urls."' />";

  $fields = tom_get_results("a_form_fields", "*", "section_id='".$section->ID."'", array("field_order ASC"));

  // Render form fields.
  if ($form->show_section_names) {
    $return_content .= "<h2>".$section->section_name."</h2>";
  }

  foreach ($fields as $field) {
    $field_name = str_replace(" ", "_", strtolower($field->field_label));
    $value_options = array();
    if ($field->value_options != "") {
      $options = explode(",", $field->value_options);
      foreach($options as $option_with_label) {
        $temp_array = explode(":", $option_with_label);
        $option = $temp_array[1];
        $value = $temp_array[0];
        if ($option == "") {
          $option = $value;
        }
        $value_options[$option] = $value;
      }
    }
    $field_label = $field->field_label;
    if (preg_match("/required/",$validation_array["a_form_".$field_name])) {
      $field_label .= "<abbr title='required'>*</abbr>";
    }

    ob_start();
    if ($field->field_type == "file" && $field->file_ext_allowed != "") {
      echo("<div>");
    } 
    tom_add_form_field(null, $field->field_type, $field_label, "a_form_".$field_name, "a_form_".$field_name, array("class" => $field->field_type), "div", array(), $value_options);
    if ($field->field_type == "file" && $field->file_ext_allowed != "") {
      $extensions_allowed = $field->file_ext_allowed;
      $extensions_allowed = preg_replace('/(\s)+/',' ', $extensions_allowed);
      $extensions_allowed = preg_replace('/(\s)+$/', '', $extensions_allowed);
      $extensions_allowed = preg_replace('/(\s)/', ', ', $extensions_allowed);
      $extensions_allowed = preg_replace('/ \.([a-z|A-Z])*$/', ' and $0', $extensions_allowed);
      $extensions_allowed = preg_replace('/,(\s)+and/', ' and', $extensions_allowed);
      echo("<span class='file-ext-allowed'>Can only accept: ".$extensions_allowed."</span>");
      echo("</div>");
    }

    $return_content .= ob_get_contents();
    ob_end_clean();
  }
  
  $return_content .= "<fieldset class='submit'><div><input type='hidden' name='send_a_form_section' value='".$section_index."' /><input type='hidden' name='send_a_form' value='".$atts["id"]."' />";

  // Add action buttons
  // Check if more then one section
  if (count($sections) > 1) {
    // There is more then one section.

    if (($section_index+1) == count($sections)) {
      // Looking at the last section.
      $return_content .= render_a_form_submit_html($form);
    } else {
      // Not looking at the last section.
      $return_content .= "<input type='submit' name='action' value='Next' class='next'/>";
    }

  } else {
    // Only one section.
    $return_content .= render_a_form_submit_html($form);
  }

  // Check which section your currently looking at.
  if ($section_index > 0) {
    // Not looking at the first section.
    $return_content .= "<input type='submit' name='action' value='Back' class='prev'/>";
  }

  return $return_content."</fieldset></div></form>";
}

function render_a_form_submit_html($form) {
  $return_content = "";
  if ($form->include_captcha) {
    ob_start();
    tom_add_form_field(null, "captcha", "Captcha", "a_form_captcha", "a_form_captcha", array(), "div", array("class" => "captcha"));
    $return_content .= ob_get_contents();
    ob_end_clean();
  }
  $return_content .= "<input type='submit' name='action' value='Send' class='send'/>";
  return $return_content;
}

add_action('wp_head', 'add_a_forms_js_and_css');
function add_a_forms_js_and_css() { 
  wp_enqueue_script('jquery');

  wp_register_script("a-forms", plugins_url("/js/application.js", __FILE__));
  wp_enqueue_script("a-forms");

  wp_register_style("a-forms", get_option("siteurl")."/wp-content/themes/".str_replace(" ", "", strtolower(get_current_theme())).'/aforms_css/'.get_option("aform_current_css_file"));
  wp_enqueue_style("a-forms");
} 

function check_a_forms_dependencies_are_active($plugin_name, $dependencies) {
  $msg_content = "<div class='updated'><p>Sorry for the confusion but you must install and activate ";
  $plugins_array = array();
  $upgrades_array = array();
  define('PLUGINPATH', ABSPATH.'wp-content/plugins');
  foreach ($dependencies as $key => $value) {
    $plugin = get_plugin_data(PLUGINPATH."/".$value["plugin"],true,true);
    $url = $value["url"];
    if (!is_plugin_active($value["plugin"])) {
      array_push($plugins_array, $key);
    } else {
      if (isset($value["version"]) && str_replace(".", "", $plugin["Version"]) < str_replace(".", "", $value["version"])) {
        array_push($upgrades_array, $key);
      }
    }
  }
  $msg_content .= implode(", ", $plugins_array) . " before you can use $plugin_name. Please go to Plugins/Add New and search/install the following plugin(s): ";
  $download_plugins_array = array();
  foreach ($dependencies as $key => $value) {
    if (!is_plugin_active($value["plugin"])) {
      $url = $value["url"];
      array_push($download_plugins_array, $key);
    }
  }
  $msg_content .= implode(", ", $download_plugins_array)."</p></div>";
  if (count($plugins_array) > 0) {
    deactivate_plugins( __FILE__, true);
    echo($msg_content);
  } 

  if (count($upgrades_array) > 0) {
    deactivate_plugins( __FILE__,true);
    echo "<div class='updated'><p>$plugin_name requires the following plugins to be updated: ".implode(", ", $upgrades_array).".</p></div>";
  }
}

// Copy directory to another location.
function aform_copy_directory($src,$dst) { 
    $dir = opendir($src); 
    try{
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    aform_copy_directory($src . '/' . $file,$dst . '/' . $file); 
                } else { 
                    copy($src . '/' . $file,$dst . '/' . $file);
                } 
            }   
        }
        closedir($dir); 
    } catch(Exception $ex) {
        return false;
    }
    return true;
}

?>