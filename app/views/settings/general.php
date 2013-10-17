<? use Studip\Button; ?>

<?
$start_pages = array(
    '' => _('keine'),
     1 => _('Meine Veranstaltungen'),
     3 => _('Mein Stundenplan'),
     4 => _('Mein Adressbuch'),
     5 => _('Mein Planer'),
     6 => _('Mein globaler Blubberstream'),
);
?>

<form method="post" action="<?= $controller->url_for('settings/general/store') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">

    <table id="main_content" class="default">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <caption>
            <?= _('Allgemeine Einstellungen anpassen') ?>
        </caption>
        <tbody>
            <tr>
                <th colspan="2"><?= _('Allgemein') ?></th>
            </tr>
            <tr>
                <td>
                    <label for="forced_language"><?= _('Sprache') ?></label>
                </td>
                <td>
                    <select name="forced_language" id="forced_language">
                    <? foreach ($GLOBALS['INSTALLED_LANGUAGES'] as $key => $language): ?>
                        <option value="<?= $key ?>"
                                <? if ($user_language == $key) echo 'selected'; ?>>
                            <?= $language['name'] ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
        <? if (!$GLOBALS['perm']->have_perm('root')): ?>
            <tr>
                <td>
                    <label for="personal_startpage">
                        <?= _('Pers�nliche Startseite') ?><br>
                        <dfn id="personal_startpage_description">
                            <?= _('Sie k�nnen hier einstellen, welche Seite standardm��ig nach dem Einloggen '
                                 .'angezeigt wird. Wenn Sie zum Beispiel regelm��ig die Seite &raquo;Meine '
                                 .'Veranstaltungen&laquo; nach dem Login aufrufen, so k�nnen Sie dies hier '
                                 .'direkt einstellen.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <select name="personal_startpage" id="personal_startpage" aria-describedby="personal_startpage_description">
                    <? foreach ($start_pages as $index => $label): ?>
                        <option value="<?= $index ?>" <? if ($config->PERSONAL_STARTPAGE == $index) echo 'selected'; ?>>
                            <?= htmlReady($label) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
        <? endif; ?>
            <tr>
                <td>
                    <label for="skiplinks_enable">
                        <?= _('Skiplinks einblenden') ?><br>
                        <dfn id="skiplinks_enable_description">
                            <?= _('Mit dieser Einstellung wird nach dem ersten Dr�cken der Tab-Taste eine '
                                 .'Liste mit Skiplinks eingeblendet, mit deren Hilfe Sie mit der Tastatur '
                                 .'schneller zu den Hauptinhaltsbereichen der Seite navigieren k�nnen. '
                                 .'Zus�tzlich wird der aktive Bereich einer Seite hervorgehoben.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="skiplinks_enable" id="skiplinks_enable"
                           aria-describedby="skiplinks_enable_description" value="1"
                           <? if ($config->SKIPLINKS_ENABLE) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="accesskey_enable">
                        <?= _('Tastenkombinationen f�r Hauptfunktionen') ?><br>
                        <dfn id="accesskey_enable_description">
                            <?= _('Mit dieser Einstellung k�nnen Sie f�r die meisten in der Kopfzeile '
                                 .'erreichbaren Hauptfunktionen eine Bedienung �ber Tastenkombinationen '
                                 .'aktivieren. <br>Die Tastenkombination wird im Tooltip des jeweiligen '
                                 .'Icons angezeigt.') ?>
                            <?= _('Diese kann f�r jeden Browser und jedes Betriebssystem unterschiedlich '
                                 .'sein (siehe <a href="http://en.wikipedia.org/wiki/Accesskey" '
                                 .'target="_blank"">Wikipedia</a>).') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="accesskey_enable" id="accesskey_enable"
                           aria-describedby="accesskey_enable_description" value="1"
                           <? if ($config->ACCESSKEY_ENABLE) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="showsem_enable">
                        <?= _('Semesteranzeige auf &raquo;Meine Veranstaltungen&laquo;');?><br>
                        <dfn id="showsem_enable_description">
                            <?= _('Mit dieser Einstellung k�nnen Sie auf der Seite &raquo;Meine '
                                .'Veranstaltungen&laquo; die Einblendung des Start- und Endsemesters '
                                .'hinter jeder Veranstaltung aktivieren.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="showsem_enable" id="showsem_enable"
                           aria-describedby="showsem_enable_description" value="1"
                           <? if ($config->SHOWSEM_ENABLE) echo 'checked'; ?>>
                </td>
            </tr>
        </tbody>
        <? if (PersonalNotifications::isGloballyActivated()): ?>
        <tbody>
            <tr>
                <th colspan="2"><?= _('Benachrichtigungen') ?></th>
            </tr>
            <tr>
                <td>
                    <label for="personal_notifications_activated">
                        <?= _('Benachrichtigungen �ber Javascript') ?><br>
                        <dfn id="personal_notifications_activated_description">
                            <?= _('Hiermit wird in der Kopfzeile dargestellt, wenn es Benachrichtigungen f�r '
                                 .'Sie gibt. Die Benachrichtigungen werden auch angezeigt, wenn Sie nicht die '
                                 .'Seite neuladen.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="personal_notifications_activated" id="personal_notifications_activated"
                           aria-describedby="personal_notifications_activated_description" value="1"
                           <? if (PersonalNotifications::isActivated($user->user_id)) echo 'checked'; ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="personal_notifications_audio_activated">
                        <?= _('Audio-Feedback zu Benachrichtigungen') ?><br>
                        <dfn id="personal_notifications_audio_activated_description">
                            <?= _('Wenn eine neue Benachrichtigung f�r Sie reinkommt, ' .
                                  'werden Sie mittels eines kleinen Plopps dar�ber in Kenntnis gesetzt ' .
                                  '- auch wenn Sie gerade einen anderen Browsertab anschauen. Der Plopp ist ' .
                                  'nur zu h�ren, wenn Sie die Benachrichtigungen �ber Javascript aktiviert haben.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="personal_notifications_audio_activated" id="personal_notifications_audio_activated"
                           aria-describedby="personal_notifications_audio_activated_description" value="1"
                           <? if (PersonalNotifications::isAudioActivated($user->user_id)) echo 'checked'; ?>>
                </td>
            </tr>
        </tbody>
        <? endif; ?>
        <tfoot>
            <tr>
                <td class="table_row_odd" colspan="2" align="center">
                    <?= Button::create(_('�bernehmen'), 'submit', array('title' => _('�nderungen �bernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
