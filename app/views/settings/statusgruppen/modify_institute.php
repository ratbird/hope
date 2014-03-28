<? 
    use Studip\Button, Studip\LinkButton;

    // Datenfelder f�r Rollen in Einrichtungen ausgeben
    // Default-Daten der Einrichtung
    $entries = (array)DataFieldEntry::getDataFieldEntries(array($user->user_id, $inst_id), 'userinstrole')
?>

<tr>
    <td class="<?= $followers ? 'in-between' : 'blank'; ?>">&nbsp;</td>
    <td colspan="2" class="centered">

        <br>
        <form action="<?= $controller->url_for('settings/statusgruppen/store/institute', $inst_id) ?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="name" value="<?= htmlReady($institute['Name']) ?>">

            <table class="default settings" style="width:90%">
                <colgroup>
                    <col width="50%">
                    <col width="50%">
                </colgroup>
                <thead>
                    <tr>
                        <th colspan="2">
                            <?= _('Einrichtungsdaten') ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="maxed">
                    <tr>
                        <td>
                            <label><?= _('Status') ?>:</label>
                        </td>
                        <td>
                        <? if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id) && $institute['inst_perms'] != 'admin' && !$locked): ?>
                            <select name="status">
                            <? foreach ($about->allowedInstitutePerms() as $cur_status): ?>
                                <option <? if ($cur_status == $institute['inst_perms']) echo 'selected'; ?>><?= $cur_status ?></option>
                            <? endforeach; ?>
                            </select>
                        <? else: ?>
                            <?= ucfirst($institute['inst_perms']) ?>
                        <? endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="raum"><?= _('Raum:') ?></label>
                        </td>
                        <td>
                            <input type="text" name="raum" id="raum"
                                   value="<?= htmlReady($institute['raum']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="sprech"><?= _('Sprechzeit:') ?></label>
                        </td>
                        <td>
                            <input type="text" name="sprech" id="sprech"
                                   value="<?= htmlReady($institute['sprechzeiten']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><?= _('Telefon:') ?></label>
                        </td>
                        <td>
                            <input type="tel" name="tel"
                                   value="<?= htmlReady($institute['Telefon']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><?= _('Fax:') ?></label>
                        </td>
                        <td>
                            <input type="tel" name="fax"
                                   value="<?= htmlReady($institute['Fax']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                <? foreach ($entries as $id => $entry): ?>
                    <tr>
                        <td>
                            <label><?= $entry->getName() ?>:</label>
                        </td>
                        <td>
                        <? if ($locked): ?>
                            <?= $entry->getDisplayValue() ?>
                        <? else: ?>
                            <?= $entry->getHTML('datafields') ?>
                        <? endif; ?>
                        </td>
                    </tr>
                <? endforeach; ?>
                    <tr>
                        <td>
                            <label for="default_institute">
                                <?= _('Standard-Adresse:') ?>
                            </label>
                            <dfn>
                                <?= _('Angaben, die im Adressbuch und auf den externen '
                                     .'Seiten als Standard benutzt werden.') ?>
                            </dfn>
                        </td>
                        <td>
                        <? if ($institute['externdefault']) : ?>
                            <?= Assets::img('icons/16/grey/accept', array('class' => 'text-top'));?>
                            <input type="hidden" name="default_institute" value="1">
                        <? else : ?>
                            <input type="checkbox" id="default_institute" name="default_institute" value="1"
                                   <? if ($institute['externdefault']) echo 'checked'; ?>>
                        <? endif; ?>
                        </td>
                    </tr>
                        <td>
                            <label for="invisible">
                                <?= _('Einrichtung nicht auf der Profilseite:'); ?>
                            </label>
                            <dfn>
                                <?= _('Die Angaben zu dieser Einrichtung werden nicht '
                                      .'auf Ihrer Profilseite und in Adressb�chern ausgegeben.') ?>
                            </dfn>
                        </td>
                        <td>
                            <input type="checkbox" name="invisible" id="invisible" value="1"
                                   <? if ($institute['visible'] != 1) echo 'checked'; ?>>
                        </td>
                    </tr>
                </tbody>
                <tfoot style="text-align: center;">
                    <tr>
                        <td colspan="2">
                            <?= Button::createAccept(_('�nderungen speichern'), 'speichern') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
        <br>

    </td>
</tr>
