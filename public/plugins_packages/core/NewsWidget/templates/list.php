<table id="news_box" role="article" class="index_box" <? if ($width): ?>style="width: <?= $width ?>;"<? endif; ?>>
    <!-- TODO: JS aus dem js-Verzeichnis veröffentlichen  -->
   <!-- <script type="text/javascript" url=<?  ?>></script> -->
    <tr>
        <td class="blank" colspan="2">
<? if(empty($news)) :?>
    <?=  MessageBox::info(_('Momentan sind keine Ankündigungen verfügbar.'));?>
<? else:?>
    <? foreach ($news as $id => $news_item): ?>
                <div id="news_item_<?= $id . $widgetId?>" class="news_item" role="article">
                    <?= NewsWidget::show_news_item_action($news_item, $cmd_data, $show_admin, $admin_link,$plugin,$widgetId) ?>
                </div>
    <? endforeach; ?>
<? endif;?>
        </td>
    </tr>
</table>
