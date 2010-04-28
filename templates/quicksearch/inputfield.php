<? if ($withButton) : ?>
<div style="width: 233px; background-color: #ffffff; border: 1px #999999 solid; display:inline-block">
<? $input_style = " style=\"width: 210px; background-color:#ffffff; border: 0px;\""; ?>
<? endif ?>
<? if ($inputStyle) {
	       $input_style = " style=\"".$inputStyle."\"";
        }
        if ($beschriftung) {
            $clear_input = " onFocus=\"if (this.value == '$beschriftung'){this.value = ''; $(this).css('color', '');}\" " .
                "onBlur=\"if (this.value == ''){this.value = '$beschriftung';$(this).css('color', '".$descriptionColor."');}\"";
        } ?>
            <input type=hidden id="<?= $name ?>_realvalue" name="<?= $name ?>" value="<?= $defaultID ?>">
            <input<?= $input_style.($inputClass ? " class=\"".$inputClass."\"" : "") 
                ?> id="<?= $name ?>"<?= ($clear_input ? $clear_input : "") ?> type=text name="<?= 
                    $name ?>_parameter" value="<?= $defaultName ?>">
<? if ($withButton) : ?>
            <input style="vertical-align:middle" type="image" src="<?= Assets::image_path("suche2.gif")?>">
        </div>
<? endif ?>
        <script type="text/javascript" language="javascript">
            //Die Autovervollständigen-Funktion aktivieren:
            //dispatch.php/quicksearch/response/<?= $query_id ?>?searchkey=test
            
            STUDIP.QuickSearch.autocomplete("<?= $name ?>", 
                "<?= URLHelper::getURL("dispatch.php/quicksearch/response/".$query_id) ?>",
                "<?= JSfunction ? htmlReady($JSfunction) : "" ?>");
<? if ($beschriftung && !$defaultID) : ?>
            $("#<?= $name ?>").attr("value", "<?= $beschriftung ?>");
            $("#<?= $name ?>").css("color", "<?= $descriptionColor ?>");
<? endif ?>
        </script>