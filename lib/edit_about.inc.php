<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
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

    $query = "SELECT email, username, auth_plugin
              FROM auth_user_md5
              WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($uid));
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    $email_cur   = $row['email'];
    $username    = $row['username'];
    $auth_plugin = $row['auth_plugin'];

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

    $query = "SELECT Vorname, Nachname
              FROM auth_user_md5
              WHERE Email = ? AND user_id != ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($email, $uid));
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $msg.=  "error§" . sprintf(_("Die angegebene E-Mail-Adresse wird bereits von einem anderen Benutzer (%s %s) verwendet. Bitte geben Sie eine andere E-Mail-Adresse an."), htmlReady($row['Vorname']), htmlReady($row['Nachname'])) . "§";
        return array(False, $msg);
    }

    $query = "UPDATE auth_user_md5 SET Email = ? WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($email, $uid));

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

        $query = "UPDATE auth_user_md5 SET validation_key = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($key, $uid));

        $msg.= "info§<b>" . sprintf(_('An Ihre neue E-Mail-Adresse <b>%s</b> wurde ein Aktivierungslink geschickt, dem Sie folgen müssen bevor Sie sich das nächste mal einloggen können.'), $email). '</b>§';
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
class about extends messaging
{

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
        global $perm;

        $this->get_auth_user($username);
        $this->dataFieldEntries = DataFieldEntry::getDataFieldEntries($this->auth_user["user_id"], 'user');
        $this->check = $perm->get_profile_perm($this->auth_user['user_id']);
        $this->msg = $msg; //Meldungen restaurieren
    }


    function get_auth_user($username)
    {
        //ein paar userdaten brauchen wir schon mal
        $query = "SELECT * FROM auth_user_md5 WHERE username = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($username));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if ($temp) {
            foreach ($temp as $key => $value) {
                $this->auth_user[$key] = $value;
            }
        }
        if (!$this->auth_user['auth_plugin']){
            $this->auth_user['auth_plugin'] = "standard";
        }
    }

    // füllt die arrays  mit Daten
    function get_user_details()
    {
        $query = "SELECT * FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->auth_user['user_id']));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if ($temp) {
            foreach ($temp as $key => $value) {
                $this->user_info[$key] = $value;
            }
            if (!$this->user_info['Home']) {
                $this->user_info['Home'] = $this->default_url;
            }
        }

       $query = "SELECT studiengang_id, abschluss_id, semester,
                        studiengaenge.name AS fname, abschluss.name AS aname
                 FROM user_studiengang
                 LEFT JOIN studiengaenge USING (studiengang_id)
                 LEFT JOIN abschluss USING (abschluss_id)
                 WHERE user_id = ?
                 ORDER BY fname, aname";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->auth_user['user_id']));
        $this->user_fach_abschluss = $statement->fetchAll(PDO::FETCH_ASSOC);

        $this->user_userdomains = UserDomain::getUserDomainsForUser($this->auth_user['user_id']);

        $query = "SELECT Institut_id, inst_perms, sprechzeiten, raum,
                         user_inst.Telefon, user_inst.Fax, Institute.Name,
                         externdefault, priority, visible
                  FROM user_inst
                  LEFT JOIN Institute USING (Institut_id)
                  WHERE user_id = ?
                  ORDER BY priority ASC, Institut_id ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->auth_user['user_id']));
        $this->user_inst = $statement->fetchGrouped(PDO::FETCH_ASSOC);

        // Let's see whether the user is not just a user in any of his
        // institutes
        $this->special_user = array_reduce($this->user_inst, function ($result, $item) { 
            return $result || ($item['inst_perms'] != 'user');
        }, false);
    }

    /**
     * add, edit, delete courses of study
     * @param array $fach_abschluss_delete
     * @param string $new_studiengang
     * @param string $new_abschluss
     * @param int $fachsem
     * @param array $change_fachsem
     */
function fach_abschluss_edit($fach_abschluss_delete, $new_studiengang, $new_abschluss, $fachsem, $change_fachsem)
{

        $any_change = false;
        if (!empty($fach_abschluss_delete)) {
            $any_change = true;

            $query = "DELETE FROM user_studiengang
                      WHERE user_id = ? AND studiengang_id = ? AND abschluss_id IN (?)";
            $statement = DBManager::get()->prepare($query);

            foreach ($fach_abschluss_delete as $studiengang_id => $abschluesse) {
                $statement->execute(array(
                    $this->auth_user['user_id'],
                    $studiengang_id,
                    $abschluesse
                ));
                if ($statement->rowCount() > 0) {
                    $delete = true;
                }
            }
        }

        if (!$any_change) {
            $query = "UPDATE IGNORE user_studiengang
                      SET semester = ?
                      WHERE user_id = ? AND studiengang_id = ? AND abschluss_id = ?";
            $statement = DBManager::get()->prepare($query);
            
            foreach ($change_fachsem as $studiengang_id => $abschluesse) {
                foreach ($abschluesse as $abschluss_id => $semester) {
                    $statement->execute(array(
                        $semester,
                        $this->auth_user['user_id'],
                        $studiengang_id,
                        $abschluss_id
                    ));
                    if ($statement->rowCount() > 0) {
                        $any_change = true;
                    }
                }
            }

            if ($new_studiengang && $new_studiengang != 'none') {
                $query = "INSERT IGNORE INTO user_studiengang
                            (user_id, studiengang_id, abschluss_id, semester)
                          VALUES (?, ?, ?, ?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $this->auth_user['user_id'],
                    $new_studiengang,
                    $new_abschluss,
                    $fachsem
                ));
                if ($statement->rowCount() > 0) {
                    $any_change = true;
                }
            }
        }

        if ($any_change && !$this->msg) {
            $this->msg = 'msg§' . _('Die Zuordnung zu Studiengängen wurde ge&auml;ndert.');
            setTempLanguage($this->auth_user['user_id']);
            $this->priv_msg .= _('Die Zuordnung zu Studiengängen wurde geändert!\n');
            restoreLanguage();
        }
    }
 
    function userdomain_edit ($userdomain_delete, $new_userdomain) {
        if (is_array($userdomain_delete)) {
            for ($i=0; $i < count($userdomain_delete); $i++) {
                $domain = new UserDomain($userdomain_delete[$i]);
                $domain->removeUser($this->auth_user['user_id']);
            }
        }
        if ($new_userdomain && $new_userdomain != 'none' ) {
            $domain = new UserDomain($new_userdomain);
            $domain->addUser($this->auth_user['user_id']);
        }

        if (($userdomain_delete || ($new_userdomain && $new_userdomain != 'none')) && !$this->msg) {
            $this->msg = "msg§" . _("Die Zuordnung zu Nutzerdomänen wurde ge&auml;ndert.");
            setTempLanguage($this->auth_user["user_id"]);
            $this->priv_msg .= _("Die Zuordnung zu Nutzerdomänen wurde geändert!\n");
            restoreLanguage();
        }
    }

    function inst_edit($inst_delete, $new_inst)
    {
        if (count($inst_delete) > 0) {
            $query = "DELETE FROM user_inst WHERE user_id = ? AND Institut_id = ?";
            $statement = DBManager::get()->prepare($query);
            
            foreach ($inst_delete as $institute_id) {
                $statement->execute(array(
                    $this->auth_user['user_id'],
                    $institute_id
                ));
                if ($statement->rowCount() > 0) {
                    $delete = true;
                    log_event('INST_USER_DEL', $institute_id, $this->auth_user['user_id']);
                }
            }
        }
        
        if ($new_inst) {
            $query = "INSERT IGNORE INTO user_inst
                        (user_id, Institut_id, inst_perms)
                      VALUES (?, ?, 'user')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->auth_user['user_id'],
                $new_inst
            ));
            if ($statement->rowCount() > 0) {
                log_event('INST_USER_ADD', $new_inst , $this->auth_user['user_id'], 'user');
                $new = true;
            }
        }

        if ($delete || $new) {
            $this->msg = 'msg§' . _('Die Zuordnung zu Einrichtungen wurde ge&auml;ndert.');
            setTempLanguage($this->auth_user['user_id']);
            $this->priv_msg .= _('Die Zuordnung zu Einrichtungen wurde geändert!\n');
            restoreLanguage();
        }
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

    function special_edit ($raum, $sprech, $tel, $fax, $name, $default_inst, $visible, $datafields, $group_id, $role_id, $status)
    {
        if (!LockRules::Check($this->auth_user["user_id"], 'institute_data')) {
            if (!empty($raum)) {
                list($inst_id, $detail) = each($raum);
                $query = "UPDATE user_inst
                          SET raum = ?, sprechzeiten = ?, Telefon = ?, Fax = ?
                          WHERE Institut_id = ? AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $detail,
                    $sprech[$inst_id],
                    $tel[$inst_id],
                    $fax[$inst_id],
                    $inst_id,
                    $this->auth_user['user_id']
                ));
                if ($statement->rowCount() > 0) {
                    $this->msg .= 'msg§' . sprintf(_('Ihre Daten an der Einrichtung %s wurden ge&auml;ndert'), htmlReady($name[$inst_id])) . '§';
                    setTempLanguage($this->auth_user['user_id']);
                    $this->priv_msg .= sprintf(_('Ihre Daten an der Einrichtung %s wurden geändert.\n'), htmlReady($name[$inst_id]));
                    restoreLanguage();
                }
            }
        }

        $inst_id = $status['inst_id'];
        if ($default_inst == $inst_id) {
            $query = "UPDATE user_inst SET externdefault = 0 WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->auth_user['user_id']
            ));
        }
        $query = "UPDATE user_inst
                  SET externdefault = ?, visible = ?
                  WHERE Institut_id = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $default_inst == $inst_id ? 1 : 0,
            empty($visible[$inst_id]) ? 1 : 0,
            $inst_id,
            $this->auth_user['user_id']
        ));

        if ($status['status'] && $status['inst_id']) {
            $query = "SELECT inst_perms FROM user_inst WHERE user_id = ? AND Institut_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->auth_user['user_id'],
                $status['inst_id']
            ));
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($row['inst_perms'] != $status['status'] && in_array($status['status'], $this->allowedInstitutePerms())) {
                    $this->msg .= 'msg§'. _('Der Status wurde geändert!') .'§';

                    log_event("INST_USER_STATUS", $status['inst_id'], $this->auth_user['user_id'], $GLOBALS['user']->id .' -> '. $status['status']);

                    $query = "UPDATE user_inst SET inst_perms = ? WHERE user_id = ? AND Institut_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $stmt->execute(array(
                        $status['status'],
                        $this->auth_user['user_id'],
                        $status['inst_id']
                    ));
                }
            }
        }
        // process user role datafields
        $sec_range_id = $inst_id ?: $role_id;
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


    function edit_private($telefon, $cell, $anschrift, $home, $motto, $hobby)
    {
        if ($home == $this->default_url) {
            $home = '';
        }

        // Prepare check function for better readability
        $auth_user = $this->auth_user['auth_plugin'];
        $check = function ($column, $complete = true) use ($auth_user) {
            return !StudipAuthAbstract::CheckField('user_info.' . $column, $auth_user['auth_plugin'])
                && (!$complete || !LockRules::check($auth_user['user_id'], strtolower($column)));
        };

        $columns = array();
        if ($check('privatnr')) {
            $columns['privatnr'] = $telefon;
        }

        if ($check('privatcell')) {
            $columns['privatcell'] = $cell;
        }

        if ($check('privadr')) {
            $columns['privadr'] = $anschrift;
        }
        
        if ($check('Home')) {
            $columns['Home'] = $home;
        }
        if ($check('motto', false)) {
            $columns['motto'] = $motto;
        }

        if ($check('hobby')) {
            $columns['hobby'] = $hobby;
        }

        if (count($columns) > 0) {
            $cols = $parameters = array();
            foreach ($columns as $key => $value) {
                $cols[] = sprintf('%s = :%s', $key, $key);
                $parameters[':' . $key] = $value;
            }
            $cols = implode(', ', $cols);
            
            $query = "UPDATE user_info
                      SET {$cols}, chdate = UNIX_TIMESTAMP()
                      WHERE user_id = :user_id";
            $parameters[':user_id'] = $this->auth_user['user_id'];

            $statement = DBManager::get()->prepare($query);
            $statement->execute($parameters);

            $this->priv_msg .= _('Private Daten wurden geändert.'. "\n");
        }
    }

    function edit_leben($lebenslauf,$schwerp,$publi,$view, $datafields) {
        //Update additional data-fields
        $invalidEntries = array();
        foreach ($this->dataFieldEntries as $id => $entry) {
            if (isset($datafields[$id])){
                $entry->setValueFromSubmit($datafields[$id]);
                if ($entry->isValid()) {
                    $resultDataFields |= $entry->store();
                } else {
                    $invalidEntries[$id] = $entry;
                }
            }
        }

        //check ob die blobs verändert wurden...
        $query = "SELECT lebenslauf, schwerp, publi
                  FROM user_info
                  WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id']
        ));
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $change = false;
        foreach(words('lebenslauf schwerp publi') as $param) {
            if (LockRules::check($this->auth_user['user_id'], $param)) {
                $$param = $row[$param];
            }
            if ($$param != $row[$param]) {
                $change = true;
            }
        }
        if ($change || $resultDataFields) {
            $query = "UPDATE user_info
                      SET lebenslauf = ?, schwerp = ?, publi = ?, chdate = UNIX_TIMESTAMP()
                      WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $lebenslauf,
                $schwerp,
                $publi,
                $this->auth_user['user_id']
            ));

            $this->msg .= 'msg§' . _('Daten im Lebenslauf u.a. wurden ge&auml;ndert') . '§';
            setTempLanguage($this->auth_user['user_id']);
            $this->priv_msg .= _('Daten im Lebenslauf u.a. wurden geändert.\n');
            restoreLanguage();
        }
        return $invalidEntries;
    }


    function edit_pers($password, $new_username, $vorname, $nachname, $email,
                       $geschlecht, $title_front, $title_front_chooser,
                       $title_rear, $title_rear_chooser)
    {
        global $UNI_NAME_CLEAN, $_language_path, $auth, $perm;
        global $ALLOW_CHANGE_USERNAME, $ALLOW_CHANGE_EMAIL, $ALLOW_CHANGE_NAME, $ALLOW_CHANGE_TITLE;

        //erstmal die "unwichtigen" Daten
        if ($title_front == '') {
            $title_front = $title_front_chooser;
        }
        if ($title_rear == '') {
            $title_rear = $title_rear_chooser;
        }

        $columns = array();
        if (!StudipAuthAbstract::CheckField('user_info.geschlecht', $this->auth_user['auth_plugin'])
            && !LockRules::check($this->auth_user['user_id'], 'gender')) 
        {
            $columns['geschlecht'] = $geschlecht;
        }
        if ($ALLOW_CHANGE_TITLE
            && !StudipAuthAbstract::CheckField("user_info.title_front", $this->auth_user['auth_plugin'])
            && !LockRules::check($this->auth_user['user_id'], 'title'))
        {
            $columns['title_front'] = $title_front;
        }
        if ($ALLOW_CHANGE_TITLE
            && !StudipAuthAbstract::CheckField("user_info.title_rear", $this->auth_user['auth_plugin'])
            && !LockRules::check($this->auth_user['user_id'], 'title'))
        {
            $columns['title_rear'] = $title_rear;
        }

        if (count($columns) > 0) {
            $cols = $parameters = array();
            foreach ($columns as $key => $value) {
                $cols[] = sprintf('%s = :%s', $key, $key);
                $parameters[':' . $key] = $value;
            }
            $cols = implode(', ', $cols);

            $query = "UPDATE user_info
                      SET {$cols}, chdate = UNIX_TIMESTAMP()
                      WHERE user_id = :user_id";
            $parameters[':user_id'] = $this->auth_user['user_id'];

            $statement = DBManager::get()->prepare($query);
            $statement->execute($parameters);
            if ($statement->rowCount() > 0) {
                $this->msg .= 'msg§' . _('Ihre pers&ouml;nlichen Daten wurden ge&auml;ndert.') . '§';
                setTempLanguage($this->auth_user['user_id']);
                $this->priv_msg .= _('Ihre persönlichen Daten wurden geändert.\n');
                restoreLanguage();
            }
        }

        $new_username = trim($new_username);
        $vorname = trim($vorname);
        $nachname = trim($nachname);
        $email = trim($email);

        //nur nötig wenn der user selbst seine daten ändert
        if ($this->check == 'user') {
            //erstmal die Syntax checken $validator wird in der local.inc.php benutzt, sollte also funzen
            $validator = new email_validation_class; ## Klasse zum Ueberpruefen der Eingaben
            $validator->timeout=10;

            // Passwort verändert ?
            if (!StudipAuthAbstract::CheckField('auth_user_md5.password', $this->auth_user['auth_plugin'])
                && $password != '*****' 
                && !LockRules::check($this->auth_user['user_id'], 'password'))
            {      
                // auf doppelte Vergabe wird weiter unten getestet.
                if (!$validator->ValidatePassword($password)) {
                    $this->msg .= 'error§' . _('Das Passwort ist zu kurz - es sollte mindestens 4 Zeichen lang sein.') . '§';
                    return false;
                }

                $query = "UPDATE auth_user_md5 SET password = MD5(?) WHERE user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $password,
                    $this->auth_user['user_id']
                ));
                $this->msg .= 'msg§' . _('Ihr Passwort wurde ge&auml;ndert!') . '§';
            }

            //Vornamen verändert ?
            if ($ALLOW_CHANGE_NAME
                && !StudipAuthAbstract::CheckField('auth_user_md5.Vorname', $this->auth_user['auth_plugin'])
                && $vorname != $this->auth_user['Vorname']
                && !LockRules::check($this->auth_user['user_id'], 'name'))
            {
                // Vorname nicht korrekt oder fehlend
                if (!$validator->ValidateName($vorname)) {
                    $this->msg .= 'error§' . _('Der Vorname fehlt oder ist unsinnig!') . '§';
                    return false;
                }

                $query = "UPDATE auth_user_md5 SET Vorname = ? WHERE user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $vorname,
                    $this->auth_user['user_id']
                ));
                $this->msg .= 'msg§' . _('Ihr Vorname wurde ge&auml;ndert!') . '§';
            }

            //Namen verändert ?
            if ($ALLOW_CHANGE_NAME
                && !StudipAuthAbstract::CheckField('auth_user_md5.Nachname', $this->auth_user['auth_plugin'])
                && $nachname != $this->auth_user['Nachname']
                && !LockRules::check($this->auth_user['user_id'], 'name'))
            {
                // Nachname nicht korrekt oder fehlend
                if (!$validator->ValidateName($nachname)) {
                    $this->msg .= 'error§' . _('Der Nachname fehlt oder ist unsinnig!') . '§';
                    return false;
                }

                $query = "UPDATE auth_user_md5 SET Nachname = ? WHERE user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $nachname,
                    $this->auth_user['user_id']
                ));
                $this->msg .= 'msg§' . _('Ihr Nachname wurde ge&auml;ndert!') . '§';
            }

            if ($ALLOW_CHANGE_USERNAME
                && !StudipAuthAbstract::CheckField('auth_user_md5.username', $this->auth_user['auth_plugin'])
                && $this->auth_user['username'] != $new_username
                && !LockRules::check($this->auth_user['user_id'], 'username'))
            {
                if (!$validator->ValidateUsername($new_username)) {
                    $this->msg .= 'error§' . _('Der gewählte Benutzername ist nicht lang genug!') . '§';
                    return false;
                }
                $check_uname = StudipAuthAbstract::CheckUsername($new_username);
                if ($check_uname['found']) {
                    $this->msg .= 'error§' . _('Der Benutzername wird bereits von einem anderen Benutzer verwendet. Bitte wählen Sie einen anderen Usernamen!') . '§';
                    return false;
                }

                $query = "UPDATE auth_user_md5 SET username = ? WHERE user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $new_username,
                    $this->auth_user['user_id']
                ));

                $this->msg .= 'msg§' . _('Ihr Benutzername wurde ge&auml;ndert!') . '§';
                $this->logout_user = TRUE;
            }
        }
    }

    function edit_email($email)
    {
        $return = edit_email($this->auth_user['user_id'], $email);
        $this->msg .= $return[1];
        return $return[0];
    }

    /**
     * Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Studiengängen
     */
    public function select_studiengang()
    {
        $query = "SELECT studiengang_id, name FROM studiengaenge ORDER BY name";
        $statement = DBManager::get()->query($query);
        $studiengaenge = $statement->fetchGrouped(PDO::FETCH_COLUMN);

        echo '<select name="new_studiengang">'."\n";
        echo '<option selected value="none">' . _('-- Bitte Fach auswählen --') . '</option>'."\n";
        foreach ($studiengaenge as $id => $name) {
            printf('<option value="%s">%s</option>' . "\n", $id, htmlReady(my_substr($name, 0, 50)));
        }
        echo "</select>\n";
    }

    /**
     * Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Abschluesse
     */
   public function select_abschluss()
   {
       $query = "SELECT abschluss_id, name FROM abschluss ORDER BY name";
       $statement = DBManager::get()->query($query);
       $abschluesse = $statement->fetchGrouped(PDO::FETCH_COLUMN);
       
        echo '<select name="new_abschluss">'."\n";
        echo '<option selected value="none">'. _('-- Bitte Abschluss auswählen --') . '</option>'."\n";
        foreach ($abschluesse as $id => $name) {
            printf('<option value="%s">%s</option>' . "\n", $id, htmlReady(my_substr($name, 0, 50)));
        }
        echo "</select>\n";
    }

    //Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Nutzerdomänen
    function select_userdomain()
    {
        $user_domains = UserDomain::getUserDomainsForUser($this->auth_user['user_id']);
        $all_domains  = UserDomain::getUserDomains();
        $domains      = array_diff($all_domains, $user_domains);

        echo '<select name="new_userdomain">'."\n";
        echo '<option selected value="none">' . _('-- Bitte Nutzerdomäne auswählen --') . '</option>'."\n";
        foreach ($domains as $domain) {
            printf('<option value="%s">%s</option>' . "\n", $domain->getID(), htmlReady(my_substr($domain->getName(), 0, 50)));
        }
        echo "</select>\n";
    }

    /**
     * Hilfsfunktion, erzeugt eine Auswahlbox mit noch auswählbaren Instituten
     */
    function select_inst()
    {
        $query = "SELECT a.Institut_id, a.Name
                  FROM Institute AS a
                  LEFT JOIN user_inst AS b ON (b.user_id = ? AND a.Institut_id = b.Institut_id)
                  WHERE b.Institut_id IS NULL
                  ORDER BY a.Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id']
        ));
        $institutes = $statement->fetchGrouped(PDO::FETCH_COLUMN);

        echo '<select name="new_inst" id="select_new_inst">' . "\n";
        echo '<option selected value=""> ' . _("-- Bitte Einrichtung auswählen --") . ' </option>'."\n";
        foreach ($institutes as $id => $name) {
            printf('<option value="%s">%s</option>' . "\n", $id, htmlReady(my_substr($name, 0, 50)));
        }
        echo "</select>\n";
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
            $query = "SELECT Institut_id
                      FROM user_inst
                      WHERE user_id = ? AND inst_perms != 'user'
                      ORDER BY priority ASC";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->auth_user['user_id']
            ));

            $i = 1;
            while ($institute_id = $statement->fetchColumn()) {
                $to_order[$i] = $institut_id;
                if ($to_order[$i] == $inst_id) {
                    $pos = $i;
                }
                $i += 1;
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
            
            $query = "UPDATE user_inst
                      SET priority = ?
                      WHERE user_id = ? AND Institut_id = ?";
            $statement = DBManager::get()->prepare($query);

            for ($i -= 1; $i > 0; $i -= 1) {
                $statement->execute(array(
                    $i,
                    $this->auth_user['user_id'],
                    $to_order[$i]
                ));
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
    function change_global_visibility($global, $online, $chat, $search, $email, $foaf_show_identity)
    {
        // Globally visible or unknown -> set local visibilities accordingly.
        if ($global != 'no') {
            $online = $online ? 1 : 0;
            $chat   = $chat ? 1 : 0;
            $search = $search ? 1 : 0;
            $email  = $email ? 1 : 0;
            $foaf_show_identity = $foaf_show_identity ? 1 : 0;
        // Globally invisible -> set all local fields to invisible.
        } else {
            $online = 0;
            $chat   = 0;
            $search = 0;
            $email  = get_config('DOZENT_ALLOW_HIDE_EMAIL') ? 0 : 1;
            $success1 = $this->change_all_homepage_visibility(VISIBILITY_ME);
            $foaf_show_identity = 0;
        }
        $user_cfg = UserConfig::get($this->auth_user["user_id"]);
        $user_cfg->store("FOAF_SHOW_IDENTITY", $foaf_show_identity);

        $query = "UPDATE auth_user_md5 SET visible = ? WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $global,
            $this->auth_user['user_id']
        ));

        $query = "INSERT INTO user_visibility
                    (user_id, online, chat, search, email, mkdate)
                  VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE online = VALUES(online), chat = VALUES(chat),
                           search = VALUES(search), email = VALUES(email)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id'],
            $online,
            $chat,
            $search,
            $email
        ));
        return $statement->rowCount() > 0;
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
    function set_default_homepage_visibility($visibility)
    {
        $query = "INSERT INTO user_visibility
                    (user_id, default_homepage_visibility, mkdate)
                  VALUES (?, ?, UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE default_homepage_visibility = VALUES(default_homepage_visibility)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id'],
            (int)$visibility
        ));
        return $statement->rowCount();
    }

    /**
     * Saves user specified visibility settings for homepage elements.
     *
     * @param array $data all homepage elements with their visiblities in
     * the form $name => $visibility
     * @return int Number of affected database rows (hopefully 1).
     */
    function change_homepage_visibility($data)
    {
        $query = "INSERT INTO user_visibility
                    (user_id, homepage, mkdate)
                  VALUES (?, ?, UNIX_TIMESTAMP())
                  ON DUPLICATE KEY
                    UPDATE homepage = VALUES(homepage)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id'],
            json_encode($data)
        ));
        return $statement->rowCount();
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
    function get_homepage_elements()
    {
        global $NOT_HIDEABLE_FIELDS;

        $query = "SELECT user_info.*, auth_user_md5.*
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id']
        ));
        $my_data = $statement->fetch(PDO::FETCH_ASSOC);

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
        $guestbook = new Guestbook($this->auth_user['user_id'], 1);
        $guestbook = $guestbook->checkGuestbook();
        // Homepage plugins
        //$homepageplugins = PluginEngine::getPlugins('HomepagePlugin');
        // Deactivate plugin visibility settings because they aren't working now.
        $homepageplugins = array();

        $user_domains = count(UserDomain::getUserDomains());

        // Now join all available elements with visibility settings.
        $homepage_elements = array();
        if (Avatar::getAvatar($this->auth_user['user_id'])->is_customized() && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['picture']) {
            $homepage_elements["picture"] = array("name" => _("Eigenes Bild"), "visibility" => $homepage_visibility["picture"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Allgemeine Daten');
        }
        if ($my_data["motto"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['motto'])
            $homepage_elements["motto"] = array("name" => _("Motto"), "visibility" => $homepage_visibility["motto"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($GLOBALS['ENABLE_SKYPE_INFO']) {
            if ($GLOBALS['user']->cfg->getValue('SKYPE_NAME') && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['skype_name']) {
                $homepage_elements["skype_name"] = array("name" => _("Skype Name"), "visibility" => $homepage_visibility["skype_name"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
                if ($GLOBALS['user']->cfg->getValue('SKYPE_ONLINE_STATUS')) {
                    $homepage_elements["skype_online_status"] = array("name" => _("Skype Online Status"), "visibility" => $homepage_visibility["skype_online_status"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
                }
            }
        }
        if ($my_data["privatnr"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['Private Daten_phone'])
            $homepage_elements["private_phone"] = array("name" => _("Private Telefonnummer"), "visibility" => $homepage_visibility["private_phone"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["privatcell"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['private_cell'])
            $homepage_elements["private_cell"] = array("name" => _("Private Handynummer"), "visibility" => $homepage_visibility["private_cell"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["privadr"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['privadr'])
            $homepage_elements["privadr"] = array("name" => _("Private Adresse"), "visibility" => $homepage_visibility["privadr"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["Home"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['homepage'])
            $homepage_elements["homepage"] = array("name" => _("Homepage-Adresse"), "visibility" => $homepage_visibility["homepage"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($news && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['news'])
            $homepage_elements["news"] = array("name" => _("Ankündigungen"), "visibility" => $homepage_visibility["news"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Allgemeine Daten');
        if ($GLOBALS["CALENDAR_ENABLE"] && $dates && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['dates'])
            $homepage_elements["termine"] = array("name" => _("Termine"), "visibility" => $homepage_visibility["termine"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Allgemeine Daten');
        if (get_config('VOTE_ENABLE') && ($activeVotes || $stoppedVotes || $activeEvals) && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['votes'])
            $homepage_elements["votes"] = array("name" => _("Umfragen"), "visibility" => $homepage_visibility["votes"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Allgemeine Daten');
        if ($my_data["guestbook"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['guestbook'])
            $homepage_elements["guestbook"] = array("name" => _("Gästebuch"), "visibility" => $homepage_visibility["guestbook"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Allgemeine Daten');

        $query = "SELECT 1
                  FROM user_inst
                  LEFT JOIN Institute USING (Institut_id)
                  WHERE user_id = ? AND inst_perms = 'user'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id']
        ));
        if ($statement->fetchColumn() && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['studying']) {
            $homepage_elements["studying"] = array("name" => _("Wo ich studiere"), "visibility" => $homepage_visibility["studying"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Studien-/Einrichtungsdaten');
        }
        if ($lit_list && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['literature'])
            $homepage_elements["literature"] = array("name" => _("Literaturlisten"), "visibility" => $homepage_visibility["literature"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Allgemeine Daten');
        if ($my_data["lebenslauf"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['lebenslauf'])
            $homepage_elements["lebenslauf"] = array("name" => _("Lebenslauf"), "visibility" => $homepage_visibility["lebenslauf"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($my_data["hobby"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['hobby'])
            $homepage_elements["hobby"] = array("name" => _("Hobbies"), "visibility" => $homepage_visibility["hobby"] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Private Daten');
        if ($my_data["publi"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['publi'])
            $homepage_elements["publi"] = array("name" => _("Publikationen"), "visibility" => $homepage_visibility["publi"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($my_data["schwerp"] && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']]['schwerp'])
            $homepage_elements["schwerp"] = array("name" => _("Arbeitsschwerpunkte"), "visibility" => $homepage_visibility["schwerp"] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Private Daten');
        if ($data_fields) {
            foreach ($data_fields as $key => $field) {
                if ($field->structure->accessAllowed($GLOBALS['perm']) && !$NOT_HIDEABLE_FIELDS[$this->auth_user['perms']][$key]) {
                    $homepage_elements[$key] = array("name" => _($field->structure->data['name']), "visibility" => $homepage_visibility[$key] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Zusätzliche Datenfelder');
                }
            }
        }

        $query = "SELECT kategorie_id, name
                  FROM kategorien
                  WHERE range_id = ?
                  ORDER BY priority";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $this->auth_user['user_id']
        ));
        while ($category = $statement->fetch(PDO::FETCH_ASSOC)) {
            $homepage_elements["kat_".$category["kategorie_id"]] = array("name" => $category["name"], "visibility" => $homepage_visibility["kat_".$category["kategorie_id"]] ?: get_default_homepage_visibility($this->auth_user['user_id']), "extern" => true, 'category' => 'Eigene Kategorien');
        }

        if ($homepageplugins) {
            foreach ($homepageplugins as $plugin) {
                $homepage_elements['plugin_'.$plugin->getPluginId()] = array("name" => $plugin->getPluginName(), "visibility" => $homepage_visibility["plugin_".$plugin->getPluginId()] ?: get_default_homepage_visibility($this->auth_user['user_id']), 'category' => 'Plugins');
            }
        }
        return $homepage_elements;
    }

} // end class definition

