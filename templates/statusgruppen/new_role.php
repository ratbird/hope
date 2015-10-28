<?
# Lifter010: TODO
    use Studip\Button, Studip\LinkButton;
?>
<tr>
    <td colspan="5" class="blank">
        <form action="<?= URLHelper::getLink('#'. $role_data['id']) ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <table class="default">
            <colgroup>
                <col width="30%">
                <col width="70%">
            </colgroup>
            <thead>
                <tr>
                    <th class="printhead" colspan="2">
                        <?= _("Neue Gruppe anlegen") ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= _("Gruppenname") ?>:</font>
                    </td>
                    <td>
                        <input type="text" name="new_name" value="<?= htmlReady($role_data['name']) ?>">
                        <?= _("oder Vorlage") ?>:
                        <select name="presetName">
                            <option value="none"> -- <?= _("wählen") ?> -- </option>
                        <? for ($i = 0; $i < sizeof($GLOBALS['INST_STATUS_GROUPS']["default"]); $i++) : ?>
                            <option><?= $GLOBALS['INST_STATUS_GROUPS']["default"][$i] ?></option>
                        <? endfor; ?>
                        </select>
                    </td>
                </tr>
                <? if ($range_type != 'sem') : ?>
                <tr>
                    <td><?= _("Übergeordnete Gruppe") ?>:</td>
                    <td>
                        <select name="vather">
                            <option value="root"> -- <?= _("Hauptebene") ?> -- </option>
                            <? Statusgruppe::displayOptionsForRoles($all_roles); ?>
                        </select>
                    </td>
                </tr>
                <? endif; ?>

                <tr>
                    <td>
                        <?= _("Gruppengröße") ?>:
                        <?=tooltipicon(_("Mit dem Feld 'Gruppengröße' haben Sie die Möglichkeit, die Sollstärke für eine Gruppe festzulegen. Dieser Wert ist nur aus Teilnehmersicht relevant - verantwortliche Personen (Tutoren, Lehrende) können auch mehr Gruppenmitglieder eintragen.")) ?>
                    </td>
                    <td>
                        <input type="text" name="new_size" value="<?= $role_data['size'] ?>">
                    </td>
                </tr>

                <? if ($range_type == 'sem') : ?>
                <tr>
                    <td>
                        <?=_("Selbsteintrag") ?>:
                    </td>
                    <td>
                        <input type="checkbox" name="new_selfassign" value="1" <?= $role_data['selfassign']? 'checked' : '' ?>>
                        <input type="hidden" name="vather" value="root">
                    </td>
                </tr>

                <tr>
                    <td>
                        <?=_("Gruppenordner:") ?>:
                    </td>
                    <td>
                        <input type="checkbox" name="groupfolder" value="1">
                    </td>
                </tr>

                <? endif; ?>

                <? if ($range_type != 'sem' && is_array($role_data['datafields'])) foreach ($role_data['datafields'] as $field) : ?>
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
                    <td class="blank" align="center" colspan="2">
                        <br>

                        <?= Button::createAccept(_('Speichern'), 'speichern') ?>
                        &nbsp;
                        <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('', compact('range_id'))) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <input type="hidden" name="cmd" value="addRole">
        <input type="hidden" name="role_id" value="<?= $role->getId() ?>">
        <input type="hidden" name="range_id" value="<?= $range_id ?>">
        </form>
    </td>
</tr>
