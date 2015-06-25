<? use Studip\Button, Studip\LinkButton; ?>

<?
    $send_as_email = array(
        1 => _('nie'),
        2 => _('immer'),
        3 => _('wenn vom Absender gewünscht'),
    );
    $mail_formats = array(
        0 => _('Text'),
        1 => _('HTML'),
    );
?>

<? if ($verify_action === 'reset'): ?>
<?= $controller->verifyDialog(
        _('Durch das Zurücksetzen werden die persönliche Messaging-Einstellungen '
         .'auf die Startwerte zurückgesetzt und die persönlichen Nachrichten-Ordner '
         .'gelöscht. ' . "\n\n" . 'Nachrichten werden nicht entfernt.'),
        array('settings/messaging/reset/reset', true),
        array('settings/messaging')
    ) ?>
<? elseif ($verify_action === 'forward_receiver'): ?>
<?= $controller->verifyDialog(
        _('Wollen Sie wirklich die eingestellte Weiterleitung entfernen?'),
        array('settings/messaging/reset/forward_receiver', true),
        array('settings/messaging')
    ) ?>
<? endif; ?>

<form action="<?= $controller->url_for('settings/messaging') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table class="default" id="main_content">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <caption>
            <?= _('Einstellungen des Nachrichtensystems anpassen') ?>
        </caption>
        <tbody>
            <tr>
                <th colspan="2"><?= _('Nachrichten') ?></th>
            </tr>
            <tr>
                <td>
                    <label for="save_snd"><?= _('Gesendete Nachrichten im Postausgang speichern') ?></label>
                </td>
                <td>
                    <input type="checkbox" value="1" name="save_snd" id="save_snd"
                           <? if ($settings['save_snd'] == 1) echo 'checked'; ?>>
                </td>
            </tr>
        <? if ($GLOBALS['MESSAGING_FORWARD_AS_EMAIL']): ?>
            <tr>
                <td>
                    <label for="request_mail_forward"><?= _('Gesendete Nachrichten auch als E-Mail verschicken') ?></label>
                </td>
                <td>
                    <input type="checkbox" value="1" name="request_mail_forward" id="request_mail_forward"
                           <? if ($settings['request_mail_forward'] == 1) echo 'checked'; ?>>
                </td>
            </tr>
        <? endif ?>
            <tr>
                <td>
                    <label for="logout_markreaded"><?= _('Beim Logout alle Nachrichten als gelesen speichern') ?></label>
                </td>
                <td>
                    <input type="checkbox" value="1" name="logout_markreaded" id="logout_markreaded"
                           <? if ($settings['logout_markreaded'] == 1) echo 'checked'; ?>>
                </td>
            </tr>
        <? if ($GLOBALS['MESSAGING_FORWARD_AS_EMAIL']): ?>
            <tr>
                <td>
                    <label><?= _('Kopie empfangener Nachrichten an eigene E-Mail-Adresse schicken') ?></label>
                </td>
                <td>
                <? foreach ($send_as_email as $key => $label): ?>
                    <label>
                        <input type="radio" name="send_as_email" value="<?= $key ?>"
                               <? if (($user->email_forward ?: $GLOBALS['MESSAGING_FORWARD_DEFAULT']) == $key) echo 'checked'; ?>>
                        <?= htmlReady($label) ?>
                    </label>
                    <br>
                <? endforeach; ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label><?= _('E-Mail in folgendem Format versenden') ?></label>
                </td>
                <td>
                <? foreach ($mail_formats as $key => $label): ?>
                    <label>
                        <input type="radio" name="mail_format" value="<?= $key ?>"
                               <? if ($config->getValue('MAIL_AS_HTML') == $key) echo 'checked'; ?>>
                        <?= htmlReady($label) ?>
                    </label>
                    <br>
                <? endforeach; ?>
               </td>
             </tr>
        <? endif; ?>
            <tr>
                <td>
                    <label for="search_exp"><?= _('Weiterleitung empfangener Nachrichten') ?></label>
                </td>
                <td>
                <? if ($user->smsforward_rec):  // empfaenger ausgewaehlt ?>
                    <?= _('Empfänger') ?>
                    <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . get_username($user->smsforward_rec)) ?>">
                        <?= Avatar::getAvatar($user->smsforward_rec)->getImageTag(Avatar::SMALL) ?>
                        <?= get_fullname($user->smsforward_rec, 'full', true) ?>
                    </a>
                    <a href="<?= $controller->url_for('settings/messaging/verify/forward_receiver') ?>">
                        <?= Assets::img('icons/16/blue/trash.png') ?>
                    </a>
                    <br>
                    <label>
                        <input type="checkbox" value="1" name="smsforward_copy"
                               <? if ($user->smsforward_copy) echo 'checked'; ?>>
                        <?= _('Kopie im persönlichen Posteingang speichern.') ?>
                    </label>
                <? else: ?>
                    <?= QuickSearch::get('new_smsforward_rec', new StandardSearch('username'))->withButton()->render() ?>
                <? endif; ?>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2">
                    <?= _('Signatur') ?>
                </th>
            </tr>
            <tr>
                <td>
                    <label for="signature"><?= _('Signatur') ?></label>
                </td>
                <td>
                    <textarea class="add_toolbar wysiwyg" name="sms_sig" id="signature" aria-label="<?= _('Signatur') ?>" style="width: 100%;" rows="3"><?= wysiwygReady($settings['sms_sig']) ?></textarea>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2">
                    <?= _('Buddies/ Wer ist online?') ?>
                </th>
            </tr>
            <tr>
                <td>
                    <label for="online_format"><?= _('Formatierung der Namen auf &raquo;Wer ist Online?&laquo;');?></label>
                </td>
                <td>
                    <select name="online_format" id="online_format">
                    <? foreach ($GLOBALS['NAME_FORMAT_DESC'] as $key => $value): ?>
                        <option value="<?= $key ?>" <? if ($config->getValue('ONLINE_NAME_FORMAT') == $key) echo 'selected '; ?>>
                            <?= htmlReady($value) ?>
                        </option>
                    <? endforeach; ?>
                    </select>

                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">
                    <?= Button::createAccept(_('Übernehmen'), 'store', array('title' => _('Änderungen übernehmen'))) ?>
                    <?= LinkButton::create(_('Zurücksetzen'), $controller->url_for('settings/messaging/verify/reset'), array('title' => _('Einstellungen zurücksetzen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
