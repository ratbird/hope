<? use Studip\Button; ?>

<? if ($locked_info): ?>
    <?= MessageBox::info(formatLinks($locked_info)) ?>
<? endif; ?>

<form id="edit_private" action="<?= $controller->url_for('settings/details/store') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="zebra-hover settings">
        <colgroup>
            <col width="33%">
            <col width="33%">
            <col width="33%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="3">
                    <?= sprintf(_('Lebenslauf von %s %s (%s - Status: %s) bearbeiten'),
                        htmlReady($this->user->Vorname),
                        htmlReady($this->user->Nachname),
                        $this->user->username,
                        $this->user->perms) ?>                
                </th>
            </tr>
        </thead>
        <tbody class="labeled maxed">
            <tr>
                <td><?= _('Telefon (privat):') ?></td>
                <td>
                    <label>
                        <?= _('Festnetz') ?>:<br>
                        <input type="tel" name="telefon"
                               value="<?= htmlReady($user->privatnr) ?>"
                               <? if (!$controller->shallChange('user_info.privatnr')) echo 'disabled'; ?>>
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('Mobiltelefon') ?>:<br>
                        <input type="tel" name="cell"
                               value="<?= htmlReady($user->privatcell) ?>"
                               <? if (!$controller->shallChange('user_info.privatcell')) echo 'disabled'; ?>>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="private_address">
                        <?= _('Adresse (privat):') ?>
                    </label>
                </td>
                <td colspan="2">
                    <input type="text" name="anschrift" id="private_address" 
                           value="<?= htmlReady($user->privadr) ?>"
                           <? if (!$controller->shallChange('user_info.privadr')) echo 'disabled'; ?>>
                </td>
            </tr>
        <? if ($GLOBALS['ENABLE_SKYPE_INFO']): ?>
            <tr>
                <td><?= _('Skype:') ?></td>
                <td>
                    <label>
                        <?= _('Skype Name:') ?><br>
                        <input type="text" name="skype_name"
                               value="<?= htmlReady($config->SKYPE_NAME) ?>">
                    </label>
                </td>
                <td>
                    <label>
                        <?= _('Skype Online Status anzeigen:') ?><br>
                        <input type="checkbox" name="skype_online_status" value="1"
                               <? if ($config->SKYPE_ONLINE_STATUS) echo 'checked'; ?>>
                    </label>
                </td>
            </tr>
        <? endif; ?>
            <tr>
                <td>
                    <label for="motto"><?= _('Motto:') ?></label>
                </td>
                <td colspan="2">
                    <input type="text" name="motto" id="motto"
                           value="<?= htmlReady($user->motto) ?>"
                           <? if (!$controller->shallChange('user_info.motto')) echo 'disabled'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="homepage"><?= _('Homepage:') ?></label>
                </td>
                <td colspan="2">
                    <input type="url" name="home" id="homepage"
                           value="<?= htmlReady($user->Home) ?>"
                           <? if (!$controller->shallChange('user_info.Home')) echo 'disabled'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="hobbies"><?= _('Hobbys:') ?></label>
                </td>
                <td colspan="2">
                    <textarea name="hobby" id="hobbies" style="height:100px;"
                              <? if (!$controller->shallChange('user_info.hobby')) echo 'disabled'; ?>
                    ><?= htmlReady($user->hobby) ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <a name="lebenslauf"></a>
                    <label for="lebenslauf"><?= _('Lebenslauf:') ?></label>
                </td>
                <td colspan="2">
                    <textarea id="lebenslauf" name="lebenslauf" style="height:100px;"
                              <? if (!$controller->shallChange('user_info.lebenslauf')) echo 'disabled'; ?>
                    ><?= htmlReady($user->lebenslauf) ?></textarea>
                </td>
            </tr>
        <? if ($is_dozent): ?>
            <tr>
                <td>
                    <a name="schwerpunkte"></a>
                    <label for="schwerp"><?= _('Schwerpunkte:') ?></label>
                </td>
                <td colspan="2">
                    <textarea id="schwerp" name="schwerp" style="height:100px;"
                              <? if (!$controller->shallChange('user_info.schwerp')) echo 'disabled'; ?>
                    ><?= htmlReady($user->schwerp) ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <a name="publikationen"></a>
                    <label for="publi"><?= _('Publikationen:') ?></label>
                </td>
                <td colspan="2">
                    <textarea id="publi" name="publi" style="height:100px;"
                              <? if (!$controller->shallChange('user_info.publi')) echo 'disabled'; ?>
                    ><?= htmlReady($user->publi) ?></textarea>
                </td>
            </tr>
        <? endif; ?>
        <? foreach ($user_entries as $id => $entry): ?>
            <tr>
            <? if (isset($invalid_entries[$id])): ?>
                <td style="color:#f00;">
                <? $entry = $invalid_entries[$id]; // Exchange entry ?>
            <? else: ?>
                <td>
            <? endif; ?>
                    <?= htmlReady($entry->getName()) ?>
                </td>
                <td colspan="2">
                <? if ($entry->isEditable() && !LockRules::check($user->user_id, $entry->getId())): ?>
                    <?= $entry->getHTML('datafields') ?>
                <? else: ?>
                    <?= formatReady($entry->getDisplayValue(false)) ?><br>
                    <hr style="background: #888; border: 0; color: #888; height: 1px; ">
                    <?= _('(Das Feld ist für die Bearbeitung gesperrt und kann '
                         .'nur durch einen Administrator verändert werden.)') ?>
                <? endif; ?>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <?= Button::create(_('Übernehmen'), 'store', array('title' => _('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
