<?php
//ganz normales Suchfeld, vermutlich ist JavaScript aktiviert.
if ($withButton) {
	print "<div style=\"width: 233px; background-color: #ffffff; " .
	   "border: 1px #999999 solid;\">";
	$input_style = " style=\"width: 210px; background-color:#ffffff; border: 0px;\"";
}
if ($inputStyle) {
	$input_style = " style=\"".$inputStyle."\"";
}
if ($beschriftung) {
	$clear_input = " onFocus=\"if (this.value == '$beschriftung'){this.value = ''; $(this).style.color = ''}\" " .
		"onBlur=\"if (this.value == ''){this.value = '$beschriftung';$(this).style.color = '".$descriptionColor."'}\"";
}
?>
				<input type=hidden id="<?= $name ?>_realvalue" name="<?= $name ?>" value="<?= $defaultID ?>">
				<input<?= $input_style.($inputClass ? " class=\"".$inputClass."\"" : "") 
					?> id="<?= $name ?>"<?= ($clear_input ? $clear_input : "") ?> type=text name="<?= 
						$name ?>_parameter" value="<?= $defaultName ?>"> 
				<?php
if ($withButton) {
	print "<input style=\"vertical-align:middle\" type=\"image\" src=\"".Assets::image_path("suche2.gif")."\">";
	print "</div>";
}
?>
				<div id="<?= $name ?>_choices" class="autocomplete"></div>
				<script type="text/javascript" language="javascript">
				//Die Autovervollständigen-Funktion aktivieren:
				//dispatch.php/quicksearch/response/<?= $query_id ?>?searchkey=test

				//<?= str_replace('"', "", $_SERVER['REQUEST_URI']) ?>
				
				/*
				new Ajax.Autocompleter("<?= $name ?>", "<?= $name ?>_choices", "<?= URLHelper::getLink("dispatch.php/quicksearch/response/".$query_id) ?>", { 
					paramName: "searchkey",
					afterUpdateElement : function (text, li) {
                        $('<?= $name ?>_realvalue').value = li.id;
                        <?php
		                if ($JSfunction) {
		                    print htmlReady($JSfunction)."(li.id, text.value);\n";
		                }
		                ?>
					},
					callback: function(inputtext, querystring) {
						//This has to be set, so that the hidden real input field is 
						//cleared when the user begins typing and not only when 
						//the next ajax-script is responding. 
						querystring += "&form_data=" 
                            + encodeURIComponent(
                                JSON.stringify(
                                  $('<?= $name ?>_realvalue').up("form").serialize(true)
                              ));
                        $('<?= $name ?>_realvalue').value = "";
						//alert("<?= URLHelper::getLink("dispatch.php/quicksearch/response/".$query_id) ?>?" + querystring);
						return querystring;
					},
					frequency: 0.2
				});*/
				<?php
if ($beschriftung && !$defaultID) {
	print '$("#'.$name.'").attr("value", "'.$beschriftung.'");' ."\n\t\t\t\t".
	'$("#'.$name.'").css("color", "'.$descriptionColor.'");'."\n";
}
print "\t\t\t\t</script>";