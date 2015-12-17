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
                                <?= Icon::create('forum', 'clickable', ['title' => _('Beiträge des Forums der Veranstaltung')])->asImg(20) ?>
                            </a>
                        <? else: ?>
                            <?= Icon::create('forum', 'inactive')->asImg(20, ["style" => 'visibility: hidden;']) ?>
                        <? endif; ?>

                        <? if ($row['archiv_file_id']):
                            $filename = _('Dateisammlung') . '-' . substr($row['name'], 0, 200) . '.zip';
                            ?>
                            <a href="<?= URLHelper::getLink(GetDownloadLink($row['archiv_file_id'], $filename, 1)) ?>">
                                <?= Icon::create('download', 'clickable', ['title' => _('Dateisammlung der Veranstaltung herunterladen')])->asImg(20) ?>
                            </a>
                        <? else: ?>
                            <?= Icon::create('download', 'inactive')->asImg(20, ["style" => 'visibility: hidden;']) ?>
                        <? endif; ?>

                        <? if ($row['wikidump']): ?>
                            <a href="<?= URLHelper::getLink('archiv.php?wiki_dump_id=' . $row['seminar_id']) ?>" target="_blank">
                                <?= Icon::create('wiki', 'clickable', ['title' => _('Beiträge des Wikis der Veranstaltung')])->asImg(20) ?>
                            </a>
                        <? else: ?>
                            <?= Icon::create('wiki', 'inactive')->asImg(20, ["style" => 'visibility: hidden;']) ?>
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
$links->addLink(_('Suche im Archiv'),URLHelper::getLink('archiv.php'), Icon::create('search', 'info'));

$sidebar->addWidget($links, 'actions');
?>
