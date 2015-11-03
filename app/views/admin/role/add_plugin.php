<?php
use Studip\Button;
use Studip\LinkButton;
?>

<form action="<?= $controller->url_for('admin/role/add_plugin/' . $role_id) ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>

    <label>
        <?= _('Plugin(s) auswählen') ?>

        <select name="plugin_ids[]" multiple>
        <? foreach ($plugins as $plugin): ?>
            <option value="<?= $plugin['id'] ?>">
                <?= htmlReady($plugin['name']) ?>
            </option>
        <? endforeach; ?>
        </select>
    </label>

    <footer data-dialog-button>
        <?= Button::createAccept(_('Hinzufügen')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'),
                                     $controller->url_for('admin/role/show_role/' . $role_id)) ?>
    </footer>
</form>