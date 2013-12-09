<form>
    <?= CSRFProtection::tokenTag() ?>
    <?= sprintf(_('Benutzer %s wirklich aus %s entfernen?'), $user->getFullname(), $group->name) ?>
    <br>
    <?= Studip\Button::create(_('Entfernen'), 'confirm') ?>
    <?= Studip\LinkButton::create(_('Abbrechen'), URLHelper::getLink('dispatch.php/admin/statusgroups/index')) ?>
</form>