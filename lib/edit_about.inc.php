<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// edit_about.inc.php
// administration of personal home page, helper functions
//
// Copyright (C) 2008 Till Glöggler <tgloeggl@uos.de>
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

require_once('lib/messaging.inc.php');
require_once('lib/log_events.inc.php');
require_once('lib/classes/StudipNews.class.php');
require_once('lib/calendar/lib/SingleCalendar.class.php');
require_once('lib/calendar/lib/DbCalendarEventList.class.php');
require_once('lib/vote/VoteDB.class.php');
require_once('lib/evaluation/classes/db/EvaluationDB.class.php');
require_once('lib/classes/StudipLitList.class.php');
require_once('lib/classes/guestbook.class.php');
require_once('lib/classes/Avatar.class.php');

function edit_email($uid, $email, $force=False) {
    $msg = '';

    $db = new DB_Seminar(sprintf("SELECT email, username, auth_plugin FROM auth_user_md5 WHERE user_id='%s'", $uid));
    $db->next_record();
    $email_cur = $db->f('email');
    $username = $db->f('username');
    $auth_plugin = $db->f('auth_plugin');

    if($email_cur == $email && !$force) {
        return array(True, $msg);
    }

    if(StudipAuthAbstract::CheckField("auth_user_md5.Email", $auth_plugin) || LockRules::check($uid, 'email')) {
        return array(False, $msg);
    }

    if(!$GLOBALS['ALLOW_CHANGE_EMAIL']) {
        return array(False, $msg);
    }

    $validator = new email_validation_class; ## Klasse zum Ueberpruefen der Eingaben
    $validator->timeout = 10;
    $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
    $Zeit = date("H:i:s, d.m.Y",time());

    // accept only registered domains if set
    $email_restriction = trim(get_config('EMAIL_DOMAIN_RESTRICTION'));
    if (!$validator->ValidateEmailAddress($email, $email_restriction)) {
        if ($email_restriction) {
            $email_restriction_msg_part = '';
            $email_restriction_parts = explode(',', $email_restriction);
            for ($email_restriction_count = 0; $email_restriction_count < count($email_restriction_parts); $email_restriction_count++) {
                if ($email_restriction_count == count($email_restriction_parts) - 1) {
                    $email_restriction_msg_part .= '@' . trim($email_restriction_parts[$email_restriction_count]) . '<br>';
                } else if (($email_restriction_count + 1) % 3) {
                    $email_restriction_msg_part .= '@' . trim($email_restriction_parts[$email_restriction_count]) . ', ';
                } else {
                    $email_restriction_msg_part .= '@' . trim($email_restriction_parts[$email_restriction_count]) . ',<br>';
                }
            }
            $msg.= 'error§'
                .sprintf(_("Die E-Mail-Adresse fehlt, ist falsch geschrieben oder gehört nicht zu folgenden Domains:%s"),
                            '<br>' . $email_restriction_msg_part);
        } else {
            $msg.= "error§" . _("Die E-Mail-Adresse fehlt oder ist falsch geschrieben!") . "§";
        }
        return array(False, $msg);        // E-Mail syntaktisch nicht korrekt oder fehlend
    }

    if (!$validator->ValidateEmailHost($email)) {     // Mailserver nicht erreichbar, ablehnen
        $msg.=  "error§" . _("Der Mailserver ist nicht erreichbar. Bitte &uuml;berpr&uuml;fen Sie, ob Sie E-Mails mit der angegebenen Adresse verschicken k&ouml;nnen!") . "§";
        return array(False, $msg);
    } else {       // Server ereichbar
        if (!$validator->ValidateEmailBox($email)) {    // aber user unbekannt. Mail an abuse!
            StudipMail::sendAbuseMessage("edit_about", "Emailbox unbekannt\n\nUser: ". $username ."\nEmail: $email\n\nIP: $REMOTE_ADDR\nZeit: $Zeit\n");
            $msg.=  "error§" . _("Die angegebene E-Mail-Adresse ist nicht erreichbar. Bitte &uuml;berpr&uuml;fen Sie Ihre Angaben!") . "§";
            return array(False, $msg);
        }
    }

    $db->query("SELECT user_id, Email,Vorname,Nachname FROM auth_user_md5 WHERE Email='$email'") ;
    if ($db->next_record() and $db->f('user_id') != $uid) {
        $msg.=  "error§" . sprintf(_("Die angegebene E-Mail-Adresse wird bereits von einem anderen Benutzer (%s %s) verwendet. Bitte geben Sie eine andere E-Mail-Adresse an."), htmlReady($db->f("Vorname")), htmlReady($db->f("Nachname"))) . "§";
        return array(False, $msg);
    }

    $db->query("UPDATE auth_user_md5 SET Email='$email' WHERE user_id='".$uid."'");

    if (StudipAuthAbstract::CheckField("auth_user_md5.validation_key", $auth_plugin)) {
        $msg.= "msg§" . _("Ihre E-Mail-Adresse wurde ge&auml;ndert!") . "§";
        return array(True, $msg);
    } else {
        // auth_plugin does not map validation_key (what if...?)

        // generate 10 char activation key
        $key = '';
        mt_srand((double)microtime()*1000000);
        for ($i=1;$i<= 10;$i++) {
            $temp = mt_rand() % 36;
            if ($temp < 10)
                $temp += 48;   // 0 = chr(48), 9 = chr(57)
            else
                $temp += 87;   // a = chr(97), z = chr(122)
            $key .= chr($temp);
        }

        $activatation_url = $GLOBALS['ABSOLUTE_URI_STUDIP']
                            .'activate_email.php?uid='. $uid
                            .'&key='. $key;

        // include language-specific subject and mailbody with fallback to german
        $lang = $GLOBALS['_language_path']; // workaround
        if($lang == '') {
            $lang = 'de';
        }
        include_once("locale/$lang/LC_MAILS/change_self_mail.inc.php");

        $mail = StudipMail::sendMessage($email, $subject, $mailbody);

        if(!$mail) {
            return array(True, $msg);
        }

        $msg.= "info§<b>" . sprintf(_('An Ihre neue E-Mail-Adresse <b>%s</b> wurde ein Aktivierungslink geschickt, dem Sie folgen müssen bevor Sie sich das nächste mal einloggen können.'), $email). '</b>§';
        $db->query("UPDATE auth_user_md5 SET validation_key='$key' WHERE user_id='".$uid."'");
        log_event("USER_NEWPWD",$uid); // logging
    }
    return array(True, $msg);
}

/*
function parse_datafields($user_id) {
    global $datafield_id, $datafield_type, $datafield_content;
    global $my_about;

    if (is_array($datafield_id)) {
        $ffCount = 0; // number of processed form fields
        foreach ($datafield_id as $i=>$id) {
            $struct = new DataFieldStructure($zw = array("datafield_id"=>$id, 'type'=>$datafield_type[$i]));
            $entry  = DataFieldEntry::createDataFieldEntry($struct, $user_id);
            $numFields = $entry->numberOfHTMLFields(); // number of form fields used by this datafield
            if ($datafield_type[$i] == 'bool' && $datafield_content[$ffCount] != $id) { // unchecked checkbox?
                $entry->setValue('');
                $ffCount -= $numFields;  // unchecked checkboxes are not submitted by GET/POST
            }
            elseif ($numFields == 1)
                $entry->setValue($datafield_content[$ffCount]);
            else
                $entry->setValue(array_slice($datafield_content, $ffCount, $numFields));
            $ffCount += $numFields;

            $entry->structure->load();
            if ($entry->isValid()) {
                $entry->store();
            }   else {
                $invalidEntries[$struct->getID()] = $entry;
            }
        }
        // change visibility of role data
            foreach ($group_id as $groupID)
            setOptionsOfStGroup($groupID, $u_id, ($visible[$groupID] == '0') ? '0' : '1');
        $my_about->msg .= 'msg§'. _("Die Daten wurden gespeichert!").'§';
        if (is_array($invalidEntries)) {
            foreach ($invalidEntries as $field) {
                $name = $field->structure->getName();
                $my_about->msg .= 'error§'. sprintf(_("Fehlerhafte Eingabe im Datenfeld %s (wurde nicht gespeichert)!"), "<b>$name</b>") .'§';
            }
        }
    }

    return $invalidEntries;
}
*/

// class definition
class about extends messaging {

    var $db;     //unsere Datenbankverbindung
    var $auth_user = array();        // assoziatives Array, enthält die Benutzerdaten aus der Tabelle auth_user_md5
    var $user_info = array();        // assoziatives Array, enthält die Benutzerdaten aus der Tabelle user_info
    var $user_inst = array();        // assoziatives Array, enthält die Benutzerdaten aus der Tabelle user_inst
    var $user_fach_abschluss = array(); // assoziatives Array, enthält die Benutzerdaten aus der Tabelle user_studiengang
    var $user_userdomains = array(); // assoziatives Array, enthält die Benutzerdaten aus der Tabelle user_userdomains
    var $check = "";    //Hilfsvariable für den Rechtecheck
    var $special_user = FALSE;  // Hilfsvariable für bes. Institutsfunktionen
    var $msg = ""; //enthält evtl Fehlermeldungen
    var $logout_user = FALSE; //Hilfsvariable, zeigt an, ob der Benutzer ausgeloggt werden muß
    var $priv_msg = "";  //Änderungsnachricht bei Adminzugriff
    var $default_url = "http://www"; //default fuer private URL


    function about($username,$msg) {  // Konstruktor, prüft die Rechte
        global $user,$perm,$auth;

        $this->db = new DB_Seminar;
        $this->get_auth_user($username);
        $this->dataFieldEntries = DataFieldEntry::getDataFieldEntries($this->auth_user["user_id"]);
        $this->msg = $msg; //Meldungen restaurieren

        // der user selbst natürlich auch
        if ($auth->auth["uname"] == $username AND $perm->have_perm("autor"))
            $this->check="user";
        // Vertretungen dürfen auch, wenn das freigegeben ist
        else if (isDeputyEditAboutActivated() && isDeputy($user->id, get_userid($username), true))
            $this->check='user';
        //bei admins schauen wir mal
        elseif ($auth->auth["perm"]=="admin") {
            $this->db->query("SELECT a.user_id FROM user_inst AS a LEFT JOIN user_inst AS b USING (Institut_id) WHERE (b.inst_perms='admin' AND b.user_id='$user->id') AND (a.user_id='".$this->auth_user["user_id"]."' AND a.inst_perms IN ('dozent','tutor','autor'))");
            if ($this->db->num_rows())
                $this->check="admin";

            if ($perm->is_fak_admin()){
                $this->db->query("SELECT c.user_id FROM user_inst a LEFT JOIN Institute b ON(a.Institut_id=b.fakultaets_id)  LEFT JOIN user_inst c ON(b.Institut_id=c.Institut_id) WHERE a.user_id='$user->id' AND a.inst_perms='admin' AND c.user_id='".$this->auth_user["user_id"]."'");
                if ($this->db->next_record())
                    $this->check="admin";
            }
        }
        //root darf mal wieder alles
        elseif ($auth->auth["perm"]=="root")
            $this->check="admin";
        else
            $this->check="";
        //hier ist wohl was falschgelaufen...
        if ($this->auth_user["username"]=="")
            $this->check="";

        return;
    }


    function get_auth_user($username) {
        //ein paar userdaten brauchen wir schon mal
        $this->db->query("SELECT * FROM auth_user_md5 WHERE username = '$username'");
        $fields = $this->db->metadata();
        if ($this->db->next_record()) {
            for ($i=0; $i<count($fields); $i++) {
                $field_name = $fields[$i]["name"];
                $this->auth_user[$field_name] = $this->db->f("$field_name");
            }
        }
        if (!$this->auth_user['auth_plugin']){
            $this->auth_user['auth_plugin'] = "standard";
        }
    }

    // füllt die arrays  mit Daten
    function get_user_details() {
        $this->db->query("SELECT * FROM user_info WHERE user_id = '".$this->auth_user["user_id"]."'");
        $fields = $this->db->metadata();
        if ($this->db->next_record()) {
            for ($i=0; $i<count($fields); $i++) {
                $field_name = $fields[$i]["name"];
                $this->user_info[$field_name] = $this->db->f("$field_name");
                if (!$this->user_info["Home"])
                    $this->user_info["Home"]=$this->default_url;
            }
        }

       $this->db->query("SELECT user_studiengang.*,studiengaenge.name AS fname, abschluss.name AS aname, semester FROM user_studiengang LEFT JOIN studiengaenge USING (studiengang_id) LEFT JOIN abschluss USING (abschluss_id) WHERE user_id = '".$this->auth_user["user_id"]."' ORDER BY fname,aname");
        while ($this->db->next_record()) {
            $this->user_fach_abschluss[$this->db->f("studiengang_id")] = array(
                                                                     "fname" => $this->db->f("fname"),
                                                                     "semester" => $this->db->f("semester"),
                                                                     "aname" => $this->db->f("aname"));
        }

        $this->user_userdomains = UserDomain::getUserDomainsForUser($this->auth_user['user_id']);

        $this->db->query("SELECT user_inst.*,Institute.Name FROM user_inst LEFT JOIN Institute USING (Institut_id) WHERE user_id = '".$this->auth_user["user_id"]."' ORDER BY priority ASC, Institut_id ASC");
        while ($this->db->next_record()) {
            $this->user_inst[$this->db->f("Institut_id")] =
                array("inst_perms" => $this->db->f("inst_perms"),
                        "sprechzeiten" => $this->db->f("sprechzeiten"),
                        "raum" => $this->db->f("raum"),
                        "Telefon" => $this->db->f("Telefon"),
                        "Fax" => $this->db->f("Fax"),
                        "Name" => $this->db->f("Name"),
                        "externdefault" => $this->db->f("externdefault"),
                        "priority" => $this->db->f("priority"),
                        "visible" => $this->db->f("visible"));
            if ($this->db->f("inst_perms")!="user")
                $this->special_user=TRUE;
        }

        return;
    }

    /**
     * add, edit, delete courses of study
     * @param array $fach_abschluss_delete
     * @param string $new_studiengang
     * @param string $new_abschluss
     * @param int $fachsem
     * @param array $change_fachsem
     * @param array $course_id
     */
function fach_abschluss_edit($fach_abschluss_delete,$new_studiengang,$new_abschluss,$fachsem,$change_fachsem,$course_id) {

        $any_change = true;
        if (is_array($fach_abschluss_delete)) {
            $any_change = false;
            for ($i=0; $i < count($fach_abschluss_delete); $i++) {
                $this->db->query("DELETE FROM user_studiengang WHERE user_id='".$this->auth_user["user_id"]."' AND studiengang_id='$fach_abschluss_delete[$i]'");
                if ($this->db->affected_rows()) {
                    $delete = true;
                }
            }
        }

        if ($any_change) {
            if ( is_array($change_fachsem)) {
                for ($i=0; $i < count($change_fachsem); $i++) {
                    $this->db->query("UPDATE IGNORE user_studiengang SET user_studiengang.semester = '".$change_fachsem[$i]."' WHERE user_studiengang.user_id='".$this->auth_user["user_id"]."' AND user_studiengang.studiengang_id='$course_id[$i]'");
                    if ($this->db->affected_rows()) {
                        $edit_fachsem = true;
                    }
                }
            }

            if ($new_studiengang && $new_studiengang != 'none') {
                $this->db->query("INSERT IGNORE INTO user_studiengang (user_id,studiengang_id,abschluss_id,semester) VALUES ('".$this->auth_user["user_id"]."','$new_studiengang','$new_abschluss','$fachsem')");
                if ($this->db->affected_rows()) {
                    $new = true;
                }
            }
        }
        if ( ($new || $delete|| $edit_fachsem) && !$this->msg) {
            $this->msg = "msg§" . _("Die Zuordnung zu Studiengängen wurde ge&auml;ndert.");
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg .= _("Die Zuordnung zu Studiengängen wurde geändert!\n");
            restoreLanguage();
        }

        return;
    }
    function userdomain_edit ($userdomain_delete, $new_userdomain) {
        if (is_array($userdomain_delete)) {
            for ($i=0; $i < count($userdomain_delete); $i++) {
                $domain = new UserDomain($userdomain_delete[$i]);
                $domain->removeUser($this->auth_user['user_id']);
            }
        }

        if ($new_userdomain) {
            $domain = new UserDomain($new_userdomain);
            $domain->addUser($this->auth_user['user_id']);
        }

        if (($userdomain_delete || $new_userdomain) && !$this->msg) {
            $this->msg = "msg§" . _("Die Zuordnung zu Nutzerdomänen wurde ge&auml;ndert.");
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg .= _("Die Zuordnung zu Nutzerdomänen wurde geändert!\n");
            restoreLanguage();
        }
    }

    function inst_edit($inst_delete, $new_inst)
    {
        if (is_array($inst_delete)) {
            for ($i=0; $i < count($inst_delete); $i++) {
                $this->db->query("DELETE FROM user_inst WHERE user_id='".$this->auth_user["user_id"]."' AND Institut_id='$inst_delete[$i]'");
                if ($this->db->affected_rows()) {
                    $delete = true;
                    log_event('INST_USER_DEL', $inst_delete[$i], $this->auth_user["user_id"]);
                }
            }
        }

        if ($new_inst) {
            $this->db->query("INSERT IGNORE INTO user_inst (user_id,Institut_id,inst_perms) VALUES ('".$this->auth_user["user_id"]."','$new_inst','user')");
            if ($this->db->affected_rows()) {
                log_event('INST_USER_ADD', $new_inst , $this->auth_user['user_id'], 'user');
                $new = true;
            }

        }

        if ( $delete || $new ) {
            $this->msg = "msg§" . _("Die Zuordnung zu Einrichtungen wurde ge&auml;ndert.");
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg .= _("Die Zuordnung zu Einrichtungen wurde geändert!\n");
            restoreLanguage();
        }

        return;
    }

    /**
     * This function returns the perms allowed for an institute for the current user
     *
     * @return array list of perms
     */
    function allowedInstitutePerms() {

        // find out the allowed perms
        $possible_perms=array("autor","tutor","dozent");
        $counter=0;
        if ($this->auth_user["perms"] == "admin")
            $allowed_status = array ('admin'); // einmal admin, immer admin...
        else {
            $allowed_status = array();
            while ($counter <= 3 ) {
                $allowed_status[] = $possible_perms[$counter];
                if ($possible_perms[$counter] == $this->auth_user['perms'])
                    break;
                $counter++;
            }
        }

        return $allowed_status;
    }

    function special_edit ($raum, $sprech, $tel, $fax, $name, $default_inst, $visible, $datafields, $group_id, $role_id, $status) {
        if (!LockRules::Check($this->auth_user["user_id"], 'institute_data')) {
            if (is_array($raum)) {
                list($inst_id, $detail) = each($raum);
                $query = "UPDATE user_inst SET raum='$detail', sprechzeiten='$sprech[$inst_id]', ";
                $query .= "Telefon='$tel[$inst_id]', Fax='$fax[$inst_id]'";
                $query .= " WHERE Institut_id='$inst_id' AND user_id='" . $this->auth_user["user_id"] . "'";
                $this->db->query($query);
                if ($this->db->affected_rows()) {
                    $this->msg = $this->msg . "msg§" . sprintf(_("Ihre Daten an der Einrichtung %s wurden ge&auml;ndert"), htmlReady($name[$inst_id])) . "§";
                    setTempLanguage($this->auth_user["user_id"]);
                    $this->priv_msg .= $this->priv_msg . sprintf(_("Ihre Daten an der Einrichtung %s wurden geändert.\n"), htmlReady($name[$inst_id]));
                    restoreLanguage();
                }
            }
        }
        $inst_id = $status['inst_id'];
        if ($default_inst == $inst_id) {
            $this->db->query("UPDATE user_inst SET externdefault = 0 WHERE user_id = '".$this->auth_user['user_id']."'");
        }
        $query = "UPDATE user_inst SET externdefault=";
        $query .= $default_inst == $inst_id ? '1' : '0';
        $query .= ", visible=" . (isset($visible[$inst_id]) ? '0' : '1');
        $query .= " WHERE Institut_id='$inst_id' AND user_id='" . $this->auth_user["user_id"] . "'";
        $this->db->query($query);

        if ($status['status'] && $status['inst_id']) {
            $stmt = DBManager::get()->prepare("SELECT inst_perms FROM user_inst WHERE user_id = ? AND Institut_id = ?");
            if ($stmt->execute(array($this->auth_user['user_id'], $status['inst_id']))) {
                $data = $stmt->fetch();
                if ($data['inst_perms'] != $status['status'] && in_array($status['status'], $this->allowedInstitutePerms())) {
                    $this->msg .= 'msg§'. _("Der Status wurde geändert!") .'§';

                    log_event("INST_USER_STATUS", $status['inst_id'], $this->auth_user['user_id'], $GLOBALS['user']->id .' -> '. $status['status']);

                    $stmt = DBManager::get()->prepare("UPDATE user_inst SET inst_perms = ? WHERE user_id = ? AND Institut_id = ?");
                    $stmt->execute(array($status['status'], $this->auth_user['user_id'], $status['inst_id']));
                }
            }
        }
        // process user role datafields
        $sec_range_id = $inst_id ? $inst_id : $role_id;
        if (is_array($datafields)) {
            foreach ($datafields as $id => $data) {
                $struct = new DataFieldStructure(array("datafield_id"=>$id));
                $struct->load();
                $entry  = DataFieldEntry::createDataFieldEntry($struct, array($this->auth_user['user_id'], $sec_range_id ));
                $entry->setValueFromSubmit($data);
                if ($entry->isValid())
                    $entry->store();
                else
                    $invalidEntries[$struct->getID()] = $entry;
            }
            // change visibility of role data
            if (is_array($group_id))
                foreach ($group_id as $groupID)
                    setOptionsOfStGroup($groupID, $this->auth_user['user_id'], ($visible[$groupID] == '0') ? '0' : '1');
        }
        return $invalidEntries;
    }


    function edit_private($telefon, $cell, $anschrift, $home, $motto, $hobby) {
        $query = "";

        if ($home == $this->default_url) {
            $home = '';
        }

        if (!StudipAuthAbstract::CheckField("user_info.privatnr", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'privatnr')){
            $query .= "privatnr='$telefon',";
        }

        if (!StudipAuthAbstract::CheckField("user_info.privatcell", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'privatcell')){
            $query .= "privatcell='$cell',";
        }

        if (!StudipAuthAbstract::CheckField("user_info.privadr", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'privadr')){
            $query .= "privadr='$anschrift',";
        }
        if (!StudipAuthAbstract::CheckField("user_info.Home", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'home')){
            $query .= "Home='$home',";
        }
        if (!StudipAuthAbstract::CheckField("user_info.motto", $this->auth_user['auth_plugin'])){
            $query .= "motto='$motto',";
        }
        if (!StudipAuthAbstract::CheckField("user_info.hobby", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'hobby')){
            $query .= "hobby='$hobby',";
        }

        $query = "UPDATE user_info SET " . $query . " chdate='".time()."' WHERE user_id='".$this->auth_user["user_id"]."'";
        DBManager::get()->query($query);
        $this->priv_msg .= _("Private Daten wurden geändert.\n");
    }


    function edit_leben($lebenslauf,$schwerp,$publi,$view, $datafields) {
        //Update additional data-fields
        $invalidEntries = array();
        if (is_array($datafields)) {
            foreach ($this->dataFieldEntries as $id => $entry) {
                if(isset($datafields[$id])){
                $entry->setValueFromSubmit($datafields[$id]);
                if ($entry->isValid())
                    $resultDataFields |= $entry->store();
                else
                    $invalidEntries[$id] = $entry;
                }
            }
        }

        //check ob die blobs verändert wurden...
        $this->db->query("SELECT  lebenslauf, schwerp, publi FROM user_info WHERE user_id='".$this->auth_user["user_id"]."'");
        $this->db->next_record();
        foreach(words('lebenslauf schwerp publi') as $param) {
            if (LockRules::check($this->auth_user['user_id'], $param)) {
                $$param = $this->db->f($param);
            }
        }
        if ($lebenslauf!=$this->db->f("lebenslauf") || $schwerp!=$this->db->f("schwerp") || $publi!=$this->db->f("publi") || $resultDataFields) {
            $this->db->query("UPDATE user_info SET lebenslauf='$lebenslauf', schwerp='$schwerp', publi='$publi', chdate='".time()."' WHERE user_id='".$this->auth_user["user_id"]."'");
            $this->msg = $this->msg . "msg§" . _("Daten im Lebenslauf u.a. wurden ge&auml;ndert") . "§";
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg .= _("Daten im Lebenslauf u.a. wurden geändert.\n");
            restoreLanguage();
        }
        return $invalidEntries;
    }


    function edit_pers($password, $new_username, $vorname, $nachname, $email, $geschlecht, $title_front, $title_front_chooser, $title_rear, $title_rear_chooser, $view) {
        global $UNI_NAME_CLEAN, $_language_path, $auth, $perm;
        global $ALLOW_CHANGE_USERNAME, $ALLOW_CHANGE_EMAIL, $ALLOW_CHANGE_NAME, $ALLOW_CHANGE_TITLE;

        //erstmal die "unwichtigen" Daten
        if($title_front == "")
            $title_front = $title_front_chooser;
        if($title_rear == "")
            $title_rear = $title_rear_chooser;
        $query = "";
        if (!StudipAuthAbstract::CheckField("user_info.geschlecht", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'gender')){
            $query .= "geschlecht='$geschlecht',";
        }
        if ($ALLOW_CHANGE_TITLE && !StudipAuthAbstract::CheckField("user_info.title_front", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'title')){
            $query .= "title_front='$title_front',";
        }
        if ($ALLOW_CHANGE_TITLE && !StudipAuthAbstract::CheckField("user_info.title_rear", $this->auth_user['auth_plugin']) && !LockRules::check($this->auth_user['user_id'], 'title')){
            $query .= "title_rear='$title_rear',";
        }
        if ($query != "") {
            $query = "UPDATE user_info SET " . $query . " chdate='".time()."' WHERE user_id='".$this->auth_user["user_id"]."'";
            $this->db->query($query);
            if ($this->db->affected_rows()) {
                $this->msg = $this->msg . "msg§" . _("Ihre pers&ouml;nlichen Daten wurden ge&auml;ndert.") . "§";
                setTempLanguage($this->auth_user["user_id"]);
                $this->priv_msg .= _("Ihre persönlichen Daten wurden geändert.\n");
                restoreLanguage();
            }
        }

        $new_username = trim($new_username);
        $vorname = trim($vorname);
        $nachname = trim($nachname);
        $email = trim($email);

        //nur nötig wenn der user selbst seine daten ändert
        if ($this->check == "user") {
            //erstmal die Syntax checken $validator wird in der local.inc.php benutzt, sollte also funzen
            $validator=new email_validation_class; ## Klasse zum Ueberpruefen der Eingaben
            $validator->timeout=10;

            if (!StudipAuthAbstract::CheckField("auth_user_md5.password", $this->auth_user['auth_plugin']) && $password!="*****" && !LockRules::check($this->auth_user['user_id'], 'password')) {      //Passwort verändert ?

                // auf doppelte Vergabe wird weiter unten getestet.
                if (!$validator->ValidatePassword($password)) {
                    $this->msg=$this->msg . "error§" . _("Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.") . "§";
                    return false;
                }
                $newpass = md5($password);

                $this->db->query("UPDATE auth_user_md5 SET password='$newpass' WHERE user_id='".$this->auth_user["user_id"]."'");
                $this->msg=$this->msg . "msg§" . _("Ihr Passwort wurde ge&auml;ndert!") . "§";
            }

            if (!StudipAuthAbstract::CheckField('auth_user_md5.Vorname', $this->auth_user['auth_plugin']) && $vorname != $this->auth_user['Vorname'] && !LockRules::check($this->auth_user['user_id'], 'name')) { //Vornamen verändert ?
                if ($ALLOW_CHANGE_NAME) {
                    if (!$validator->ValidateName($vorname)) {
                        $this->msg=$this->msg . "error§" . _("Der Vorname fehlt oder ist unsinnig!") . "§";
                        return false;
                    }   // Vorname nicht korrekt oder fehlend
                    $this->db->query("UPDATE auth_user_md5 SET Vorname='$vorname' WHERE user_id='".$this->auth_user["user_id"]."'");
                    $this->msg=$this->msg . "msg§" . _("Ihr Vorname wurde ge&auml;ndert!") . "§";
                } else $vorname = $this->auth_user['Vorname'];
            }

            if (!StudipAuthAbstract::CheckField('auth_user_md5.Nachname', $this->auth_user['auth_plugin']) && $nachname != $this->auth_user['Nachname'] && !LockRules::check($this->auth_user['user_id'], 'name')) { //Namen verändert ?
                if ($ALLOW_CHANGE_NAME) {
                    if (!$validator->ValidateName($nachname)) {
                        $this->msg=$this->msg . "error§" . _("Der Nachname fehlt oder ist unsinnig!") . "§";
                        return false;
                    }   // Nachname nicht korrekt oder fehlend
                    $this->db->query("UPDATE auth_user_md5 SET Nachname='$nachname' WHERE user_id='".$this->auth_user["user_id"]."'");
                    $this->msg=$this->msg . "msg§" . _("Ihr Nachname wurde ge&auml;ndert!") . "§";
                } else $nachname = $this->auth_user['Nachname'];
            }


            if (!StudipAuthAbstract::CheckField('auth_user_md5.username', $this->auth_user['auth_plugin']) && $this->auth_user['username'] != $new_username && !LockRules::check($this->auth_user['user_id'], 'username')) {
                if ($ALLOW_CHANGE_USERNAME) {
                    if (!$validator->ValidateUsername($new_username)) {
                        $this->msg=$this->msg . "error§" . _("Der gewählte Benutzername ist nicht lang genug!") . "§";
                        return false;
                    }
                    $check_uname = StudipAuthAbstract::CheckUsername($new_username);
                    if ($check_uname['found']) {
                        $this->msg .= "error§" . _("Der Benutzername wird bereits von einem anderen Benutzer verwendet. Bitte wählen Sie einen anderen Usernamen!") . "§";
                        return false;
                    } else {
                        //$this->msg .= "info§" . $check_uname['error'] ."§";
                    }
                    $this->db->query("UPDATE auth_user_md5 SET username='$new_username' WHERE user_id='".$this->auth_user["user_id"]."'");
                    $this->msg=$this->msg . "msg§" . _("Ihr Benutzername wurde ge&auml;ndert!") . "§";
                    $this->logout_user = TRUE;
                } else $new_username = $this->auth_user['username'];
            }

        }
        return;
    }

    function edit_email($email) {
        $return = edit_email($this->auth_user["user_id"], $email);
        $this->msg.= $return[1];
        return $return[0];
    }


    /**
     * Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Studiengängen
     */
    public function select_studiengang()
    {
        echo '<select name="new_studiengang">'."\n";
        echo '<option selected="selected" value="none">' . _('-- Bitte Fach auswählen --') . '</option>'."\n";
        $this->db->query("SELECT a.studiengang_id,a.name FROM studiengaenge AS a LEFT JOIN user_studiengang AS b ON (b.user_id='".$this->auth_user["user_id"]."' AND a.studiengang_id=b.studiengang_id) WHERE b.studiengang_id IS NULL ORDER BY a.name");
        while ($this->db->next_record()) {
            echo "<option value=\"".$this->db->f("studiengang_id")."\">".htmlReady(my_substr($this->db->f("name"),0,50))."</option>\n";
        }
        echo "</select>\n";
        return;
    }

    /**
     * Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Abschluesse
     */
   public function select_abschluss()
   {
        echo '<select name="new_abschluss">'."\n";
        echo '<option selected="selected" value="none">'. _('-- Bitte Abschluss auswählen --') . '</option>'."\n";
        $this->db->query("SELECT abschluss_id,name FROM abschluss ORDER BY name");
        while ($this->db->next_record()) {
            echo "<option value=\"".$this->db->f("abschluss_id")."\">".htmlReady(my_substr($this->db->f("name"),0,50))."</option>\n";
        }
        echo "</select>\n";
        return;
    }

    function select_userdomain() {  //Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Nutzerdomänen

        echo '<select name="new_userdomain">'."\n";
        echo '<option selected="selected" value="none">' . _('-- Bitte Nutzerdomäne auswählen --') . '</option>'."\n";
        $user_domains = UserDomain::getUserDomainsForUser($this->auth_user['user_id']);
        $domains = UserDomain::getUserDomains();

        foreach (array_diff($domains, $user_domains) as $domain) {
            echo "<option value=\"".$domain->getID()."\">".htmlReady(my_substr($domain->getName(),0,50))."</option>\n";
        }
        echo "</select>\n";
    }

    /**
     * Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Instituten
     */
    function select_inst()
    {

        echo '<select name="new_inst" id="select_new_inst"><option selected="selected" value=""> ' . _("-- Bitte Einrichtung auswählen --") . ' </option>'."\n";
        $this->db->query("SELECT a.Institut_id,a.Name FROM Institute AS a LEFT JOIN user_inst AS b ON (b.user_id='".$this->auth_user["user_id"]."' AND a.Institut_id=b.Institut_id) WHERE b.Institut_id IS NULL ORDER BY a.Name");
        while ($this->db->next_record()) {
            echo "<option value=\"".$this->db->f("Institut_id")."\">".htmlReady(my_substr($this->db->f("Name"),0,50))."</option>\n";
        }
        echo "</select>\n";
        return;
    }

    //Displays Errosmessages (kritischer Abbruch, Symbol "X")

    function my_error($msg)
    {
        echo '<tr><td>';
        echo MessageBox::error($msg);
        echo '</td></tr>';
    }


    //Displays  Successmessages (Information &uuml;ber erfolgreiche Aktion, Symbol Haken)

    function my_msg($msg)
    {
        echo '<tr><td>';
        echo MessageBox::success($msg);
        echo '</td></tr>';
    }

    //Displays  Informationmessages  (Hinweisnachrichten, Symbol Ausrufungszeichen)

    function my_info($msg)
    {
        echo '<tr><td>';
        echo MessageBox::info($msg);
        echo '</td></tr>';
    }

    function parse_msg($long_msg,$separator="§") {

        $msg = explode ($separator,$long_msg);
        for ($i=0; $i < count($msg); $i=$i+2) {
            switch ($msg[$i]) {
                case "error" : $this->my_error($msg[$i+1]); break;
                case "info" : $this->my_info($msg[$i+1]); break;
                case "msg" : $this->my_msg($msg[$i+1]); break;
            }
        }
        return;
    }

    function move ($inst_id, $direction) {
        if ($this->check == 'user' || $this->check == 'admin') {
            $db = new DB_Seminar();
            $query = "SELECT * FROM user_inst WHERE user_id = '{$this->auth_user['user_id']}' ";
            $query .= "AND inst_perms != 'user' ORDER BY priority ASC";
            $db->query($query);
            $i = 1;
            while ($db->next_record()) {
                $to_order[$i] = $db->f('Institut_id');
                if ($to_order[$i] == $inst_id)
                    $pos = $i;
                $i++;
            }
            if ($direction == 'up') {
                $a = $to_order[$pos - 1];
                $to_order[$pos - 1] = $to_order[$pos];
                $to_order[$pos] = $a;
            }
            else {
                $a = $to_order[$pos + 1];
                $to_order[$pos + 1] = $to_order[$pos];
                $to_order[$pos] = $a;
            }
            $i--;
            for (;$i > 0; $i--) {
                $query = "UPDATE user_inst SET priority = $i WHERE user_id = '{$this->auth_user['user_id']}' ";
                $query .= "AND Institut_id = '{$to_order[$i]}'";
                $db->query($query);
            }
        }
    }

    /**
     * Changes visibility settings for the current user.
     *
     * @param string $global global visibility of the account in Stud.IP
     * @param string $online visiblity in "Who is online" list
     * @param string $chat visibility of the private chatroom in active chats list
     * @param string $search visiblity in user search
     * @param string $email visibility of the email address
     * @return boolean All settings saved?
     */
    function change_global_visibility($global, $online, $chat, $search, $email, $foaf_show_identity) {
        $success = false;
        // Globally visible or unknown -> set local visibilities accordingly.
        if ($global != 'no') {
            $online = $online ? 1 : 0;
            $chat = $chat ? 1 : 0;
            $search = $search ? 1 : 0;
            $email = $email ? 1 : 0;
            $foaf_show_identity = $foaf_show_identity ? 1 : 0;
        // Globally invisible -> set all local fields to invisible.
        } else {
            $online = 0;
            $chat = 0;
            $search = 0;
            $email = get_config('DOZENT_ALLOW_HIDE_EMAIL') ? 0 : 1;
            $success1 = $this->change_all_homepage_visibility(VISIBILITY_ME);
            $foaf_show_identity = 0;
        }
        $user_cfg = UserConfig::get($this->auth_user["user_id"]);
        $user_cfg->store("FOAF_SHOW_IDENTITY", $foaf_show_identity);
        
        $success2 = DBManager::get()->exec("UPDATE auth_user_md5 SET visible='".$global."' WHERE user_id='".$this->auth_user["user_id"]."'");
        $data = DBManager::get()->query("SELECT `user_id` FROM `user_visibility` WHERE `user_id`='".$this->auth_user["user_id"]."'");
        if ($data->fetch()) {
            $success3 = DBManager::get()->exec("UPDATE user_visibility
                SET online=".$online.", chat=".$chat.", search=".$search.", email=".$email."
                WHERE user_id='".$this->auth_user["user_id"]."'");
        } else {
            $success3 = DBManager::get()->exec("INSERT INTO user_visibility
                SET `user_id`='".$this->auth_user["user_id"]."', `online`=".$online.", `chat`=".$chat.", `search`=".$search.", `email`=".$email.", `mkdate`=".time());
        }
        return true;
    }

    /**
     * Changes the visibility of all homepage elements to the given value.
     *
     * @param int $new_visibility new visiblity of homepage elements, one of
     * the visibility constants defined in lib/user_visible.inc.php.
     * @return array All of the user's hoempage elements with new visibility
     * set.
     */
    function change_all_homepage_visibility($new_visibility) {
        $result = array();
        $new_data = array();
        $db_result = array();
        // Retrieve homepage elements.
        $data = $this->get_homepage_elements();
        // Iterate through data and set new visibility.
        foreach ($data as $key => $entry) {
            $new_data[$key] = array("name" => $entry["name"], "visibility" => $new_visibility);
            if ($entry["extern"]) {
                $new_data[$key]["extern"] = true;
            }
            $new_data[$key]['category'] = $entry['category'];
            $db_result[$key] = $new_visibility;
        }
        $success = $this->change_homepage_visibility($db_result);
        if ($success) {
            $result = $new_data;
        }
        return $result;
    }

    /**
     * Sets a default visibility for elements that are added to a user's
     * homepage but whose visibility hasn't been configured explicitly yet.
     *
     * @param int $visibility default visibility for new homepage elements
     * @return Number of affected database rows (hopefully 1).
     */
    function set_default_homepage_visibility($visibility) {
        $success = false;
        $existing = DBManager::get()->query(
            "SELECT `user_id` FROM `user_visibility` WHERE `user_id`='".
            $this->auth_user["user_id"]."'")->fetch();
        if ($existing) {
            $query = "UPDATE `user_visibility` SET `default_homepage_visibility`=".
                intval($visibility)." WHERE `user_id`='".
                $this->auth_user["user_id"]."'";
        } else {
            $query = "INSERT INTO `user_visibility` SET `user_id`='".
                $this->auth_user["user_id"]."', `default_homepage_visibility`=".
                intval($visibility).", `mkdate`=".time();
        }
        $success = DBManager::get()->exec($query);
        return $success;
    }

    /**
     * Saves user specified visibility settings for homepage elements.
     *
     * @param array $data all homepage elements with their visiblities in
     * the form $name => $visibility
     * @return int Number of affected database rows (hopefully 1).
     */
    function change_homepage_visibility($data) {
        $success = false;
        $db = DBManager::get();
        $existing = $db->query(
            "SELECT `user_id` FROM `user_visibility` WHERE `user_id`=".
            $db->quote($this->auth_user["user_id"]))->fetch();
        if ($existing) {
            $query = "UPDATE `user_visibility` SET `homepage`=".$db->quote(json_encode($data)).
                " WHERE user_id=".$db->quote($this->auth_user["user_id"]);
        } else {
            $query = "INSERT INTO `user_visibility` SET `user_id`=".
                $db->quote($this->auth_user["user_id"]).", `homepage`=".
                $db->quote(json_encode($data)).", `mkdate`=".time();
        }
        $success = $db->exec($query);
        return $success;
    }

    /**
     * Builds an array containing all available elements that are part of a
     * user's homepage together with their visibility. It isn't sufficient to
     * just load the visibility settings from database, because if the user
     * has added some data (e.g. CV) but not yet assigned a special visibility
     * to that field, it wouldn't show up.
     *
     * @return array An array containing all available homepage elements
     * together with their visibility settings in the form
     * $name => $visibility.
     */
    function get_homepage_elements() {
        global $NOT_HIDEABLE_FIELDS;
        $homepage_elements = array();
        $my_data = DBManager::get()->query("SELECT user_info.*, auth_user_md5.* FROM auth_user_md5 LEFT JOIN user_info USING (user_id) WHERE auth_user_md5.user_id = '".$this->auth_user['user_id']."'");
        $my_data = $my_data->fetch();

        $homepage_visibility = get_local_visibility_by_id($this->auth_user['user_id'], 'homepage');
        if (is_array(json_decode($homepage_visibility, true))) {
            $homepage_visibility = json_decode($homepage_visibility, true);
        } else {
            $homepage_visibility = array();
        }

        // News
        $news = StudipNews::GetNewsByRange($this->auth_user['user_id'], true);
        // Non-private dates.
        if ($GLOBALS["CALENDAR_ENABLE"]) {
            $dates = new DbCalendarEventList(new SingleCalendar($this->auth_user['user_id']), time(), -1, TRUE);
            $dates = $dates->events;
        }
        // Votes
        if (get_config('VOTE_ENABLE')) {
            $voteDB = new VoteDB();
            $activeVotes  = $voteDB->getActiveVotes($this->auth_user['user_id']);
            $stoppedVotes = $voteDB->getStoppedVisibleVotes($this->auth_user['user_id']);
        }
        // Evaluations
        $evalDB = new EvaluationDB();
        $activeEvals = $evalDB->getEvaluationIDs($this->auth_user['user_id'], EVAL_STATE_ACTIVE);
        // Literature
        $lit_list = StudipLitList::GetFormattedListsByRange($this->auth_user['user_id']);
        // Free datafields
        $data_fields = DataFieldEntry::getDataFieldEntries($this->auth_user['user_id']);
        $guestbook = new Guestbook($this->auth_user['user_id'], true, 1);
        $guestbook = $guestbook->checkGuestbook();
        // Homepage plugins
        //$homepageplugins = PluginEngine::getPlugins('HomepagePlugin');
        // Deactivate plugin visibility settings because they aren't working now.
        $homepageplugins = array();

        $user_domains = count(UserDomain::getUserDomains());

        // Now join all available elements with visibility settings.
        $homepage_elements = array();
        if (Avatar::getAvatar($this->auth_user['user_id'])->is_customized() && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['picture']) {
            $homepage_elements["picture"] = array("name" => _("Eigenes Bild"), "visibility" => $homepage_visibility["picture"] ? $homepage_visibility["picture"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Allgemeine Daten');
        }
        if ($my_data["motto"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['motto'])
            $homepage_elements["motto"] = array("name" => _("Motto"), "visibility" => $homepage_visibility["motto"] ? $homepage_visibility["motto"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($GLOBALS['ENABLE_SKYPE_INFO']) {
            if ($GLOBALS['user']->cfg->getValue('SKYPE_NAME') && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['skype_name']) {
                $homepage_elements["skype_name"] = array("name" => _("Skype Name"), "visibility" => $homepage_visibility["skype_name"] ? $homepage_visibility["skype_name"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
                if ($GLOBALS['user']->cfg->getValue('SKYPE_ONLINE_STATUS')) {
                    $homepage_elements["skype_online_status"] = array("name" => _("Skype Online Status"), "visibility" => $homepage_visibility["skype_online_status"] ? $homepage_visibility["skype_online_status"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
                }
            }
        }
        if ($my_data["privatnr"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['Private Daten_phone'])
            $homepage_elements["private_phone"] = array("name" => _("Private Telefonnummer"), "visibility" => $homepage_visibility["private_phone"] ? $homepage_visibility["private_phone"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["privatcell"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['private_cell'])
            $homepage_elements["private_cell"] = array("name" => _("Private Handynummer"), "visibility" => $homepage_visibility["private_cell"] ? $homepage_visibility["private_cell"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["privadr"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['privadr'])
            $homepage_elements["privadr"] = array("name" => _("Private Adresse"), "visibility" => $homepage_visibility["privadr"] ? $homepage_visibility["privadr"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["Home"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['homepage'])
            $homepage_elements["homepage"] = array("name" => _("Homepage-Adresse"), "visibility" => $homepage_visibility["homepage"] ? $homepage_visibility["homepage"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($news && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['news'])
            $homepage_elements["news"] = array("name" => _("Ankündigungen"), "visibility" => $homepage_visibility["news"] ? $homepage_visibility["news"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Allgemeine Daten');
        if ($GLOBALS["CALENDAR_ENABLE"] && $dates && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['dates'])
            $homepage_elements["termine"] = array("name" => _("Termine"), "visibility" => $homepage_visibility["termine"] ? $homepage_visibility["termine"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Allgemeine Daten');
        if (get_config('VOTE_ENABLE') && ($activeVotes || $stoppedVotes || $activeEvals) && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['votes'])
            $homepage_elements["votes"] = array("name" => _("Umfragen"), "visibility" => $homepage_visibility["votes"] ? $homepage_visibility["votes"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Allgemeine Daten');
        if ($my_data["guestbook"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['guestbook'])
            $homepage_elements["guestbook"] = array("name" => _("Gästebuch"), "visibility" => $homepage_visibility["guestbook"] ? $homepage_visibility["guestbook"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Allgemeine Daten');
        $data = DBManager::get()->query("SELECT Institute.* FROM user_inst LEFT JOIN Institute  USING (Institut_id) WHERE user_id = '$user_id' AND inst_perms = 'user'");
        if ($data->fetch() && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['studying']) {
            $homepage_elements["studying"] = array("name" => _("Wo ich studiere"), "visibility" => $homepage_visibility["studying"] ? $homepage_visibility["studying"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Studien-/Einrichtungsdaten');
        }
        if ($lit_list && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['literature'])
            $homepage_elements["literature"] = array("name" => _("Literaturlisten"), "visibility" => $homepage_visibility["literature"] ? $homepage_visibility["literature"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Allgemeine Daten');
        if ($my_data["lebenslauf"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['lebenslauf'])
            $homepage_elements["lebenslauf"] = array("name" => _("Lebenslauf"), "visibility" => $homepage_visibility["lebenslauf"] ? $homepage_visibility["lebenslauf"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($my_data["hobby"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['hobby'])
            $homepage_elements["hobby"] = array("name" => _("Hobbies"), "visibility" => $homepage_visibility["hobby"] ? $homepage_visibility["hobby"] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["publi"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['publi'])
            $homepage_elements["publi"] = array("name" => _("Publikationen"), "visibility" => $homepage_visibility["publi"] ? $homepage_visibility["publi"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($my_data["schwerp"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['schwerp'])
            $homepage_elements["schwerp"] = array("name" => _("Arbeitsschwerpunkte"), "visibility" => $homepage_visibility["schwerp"] ? $homepage_visibility["schwerp"] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($data_fields) {
            foreach ($data_fields as $key => $field) {
                if ($field->structure->accessAllowed($GLOBALS['perm']) && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']][$key]) {
                    $homepage_elements[$key] = array("name" => _($field->structure->data['name']), "visibility" => $homepage_visibility[$key] ? $homepage_visibility[$key] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Zusätzliche Datenfelder');
                }
            }
        }
        $categories = DBManager::get()->query("SELECT * FROM kategorien WHERE range_id = '".$this->auth_user['user_id']."' ORDER BY priority");
        foreach ($categories as $category) {
            $homepage_elements["kat_".$category["kategorie_id"]] = array("name" => $category["name"], "visibility" => $homepage_visibility["kat_".$category["kategorie_id"]] ? $homepage_visibility["kat_".$category["kategorie_id"]] : get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Eigene Kategorien');
        }
        if ($homepageplugins) {
            foreach ($homepageplugins as $plugin) {
                $homepage_elements['plugin_'.$plugin->getPluginId()] = array("name" => $plugin->getPluginName(), "visibility" => $homepage_visibility["plugin_".$plugin->getPluginId()] ? $homepage_visibility["plugin_".$plugin->getPluginId()] : get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Plugins');
            }
        }
        return $homepage_elements;
    }

} // end class definition
?>
