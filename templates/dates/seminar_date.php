<div id="termin_item_<?= $termin_item['termin_id'] ?>" class="news_item" role="article">
<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
    <tr>
        <? printhead(0, 0, $link, ($termin_item['open']) ? 'open' : 'close', $new, $icon, $titel, $zusatz, $termin_item['chdate']); ?>
    
    </tr>
</table>
</div>
<div id="termin_item_<?= $termin_item['termin_id'] ?>_content" <? if ((!$termin_item['open'])): ?> style="display:none;"<? endif; ?>>
        <?= show_termin_item_content($termin_item, $new, $range_id, $show_admin) ?>
   
</div>
