<?
# Lifter010: TODO
?>
<? if ($withButton): ?>
<div class="quicksearch_frame" style="width: <?= $box_width ?>px;">
<? $withAttributes['style'] = "width: ".($box_width-23)."px;"; ?>
    <? if ($box_align === 'left'): ?>
        <?= Assets::input('icons/16/blue/search.png', array('class' => 'text-bottom')) ?>
    <? endif; ?>
<? endif; ?>
    <input type=hidden id="<?= $id ?>_realvalue" name="<?= $name ?>" value="<?= htmlReady($defaultID) ?>">
    <input<?
        foreach ($withAttributes as $attr_name => $attr_value) {
            print ' '.$attr_name.'="'.$attr_value.'"';
        }
        ?> id="<?= $id ?>"<?= $clear_input ?: '' ?> type="text" name="<?=
            $name ?>_parameter" value="<?= htmlReady($defaultName) ?>" placeholder="<?= $beschriftung && !$defaultID ? htmlReady($beschriftung) : '' ?>">
<? if ($withButton): ?>
    <? if ($box_align !== 'left'): ?>
        <?= Assets::input('icons/16/blue/search.png', tooltip2(_('Suche starten')) + array(
            'name' => $search_button_name,
            'class' => 'text-bottom',
        )) ?>
    <? endif; ?>
</div>
<? endif; ?>
<script type="text/javascript" language="javascript">
    //Die Autovervollständigen-Funktion aktivieren:
    jQuery(function () {
        STUDIP.QuickSearch.autocomplete("<?= $id ?>",
            "<?= URLHelper::getURL("dispatch.php/quicksearch/response/".$query_id) ?>",
            <?= $jsfunction ? $jsfunction : "null" ?>,
            <?= $autocomplete_disabled ? "true" : "false" ?>
            );
    });
</script>
