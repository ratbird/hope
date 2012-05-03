<? foreach ($documents as $document): ?>
<table class="default">
    <tr>
        <? printhead(0, 0, $document['link'], $document['is_open'], false,
                     $document['icon'], $document['title'], $document['addon'], $document['chdate']); ?>
    </tr>
    <? if ($document['is_open'] === 'open'): ?>
    <tr>
        <td class="printcontent" width="22">&nbsp;</td>
        <td class="printcontent" colspan="3">
            <?= htmlReady($document['description'] ?: _('Keine Beschreibung vorhanden'), true, true) ?>
            <br>
            <br>
            <?= sprintf(_('<b>Dateigröße:</b> %u kB '), round($document['filesize'] / 1024)) ?>
            <?= sprintf(_('<b>Dateiname:</b> %s '), $document['filename']) ?>

        <? if ($document['protected']): ?>
            <?= Messagebox::info(_('Diese Datei ist urheberrechtlich geschützt'), array(
                                 _('Sie darf nur im Rahmen dieser Veranstaltung verwendet werden, jede weitere '
                                  .'Verbreitung ist strafbar!')))?>
        <? endif; ?>
            <div style="text-align: center;">
                <div class="button-group">
                    <?= Studip\LinkButton::create(_('Herunterladen'),
                                                  GetDownloadLink($document['dokument_id'], $document['filename'],
                                                                  $document['type'], 'force')) ?>
                <? if ($type != 6 && !in_array($document['extension'], words('bz2 gzip tgz zip'))): ?>
                    <?= Studip\LinkButton::create(_('Als ZIP herunterladen'),
                                                  GetDownloadLink($document['dokument_id'], $document['filename'],
                                                                  $document['type'], 'zip')) ?>
                <? endif; ?>
                </div>
            </div>
        </td>
    </tr>
    <? endif; ?>
</table>
<? endforeach; ?>
