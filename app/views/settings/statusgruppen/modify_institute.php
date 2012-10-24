<? 
    use Studip\Button, Studip\LinkButton;

    // Datenfelder für Rollen in Einrichtungen ausgeben
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

            <table class="default zebra settings" style="width:90%">
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
                <tbody class="labeled maxed">
                    <tr>
                        <td><?= _('Status') ?>:</td>
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
                        <td><?= _('Raum:') ?></td>
                        <td>
                            <input type="text" name="raum"
                                   value="<?= htmlReady($institute['raum']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td><?= _('Sprechzeit:') ?></td>
                        <td>
                            <input type="text" name="sprech"
                                   value="<?= htmlReady($institute['sprechzeiten']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td><?= _('Telefon:') ?></td>
                        <td>
                            <input type="tel" name="tel"
                                   value="<?= htmlReady($institute['Telefon']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                    <tr>
                        <td><?= _('Fax:') ?></td>
                        <td>
                            <input type="tel" name="fax"
                                   value="<?= htmlReady($institute['Fax']) ?>"
                                   <? if ($locked) echo 'disabled'; ?>>
                        </td>
                    </tr>
                <? foreach ($entries as $id => $entry): ?>
                    <tr>
                        <td><?= $entry->getName() ?>:</td>
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
                                      .'auf Ihrer Profilseite und in Adressbüchern ausgegeben.') ?>
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
                            <?= Button::createAccept(_('Änderungen speichern'), 'speichern') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
        <br>

    </td>
</tr>
