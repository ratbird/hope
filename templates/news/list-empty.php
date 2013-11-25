<table id="news_box" role="article" class="index_box" <? if ($width): ?> style="width: <?= $width ?>;"<? endif; ?>>
    <tr>
        <td class="table_header_bold">
            <img src="<?= Assets::image_path('icons/16/white/news.png') ?>"
                 <?= tooltip(_('Newsticker. Klicken Sie rechts auf die Zahnr�der, '
                              .'um neue News in diesen Bereich einzustellen. Klicken '
                              .'Sie auf die Pfeile am linken Rand, um den ganzen '
                              .'Nachrichtentext zu lesen.')) ?>>
            <b><?= _('Ank�ndigungen') ?></b>
        </td>
        <td align="right" class="table_header_bold">
            <a href="<?=URLHelper::getURL('dispatch.php/news/edit_news/new/'.$range_id)?>" rel="get_dialog" target="_blank">
                <img src="<?= Assets::image_path('icons/16/white/add.png') ?>" 
                     <?= tooltip(_('Ank&uuml;ndigung erstellen')) ?>>
            </a>
        </td>
    </tr>
    <tr>
        <td class="table_row_even" colspan="2">
            <p class="info">
                <?= _('Es sind keine aktuellen Ank�ndigungen vorhanden. '
                     .'Um neue Ank�ndigungen zu erstellen, klicken Sie rechts auf das Plus-Zeichen.') ?>
            </p>
        </td>
    </tr>
</table>
