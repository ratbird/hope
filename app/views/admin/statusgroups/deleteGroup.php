<form method="post" action="<?= $controller->url_for("admin/statusgroups/deleteGroup/{$group->id}") ?>">
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich löschen?'), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('Löschen'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), URLHelper::getLink('dispatch.php/admin/statusgroups/index')) ?>
</form>