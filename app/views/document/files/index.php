<? if (isset($flash['question'])): ?>
    <?= $flash['question'] ?>
<? endif; ?>

<form action="<?= $controller->url_for('document/files/bulk/' . $dir_id . '/' . $page) ?>" method="post" data-shiftcheck>

<table class="default documents <? if (!empty($files)) echo 'sortable-table'; ?>">
    <caption>
        <div class="caption-container">
            <? $last_crumb = end($breadcrumbs); ?>
        <? if (count($breadcrumbs) > 1): ?>
            <div class="extendable bread-crumbs" title="<?= _('In übergeordnete Verzeichnisse wechseln') ?>">
        <? else: ?>
            <div class="bread-crumbs">
        <? endif; ?>
                <a href="<?= $controller->url_for('document/files/index/' . $last_crumb['id']) ?>">
                    <?= Assets::img('icons/24/blue/folder-parent.png') ?>
                </a>
            <? if (count($breadcrumbs) > 1): ?>
                <ul>
                <? foreach (array_slice($breadcrumbs, 0, -1) as $crumb): ?>
                    <li>
                        <a href="<?= $controller->url_for('document/files/index/' . $crumb['id']) ?>">
                            <?= htmlReady($crumb['name']) ?>
                        </a>
                    </li>
                <? endforeach; ?>
                </ul>
            <? endif; ?>
            </div>
            <div class="caption-content">
                <header class="folder-description">
                    <h2>
                        <?= htmlReady($last_crumb['name']) ?>
                    <? if ($maxpages > 1): ?>
                        (<?= sprintf(_('Seite %u von %u'), $page, $maxpages) ?>)
                    <? endif; ?>
                    </h2>
                <? if ($last_crumb['description']): ?>
                    <p><?= formatReady($last_crumb['description']) ?></p>
                <? endif; ?>
                </header>
            </div>

            <div class="caption-actions">
                <?= Assets::img('icons/16/black/stat.png', tooltip2(_('Speicherplatz'))) ?>
                <?= sprintf(_('%0.1f%% belegt'), $space_used / $space_total * 100) ?>
                (<?= relsize($space_used, false) ?>
                /<?= relsize($space_total, false) ?>)
            </div>        
        </div>
    </caption>
    <colgroup>
        <col width="25px">
        <col width="30px">
        <col width="20px">
        <col>
        <col width="100px">
        <col width="150px">
        <col width="120px">
        <col width="120px">
    </colgroup>
    <thead>
        <tr>
            <th data-sort="false">&nbsp;</th>
            <th data-sort="false">
                <input type="checkbox" data-proxyfor=":checkbox[name='ids[]']"
                       data-activates="table.documents tfoot button">
            </th>
            <th data-sort="htmldata"><?= _('Typ') ?></th>
            <th data-sort="text"><?= _('Name') ?></th>
            <th data-sort="htmldata"><?= _('Größe') ?></th>
            <th data-sort="htmldata"><?= _('Autor/in') ?></th>
            <th data-sort="htmldata"><?= _('Datum') ?></th>
            <th data-sort="false"><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
<? if (!$directory->isRootDirectory()): ?>
        <tr class="chdir-up" data-folder="<?= $folder_id ?>" data-sort-fixed>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td class="document-icon">
                <a href="<?= $controller->url_for('document/files/index/' . $parent_id, $parent_page ) ?>">
                    <?= Assets::img('icons/24/blue/arr_1up.png', tooltip2(_('Ein Verzeichnis nach oben wechseln'))) ?>
                </a>
            </td>
            <td>
                <a href="<?= $controller->url_for('document/files/index/' . $parent_id, $parent_page) ?>" title="<?= _('Ein Verzeichnis nach oben wechseln') ?>">
                    .. <small><?= _('Ein Verzeichnis nach oben wechseln') ?></small>
                </a>
            </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
<? endif; ?>
<? if (empty($files)): ?>
        <tr>
            <td colspan="8" class="empty">
                <?= _('Dieser Ordner ist leer') ?>
            </td>
        </tr>
<? else: ?>
    <? foreach ($files as $file): ?>
        <tr data-file="<?= $file->id ?>" <? if ($file->isDirectory()) printf('data-folder="%s"', $file->file->id); ?>>
            <td class="dragHandle">&nbsp;</td>
            <td>
                <input type="checkbox" name="ids[]" value="<?= $file->id ?>" <? if (in_array($file->id, $marked)) echo 'checked'; ?>>
            </td>
        <? if ($file->isDirectory()): ?>
            <td class="document-icon" data-sort-value="0">
                <a href="<?= $controller->url_for('document/files/index/' . $file->id) ?>">
                <? if ($file->file->isEmpty()): ?>
                    <?= Assets::img('icons/24/blue/folder-empty.png') ?>
                <? else: ?>
                    <?= Assets::img('icons/24/blue/folder-full.png') ?>
                <? endif; ?>
                </a>
            </td>
            <td>
                <a href="<?= $controller->url_for('document/files/index/' . $file->id) ?>">
                    <?= htmlReady($file->name) ?>
                </a>
            <? if ($file->description): ?>
                <small><?= htmlReady($file->description) ?></small>
            <? endif; ?>
            </td>
            <? // -number + file count => directories should be sorted apart from files ?>
            <td data-sort-value="<?= -1000000 + ($count = $file->file->countFiles()) ?>">
                <?= sprintf(ngettext('%u Eintrag', '%u Einträge', $count), $count) ?>
            </td>
            <td data-sort-value="<?= htmlReady($file->file->owner->getFullName('no_title')) ?>">
            <? if ($file->file->owner->id !== $GLOBALS['user']->id): ?>
                <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $file->file->owner->username) ?>">
                    <?= htmlReady($file->file->owner->getFullName()) ?>
                </a>
            <? else: ?>
                <?= htmlReady($file->file->owner->getFullName()) ?>
            <? endif; ?>
            </td>
            <td title="<?= strftime('%x %X', $file->file->mkdate) ?>" data-sort-value="<?= $file->file->mkdate ?>">
                <?= reltime($file->file->mkdate) ?>
            </td>
            <td class="options">
                <a href="<?= $controller->url_for('document/folder/edit/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Ordner bearbeiten') ?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('alt' => _('bearbeiten'))) ?>
                </a>
                <a href="<?= $file->getDownloadLink() ?>" title="<?= _('Ordner herunterladen') ?>">
                    <?= Assets::img('icons/16/blue/download.png', array('alt' => _('herunterladen'))) ?>
                </a>
                <a href="<?= $controller->url_for('document/files/move/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Ordner verschieben') ?>">
                    <?= Assets::img('icons/16/blue/move_right/folder-empty.png', array('alt' => _('verschieben'))) ?>
                </a>
                 <a href="<?= $controller->url_for('document/files/copy/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Ordner kopieren') ?>">
                    <?= Assets::img('icons/16/blue/add/folder-empty.png', array('alt' => _('kopieren'))) ?>
                </a>
                <a href="<?= $controller->url_for('document/folder/delete/' . $file->id) ?>" title="<?= _('Ordner löschen') ?>">
                    <?= Assets::img('icons/16/blue/trash.png', array('alt' => _('löschen'))) ?>
                </a>
            </td>
        <? else: ?>
            <td class="document-icon" data-sort-value="1">
                <a href="<?= $file->getDownloadLink(true) ?>">
                    <?= Assets::img('icons/24/blue/'. get_icon_for_mimetype($file->file->mime_type)) ?>
                </a>
            </td>
            <td>
                <a href="<?= $file->getDownloadLink() ?>">
                    <?= htmlReady($file->name) ?>
                </a>
            <? if ($file->file->restricted): ?>
                <?= Assets::img('icons/16/blue/lock-locked.png', array('class' => 'text-top') + tooltip2(_('Diese Datei ist nicht frei von Rechten Dritter.'))) ?>
            <? endif; ?>
            <? if ($file->description): ?>
                <small><?= htmlReady($file->description) ?></small>
            <? endif; ?>
            </td>
            <td title="<?= number_format($file->file->size, 0, ',', '.') . ' Byte' ?>" data-sort-value="<?= $file->file->size ?>">
                <?= relSize($file->file->size, false) ?>
            </td>
            <td data-sort-value="<?= $file->file->owner->getFullName('no_title') ?>">
            <? if ($file->file->owner->id !== $GLOBALS['user']->id): ?>
                <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $file->file->owner->username) ?>">
                    <?= htmlReady($file->file->owner->getFullName()) ?>
                </a>
            <? else: ?>
                <?= htmlReady($file->file->owner->getFullName()) ?>
            <? endif; ?>
            </td>
            <td title="<?= strftime('%x %X', $file->file->mkdate) ?>" data-sort-value="<?= $file->file->mkdate ?>">
                <?= reltime($file->file->mkdate) ?>
            </td>
            <td class="options">
                <a href="<?= $controller->url_for('document/files/edit/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Datei bearbeiten') ?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('alt' => _('bearbeiten'))) ?>
                </a>
                <a href="<?= $file->getDownloadLink() ?>" title="<?= _('Datei herunterladen') ?>">
                    <?= Assets::img('icons/16/blue/download.png', array('alt' => _('herunterladen'))) ?>
                </a>
                <a href="<?= $controller->url_for('document/files/move/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Datei verschieben') ?>">
                    <?= Assets::img('icons/16/blue/move_right/file.png', array('alt' => _('verschieben'))) ?>
                </a>
                <a href="<?= $controller->url_for('document/files/copy/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Datei kopieren') ?>">
                    <?= Assets::img('icons/16/blue/add/file.png', array('alt' => _('kopieren'))) ?>
                </a>
                <a href="<?= $controller->url_for('document/files/delete/' . $file->id) ?>" title="<?= _('Datei löschen') ?>">
                    <?= Assets::img('icons/16/blue/trash.png', array('alt' => _('löschen'))) ?>
                </a>
            </td>
        <? endif; ?>
        </tr>
    <? endforeach; ?>
<? endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">
                <?= _('Alle markierten') ?>
            <? if (extension_loaded('zip')): ?>
                <?= Studip\Button::create(_('Herunterladen'), 'download') ?>
            <? endif; ?>
                <?= Studip\Button::create(_('Verschieben'), 'move', array('data-dialog' => '')) ?>
                <?= Studip\Button::create(_('Kopieren'), 'copy', array('data-dialog' => ''))?>
                <?= Studip\Button::create(_('Löschen'), 'delete') ?>
            </td>
            <td colspan="3" class="actions">
                <?= $GLOBALS['template_factory']->render('shared/pagechooser', array(
                        'perPage'      => $limit,
                        'num_postings' => $filecount,
                        'page'         => $page,
                        'pagelink'     => $controller->url_for('document/files/index/' . $dir_id . '/%u')
                    ))
                ?>
            </td>
        </tr>
    </tfoot>
</table>
</form>
