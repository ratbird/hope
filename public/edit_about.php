<?php
# Lifter002: TODO
# Lifter005: TODO - form validation
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// edit_about.php
// administration of personal home page
//
// Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>,
// Niklas Nohlen <nnohlen@gwdg.de>, Miro Freitag <mfreita@goe.net>, André Noack <andre.noack@gmx.net>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(!$logout && ($auth->auth["uid"] == "nobody"));

if ($usr_name)  $username=$usr_name; //wenn wir von den externen Seiten kommen, nehmen wir den Usernamen aus usr_name, falls dieser gesetzt ist, um die Anmeldeprozedur nicht zu verwirren....

require_once('config.inc.php');
require_once('lib/my_rss_feed.inc.php');
require_once('lib/kategorien.inc.php');
require_once('lib/msg.inc.php');
require_once('lib/messaging.inc.php');
require_once('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once('lib/statusgruppe.inc.php');
require_once('lib/language.inc.php');
require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/log_events.inc.php');
require_once('lib/classes/Avatar.class.php');
require_once('lib/edit_about.inc.php');
require_once('lib/classes/UserDomain.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

$sess->register('edit_about_data');

if (!isset($ALLOW_CHANGE_NAME)) $ALLOW_CHANGE_NAME = TRUE; //wegen Abwärtskompatibilität, erst ab 1.1 bekannt

// hier gehts los
if (!$username) $username = $auth->auth["uname"];
if($edit_about_msg){
    $msg = $edit_about_msg;
    $edit_about_msg = '';
    $sess->unregister('edit_about_msg');
}
if($nobodymsg && $logout && $auth->auth["uid"] == "nobody"){
    $msg = $nobodymsg;
}

checkExternDefaultForUser(get_userid($username));

$my_about = new about($username,$msg);
$cssSw = new cssClassSwitcher;
#$DataFields = new DataFields($my_about->auth_user["user_id"]);

if ($logout && $auth->auth["uid"] == "nobody")  // wir wurden gerade ausgeloggt...
    {

    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head

    echo '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
    echo '<tr><td class="topic" colspan="2"><b>&nbsp;'. _("Daten ge&auml;ndert!") .'</b></td></tr>';

    $my_about->parse_msg($my_about->msg);
    $temp_string = '<br><font color="black">'
        . sprintf(_("Um eine korrekte Authentifizierung mit ihren neuen Daten sicherzustellen, wurden sie automatisch ausgeloggt.<br>Wenn sie ihre E-Mail-Adresse ge&auml;ndert haben, m&uuml;ssen sie das Ihnen an diese Adresse zugesandte Passwort verwenden!<br><br>Ihr aktueller Username ist: %s"), '<b>'. $username. '</b>')
        . '<br>---&gt; <a href="index.php?again=yes">' . _("Login") . '</a> &lt;---</font>';
    $my_about->my_info($temp_string);


    echo '</table>';
    include ('lib/include/html_end.inc.php');
    page_close();
    die;
    }

//No Permission to change userdata
if (!$my_about->check) {
    // -- here you have to put initialisations for the current page
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    parse_window('error§' . _("Zugriff verweigert.").
                 "<br>\n<font size=-1 color=black>".
                 sprintf(_("Wahrscheinlich ist Ihre Session abgelaufen. Wenn sie sich länger als %s Minuten nicht im System bewegt haben, werden Sie automatisch abgemeldet. Bitte nutzen Sie in diesem Fall den untenstehenden Link, um zurück zur Anmeldung zu gelangen.<br> <br> Eine andere Ursache kann der Versuch des Zugriffs auf Userdaten, die Sie nicht bearbeiten d&uuml;rfen, sein. Nutzen Sie den untenstehenden Link, um zurück auf die Startseite zu gelangen."), $AUTH_LIFETIME).
                 '</font>', '§', _("Zugriff auf Userdaten verweigert"),
                 sprintf(_("%s Hier%s geht es wieder zur Anmeldung beziehungsweise Startseite."),'<a href="index.php"><b>&nbsp;','</b></a>')."<br>\n&nbsp;");

    include ('lib/include/html_end.inc.php');
    page_close();
    exit;
}

$my_about->get_user_details();


/* * * * * * * * * * * * * * * *
 * * * C O N T R O L L E R * * *
 * * * * * * * * * * * * * * * */

if (check_ticket($studipticket)) {
    //wozu ??? siehe about::edit_leben()
    //$invalidEntries = parse_datafields($my_about->auth_user['user_id']);

    if ($cmd == "edit_pers" && $_REQUEST['email1'] != '' && $my_about->auth_user["Email"] != $_REQUEST['email1']) {
        if($_REQUEST['email1'] == $_REQUEST['email2']) {
            $my_about->edit_email($_REQUEST['email1']);
        } else {
            $my_about->msg.= "info§" . _('Die Wiederholung der E-Mail Adresse stimmt nicht mit Ihrer Eingabe überein. Bitte überprüfen Sie Ihre Eingabe.'). '§';
        }
    }


    // Person einer Rolle hinzufügen
    if ($cmd == 'addToGroup') {
        $db_group = new DB_Seminar();
        if (InsertPersonStatusgruppe($my_about->auth_user['user_id'], $role_id)) {
            $globalperms = get_global_perm($my_about->auth_user['user_id']);
            if ($perm->get_studip_perm($subview_id, $my_about->auth_user['user_id']) == FALSE) {
                log_event('INST_USER_ADD', $subview_id , $my_about->auth_user['user_id'], $globalperms);
                $db_group->query("INSERT IGNORE INTO user_inst SET Institut_id = '$subview_id', user_id = '{$my_about->auth_user['user_id']}', inst_perms = '$globalperms'");
            }
            if ($perm->get_studip_perm($subview_id, $my_about->auth_user['user_id']) == 'user') {
                log_event('INST_USER_STATUS', $subview_id , $my_about->auth_user['user_id'], $globalperms);
                $db_group->query("UPDATE user_inst SET inst_perms = '$globalperms' WHERE user_id = '{$my_about->auth_user['user_id']}' AND Institut_id = '$subview_id'");
            }
            $my_about->msg .= 'msg§'. _("Die Person wurde in die ausgewählte Gruppe eingetragen!"). '§';
            checkExternDefaultForUser($my_about->auth_user['user_id']);
        } else {
            $my_about->msg .= 'error§'. _("Fehler beim Eintragen in die Gruppe!") . '§';
        }
    }

    //Default von Einrichtung Übernehmen
    if ($cmd == 'set_default') {
        $dbdef = new DB_Seminar();
        $dbdef->query("UPDATE datafields_entries SET content='default_value' WHERE datafield_id = '".$_REQUEST['chgdef_entry_id']."' AND range_id = '".$my_about->auth_user['user_id']."' AND sec_range_id = '".$_REQUEST['sec_range_id']."'");
        if ($dbdef->affected_rows() == 0) {
            $dbdef->query("INSERT INTO datafields_entries (datafield_id, range_id, sec_range_id, content, chdate, mkdate) VALUES ".
                "('".$_REQUEST['chgdef_entry_id']."',".
                "'".$my_about->auth_user['user_id']."', ".
                "'".$_REQUEST['sec_range_id']."', ".
                "'default_value', ".time().", ".time().")");
        }
    }

    //Default NICHT von Einrichtung Übernehmen
    if ($cmd == 'unset_default') {
        $default_entries = DataFieldEntry::getDataFieldEntries($zw = array($my_about->auth_user['user_id'], $_REQUEST['cor_inst_id']));
        $dbdef = new DB_Seminar();
        $dbdef->query("UPDATE datafields_entries SET content='".$default_entries[$_REQUEST['chgdef_entry_id']]->getValue()."' WHERE datafield_id = '".$_REQUEST['chgdef_entry_id']."' AND range_id = '".$my_about->auth_user['user_id']."' AND sec_range_id = '".$_REQUEST['sec_range_id']."'");
    }

    if ($cmd == 'makeAllDefault') {
        MakeDatafieldsDefault($my_about->auth_user['user_id'], $_REQUEST['role_id']);
    }

    if ($cmd == 'makeAllSpecial') {
        MakeDatafieldsDefault($my_about->auth_user['user_id'], $_REQUEST['role_id'], '');
    }

    if ($cmd == 'removeFromGroup') {
        $db_group = new DB_Seminar();
        $db_group->query("DELETE FROM statusgruppe_user WHERE user_id = '" . $my_about->auth_user['user_id'] . "' AND statusgruppe_id = '$role_id'");
        $my_about->msg .= 'msg§' . _("Die Person wurde aus der ausgewählten Gruppe gelöscht!") . '§';
    }

    //ein Bild wurde hochgeladen
    if ($cmd == "copy") {
        try {
            Avatar::getAvatar($my_about->auth_user["user_id"])->createFromUpload('imgfile');
            $my_about->msg = "msg§" . _("Die Bilddatei wurde erfolgreich hochgeladen. Eventuell sehen Sie das neue Bild erst, nachdem Sie diese Seite neu geladen haben (in den meisten Browsern F5 dr&uuml;cken).") . '§';
        } catch (Exception $e) {
            $my_about->msg = 'error§' . $e->getMessage() . '§';
        }

        setTempLanguage($my_about->auth_user["user_id"]);
        $my_about->priv_msg = _("Ein neues Bild wurde hochgeladen.\n");
        restoreLanguage();
    }

    //Veränderungen an Studiengängen

    if ($cmd == "fach_abschluss_edit" && (!StudipAuthAbstract::CheckField("studiengang_id", $my_about->auth_user['auth_plugin'])) && ($ALLOW_SELFASSIGN_STUDYCOURSE || $perm->have_perm('admin')))
    {
        $my_about->fach_abschluss_edit($fach_abschluss_delete,$new_studiengang,$new_abschluss,$fachsem,$change_fachsem,$course_id);
    }

    //Veränderungen an Nutzer-Domains
    if ($cmd == "userdomain_edit" && !StudipAuthAbstract::CheckField("userdomain_id", $my_about->auth_user['auth_plugin']) && $perm->have_perm('admin'))
    {
        $my_about->userdomain_edit($userdomain_delete,$new_userdomain);
    }

    //Veränderungen an Instituten für Studies
    if ($cmd == "inst_edit" && ($ALLOW_SELFASSIGN_INSTITUTE || $perm->have_perm('admin')))
    {
        $my_about->inst_edit($inst_delete,$new_inst);
    }

    // change order of institutes
    if ($cmd == 'move') {
        $my_about->move($move_inst, $direction);
    }

    if ($cmd=="special_edit") {
        $invalidEntries = $my_about->special_edit($raum, $sprech, $tel, $fax, $name, $default_inst, $visible,
                                        $datafields, $group_id, $role_id, array('status' => $_REQUEST['status'], 'inst_id' => $_REQUEST['inst_id']));

        if (is_array($invalidEntries))
            foreach ($invalidEntries as $entry)
                $my_about->msg .= "error§" . sprintf(_("Fehlerhafter Eintrag im Feld <em>%s</em>: %s (Eintrag wurde nicht gespeichert)"), $entry->getName(), $entry->getDisplayValue()) . "§";

        if (count($_REQUEST['role_visible']) > 0) { // change inheritance state of a user role
            $groupID = array_pop(array_keys($_REQUEST['role_visible'])); // there is only 1 element in the array (and we get its key)
            if ($_REQUEST['role_visible'][$groupID] == 1) {
                $visible = 0;
            } else {
                $visible = 1;
            }
            setOptionsOfStGroup($groupID, $my_about->auth_user['user_id'], $visible, 1);
            // Due to the changes concerning the statusgroups, inherit ist now always 1

        }
    }


    //Veränderungen der pers. Daten
    if ($cmd == "edit_pers") {
        $new_password = '*****'; // ***** as in "don't change password"
        if($_REQUEST['update_pw'] == 'on') {
            if($_REQUEST['new_passwd_1'] != $_REQUEST['new_passwd_2']) {
                $my_about->msg.= 'info§'. _('Die Wiederholung Ihres Passwords stimmt nicht mit Ihrer Eingabe überrein. Bitte überprüfen Sie Ihre Eingabe.') . '§';
            } else {
                $new_password = $_REQUEST['new_passwd_1'];
            }
        } else if($_REQUEST['new_passwd_2'] != '' && $_REQUEST['new_passwd_2'] != '*****') {
            $my_about->msg.= 'info§'. _('Sie müssen den Haken bei "ändern" setzen, wenn Sie Ihr Passwort ändern wollen.') .'§';
        }

        if($_REQUEST['password'] != $my_about->auth_user["username"])
            $my_about->edit_pers($new_password,
                         $_REQUEST['new_username'],
                         $_REQUEST['vorname'], $_REQUEST['nachname'],
                         $_REQUEST['email'], $_REQUEST['geschlecht'],
                         $_REQUEST['title_front'],
                         $_REQUEST['title_front_chooser'],
                         $_REQUEST['title_rear'], $_REQUEST['title_rear_chooser'],
                         $_REQUEST['view']);

            if (($my_about->auth_user["username"] != $new_username) && $my_about->logout_user == TRUE) {
                $my_about->get_auth_user($new_username);   //username wurde geändert!
            } else {
                $my_about->get_auth_user($username);
            }
            $username = $my_about->auth_user["username"];
    }

    if ($cmd=="edit_leben")  {
        if (get_config("ENABLE_SKYPE_INFO")) {
            UserConfig::get($my_about->auth_user['user_id'])->store('SKYPE_NAME', preg_replace('/[^a-zA-Z0-9.,_-]/', '', $_REQUEST['skype_name']));
            UserConfig::get($my_about->auth_user['user_id'])->store('SKYPE_ONLINE_STATUS', (int)$_REQUEST['skype_online_status']);
        }

        $my_about->edit_private(
             $_REQUEST['telefon'], $_REQUEST['cell'], $_REQUEST['anschrift'],
             $_REQUEST['home'], $_REQUEST['motto'], $_REQUEST['hobby']
        );

        $invalidEntries = $my_about->edit_leben($lebenslauf,$schwerp,$publi,$view, $_REQUEST['datafields']);
        $my_about->msg = "";
        foreach ($invalidEntries as $entry)
            $my_about->msg .= "error§" . sprintf(_("Fehlerhafter Eintrag im Feld <em>%s</em>: %s (Eintrag wurde nicht gespeichert)"), $entry->getName(), $entry->getDisplayValue()) . "§";
        $my_about->get_auth_user($username);
    }

    // general settings from mystudip: language, jshover, accesskey
    if ($cmd=="change_general") {
        $my_about->db->query("UPDATE user_info SET preferred_language = '$forced_language' WHERE user_id='" . $my_about->auth_user["user_id"] ."'");
        $_language = $forced_language;
        $forum["jshover"]=$jshover;
        $my_studip_settings["startpage_redirect"] = $personal_startpage;
        UserConfig::get($user->id)->store('ACCESSKEY_ENABLE', (int)$_REQUEST['accesskey_enable']);
        UserConfig::get($user->id)->store('SHOWSEM_ENABLE', (int)$_REQUEST['showsem_enable']);
    }

    if ($cmd == 'change_global_visibility') {
        $success = $my_about->change_global_visibility($global_visibility, $online, $chat, $search, $email);
        if ($success) {
            $my_about->msg .= 'msg§'._('Ihre Sichtbarkeitseinstellungen wurden gespeichert.');
        } else {
            $my_about->msg .= 'error§'._('Ihre Sichtbarkeitseinstellungen wurden nicht gespeichert!');
        }
    }

    if ($cmd == 'change_all_homepage_visibility') {
        if ($_REQUEST['all_homepage_visibility']) {
            $success = $my_about->change_all_homepage_visibility($_REQUEST['all_homepage_visibility']);
            if ($success) {
                $my_about->msg .= 'msg§'._('Die Sichtbarkeit der Homepageelemente wurde gespeichert.');
            } else {
                $my_about->msg .= 'error§'._('Die Sichtbarkeitseinstellungen der Homepageelemente wurden nicht gespeichert!');
            }
        } else {
            $my_about->msg .= 'error§'._('Bitte wählen Sie eine Sichtbarkeitsstufe für Ihre Homepageelemente!');
        }
    }

    if ($cmd == 'change_homepage_visibility') {
        $data = $_POST;
        unset($data["change_homepage_visibility_x"]);
        unset($data["change_homepage_visibility_y"]);
        $success = $my_about->change_homepage_visibility($data);
        if ($success) {
            $my_about->msg .= 'msg§'._('Die Sichtbarkeit der Homepageelemente wurde gespeichert.');
        } else {
            $my_about->msg .= 'error§'._('Die Sichtbarkeitseinstellungen der Homepageelemente wurden nicht gespeichert!');
        }
    }

    if ($my_about->logout_user)
     {
        $sess->delete();  // User logout vorbereiten
        $auth->logout();
        $timeout=(time()-(15 * 60));
        $nobodymsg = rawurlencode($my_about->msg);
        page_close();
        $user->set_last_action($timeout);
        header("Location: $PHP_SELF?username=$username&nobodymsg=$nobodymsg&logout=1&view=$view"); //Seite neu aufrufen, damit user nobody wird...
        die;
        }

    if ($cmd) {
        if ($view == "Bild" &&
            $cmd == "bild_loeschen" &&
            $_SERVER["REQUEST_METHOD"] == "POST") {
                Avatar::getAvatar($my_about->auth_user["user_id"])->reset();
                $my_about->msg .= "info§" . _("Bild gel&ouml;scht.") . "§";
        }

        if (($my_about->check != "user") && ($my_about->priv_msg != "")) {
            $m_id=md5(uniqid("smswahn"));
            setTempLanguage($my_about->auth_user["user_id"]);
            $priv_msg = _("Ihre persönliche Seite wurde von einer Administratorin oder einem Administrator verändert.\n Folgende Veränderungen wurden vorgenommen:\n \n").$my_about->priv_msg;
            restoreLanguage();
            $my_about->insert_message($priv_msg, $my_about->auth_user["username"], "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("persönliche Homepage verändert"));
        }
        $sess->register('edit_about_msg');
        $edit_about_msg = $my_about->msg;
        header("Location: $PHP_SELF?username=$username&view=$view");  //Seite neu aufrufen, um Parameter loszuwerden
        page_close();
        die;
    }

} else {
    unset($cmd);
}

/* * * * * * * * * * * * * * * *
 * * * * * * V I E W * * * * * *
 * * * * * * * * * * * * * * * */

switch($view) {
    case "Bild":
        $HELP_KEYWORD="Basis.HomepageBild";
        $CURRENT_PAGE=_("Hochladen eines persönlichen Bildes");
        Navigation::activateItem('/profil/avatar');
        break;
    case "Daten":
        $HELP_KEYWORD="Basis.HomepagePersönlicheDaten";
        $CURRENT_PAGE=_("Benutzerkonto bearbeiten");
        Navigation::activateItem('/profil/edit/profile');
        break;
    case "Karriere":
        $HELP_KEYWORD="Basis.HomepageUniversitäreDaten";
        $CURRENT_PAGE=_("Einrichtungsdaten bearbeiten");
        Navigation::activateItem('/profil/edit/inst_data');
        break;
    case 'Studium':
        $HELP_KEYWORD="Basis.HomepageUniversitäreDaten";
        $CURRENT_PAGE=_("Studiengang bearbeiten");
        Navigation::activateItem('/profil/edit/study_data');
        break;
    case 'userdomains':
        $HELP_KEYWORD="Basis.HomepageNutzerdomänen";
        $CURRENT_PAGE=_("Nutzerdomänen bearbeiten");
        Navigation::activateItem('/profil/edit/user_domains');
        break;
    case "Lebenslauf":
        $HELP_KEYWORD="Basis.HomepageLebenslauf";
        if ($auth->auth['perm'] == "dozent")
            $CURRENT_PAGE =  _("Lebenslauf, Arbeitsschwerpunkte und Publikationen bearbeiten");
        else
            $CURRENT_PAGE =  _("Lebenslauf bearbeiten");
        Navigation::activateItem('/profil/edit/private');
        break;
    case "Sonstiges":
        $HELP_KEYWORD="Basis.HomepageSonstiges";
        $CURRENT_PAGE=_("Eigene Kategorien bearbeiten");
        Navigation::activateItem('/profil/sections');
        break;
    case "Login":
        $HELP_KEYWORD="Basis.MyStudIPAutoLogin";
        $CURRENT_PAGE=_("Auto-Login einrichten");
        Navigation::activateItem('/account/login');
        break;
    case "Forum":
        $HELP_KEYWORD="Basis.MyStudIPForum";
        $CURRENT_PAGE=_("Einstellungen des Forums anpassen");
        Navigation::activateItem('/account/forum');
        break;
    case "calendar":
        $HELP_KEYWORD="Basis.MyStudIPTerminkalender";
        $CURRENT_PAGE=_("Einstellungen des Terminkalenders anpassen");
        Navigation::activateItem('/account/calendar');
        break;
    case "Tools":
        $HELP_KEYWORD="Basis.HomepageTools";
        $CURRENT_PAGE=_("Benutzer-Tools");
        break;
    case "Stundenplan":
        $HELP_KEYWORD="Basis.MyStudIPStundenplan";
        $CURRENT_PAGE=_("Einstellungen des Stundenplans anpassen");
        Navigation::activateItem('/account/schedule');
        break;
    case "Messaging":
        $HELP_KEYWORD="Basis.MyStudIPMessaging";
        $CURRENT_PAGE=_("Einstellungen des Nachrichtensystems anpassen");
        Navigation::activateItem('/account/messaging');
        break;
    case "rss":
        $HELP_KEYWORD="Basis.MyStudIPRSS";
        $CURRENT_PAGE=_("Einstellungen der RSS-Anzeige anpassen");
        Navigation::activateItem('/account/rss');
        break;
    case "allgemein":
        $CURRENT_PAGE=_("Allgemeine Einstellungen anpassen");
        Navigation::activateItem('/account/general');
        break;
    case "privacy":
        $CURRENT_PAGE=_("Privatsphäre");
        Navigation::activateItem('/account/privacy');
        break;
    default:
        $HELP_KEYWORD="Basis.MyStudIP";
        break;
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head

if ($auth->auth["jscript"]) { // nur wenn JS aktiv
if ($view == 'Daten') {
    $validator=new email_validation_class;
?>
<script type="text/javascript" language="javascript">
<!--

function checkusername(){
 var re_username = <?=$validator->username_regular_expression?>;
 var checked = true;
 if (document.pers.new_username.value.length<4) {
    alert("<?=_("Der Benutzername ist zu kurz - er sollte mindestens 4 Zeichen lang sein.")?>");
     document.pers.new_username.focus();
    checked = false;
    }
 if (re_username.test(document.pers.new_username.value)==false) {
    alert("<?=_("Der Benutzername enthält unzulässige Zeichen - er darf keine Sonderzeichen oder Leerzeichen enthalten.")?>");
     document.pers.new_username.focus();
    checked = false;
    }
 return checked;
}

function checkpassword(){
 var checked = true;

 if (document.pers.update_pw.checked && document.pers.new_passwd_1.value != document.pers.new_passwd_2.value) {
    alert("<?=_("Bei der Wiederholung des Paßwortes ist ein Fehler aufgetreten! Bitte geben sie das  exakte Paßwort ein!")?>");
    document.pers.new_passwd_2.focus();
    checked = false;
 }

 if (document.pers.update_pw.checked && document.pers.new_passwd_1.value.length<4 && document.pers.new_passwd_2.value.length<4) {
    alert("<?=_("Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.")?>");
     document.pers.new_passwd_1.focus();
    checked = false;
 }

 return checked;
}

function checkvorname(){
 var re_vorname = <?=$validator->name_regular_expression?>;
 var checked = true;
 if (document.pers.vorname.value!='<?=$my_about->auth_user["Vorname"]?>' && re_vorname.test(document.pers.vorname.value)==false) {
    alert("<?=_("Bitte geben Sie Ihren tatsächlichen Vornamen an.")?>");
     document.pers.vorname.focus();
    checked = false;
    }
 return checked;
}

function checknachname(){
 var re_nachname = <?=$validator->name_regular_expression?>;
 var checked = true;
 if (document.pers.nachname.value!='<?=$my_about->auth_user["Nachname"]?>' && re_nachname.test(document.pers.nachname.value)==false) {
    alert("<?=_("Bitte geben Sie Ihren tatsächlichen Nachnamen an.")?>");
     document.pers.nachname.focus();
    checked = false;
    }
 return checked;
}

function checkemail(){
 var re_email = <?=$validator->email_regular_expression?>;
 var email = document.pers.email.value;
 var checked = true;
 if (email!='<?=$my_about->auth_user["Email"]?>' && re_email.test(email)==false || email.length==0) {
    alert("<?=_("Die E-Mail-Adresse ist nicht korrekt!")?>");
     document.pers.email.focus();
    checked = false;
    }
 return checked;
}

function checkdata(){
 // kompletter Check aller Felder vor dem Abschicken
 var checked = true;
 if (document.pers.new_username && !checkusername())
    checked = false;
 if (document.pers.new_passwd_1 && !checkpassword())
    checked = false;
 if (document.pers.vorname && !checkvorname())
    checked = false;
 if (document.pers.nachname && !checknachname())
    checked = false;
 if (document.pers.email && !checkemail())
    checked = false;
 return checked;
}

function update_pw_fields() {
	document.getElementById('new_passwd_1').disabled = !document.getElementById('update_pw').checked;
    document.getElementById('new_passwd_2').disabled = !document.getElementById('update_pw').checked;

    if(document.getElementById('update_pw').checked) {
        if(document.getElementById('new_passwd_1').value == '*****') {
            document.getElementById('new_passwd_1').value = '';
        }

        if(document.getElementById('new_passwd_2').value == '*****') {
            document.getElementById('new_passwd_2').value = '';
        }
    } else {
        if(document.getElementById('new_passwd_1').value == '') {
            document.getElementById('new_passwd_1').value = '*****';
        }

        if(document.getElementById('new_passwd_2').value == '') {
            document.getElementById('new_passwd_2').value = '*****';
        }
    }
}
// -->
</SCRIPT>

<?
} // end if view == Daten
elseif( $view == 'Login') {
?>
<script type="text/javascript" language="javascript">
<!--
function oeffne()
{
    fenster=window.open('get_auto.php','','scrollbars=no,width=400,height=150','resizable=no');
    fenster.focus();
}
// -->
</SCRIPT>
<?
} // end if view == Login
} // Ende nur wenn JS aktiv

include ('lib/include/header.php');   // Output of Stud.IP head


if (!$cmd)
 {
 // darfst du ändern?? evtl erst ab autor ?
    $perm->check("user");
    $username = $my_about->auth_user["username"];
    //maximale spaltenzahl berechnen
     if ($auth->auth["jscript"]) $max_col = round($auth->auth["xres"] / 10 );
     else $max_col =  64 ; //default für 640x480

//Kopfzeile bei allen eigenen Modulen ausgeben
$table_open = FALSE;
if ($view != 'Forum'
        && $view != 'calendar'
        && $view != 'Stundenplan'
        && $view != 'Messaging'
        && $view != 'allgemein') {
    echo '<table class="blank" cellspacing=0 cellpadding=0 border=0 width="100%">'."\n";

//  echo '<tr><td class="'.(($username != $auth->auth["uname"])? 'topicwrite':'topic').'" colspan=2><img src="'. $GLOBALS['ASSETS_URL'] . 'images/einst.gif" border="0" align="texttop"><b>&nbsp;';


    if ($username != $auth->auth['uname']) {
        echo '<tr><td class="topicwrite" colspan="2"> &nbsp; &nbsp; <b><font size="-1">';
        printf(_("Daten von: %s %s (%s), Status: %s"), htmlReady($my_about->auth_user['Vorname']), htmlReady($my_about->auth_user['Nachname']), $username, $my_about->auth_user['perms']);
        echo '</font>';
    echo "</b></td></tr>\n";
    }
?>
        <tr>
            <td class="blank" colspan="2">&nbsp;</td>
        </tr>
    </table>
    <table class="blank" cellspacing="0" cellpadding="2" border="0" width="100%">
        <? if ($view == 'Daten' || $view == 'Lebenslauf' || $view == 'Studium' || $view == 'userdomains') :
        $info_text['Studium'] = _("Hier können Sie Angaben &uuml;ber ihre Studienkarriere machen.");
        $info_text['userdomains'] = _("Hier können Sie die Liste Ihrer Nutzerdomänen einsehen.");
        $info_text['Daten'] = _("Hier k&ouml;nnen sie Ihre Benutzerdaten ver&auml;ndern.") . '<br>' .
            sprintf(_("Alle mit einem Sternchen %s markierten Felder m&uuml;ssen ausgef&uuml;llt werden."), '</font><font color="red" size="+1"><b>*</b></font><font size="-1">');
        $info_text['Lebenslauf'] = _("Hier können Sie Angaben &uuml;ber ihre privaten Kontaktdaten sowie Lebenslauf und Hobbys machen.") . '<br>' .
            sprintf(_("Alle Angaben die Sie hier machen sind freiwillig!"));
        ?>
        <tr>
            <td class="blank"></td>
            <td align="right" valign="top" rowspan="10" width="270">
            <?
                $template = $GLOBALS['template_factory']->open('infobox/infobox_generic_content');
                $template->set_attribute('picture', 'infoboxes/groups.jpg');
                $content[] = array (
                    'kategorie' => _("Informationen:"),
                    'eintrag' => array(
                        array('icon' => 'ausruf_small.gif',
                            'text' => $info_text[$view]
                    )
                    )
                );
                $template->set_attribute('content', $content);
                echo $template->render();
            ?>
            </td>
        </tr>
    <?
    endif;

    $table_open = TRUE;
}

// evtl Fehlermeldung ausgeben
if ($my_about->msg) {
    $my_about->parse_msg($my_about->msg);
}

if ($view == 'Bild') {
    // hier wird das Bild ausgegeben
    $cssSw->switchClass();
    echo '<tr><td colspan=2 class="blank" style="padding-left:20px;">' . _("Auf dieser Seite k&ouml;nnen Sie ein pers&ouml;nliches Bild f&uuml;r Ihre Homepage hochladen.") . "<br><br><br></td></tr>\n";
    echo '<tr><td width="30%" class="'.$cssSw->getClass().'" align="center">';
    echo '<font size="-1"><b>' . _("Aktuell angezeigtes Bild:") . '<br><br></b></font>';

    echo Avatar::getAvatar($my_about->auth_user['user_id'])->getImageTag(Avatar::NORMAL);
    if (Avatar::getAvatar($my_about->auth_user['user_id'])->is_customized()) {
        ?>
        <form name="bild_loeschen" method="POST" action="<?= $GLOBALS['PHP_SELF'] ?>?studipticket=<?= get_ticket() ?>">
            <input type="hidden" name="user_id" value="<?= $my_about->auth_user["user_id"] ?>">
            <input type="hidden" name="username" value="<?= $username ?>">
            <input type="hidden" name="view" value="Bild">
            <input type="hidden" name="cmd" value="bild_loeschen">
            <font size="-1"><b><?= _("Aktuelles Bild") ?></b></font><br><input type="image" <?= makeButton("loeschen", "src") ?> border="0">
        </form>
    <?
    }

    echo '</td><td class="'.$cssSw->getClass().'" width="70%" align="left" valign="top">';
    echo '<form enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '?cmd=copy&username=' . $username . '&view=Bild&studipticket='.get_ticket().'" method="POST">';
    echo "<br>\n" . _("Hochladen eines Bildes:") . "<br><br>\n" . _("1. Wählen sie mit <b>Durchsuchen</b> eine Bilddatei von ihrer Festplatte aus.") . "<br><br>\n";
    echo '&nbsp;&nbsp;<input name="imgfile" type="file" style="width: 80%" cols="'.round($max_col*0.7*0.8)."\"><br><br>\n";
    echo _("2. Klicken sie auf <b>absenden</b>, um das Bild hochzuladen.") . "<br><br>\n";
    echo '&nbsp;&nbsp;<input type="image" ' . makeButton('absenden', 'src') . ' border="0" value="' . _("absenden") . "\"><br><br>\n";
    echo '<b>'. _("ACHTUNG!"). '</b><br>';
    printf (_("Die Bilddatei darf max. %d KB groß sein, es sind nur Dateien mit den Endungen %s, %s oder %s erlaubt!"), Avatar::MAX_FILE_SIZE / 1024, '<b>.jpg</b>', '<b>.png</b>', '<b>.gif</b>');
    echo '</form></td></tr>'."\n";
}

if ($view == 'Daten') {
    $cssSw->switchClass();
    //persönliche Daten...
    if ($my_about->auth_user['auth_plugin'] != "standard"){
        echo '<tr><td align="left" valign="top" class="blank" style="padding-left:20px;">';
        echo '<font size="-1">' . sprintf(_("Ihre Authentifizierung (%s) benutzt nicht die Stud.IP Datenbank, daher k&ouml;nnen Sie einige Felder nicht ver&auml;ndern!"),$my_about->auth_user['auth_plugin']) . "</font>";
        echo "<br><br></td></tr>\n";
    }
    echo '<tr><td class=blank>';

    echo '<form action="'. $PHP_SELF. '?cmd=edit_pers&username='. $username. '&view='. $view. '&studipticket=' . get_ticket(). '" method="POST" name="pers"';
    //Keine JavaScript überprüfung bei adminzugriff
    if ($my_about->check == 'user' && $auth->auth['jscript'] ) {
        echo ' onsubmit="return checkdata()" ';
    }
    echo '><table align="center" width="99%" class="blank" border="0" cellpadding="2" cellspacing="0">';
    if ($my_about->check == 'user') {
        echo "<tr><td class=\"".$cssSw->getClass()."\" width=\"25%\" align=\"left\"><b>" . _("Username:") . " </b></td><td class=\"".$cssSw->getClass()."\" colspan=2 width=\"75%\" align=\"left\">&nbsp;";
        if (($ALLOW_CHANGE_USERNAME && !StudipAuthAbstract::CheckField("auth_user_md5.username",$my_about->auth_user['auth_plugin'])) ) {
            echo "&nbsp;<input type=\"text\" size=\"".round($max_col*0.25)."\" name=\"new_username\" value=\"".$my_about->auth_user["username"]."\">&nbsp; <font color=\"red\" size=+2>*</font>";
        } else {
            echo "&nbsp;<font size=\"-1\">".$my_about->auth_user["username"]."</font>";
        }
    echo "</td></tr>\n";
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><div style=\"display:inline;float:left;\"><b>" . _("Passwort:") . " </b></div>";
    if (StudipAuthAbstract::CheckField("auth_user_md5.password", $my_about->auth_user['auth_plugin'])) {
        echo "<td class=\"".$cssSw->getClass()."\" colspan=\"2\" align=\"left\">&nbsp; <font size=\"-1\">*****</font>";
    } else {
        echo '<div style="display:inline;float:right;"> '._("ändern").'? <input type="checkbox" name="update_pw" id="update_pw" onclick="update_pw_fields();"></div></td>';
        echo '<td class="'.$cssSw->getClass().'" nowrap width="20%" align="left">';
        $pw_input = "<font size=-1>&nbsp; %s</font><br>&nbsp;"
                    ."<input type=\"password\" size=\"".round($max_col*0.25)."\" id=\"new_passwd_%s\" name=\"new_passwd_%s\"  %s value=\"*****\">";

        echo '<script>document.write(\''.sprintf($pw_input, _("neues Passwort:"), '1', '1', 'disabled').'\');</script>';
        // if javascript is disabled dont disable the input fields
        printf('<noscript>'.$pw_input.'</noscript>', _("neues Passwort:"), '1', '1','');
        echo "</td><td class=\"".$cssSw->getClass()."\" width=\"55%\" nowrap align=\"left\">";

        echo '<script>document.write(\''.sprintf($pw_input, _("Passwort Wiederholung:"), '2', '2','disabled').'\');</script>';
        // if javascript is disabled dont disable the input fields
        printf('<noscript>'.$pw_input.'</noscript>', _("Passwort Wiederholung:"), '2', '2','');
    }
    echo "</td></tr>\n";

    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Name:") . " </b></td><td class=\"".$cssSw->getClass()."\" nowrap align=\"left\"><font size=-1>&nbsp; " . _("Vorname:") . "</font><br>";
    if ((!$ALLOW_CHANGE_NAME) || StudipAuthAbstract::CheckField("auth_user_md5.Vorname", $my_about->auth_user['auth_plugin'])) {
            echo "&nbsp; <font size=\"-1\">" . htmlReady($my_about->auth_user["Vorname"])."</font>";
    } else {
            echo "&nbsp; <input type=\"text\" size=\"".round($max_col*0.25)."\" name=\"vorname\" value=\"".htmlReady($my_about->auth_user["Vorname"])."\">&nbsp; <font color=\"red\" size=+2>*</font>";
    }
    echo "</td><td class=\"".$cssSw->getClass()."\" nowrap align=\"left\"><font size=-1>&nbsp; " . _("Nachname:") . "</font><br>";
    if ((!$ALLOW_CHANGE_NAME) || StudipAuthAbstract::CheckField("auth_user_md5.Nachname", $my_about->auth_user['auth_plugin'])) {
        echo "&nbsp; <font size=\"-1\">" . htmlReady($my_about->auth_user["Nachname"])."</font>";
    } else {
        echo "&nbsp; <input type=\"text\" size=\"".round($max_col*0.25)."\" name=\"nachname\" value=\"".htmlReady($my_about->auth_user["Nachname"])."\">&nbsp; <font color=\"red\" size=+2>*</font>";
    }

    echo "</td></tr>\n";

    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("E-Mail:") . " </b></td><td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp;";
    if (($ALLOW_CHANGE_EMAIL && !(StudipAuthAbstract::CheckField("auth_user_md5.Email", $my_about->auth_user['auth_plugin'])))) {
        echo '<font size=-1>&nbsp; '. _("E-Mail:") .'</font><br>'.
             ' &nbsp; <input type="text" size="'. round($max_col*0.25). '" name="email1" value="'.$my_about->auth_user["Email"].'">&nbsp; <font color="red" size=+2>*</font>'.
             ' </td><td class="'. $cssSw->getClass() .'" align="left">'.
             '<font size=-1>&nbsp; '. _("E-Mail Wiederholung:") .'</font><br>'.
             '&nbsp; <input type="text" size="'. round($max_col*0.25).'" name="email2" value="'.$my_about->auth_user["Email"]. '">&nbsp; <font color="red" size=+2>*</font>';
    } else {
        echo "&nbsp; <font size=\"-1\">".$my_about->auth_user["Email"]."</font>";
    }
    echo "</td></tr>\n";
    } else {
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass()."\" width=\"25%\" align=\"left\"><b>" . _("Username:") . " </b></td><td class=\"".$cssSw->getClass()."\" width=\"25%\" align=\"left\">&nbsp; ".$my_about->auth_user["username"]."</td><td width=\"50%\" rowspan=4 align=\"center\"><b><font color=\"red\">" . _("Adminzugriff hier nicht möglich!") . "</font></b></td></tr>\n";
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Passwort:") . " </b>";
        echo "</td><td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp; *****</td></tr>\n";
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Name:") . " </b></td><td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp; ".htmlReady($my_about->auth_user["Vorname"]." ".$my_about->auth_user["Nachname"])."</td></tr>\n";
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("E-Mail:") . " </b></td><td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp; ".$my_about->auth_user["Email"]."</td></tr>\n";
    }
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Titel:") . " </b></td>";
    if (!$ALLOW_CHANGE_TITLE || StudipAuthAbstract::CheckField("user_info.title_front", $my_about->auth_user['auth_plugin'])) {
        echo "<td class=\"".$cssSw->getClass()."\" colspan=\"2\" align=\"left\">&nbsp;" .  htmlReady($my_about->user_info['title_front']) . "</td></tr>";
    } else {
        echo "<td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp;";
        echo "\n<select name=\"title_front_chooser\" onChange=\"document.pers.title_front.value=document.pers.title_front_chooser.options[document.pers.title_front_chooser.selectedIndex].text;\">";
        for($i = 0; $i < count($TITLE_FRONT_TEMPLATE); ++$i) {
            echo "\n<option";
            if ($TITLE_FRONT_TEMPLATE[$i] == $my_about->user_info['title_front']) {
                echo " selected ";
            }
            echo '>'.$TITLE_FRONT_TEMPLATE[$i].'</option>';
        }
        echo "</select></td><td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp;&nbsp;";
        echo "<input type=\"text\" size=\"".round($max_col*0.25)."\" name=\"title_front\" value=\"".htmlReady($my_about->user_info['title_front'])."\"></td></tr>\n";
    }
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\" nowrap><b>" . _("Titel nachgest.:") . " </b></td>";
    if (!$ALLOW_CHANGE_TITLE || StudipAuthAbstract::CheckField("user_info.title_rear", $my_about->auth_user['auth_plugin'])) {
        echo "<td class=\"".$cssSw->getClass()."\" colspan=\"2\" align=\"left\">&nbsp;" .  htmlReady($my_about->user_info['title_rear']) . "</td></tr>";
    } else {
        echo "<td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp;";
        echo "\n<select name=\"title_rear_chooser\" onChange=\"document.pers.title_rear.value=document.pers.title_rear_chooser.options[document.pers.title_rear_chooser.selectedIndex].text;\">";
        for($i = 0; $i < count($TITLE_REAR_TEMPLATE); ++$i) {
            echo "\n<option";
            if($TITLE_REAR_TEMPLATE[$i] == $my_about->user_info['title_rear']) {
                echo " selected ";
            }
            echo '>'.$TITLE_REAR_TEMPLATE[$i].'</option>';
        }
        echo "</select></td><td class=\"".$cssSw->getClass()."\" align=\"left\">&nbsp;&nbsp;";
        echo "<input type=\"text\" size=\"".round($max_col*0.25)."\" name=\"title_rear\" value=\"".htmlReady($my_about->user_info['title_rear'])."\"></td></tr>\n";
    }
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Geschlecht:") . " </b></td><td class=\"".$cssSw->getClass()."\" colspan=2 nowrap align=\"left\"><font size=-1>";
    if (StudipAuthAbstract::CheckField("user_info.geschlecht", $my_about->auth_user['auth_plugin'])) {
        echo "&nbsp;" . ($my_about->user_info["geschlecht"] == 1 ? _("m&auml;nnlich") : ($my_about->user_info["geschlecht"] == 2 ? _("weiblich") : _("unbekannt")));
    } else {
        echo "&nbsp; <input type=\"radio\" name=\"geschlecht\" value=\"0\" ";
        if (!$my_about->user_info["geschlecht"]) {
            echo "checked";
        }
        echo "> " . _("unbekannt");
        echo "&nbsp; <input type=\"radio\" name=\"geschlecht\" value=\"1\" ";
        if ($my_about->user_info["geschlecht"] == 1) {
            echo "checked";
        }
        echo "> " . _("männlich");
        echo "&nbsp; <input type=\"radio\" name=\"geschlecht\" value=\"2\" ";
        if ($my_about->user_info["geschlecht"] == 2) {
            echo "checked";
        }
        echo "> " . _("weiblich");
    }
    echo "</font></td></tr>";
    $cssSw->switchClass();


    echo "<tr><td class=\"".$cssSw->getClass()."\" colspan=\"3\" align=\"center\">&nbsp; <input type=\"IMAGE\" " . makeButton("uebernehmen", "src") . " border=0 value=\"" . _("Änderungen übernehmen") . "\"></td></tr>\n</table></form>\n</td></tr>";
}


//if ($view == 'Studium' && !$perm->have_perm("dozent")) {
if ($view == 'Studium') {

    if ($perm->have_perm('root') AND $username == $auth->auth["uname"]) {
        echo '<tr><td align="left" valign="top" class="blank">'."<br><br>\n" . _("Als Root haben Sie bereits genug Karriere gemacht ;-)") . "<br><br>\n";
    } else {
        echo '<tr><td align="left" valign="top" class="blank">'."\n";
    }

    //my profession degrees
    if (($my_about->auth_user['perms'] == 'autor' || $my_about->auth_user['perms'] == 'tutor' || $my_about->auth_user['perms'] == 'dozent')) {
        // nur für Autoren und Tutoren und Dozenten
        $allow_change_sg = (!StudipAuthAbstract::CheckField("studiengang_id", $my_about->auth_user['auth_plugin']) && ($GLOBALS['ALLOW_SELFASSIGN_STUDYCOURSE'] || $perm->have_perm('admin')))? TRUE : FALSE;

        $cssSw->resetClass();
        $cssSw->switchClass();
        echo '<tr><td class="blank">';
        echo '<h3>' . _("Ich studiere folgende Fächer und Abschlüsse:") . '</h3>';
        if ($allow_change_sg){
            echo '<form action="'. $_SERVER['PHP_SELF']. '?cmd=fach_abschluss_edit&username=' . $username . '&view=' . $view . '&studipticket=' . get_ticket() . '#studiengaenge" method="POST">';
        }
        echo '<table class="default">'."\n";
        echo '<tr><td><table width="100%" border="0" cellspacing="0" cellpadding="2">';
        reset ($my_about->user_fach_abschluss);
        $flag = FALSE;

        $i = 0;
        while (list ($studiengang_id,$details) = each ($my_about->user_fach_abschluss)) {
            if (!$i) {
                echo '<tr><th>' . _("Fach") . '</th>' ;
                echo '<th>' . _("Abschluss") . '</th>' ;
                echo '<th width="10%">' . _("Fachsemester") . '</th><th width="10%" align="center">' ;
                echo (($allow_change_sg)?  _("austragen") : '&nbsp;');
                echo '</th></tr>';
            }
            $cssSw->switchClass();
            echo '<tr>
                <td class="'.$cssSw->getClass().'">' . htmlReady($details['fname']). '</td>
                <td class="'.$cssSw->getClass().'">' . htmlReady($details['aname']). '</td>';
            if($allow_change_sg){
                echo '<td class="'.$cssSw->getClass().'">';
                echo '<input type="hidden" name="course_id[]" value = "'.$studiengang_id.'">';
                echo '<select name="change_fachsem[]">';
                for($i = 1; $i < 51; ++$i) {
                    echo '<option';
                    if ($i == $details['semester']) {
                        echo ' selected';
                    }
                    echo '>'.$i.'</option>';
                }
                echo '</select></td><td class="'. $cssSw->getClass().'" align="center">';
                echo '<input type="CHECKBOX" name="fach_abschluss_delete[]" value="'.$studiengang_id.'">';
            } else {
                echo '<td class="'.$cssSw->getClass().'">' . htmlReady($details['semester']). '</td><td class="' . $cssSw->getClass().'" align="center">';
                echo '<img src="'. $GLOBALS['ASSETS_URL'] . 'images/haken_transparent.gif">';
            }

            echo "</td><tr>\n";
            $i++;
            $flag = TRUE;
        }

        if (!$flag && $allow_change_sg) {
            echo '<tr><td class="'.$cssSw->getClass().'" colspan="2"><br><font size=-1><b>' . _("Sie haben sich noch keinem Studiengang zugeordnet.") . "</b><br><br>\n" . _("Tragen Sie bitte hier die Angaben aus Ihrem Studierendenausweis ein!") . "</font></td><tr>\n";
        }
        $cssSw->resetClass();
        $cssSw->switchClass();
        echo '</table></td></tr><tr><td class="'.$cssSw->getClass().'" width="40%" align="left" valign="top"><br>';
        if($allow_change_sg){
            echo _("Wählen Sie die Fächer, Abschlüsse und Fachsemester in der folgenden Liste aus:") . "<br>\n";
            echo '<br><div align="left"><a name="studiengaenge">&nbsp;';
            $my_about->select_studiengang();
            echo '<a name="abschluss">&nbsp;</a>';
            $my_about->select_abschluss();
            echo '<a name="semester">&nbsp;</a>';
            echo '<select name="fachsem" selected="yes">';
            for ($s=1; $s < 51; $s++) {
                echo '<option>'.$s.'</option>';
            }
            echo '</select>';
            echo '</div><br></b>' . _("Wenn Sie einen Studiengang wieder austragen möchten, markieren Sie die entsprechenden Felder in der oberen Tabelle.") . "<br>\n";
            echo _("Mit einem Klick auf <b>&Uuml;bernehmen</b> werden die gewählten Änderungen durchgeführt.") . "<br><br>\n";
            echo '<input type="image" ' . makeButton('uebernehmen', 'src') . ' value="' . _("Änderungen übernehmen") . '">';
            echo "</form>\n";
        } else {
            echo _("Die Informationen zu Ihrem Studiengang werden vom System verwaltet, und k&ouml;nnen daher von Ihnen nicht ge&auml;ndert werden.");
        }
        echo '</td></tr></table>'."\n";
        if ($allow_change_sg) echo "</form>\n";
    }

    #echo "</td></tr>\n";
    // end my profession and degrees


    //Institute, an denen studiert wird
    if (($my_about->auth_user["perms"]=="autor" || $my_about->auth_user["perms"]=="tutor" || $my_about->auth_user["perms"]=="dozent")) {
        $allow_change_in = ($GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] || $perm->have_perm('admin'))? TRUE:FALSE;
        $cssSw->resetClass();
        $cssSw->switchClass();
        echo '<tr><td class="blank">';
        echo "<h3>" . _("Ich studiere an folgenden Einrichtungen:") . "</h3>";
        if ($allow_change_in) echo '<form action="' . $_SERVER['PHP_SELF'] . '?cmd=inst_edit&username='.$username.'&view='.$view.'&studipticket=' . get_ticket() . '#einrichtungen" method="POST">'. "\n";
        echo '<table class="default">'."\n";
        echo '<tr><td><table width="100%" border="0" cellspacing="0" cellpadding="2">';
        reset ($my_about->user_inst);
        $flag=FALSE;
        $i=0;
        while (list ($inst_id,$details) = each ($my_about->user_inst)) {
            if ($details['inst_perms'] == 'user') {
                if (!$i) {
                    echo '<tr><th>' . _("Einrichtung") . '</th><th width="10%" align="center">';
                    echo  (($allow_change_in)? _("austragen") : '');
                    echo "</td></tr>\n";
                }
                $cssSw->switchClass();
                echo '<tr>
                    <td class="' . $cssSw->getClass() . '">' . htmlReady($details['Name']) . '</td>
                    <td class="' . $cssSw->getClass() . '" align="center">';
                if ($allow_change_in) {
                    echo '<input type="CHECKBOX" name="inst_delete[]" value="'.$inst_id.'">';
                } else {
                    echo '<img src="'. $GLOBALS['ASSETS_URL'] . 'images/haken_transparent.gif">';
                }
                echo "</td></tr>\n";
                $i++;
                $flag = TRUE;
            }
        }
        if (!$flag && $allow_change_in) {
            echo '<tr><td class="'.$cssSw->getClass().'" colspan="2"><br><font size="-1"><b>' . _("Sie haben sich noch keinen Einrichtungen zugeordnet.") . "</b><br><br>\n" . _("Wenn Sie auf ihrer Homepage die Einrichtungen, an denen Sie studieren, auflisten wollen, k&ouml;nnen Sie diese Einrichtungen hier entragen.") . "</font></td></tr>";
        }
        $cssSw->resetClass();
        $cssSw->switchClass();
        echo '</table></td></tr><tr><td class="' . $cssSw->getClass() . '"><br>'."\n" ;
        if ($allow_change_in){
            echo _("Um sich als Student einer Einrichtung zuzuordnen, wählen Sie die entsprechende Einrichtung aus der folgenden Liste aus:") . "<br>\n";
            echo "<br>\n".'<div align="left"><a name="einrichtungen"></a>';
            $my_about->select_inst();
            echo "</div><br>" . _("Wenn sie aus Einrichtungen wieder ausgetragen werden möchten, markieren Sie die entsprechenden Felder in der linken Tabelle.") . "<br>\n";
            echo _("Mit einem Klick auf <b>&Uuml;bernehmen</b> werden die gewählten Änderungen durchgeführt.") . "<br><br> \n";
            echo '<input type="image" ' . makeButton('uebernehmen', 'src') . ' value="' . _("Änderungen übernehmen") . '">';
        } else {
            echo _("Die Informationen zu Ihrer Einrichtung werden vom System verwaltet, und k&ouml;nnen daher von Ihnen nicht ge&auml;ndert werden.");
        }
        echo '</td></tr></table>';
        if ($allow_change_in) echo '</form>';
    }
    echo '</td></tr>';

}


if ($view == 'userdomains') {
    if ($perm->have_perm('root') && $username == $auth->auth["uname"]) {
        echo '<tr><td align="left" valign="top" class="blank">'."<br><br>\n" . _("Als Root haben Sie keine Nutzerdomänen.") . "<br><br>\n";
    } else {
        echo '<tr><td align="left" valign="top" class="blank">'."\n";
    }

    // Nutzerdomänen, die mir zugeordnet sind
    $allow_change_ud = !StudipAuthAbstract::CheckField("userdomain_id", $my_about->auth_user['auth_plugin']) && $perm->have_perm('admin');

    $cssSw->resetClass();
    $cssSw->switchClass();
    echo '<tr><td class="blank" valign="top">';
    echo '<b>&nbsp; ' . _("Ich bin folgenden Nutzerdomänen zugeordnet:") . '</b>';
    if ($allow_change_ud){
        echo '<form action="'.URLHelper::getLink('?cmd=userdomain_edit&username='.$username.'&view='.$view.'&studipticket='.get_ticket().'#userdomains').'" method="POST">';
    }
    echo '<table width="99%" align="center" border="0" cellpadding="2" cellspacing="0">'."\n";
    echo '<tr><td width="30%" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">';

    $flag = FALSE;
    $i = 0;
    foreach ($my_about->user_userdomains as $domain) {
        if (!$i) {
            echo '<tr><td class="steelgraudunkel" width="80%">' . _("Nutzerdomäne") . '</td><td class="steelgraudunkel" width="30%">' ;
            echo (($allow_change_ud)?  _("austragen") : '&nbsp;');
            echo '</td></tr>';
        }
        $cssSw->switchClass();
        echo '<tr><td class="'.$cssSw->getClass().'" width="80%">' . htmlReady($domain->getName()) . '</td><td class="' . $cssSw->getClass().'" width="20%" align="center">';
        if ($allow_change_ud){
            echo '<input type="CHECKBOX" name="userdomain_delete[]" value="'.$domain->getID().'">';
        } else {
            echo '<img src="'. $GLOBALS['ASSETS_URL'] . 'images/haken_transparent.gif" border="0">';
        }
        echo "</td><tr>\n";
        $i++;
        $flag = TRUE;
    }

    if (!$flag && $allow_change_ud) {
        echo '<tr><td class="'.$cssSw->getClass().'" colspan="2"><br><font size=-1><b>' . _("Sie sind noch keiner Nutzerdomäne zugeordnet.") . "</b><br><br>\n" . "</font></td><tr>\n";
    }
    $cssSw->resetClass();
    $cssSw->switchClass();
    echo '</table></td><td class="'.$cssSw->getClass().'" width="70%" align="left" valign="top"><br>';
    if($allow_change_ud){
        echo _("Wählen Sie eine Nutzerdomäne aus der folgenden Liste aus:") . "<br>\n";
        echo '<br><div align="center"><a name="userdomains">&nbsp;</a>';
        $my_about->select_userdomain();
        echo '</div><br></b>' . _("Wenn Sie Nutzerdomänen wieder entfernen möchten, markieren Sie die entsprechenden Felder in der linken Tabelle.") . "<br>\n";
        echo _("Mit einem Klick auf <b>&Uuml;bernehmen</b> werden die gewählten Änderungen durchgeführt.") . "<br><br>\n";
        echo '<input type="image" ' . makeButton('uebernehmen', 'src') . ' value="' . _("Änderungen übernehmen") . '">';
        echo "</form>\n";
    } else {
        echo _("Die Informationen zu Ihren Nutzerdomänen werden vom System verwaltet und k&ouml;nnen daher von Ihnen nicht ge&auml;ndert werden.");
    }
    echo '</td></tr></table>'."\n";
    if ($allow_change_ud) echo "</form>\n";
    echo "</td></tr>\n";
}


if ($view == 'Karriere') {

    $all_rights = false;
    if ($my_about->auth['username'] != $username) {
        $db_r = new DB_Seminar();

        if ($auth->auth['perm'] == "root"){
            $all_rights = true;
            $db_r->query("SELECT Institut_id, Name, 1 AS is_fak  FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");
        } elseif ($auth->auth['perm'] == "admin") {
            $db_r->query("SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                    WHERE a.user_id='$user->id' AND a.inst_perms='admin' ORDER BY is_fak,Name");
        } else {
            $db_r->query("SELECT a.Institut_id,Name FROM user_inst a LEFT JOIN Institute b USING (Institut_id) WHERE inst_perms IN('tutor','dozent') AND user_id='$user->id' ORDER BY Name");
        }

        $inst_rights = array();
        $admin_insts = array();
        while ($db_r->next_record()) {
            if ($auth->auth['perm'] == 'admin' && $db_r->f('is_fak')) {
                $db_r2 = new DB_Seminar("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" .$db_r->f("Institut_id") . "' AND institut_id!='" .$db_r->f("Institut_id") . "' ORDER BY Name");
                while ($db_r2->next_record()) {
                    $inst_rights[] = $db_r2->f('Institut_id');
                }
            }
            $inst_rights[] = $db_r->f('Institut_id');
            $admin_insts[] = $db_r->Record;
        }
    } else {
        $all_rights = true;
    }
    foreach ($admin_insts as $data) {
        if ($data["is_fak"]) {
            $stmt = DBManager::get()->prepare("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id = ? AND Institut_id != ? ORDER BY Name");
            if ($stmt->execute(array($data['Institut_id'], $data['Institut_id']))) {
                while($sub_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sub_admin_insts[$data['Institut_id']][$sub_data['Institut_id']] = $sub_data;
                }
            }
        }
    }

    // a group has been chosen to be opened / closed
    if ($_REQUEST['switch']) {
        if ($edit_about_data['open'] == $_REQUEST['switch']) {
            $edit_about_data['open'] = '';
        } else {
            $edit_about_data['open'] = $_REQUEST['switch'];
        }
    }

    if ($_REQUEST['open']) {
        $edit_about_data['open'] = $_REQUEST['open'];
    }

    echo '<tr><td class=blank>';

    // get the roles the user is in
    $institutes = array();
    foreach ($my_about->user_inst as $inst_id => $details) {
        if ($details['inst_perms'] != 'user') {
            $institutes[$inst_id] = $details;
            $roles = GetAllStatusgruppen($inst_id, $my_about->auth_user['user_id'], true);
            $institutes[$inst_id]['roles'] = ($roles) ? $roles : array();
        }
    }


    // template for tree-view of roles, layout for infobox-location and content-variables
    $template = $GLOBALS['template_factory']->open('statusgruppen/roles_edit_about');
    $template->set_layout('statusgruppen/layout_edit_about');
    $template->set_attribute('open', $edit_about_data['open']); // the ids of the currently opened statusgroups
    $template->set_attribute('messages', $msgs);
    $template->set_attribute('institutes', $institutes);

    $template->set_attribute('view', $view);
    $template->set_attribute('username', $username);
    $template->set_attribute('user_id', $my_about->auth_user['user_id']);
    $template->set_attribute('allowed_status', $my_about->allowedInstitutePerms());

    // data for edit_about_add_person_to_role
    $template->set_attribute('subview_id', $subview_id);
    $template->set_attribute('admin_insts', $admin_insts);
    $template->set_attribute('sub_admin_insts', $sub_admin_insts);

    echo $template->render();

    echo '</td></tr>';
}

if ($view == 'Lebenslauf') {
    $cssSw->switchClass();

    echo "<tr><td class=blank>";
    echo '<form action="' . $_SERVER['PHP_SELF'] . '?cmd=edit_leben&username=' . $username . '&view=' . $view . '&studipticket=' . get_ticket() . '" method="POST" name="pers">';
    echo '<table align="center" width="99%" align="center" border="0" cellpadding="2" cellspacing="0">' . "\n";

     $cssSw->switchClass();
    echo '<tr><td class="'.$cssSw->getClass(). '" width="25%" align="left"><b>' . _("Telefon (privat):") . ' </b></td>';
    ?>
        <td class="<?= $cssSw->getClass() ?>"  width="25%" align="left" nowrap>
            <font size="-1">
                &nbsp; <?= _("Festnetz") ?>:
            </font>
            <br>
    <?
    if (StudipAuthAbstract::CheckField('user_info.privatnr', $my_about->auth_user['auth_plugin'])) {
        echo '&nbsp;' . htmlReady($my_about->user_info['privatnr']);
    } else {
        echo '&nbsp; <input type="text" size="' .round($max_col*0.25).'" name="telefon" value="'. htmlReady($my_about->user_info["privatnr"]). '">';
    }
    echo '<td class="'.$cssSw->getClass(). '"  width="50%" align="left"><font size="-1">&nbsp; '. _("Mobiltelefon"). ":</font><br>\n";
    if (StudipAuthAbstract::CheckField('user_info.privatcell', $my_about->auth_user['auth_plugin'])) {
        echo '&nbsp;' . htmlReady($my_about->user_info['privatcell']);
    } else {
        echo '&nbsp; <input type="text" size="' .round($max_col*0.25). '" name="cell" value="' .htmlReady($my_about->user_info['privatcell']).'">';
    }
    echo "</td></tr>\n";
     $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Adresse (privat):") . " </b></td><td class=\"".$cssSw->getClass()."\" colspan=2 align=\"left\">";
    if (StudipAuthAbstract::CheckField("user_info.privadr", $my_about->auth_user['auth_plugin'])) {
        echo "&nbsp;" . htmlReady($my_about->user_info["privadr"]);
    } else {
        echo "&nbsp; <input type=\"text\" size=\"".round($max_col*0.6)."\" name=\"anschrift\" value=\"".htmlReady($my_about->user_info["privadr"])."\">";
    }
    echo "</td></tr>\n";
    if (get_config("ENABLE_SKYPE_INFO")) {
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Skype:") . " </b></td>";
        echo "<td class=\"".$cssSw->getClass()."\" align=\"left\">";
        echo "<font size=\"-1\">&nbsp; " . _("Skype Name:") . "</font><br>&nbsp; <input type=\"text\" size=\"".round($max_col*0.25)."\" name=\"skype_name\" value=\"".htmlReady(UserConfig::get($my_about->auth_user['user_id'])->SKYPE_NAME)."\"></td>";
        echo "<td class=\"".$cssSw->getClass()."\" align=\"left\">";
        echo "<font size=\"-1\">&nbsp; "  . _("Skype Online Status anzeigen:") . "</font><br>&nbsp;<input type=\"checkbox\" name=\"skype_online_status\" value=\"1\" ". (UserConfig::get($my_about->auth_user['user_id'])->SKYPE_ONLINE_STATUS ? 'checked' : '') . "></td>";
        echo "</tr>\n";
    }
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Motto:") . " </b></td><td class=\"".$cssSw->getClass()."\" colspan=2 align=\"left\">";
    if (StudipAuthAbstract::CheckField("user_info.motto", $my_about->auth_user['auth_plugin'])) {
        echo "&nbsp;" . htmlReady($my_about->user_info["motto"]);
    } else {
        echo "&nbsp; <input type=\"text\" size=\"".round($max_col*0.6)."\" name=\"motto\" value=\"".htmlReady($my_about->user_info["motto"])."\">";

    }   echo "</td></tr>\n";
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Homepage:") . " </b></td><td class=\"".$cssSw->getClass()."\" colspan=2 align=\"left\">";
    if (StudipAuthAbstract::CheckField("user_info.Home", $my_about->auth_user['auth_plugin'])) {
        echo "&nbsp;" . htmlReady($my_about->user_info["Home"]);
    } else {
        echo "&nbsp; <input type=\"text\" size=\"".round($max_col*0.6)."\" name=\"home\" value=\"".htmlReady($my_about->user_info["Home"])."\">";

    }
    echo "</td></tr>\n";
    $cssSw->switchClass();
    echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\"><b>" . _("Hobbys:") . " </b></td><td class=\"".$cssSw->getClass()."\" colspan=2 align=\"left\">";
    if (StudipAuthAbstract::CheckField("user_info.hobby", $my_about->auth_user['auth_plugin'])) {
        echo "&nbsp;" . htmlReady($my_about->user_info["hobby"]);
    } else {
        echo "&nbsp; <textarea  name=\"hobby\"  style=\"width: 50%\" cols=".round($max_col*0.5)." rows=4 maxlength=250 wrap=virtual >".htmlReady($my_about->user_info["hobby"])."</textarea>";
    }
    echo "</td></tr>\n";
    $cssSw->switchClass();


    /*echo '<tr><td align="left" valign="top" class="blank"><br>'."\n";
    if ($my_about->auth_user['perms'] == 'dozent') {
         echo _("Hier k&ouml;nnen Sie Lebenslauf, Publikationen und Arbeitschwerpunkte bearbeiten.");
    } else {
        echo  _("Hier k&ouml;nnen Sie Ihren Lebenslauf bearbeiten.");
    }
    echo "<br>&nbsp; </td></tr>\n"; */

    echo '<tr><td class="'.$cssSw->getClass().'" align="left"><b>' . _("Lebenslauf:") . "</b></td>\n";
    echo '<td class="'. $cssSw->getClass() .'" colspan="2" align="left" valign="top">&nbsp;', "\n";
    echo '<textarea  name="lebenslauf" style=" width: 80%" cols="'.round($max_col/1.3).'" rows="7" wrap="virtual">' . htmlReady($my_about->user_info['lebenslauf']).'</textarea><a name="lebenslauf"></a></td></tr>'."\n";
    if ($my_about->auth_user["perms"] == "dozent") {
        $cssSw->switchClass();
        echo '<tr><td class="'.$cssSw->getClass().'" align="left"><b>' . _("Schwerpunkte:") . "</b></td>\n";
        echo '<td class="'. $cssSw->getClass() .'" colspan="2" align="left" valign="top">&nbsp;', "\n";
        echo '<textarea  name="schwerp" style="width: 80%" cols="'.round($max_col/1.3).'" rows="7" wrap="virtual">'.htmlReady($my_about->user_info["schwerp"]).'</textarea><a name="schwerpunkte"></a></td></tr>'."\n";
        $cssSw->switchClass();
        echo "<tr><td class=\"".$cssSw->getClass(). '" align="left" ><b>' . _("Publikationen:") . "</b></td>\n";
        echo '<td class="'. $cssSw->getClass() .'" colspan="2" align="left" valign="top">&nbsp;', "\n";
        echo '<textarea  name="publi" style=" width: 80%" cols="'.round($max_col/1.3) . '" rows="7" wrap="virtual">'.htmlReady($my_about->user_info['publi']).'</textarea><a name="publikationen"></a></td></tr>'."\n";
    }

    //add the free administrable datafields
    $userEntries = DataFieldEntry::getDataFieldEntries($my_about->auth_user['user_id']);
    foreach ($userEntries as $id => $entry) {
        $color = '#000000';
        if ($invalidEntries[$id]) {
            $entry = $invalidEntries[$id];
            $color = '#ff0000';
        }
        if ($entry->isVisible()) {
            $cssSw->switchClass();
            echo "<tr><td class=\"".$cssSw->getClass()."\" align=\"left\" ><b>";
            echo "<font color=\"$color\">" . htmlReady($entry->getName()). ":</font></b></td>";
            echo '<td class="'. $cssSw->getClass() .'" colspan="2" align="left" valign="top">&nbsp;', "\n";
            if ($entry->isEditable()) {
                echo $entry->getHTML("datafields");
            }
            else {
                echo formatReady($entry->getDisplayValue(false));
                echo "<br><br><hr><font size=\"-1\">"._("(Das Feld ist f&uuml;r die Bearbeitung gesperrt und kann nur durch einen Administrator ver&auml;ndert werden.)")."</font>";
            }
        }
    }

    $cssSw->switchClass();
    echo '<tr><td class="'.$cssSw->getClass().'" colspan="3" align="center"><br><input type="image" ' . makeButton('uebernehmen', 'src') . ' border="0" value="' . _("Änderungen übernehmen") . "\"><br></td></tr>\n</table>\n</form>\n</td></tr>";
}

if ($view == "Sonstiges") {
    if ($freie == "create_freie") create_freie();
    if ($freie == "delete_freie") delete_freie($freie_id);
    if ($freie == "verify_delete_freie") verify_delete_freie($freie_id);
    if ($freie == "update_freie") update_freie();
    if ($freie == "order_freie") order_freie($cat_id,$direction,$username);
    print_freie($username);
}

// Ab hier die Views der MyStudip-Sektion

if ($view=="rss") {
        if ($rss=="create_rss") create_rss();
        if ($rss=="delete_rss") delete_rss($rss_id);
        if ($rss=="update_rss") update_rss();
        if ($rss=="order_rss") order_rss($cat_id,$direction,$username);
        print_rss($username);
}


if($view == "allgemein") {
    require_once('lib/mystudip.inc.php');
    change_general_view();
}

if($view == "Forum") {
    require_once('lib/include/forumsettings.inc.php');
}

if ($view == "Stundenplan") {
    require_once('lib/include/ms_stundenplan.inc.php');
    check_schedule_default();
    change_schedule_view();
}

if($view == 'calendar' && get_config('CALENDAR_ENABLE')) {
    require_once($GLOBALS['RELATIVE_PATH_CALENDAR'].'/calendar_settings.inc.php');
}

if ($view == "Messaging") {
    require_once('lib/include/messagingSettings.inc.php');
    check_messaging_default();
    change_messaging_view();
}

if ($view == 'Login') {
    echo '<tr><td colspan="2" class="blank">'."<br><br>\n" ;
    if ($my_about->check == 'user' && !$perm->have_perm('admin')) {
        echo _("Um die automatische Anmeldung zu nutzen, m&uuml;ssen Sie ihre pers&ouml;nliche Login-Datei auf ihren Rechner kopieren. Mit dem folgenden Link &ouml;ffnet sich ein Fenster, indem Sie ihr Passwort eingeben m&uuml;ssen.") . " ";
        echo _("Dann wird die Datei erstellt und zu Ihrem Rechner geschickt.") . "<br><br>\n";
        echo '<div align="center"><b><a href="javascript:oeffne();">' . _("Auto-Login-Datei erzeugen") . '</a></b></div>';
        echo "<br><br>\n" . _("<b>ACHTUNG!</b> Die automatische Anmeldung stellt eine große Sicherheitslücke dar. Jeder, der Zugriff auf Ihren Rechner hat, kann sich damit unter Ihrem Namen in Stud.IP einloggen!");
        echo "<br><br>\n";
        echo _("Eine sichere Variante besteht aus folgendem Link:") . "<br>\n";
        echo '<div align="center"><b><a href="index.php?again=yes&shortcut=' . $auth->auth['uname'] . '">'. sprintf( _("Stud.IP - Login (%s)"), $auth->auth['uname']) ."</a></b></div><br>\n";
        echo _("Speichern Sie diesen Link als Bookmark oder Favoriten.") . "<br>\n";
        echo _("Er f&uuml;hrt Sie direkt zum Login-Bildschirm von Stud.IP mit Ihrem schon eingetragenen Benutzernamen. Sie m&uuml;ssen nur noch Ihr Passwort eingeben.");
    } else {
        echo _("Als Administrator d&uuml;rfen Sie dieses Feature nicht nutzen - Sie tragen Verantwortung!");

    }
    echo "<br>\n</td></tr>\n";
}

if ($view == 'privacy') {
    require_once ('lib/include/privacy.inc.php');
}

    if ($table_open) echo "\n</table>\n";

    include ('lib/include/html_end.inc.php');
}

page_close();
?>
