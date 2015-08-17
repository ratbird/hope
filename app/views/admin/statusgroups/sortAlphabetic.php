<form method="post" action="<?= $controller->url_for("admin/statusgroups/sortAlphabetic/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich alphabetisch sortieren? Die vorherige Sortierung kann nicht wiederhergestellt werden.'), $group->name) ?>
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Sortieren'), 'confirm') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups')) ?>
    </div>
</form>