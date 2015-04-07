<form method="post" action="<?= $controller->url_for("admin/statusgroups/sortAlphabetic/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich alphabetisch sortieren? Die vorherige Sortierung kann nicht wiederhergestellt werden.'), $group->name) ?>
    <br>
    <?= Studip\Button::createAccept(_('Sortieren'), 'confirm', array('data-dialog-button' => '')) ?>
    <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups'), array('data-dialog-button' => '', 'data-dialog' => 'close')) ?>
</form>