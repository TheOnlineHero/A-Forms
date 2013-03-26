<?php
	wp_enqueue_script('jquery');
	wp_register_script("a-forms", plugins_url("js/application.js", __FILE__));
  wp_enqueue_script("a-forms");

  wp_register_style("a-forms", plugins_url("css/style.css", __FILE__));
  wp_enqueue_style("a-forms");

   wp_localize_script( 'a-forms', 'AFormsAjax', array(
    'ajax_url' => admin_url('admin-ajax.php')
   ));

	$css_content = @file_get_contents(AFormsPath::normalize(dirname(__FILE__)."../../../themes/".str_replace(" ", "", strtolower(get_current_theme()))."/aforms_css/".get_option("aform_current_css_file")));
	if (isset($_POST["css_content"])) {
		$location = AFormsPath::normalize(dirname(__FILE__)."../../../themes/".str_replace(" ", "", strtolower(get_current_theme()))."/aforms_css/".get_option("aform_current_css_file"));
		$css_content = $_POST["css_content"];
		tom_write_to_file($_POST["css_content"], $location);
	}
?>
<div class="wrap a-form">
<h2>A Forms - Styling</h2>
<div class="postbox " style="display: block; ">
<div class="inside">
  <form action="" method="post">
  	<p>
  		<label for="css_file_selection">Select CSS File</label>
  		<select id="css_file_selection" name="css_file_selection">
  			<?php
  			if ($handle = opendir(AFormsPath::normalize(dirname(__FILE__)."../../../themes/".str_replace(" ", "", strtolower(get_current_theme()))."/aforms_css"))) {
			    /* This is the correct way to loop over the directory. */
			    while (false !== ($entry = readdir($handle))) {
			        if (preg_match("/\.css$/", $entry)) {
			        	$selected = "";
			        	if (get_option("aform_current_css_file") == $entry) {
			        		$selected = "selected";
			        	}
			        	echo "<option value='".$entry."' ".$selected.">".$entry."</option>";
			        }
			    }
			    closedir($handle);
				}
  			?>
  		</select>
  	</p>
  	<p><label for="css_content">CSS</label><textarea id="css_content" name="css_content"><?php echo($css_content); ?></textarea></p>
  	<p><input type="submit" value="Update"/></p>
  </form>
</div>
</div>
</div>