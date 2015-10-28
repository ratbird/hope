<?
# Lifter010: TEST
use Studip\Button, Studip\LinkButton;
$group_data = $role->getData();
?>
<tr>
    <td colspan="5" class="printcontent">
        <form action="<?= URLHelper::getLink('#'. $role->getId()) ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <table class="default">
            <colgroup>
                <col width="50%">
                <col width="50%">
            </colgroup>
            <tbody>
                <tr>
                    <td>
                        <label for="new_name"><?= _("Gruppenname") ?>:</label>
                    </td>
                    <td>
                        <input type="text" name="new_name" id="new_name"
                               value="<?=htmlReady($group_data['name'])?>">
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="vather"><?= _("Übergeordnete Gruppe") ?>:</label>
                    </td>
                    <td>
                        <select name="vather" id="vather">
                            <option value="nochange"> -- <?= _("Keine Änderung") ?> -- </option>
                            <option value="root"> -- <?= _("Hauptebene") ?> -- </option>
                            <? Statusgruppe::displayOptionsForRoles($all_roles, $role->getId()); ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="new_size"><?= _("Gruppengröße") ?>:</label>
                        <?=tooltipicon(_("Mit dem Feld 'Gruppengröße' haben Sie die Möglichkeit, die Sollstärke für eine Gruppe festzulegen. Dieser Wert ist nur aus Teilnehmersicht relevant - verantwortliche Personen (Tutoren, Lehrende) können auch mehr Gruppenmitglieder eintragen.")) ?>
                    </td>
                    <td>
                        <input type="text" name="new_size" id="new_size"
                               value="<?=$group_data['size']?>"><br>
                    </td>
                </tr>

                <? if (is_array($group_data['datafields'])) foreach ($group_data['datafields'] as $field) : ?>
                <tr>
                    <td <?= $field['invalid'] ? 'style="color: red; font-weight: bold;"' : '' ?>>
                        <?=$field['name']?>
                    </td>
                    <td>
                        <?=$field['html']?>
                    </td>
                </tr>
                <? endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="table_row_even" align="center" colspan="2">
                        <?= Button::createAccept(_('Speichern'), 'speichern') ?>
                        <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('', array('role_id' => $role->getId())) . '#' . $role->getId()) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <input type="hidden" name="view" value="editRole">
        <input type="hidden" name="cmd" value="editRole">
        <input type="hidden" name="role_id" value="<?= $role->getId() ?>">
        </form>
    </td>
</tr>
