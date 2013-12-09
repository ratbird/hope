<form>
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Gruppe %s wirklich leeren?'), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('Leeren'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), URLHelper::getLink('dispatch.php/admin/statusgroups/index')) ?>
</form>