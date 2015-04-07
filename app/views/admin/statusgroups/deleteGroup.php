<form method="post" action="<?= $controller->url_for("admin/statusgroups/deleteGroup/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich l�schen?'), $group->name) ?>
    <br>
    <?= Studip\Button::createAccept(_('L�schen'), 'confirm', array('data-dialog-button' => '')) ?>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups/index'), array('data-dialog-button' => '', 'data-dialog' => 'close')) ?>
</form>