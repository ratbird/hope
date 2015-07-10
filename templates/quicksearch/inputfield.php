<?
# Lifter010: TODO
?>
<? if ($withButton): ?>
<div class="quicksearch_frame <?= ($extendedLayout === true) ? 'extendedLayout' : ''; ?>" id="<?= $id ?>_frame">
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
        <input type="submit" value="Suche starten" name="<?= $search_button_name; ?>"></input>
    <? endif; ?>
</div>
<? endif; ?>
<script type="text/javascript" language="javascript">
    //Die Autovervollst�ndigen-Funktion aktivieren:
    jQuery(function () {
        STUDIP.QuickSearch.autocomplete("<?= $id ?>",
            "<?= URLHelper::getURL("dispatch.php/quicksearch/response/".$query_id) ?>",
            <?= $jsfunction ? $jsfunction : "null" ?>,
            <?= $autocomplete_disabled ? "true" : "false" ?>
            );
    });
</script>
