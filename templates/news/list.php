<? if(!empty($question_text)) : ?>
    <?= createQuestion2($question_text, 
            $question_param,
            array()); ?>
<? endif ?>
<table id="news_box" role="article" class="index_box" <? if ($width): ?>style="width: <?= $width ?>;"<? endif; ?>>
    <tr>
        <td class="table_header_bold">
            <?= Assets::img('icons/16/white/news.png', 
                            tooltip2(_('Newsticker. Klicken Sie rechts auf die Zahnräder, '
                                      .'um neue Ankündigungen in diesen Bereich einzustellen. '
                                      .'Klicken Sie auf die Pfeile am linken Rand, um den '
                                      .'ganzen Nachrichtentext zu lesen.'))) ?>
            <b><?= _('Ankündigungen') ?></b>
        </td>
        <td align="right" class="table_header_bold">
        <? if ($rss_id): ?>
            <a href="rss.php?id=<?= $rss_id ?>">
                <?= Assets::img('icons/16/white/rss.png', tooltip2(_('RSS-Feed'))) ?>
            </a>
        <? endif; ?>
        <? if ($may_add): ?>
            <a href="<?= URLHelper::getURL('dispatch.php/news/edit_news/new/'.$range_id)?>" rel="get_dialog" target="_blank">
                <?= Assets::img('icons/16/white/add.png', tooltip2(_('Ankündigung erstellen'))) ?>
            </a>
        <? endif; ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">
        <? foreach ($news as $id => $news_item): ?>
            <div id="news_item_<?= $id ?>" class="news_item" role="article">
                <?= show_news_item($news_item, $cmd_data, $range_id) ?>
            </div>
        <? endforeach; ?>
        </td>
    </tr>
</table>