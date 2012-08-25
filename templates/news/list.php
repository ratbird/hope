<table id="news_box" role="article" class="index_box" <? if ($width): ?>style="width: <?= $width ?>;"<? endif; ?>>
    <tr>
        <td class="table_header_bold">
            <img src="<?= Assets::image_path('icons/16/white/breaking-news.png') ?>" 
                 <?= tooltip(_('Newsticker. Klicken Sie rechts auf die Zahnräder, '
                              .'um neue Ankündigungen in diesen Bereich einzustellen. '
                              .'Klicken Sie auf die Pfeile am linken Rand, um den '
                              .'ganzen Nachrichtentext zu lesen.')) ?>>
            <b><?= _('Ankündigungen') ?></b>
        </td>
        <td align="right" class="table_header_bold">
        <? if ($rss_id): ?>
            <a href="rss.php?id=<?= $rss_id ?>">
                <img src="<?= Assets::image_path('icons/16/white/rss.png') ?>"
                     <?= tooltip(_('RSS-Feed')) ?>>
            </a>
        <? endif; ?>
        <? if ($show_admin): ?>
            <a href="<?= URLHelper::getLink('admin_news.php?' . $admin_link . '&modus=admin&cmd=show') ?>">
                <img src="<?= Assets::image_path('icons/16/white/admin.png') ?>"
                     <?= tooltip(_('Ankündigungen bearbeiten')) ?>>
            </a>
        <? endif; ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">
<? foreach ($news as $id => $news_item): ?>
            <div id="news_item_<?= $id ?>" class="news_item" role="article">
                <?= show_news_item($news_item, $cmd_data, $show_admin, $admin_link) ?>
            </div>
<? endforeach; ?>
        </td>
    </tr>
</table>
