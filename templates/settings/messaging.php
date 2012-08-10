<? use Studip\Button; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
    <tr>
        <td class="blank" colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td class="blank" width="100%" colspan="2" align="center">

            <form action="<?=URLHelper::getLink('?messaging_cmd=change_view_insert') ?>" method="post">
                <?= CSRFProtection::tokenTag() ?>

                <table class="zebra settings" width="70%" align="center" cellpadding="8" cellspacing="0" border="0"  id="main_content">
                    <colgroup>
                        <col width="50%">
                        <col width="50%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><?= _('Option') ?></th>
                            <th><?= _('Auswahl') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <label for="opennew"><?= _('Neue Nachrichten immer aufgeklappt') ?></label>
                            </td>
                            <td>
                                <input type="checkbox" value="1" name="opennew" id="opennew"
                                       <? if ($settings['opennew'] == 1) echo 'checked'; ?>>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="openall"><?= _('Alle Nachrichten immer aufgeklappt') ?></label>
                            </td>
                            <td>
                                <input type="checkbox" value="1" name="openall" id="openall"
                                       <? if ($settings['openall'] == 1) echo 'checked'; ?>>
                            </td>
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
                                <label for="delete_messages_after_logout"><?= _('Beim Logout alle Nachrichten löschen') ?></label>
                                <div class="setting_info">(<?= _('davon ausgenommen sind geschützte Nachrichten') ?>)</div>
                            </td>
                            <td>
                                <input type="checkbox" value="1" name="delete_messages_after_logout" id="delete_messages_after_logout"
                                       <? if ($settings['delete_messages_after_logout'] == 1) echo 'checked'; ?>>
                            </td>
                        </tr>
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
                                <?= _('Kopie empfangener Nachrichten an eigene E-Mail-Adresse schicken') ?>
                            </td>
                            <td>
                            <? foreach ($send_as_email as $key => $label): ?>
                                <label>
                                    <input type="radio" name="send_as_email" value="<?= $key ?>"
                                           <? if ($email_forward == $key) echo 'checked'; ?>>
                                    <?= htmlReady($label) ?>
                                </label>
                                <br>
                            <? endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?= _('E-Mail in folgendem Format versenden') ?>
                            </td>
                            <td>
                            <? foreach ($mail_formats as $key => $label): ?>
                                <label>
                                    <input type="radio" name="mail_format" value="<?= $key ?>"
                                           <? if ($mail_format == $key) echo 'checked'; ?>>
                                    &nbsp;
                                    <?= htmlReady($label) ?>
                                </label>
                                <br>
                            <? endforeach; ?>
                           </td>
                         </tr>
                    <? endif; ?>
                        <tr>
                            <td>
                               <?= _('Umgang mit angeforderter Lesebestätigung') ?>
                            </td>
                            <td>
                            <? foreach ($confirmation_types as $key => $label): ?>
                                <label>
                                    <input type="radio" name="confirm_reading" value="<?= $key ?>"
                                           <? if ($settings['confirm_reading'] == $key) echo 'checked'; ?>>
                                    &nbsp;<?= htmlReady($label) ?>
                                </label>
                                <br>
                            <? endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="search_exp"><?= _('Weiterleitung empfangener Nachrichten') ?></label>
                            </td>
                            <td>
                            <? if ($smsforward['rec']):  // empfaenger ausgewaehlt ?>
                                &nbsp;<?= _('Empfänger:') ?>
                                <a href="<?= URLHelper::getLink('about.php?username=' . get_username($smsforward['rec'])) ?>">
                                    <?= get_fullname($smsforward['rec'], 'full', true) ?>
                                </a>
                                &nbsp;&nbsp;
                                <input type="image" name="del_forwardrec"
                                       src="<?= Assets::image_path('icons/16/blue/trash.png') ?>"
                                       <?= tooltip(_('Empfänger und Weiterleitung löschen.')) ?>>
                                &nbsp;
                                <input type="image" name="del_forwardrec"
                                       src="<?= Assets::image_path('icons/16/blue/search.png') ?>"
                                       <?= tooltip(_('Neuen Empfänger suchen.')) ?>>
                                <br>
                                <label>
                                    <input type="checkbox" value="1" name="smsforward_copy"
                                           <? if ($smsforward['copy']) echo 'checked'; ?>>
                                    <?= _('Kopie im persönlichen Posteingang speichern.') ?>
                                </label>
                            <? elseif ($matches === false): // kein empfaenger ausgewaehlt, keine suche ?>
                                <input type="text" name="search_exp" id="search_exp" size="30" value="">
                                <input type="image" name="gosearch" class="middle"
                                       src="<?= Assets::image_path('icons/16/blue/search.png') ?>"
                                       title="<?= _('Nach Empfänger suchen') ?>"
                                       border="0">
                            <? elseif (count($matches) === 0): // Keine Suchergebnisse ?>
                                <input type="image" name="reset_serach" class="text-top"
                                       src="<?= Assets::image_path('icons/16/blue/refresh.png') ?>"
                                       value="<?= _('Suche zurücksetzen') ?>"
                                       <?= tooltip(_('setzt die Suche zurück')) ?>>
                                &nbsp;<?= _('keine Treffer') ?>
                            <? else: // treffer auswählen ?>
                                <input type="image" name="add_smsforward_rec" border="0"
                                       src="<?= Assets::image_path('icons/16/blue/accept.png') ?>"
                                       value="<?= _('als Empfänger auswählen') ?>"
                                       <?= tooltip(_('als Empfänger weitergeleiteter Nachrichten eintragen')) ?>>
                                &nbsp;&nbsp;
                                <select size="1" name="smsforward_rec">
                                <? foreach ($matches as $match): ?>
                                    <option value="<?= htmlReady($match['username']) ?>">
                                        <?= htmlReady(my_substr($match['fullname'], 0, 35)) ?>
                                        (<?= htmlReady($match['username']) ?>)
                                        - <?= $match['perms'] ?>
                                    </option>
                                <? endforeach; ?>
                                </select>
                                <input type="image" name="reset_serach" class="text-top"
                                       src="<?= Assets::image_path('icons/16/blue/refresh.png') ?>"
                                       value="<?= _('Suche zurücksetzen')?>"
                                       <?= tooltip(_('setzt die Suche zurück')) ?>>
                            <? endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label for="timefilter"><?= _('Zeitfilter der Anzeige in Postein- bzw. ausgang') ?></label>
                            </td>
                            <td>
                                &nbsp;<select name="timefilter" id="timefilter">
                                <? foreach ($timefilters as $key => $label): ?>
                                    <option value="<?= $key ?>" <? if ($settings['timefilter'] == $key) echo 'selected'; ?>>
                                        <?= htmlReady($label) ?>
                                    </option>
                                <? endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?= _('Signatur gesendeten Nachrichten anhängen') ?>
                            </td>
                            <td>
                                <label>
                                    <input type="checkbox" value="1" name="addsignature"<? if ($settings['addsignature']) echo 'checked'; ?>>
                                    <?= _('Signatur anhängen') ?>
                                </label>
                                <br>
                                &nbsp;<textarea name="sms_sig" aria-label="<?= _('Signatur') ?>" rows="3" cols="30"><?= htmlready($settings['sms_sig']) ?></textarea>
                            </td>
                        </tr>
                    </tbody>
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <b><?= _('Stud.IP-Messenger') ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="start_messenger_at_startup"><?= _('Stud.IP-Messenger automatisch nach dem Login starten') ?></label>
                            </td>
                            <td>
                                <input type="checkbox" id="start_messenger_at_startup" name="start_messenger_at_startup"
                                       value="1"
                                       <? if ($settings['start_messenger_at_startup'] == 1) echo 'checked'; ?> >
                            </td>
                        </tr>
                    </tbody>
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <b><?= _('Buddies/ Wer ist online?') ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="online_format"><?= _('Formatierung der Namen auf &raquo;Wer ist Online?&laquo;');?></label>
                            </td>
                            <td>
                                <select name="online_format" id="online_format">
                                <? foreach ($GLOBALS['NAME_FORMAT_DESC'] as $key => $value): ?>
                                    <option value="<?= $key ?>" <? if ($name_format == $key) echo 'selected '; ?>>
                                        <?= htmlReady($value) ?>
                                    </option>
                                <? endforeach; ?>
                                </select>

                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="steelgraulight" colspan="2" align="center">
                                <input type="hidden" name="view" value="Messaging">
                                <?=Button::create(_('Übernehmen'), 'newmsgset', array('title' => _("Änderungen übernehmen")))?>
                                &nbsp;
                                <?=Button::create(_('Zurücksetzen'), 'set_msg_default', array('title' => _("Einstellungen zurücksetzen")))?>
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>

        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">&nbsp;</td>
    </tr>
</table>
