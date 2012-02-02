<?
# Lifter010: TODO
    use Studip\Button, Studip\LinkButton;
?>
<?
    $cssSw = new cssClassSwitcher();
    $num = 0;
?>
<tr>
    <td colspan="5" class="blank">
        <form action="<?= URLHelper::getLink('#'. $role_data['id']) ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <table cellspacing="0" cellpadding="1" border="0" width="100%">
            <tr>
                <td class="printhead" colspan="2">
                    &nbsp;<b><?= _("Neue Gruppe anlegen") ?></b>
                </td>
            </tr>
            <tr>
                <td class="<?= $cssSw->getClass() ?>" width="30%" nowrap>
                    <font size="-1">
                        <?= _("Gruppenname") ?>:
                    </font>
                </td>
                <td class="<?= $cssSw->getClass() ?>" width="70%" nowrap>
                    <font size="-1">
                        <input type="text" name="new_name" value="<?= htmlReady($role_data['name']) ?>">
                    </font>
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
            <? $cssSw->switchClass() ?>
            <tr>
                <td class="<?= $cssSw->getClass() ?>" width="30%" nowrap>
                    <font size="-1">
                        <?= _("Übergeordnete Gruppe") ?>:
                    </font>
                </td>
                <td class="<?= $cssSw->getClass() ?>" width="70%" nowrap>
                    <font size="-1">
                        <select name="vather">
                            <option value="root"> -- <?= _("Hauptebene") ?> -- </option>
                            <? Statusgruppe::displayOptionsForRoles($all_roles); ?>
                        </select>
                    </font>
                </td>
            </tr>
            <? endif; ?>

            <? $cssSw->switchClass() ?>
            <tr>
                <td class="<?= $cssSw->getClass() ?>" width="30%" nowrap>
                    <font size="-1">
                        <?= _("Gruppengröße") ?>:
                        &nbsp;<img style="cursor:pointer; vertical-align:bottom;" src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/grey/info-circle.png" <?=tooltip(_("Mit dem Feld 'Gruppengröße' haben Sie die Möglichkeit, die Sollstärke für eine Gruppe festzulegen. Dieser Wert wird nur für die Anzeige benutzt - es können auch mehr Personen eingetragen werden."), TRUE, TRUE)?>>
                    </font>
                </td>
                <td class="<?= $cssSw->getClass() ?>" width="70%" nowrap>
                    <input type="text" name="new_size" value="<?= $role_data['size'] ?>"><br>
                </td>
            </tr>

            <? if ($range_type == 'sem') : ?>
            <? $cssSw->switchClass() ?>
            <tr>
                <td class="<?= $cssSw->getClass() ?>" width="30%" nowrap>
                    <font size="-1">
                        <?=_("Selbsteintrag") ?>:
                    </font>
                </td>
                <td class="<?= $cssSw->getClass() ?>" width="70%" nowrap>
                    <input type="checkbox" name="new_selfassign" value="1" <?= $role_data['selfassign']? 'checked="checked"' : '' ?>>
                    <input type="hidden" name="vather" value="root">
                </td>
            </tr>

            <? $cssSw->switchClass() ?>
            <tr>
                <td class="<?= $cssSw->getClass() ?>" width="30%" nowrap>
                    <font size="-1">
                        <?=_("Gruppenordner:") ?>:
                    </font>
                </td>
                <td class="<?= $cssSw->getClass() ?>" width="70%" nowrap>
                    <input type="checkbox" name="groupfolder" value="1">
                </td>
            </tr>

            <? endif; ?>

            <? if ($range_type != 'sem' && is_array($role_data['datafields'])) foreach ($role_data['datafields'] as $field) : ?>
            <? $cssSw->switchClass() ?>
            <tr>
                <td class="<?= $cssSw->getClass() ?>" width="30%" nowrap>
                    <?=$field['invalid']?'<font color="red" size="-1"><b>':'<font size="-1">'?>
                    <?=$field['name']?>
                    <?=$field['invalid']?'</b></font>':'</font>'?>
                </td>
                <td class="<?= $cssSw->getClass() ?>" width="70%" nowrap>
                    <font size="-1">
                        <?=$field['html']?>
                    </font>
                </td>
            </tr>
            <? endforeach; ?>
            <tr>
                <td class="blank" align="right" colspan="2">
                    <br>
                    
                    <?= Button::createAccept(_('speichern'), 'speichern') ?>
                    &nbsp;
                    <?= LinkButton::createCancel(_('abbrechen'), URLHelper::getURL('', compact('range_id'))) ?>
                </td>
            </tr>
        </table>
        <input type="hidden" name="cmd" value="addRole">
        <input type="hidden" name="role_id" value="<?= $role->getId() ?>">
        <input type="hidden" name="range_id" value="<?= $range_id ?>">
        </form>
    </td>
</tr>
