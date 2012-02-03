<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<h2><?= _('Einen neuen Benutzer anlegen') ?></h2>

<form method="post" action="<?= $controller->url_for('admin/user/new') ?>">
<?= CSRFProtection::tokenTag() ?>
<table class="default">
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td width="25%">
            <?= _("Benutzername:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <input class="user_form" type="text" name="username" value="<?= $user['username'] ?>" required >
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("globaler Status:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <select class="user_form" name="perm" id="perm" onchange="jQuery('#admin_special').toggle( jQuery('#institut').val() != '0' && jQuery('#perm').val() == 'admin' )">
                <option>user</option>
                <option selected="selected">autor</option>
                <option>tutor</option>
                <option>dozent</option>
                <option>admin</option>
                <option>root</option>
            </select>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("Sichtbarkeit:") ?>
        </td>
        <td>
            <?= vis_chooser($user['visible'], true) ?>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("Vorname:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <input class="user_form" type="text" name="Vorname" value="<?= htmlReady($user['Vorname']) ?>" required>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("Nachname:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <input class="user_form" type="text" name="Nachname" value="<?= htmlReady($user['Nachname']) ?>" required>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("Geschlecht:") ?>
        </td>
        <td>
            <input id="unknown" type="radio"<?= (!$user['geschlecht']) ? ' checked' : '' ?> name="geschlecht" value="0">
            <label for="unknown"><?= _("unbekannt") ?></label>
            <input id="male" type="radio"<?= ($user['geschlecht'] == 1) ? ' checked' : '' ?> name="geschlecht" value="1">
            <label for="male"><?= _("männlich") ?></label>
            <input id="female" type="radio"<?= ($user['geschlecht'] == 2) ? ' checked' : '' ?> name="geschlecht" value="2">
            <label for="female"><?= _("weiblich") ?></label>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
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
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
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
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("E-Mail:") ?>
            <span style="color: red; font-size: 1.6em">*</span>
        </td>
        <td>
            <input class="user_form" type="email" name="Email" value="<?= htmlReady($user['Email']) ?>" required>
            <? if ($GLOBALS['MAIL_VALIDATE_BOX']) : ?>
                <input type="checkbox" id="disable_mail_host_check" name="disable_mail_host_check" value="1">
                <label for="disable_mail_host_check"><?= _("Mailboxüberprüfung deaktivieren") ?></label>
            <? endif ?>
        </td>
    </tr>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("Einrichtung:") ?>
        </td>
        <td>
            <select id="institut" class="user_form" name="institute" onchange="jQuery('#admin_special').toggle( jQuery('#institut').val() != '0' && jQuery('#perm').val() == 'admin')">
                <option value="0"><?= _("-- bitte Einrichtung auswählen --") ?></option>
                <? foreach ($faks as $fak) : ?>
                    <option value="<?= $fak['Institut_id'] ?>"<?= ($user['inst'] == $fak['Institut_id']) ? 'selected' : '' ?><?= ($fak['is_fak']) ? 'style="font-weight: bold;"' : '' ?>><?= htmlReady($fak['Name']) ?></option>
                    <? foreach ($fak['institutes'] as $institute) : ?>
                    <option value="<?= $institute['Institut_id'] ?>"<?= ($user['inst'] == $institute['Institut_id']) ? 'selected' : '' ?>>&nbsp;&nbsp;&nbsp;<?= htmlReady($institute['Name']) ?></option>
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
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <?= _("Nutzerdomäne:") ?>
        </td>
        <td>
            <select class="user_form" name="select_dom_id">
                <option value="0"><?= _("-- bitte Nutzerdomäne auswählen --") ?></option>
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
