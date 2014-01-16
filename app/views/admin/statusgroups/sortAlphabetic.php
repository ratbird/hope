<form method="post" action="<?= $controller->url_for("admin/statusgroups/sortAlphabetic/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich alphabetisch sortieren? Die vorherige Sortierung kann nicht wiederhergestellt werden'), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('Sortieren'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), URLHelper::getLink('dispatch.php/admin/statusgroups/index')) ?>
</form>