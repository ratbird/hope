<? if ($withButton) : ?>
<div class="quicksearch_frame" style="width: <?= $box_width ?>px;">
<? $input_style = " style=\"width: ".($box_width-23)."px;\""; ?>
    <? if ($box_align === "left") : ?>
            <input class="text-bottom" type="image" src="<?= Assets::image_path("icons/16/blue/search.png")?>">
    <? endif ?>
<? endif ?>
<? 
if ($inputStyle) {
    $input_style = " style=\"".$inputStyle."\"";
}
?>
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
            jQuery(function () {
                STUDIP.QuickSearch.autocomplete("<?= $id ?>",
                    "<?= URLHelper::getURL("dispatch.php/quicksearch/response/".$query_id) ?>",
                    <?= $jsfunction ? htmlReady($jsfunction) : "null" ?>,
                    <? if ($beschriftung && !$defaultID) : ?>
                    '<?= $beschriftung ?>');
                    <? else : ?>
                    null);
                    <? endif ?>
            });
        </script>
