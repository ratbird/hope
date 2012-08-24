<? use Studip\Button, Studip\LinkButton; ?>
<? if (empty($via_ajax)): ?>
<h2><?= _("Bearbeiten der Einrichtungsdaten") ?></h2>
<? endif; ?>

<form action="<?= $controller->url_for('admin/user/edit_institute/' . $user['user_id'] .'/' .$institute['Institut_id']) ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Status") ?>:</td>
            <td>
            <select name="inst_perms">
            <? foreach ($perms as $perm) : ?>
                <option value="<?= $perm ?>" <?= ($institute['inst_perms'] == $perm) ? 'selected' : '' ?>><?= $perm ?></option>
            <? endforeach ?>
            </select>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Raum") ?>:</td>
            <td><input class="user_form" type="text" name="raum" value="<?= htmlReady($institute['raum'])?>"></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Sprechzeiten") ?>:</td>
            <td><input class="user_form" type="text" name="sprechzeiten" value="<?= htmlReady($institute['sprechzeiten'])?>"></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Telefon") ?>:</td>
            <td><input class="user_form" type="tel" name="telefon" value="<?= htmlReady($institute['Telefon'])?>"></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Fax") ?>:</td>
            <td><input class="user_form" type="tel" name="fax" value="<?= htmlReady($institute['Fax'])?>"></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Standard-Adresse") ?>:</td>
            <td><input type="checkbox" name="externdefault" value="1" <?= ($institute['externdefault'])? 'checked' : '' ?>></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?= _("Auf der Profilseite und in Adressbüchern sichtbar") ?>:</td>
            <td><input type="checkbox" name="visible" value="1" <?= ($institute['visible'])? 'checked' : '' ?>></td>
        </tr>
<? if (count($datafields) > 0) : ?>
    <? foreach ($datafields as $entry) : ?>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td>
                <?= htmlReady($entry->getName()) ?>:
            </td>
            <td>
            <? if ($entry->isEditable()) : ?>
                <?= $entry->getHTML("datafields") ?>
            <? else : ?>
                <?= $entry->getDisplayValue() ?>
            <? endif ?>
            </td>
        </tr>
    <? endforeach ?>
<? endif ?>

        <tr class="table_footer">
            <td>&nbsp;</td>
            <td>
                <?= Button::createAccept(_('Übernehmen'), 'uebernehmen', array('title' => _('Änderungen übernehmen')))?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/user/edit/'.$user['user_id']), array('class' =>'cancel'))?>
            </td>
        </tr>
    </table>
</form>