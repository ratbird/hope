<?php
# Lifter001: TODO
# Lifter002: TODO
# Lifter003: TODO
# Lifter007: TODO
/*
new_user_md5.php - die globale Benutzerverwaltung von Stud.IP.
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check($GLOBALS['RESTRICTED_USER_MANAGEMENT'] ? 'root' : 'admin');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once 'lib/classes/MessageBox.class.php';
require_once 'config.inc.php'; // Wir brauchen den Namen der Uni
require_once 'lib/visual.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/classes/UserManagement.class.php';
require_once('lib/messaging.inc.php');


$cssSw = new cssClassSwitcher;

$CURRENT_PAGE = _("Benutzerverwaltung");
Navigation::activateItem('/admin/config/new_user');

//-- hier muessen Seiten-Initialisierungen passieren --

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');  //hier wird der "Kopf" nachgeladen


// Get a database connection
$db = new DB_Seminar;
$db2 = new DB_Seminar;

// User_config for expiration_date
$uc = new UserConfig();

// Check if there was a submission
if (check_ticket($_REQUEST['studipticket'])){
    if ($_REQUEST['disable_mail_host_check']) $GLOBALS['MAIL_VALIDATE_BOX'] = false;
    while ( is_array($_POST)
             && list($key, $val) = each($_POST)) {
        switch ($key) {

        // Create a new user
        case "create_x":

            $UserManagement = new UserManagement;

            if (!$title_front)
                $title_front = $title_front_chooser;
            if (!$title_rear)
                $title_rear = $title_rear_chooser;

            $newuser = array(   'auth_user_md5.username' => stripslashes(trim($username)),
                                                'auth_user_md5.Vorname' => stripslashes(trim($Vorname)),
                                                'auth_user_md5.Nachname' => stripslashes(trim($Nachname)),
                                                'auth_user_md5.Email' => stripslashes(trim($Email)),
                                                'auth_user_md5.perms' => implode($perms,","),
                                                'auth_user_md5.auth_plugin' => $auth_plugin,
                                                'auth_user_md5.visible' => $visible,
                                                'user_info.title_front' => stripslashes(trim($title_front)),
                                                'user_info.title_rear' => stripslashes(trim($title_rear)),
                                                'user_info.geschlecht' => stripslashes(trim($geschlecht)),
                                            );

            if($UserManagement->createNewUser($newuser)){
                if(isset($expiration_date) && $expiration_date != ''){
                    $a = explode(".",stripslashes(trim($expiration_date)));
                    if(!($timestamp = @mktime(0,0,0,$a[1],$a[0],$a[2]))){
                        $UserManagement->msg .= "error§" . _("Das Ablaufdatum wurde in einem falschen Format angegeben.") . "§";
                        break;
                    }else
                        $uc->setValue($timestamp,$UserManagement->user_data['auth_user_md5.user_id'],"EXPIRATION_DATE");
                }

                if ($_REQUEST['select_inst_id'] && $perm->have_studip_perm('admin', $_REQUEST['select_inst_id'])){
                    $db = new DB_Seminar();
                    $db->query(sprintf("SELECT Name, Institut_id FROM Institute WHERE Institut_id='%s'", $_REQUEST['select_inst_id']));
                    if($db->next_record()){
                        $inst_name = $db->f('Name');
                        log_event('INST_USER_ADD', $_REQUEST['select_inst_id'], $UserManagement->user_data['auth_user_md5.user_id'], $UserManagement->user_data['auth_user_md5.perms']);
                        $db->query(sprintf("INSERT INTO user_inst (user_id,Institut_id,inst_perms) VALUES ('%s','%s','%s')",
                        $UserManagement->user_data['auth_user_md5.user_id'], $_REQUEST['select_inst_id'], $UserManagement->user_data['auth_user_md5.perms']));
                        if ($db->affected_rows()){
                            if($_POST['enable_mail_admin'] == "admin" && $_POST['enable_mail_dozent'] == "dozent"){
                                $in = "'admin','dozent'";
                                $wem = "Admins und Dozenten";
                            }else if($_POST['enable_mail_admin'] == "admin"){
                                $in = "'admin'";
                                $wem = "Admins";
                            }else if($_POST['enable_mail_dozent'] == "dozent"){
                                $in = "'dozent'";
                                $wem = "Dozenten";
                            }
                            if($in != "" && $perms[0] == "admin"){
                                $i=0;
                                $notin = array();
                                $instname = htmlReady($inst_name);
                                $vorname = $UserManagement->user_data['auth_user_md5.Vorname'];
                                $nachname = $UserManagement->user_data['auth_user_md5.Nachname'];
                                
                                $db->query(sprintf("SELECT a.user_id,b.Vorname,b.Nachname,b.Email FROM user_inst a INNER JOIN auth_user_md5 b ON a.user_id = b.user_id WHERE a.Institut_id = '%s' AND a.inst_perms IN (%s) AND a.user_id != '%s' ",$_REQUEST['select_inst_id'],$in,$UserManagement->user_data['auth_user_md5.user_id']));
                                while($db->next_record()){
                                    $user_language = getUserLanguagePath($db->f('user_id'));
                                    include("locale/$user_language/LC_MAILS/new_admin_mail.inc.php");
                                    StudipMail::sendMessage($db->f('Email'), $subject, $mailbody);
                                    $notin[$i] = $db->f('user_id'); $i++;
                                }
                                if($in != "'dozent'"){
                                    //Noch ein paar Mails für die Fakultätsadmins
                                    $db->query(sprintf("SELECT a.user_id,b.Vorname,b.Nachname,b.Email FROM user_inst a INNER JOIN auth_user_md5 b ON a.user_id = b.user_id WHERE a.user_id NOT IN ('%s','%s') AND  a.Institut_id IN (SELECT fakultaets_id FROM Institute WHERE Institut_id = '%s' AND fakultaets_id !=  Institut_id) AND a.inst_perms = 'admin' AND a.user_id != '%s' ",implode("','",$notin),$UserManagement->user_data['auth_user_md5.user_id'],$_REQUEST['select_inst_id'],$UserManagement->user_data['auth_user_md5.user_id']));
                                    while($db->next_record()){
                                        $user_language = getUserLanguagePath($db->f('user_id'));
                                        include("locale/$user_language/LC_MAILS/new_admin_mail.inc.php");
                                        StudipMail::sendMessage($db->f('Email'), $subject, $mailbody);
                                        $i++;
                                    }
                                }
                                $UserManagement->msg .= "msg§" . sprintf(_("Es wurden ingesamt %s Mails an die %s der Einrichtung \"%s\" geschickt."),$i,$wem,htmlReady($inst_name)) . "§";
                            }
                            $UserManagement->msg .= "msg§" . sprintf(_("Benutzer in Einrichtung \"%s\" mit dem Status \"%s\" eingetragen."), htmlReady($inst_name), $UserManagement->user_data['auth_user_md5.perms']) . "§";
                        } else {
                            $UserManagement->msg .= "error§" . sprintf(_("Benutzer konnte nicht in  Einrichtung \"%s\" eingetragen werden."), htmlReady($inst_name)) . "§";
                        }
                    }
                }
                if ($_REQUEST['select_dom_id'] != '') {
                    $domain = new UserDomain($_REQUEST['select_dom_id']);
                    if ($perm->have_perm('root') || in_array($domain, UserDomain::getUserDomainsForUser($auth->auth["uid"]))) {
                        $domain->addUser($UserManagement->user_data['auth_user_md5.user_id']);
                        $UserManagement->msg .= "msg§" . sprintf(_("Benutzer wurde in Nutzerdomäne \"%s\" eingetragen." ) , htmlReady($domain->getName()));
                    } else {
                        $UserManagement->msg .= "error§" . sprintf(_("Benutzer konnte nicht in die Nutzerdomäne eingetragen werden."));
                    }
                }
                $_GET['details'] = $details = $UserManagement->user_data['auth_user_md5.username'];
            } else {
                $_GET['details'] = $details = '__';
            }

            break;


        // Change user parameters
        case "u_edit_x":

            $UserManagement = new UserManagement($u_id);

            $newuser = array();
            if (isset($username))
                $newuser['auth_user_md5.username'] = stripslashes(trim($username));
            if (isset($Vorname))
                $newuser['auth_user_md5.Vorname'] = stripslashes(trim($Vorname));
            if (isset($Nachname))
                $newuser['auth_user_md5.Nachname'] = stripslashes(trim($Nachname));
            if (isset($Email))
                $newuser['auth_user_md5.Email'] = stripslashes(trim($Email));
            if (isset($perms))
                $newuser['auth_user_md5.perms'] = implode($perms,",");
            if ($delete_val_key == "1")
                $newuser['auth_user_md5.validation_key'] = '';
            $newuser['auth_user_md5.locked']     = (isset($locked) ? $locked : 0);
            $newuser['auth_user_md5.lock_comment']    = (isset($lock_comment) ? stripslashes(trim($lock_comment)) : "");
            $newuser['auth_user_md5.locked_by'] = ($locked==1 ? $auth->auth["uid"] : "");

            if (isset($auth_plugin))
                $newuser['auth_user_md5.auth_plugin'] = $auth_plugin;
            if (isset($visible))
                $newuser['auth_user_md5.visible'] = $visible;
            if (isset($title_front) || isset($title_front_chooser)) {
                if (!$title_front)
                    $title_front = $title_front_chooser;
                $newuser['user_info.title_front'] = stripslashes(trim($title_front));
            }
            if (isset($title_rear) || isset($title_rear_chooser)) {
                if (!$title_rear)
                    $title_rear = $title_rear_chooser;
                $newuser['user_info.title_rear'] = stripslashes(trim($title_rear));
            }
            if (isset($geschlecht))
                $newuser['user_info.geschlecht'] = stripslashes(trim($geschlecht));

            $UserManagement->changeUser($newuser);

            if($expiration_del == "1")
                $uc->unsetValue($UserManagement->user_data['auth_user_md5.user_id'],"EXPIRATION_DATE");
            else if(isset($expiration_date) && $expiration_date != ''){
                $a = explode(".",stripslashes(trim($expiration_date)));
                if($timestamp = @mktime(0,0,0,$a[1],$a[0],$a[2])){
                    $uc->setValue($timestamp,$UserManagement->user_data['auth_user_md5.user_id'],"EXPIRATION_DATE");
                }else{
                    $UserManagement->msg .= "error§" . _("Das Ablaufdatum wurde in einem falschen Format angegeben.") . "§";
                    break;
                }
            }


            if (is_array($_POST['datafields'])) {
                $invalidEntries = array();
                foreach (DataFieldEntry::getDataFieldEntries($u_id, 'user') as $entry) {
                    if(isset($_REQUEST['datafields'][$entry->getId()])){
                        $entry->setValueFromSubmit($_REQUEST['datafields'][$entry->getId()]);
                        if ($entry->isValid())
                            $entry->store();
                        else
                            $invalidEntries[$entry->getId()] = $entry;
                    }
                }

                if (is_array($invalidEntries)) {
                    foreach ($invalidEntries as $field) {
                        $msg .= 'error§'. sprintf(_("Fehlerhafte Eingabe im Datenfeld %s (wurde nicht gespeichert)!"), "<b>".$field->structure->getName()."</b>") .'§';
                    }
                }
            }

            // Change Password...

            if(($perm->have_perm('root')  && $ALLOW_ADMIN_USERACCESS) && ( $_REQUEST['pass_1'] != ''  || $_REQUEST['pass_2'] != '' ))
            {
                if($_REQUEST['pass_1'] == $_REQUEST['pass_2']){
                    if(strlen($_REQUEST['pass_1'])<4){
                        $pass_msg .= "error§" . _("Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.") . "§";
                        $showform = true;
                    }
                    $UserManagement->changePassword($pass_1);

                }
                else{
                    $pass_msg .= "error§" . _("Bei der Wiederholung des Passwortes ist ein Fehler aufgetreten! Bitte geben sie das exakte Passwort ein!") . "§";
                    $showform = true;
                }

            }


            break;


        // Change user password
        case "u_pass_x":

            $UserManagement = new UserManagement($u_id);

            $UserManagement->setPassword();

            break;


        // Delete the user
        case "u_kill_x":

            $username = get_username($u_id);
            $question = sprintf(_('Möchten Sie wirklich den User **%s** löschen ?'), $username);
            echo createQuestion( $question, array("studipticket" => get_ticket(), 'u_kill_id' => $u_id), array('details' => $username));

            break;

        case 'pers_browse_search_x':
            $_SESSION['pers_browse_old']['username'] = remove_magic_quotes($_POST['pers_browse_username']);
            $_SESSION['pers_browse_old']['Vorname'] = remove_magic_quotes($_POST['pers_browse_Vorname']);
            $_SESSION['pers_browse_old']['Email'] = remove_magic_quotes($_POST['pers_browse_Email']);
            $_SESSION['pers_browse_old']['Nachname'] = remove_magic_quotes($_POST['pers_browse_Nachname']);
            $_SESSION['pers_browse_old']['perms'] = remove_magic_quotes($_POST['pers_browse_perms']);
            $_SESSION['pers_browse_old']['crit'] = remove_magic_quotes($_POST['pers_browse_crit']);
            $_SESSION['pers_browse_old']['changed'] = strlen($_POST['pers_browse_changed']) ? abs($_POST['pers_browse_changed']) : null;
            $_SESSION['pers_browse_old']['locked'] = (int)$_POST['pers_browse_locked'];

            $_SESSION['pers_browse_search_string'] = "";
            foreach(array('username', 'Vorname', 'Email', 'Nachname') as $field){
                if($_SESSION['pers_browse_old'][$field]){
                    $_SESSION['pers_browse_search_string'] .= "$field LIKE '%" . mysql_escape_string($_SESSION['pers_browse_old'][$field]) . "%' AND ";
                }
            }
            
            //Datenfelder
			$datafields_list = DataFieldStructure::getDataFieldStructures("user");
			foreach($datafields_list as $datafield){
				if(DataFieldStructure::permMask($GLOBALS["auth"]->auth["perm"]) < DataFieldStructure::permMask($datafield->getViewPerms())) continue;
				$_SESSION['pers_browse_old']['datafields'][$datafield->getID()] = Request::get('pers_browse_datafields_'.$datafield->getID());
				if($_SESSION['pers_browse_old']['datafields'][$datafield->getID()]){
					$_SESSION['pers_browse_search_string'] .= "auth_user_md5.user_id IN(SELECT range_id FROM datafields_entries WHERE datafield_id = '".$datafield->getID()."' AND content LIKE '".mysql_escape_string($_SESSION['pers_browse_old']['datafields'][$datafield->getID()])."') AND ";
				}
			}
			//Datenfelder:Ende
            
            if ($_SESSION['pers_browse_old']['locked'])
                $_SESSION['pers_browse_search_string'] .= "locked = 1 AND ";
            if ($_SESSION['pers_browse_old']['perms'] && $_SESSION['pers_browse_old']['perms'] != _("alle"))
                $_SESSION['pers_browse_search_string'] .= "perms = '".mysql_escape_string($_SESSION['pers_browse_old']['perms'])."' AND ";
            if (isset($_SESSION['pers_browse_old']['changed'])) {
                $searchdate = date("YmdHis",  time()-$_SESSION['pers_browse_old']['changed']*3600*24);
                $searchdate2 = date("YmdHis",  time()-($_SESSION['pers_browse_old']['changed']+1)*3600*24);
                    if ($_SESSION['pers_browse_old']['crit'] == "<") {
                        $searchcrit = ">";
                        $_SESSION['pers_browse_search_string'] .= "changed $searchcrit '$searchdate' AND ";
                    }
                    if ($_SESSION['pers_browse_old']['crit'] == ">=") {
                        $searchcrit = "<";
                        $_SESSION['pers_browse_search_string'] .= "changed $searchcrit '$searchdate' AND ";
                    }
                    if ($_SESSION['pers_browse_old']['crit'] == "=") {
                        $_SESSION['pers_browse_search_string'] .= "changed < '$searchdate' AND changed > '$searchdate2' AND ";
                    }
                }
            if ($_SESSION['pers_browse_old']['crit'] == _("nie")){
                $_SESSION['pers_browse_old']['changed'] = null;
                $_SESSION['pers_browse_search_string'] .= "changed IS NULL AND ";
            }

            if ($_SESSION['pers_browse_search_string'] != "") {
                $_SESSION['pers_browse_search_string'] = " WHERE " . $_SESSION['pers_browse_search_string'];
                $_SESSION['pers_browse_search_string'] = substr($_SESSION['pers_browse_search_string'],0,-4);
            } else {
                unset($_SESSION['pers_browse_search_string']);
                $msg = "error§" . _("Bitte geben Sie einen Suchbegriff ein.") . "§";
            }
            break;
        default:
            break;
        }
    }

    if ($_REQUEST['u_kill_id']) {

        $UserManagement = new UserManagement($_REQUEST['u_kill_id']);
        $UserManagement->deleteUser();
    }

}
// Formular zuruecksetzen
if (isset($_GET['pers_browse_clear'])) {
    unset($_SESSION['pers_browse_old']);
    unset($_SESSION['pers_browse_search_string']);
}

URLHelper::addLinkParam("studipticket", get_ticket());

// --- ab hier neue messageboxen zusammengefasst -------------------------------
// messages erstmal nach dem alten muster zusammen in einen string speichern
//TODO: $UserManagement und Meldungen anpassen und optimieren
$messages = $UserManagement->msg;
if(empty($_REQUEST['details'])) {
    $messages .= $pass_msg;
}
$messages .= $msg;

// dann messages wieder separat nach typen in arrays speichern
$msg = explode('§', $messages);
for ($i=0; $i < count($msg); $i=$i+2) {
    switch ($msg[$i]) {
        case "error" :
            $details_error[] = $msg[$i+1];
            break;
        case "info" :
            $details_info[] =$msg[$i+1];
            break;
        case "msg" :
            $details_success[] =$msg[$i+1];
            break;
    }
}

// und schliesslich anzeigen (TODO: optimieren beim durchführen von lifter2)
?>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
    <tr>
        <td class="blank">
        <? // fehlermeldungen
        if (count($details_error) > 1) {
            $details_error = array_reverse($details_error);
            echo MessageBox::error(array_pop($details_error), $details_error);
        } elseif (count($details_error) == 1) {
            echo MessageBox::error(array_pop($details_error));
        }
        // infos
        if (count($details_info) > 1) {
            $details_info = array_reverse($details_info);
            echo MessageBox::info(array_pop($details_info), $details_info);
        } elseif (count($details_info) == 1) {
            echo MessageBox::info(array_pop($details_info));
        }
        // erfolg
        if (count($details_success) > 1) {
            $details_success = array_reverse($details_success);
            echo MessageBox::success(array_pop($details_success), $details_success);
        } elseif (count($details_success) == 1) {
            echo MessageBox::success(array_pop($details_success));
        } ?>
        </td>
    </tr>
</table>

<?
// einzelnen Benutzer anzeigen
if (isset($_GET['details']) || $showform ) {
    if ($details=="__" && in_array("Standard",$GLOBALS['STUDIP_AUTH_PLUGIN'])) { // neuen Benutzer anlegen
        ?>
        <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <tr>
            <td class="blank" colspan="2">
            <table border=0 bgcolor="#eeeeee" align="center" cellspacing=0 cellpadding=2>
            <form name="edit" method="post" action="<?=URLHelper::getLink('')?>">
                <tr>
                    <td colspan="2"><b>&nbsp;<?=_("Benutzername:")?></b></td>
                    <td>&nbsp;<input type="text" name="username" size=24 maxlength=63 value="<?=htmlReady(remove_magic_quotes($_POST['username']))?>"></td>
                </tr>
                <tr>
                    <td colspan="2"><b>&nbsp;<?=_("globaler Status:")?>&nbsp;</b></td>
                    <td>&nbsp;<? print $perm->perm_sel("perms", $_POST['perms'] ? $_POST['perms'][0] : 'autor') ?></td>
                </tr>
                <tr>
                    <td colspan="2"><b>&nbsp;<?=_("Sichtbarkeit")?>&nbsp;</b></td>
                    <td>&nbsp;<?=vis_chooser($_POST['visible'], !isset($_POST['visible'])) ?></td>
                </tr>
                <tr>
                    <td colspan="2"><b>&nbsp;<?=_("Vorname:")?></b></td>
                    <td>&nbsp;<input type="text" name="Vorname" size=24 maxlength=63 value="<?=htmlReady(remove_magic_quotes($_POST['Vorname']))?>"></td>
                </tr>
                <tr>
                    <td colspan="2"><b>&nbsp;<?=_("Nachname:")?></b></td>
                    <td>&nbsp;<input type="text" name="Nachname" size=24 maxlength=63 value="<?=htmlReady(remove_magic_quotes($_POST['Nachname']))?>"></td>
                </tr>
                <tr>
                <td><b>&nbsp;<?=_("Titel:")?></b>
                </td><td align="right"><select name="title_front_chooser" onChange="document.edit.title_front.value=document.edit.title_front_chooser.options[document.edit.title_front_chooser.selectedIndex].text;">
                <?
                for($i = 0; $i < count($TITLE_FRONT_TEMPLATE); ++$i){
                    echo "\n<option>$TITLE_FRONT_TEMPLATE[$i]</option>";
                }
                ?>
                </select></td>
                <td>&nbsp;<input type="text" name="title_front" value="<?=htmlReady(remove_magic_quotes($_POST['title_front']))?>" size=24 maxlength=63></td>
                </tr>
                <tr>
                <td><b>&nbsp;<?=_("Titel nachgest.:")?></b>
                </td><td align="right"><select name="title_rear_chooser" onChange="document.edit.title_rear.value=document.edit.title_rear_chooser.options[document.edit.title_rear_chooser.selectedIndex].text;">
                <?
                for($i = 0; $i < count($TITLE_REAR_TEMPLATE); ++$i){
                    echo "\n<option>$TITLE_REAR_TEMPLATE[$i]</option>";
                }
                ?>
                </select></td>
                <td>&nbsp;<input type="text" name="title_rear" value="<?=htmlReady(remove_magic_quotes($_POST['title_rear']))?>" size=24 maxlength=63></td>
                </tr>
                <tr>
                <td colspan="2"><b>&nbsp;<?=_("Geschlecht:")?></b></td>
                <td>&nbsp;<input type="radio" <?=(!$_POST['geschlecht'] ? 'checked' : '')?> name="geschlecht" value="0"><?=_("unbekannt")?>&nbsp;
                <input type="radio" name="geschlecht" value="1" <?=($_POST['geschlecht'] == 1 ? 'checked' : '')?>><?=_("männlich")?>&nbsp;
                <input type="radio" name="geschlecht" value="2" <?=($_POST['geschlecht'] == 2 ? 'checked' : '')?>><?=_("weiblich")?></td>
                </tr>
                <tr>
                    <td colspan="2"><b>&nbsp;<?=_("E-Mail:")?></b></td>
                    <td>&nbsp;<input type="text" name="Email" size=48 maxlength=63 value="<?=htmlReady(remove_magic_quotes($_POST['Email']))?>">&nbsp;</td>
                </tr>
                <tr>
                <td colspan="2"><b>&nbsp;<?=_("Einrichtung:")?></b></td>
                    <td>&nbsp;<select name="select_inst_id">
                    <?
            if ($auth->auth['perm'] == "root"){
                $db->query("SELECT Institut_id, Name, 1 AS is_fak  FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");
            } elseif ($auth->auth['perm'] == "admin") {
                $db->query("SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                WHERE a.user_id='$user->id' AND a.inst_perms='admin' ORDER BY is_fak,Name");
            }
            printf ("<option value=\"0\">%s</option>\n", _("-- bitte Einrichtung ausw&auml;hlen (optional) --"));
            while ($db->next_record()){
                printf ("<option value=\"%s\" style=\"%s\" %s>%s </option>\n", $db->f("Institut_id"),($db->f("is_fak") ? "font-weight:bold;" : ""), ($_POST['select_inst_id'] == $db->f("Institut_id") ? 'selected' : ''), htmlReady(substr($db->f("Name"), 0, 70)));
                if ($db->f("is_fak")){
                    $db2->query("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" .$db->f("Institut_id") . "' AND institut_id!='" .$db->f("Institut_id") . "' ORDER BY Name");
                    while ($db2->next_record()){
                        printf("<option %s value=\"%s\">&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n", ($_POST['select_inst_id'] == $db2->f("Institut_id") ? 'selected' : ''), $db2->f("Institut_id"), htmlReady(substr($db2->f("Name"), 0, 70)));
                    }
                }
            }
            echo "</select>";
            if($GLOBALS['MAIL_VALIDATE_BOX'] || $_POST['disable_mail_host_check']){
                echo chr(10).'<tr><td colspan="2">&nbsp;</td><td><input type="checkbox" id="disable_mail_host_check" name="disable_mail_host_check" value="1" '.($_POST['disable_mail_host_check'] ? 'checked' : '').'><label for="disable_mail_host_check" >'._("Mailboxüberprüfung deaktivieren").'</label></td></tr>';
            }
            if( $perm->have_perm('root') ){
                $domains = UserDomain::getUserDomains();
            }
            else{
                $domains = UserDomain::getUserDomainsForUser( $auth->auth["uid"] );
            }
            if( count( $domains ) ){
            ?>
            <tr>
                <td colspan="2">
                    <b>
                        &nbsp;<?=_("Nutzerdomäne:")?>
                    </b>
                </td>
                <td>
                    &nbsp;<select name="select_dom_id">
                    <?php
                        if( $perm->have_perm('root') ){
                            ?>
                                <option value=""><?= _("-- bitte Nutzerdomäne auswählen (optional) --") ?></option>
                            <?php 
                        } 
                        foreach( $domains as $domain ){
                            ?>
                                <option value="<?= $domain->getID() ?>"><?= $domain->getName() ?></option>
                            <?php
                        }
                        echo "</select>";
                    ?>
                </td>
            </tr>
            <?
            }
            ?>
            <tr><td colspan="2">&nbsp;</td><td><b><?=_("Folgende nur beim Anlegen eines Admins:")?></b></td></tr>
            <tr><td colspan="2">&nbsp;</td><td><input type="checkbox" id="enable_mail_admin" name="enable_mail_admin" value="admin"><label for="enable_mail_admin" ><?=_("Admins der Einrichtung benachrichtigen")?></label></td></tr>
            <tr><td colspan="2">&nbsp;</td><td><input type="checkbox" id="enable_mail_dozent" name="enable_mail_dozent" value="dozent"><label for="enable_mail_dozent" ><?=_("Dozenten der Einrichtung benachrichtigen")?></label></td></tr>
                <tr>
                <td colspan=3 align=center>&nbsp;
                <input type="image" name="create" <?=makeButton("anlegen", "src")?> value="<?=_("Benutzer anlegen")?>" alt="anlegen">
                <input type="image" name="nothing" <?=makeButton("abbrechen", "src")?> value="<?=_("Abbrechen")?>" alt="abbrechen">
                &nbsp;</td></tr>
            </form></table>

        </td></tr>
        <tr><td class="blank" colspan=2>&nbsp;</td></tr>
        </table>
        <?

    } else { // alten Benutzer bearbeiten
    ?>

    <table border="0" bgcolor="#000000" cellspacing="0" cellpadding="0" width="100%">
    <?
    if(empty($_REQUEST['details'])) {
        $details = $_REQUEST['username'];
    }

        $db->query("SELECT auth_user_md5.*, (changed + 0) as changed_compat, mkdate, title_rear, title_front, geschlecht FROM auth_user_md5 LEFT JOIN ".$GLOBALS['user']->that->database_table." ON auth_user_md5.user_id = sid LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) WHERE username ='$details'");
        while ($db->next_record()) {
            if ($db->f("changed_compat") != "") {
                $stamp = mktime(substr($db->f("changed_compat"),8,2),substr($db->f("changed_compat"),10,2),substr($db->f("changed_compat"),12,2),substr($db->f("changed_compat"),4,2),substr($db->f("changed_compat"),6,2),substr($db->f("changed_compat"),0,4));
                $inactive = floor((time() - $stamp) / 3600 / 24)    ." " . _("Tagen");
            } else {
                $inactive = _("nie benutzt");
            }
            $auth_plugin = $db->f('auth_plugin') ? $db->f('auth_plugin') : 'Standard';
            ?>
            <tr><td class="blank" colspan=2>&nbsp;</td></tr>
            <tr><td class="blank" colspan=2>
            <table border=0 bgcolor="#eeeeee" align="center" cellspacing=0 cellpadding=2>
            <form name="edit" method="post" action="<?=URLHelper::getLink('')?>">
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Benutzername:")?></b></td>
                    <td class="steel1">&nbsp;
                    <?
                    if (StudipAuthAbstract::CheckField("auth_user_md5.username", $auth_plugin)) {
                        echo htmlReady($db->f("username"));
                    } else {
                    ?><input type="text" name="username" size=24 maxlength=63 value="<?=htmlReady($db->f("username"))?>"><?
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("globaler Status:")?>&nbsp;</b></td>
                    <td class="steel1">&nbsp;
                    <?
                    if (StudipAuthAbstract::CheckField("auth_user_md5.perms", $auth_plugin)) {
                        echo $db->f("perms");
                    } else {
                        print $perm->perm_sel("perms", $db->f("perms"));
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Sichtbarkeit:")?>&nbsp;</b></td>
                    <td class="steel1">&nbsp;&nbsp;<?=vis_chooser($db->f('visible'))?>&nbsp;<small>(<?=$db->f('visible')?>)</small></td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Vorname:")?></b></td>
                    <td class="steel1">&nbsp;
                    <?
                    if (StudipAuthAbstract::CheckField("auth_user_md5.Vorname", $auth_plugin)) {
                        echo htmlReady($db->f("Vorname"));
                    } else {
                        ?><input type="text" name="Vorname" size=24 maxlength=63 value="<?=htmlReady($db->f("Vorname"))?>"><?
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Nachname:")?></b></td>
                    <td class="steel1">&nbsp;
                    <?
                    if (StudipAuthAbstract::CheckField("auth_user_md5.Nachname", $auth_plugin)) {
                        echo htmlReady($db->f("Nachname"));
                    } else {
                        ?><input type="text" name="Nachname" size=24 maxlength=63 value="<?=htmlReady($db->f("Nachname"))?>"><?
                    }
                    ?>
                    </td>
                </tr>
                <? if ($perm->have_perm('root') && $ALLOW_ADMIN_USERACCESS && !StudipAuthAbstract::CheckField("auth_user_md5.password", $auth_plugin)) { ?>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Neues Passwort:")?></b></td>
                    <td class="steel1">&nbsp;
                    <input name="pass_1" type="password" id="pass_1"><br>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Passwortwiederholung:")?></b></td>
                    <td class="steel1">&nbsp;
                    <input name="pass_2" type="password" id="pass_2"><br>
                    </td>
                </tr>
                <?}?>

                <tr>
                <td class="steel1"><b>&nbsp;<?=_("Titel:")?></b>
                </td><td class="steel1" align="right">
                <?
                if (StudipAuthAbstract::CheckField("user_info.title_front", $auth_plugin)) {
                        echo "&nbsp;</td><td class=\"steel1\">&nbsp;" . htmlReady($db->f("title_front"));
                } else {
                ?>
                <select name="title_front_chooser" onChange="document.edit.title_front.value=document.edit.title_front_chooser.options[document.edit.title_front_chooser.selectedIndex].text;">
                <?
                 for($i = 0; $i < count($TITLE_FRONT_TEMPLATE); ++$i){
                     echo "\n<option";
                     if($TITLE_FRONT_TEMPLATE[$i] == $db->f("title_front"))
                        echo " selected ";
                     echo ">".htmlReady($TITLE_FRONT_TEMPLATE[$i])."</option>";
                    }
                ?>
                </select></td>
                <td class="steel1">&nbsp;<input type="text" name="title_front" value="<?=htmlReady($db->f("title_front"))?>" size=24 maxlength=63>
                <?
                }
                ?>
                </td>
                </tr>
                <tr>
                <td class="steel1"><b>&nbsp;<?=_("Titel nachgest.:")?></b>
                </td><td class="steel1" align="right">
                <?
                if (StudipAuthAbstract::CheckField("user_info.title_rear", $auth_plugin)) {
                        echo "&nbsp;</td><td class=\"steel1\">&nbsp;" . htmlReady($db->f("title_rear"));
                } else {
                ?>
                <select name="title_rear_chooser" onChange="document.edit.title_rear.value=document.edit.title_rear_chooser.options[document.edit.title_rear_chooser.selectedIndex].text;">
                <?
                 for($i = 0; $i < count($TITLE_REAR_TEMPLATE); ++$i){
                     echo "\n<option";
                     if($TITLE_REAR_TEMPLATE[$i] == $db->f("title_rear"))
                        echo " selected ";
                     echo ">".htmlReady($TITLE_REAR_TEMPLATE[$i])."</option>";
                    }
                ?>
                </select></td>
                <td class="steel1">&nbsp;<input type="text" name="title_rear" value="<?=htmlReady($db->f("title_rear"))?>" size=24 maxlength=63>
                <?
                }
                ?>
                </td>
                </tr>
                <tr>
                <td colspan="2" class="steel1"><b>&nbsp;<?=_("Geschlecht:")?></b></td>
                <td class="steel1">&nbsp;
                <?
                if (StudipAuthAbstract::CheckField("user_info.geschlecht", $auth_plugin)) {
                    echo "&nbsp;" . ($db->f("geschlecht") == 1 ? _("männlich") : ($db->f("geschlecht") == 2 ? _("weiblich") : _("unbekannt")));
                } else {
                ?>
                <input type="radio" <? if (!$db->f("geschlecht")) echo "checked";?> name="geschlecht" value="0"><?=_("unbekannt")?>&nbsp;
                <input type="radio" <? if ($db->f("geschlecht") == 1) echo "checked";?> name="geschlecht" value="1"><?=_("männlich")?>&nbsp;
                <input type="radio" <? if ($db->f("geschlecht") == 2) echo "checked";?> name="geschlecht" value="2"><?=_("weiblich")?>
                <?
                }
                ?>
                </td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("E-Mail:")?></b></td>
                    <td class="steel1">&nbsp;
                    <?
                    if (StudipAuthAbstract::CheckField("auth_user_md5.Email", $auth_plugin)) {
                        echo htmlReady($db->f("Email"));
                    } else {
                    ?><input type="text" name="Email" size=48 maxlength=63 value="<?=htmlReady($db->f("Email"))?>">&nbsp;
                    <?
                    }
                    ?>
                    </td>
                </tr>
                <? if ($GLOBALS['MAIL_VALIDATE_BOX'] && !StudipAuthAbstract::CheckField("auth_user_md5.Email", $auth_plugin)) { ?>
                    <tr>
                        <td class="steel1" colspan="2"></td>
                        <td class="steel1">&nbsp;<input type="checkbox" id="disable_mail_host_check" name="disable_mail_host_check" value="1">
                            <label for="disable_mail_host_check"><?= _("Mailboxüberprüfung deaktivieren") ?></label>
                        </td>
                    </tr>
                <? } ?>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("inaktiv seit:")?></b></td>
                    <td class="steel1">&nbsp;<? echo $inactive ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("registriert seit:")?></b></td>
                    <td class="steel1">&nbsp;<? if ($db->f("mkdate")) echo date("d.m.y, G:i", $db->f("mkdate")); else echo _("unbekannt"); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="steel1"><b>&nbsp;<?=_("Authentifizierung:")?></b></td>
                    <td class="steel1">&nbsp;
                        <select name="auth_plugin">
                        <? foreach ($GLOBALS['STUDIP_AUTH_PLUGIN'] as $val): ?>
                            <option value="<?= strtolower($val) ?>" <?= strcasecmp($val, $auth_plugin) == 0 ? 'selected' : '' ?>><?= $val ?></option>
                        <? endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="steel1"><b>&nbsp;<?=_("Validation_key:")?></b></td>
                    <td class="steel1"><input type="checkbox" name="delete_val_key" value="1">L&ouml;schen</td>
                    <td class="steel1">
                    <?=htmlReady($db->f("validation_key"))?>
                    </td>
                </tr>
                <tr>
                    <td class="steel1"><b>&nbsp;<?=_("Ablaufdatum:")?></b></td>
                    <td class="steel1"><input type="checkbox" name="expiration_del" value="1">L&ouml;schen</td>
                    <td class="steel1">
                    <?$expiration = ($uc->getValue($db->f('user_id'),"EXPIRATION_DATE") > 0)?date("d.m.Y",$uc->getValue($db->f('user_id'),"EXPIRATION_DATE")):'';?>
                    <input type="text" name="expiration_date" size=20 maxlength=63 value="<?=$expiration?>"> (TT.MM.JJJJ z.B. 31.01.2009)
                    </td>
                </tr>

                <?
                $admin_ok = false;
                if ($perm->is_fak_admin() && $db->f('perms') == 'admin'){
                    $db2->query("SELECT IF(count(a.Institut_id) - count(c.inst_perms),0,1) AS admin_ok FROM user_inst AS a
                            LEFT JOIN Institute b ON (a.Institut_id=b.Institut_id AND b.Institut_id!=b.fakultaets_id)
                            LEFT JOIN user_inst AS c ON(b.fakultaets_id=c.Institut_id AND c.user_id = '".$user->id."' AND c.inst_perms='admin')
                            WHERE a.user_id ='".$db->f('user_id')."' AND a.inst_perms = 'admin'");
                    $db2->next_record();
                    $admin_ok = $db2->f('admin_ok');
                }

                if ($perm->have_perm('root') || ($db->f('perms') != 'admin' && $db->f('perms') != 'root') || $admin_ok) {

                    echo "<tr>\n";
                                    echo "  <td class=\"steel1\"><b>&nbsp;"._("Benutzer sperren:")."</b></td>\n";
                                    echo "  <td class=\"steel1\">\n";
                                    echo "    <input type=\"checkbox\" name=\"locked\" value=\"1\" ".($db->f("locked")==1 ? "CHECKED" : "").">"._("sperren")."\n";
                                    echo "  </td>\n";
                                    echo "  <td class=\"steel1\">\n";
                                    echo "    &nbsp;"._("Kommentar:")."&nbsp;\n";
                                    echo "    <input type=\"text\" name=\"lock_comment\" value=\"".htmlReady($db->f("lock_comment"))."\" size=\"24\" MAXLENGTH=\"255\">\n";
                                    echo "  </td>\n";
                                    echo "</tr>\n";
                    if ($db->f("locked")==1)
                                            echo "<tr><td class=\"steel1\" colspan=\"3\" align=\"center\"><font size=\"-2\">"._("Gesperrt von:")." ".htmlReady(get_fullname($db->f("locked_by")))." (<a href=\"about.php?username=".get_username($db->f("locked_by"))."\">".get_username($db->f("locked_by"))."</a>)</font></td></tr>\n";
                }
                $userEntries = DataFieldEntry::getDataFieldEntries($db->f('user_id'));
                foreach ($userEntries as $entry) {
                    $id = $entry->getID();
                    $color = '#000000';
                    if ($invalidEntries[$id]) {
                        $entry = $invalidEntries[$id];
                        $color = '#ff0000';
                    }
                    if ($entry->isVisible()) {
                        echo chr(10) . '<tr><td class="steel1" colspan="2">';
                        echo chr(10) . '<span style="font-weight:bold;color:'.$color.'">&nbsp;' . htmlReady($entry->getName()).':</span></td>';
                        echo chr(10) . '<td class="steel1">&nbsp;';
                        if ($entry->isEditable()) {
                            echo chr(10).$entry->getHTML("datafields");
                        } else {
                            echo chr(10).$entry->getDisplayValue();
                        }
                        echo chr(10).'</td></tr>';
                    }
                }
                ?>

                <td class="steel1" colspan=3 align=center>&nbsp;
                <input type="hidden" name="u_id" value="<?= $db->f("user_id") ?>">
                <?
                if ($perm->have_perm('root') || ($db->f('perms') != 'admin' && $db->f('perms') != 'root') || $admin_ok) {
                    ?>
                    <input type="image" name="u_edit" <?=makeButton("uebernehmen", "src")?> value=" <?=_("Ver&auml;ndern")?> ">&nbsp;
                    <?
                    if (!StudipAuthAbstract::CheckField("auth_user_md5.password", $auth_plugin)) {
                        ?>
                        <input type="image" name="u_pass" <?=makeButton("neuespasswort", "src")?> value=" <?=_("Passwort neu setzen")?> ">&nbsp;
                        <?
                    }
                    ?>
                    <input type="image" name="u_kill" <?=makeButton("loeschen", "src")?> value=" <?=_("L&ouml;schen")?> ">&nbsp;
                    <?
                }
                ?>
                <input type="image" name="nothing" <?=makeButton("abbrechen", "src")?> value=" <?=_("Abbrechen")?> ">
                &nbsp;</td></tr>
            </form>

            <tr><td colspan=3 class="blank">&nbsp;</td></tr>

            <? // links to everywhere
            echo "<tr><td class=\"steelgraulight\" colspan=3 align=\"center\">";
            echo _("pers&ouml;nliche Homepage") . " <a href=\"".URLHelper::getLink('about.php?username=' . $db->f("username")) . "\"><img class=\"middle\" src=\"".$GLOBALS['ASSETS_URL']."images/einst.gif\" ".tooltip(_("Zur persönlichen Homepage des Benutzers"))."></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            echo _("Nachricht an BenutzerIn") . " <a href=\"".URLHelper::getLink('sms_send.php?rec_uname=' . $db->f("username")) . "\"><img class=\"middle\" src=\"".$GLOBALS['ASSETS_URL']."images/nachricht1.gif\" ".tooltip(_("Nachricht an den Benutzer verschicken")) . "></a>";
            echo "</td></tr>";
            if ($perm->have_perm('root')){
                echo "<tr><td class=\"steel2\" colspan=3 align=\"center\">";
                echo "&nbsp;" . _("Datei- und Aktivitätenübersicht") . "&nbsp;";
                echo '<a href="' . URLHelper::getLink('user_activities.php?username=' . $db->f('username')) .'">
                    <img class="middle" src="'.$GLOBALS['ASSETS_URL'].'images/icon-disc.gif">
                    </a>';
                echo "</td></tr>\n";
                if(get_config('LOG_ENABLE')){
                    echo "<tr><td class=\"steel2\" colspan=3 align=\"center\">";
                    echo "&nbsp;" . _("Log") . "&nbsp;";
                    echo '<a href="' . URLHelper::getLink('dispatch.php/event_log/show', array('search' => $db->f('username'), 'type' => 'user', 'object_id' => $db->f('user_id'))) .'">
                    <img class="middle" src="'.$GLOBALS['ASSETS_URL'].'images/suchen.gif">
                    </a>';
                    echo "</td></tr>\n";
                }
            }
            $temp_user_id = $db->f("user_id");
            if ($perm->have_perm("root"))
                $db2->query("SELECT Institute.Institut_id, Name FROM user_inst LEFT JOIN Institute USING (Institut_id) WHERE user_id ='$temp_user_id' AND inst_perms != 'user'");
            elseif ($perm->is_fak_admin())
                $db2->query("SELECT a.Institut_id,b.Name FROM user_inst AS a
                            LEFT JOIN Institute b ON (a.Institut_id=b.Institut_id)
                            LEFT JOIN user_inst AS c ON(b.fakultaets_id=c.Institut_id )
                            WHERE a.user_id ='".$db->f("user_id")."' AND a.inst_perms <> 'user' AND c.user_id = '$user->id' AND c.inst_perms='admin'");
            else
                $db2->query("SELECT Institute.Institut_id, Name FROM user_inst AS x LEFT JOIN user_inst AS y USING (Institut_id) LEFT JOIN Institute USING (Institut_id) WHERE x.user_id ='$temp_user_id' AND x.inst_perms != 'user' AND y.user_id = '$user->id' AND y.inst_perms = 'admin'");
            if ($db2->num_rows()) {
                print "<tr><td class=\"steel2\" colspan=3 align=\"center\">";
                print "<b>&nbsp;" . _("Link zur MitarbeiterInnen-Verwaltung") . "&nbsp;</b>";
                print "</td></tr>\n";
            }
            while ($db2->next_record()) {
                echo "<tr><td class=\"steel2\" colspan=3 align=\"center\">";
                echo "&nbsp;" . htmlReady($db2->f("Name"));
                echo ' <a href="' . URLHelper::getLink(sprintf('inst_admin.php?details=%s&admin_inst_id=%s', $db->f("username"), $db2->f("Institut_id"))) . '">';
                echo "<img class=\"middle\" src=\"".$GLOBALS['ASSETS_URL']."images/admin.gif\" ".tooltip(_("Ändern der Einträge des Benutzers in der jeweiligen Einrichtung"))."\"></a>";
                echo "</td></tr>\n";
            }
            ?>

            </table>

            </td></tr>
            <tr><td class="blank" colspan=2>&nbsp;</td></tr>

            </table>
            <?
        }
    }

} else {

    // Gesamtliste anzeigen

    ?>
    <table border="0" bgcolor="#000000" cellspacing="0" cellpadding="0" width="100%">
    <tr><td class="blank" colspan=2>&nbsp;</td></tr>
    <tr><td class="blank" colspan="2">
    <?
    if (in_array("Standard",$GLOBALS['STUDIP_AUTH_PLUGIN'])){
        printf("&nbsp;&nbsp;"._("Neuen Benutzer-Account %s")."<br><br>", "<a href=\"" . URLHelper::getLink("?details=__") . "\"><img ".makeButton("anlegen", "src")."></a>");
    } else {
        echo "<p>&nbsp;" . _("Die Standard Authentifizierung ist ausgeschaltet. Das Anlegen von neuen Benutzern ist nicht möglich!") . "</p>";
    }

    // Suchformular
    print "<form action=\"".URLHelper::getLink()."\" method=\"post\">\n";
    print "<table border=0 align=\"center\" cellspacing=0 cellpadding=2 width = \"80%\">\n";
    print "<tr><th colspan=5>" . _("Suchformular") . "</th></tr>";
    print "\n<tr><td class=steel1 align=\"right\" width=\"15%\">" . _("Benutzername:") . " </td>";
    print "\n<td class=steel1 align=\"left\" width=\"35%\"><input name=\"pers_browse_username\" type=\"text\" value=\"".htmlReady($_SESSION['pers_browse_old']['username'])."\" size=30 maxlength=255></td>\n";
    print "\n<td class=steel1 align=\"right\" width=\"15%\">" . _("Vorname:") . " </td>";
    print "\n<td class=steel1 colspan=2 align=\"left\" width=\"35%\"><input name=\"pers_browse_Vorname\" type=\"text\" value=\"".htmlReady($_SESSION['pers_browse_old']['Vorname'])."\" size=30 maxlength=255></td></tr>\n";
    print "\n<tr><td class=steel1 align=\"right\" width=\"15%\">" . _("E-Mail:") . " </td>";
    print "\n<td class=steel1 align=\"left\" width=\"35%\"><input name=\"pers_browse_Email\" type=\"text\" value=\"".htmlReady($_SESSION['pers_browse_old']['Email'])."\" size=30 maxlength=255></td>\n";
    print "\n<td class=steel1 align=\"right\" width=\"15%\">" . _("Nachname:") . " </td>";
    print "\n<td class=steel1 colspan=2 align=\"left\" width=\"35%\"><input name=\"pers_browse_Nachname\" type=\"text\" value=\"".htmlReady($_SESSION['pers_browse_old']['Nachname'])."\" size=30 maxlength=255></td></tr>\n";
    
    //Datenfelder
	$datafields_empty = true;
	if(isset($_SESSION['pers_browse_old']['datafields']))
		foreach($_SESSION['pers_browse_old']['datafields'] as $df){if($df != ""){$datafields_empty = false; break;}}
	
	$i=0;
	$datafields_list = DataFieldStructure::getDataFieldStructures("user");
	foreach($datafields_list as $datafield){
		if(DataFieldStructure::permMask($GLOBALS["auth"]->auth["perm"]) < DataFieldStructure::permMask($datafield->getViewPerms())) continue;
		if($i%2==0) echo "<tr class=\"pers_browse_datafields\" ".(($datafields_empty)?"style=\"display:none\"":"").">";
		echo "<td class=steel1 align=\"right\" width=\"15%\">".$datafield->getName()."</td>";
		echo "<td ".(($i%2!=0)?"colspan=\"2\"":"")." class=steel1 align=\"left\" width=\"35%\"><input name=\"pers_browse_datafields_".$datafield->getID()."\" type=\"text\" value=\"".htmlReady($_SESSION['pers_browse_old']['datafields'][$datafield->getID()])."\" size=30 maxlength=255></td>";
		if($i%2!=0) echo "</tr>";
		$i++;
	}
	if($i%2!=0) echo "<td class=steel1>&nbsp;</td><td class=steel1 colspan=\"2\">&nbsp;</td>";
	echo "<tr><td class=steel1 align=\"right\" colspan=\"5\"><a href=\"#\" onClick=\"\$('.pers_browse_datafields').each(function(index){if(\$(this).css('display')=='none') $(this).css('display',''); else $(this).css('display','none');});this.innerHTML=(this.innerHTML=='Zuklappen')?'Erweiterte Suche':'Zuklappen';\">Erweiterte Suche</a></td></tr>";
	//Datenfelder:Ende
    
    
    print "\n<tr><td class=steel1 align=\"right\" width=\"15%\">" . _("Status:") . " </td>";
    print "\n<td class=steel1 align=\"left\" width=\"35%\">";
    echo '<select name="pers_browse_perms">';
    foreach(array(_("alle"),"user","autor","tutor","dozent","admin","root") as $one) {
        echo "\n<option";
        if ($_SESSION['pers_browse_old']['perms'] == $one)
            echo ' selected';
        echo '>'.$one.'</option>';
    }
    echo "</select>";
    echo "&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"pers_browse_locked\" value=\"1\" " . ($_SESSION['pers_browse_old']['locked'] ? "checked" : "" ) . ">&nbsp;"._("gesperrt");
    print "</td>\n";
    print "\n<td class=steel1 align=\"right\" width=\"15%\">" . _("inaktiv:") . " </td>";
    print "\n<td class=steel1 align=\"left\" width=\"10%\">";
    echo '<select name="pers_browse_crit">';
    foreach(array(">=","=","<",_("nie")) as $one) {
        echo "\n<option";
        if ($_SESSION['pers_browse_old']['crit'] == $one)
            echo ' selected';
        echo '>'.$one.'</option>';
    }
    echo "</select>";
    print "</td>";
    print "\n<td class=steel1 align=\"left\" width=\"25%\"><input name=\"pers_browse_changed\" type=\"text\" value=\"".htmlReady($_SESSION['pers_browse_old']['changed'])."\" size=10 maxlength=50> "._('Tage')."</td></tr>\n";
    print "\n<tr><td class=steel1>&nbsp</td><td class=steel1 align=\"left\">";
    echo makeButton("suchestarten", "input", _("Suche starten"),'pers_browse_search');
    echo "</td>\n";
    print "\n<td class=steel1>&nbsp</td><td class=steel1 colspan=2 align=\"left\"><a href=\"".URLHelper::getLink('', array('pers_browse_clear' => 1))."\"" . tooltip(_("Formular zurücksetzen")) . ">" . makeButton("zuruecksetzen", "img") . "</a></td></tr>\n";
    print "\n</table></form>\n";

    if (isset($_SESSION['pers_browse_search_string'])) { // Es wurde eine Suche initiert

        // nachsehen, ob wir ein Sortierkriterium haben, sonst nach username
        if (isset($_GET['sortby']) && in_array($_GET['sortby'], words('username perms Vorname Nachname Email changed mkdate auth_plugin'))) {
            $_SESSION['new_user_md5_sortby'] = $_GET['sortby'];
        } else {
            $_SESSION['new_user_md5_sortby'] = 'username';
        }

        // Traverse the result set
        $db->query("SELECT auth_user_md5.*, (changed + 0) as changed_compat, mkdate FROM auth_user_md5 LEFT JOIN ".$GLOBALS['user']->that->database_table." ON auth_user_md5.user_id = sid LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) {$_SESSION['pers_browse_search_string']} ORDER BY " . $_SESSION['new_user_md5_sortby']);

        if ($db->num_rows() == 0) { // kein Suchergebnis
            print "<table border=0 bgcolor=\"#eeeeee\" align=\"center\" cellspacing=0 cellpadding=2 width=\"80%\">";
            print "<tr valign=\"top\" align=\"middle\">";
            print "<td>" . MessageBox::info(_("Es wurden keine Personen gefunden, auf die die obigen Kriterien zutreffen.")) . "</td>";
            print "</tr><tr><td class=\"blank\">&nbsp;</td></tr></table>";

        } else { // wir haben ein Suchergebnis
            echo '<table border="0" bgcolor="#eeeeee" align="center" cellspacing="0" class="blank" cellpadding="2" width="100%">';

            if ($perm->have_perm('root')){
                echo '<tr valign="top"><td colspan="8"><a href="' . URLHelper::getLink('admin_user_kill.php?transfer_search=1') . '">'._("Suchergebnis in Löschformular übernehmen").'</a></td></tr>';
            }

            echo '<tr valign="top" align="middle">';
                if ($db->num_rows() == 1)
                    echo '<td colspan="8">' . _("Suchergebnis: Es wurde <b>1</b> Person gefunden.") . "</td></tr>\n";
                else
                    printf('<td colspan="8">' . _("Suchergebnis: Es wurden <b>%s</b> Personen gefunden.") . "</td></tr>\n", $db->num_rows());
            ?>
             <tr valign="top" align="middle">
                <th align="left"><a href="<?=URLHelper::getLink('?sortby=username')?>"><?=_("Benutzername")?></a>&nbsp;<span style="font-size:smaller;font-weight:normal;color:#f8f8f8;">(<?=_("Sichtbarkeit")?>)</span></th>
                <th align="left"><a href="<?=URLHelper::getLink('?sortby=perms')?>"><?=_("Status")?></a></th>
                <th align="left"><a href="<?=URLHelper::getLink('?sortby=Vorname')?>"><?=_("Vorname")?></a></th>
                <th align="left"><a href="<?=URLHelper::getLink('?sortby=Nachname')?>"><?=_("Nachname")?></a></th>
                <th align="left"><a href="<?=URLHelper::getLink('?sortby=Email')?>"><?=_("E-Mail")?></a></th>
                <th align="right"><a href="<?=URLHelper::getLink('?sortby=changed')?>"><?=_("inaktiv")?></a></th>
                <th><a href="<?=URLHelper::getLink('?sortby=mkdate')?>"><?=_("registriert seit")?></a></th>
                <th><a href="<?=URLHelper::getLink('?sortby=auth_plugin')?>"><?=_("Authentifizierung")?></a></th>
             </tr>
            <?

            while ($db->next_record()):
                if ($db->f("changed_compat") != "") {
                    $stamp = mktime(substr($db->f("changed_compat"),8,2),substr($db->f("changed_compat"),10,2),substr($db->f("changed_compat"),12,2),substr($db->f("changed_compat"),4,2),substr($db->f("changed_compat"),6,2),substr($db->f("changed_compat"),0,4));
                    $inactive = time() - $stamp;
                    if ($inactive < 3600 * 24) {
                        $inactive = gmdate('H:i:s', $inactive);
                    } else {
                        $inactive = floor($inactive / (3600 * 24)).' '._('Tage');
                    }
                } else {
                    $inactive = _("nie benutzt");
                }
                ?>
                <tr valign=middle align=left>
                    <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>"><a href="<?=URLHelper::getLink('?details=' . $db->f("username"))?>"><?php $db->p("username") ?></a>&nbsp;<?
                    if ($db->f('locked')=='1'){
                        echo '<span style="font-size:smaller;color:red;font-weight:bold;">' . _("gesperrt!") .'</span>';
                    } else {
                        echo '<span style="font-size:smaller;color:#888;">('.$db->f('visible').')</span>';
                    }
                    ?></td>
                    <td class="<? echo $cssSw->getClass() ?>"><?=$db->f("perms") ?></td>
                    <td class="<? echo $cssSw->getClass() ?>"><?=htmlReady($db->f("Vorname")) ?>&nbsp;</td>
                    <td class="<? echo $cssSw->getClass() ?>"><?=htmlReady($db->f("Nachname")) ?>&nbsp;</td>
                    <td class="<? echo $cssSw->getClass() ?>"><?=htmlReady($db->f("Email"))?></td>
                    <td class="<? echo $cssSw->getClass() ?>" align="right"><?php echo $inactive ?></td>
                    <td class="<? echo $cssSw->getClass() ?>" align="center"><? if ($db->f("mkdate")) echo date("d.m.y, H:i", $db->f("mkdate")); else echo _("unbekannt"); ?></td>
                    <td class="<? echo $cssSw->getClass() ?>" align="center"><?=($db->f("auth_plugin") ? $db->f("auth_plugin") : "Standard")?></td>
                </tr>
                <?
            endwhile;
            print ("</table>");
        }
    }
    print ("</td></tr></table>");


}
include ('lib/include/html_end.inc.php');
page_close();
?>
