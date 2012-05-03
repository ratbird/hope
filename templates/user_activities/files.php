<?
    $sections = array(
        'seminars' => array(
            'title' => _('Dateiübersicht Veranstaltungen'),
            'link'  => 'seminar_main.php?redirect_to=folder.php&cmd=all',
        ),
        'institutes' => array(
            'title' => _('Dateiübersicht Einrichtungen'),
            'link'  => 'institut_main.php?redirect_to=folder.php&cmd=all',
        ),
    );
?>

<p style="margin-left:20px; text-align: center;">
    <?= _('Alle Dateien dieses Nutzers als Zip') ?>
    <?= Studip\LinkButton::create(_('Herunterladen'), '?download_as_zip=all') ?>
</p>

<? foreach ($sections as $index => $section): ?>
<table class="default">
    <thead>
        <tr>
            <td colspan="4" class="topic"><?= $section['title'] ?></td>
        </tr>
    </thead>
    <tbody>
    <? if (empty($files[$index])): ?>
        <tr>
            <td colspan="4" class="printhead" style="text-align: center;">
                <?= _('Keine Dateien vorhanden') ?>
            </td>
        </tr>
    <? endif; ?>
    <? foreach ($files[$index] as $file): ?>
        <tr>
            <? printhead(0, 0, false, $file['is_open'], false, '&nbsp;', $file['title'], $file['addon'], 0); ?>
        </tr>

        <? if ($file['is_open'] == 'open'): ?>
        <tr>
            <td class="printcontent">&nbsp;</td>
            <td class="printcontent" colspan="3">
                <div style="margin-bottom: 10px;">
                    <b>
                        <a href="<?= URLHelper::getLink($section['link'],
                                                        array('auswahl' => $file['id'])) ?>">
                            <?= Assets::img('icons/16/blue/files') ?>
                            <?= getHeaderLine($file['id']) ?>
                        </a>
                    </b>
                    <br>
                    <?= _('Status in der Veranstaltung:') ?>
                    <b><?= $file['status'] ?: _('unbekannt') ?></b>
                </div>
                <div style="margin-bottom: 10px;" align="center">
                    <?= Studip\LinkButton::create(_('Herunterladen'), '?download_as_zip=' . $file['Seminar_id']) ?>
                </div>
                <?= show_documents(get_user_documents($user_id, $file['id']), $open) ?>
            </td>
        </tr>
        <? endif; ?>
    </tbody>
    <? endforeach; ?>
</table>

<br>
<?endforeach; ?>

