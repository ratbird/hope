<?php
# Lifter002: TODO
# Lifter007: TODO
/*
admin_user_kill.php
Copyright (C) 2005 André Noack <noack@data-quest.de>
Suchi & Berg GmbH <info@data-quest.de>
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA   02111-1307, USA.
*/



require '../lib/bootstrap.php';

require_once 'lib/classes/UserManagement.class.php';
require_once 'vendor/email_message/blackhole_message.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");
unregister_globals();

$_kill_user =& $_SESSION['_kill_user'];

$db = DBManager::get();

include ('lib/seminar_open.php');       // initialise Stud.IP-Session

//-- hier muessen Seiten-Initialisierungen passieren --
if (Request::submitted('cancel')){
    $_kill_user = array();
}

if (Request::int('transfer_search') && strlen($_SESSION['pers_browse_search_string'])) {
    $rs = $db->query("SELECT auth_user_md5.*, changed, mkdate FROM auth_user_md5 LEFT JOIN ".$GLOBALS['user']->that->database_table." ON auth_user_md5.user_id = sid LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) " . $_SESSION['pers_browse_search_string']);
    $_kill_user = array();
    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
        $_kill_user[$row['username']] = $row;
        $_kill_user[$row['username']]['selected'] = true;
    }
    $_kill_user = array_filter($_kill_user, 'is_array');
    $msg[] = MessageBox::info(sprintf(_("%s Benutzer gefunden"), count($_kill_user)));
}

elseif (Request::submitted('userlist_submit') && !Request::submitted('cancel') && trim(Request::get('kill_user_list'))) {
    $_kill_user = preg_split("/[\s,;]+/", Request::get('kill_user_list'), -1, PREG_SPLIT_NO_EMPTY);
    $_kill_user = array_flip($_kill_user);
    $rs = $db->query("SELECT * FROM auth_user_md5 WHERE username IN (".join(",", array_map(array($db, 'quote'), array_keys($_kill_user))).")");
    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
        $_kill_user[$row['username']] = $row;
        $_kill_user[$row['username']]['selected'] = true;
    }
    $_kill_user = array_filter($_kill_user, 'is_array');
    $msg[] = MessageBox::info(sprintf(_("%s Benutzer gefunden"), count($_kill_user)));
}

elseif (Request::submitted('kill_accounts') && check_ticket(Request::option('studipticket'))) {
    $umanager = new UserManagement();
    $selected_user = Request::getArray('selected_user');
    $dev_null = new blackhole_message_class();
    $default_mailer = StudipMail::getDefaultTransporter();
    if (!Request::int('send_email')) {
        StudipMail::setDefaultTransporter($dev_null);
    }
    foreach($_kill_user as $uname => $udetail) {
        if (isset($selected_user[$uname])) {
            $umanager->user_data = array();
            $umanager->msg = '';
            $umanager->getFromDatabase($udetail['user_id']);

            if ($umanager->deleteUser(Request::int('delete_documents'))) {
                $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1)));
                $msg[] = MessageBox::success(sprintf(_("Der Benutzer <em>%s</em> wurde gelöscht."), $uname), $details);
                unset($_kill_user[$uname]);
            } else {
                $details = explode('§', str_replace(array('msg§', 'info§', 'error§'), '', substr($umanager->msg, 0, -1)));
                $msg[] = MessageBox::error(sprintf(_("Fehler! Der Benutzer <em>%s</em> konnte nicht gelöscht werden."), $uname), $details);
            }
        } else {
            $_kill_user[$uname]['selected'] = false;
        }
    }
    StudipMail::setDefaultTransporter($default_mailer);
}

ob_start();
PageLayout::setHelpKeyword("Admins.Benutzerkonten");
PageLayout::setTitle(_("Löschen von Benutzer-Accounts"));
Navigation::activateItem('/admin/config/new_user');

if (count($msg) > 0) {
    foreach ($msg as $message) {
        echo $message;
    }
}
?>
<div style="padding-left: 1em;padding-right:1em;">
<form action="<?=UrlHelper::getLink('?userlist_submit=1')?>" method="POST">
<?=_("Geben Sie eine Liste von Nutzernamen (username) ein, die zum Löschen vorgesehen sind. Die Namen können mit Komma, Semikolon, oder whitespaces getrennt sein.")?>
<br>
<br>
<textarea name="kill_user_list" rows="10" cols="80" wrap="virtual"><?=(is_array($_kill_user) ? join("\n", array_keys($_kill_user)): '')?></textarea>
<br>
<br>
<?=MakeButton('absenden', "input", _("Namen überprüfen"), 'userlist_submit')?>
&nbsp;<?=MakeButton('zuruecksetzen', "input", _("Zurücksetzen"), 'cancel')?>
</form>
</div>
<?
if (count($_kill_user)) {
    echo chr(10).'<div style="padding-left: 1em;padding-right:1em;;">';
    echo chr(10).'<form name="admin_user_kill" method="POST" action="'.UrlHelper::getLink('', array('kill' => 1)).'">';
    echo chr(10).'<input type="hidden" name="studipticket" value="'.get_ticket().'">';
    echo chr(10).'<div style="text-align:right">';
    echo chr(10).'<img '.makeButton('auswahlumkehr','src').' '.tooltip(_("Auswahl umkehren")) .' onClick="$(\'input[name^=selected_user]\').attr(\'checked\', function (_, v) { return !v; })">';
    echo chr(10).'</div>';
    echo chr(10).'<table class="default">';
    echo chr(10).'<tr>';
    echo '<th>' . _("Benutzername") . '</th>';
    echo '<th>' . _("Name (status)") . '</th>';
    echo '<th>' . _("Email") . '</th>';
    echo '<th>' . _("Authentifizierung") . '</th>';
    echo '<th>' . _("Löschen?") . '</th>';
    echo '</tr>';
    foreach($_kill_user as $username => $userdetail){
        echo chr(10).'<tr  class="'.TextHelper::cycle('cycle_odd', 'cycle_even') .'">';
        echo chr(10).'<td style="font-weight:bold"><a href="'.UrlHelper::getLink('new_user_md5.php?details='.$username).'">'.$username . '</a></td>';
        echo chr(10).'<td>' . htmlReady($userdetail['Vorname'] . ' ' . $userdetail['Nachname'] . ' ('.$userdetail['perms'].')').'</td>';
        echo chr(10).'<td>' . htmlReady($userdetail['Email']) . '</td>';
        echo chr(10).'<td>' . htmlReady(is_null($userdetail['auth_plugin']) ? 'standard' : $userdetail['auth_plugin']) . '</td>';
        echo chr(10).'<td align="center">';
        echo chr(10).'<input type="checkbox" value="1" name="selected_user['.$username.']" ' .($userdetail['selected'] ? ' checked ' : '') .'></td>';
        echo chr(10).'</tr>';
    }
    ?>
<tr class="steel2">
    <td colspan="5" align="right">
    <div>
        <input style="vertical-align:middle" type="checkbox" checked name="delete_documents" id="delete_documents" value="1">
        <label for="delete_documents"><?=_("Dokumente der Nutzer löschen")?></label>
    </div>
    <div>
        <input style="vertical-align:middle" type="checkbox" checked name="send_email" id="send_email" value="1">
        <label for="send_email"><?=_("Benachrichtigung per Email verschicken")?></label>
    </div>
    <div>
        <?=makeButton('loeschen', "input",  _("Ausgewählte Nutzeraccounts löschen"), 'kill_accounts')?>
    </div>
    </td>
</tr>
    <?
    echo '</table></div></form>';
}

$layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');

$layout->content_for_layout = ob_get_clean();

echo $layout->render();
page_close();
