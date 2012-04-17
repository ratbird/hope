<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
    <tr>
        <? printhead(0, 0, $link, $news_item['open'] ? 'open' : 'close', $tempnew, $icon, $titel, $zusatz, $news_item['date']) ?>
    </tr>
</table>
<div id="news_item_<?= $news_item['news_id'] ?>_content" <? if (!$news_item['open']): ?> style="display:none;"<? endif; ?>>
<? if ($news_item['open']): ?>
    <?= show_news_item_content($news_item, $cmd_data, $show_admin, $admin_link) ?>
<? endif; ?>
</div>


    