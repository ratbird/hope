<table id="news_box" role="article" class="default nohover" <? if ($width): ?> style="width: <?= $width ?>;"<? endif; ?>>
    <thead>
        <tr>
            <th>
                <img src="<?= Assets::image_path('icons/16/black/news.png') ?>"
                     <?= tooltip(_('Newsticker. Klicken Sie rechts auf die Zahnräder, '
                                  .'um neue News in diesen Bereich einzustellen. Klicken '
                                  .'Sie auf die Pfeile am linken Rand, um den ganzen '
                                  .'Nachrichtentext zu lesen.')) ?>>
                <b><?= _('Ankündigungen') ?></b>
            </th>
            <th class="actions">
                <a href="<?=URLHelper::getURL('dispatch.php/news/edit_news/new/'.$range_id)?>" rel="get_dialog" target="_blank">
                    <img src="<?= Assets::image_path('icons/16/blue/add.png') ?>" 
                         <?= tooltip(_('Ank&uuml;ndigung erstellen')) ?>>
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">
                <p class="info">
                    <?= _('Es sind keine aktuellen Ankündigungen vorhanden. '
                         .'Um neue Ankündigungen zu erstellen, klicken Sie rechts auf das Plus-Zeichen.') ?>
                </p>
            </td>
        </tr>
    </tbody>
</table>
