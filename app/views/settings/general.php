<? use Studip\Button; ?>

<?
$start_pages = array(
    '' => _('keine'),
     1 => _('Meine Veranstaltungen'),
     3 => _('Mein Stundenplan'),
     4 => _('Mein Adressbuch'),
     5 => _('Mein Planer'),
);
?>

<form method="post" action="<?= $controller->url_for('settings/general/store') ?>">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= get_ticket() ?>">
    
    <table id="main_content" class="zebra-hover settings">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>
        <thead>
            <tr>
                <th colspan="2"><?= _('Allgemeine Einstellungen anpassen') ?></th>
            </tr>
        </thead>
        <tbody>
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
                        <?= _('Persönliche Startseite') ?><br>
                        <dfn id="personal_startpage_description">
                            <?= _('Sie können hier einstellen, welche Seite standardmäßig nach dem Einloggen '
                                 .'angezeigt wird. Wenn Sie zum Beispiel regelmäßig die Seite &raquo;Meine '
                                 .'Veranstaltungen&laquo;. nach dem Login aufrufen, so können Sie dies hier '
                                 .'direkt einstellen.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <select name="personal_startpage" id="personal_startpage" aria-describedby="personal_startpage_description">
                    <? foreach ($start_pages as $index => $label): ?>
                        <option value="<?= $index ?>" <? if ($config->startpage_redirect == $index) echo 'selected'; ?>>
                            <?= htmlReady($label) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
        <? endif; ?>
        <? if (Config::get()->SKIPLINKS_ENABLE): ?>
            <tr>
                <td>
                    <label for="skiplinks_enable">
                        <?= _('Skiplinks einblenden') ?><br>
                        <dfn id="skiplinks_enable_description">
                            <?= _('Mit dieser Einstellung wird nach dem ersten Drücken der Tab-Taste eine '
                                 .'Liste mit Skiplinks eingeblendet, mit deren Hilfe Sie mit der Tastatur '
                                 .'schneller zu den Hauptinhaltsbereichen der Seite navigieren können. '
                                 .'Zusätzlich wird der aktive Bereich einer Seite hervorgehoben.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="skiplinks_enable" id="skiplinks_enable"
                           aria-describedby="skiplinks_enable_description" value="1"
                           <? if ($config->SKIPLINKS_ENABLE) echo 'checked'; ?>>
                </td>
            </tr>
        <? endif; ?>
        <? if (Config::get()->ACCESSKEY_ENABLE): ?>
            <tr>
                <td>
                    <label for="accesskey_enable">
                        <?= _('Tastenkombinationen für Hauptfunktionen') ?><br>
                        <dfn id="accesskey_enable_description">
                            <?= _('Mit dieser Einstellung können Sie für die meisten in der Kopfzeile '
                                 .'erreichbaren Hauptfunktionen eine Bedienung über Tastenkombinationen '
                                 .'aktivieren. <br>Die Tastenkombination wird im Tooltip des jeweiligen '
                                 .'Icons angezeigt.') ?>
                            <?= _('Diese kann für jeden Browser und jedes Betriebssystem unterschiedlich '
                                 .'sein (siehe <a href="http://en.wikipedia.org/wiki/Accesskey" '
                                 .'target="_blank"">Wikipedia</a>)') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="accesskey_enable" id="accesskey_enable"
                           aria-describedby="accesskey_enable_description" value="1"
                           <? if ($config->ACCESSKEY_ENABLE) echo 'checked'; ?>>
                </td>
            </tr>
        <? endif; ?>
            <tr>
                <td>
                    <label for="showsem_enable">
                        <?= _('Semesteranzeige auf &raquo;Meine Veranstaltungen&laquo;');?><br>
                        <dfn id="showsem_enable_description">
                            <?= _('Mit dieser Einstellung können Sie auf der Seite &raquo;Meine '
                                .'Veranstaltungen&laquo; die Einblendung des Start- und Endsemesters '
                                .'hinter jeder Veranstaltung aktivieren.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="showsem_enable" id="showsem_enable"
                           aria-describedby="showsem_enable_description" value="1"
                           <? if ($config->SHOWSEM_ENABLE) echo 'checked'; ?>
                </td>
            </tr>
        <? if (PersonalNotifications::isGloballyActivated()): ?>
            <tr>
                <td>
                    <label for="personal_notifications_activated">
                        <?= _('Benachrichtigungen über Javascript') ?><br>
                        <dfn id="personal_notifications_activated_description">
                            <?= _('Hiermit wird in der Kopfzeile dargestellt, wenn es Benachrichtigungen für '
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
                            <?= _('Wenn eine neue Benachrichtigung für Sie rein kommt, ' .
                                  'werden Sie mittels eines kleinen Plopps darüber in Kenntnis gesetzt ' .
                                  '- auch wenn Sie gerade einen anderen Browsertab anschauen. Der Plopp ist ' .
                                  'nur zu hören, wenn Sie die Benachrichtigungen über Javascript aktiviert haben.') ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="personal_notifications_audio_activated" id="personal_notifications_audio_activated"
                           aria-describedby="personal_notifications_audio_activated_description" value="1"
                           <? if (PersonalNotifications::isAudioActivated($user->user_id)) echo 'checked'; ?>>
                </td>
            </tr>
        <? endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="table_row_odd" colspan="2" align="center">
                    <?= Button::create(_('Übernehmen'), 'submit', array('title' => _('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
