<form method="POST">
    <?= CSRFProtection::tokenTag() ?> 
    <fieldset><legend><?= _('Gruppen') ?></legend>
        <? foreach ($unfolded as $group): ?>
            <label>
                <input type="checkbox" name="groups[]" value="<?= $group->id ?>" <?= in_array($group->id, $selectedGroups) ? "checked" : "" ; ?>>
                <?= htmlReady($group->name) ?>
            </label><br>
        <? endforeach; ?> 
    </fieldset>
    <fieldset><legend><?= _('Personen') ?></legend>
        <? foreach ($type['groups'] as $group): ?>
            <label><?= htmlReady($group['name']) ?><br>
                <select name="members[]" size="10" multiple>
                    <? foreach ($group['user']() as $user): ?>
                        <option value="<?= $user->user->id ?>" <?= in_array($user->user->id, $selectedMembers) ? "selected" : "" ; ?>><?= $user->user->getFullName('full_rev') ?></option>
                    <? endforeach; ?>
                </select>
                <br>
            </label>
        <? endforeach; ?>
        <label><?= _('Freie Personensuche') ?><br>
            <input name="freesearch" size="30" placeholder="<?= _('Personensuche') ?>" value="<?= Request::get('freesearch') ?>">
            <?= \Studip\Button::create(_('Suchen')) ?><br>
            <? if ($freepeople): ?>
                <select name="members[]" size="10" multiple>
                    <? foreach ($freepeople as $person): ?>
                    <option value="<?= $person->id ?>" <?= in_array($person->id, $selectedMembers) ? "selected" : "" ; ?>><?= htmlReady($person->getFullName('full_rev')) ?></option>
                    <? endforeach; ?>
                </select> 
            <? endif; ?>
        </label>
    </fieldset>
    <?= \Studip\Button::create(_('Hinzufügen'), 'add') ?>
    <?= \Studip\Button::create(_('Auswahl löschen'), 'removeSelection') ?>
</form>
