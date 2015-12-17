<? if (isset($flash['question'])): ?>
    <?= $flash['question'] ?>
<? endif; ?>

<form action="<?= $controller->url_for('document/files/bulk/' . $dir_id . '/' . $page) ?>" method="post" data-shiftcheck>

<table class="default documents <? if ($files->count() > 0) echo 'sortable-table'; ?>">
    <caption>
        <div class="caption-container">
            <? $last_crumb = end($breadcrumbs); ?>
        <? if (count($breadcrumbs) > 1): ?>
            <div class="extendable bread-crumbs" title="<?= _('In übergeordnete Verzeichnisse wechseln') ?>">
        <? else: ?>
            <div class="bread-crumbs">
        <? endif; ?>
                <a href="<?= $controller->url_for('document/files/index/' . $last_crumb['id']) ?>">
                    <?= Icon::create('folder-parent', 'clickable')->asImg(24) ?>
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
                <?= Icon::create('stat', 'info', ['title' => _('Speicherplatz')])->asImg() ?>
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
        <tr class="chdir-up" <? if ($full_access) printf('data-folder="%s"', $folder_id) ?> data-sort-fixed>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td class="document-icon">
                <a href="<?= $controller->url_for('document/files/index/' . $parent_id, $parent_page ) ?>">
                    <?= Icon::create('arr_1up', 'clickable', ['title' => _('Ein Verzeichnis nach oben wechseln')])->asImg(24) ?>
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
<? if ($files->count() === 0): ?>
        <tr>
            <td colspan="8" class="empty">
                <?= _('Dieser Ordner ist leer') ?>
            </td>
        </tr>
<? else: ?>
    <? foreach ($files as $file): ?>
        <tr <? if ($full_access) printf('data-file="%s"', $file->id) ?> <? if ($full_access && $file->isDirectory()) printf('data-folder="%s"', $file->file->id); ?>>
            <td class="dragHandle">&nbsp;</td>
            <td>
                <input type="checkbox" name="ids[]" value="<?= $file->id ?>" <? if (in_array($file->id, $marked)) echo 'checked'; ?>>
            </td>
        <? if ($file->isDirectory()): ?>
            <td class="document-icon" data-sort-value="0">
                <a href="<?= $controller->url_for('document/files/index/' . $file->id) ?>">
                <? if ($file->file->isEmpty()): ?>
                    <?= Icon::create('folder-empty', 'clickable')->asImg(24) ?>
                <? else: ?>
                    <?= Icon::create('folder-full', 'clickable')->asImg(24) ?>
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
            <? if ($full_access): ?>
                <a href="<?= $controller->url_for('document/folder/edit/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Ordner bearbeiten') ?>">
                    <?= Icon::create('edit', 'clickable')->asImg(16, ["alt" => _('bearbeiten')]) ?>
                </a>
            <? endif; ?>
                <a href="<?= $file->getDownloadLink() ?>" title="<?= _('Ordner herunterladen') ?>">
                    <?= Icon::create('download', 'clickable')->asImg(16, ["alt" => _('herunterladen')]) ?>
                </a>
            <? if ($full_access): ?>
                <a href="<?= $controller->url_for('document/files/move/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Ordner verschieben') ?>">
                    <?= Icon::create('folder-empty+move_right', 'clickable')->asImg(16, ["alt" => _('verschieben')]) ?>
                </a>
                 <a href="<?= $controller->url_for('document/files/copy/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Ordner kopieren') ?>">
                    <?= Icon::create('folder-empty+add', 'clickable')->asImg(16, ["alt" => _('kopieren')]) ?>
                </a>
                <a href="<?= $controller->url_for('document/folder/delete/' . $file->id) ?>" title="<?= _('Ordner löschen') ?>">
                    <?= Icon::create('trash', 'clickable')->asImg(16, ["alt" => _('löschen')]) ?>
                </a>
            <? endif; ?>
            </td>
        <? else: ?>
            <td class="document-icon" data-sort-value="1">
                <a href="<?= $file->getDownloadLink(true) ?>">
                    <?= Icon::create(get_icon_for_mimetype($file->file->mime_type), 'clickable')->asImg(24) ?>
                </a>
            </td>
            <td>
                <a href="<?= $file->getDownloadLink() ?>">
                    <?= htmlReady($file->name) ?>
                </a>
            <? if ($file->file->restricted): ?>
              <?= Icon::create('lock-locked', 'clickable',['title' => _('Diese Datei ist nicht frei von Rechten Dritter.')])->asImg(['class' => 'text-top']) ?>
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
            <? if ($full_access): ?>
                <a href="<?= $controller->url_for('document/files/edit/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Datei bearbeiten') ?>">
                    <?= Icon::create('edit', 'clickable')->asImg(16, ["alt" => _('bearbeiten')]) ?>
                </a>
            <? endif; ?>
                <a href="<?= $file->getDownloadLink() ?>" title="<?= _('Datei herunterladen') ?>">
                    <?= Icon::create('download', 'clickable')->asImg(16, ["alt" => _('herunterladen')]) ?>
                </a>
            <? if ($full_access): ?>
                <a href="<?= $controller->url_for('document/files/move/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Datei verschieben') ?>">
                    <?= Icon::create('file+move_right', 'clickable')->asImg(16, ["alt" => _('verschieben')]) ?>
                </a>
                <a href="<?= $controller->url_for('document/files/copy/' . $file->id) ?>" data-dialog="size=auto" title="<?= _('Datei kopieren') ?>">
                    <?= Icon::create('file+add', 'clickable')->asImg(16, ["alt" => _('kopieren')]) ?>
                </a>
                <a href="<?= $controller->url_for('document/files/delete/' . $file->id) ?>" title="<?= _('Datei löschen') ?>">
                    <?= Icon::create('trash', 'clickable')->asImg(16, ["alt" => _('löschen')]) ?>
                </a>
            <? endif; ?>
            </td>
        <? endif; ?>
        </tr>
    <? endforeach; ?>
<? endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">
        <? if ($full_access || extension_loaded('zip')): ?>
                <?= _('Alle markierten') ?>
            <? if (extension_loaded('zip')): ?>
                <?= Studip\Button::create(_('Herunterladen'), 'download') ?>
            <? endif; ?>
            <? if ($full_access): ?>
                <?= Studip\Button::create(_('Verschieben'), 'move', array('data-dialog' => '')) ?>
                <?= Studip\Button::create(_('Kopieren'), 'copy', array('data-dialog' => ''))?>
                <?= Studip\Button::create(_('Löschen'), 'delete') ?>
            <? endif; ?>
        <? endif; ?>
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
