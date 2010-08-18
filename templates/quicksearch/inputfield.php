<? if ($withButton) : ?>
<div style="width: <?= $box_width ?>px; background-color: #ffffff; border: 1px #999999 solid; display:inline-block">
<? $input_style = " style=\"width: ".($box_width-23)."px; background-color:#ffffff; border: 0px;\""; ?>
    <? if ($box_align === "left") : ?>
            <input class="text-bottom" type="image" src="<?= Assets::image_path("icons/16/blue/search.png")?>">
    <? endif ?>
<? endif ?>
<? if ($inputStyle) {
           $input_style = " style=\"".$inputStyle."\"";
        }
        if ($beschriftung) {
            $clear_input = " onFocus=\"if (this.value == '$beschriftung'){this.value = ''; $(this).css('color', '');}\" " .
                "onBlur=\"if (this.value == ''){this.value = '$beschriftung';$(this).css('color', '".$descriptionColor."');}\"";
        } ?>
            <input type=hidden id="<?= $id ?>_realvalue" name="<?= $name ?>" value="<?= $defaultID ?>">
            <input<?= $input_style.($inputClass ? " class=\"".$inputClass."\"" : "")
                ?> id="<?= $id ?>"<?= ($clear_input ? $clear_input : "") ?> type=text name="<?=
                    $name ?>_parameter" value="<?= $defaultName ?>">
<? if ($withButton) : ?>
    <? if ($box_align !== "left") : ?>
            <input class="text-bottom" type="image" src="<?= Assets::image_path("icons/16/blue/search.png")?>">
    <? endif ?>
        </div>
<? endif ?>
        <script type="text/javascript" language="javascript">
            //Die Autovervollständigen-Funktion aktivieren:
            //dispatch.php/quicksearch/response/<?= $query_id ?>?searchkey=test

            STUDIP.QuickSearch.autocomplete("<?= $id ?>",
                "<?= URLHelper::getURL("dispatch.php/quicksearch/response/".$query_id) ?>",
                <?= $JSfunction ? htmlReady($JSfunction) : "null" ?>);
<? if ($beschriftung && !$defaultID) : ?>
            (function ($) {
                $("#<?= $id ?>").attr("value", "<?= $beschriftung ?>");
                $("#<?= $id ?>").css("color", "<?= $descriptionColor ?>");
            })(jQuery);
<? endif ?>
        </script>
