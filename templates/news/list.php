<? if(!empty($question_text)) : ?>
    <?= createQuestion($question_text, 
            $question_param,
            array()); ?>
<? endif ?>
<table id="news_box" role="article" class="default nohover" <? if ($width): ?>style="width: <?= $width ?>;"<? endif; ?>>
    <thead>
        <tr>
            <th>
                <img src="<?= Assets::image_path('icons/16/white/news.png') ?>" 
                     <?= tooltip(_('Newsticker. Klicken Sie rechts auf die Zahnräder, '
                                  .'um neue Ankündigungen in diesen Bereich einzustellen. '
                                  .'Klicken Sie auf die Pfeile am linken Rand, um den '
                                  .'ganzen Nachrichtentext zu lesen.')) ?>>
                <b><?= _('Ankündigungen') ?></b>
            </th>
            <th class="actions">
            <? if ($rss_id): ?>
                <a href="rss.php?id=<?= $rss_id ?>">
                    <img src="<?= Assets::image_path('icons/16/white/rss.png') ?>"
                         <?= tooltip(_('RSS-Feed')) ?>>
                </a>
            <? endif; ?>
            <? if ($may_add): ?>
                <a href="<?= URLHelper::getURL('dispatch.php/news/edit_news/new/'.$range_id)?>" rel="get_dialog" target="_blank">
                    <img src="<?= Assets::image_path('icons/16/white/add.png') ?>" 
                         <?= tooltip(_('Ank&uuml;ndigung erstellen')) ?>>
                </a>
                <? endif; ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="blank" colspan="2">
                <? foreach ($news as $id => $news_item): ?>
                    <div id="news_item_<?= $id ?>" class="news_item" role="article">
                        <?= show_news_item($news_item, $cmd_data, $range_id) ?>
                    </div>
                <? endforeach; ?>
            </td>
        </tr>
    </tbody>
</table>