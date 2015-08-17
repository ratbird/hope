<form method="post" action="<?= $controller->url_for("admin/statusgroups/delete/{$group->id}/{$user->user_id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('%s wirklich aus %s austragen?'), $user->getFullname(), $group->name) ?>
    <br>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Entfernen'), 'confirm') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups/index')) ?>
    </div>
</form>