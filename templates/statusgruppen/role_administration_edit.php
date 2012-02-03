<?
# Lifter010: TODO
    use Studip\Button, Studip\LinkButton;
?>
<?
    $cssSw = new cssClassSwitcher();
    $cssSw->switchClass();
    $num = 0;
    $group_data = $role->getData();
?>
    <tr>
        <td colspan="5" class="printcontent">
            <form action="<?= URLHelper::getLink('#'. $role->getId()) ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
            <table cellspacing="0" cellpadding="1" border="0" width="100%">
                <tr>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <font size="-1">
                            <?= _("Gruppenname") ?>:
                        </font>
                    </td>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <font size="-1">
                            <input type="text" name="new_name" value="<?=htmlReady($group_data['name'])?>">
                    </font>
                    </td>
                </tr>
                <? $cssSw->switchClass() ?>

                <tr>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <font size="-1">
                            <?= _("Übergeordnete Gruppe") ?>:
                        </font>
                    </td>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <font size="-1">
                            <select name="vather">
                                <option value="nochange"> -- <?= _("Keine Änderung") ?> -- </option>
                                <option value="root"> -- <?= _("Hauptebene") ?> -- </option>
                                <? Statusgruppe::displayOptionsForRoles($all_roles, $role->getId()); ?>
                            </select>
                        </font>
                    </td>
                </tr>
                <? $cssSw->switchClass() ?>

                <tr>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <font size="-1">
                            <?= _("Gruppengröße") ?>:
                            &nbsp;<img style="cursor:pointer; vertical-align:bottom;" src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/grey/info-circle.png" <?=tooltip(_("Mit dem Feld 'Gruppengröße' haben Sie die Möglichkeit, die Sollstärke für eine Gruppe festzulegen. Dieser Wert wird nur für die Anzeige benutzt - es können auch mehr Personen eingetragen werden."), TRUE, TRUE)?>>
                        </font>
                    </td>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <input type="text" name="new_size" value="<?=$group_data['size']?>"><br>
                    </td>
                </tr>

                <? if (is_array($group_data['datafields'])) foreach ($group_data['datafields'] as $field) : ?>
                <? $cssSw->switchClass() ?>
                <tr>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <?=$field['invalid']?'<font color="red" size="-1"><b>':'<font size="-1">'?>
                        <?=$field['name']?>
                        <?=$field['invalid']?'</b></font>':'</font>'?>
                    </td>
                    <td class="<?= $cssSw->getClass() ?>" width="50%" nowrap>
                        <font size="-1">
                            <?=$field['html']?>
                        </font>
                    </td>
                </tr>
                <? endforeach; ?>
                <tr>
                    <td class="steel1" align="right" colspan="2">
                        <br>
                        
                        <?= LinkButton::create('<< ' . _('Zurück'), URLHelper::getURL('', array('role_id' => $role->getId())) . '#' . $role->getId()) ?>
                        <?= Button::createAccept(_('Speichern'), 'speichern') ?>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="view" value="editRole">
            <input type="hidden" name="cmd" value="editRole">
            <input type="hidden" name="role_id" value="<?= $role->getId() ?>">
            </form>
        </td>
    </tr>
