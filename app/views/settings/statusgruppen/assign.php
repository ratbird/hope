<? use Studip\Button, Studip\LinkButton; ?>

<h3><?= _('Person einer Gruppe zuordnen') ?></h3>
<form action="<?= $controller->url_for('settings/statusgruppen/assign') ?>" method="post">
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    <?= CSRFProtection::tokenTag() ?>

    <label>
        <?= _('Einrichtung und Funktion auswählen') ?>:
        <select required name="role_id" class="role-selector">
            <option value="">-- <?= _('Bitte auswählen') ?> --</option>
    <? foreach ($admin_insts as $data): ?>
            <optgroup label="<?= htmlReady(substr($data['Name'], 0, 70)) ?>">
                <? Statusgruppe::displayOptionsForRoles($data['groups']) ?>
            </optgroup>
        <? foreach ($data['sub'] as $sub_id => $sub): ?>
            <optgroup label="<?= htmlReady(substr($sub['Name'], 0, 70)) ?>" class="nested">
                <? Statusgruppe::displayOptionsForRoles($sub['groups']) ?>
            </optgroup>
        <? endforeach; ?>
    <? endforeach; ?>
        </select>
    </label>

    <?= Button::create(_('Zuweisen'), 'assign') ?>
</form>
