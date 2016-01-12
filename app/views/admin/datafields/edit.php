<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>

<form action="<?= $controller->url_for('admin/datafields/edit/' . $item->id) ?>" method="post"
      class="default">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <legend><?= _('Bearbeiten der Parameter') ?></legend>

        <label>
            <span class="required"><?= _('Name') ?></span>

            <input type="text" name="datafield_name" id="datafield_name"
                   required size="60" maxlength="254"
                   value="<?= htmlReady($item->name) ?>">
        </label>

        <label>
            <?= _('Feldtyp') ?>

            <select name="datafield_type" id="datafield_type">
            <? foreach (DataFieldEntry::getSupportedTypes() as $param): ?>
                <option <? if ($item->type === $param) echo 'selected'; ?>>
                     <?= htmlReady($param) ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>

        <label>
        <? if ($item->object_type === 'sem'): ?>
            <?= _('Veranstaltungskategorie') ?>:

            <select name="object_class[]" id="object_class">
                <option value="NULL"><?= _('alle') ?></option>
            <? foreach (SemClass::getClasses() as $key => $val): ?>
                <option value="<?= $key ?>" <? if ($item->object_class === $key) echo 'selected'; ?>>
                    <?= htmlReady($val['name']) ?>
                </option>
            <? endforeach; ?>
            </select>
        <? elseif ($item->object_type === 'inst'): ?>
            <?= _('Einrichtungstyp') ?>:

            <select name="object_class[]" id="object_class">
                <option value="NULL"><?= _('alle') ?></option>
            <? foreach ($GLOBALS['INST_TYPE'] as $key => $val): ?>
                <option value="<?= $key ?>" <? if ($item->object_class == $key) echo 'selected'; ?>>
                    <?= htmlReady($val['name']) ?>
                </option>
            <? endforeach; ?>
            </select>
        <? else: ?>
            <?= _('Nutzerstatus') ?>:

            <select multiple size="<?= count($controller->user_status) ?>" name="object_class[]" id="object_class" required>
                <option value="0" <? if ($item->object_class === null) echo 'selected'; ?>>
                    <?= _('alle') ?>
                </option>
            <? foreach ($controller->user_status as $key => $value): ?>
                <option value="<?= $value ?>" <? if ($item->object_class & DataField::permMask($key)) echo 'selected'; ?>>
                    <?= $key ?>
                </option>
            <? endforeach; ?>
            </select>
        <? endif; ?>
        </label>

        <label>
            <?= _('Ben�tigter Status zum Bearbeiten') ?>

            <select name="edit_perms" id="edit_perms">
            <? foreach (array_keys($controller->user_status) as $key): ?>
                <option <? if ($item->edit_perms === $key) echo 'selected'; ?>>
                    <?= $key ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>

        <label>
            <?= _('Sichtbarkeit') ?> (<?= _('f�r andere') ?>)

            <select name="visibility_perms" id="visibility_perms">
                <option value="all" <? if ($item->view_perms == 'all') echo 'selected'; ?>>
                    <?= _('alle') ?>
                </option>
            <? foreach (array_keys($controller->user_status) as $key): ?>
                <option <? if ($item->view_perms === $key) echo 'selected'; ?>>
                    <?= $key ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>

    <? if ($item->object_type === 'user'): ?>
        <label>
            <?= _('Systemfeld') ?>
            <?= tooltipIcon(_('Nur f�r die Person selbst sichtbar, wenn der '
                            . 'ben�tigte Status zum Bearbeiten oder die '
                            . 'Sichtbarkeit ausreichend ist')) ?>

            <input type="hidden" name="system" value="0">
            <input type="checkbox" name="system" value="1"
                   <? if ($item->system) echo 'checked'; ?>>
        </label>
    <? endif; ?>

        <label>
            <?= _('Position') ?>

            <input type="text" name="priority" id="priority"
                   maxlength="10" size="5"
                   value="<?= $item->priority ?>">
           </td>
        </label>

    <? if ($item->object_type === 'sem'): ?>
        <label>
            <?= _('Eintrag verpflichtend') ?>:

            <input type="checkbox" name="is_required" id="is_required" value="1"
                   <? if ($item->is_required) echo 'checked'; ?>>
        </label>

        <label>
            <?= _('Beschreibung') ?>:

            <textarea name="description" id="description"><?= htmlReady($item->description) ?></textarea>
        </label>
    <? endif; ?>
    
    <? if ($item->object_type === 'user'): ?>
        <label>
            <?= _('M�gliche Bedingung f�r Anmelderegel') ?>:

            <input type="checkbox" name="is_userfilter" id="is_userfilter" value="1"
                   <? if ($item->is_userfilter) echo 'checked'; ?>>
        </label>
    <? endif; ?>
    </fieldset>

    <footer data-dialog-button>
        <?= Button::createAccept(_('�bernehmen'), 'uebernehmen', array('title' => _('�nderungen �bernehmen')))?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/datafields/index/'.$item->type.'#'.$item->type), array('title' => _('Zur�ck zur �bersicht')))?>
    </footer>
</form>
