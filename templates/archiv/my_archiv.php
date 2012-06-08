<? 
    // TODO: This should be removed when archive_assi uses PageLayout::postMessage() 
    if ($message) parse_msg($message);
?>

<? if (empty($seminars)): ?>
    <?= Messagebox::info(_('Es befinden sich zur Zeit keine Veranstaltungen im Archiv, an denen Sie teilgenommen haben.')) ?>
<? else: ?>

<table class="default zebra-hover">
    <colgroup>
        <col width="80%">
        <col width="10%">
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th>
                <a href="<?= URLHelper::getLink('?sortby=name') ?>">
                    <?= _('Name') ?>
                </a>
            </th>
            <th style="text-align: center"><?= _('Inhalt') ?></th>
            <th style="text-align: center">
                <a href="<?= URLHelper::getLink('?sortby=status') ?>">
                    <?= _('Status') ?>
                </a>
            </th>
        </tr>
    </thead>
<? foreach ($seminars as $semester => $rows): ?>
    <tbody>
    <? if ($semester): ?>
        <tr>
            <td class="steelkante" colspan="3">
                <strong><?= htmlReady($semester) ?></strong>
            </td>
        </tr>
    <? endif; ?>
    <? foreach ($rows as $row): ?>
        <tr>
            <td>
                <a href="<?= URLHelper::getLink('archiv.php?dump_id=' . $row['seminar_id']) ?>" target="_blank">
                    <?= htmlReady($row['name']) ?>
                </a>
            </td>
            <td align="center">
            <? if ($row['forumdump']): ?>
                <a href="<?= URLHelper::getLink('archiv.php?forum_dump_id=' . $row['seminar_id']) ?>" target="_blank">
                    <?= Assets::img('icons/16/blue/forum', tooltip2(_('Beiträge des Forums der Veranstaltung'))) ?>
                </a>
            <? else: ?>
                <?= Assets::img('icons/16/grey/forum', array('style' => 'visibility: hidden;')) ?>
            <? endif; ?>

            <? if ($row['archiv_file_id']):
                  $filename = _('Dateisammlung') . '-' . substr($row['name'], 0, 200) . '.zip';
                  
            ?>
                <a href="<?= URLHelper::getLink(GetDownloadLink($row['archiv_file_id'], $filename, 1)) ?>">
                    <?= Assets::img('icons/16/blue/download', tooltip2(_('Dateisammlung der Veranstaltung herunterladen'))) ?>
                </a>
            <? else: ?>
                <?= Assets::img('icons/16/grey/download', array('style' => 'visibility: hidden;')) ?>
            <? endif; ?>
            
            <? if ($row['wikidump']): ?>
                <a href="<?= URLHelper::getLink('archiv.php?wiki_dump_id=' . $row['seminar_id']) ?>" target="_blank">
                    <?= Assets::img('icons/16/grey/wiki', tooltip2(_('Beiträge des Wikis der Veranstaltung'))) ?>
                </a>
            <? else: ?>
                <?= Assets::img('icons/16/grey/wiki', array('style' => 'visibility: hidden;')) ?>
            <? endif; ?>
            </td>
            <td align="center"><?= $row['status'] ?></td>
        </tr>
    <? endforeach; ?>
    </tbody>
<? endforeach; ?>
</table>

<? endif; ?>
