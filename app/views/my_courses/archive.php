<?
// TODO: This should be removed when archive_assi uses PageLayout::postMessage()
if ($message) parse_msg($message);
?>

<? if (empty($seminars)): ?>
    <?= MessageBox::info(_('Es befinden sich zur Zeit keine Veranstaltungen im Archiv, an denen Sie teilgenommen haben.')) ?>
<? else: ?>
    <? foreach ($seminars as $semester => $rows): ?>
        <table class="default">
            <? if ($semester): ?>
                <caption><?= htmlReady($semester) ?></caption>

            <? endif; ?>
            <colgroup>
                <col width="80%">
                <col width="10%">
                <col width="10%">
            </colgroup>
            <thead>
            <tr>
                <th>
                    <a href="<?= $controller->url_for('my_courses/archive?sortby=name') ?>">
                        <?= _('Name') ?>
                    </a>
                </th>
                <th style="text-align: center"><?= _('Inhalt') ?></th>
                <th style="text-align: center">
                    <a href="<?= $controller->url_for('my_courses/archive?sortby=status') ?>">
                        <?= _('Status') ?>
                    </a>
                </th>
            </tr>
            </thead>
            <tbody>
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
                                <?= Assets::img('icons/20/blue/forum', tooltip2(_('Beiträge des Forums der Veranstaltung'))) ?>
                            </a>
                        <? else: ?>
                            <?= Assets::img('icons/20/grey/forum', array('style' => 'visibility: hidden;')) ?>
                        <? endif; ?>

                        <? if ($row['archiv_file_id']):
                            $filename = _('Dateisammlung') . '-' . substr($row['name'], 0, 200) . '.zip';
                            ?>
                            <a href="<?= URLHelper::getLink(GetDownloadLink($row['archiv_file_id'], $filename, 1)) ?>">
                                <?= Assets::img('icons/20/blue/download', tooltip2(_('Dateisammlung der Veranstaltung herunterladen'))) ?>
                            </a>
                        <? else: ?>
                            <?= Assets::img('icons/20/grey/download', array('style' => 'visibility: hidden;')) ?>
                        <? endif; ?>

                        <? if ($row['wikidump']): ?>
                            <a href="<?= URLHelper::getLink('archiv.php?wiki_dump_id=' . $row['seminar_id']) ?>" target="_blank">
                                <?= Assets::img('icons/20/blue/wiki', tooltip2(_('Beiträge des Wikis der Veranstaltung'))) ?>
                            </a>
                        <? else: ?>
                            <?= Assets::img('icons/20/grey/wiki', array('style' => 'visibility: hidden;')) ?>
                        <? endif; ?>
                    </td>
                    <td style="text-align: center"><?= $row['status'] ?></td>
                </tr>
            <? endforeach; ?>
            </tbody>
        </table>
    <? endforeach; ?>
<? endif; ?>
<?
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/seminar-archive-sidebar.png');
$sidebar->setTitle(_('Meine archivierten Veranstaltungen'));

$links = new LinksWidget();
$links->setTitle(_('Aktionen'));
$links->addLink(_('Suche im Archiv'),URLHelper::getLink('archiv.php'),'icons/16/black/search.png');

$sidebar->addWidget($links, 'actions');
?>
