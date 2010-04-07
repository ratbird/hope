<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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

    if(StudipAuthAbstract::CheckField("auth_user_md5.Email", $auth_plugin)) {
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
        $msg.=  "error§" . sprintf(_("Die angegebene E-Mail-Adresse wird bereits von einem anderen User (%s %s) verwendet. Bitte geben Sie eine andere E-Mail-Adresse an."), htmlReady($db->f("Vorname")), htmlReady($db->f("Nachname"))) . "§";
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

        $msg.= "info§<b>" . sprintf(_('An Ihre neue E-Mail Adresse <b>%s</b> wurde ein Aktivierungslink geschickt, dem Sie folgen müssen bevor Sie sich das nächste mal einloggen können.'), $email). '</b>§';
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
    var $auth_user = array();        // assoziatives Array, enthält die Userdaten aus der Tabelle auth_user_md5
    var $user_info = array();        // assoziatives Array, enthält die Userdaten aus der Tabelle user_info
    var $user_inst = array();        // assoziatives Array, enthält die Userdaten aus der Tabelle user_inst
    var $user_studiengang = array(); // assoziatives Array, enthält die Userdaten aus der Tabelle user_studiengang
    var $user_userdomains = array(); // assoziatives Array, enthält die Userdaten aus der Tabelle user_userdomains
    var $check = "";    //Hilfsvariable für den Rechtecheck
    var $special_user = FALSE;  // Hilfsvariable für bes. Institutsfunktionen
    var $msg = ""; //enthält evtl Fehlermeldungen
    var $logout_user = FALSE; //Hilfsvariable, zeigt an, ob der User ausgeloggt werden muß
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

        $this->db->query("SELECT user_studiengang.*,studiengaenge.name FROM user_studiengang LEFT JOIN studiengaenge USING (studiengang_id) WHERE user_id = '".$this->auth_user["user_id"]."' ORDER BY name");
        while ($this->db->next_record()) {
            $this->user_studiengang[$this->db->f("studiengang_id")] = array("name" => $this->db->f("name"));
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

    function studiengang_edit($studiengang_delete,$new_studiengang) {
        if (is_array($studiengang_delete)) {
            for ($i=0; $i < count($studiengang_delete); $i++) {
                $this->db->query("DELETE FROM user_studiengang WHERE user_id='".$this->auth_user["user_id"]."' AND studiengang_id='$studiengang_delete[$i]'");
                if (!$this->db->affected_rows())
                    $this->msg = $this->msg."error§" . sprintf(_("Fehler beim L&ouml;schen in user_studiengang bei ID=%s"), $studiengang_delete[$i]) . "§";
            }
        }

        if ($new_studiengang) {
            $this->db->query("INSERT IGNORE INTO user_studiengang (user_id,studiengang_id) VALUES ('".$this->auth_user["user_id"]."','$new_studiengang')");
            if (!$this->db->affected_rows())
                $this->msg = $this->msg."error§" . sprintf(_("Fehler beim Einf&uuml;gen in user_studiengang bei ID=%s"), $new_studiengang) . "§";
        }

        if ( ($studiengang_delete || $new_studiengang) && !$this->msg) {
            $this->msg = "msg§" . _("Die Zuordnung zu Studiengängen wurde ge&auml;ndert.");
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg= _("Die Zuordnung zu Studiengängen wurde geändert!\n");
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
            $this->priv_msg= _("Die Zuordnung zu Nutzerdomänen wurde geändert!\n");
            restoreLanguage();
        }
    }



    function inst_edit($inst_delete,$new_inst) {
        if (is_array($inst_delete)) {
            for ($i=0; $i < count($inst_delete); $i++) {
                log_event('INST_USER_DEL', $inst_delete[$i], $this->auth_user["user_id"]);
                $this->db->query("DELETE FROM user_inst WHERE user_id='".$this->auth_user["user_id"]."' AND Institut_id='$inst_delete[$i]'");
                if (!$this->db->affected_rows())
                    $this->msg = $this->msg . "error§" . sprintf(_("Fehler beim L&ouml;schen in user_inst bei ID=%s"), $inst_delete[$i]) . "§";
            }
        }

        if ($new_inst) {
            log_event('INST_USER_ADD', $new_inst , $this->auth_user['user_id'], 'user');
         
            $this->db->query("INSERT IGNORE INTO user_inst (user_id,Institut_id,inst_perms) VALUES ('".$this->auth_user["user_id"]."','$new_inst','user')");
            if (!$this->db->affected_rows())
                $this->msg = $this->msg . "error§" . sprintf(_("Fehler beim Einf&uuml;gen in user_inst bei ID=%s"), $new_inst) . "§";
        }

        if ( ($inst_delete || $new_inst) && !$this->msg) {
            $this->msg = "msg§" . _("Die Zuordnung zu Einrichtungen wurde ge&auml;ndert.");
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg= _("Die Zuordnung zu Einrichtungen wurde geändert!\n");
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
        if (is_array($raum)) {
            list($inst_id, $detail) = each($raum);
                if ($default_inst == $inst_id) {
                    $this->db->query("UPDATE user_inst SET externdefault = 0 WHERE user_id = '".$this->auth_user['user_id']."'");
                }

                $query = "UPDATE user_inst SET raum='$detail', sprechzeiten='$sprech[$inst_id]', ";
                $query .= "Telefon='$tel[$inst_id]', Fax='$fax[$inst_id]', externdefault=";
                $query .= $default_inst == $inst_id ? '1' : '0';
                $query .= ", visible=" . (isset($visible[$inst_id]) ? '0' : '1');
                $query .= " WHERE Institut_id='$inst_id' AND user_id='" . $this->auth_user["user_id"] . "'";
                $this->db->query($query);

                if ($this->db->affected_rows()) {
                    $this->msg = $this->msg . "msg§" . sprintf(_("Ihre Daten an der Einrichtung %s wurden ge&auml;ndert"), htmlReady($name[$inst_id])) . "§";
                    setTempLanguage($this->auth_user["user_id"]);
                    $this->priv_msg = $this->priv_msg . sprintf(_("Ihre Daten an der Einrichtung %s wurden geändert.\n"), htmlReady($name[$inst_id]));
                    restoreLanguage();
                }

        }

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

        if (!StudipAuthAbstract::CheckField("user_info.privatnr", $this->auth_user['auth_plugin'])){
            $query .= "privatnr='$telefon',";
        }

        if (!StudipAuthAbstract::CheckField("user_info.privatcell", $this->auth_user['auth_plugin'])){
            $query .= "privatcell='$cell',";
        }

        if (!StudipAuthAbstract::CheckField("user_info.privadr", $this->auth_user['auth_plugin'])){
            $query .= "privadr='$anschrift',";
        }
        if (!StudipAuthAbstract::CheckField("user_info.Home", $this->auth_user['auth_plugin'])){
            $query .= "Home='$home',";
        }
        if (!StudipAuthAbstract::CheckField("user_info.motto", $this->auth_user['auth_plugin'])){
            $query .= "motto='$motto',";
        }
        if (!StudipAuthAbstract::CheckField("user_info.hobby", $this->auth_user['auth_plugin'])){
            $query .= "hobby='$hobby',";
        }

        $query = "UPDATE user_info SET " . $query . " chdate='".time()."' WHERE user_id='".$this->auth_user["user_id"]."'";
        DBManager::get()->query($query);
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
        if ($lebenslauf!=$this->db->f("lebenslauf") || $schwerp!=$this->db->f("schwerp") || $publi!=$this->db->f("publi") || $resultDataFields) {
            $this->db->query("UPDATE user_info SET lebenslauf='$lebenslauf', schwerp='$schwerp', publi='$publi', chdate='".time()."' WHERE user_id='".$this->auth_user["user_id"]."'");
            $this->msg = $this->msg . "msg§" . _("Daten im Lebenslauf u.a. wurden ge&auml;ndert") . "§";
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg = _("Daten im Lebenslauf u.a. wurden geändert.\n");
            restoreLanguage();
        }
        return $invalidEntries;
    }


    function edit_pers($password, $response, $new_username, $vorname, $nachname, $email, $geschlecht, $title_front, $title_front_chooser, $title_rear, $title_rear_chooser, $view) {
        global $UNI_NAME_CLEAN, $_language_path, $auth, $perm;
        global $ALLOW_CHANGE_USERNAME, $ALLOW_CHANGE_EMAIL, $ALLOW_CHANGE_NAME, $ALLOW_CHANGE_TITLE;

        //erstmal die "unwichtigen" Daten
        if($title_front == "")
            $title_front = $title_front_chooser;
        if($title_rear == "")
            $title_rear = $title_rear_chooser;
        $query = "";
        if (!StudipAuthAbstract::CheckField("user_info.geschlecht", $this->auth_user['auth_plugin'])){
            $query .= "geschlecht='$geschlecht',";
        }
        if ($ALLOW_CHANGE_TITLE && !StudipAuthAbstract::CheckField("user_info.title_front", $this->auth_user['auth_plugin'])){
            $query .= "title_front='$title_front',";
        }
        if ($ALLOW_CHANGE_TITLE && !StudipAuthAbstract::CheckField("user_info.title_rear", $this->auth_user['auth_plugin'])){
            $query .= "title_rear='$title_rear',";
        }
        if ($query != "") {
            $query = "UPDATE user_info SET " . $query . " chdate='".time()."' WHERE user_id='".$this->auth_user["user_id"]."'";
            $this->db->query($query);
            if ($this->db->affected_rows()) {
                $this->msg = $this->msg . "msg§" . _("Ihre pers&ouml;nlichen Daten wurden ge&auml;ndert.") . "§";
                setTempLanguage($this->auth_user["user_id"]);
                $this->priv_msg = _("Ihre persönlichen Daten wurden geändert.\n");
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

            if (!StudipAuthAbstract::CheckField("auth_user_md5.password", $this->auth_user['auth_plugin'])
              && (($response && $response!=md5("*****")) || $password!="*****")) {      //Passwort verändert ?

                // auf doppelte Vergabe wird weiter unten getestet.
                if (!isset($response) || $response=="") { // wir haben kein verschluesseltes Passwort
                    if (!$validator->ValidatePassword($password)) {
                        $this->msg=$this->msg . "error§" . _("Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.") . "§";
                        return false;
                    }
                    $newpass = md5($password);             // also können wir das unverschluesselte Passwort testen
                } else {
                    $newpass = $response;
                }

                $this->db->query("UPDATE auth_user_md5 SET password='$newpass' WHERE user_id='".$this->auth_user["user_id"]."'");
                $this->msg=$this->msg . "msg§" . _("Ihr Passwort wurde ge&auml;ndert!") . "§";
            }

            if (!StudipAuthAbstract::CheckField('auth_user_md5.Vorname', $this->auth_user['auth_plugin']) && $vorname != $this->auth_user['Vorname']) { //Vornamen verändert ?
                if ($ALLOW_CHANGE_NAME) {
                    if (!$validator->ValidateName($vorname)) {
                        $this->msg=$this->msg . "error§" . _("Der Vorname fehlt oder ist unsinnig!") . "§";
                        return false;
                    }   // Vorname nicht korrekt oder fehlend
                    $this->db->query("UPDATE auth_user_md5 SET Vorname='$vorname' WHERE user_id='".$this->auth_user["user_id"]."'");
                    $this->msg=$this->msg . "msg§" . _("Ihr Vorname wurde ge&auml;ndert!") . "§";
                } else $vorname = $this->auth_user['Vorname'];
            }

            if (!StudipAuthAbstract::CheckField('auth_user_md5.Nachname', $this->auth_user['auth_plugin']) && $nachname != $this->auth_user['Nachname']) { //Namen verändert ?
                if ($ALLOW_CHANGE_NAME) {
                    if (!$validator->ValidateName($nachname)) {
                        $this->msg=$this->msg . "error§" . _("Der Nachname fehlt oder ist unsinnig!") . "§";
                        return false;
                    }   // Nachname nicht korrekt oder fehlend
                    $this->db->query("UPDATE auth_user_md5 SET Nachname='$nachname' WHERE user_id='".$this->auth_user["user_id"]."'");
                    $this->msg=$this->msg . "msg§" . _("Ihr Nachname wurde ge&auml;ndert!") . "§";
                } else $nachname = $this->auth_user['Nachname'];
            }


            if (!StudipAuthAbstract::CheckField('auth_user_md5.username', $this->auth_user['auth_plugin']) && $this->auth_user['username'] != $new_username) {
                if ($ALLOW_CHANGE_USERNAME) {
                    if (!$validator->ValidateUsername($new_username)) {
                        $this->msg=$this->msg . "error§" . _("Der gewählte Username ist nicht lang genug!") . "§";
                        return false;
                    }
                    $check_uname = StudipAuthAbstract::CheckUsername($new_username);
                    if ($check_uname['found']) {
                        $this->msg .= "error§" . _("Der Username wird bereits von einem anderen User verwendet. Bitte wählen sie einen anderen Usernamen!") . "§";
                        return false;
                    } else {
                        //$this->msg .= "info§" . $check_uname['error'] ."§";
                    }
                    $this->db->query("UPDATE auth_user_md5 SET username='$new_username' WHERE user_id='".$this->auth_user["user_id"]."'");
                    $this->msg=$this->msg . "msg§" . _("Ihr Username wurde ge&auml;ndert!") . "§";
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


    function select_studiengang() {  //Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Studiengängen

        echo '<select name="new_studiengang" style="width:30ex;"><option selected></option>'."\n";
        $this->db->query("SELECT a.studiengang_id,a.name FROM studiengaenge AS a LEFT JOIN user_studiengang AS b ON (b.user_id='".$this->auth_user["user_id"]."' AND a.studiengang_id=b.studiengang_id) WHERE b.studiengang_id IS NULL ORDER BY a.name");

        while ($this->db->next_record()) {
            echo "<option value=\"".$this->db->f("studiengang_id")."\">".htmlReady(my_substr($this->db->f("name"),0,50))."</option>\n";
        }
        echo "</select>\n";

        return;
    }


    function select_userdomain() {  //Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Nutzerdomänen

        echo '<select name="new_userdomain" style="width:30ex;"><option selected></option>'."\n";
        $user_domains = UserDomain::getUserDomainsForUser($this->auth_user['user_id']);
        $domains = UserDomain::getUserDomains();

        foreach (array_diff($domains, $user_domains) as $domain) {
            echo "<option value=\"".$domain->getID()."\">".htmlReady(my_substr($domain->getName(),0,50))."</option>\n";
        }
        echo "</select>\n";
    }


    function select_inst() {  //Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Instituten

        echo '<select name="new_inst" style="width:30ex;"><option selected></option>'."\n";
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


} // end class definition
?>
