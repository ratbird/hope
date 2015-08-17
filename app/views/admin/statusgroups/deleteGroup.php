<form method="post" action="<?= $controller->url_for("admin/statusgroups/deleteGroup/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich l�schen?'), $group->name) ?>
    <br>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('L�schen'), 'confirm', array('data-dialog-button' => '')) ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups/index'), array('data-dialog-button' => '', 'data-dialog' => 'close')) ?>
    </div>
</form>