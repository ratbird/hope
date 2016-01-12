<?php
use Studip\Button, Studip\LinkButton;
?>

<form action="<?= $controller->url_for('admin/datafields/new/' . $object_typ) ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <legend>
            <?= sprintf(_('Einen neuen Datentyp für die Kategorie "%s" erstellen'), $type_name) ?>
        </legend>

        <label>
            <span class="required"><?= _('Name') ?></span>

            <input type="text" name="datafield_name"
                   required size="60" maxlength="254"
                   value="<?= htmlReady($this->flash['request']['datafield_name']) ?>">
        </label>

        <label>
            <?= _('Feldtyp') ?>

            <select name="datafield_typ">
           <? foreach (DataFieldEntry::getSupportedTypes() as $param): ?>
                <option><?= htmlReady($param) ?></option>
            <? endforeach; ?>
            </select>
        </label>

        <label for="object_class">
        <? if ($object_typ === 'sem'): ?>
            <?= _('Veranstaltungskategorie') ?>
        <? elseif ($object_typ === 'inst'): ?>
            <?= _('Einrichtungstyp') ?>
        <? else: ?>
            <?= _('Nutzerstatus') ?>
        <? endif; ?>

        <? if ($object_typ === 'sem'): ?>
            <select name="object_class[]">
                <option value="NULL"><?= _('alle') ?></option>
            <? foreach (SemClass::getClasses() as $key => $val): ?>
                <option value="<?= $key ?>">
                    <?= htmlReady($val['name']) ?>
                </option>
            <? endforeach; ?>
        <? elseif ($object_typ === 'inst'): ?>
            <select name="object_class[]">
                <option value="NULL"><?= _('alle') ?></option>
            <? foreach ($GLOBALS['INST_TYPE'] as $key => $val): ?>
                <option value="<?= $key ?>">
                    <?= htmlReady($val['name']) ?>
                </option>
            <? endforeach; ?>
        <? else: ?>
             <select multiple size="7" name="object_class[]" required>
                <option value="NULL" selected><?= _('alle') ?></option>
            <? foreach ($controller->user_status as $perm => $value): ?>
                <option value="<?= $value ?>"><?= $perm ?></option>
            <? endforeach; ?>
        <? endif; ?>
            </select>
        </label>

        <label>
            <?= _('benötigter Status') ?>

            <select name="edit_perms">
            <? foreach (array_keys($controller->user_status) as $perm): ?>
                <option><?= $perm ?></option>
            <? endforeach; ?>
            </select>
        </label>

        <label>
            <?= _('Sichtbarkeit') ?>

            <select name="visibility_perms">
            <? foreach (array_keys($controller->user_status) as $perm): ?>
                <option><?= $perm ?></option>
            <? endforeach; ?>
            </select>
        </label>

    <? if ($object_typ === 'user') :?>
        <label>
            <?= _('Systemfeld') ?>
            <?= tooltipIcon(_('Nur für die Person selbst sichtbar, wenn der '
                            . 'benötigte Status zum Bearbeiten oder die '
                            . 'Sichtbarkeit ausreichend ist')) ?>

            <input type="hidden" name="system" value="0">
            <input type="checkbox" name="system" value="1"
                   <? if ($this->flash['request']['system']) echo 'checked'; ?>>
        </label>
    <? endif; ?>

        <label>
            <?= _('Position') ?>

            <input type="text" name="priority"
                   maxlength="10" size="2"
                   value="<?= htmlReady($this->flash['request']['priority']) ?>">
        </label>

    <? if ($object_typ === 'sem'): ?>
        <label>
            <?= _('Pflichtfeld') ?>

            <input type="checkbox" name="is_required" value="true"
                   <? if ($this->flash['request']['is_required']) echo 'checked'; ?>>
        </label>

        <label>
            <?= _('Beschreibung') ?>

            <textarea name="description"><?= htmlReady($this->flash['request']['description']) ?></textarea>
        </label>

    <? endif; ?>
    <? if ($object_typ === 'user'): ?>
        <label>
            <?= _('Mögliche Bedingung für Anmelderegel') ?>

            <input type="checkbox" name="is_userfilter" value="1"
                   <? if ($this->flash['request']['is_userfilter']) echo 'checked'; ?>>
        </label>
    <? endif; ?>
    </fieldset>

    <footer data-dialog-button>
        <?= Button::createAccept(_('Anlegen'), 'anlegen', array('title' => _('Neues Datenfeld anlegen'))) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/datafields'), array('title' => _('Zurück zur Übersicht'))) ?>
    </footer>
</form>
