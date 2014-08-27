<h2><?= _('Wählen Sie das Zielverzeichnis aus') ?></h2>
<form action="<?= $controller->url_for('document/files/move/'. $file_id . '/' . $parent_id) ?>" method="post">
    <input type="hidden" name="studip-ticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

<? if ($file_id === 'flashed'): ?>
<? foreach ($flashed as $id): ?>
    <input type="hidden" name="file_id[]" value="<?= $id ?>">
<? endforeach; ?>
<? endif; ?>

    <ul class="collapsable selectable css-tree">
        <li class="file-directory">
            <input type="radio" name="folder_id" id="folder-<?= $context_id ?>"
                   value="<?= $context_id ?>" <? if ($context_id === $parent_file_id) echo 'checked'; ?>>
            <label for="folder-<?= $context_id ?>"><?= _('Hauptverzeichnis') ?></label>
            <?= $this->render_partial('document/dir-tree.php', array('children' => $dir_tree)) ?>
        </li>
    </ul>
    
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Verschieben'), 'move') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('document/files/index/' . $parent_id)) ?>
    </div>
</form>