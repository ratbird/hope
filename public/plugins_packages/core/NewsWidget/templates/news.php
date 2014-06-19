<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
 <tr>
        <?php
        printhead(
            0,
            0,
            sprintf(
                '" onclick="NEWSWIDGET.openclose(\'%s\', \'%s\',\'%s\'); return false;"',
                $id,
                $admin_link,
                $widgetId
            ),
            $news_item['open'] ? 'open' : 'close',
            $tempnew,
            $icon,
            $titel,
            $zusatz,
            $news_item['date'],
            TRUE,
            "",
            "age",
            NULL,
            $onclick
        )
        ?>
    </tr>
</table>
<div id="news_item_<?= $news_item['news_id'] . $widgetId ?>_content" <? if (!$news_item['open']): ?> style="display:none;"<? endif; ?> data-url="<?= htmlReady(PluginEngine::getURL($plugin, array(), '<%= action %>')) ?>">
<? if ($news_item['open']): ?>
    <?= PluginEngine::getURL("NewsWidget", array($news_item, $cmd_data, $show_admin, $admin_link),"show_news_item_content") ?>
<? endif; ?>
</div>

