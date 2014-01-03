<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<h2><?= _('Einen neuen Benutzer anlegen') ?></h2>

<form method="post" action="<?= $controller->url_for('admin/user/new/' . $prelim) ?>">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td width="25%">
            <?= _("Benutzername:") ?>
            <? if (!$prelim) : ?>
                <span style="color: red; font-size: 1.6em">*</span>
            <? endif ?>
        </td>
        <td>
            <input class="user_form" type="text" name="username" value="<?= $user['username'] ?>" <?= (!$prelim ? 'required' : '')?> >
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("globaler Status:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <select class="user_form" name="perm" id="perm" onchange="jQuery('#admin_special').toggle( jQuery('#institut').val() != '0' && jQuery('#perm').val() == 'admin' )">
                <option <? if ($user['perm'] == 'user') echo 'selected'; ?>>user</option>
                <option <? if (!$user['perm'] || $user['perm'] == 'autor') echo 'selected'; ?>>autor</option>
                <option <? if ($user['perm'] == 'tutor') echo 'selected'; ?>>tutor</option>
                <option <? if ($user['perm'] == 'dozent') echo 'selected'; ?>>dozent</option>
                <? if (!$prelim) : ?>
                    <option <? if ($user['perm'] == 'admin') echo 'selected'; ?>>admin</option>
                    <option <? if ($user['perm'] == 'root') echo 'selected'; ?>>root</option>
                <? endif ?>
            </select>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Sichtbarkeit:") ?>
        </td>
        <td>
        <? if (!$prelim) : ?>
            <?= vis_chooser($user['visible'], true) ?>
        <? else : ?>
            <?= _("niemals") ?>
        <? endif ?>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Vorname:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <input class="user_form" type="text" name="Vorname" value="<?= htmlReady($user['Vorname']) ?>" required>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Nachname:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <input class="user_form" type="text" name="Nachname" value="<?= htmlReady($user['Nachname']) ?>" required>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Geschlecht:") ?>
        </td>
        <td>
            <input id="unknown" type="radio"<?= (!$user['geschlecht']) ? ' checked' : '' ?> name="geschlecht" value="0">
            <label for="unknown"><?= _("unbekannt") ?></label>
            <input id="male" type="radio"<?= ($user['geschlecht'] == 1) ? ' checked' : '' ?> name="geschlecht" value="1">
            <label for="male"><?= _("m�nnlich") ?></label>
            <input id="female" type="radio"<?= ($user['geschlecht'] == 2) ? ' checked' : '' ?> name="geschlecht" value="2">
            <label for="female"><?= _("weiblich") ?></label>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Titel:") ?>
        </td>
        <td>
            <select name="title_front_chooser" onchange="jQuery('input[name=title_front]').val( jQuery(this).val() );">
            <? foreach(get_config('TITLE_FRONT_TEMPLATE') as $title) : ?>
                <option value="<?= $title ?>" <?= ($title == $user['title_front']) ? 'selected' : '' ?>><?= $title ?></option>
            <? endforeach ?>
            </select>
            <input class="user_form" type="text" name="title_front" value="<?= htmlReady($user['title_front']) ?>">
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?=_("Titel nachgestellt:") ?>
        </td>
        <td>
            <select name="title_rear_chooser" onchange="jQuery('input[name=title_rear]').val( jQuery(this).val() );">
            <? foreach(get_config('TITLE_REAR_TEMPLATE') as $rtitle) : ?>
                <option value="<?= $rtitle ?>" <?= ($rtitle == $user['title_rear']) ? 'selected' : '' ?>><?= $rtitle ?></option>
            <? endforeach ?>
            </select>
            <input class="user_form" type="text" name="title_rear" value="<?= htmlReady($user['title_rear']) ?>">
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("E-Mail:") ?>
            <? if (!$prelim) : ?>
                <span style="color: red; font-size: 1.6em">*</span>
            <? endif ?>
        </td>
        <td>
            <input class="user_form" type="email" name="Email" value="<?= htmlReady($user['Email']) ?>" <?= (!$prelim ? 'required' : '')?>>
            <? if ($GLOBALS['MAIL_VALIDATE_BOX']) : ?>
                <input type="checkbox" id="disable_mail_host_check" name="disable_mail_host_check" value="1">
                <label for="disable_mail_host_check"><?= _("Mailbox�berpr�fung deaktivieren") ?></label>
            <? endif ?>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Einrichtung:") ?>
        </td>
        <td>
            <select id="institut" class="user_form" name="institute" onchange="jQuery('#admin_special').toggle( jQuery('#institut').val() != '0' && jQuery('#perm').val() == 'admin')">
                <option value="0"><?= _("-- bitte Einrichtung ausw�hlen --") ?></option>
                <? foreach ($faks as $fak) : ?>
                    <option value="<?= $fak['Institut_id'] ?>" <?= ($user['institute'] == $fak['Institut_id']) ? 'selected' : '' ?><?= ($fak['is_fak']) ? 'style="font-weight: bold;"' : '' ?>><?= htmlReady($fak['Name']) ?></option>
                    <? foreach ($fak['institutes'] as $institute) : ?>
                    <option value="<?= $institute['Institut_id'] ?>" <?= ($user['institute'] == $institute['Institut_id']) ? 'selected' : '' ?>>&nbsp;&nbsp;&nbsp;<?= htmlReady($institute['Name']) ?></option>
                    <? endforeach ?>
                <? endforeach ?>
            </select>
            <div style="display: none;" id="admin_special">
            <input type="checkbox" value="admin" name="enable_mail_admin" id="enable_mail_admin">
            <label for="enable_mail_admin"><?= _('Admins der Einrichtung benachrichtigen') ?></label><br>
            <input type="checkbox" value="dozent" name="enable_mail_dozent" id="enable_mail_dozent">
            <label for="enable_mail_dozent"><?= _('Dozenten der Einrichtung benachrichtigen') ?></label>
            </div>
        </td>
    </tr>
<? if (count($domains) > 0) : ?>
    <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
        <td>
            <?= _("Nutzerdom�ne:") ?>
        </td>
        <td>
            <select class="user_form" name="select_dom_id">
                <option value="0"><?= _("-- bitte Nutzerdom�ne ausw�hlen --") ?></option>
                <? foreach($domains as $domain) : ?>
                <option value="<?= $domain->getID() ?>"><?= $domain->getName() ?></option>
                <? endforeach ?>
            </select>
        </td>
    </tr>
<? endif ?>
    <tr>
        <td colspan="2" align="center">
            <?= Button::createAccept(_('Speichern'),'speichern', array('title' => _('Einen neuen Benutzer anlegen')))?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/user/?reset'), array('name' => 'abort'))?>
        </td>
    </tr>
</table>
</form>

<? //infobox

include '_infobox.php';

$infobox = array(
    'picture' => 'infobox/board1.jpg',
    'content' => array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag' => $aktionen
        ),
        array("kategorie" => _("Informationen:"),
              "eintrag"   =>
            array(
                array(
                      "icon" => "icons/16/black/info.png",
                      "text" => _("Mit roten Sternchen markierte Felder sind Pflichtfelder.")
                )
            )
        )
    )
);
if ($prelim) {
    $infobox['content'][1]['eintrag'][] = array(
                      "icon" => "icons/16/black/info.png",
                      "text" => _("Sie erstellen einen vorl�ufigen Benutzer. Vorl�ufige Benutzer k�nnen sich nicht anmelden und sind nicht �ffentlich sichtbar.")
                );
}
