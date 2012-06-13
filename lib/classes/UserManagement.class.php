<?php
# Lifter002: DONE - not applicable
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: DONE - not applicable
/**
 * UserManagement.class.php
 *
 * Management for the Stud.IP global users
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * @author      Stefan Suchi <suchi@data-quest>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @copyright   2009 Stud.IP
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL Licence 2
 * @category    Stud.IP
 */

// Imports
require_once 'lib/functions.php';
require_once 'lib/language.inc.php';
require_once 'config.inc.php';      // We need the uni name for emails
require_once 'lib/admission.inc.php';   // remove user from waiting lists
require_once 'lib/datei.inc.php';   // remove documents of user
require_once 'lib/statusgruppe.inc.php';    // remove user from statusgroups
require_once 'lib/dates.inc.php';   // remove appointments of user
require_once 'lib/messaging.inc.php';   // remove messages send or recieved by user
require_once 'lib/contact.inc.php'; // remove user from adressbooks
require_once 'lib/classes/DataFieldEntry.class.php';    // remove extra data of user
require_once 'lib/classes/auth_plugins/StudipAuthAbstract.class.php';
require_once 'lib/classes/StudipNews.class.php';
require_once 'lib/object.inc.php';
require_once 'lib/log_events.inc.php';  // Event logging
require_once 'lib/classes/Avatar.class.php'; // remove Avatarture
require_once 'app/models/studygroup.php';
require_once 'lib/classes/AutoInsert.class.php'; // automatic Insert for user in seminars

if ($GLOBALS['RESOURCES_ENABLE']) {
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/DeleteResourcesUser.class.php");
}
if (get_config('CALENDAR_ENABLE')) {
    include_once ($GLOBALS['RELATIVE_PATH_CALENDAR']
    . "/lib/driver/{$GLOBALS['CALENDAR_DRIVER']}/CalendarDriver.class.php");
}
if (get_config('ELEARNING_INTERFACE_ENABLE')){
    require_once ($GLOBALS['RELATIVE_PATH_ELEARNING_INTERFACE'] . "/ELearningUtils.class.php");
}

/**
 * Enter description here...
 *
 */
class UserManagement
{
    var $user_data = array();       // associative array, contains userdata from tables auth_user_md5 and user_info
    var $msg = "";      // contains all messages
    var $db;                // database connection1
    var $db2;           // database connection2
    var $validator;     // object used for checking input
    var $hash_secret = "jdfiuwenxclka";  // set this to something, just something different...

    /**
    * Constructor
    *
    * Pass nothing to create a new user, or the user_id from an existing user to change or delete
    * @access   public
    * @param    string  $user_id    the user which should be retrieved
    */
    function UserManagement($user_id = FALSE) {

        $this->validator = new email_validation_class;
        $this->validator->timeout = 10;                 // How long do we wait for response of mailservers?
        $mail = new StudipMail();
        $this->abuse_email = $mail->getReplyToEmail();
        if ($user_id) {
            $this->getFromDatabase($user_id);
        }
    }


    /**
    * load user data from database into internal array
    *
    * @access   private
    * @param    string  $user_id    the user which should be retrieved
    */
    function getFromDatabase($user_id)
    {
        $query = "SELECT * FROM auth_user_md5 WHERE user_id = ?"; //ein paar userdaten brauchen wir schon mal
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if ($temp) {
            foreach ($temp as $key => $value) {
                $this->user_data['auth_user_md5.' . $key] = $value;
            }
        }

        $query = "SELECT * FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if ($temp) {
            foreach ($temp as $key => $value) {
                $this->user_data['user_info.' . $key] = $value;
            }
        }

        $this->original_user_data = $this->user_data; // save original setting for logging purposes
    }


    /**
    * store user data from internal array into database
    *
    * @access   private
    * @return   bool all data stored?
    */
    function storeToDatabase()
    {
        if (!$this->user_data['auth_user_md5.user_id']) {
            $this->user_data['auth_user_md5.user_id'] = md5(uniqid($this->hash_secret));

            $query = "INSERT INTO auth_user_md5 (user_id, username, password) VALUES (?, ?, 'dummy')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->user_data['auth_user_md5.user_id'],
                $this->user_data['auth_user_md5.username'],
            ));
            if ($statement->rowCount() == 0) {
                return FALSE;
            }

            $query = "INSERT INTO user_info (user_id, mkdate) VALUES (?, UNIX_TIMESTAMP())";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->user_data['auth_user_md5.user_id']
            ));
            if ($statement->rowCount() == 0) {
                return FALSE;
            }
            log_event("USER_CREATE",$this->user_data['auth_user_md5.user_id']);
        }

        if (!$this->user_data['auth_user_md5.auth_plugin']) {
            $this->user_data['auth_user_md5.auth_plugin'] = "standard"; // just to be sure
        }

        $table = $field = $value = null; // Prepare variables

        // Prepare queries
        $query = "UPDATE :table SET :column = :value WHERE user_id = :user_id";
        $update = DBManager::get()->prepare($query);
        $update->bindValue(':user_id', $this->user_data['auth_user_md5.user_id']);
        $update->bindParam(':table', $table, StudipPDO::PARAM_COLUMN);
        $update->bindParam(':column', $field, StudipPDO::PARAM_COLUMN);
        $update->bindParam(':value', $value);

        $query = "DELETE FROM user_inst WHERE user_id = ? AND inst_perms = 'user'";
        $institute_delete = DBManager::get()->prepare($query);

        $query = "UPDATE auth_user_md5 SET visible = 'yes' WHERE user_id = ?";
        $visibility_update = DBManager::get()->prepare($query);

        $changed = 0;
        foreach ($this->user_data as $key => $value) {
            // update changed fields only
            if ($this->original_user_data[$key] != $value) {
                list($table, $field) = explode('.', $key, 2);

                $update->execute();

                // remove all 'user' entries to institutes if global status becomes 'dozent'
                // (cf. http://develop.studip.de/trac/ticket/484 )
                if ($field=='perms' && $this->user_data['auth_user_md5.perms']=='dozent' && in_array($this->original_user_data['auth_user_md5.perms'],array('user','autor','tutor'))) {
                    $this->logInstUserDel($this->user_data['auth_user_md5.user_id'], "inst_perms = 'user'");
                    $institute_delete->execute(array($this->user_data['auth_user_md5.user_id']));
                    // make user visible globally if dozent may not be invisible (StEP 00158)
                    if (get_config('DOZENT_ALWAYS_VISIBLE')) {
                        $visibility_update->execute(array($this->user_data['auth_user_md5.user_id']));
                    }
                }

                // logging
                if ($update->rowCount() != 0) {
                    ++$changed;
                    switch ($field) {
                        case 'username':
                            log_event("USER_CHANGE_USERNAME",$this->user_data['auth_user_md5.user_id'],NULL,$this->original_user_data['auth_user_md5.username']." -> ".$value);
                            break;
                        case 'Vorname':
                            log_event("USER_CHANGE_NAME",$this->user_data['auth_user_md5.user_id'],NULL,"Vorname: ".$this->original_user_data['auth_user_md5.Vorname']." -> ".$value);
                            break;
                        case 'Nachname':
                            log_event("USER_CHANGE_NAME",$this->user_data['auth_user_md5.user_id'],NULL,"Nachname: ".$this->original_user_data['auth_user_md5.Nachname']." -> ".$value);
                            break;
                        case 'perms':
                            log_event("USER_CHANGE_PERMS",$this->user_data['auth_user_md5.user_id'],NULL,$this->original_user_data['auth_user_md5.perms']." -> ".$value);
                            break;
                        case 'Email':
                            log_event("USER_CHANGE_EMAIL",$this->user_data['auth_user_md5.user_id'],NULL,$this->original_user_data['auth_user_md5.Email']." -> ".$value);
                            break;
                        case 'title_front':
                            log_event("USER_CHANGE_TITLE",$this->user_data['auth_user_md5.user_id'],NULL,"title_front: ".$this->original_user_data['user_info.title_front']." -> ".$value);
                            break;
                        case 'title_rear':
                            log_event("USER_CHANGE_TITLE",$this->user_data['auth_user_md5.user_id'],NULL,"title_rear: ".$this->original_user_data['user_info.title_front']." -> ".$value);
                        case 'password':
                            log_event("USER_CHANGE_PASSWORD",$this->user_data['auth_user_md5.user_id'],NULL,"password: ".$this->original_user_data['user_info.password']." -> ".$value);
                            break;
                    }
                }
            }

        }
        if ($changed) {
            $query = "UPDATE user_info SET chdate = UNIX_TIMESTAMP() WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        }
        return (bool)$changed;
    }


    /**
    * generate a secure password of $length characters [a-z0-9]
    *
    * @access   private
    * @param    integer $length number of characters
    * @return   string password
    */
    function generate_password($length) {
        mt_srand((double)microtime()*1000000);
        for ($i=1;$i<=$length;$i++) {
            $temp = mt_rand() % 36;
            if ($temp < 10)
                $temp += 48;     // 0 = chr(48), 9 = chr(57)
            else
                $temp += 87;     // a = chr(97), z = chr(122)
            $pass .= chr($temp);
        }
        return $pass;
    }


    /**
    * Check if Email-Adress is valid and reachable
    *
    * @access   private
    * @param    string  Email-Adress to check
    * @return   bool Email-Adress valid and reachable?
    */
    function checkMail($Email) {
        // Adress correkt?
        if (!$this->validator->ValidateEmailAddress($Email)) {
            $this->msg .= "error§" . _("E-Mail-Adresse syntaktisch falsch!") . "§";
            return FALSE;
        }
        // E-Mail reachable?
        if (!$this->validator->ValidateEmailHost($Email)) {      // Mailserver nicht erreichbar, ablehnen
            $this->msg .= "error§" . _("Mailserver ist nicht erreichbar!") . "§";
            return FALSE;
        }
        if (!$this->validator->ValidateEmailBox($Email)) {      // aber user unbekannt, ablehnen
            $this->msg .= "error§" . sprintf(_("E-Mail an <em>%s</em> ist nicht zustellbar!"), $Email) . "§";
            return FALSE;
        }
        return TRUE;
    }

    /**
    * Create a new studip user with the given parameters
    *
    * @access   public
    * @param    array   structure: array('string table_name.field_name'=>'string value')
    * @return   bool Creation successful?
    */
    function createNewUser($newuser) {
        global $perm;

        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts anzulegen.") . "§";
            return FALSE;
        }
        if (!$perm->is_fak_admin() && $newuser['auth_user_md5.perms'] == "admin") {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>>Admin-Accounts</em> anzulegen.") . "§";
            return FALSE;
        }
        if (!$perm->have_perm("root") && $newuser['auth_user_md5.perms'] == "root") {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Root-Accounts</em> anzulegen.") . "§";
            return FALSE;
        }

        // Do we have all necessary data?
        if (empty($newuser['auth_user_md5.username']) || empty($newuser['auth_user_md5.perms']) || empty ($newuser['auth_user_md5.Email'])) {
            $this->msg .= "error§" . _("Bitte geben Sie <em>Username</em>, <em>Status</em> und <em>E-Mail</em> an!") . "§";
            return FALSE;
        }

        // Is the username correct?
        if (!$this->validator->ValidateUsername($newuser['auth_user_md5.username'])) {
            $this->msg .= "error§" .  _("Der gewählte Benutzername ist zu kurz oder enthält unzulässige Zeichen!") . "§";
            return FALSE;
        }

        // Can we reach the email?
        if (!$this->checkMail($newuser['auth_user_md5.Email'])) {
            return FALSE;
        }

        // Store new values in internal array
        foreach ($newuser as $key => $value) {
            $this->user_data[$key] = $value;
        }

        $password = $this->generate_password(6);
        $this->user_data['auth_user_md5.password'] = md5($password);

        // Does the user already exist?
        // NOTE: This should be a transaction, but it is not...
        $temp = User::findByUsername($newuser['auth_user_md5.username']);
        if ($temp) {
            $this->msg .= "error§" . sprintf(_("BenutzerIn <em>%s</em> ist schon vorhanden!"), $newuser['auth_user_md5.username']) . "§";
            return FALSE;
        }


        if (!$this->storeToDatabase()) {
            $this->msg .= "error§" . sprintf(_("BenutzerIn \"%s\" konnte nicht angelegt werden."), $newuser['auth_user_md5.username']) . "§";
            return FALSE;
        }

        $this->msg .= "msg§" . sprintf(_("BenutzerIn \"%s\" angelegt."), $newuser['auth_user_md5.username']) . "§";

        // Automated entering new users, based on their status (perms)
        $result = AutoInsert::checkNewUser($this->user_data['auth_user_md5.perms'], $this->user_data['auth_user_md5.user_id']);
        foreach ($result as $item) {
            $this->msg .= "msg§".sprintf(_("Der automatische Eintrag in die Veranstaltung <em>%s</em> wurde durchgeführt."), $item) . "§";
        }

        // include language-specific subject and mailbody
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']); // user has been just created, so we will get $DEFAULT_LANGUAGE
        $Zeit=date("H:i:s, d.m.Y",time());
        include("locale/$user_language/LC_MAILS/create_mail.inc.php");

        // send mail
        StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);

        return TRUE;
    }


    /**
    * Change an existing studip user according to the given parameters
    *
    * @access   public
    * @param    array   structure: array('string table_name.field_name'=>'string value')
    * @return   bool Change successful?
    */
    function changeUser($newuser) {
        global $perm, $auth, $SEM_TYPE, $SEM_CLASS;

        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts zu ver&auml;ndern.") . "§";
            return FALSE;
        }
        if (!$perm->is_fak_admin() && $newuser['auth_user_md5.perms'] == "admin") {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung, <em>Admin-Accounts</em> anzulegen.") . "§";
            return FALSE;
        }
        if (!$perm->have_perm("root") && $newuser['auth_user_md5.perms'] == "root") {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung, <em>Root-Accounts</em> anzulegen.") . "§";
            return FALSE;
        }
        if (!$perm->have_perm("root")) {
            if (!$perm->is_fak_admin() && $this->user_data['auth_user_md5.perms'] == "admin") {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Admin-Accounts</em> zu ver&auml;ndern.") . "§";
                return FALSE;
            }
            if ($this->user_data['auth_user_md5.perms'] == "root") {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Root-Accounts</em> zu ver&auml;ndern.") . "§";
                return FALSE;
            }
            if ($perm->is_fak_admin() && $this->user_data['auth_user_md5.perms'] == "admin") {
                if (!$this->adminOK()) {
                    $this->msg .= "error§" . _("Sie haben keine Berechtigung diesen Admin-Account zu ver&auml;ndern.") . "§";
                    return FALSE;
                }
            }
        }

        // active dozent? (ignore the studygroup guys)
        $status = studygroup_sem_types();

        if (empty($status)) {
            $count = 0;
        } else {
            $query = "SELECT COUNT(*)
                      FROM seminar_user AS su
                      LEFT JOIN seminare AS s USING (Seminar_id)
                      WHERE su.user_id = ? AND s.status NOT IN (?) AND su.status = 'dozent'
                      GROUP BY user_id";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->user_data['auth_user_md5.user_id'],
                $status,
            ));
            $count = $statement->fetchColumn();
        }
        if ($count && isset($newuser['auth_user_md5.perms']) && $newuser['auth_user_md5.perms'] != "dozent") {
            $this->msg .= sprintf("error§" . _("Der Benutzer <em>%s</em> ist Dozent in %s aktiven Veranstaltungen und kann daher nicht in einen anderen Status versetzt werden!") . "§", $this->user_data['auth_user_md5.username'], $count);
            return FALSE;
        }

        // active admin?
        if ($this->user_data['auth_user_md5.perms'] == 'admin' && $newuser['auth_user_md5.perms'] != 'admin') {
            // count number of institutes where the user is admin
            $query = "SELECT COUNT(*)
                      FROM user_inst
                      WHERE user_id = ? AND inst_perms = 'admin'
                      GROUP BY Institut_id";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));

            // if there are institutes with admin-perms, add error-message and deny change
            if ($count = $statement->fetchColumn()) {
                $this->msg .= sprintf('error§'. _("Der Benutzer <em>%s</em> ist Admin in %s Einrichtungen und kann daher nicht in einen anderen Status versetzt werden!") .'§', $this->user_data['auth_user_md5.username'], $count);
                return false;
            }
        }

        // Is the username correct?
        if (isset($newuser['auth_user_md5.username'])) {
            if ($this->user_data['auth_user_md5.username'] != $newuser['auth_user_md5.username']) {
                if (!$this->validator->ValidateUsername($newuser['auth_user_md5.username'])) {
                    $this->msg .= "error§" .  _("Der gewählte Benutzername ist zu kurz oder enthält unzulässige Zeichen!") . "§";
                    return FALSE;
                }
                $check_uname = StudipAuthAbstract::CheckUsername($newuser['auth_user_md5.username']);
                if ($check_uname['found']) {
                    $this->msg .= "error§" . _("Der Benutzername wird bereits von einem anderen Benutzer verwendet. Bitte wählen Sie einen anderen Benutzernamen!") . "§";
                    return false;
                } else {
                    //$this->msg .= "info§" . $check_uname['error'] ."§";
                }
            } else
            unset($newuser['auth_user_md5.username']);
        }

        // Can we reach the email?
        if (isset($newuser['auth_user_md5.Email'])) {
            if (!$this->checkMail($newuser['auth_user_md5.Email'])) {
                return FALSE;
            }
        }

        // Store changed values in internal array if allowed
        $old_perms = $this->user_data['auth_user_md5.perms'];
        $auth_plugin = $this->user_data['auth_user_md5.auth_plugin'];
        foreach ($newuser as $key => $value) {
            if (!StudipAuthAbstract::CheckField($key, $auth_plugin)) {
                $this->user_data[$key] = $value;
            } else {
                $this->msg .= "error§" .  sprintf(_("Das Feld <em>%s</em> können Sie nicht ändern!"), $key) . "§";
                return FALSE;
            }
        }

        if (!$this->storeToDatabase()) {
            $this->msg .= "info§" . _("Es wurden keine Veränderungen der Grunddaten vorgenommen.") . "§";
            return false;
        }

        $this->msg .= "msg§" . sprintf(_("Benutzer \"%s\" ver&auml;ndert."), $this->user_data['auth_user_md5.username']) . "§";

        // Automated entering new users, based on their status (perms)
        $result = AutoInsert::checkOldUser($old_perms, $newuser['auth_user_md5.perms'], $this->user_data['auth_user_md5.user_id']);
        foreach ($result as $item) {
            $this->msg .= "msg§".sprintf(_("Der automatische Eintrag in die Veranstaltung <em>%s</em> wurde durchgeführt."), $item) . "§";
        }

        // include language-specific subject and mailbody
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']);
        $Zeit=date("H:i:s, d.m.Y",time());
        include("locale/$user_language/LC_MAILS/change_mail.inc.php");

        // send mail
        StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);

        // Upgrade to admin or root?
        if ($newuser['auth_user_md5.perms'] == "admin" || $newuser['auth_user_md5.perms'] == "root") {

         $this->re_sort_position_in_seminar_user();

            // delete all seminar entries
            $query = "SELECT seminar_id FROM seminar_user WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

            $query = "DELETE FROM seminar_user WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus Veranstaltungen gel&ouml;scht."), $db_ar) . "§";
                array_map('update_admission', $seminar_ids);
            }
            // delete all entries from waiting lists
            $query = "SELECT seminar_id FROM admission_seminar_user WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

            $query = "DELETE FROM admission_seminar_user WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus Wartelisten gel&ouml;scht."), $db_ar) . "§";
                array_map('update_admission', $seminar_ids);
            }
            // delete 'Studiengaenge'
            $query = "DELETE FROM user_studiengang WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Zuordnungen zu Studieng&auml;ngen gel&ouml;scht."), $db_ar) . "§";
            }
            // delete all private appointments of this user
            if ($db_ar = delete_range_of_dates($this->user_data['auth_user_md5.user_id'], FALSE) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus den Terminen gel&ouml;scht."), $db_ar) . "§";
            }
        }

        if ($newuser['auth_user_md5.perms'] == "admin") {

            $this->logInstUserDel($this->user_data['auth_user_md5.user_id'], "inst_perms != 'admin'");
            $query = "DELETE FROM user_inst WHERE user_id = ? AND inst_perms != 'admin'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus MitarbeiterInnenlisten gel&ouml;scht."), $db_ar) . "§";
            }
        }
        if ($newuser['auth_user_md5.perms'] == "root") {
            $this->logInstUserDel($this->user_data['auth_user_md5.user_id']);

            $query = "DELETE FROM user_inst WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus MitarbeiterInnenlisten gel&ouml;scht."), $db_ar) . "§";
            }
        }

        return TRUE;
    }


    private function logInstUserDel($user_id, $condition = NULL) 
    {
        $query = "SELECT Institut_id FROM user_inst WHERE user_id = ?";
        if (isset($condition)) {
            $query .= ' AND ' . $condition;
        }

        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        while ($institute_id = $statement->fetchColumn()) {
            log_event('INST_USER_DEL', $institute_id, $user_id);
        }
    }
    /**
    * Create a new password and mail it to the user
    *
    * @access   public
    * @return   bool Password change successful?
    */
    function setPassword()
    {
        global $perm, $auth;

        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts zu verändern.") . "§";
            return FALSE;
        }

        if (!$perm->have_perm("root")) {
            if ($this->user_data['auth_user_md5.perms'] == "root") {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Root-Accounts</em> zu verändern.") . "§";
                return FALSE;
            }
            if ($perm->is_fak_admin() && $this->user_data['auth_user_md5.perms'] == "admin"){
                if (!$this->adminOK()) {
                    $this->msg .= "error§" . _("Sie haben keine Berechtigung diesen Admin-Account zu verändern.") . "§";
                    return FALSE;
                }
            }
        }

        // Can we reach the email?
        if (!$this->checkMail($this->user_data['auth_user_md5.Email'])) {
            return FALSE;
        }

        $password = $this->generate_password(6);
        $this->user_data['auth_user_md5.password'] = md5($password);

        if (!$this->storeToDatabase()) {
            $this->msg .= "info§" . _("Es wurden keine Veränderungen vorgenommen.") . "§";
        }

        $this->msg .= "msg§" . _("Das Passwort wurde neu gesetzt.") . "§";

        // include language-specific subject and mailbody
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']);
        $Zeit=date("H:i:s, d.m.Y",time());
        include("locale/$user_language/LC_MAILS/password_mail.inc.php");

        // send mail
        StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);
        log_event("USER_NEWPWD",$this->user_data['auth_user_md5.user_id']);
        return TRUE;
    }


    /**
    * Delete an existing user from the database and tidy up
    *
    * @access   public
    * @param    bool delete all documents belonging to the user
    * @return   bool Removal successful?
    */
    function deleteUser($delete_documents = true)
    {
        global $perm, $auth;

        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts zu l&ouml;schen.") . "§";
            return FALSE;
        }

        if (!$perm->have_perm("root")) {
            if ($this->user_data['auth_user_md5.perms'] == "root") {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Root-Accounts</em> zu l&ouml;schen.") . "§";
                return FALSE;
            }
            if ($perm->is_fak_admin() && $this->user_data['auth_user_md5.perms'] == "admin"){
                if (!$this->adminOK()) {
                    $this->msg .= "error§" . _("Sie haben keine Berechtigung diesen Admin-Account zu l&ouml;schen.") . "§";
                    return FALSE;
                }
            }
        }

        $status = studygroup_sem_types();

        // active dozent?
        if (empty($status)) {
            $active_count = 0;
        } else {
            $query = "SELECT SUM(c) AS count FROM (
                          SELECT COUNT(*) AS c
                          FROM seminar_user AS su1
                          INNER JOIN seminar_user AS su2 ON (su1.seminar_id = su2.seminar_id AND su2.status = 'dozent')
                          INNER JOIN seminare ON (su1.seminar_id = seminare.seminar_id AND seminare.status NOT IN (?))
                          WHERE su1.user_id = ? AND su1.status = 'dozent'
                          GROUP BY su1.seminar_id
                          HAVING c = 1
                          ORDER BY NULL
                      ) AS sub";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                studygroup_sem_types(),
                $this->user_data['auth_user_md5.user_id'],
            ));
            $active_count = $statement->fetchColumn();
        }

        if ($active_count) {
            $this->msg .= sprintf("error§" . _("Der Benutzer/die Benutzerin <em>%s</em> ist DozentIn in %s aktiven Veranstaltungen und kann daher nicht gel&ouml;scht werden.") . "§", $this->user_data['auth_user_md5.username'], $active_count);
            return FALSE;

        //founder of studygroup?
        } elseif (get_config('STUDYGROUPS_ENABLE')) {
            $status = studygroup_sem_types();

            if (empty($status)) {
                $group_ids = array();
            } else {
                $query = "SELECT Seminar_id
                          FROM seminare AS s
                          LEFT JOIN seminar_user AS su USING (Seminar_id)
                          WHERE su.status = 'dozent' AND su.user_id = ? AND s.status IN (?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $this->user_data['auth_user_md5.user_id'],
                    studygroup_sem_types(),
                ));
                $group_ids = $statement->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach ($group_ids as $group_id) {
                $sem = Seminar::GetInstance($group_id);
                if (StudygroupModel::countMembers($group_id) > 1) {
                    // check whether there are tutors or even autors that can be promoted
                    $tutors = $sem->getMembers('tutor');
                    $autors = $sem->getMembers('autor');
                    if (count($tutors) > 0) {
                        $new_founder = current($tutors);
                        StudygroupModel::promote_user($new_founder['username'], $sem->getId(), 'dozent');
                        continue;
                    }
                    // if not promote an autor
                    elseif (count($autors) > 0) {
                        $new_founder = current($autors);
                        StudygroupModel::promote_user($new_founder['username'], $sem->getId(), 'dozent');
                        continue;
                    }
                // since no suitable successor was found, we are allowed to remove the studygroup
                } else {
                    $sem->delete();
                }
                unset($sem);
            }
        }

        // store user preferred language for sending mail
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']);

        // delete documents of this user
        if ($delete_documents) {
            $temp_count = 0;
            $query = "SELECT dokument_id FROM dokumente WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            while ($document_id = $statement->fetchColumn()) {
                if (delete_document($document_id)) {
                    $temp_count++;
                }
            }
            if ($temp_count) {
                $this->msg .= "info§" . sprintf(_("%s Dokumente gel&ouml;scht."), $temp_count) . "§";
            }

            // delete empty folders of this user
            $temp_count = 0;
            
            $query = "SELECT COUNT(*) FROM folder WHERE range_id = ?";
            $count_content = DBManager::get()->prepare($query);

            $query = "DELETE FROM folder WHERE folder_id = ?";
            $delete_folder = DBManager::get()->prepare($query);

            $query = "SELECT folder_id FROM folder WHERE user_id = ? ORDER BY mkdate DESC";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            while ($folder_id = $statement->fetchColumn()) {
                $count_content->execute(array($folder_id));
                $count = $count_content->fetchColumn();
                $count_content->closeCursor();

                if (!$count && !doc_count($folder_id)) {
                    $delete_folder->execute(array($folder_id));
                    $temp_count += $delete_folder->rowCount();
                }
            }
            if ($temp_count) {
                $this->msg .= "info§" . sprintf(_("%s leere Ordner gel&ouml;scht."), $temp_count) . "§";
            }

            // folder left?
            $query = "SELECT COUNT(*) FROM folder WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            $count = $statement->fetchColumn();
            if ($count) {
                $this->msg .= sprintf("info§" . _("%s Ordner konnten nicht gel&ouml;scht werden, da sie noch Dokumente anderer BenutzerInnen enthalten.") . "§", $count);
            }
        }
        // kill all the ressources that are assigned to the user (and all the linked or subordinated stuff!)
        if ($GLOBALS['RESOURCES_ENABLE']) {
            $killAssign = new DeleteResourcesUser($this->user_data['auth_user_md5.user_id']);
            $killAssign->delete();
        }

        $this->re_sort_position_in_seminar_user();

        // delete user from seminars (postings will be preserved)
        $query = "DELETE FROM seminar_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus Veranstaltungen gel&ouml;scht."), $db_ar) . "§";
        }

        // delete user from waiting lists
        $query = "SELECT seminar_id FROM admission_seminar_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        $seminar_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        $query = "DELETE FROM admission_seminar_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus Wartelisten gel&ouml;scht."), $db_ar) . "§";
            array_map('update_admission', $seminar_ids);
        }

        // delete user from instituts
        $this->logInstUserDel($this->user_data['auth_user_md5.user_id']);

        $query = "DELETE FROM user_inst WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus MitarbeiterInnenlisten gel&ouml;scht."), $db_ar) . "§";
        }

        // delete user from Statusgruppen
        if ($db_ar = RemovePersonFromAllStatusgruppen(get_username($this->user_data['auth_user_md5.user_id']))  > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus Funktionen / Gruppen gel&ouml;scht."), $db_ar) . "§";
        }

        // delete user from archiv
        $query = "DELETE FROM archiv_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus den Zugriffsberechtigungen f&uuml;r das Archiv gel&ouml;scht."), $db_ar) . "§";
        }

        // delete all personal news from this user
        if (($db_ar = StudipNews::DeleteNewsByAuthor($this->user_data['auth_user_md5.user_id']))) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus den Ankündigungen gel&ouml;scht."), $db_ar) . "§";
        }
        if (($db_ar = StudipNews::DeleteNewsRanges($this->user_data['auth_user_md5.user_id']))) {
            $this->msg .= "info§" . sprintf(_("%s Verweise auf Ankündigungen gel&ouml;scht."), $db_ar) . "§";
        }

        //delete entry in news_rss_range
        StudipNews::UnsetRssId($this->user_data['auth_user_md5.user_id']);

        // delete 'Studiengaenge'
        $query = "DELETE FROM user_studiengang WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0)
            $this->msg .= "info§" . sprintf(_("%s Zuordnungen zu Studieng&auml;ngen gel&ouml;scht."), $db_ar) . "§";

        // delete all private appointments of this user
        if (get_config('CALENDAR_ENABLE')) {
            $calendar = new CalendarDriver($this->user_data['auth_user_md5.user_id']);
            if ($appkills = $calendar->deleteFromDatabase('ALL'))
                $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus den Terminen gel&ouml;scht."), $appkills) ."§";
        }

        // delete all messages send or received by this user
        $messaging=new messaging;
        $messaging->delete_all_messages($this->user_data['auth_user_md5.user_id'], TRUE);

        // delete user from all foreign adressbooks and empty own adressbook
        $buddykills = RemoveUserFromBuddys($this->user_data['auth_user_md5.user_id']);
        if ($buddykills > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus Adressb&uuml;chern gel&ouml;scht."), $buddykills) . "§";
        }
        $msg = DeleteAdressbook($this->user_data['auth_user_md5.user_id']);
        if ($msg) {
            $this->msg .= "info§" . $msg . "§";
        }

        // delete all guestbook entrys
        $query = "DELETE FROM guestbook WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Eintr&auml;ge aus dem Gästebuch gel&ouml;scht."), $db_ar) . "§";
        }

        // delete the datafields
        $localEntries = DataFieldEntry::removeAll($this->user_data['auth_user_md5.user_id']);

        UserConfigEntry::deleteByUser($this->user_data['auth_user_md5.user_id']);

        // delete all remaining user data
        $query = "DELETE FROM seminar_user_schedule WHERE user_id = ?";
        DBManager::get()->prepare($query)->execute(array($this->user_data['auth_user_md5.user_id']));
        $query = "DELETE FROM rss_feeds WHERE user_id = ?";
        DBManager::get()->prepare($query)->execute(array($this->user_data['auth_user_md5.user_id']));
        $query = "DELETE FROM kategorien WHERE range_id = ?";
        DBManager::get()->prepare($query)->execute(array($this->user_data['auth_user_md5.user_id']));
        $query = "DELETE FROM user_info WHERE user_id = ?";
        DBManager::get()->prepare($query)->execute(array($this->user_data['auth_user_md5.user_id']));
        $query = "DELETE FROM user_visibility WHERE user_id = ?";
        DBManager::get()->prepare($query)->execute(array($this->user_data['auth_user_md5.user_id']));
        $GLOBALS['user']->that->ac_delete($this->user_data['auth_user_md5.user_id'], $GLOBALS['user']->name);
        object_kill_visits($this->user_data['auth_user_md5.user_id']);
        object_kill_views($this->user_data['auth_user_md5.user_id']);

        // delete picture
        $avatar = Avatar::getAvatar($this->user_data["auth_user_md5.user_id"]);
        if ($avatar->is_customized()) {
            $avatar->reset();
            $this->msg .= "info§" . _("Bild gel&ouml;scht.") . "§";
        }

        //delete connected users
        if (get_config('ELEARNING_INTERFACE_ENABLE')){
            if(ElearningUtils::initElearningInterfaces()){
                foreach($GLOBALS['connected_cms'] as $cms){
                    if(is_object($cms->user)){
                        $user_auto_create = $cms->USER_AUTO_CREATE;
                        $cms->USER_AUTO_CREATE = false;
                        $userclass = strtolower(get_class($cms->user));
                        $connected_user = new $userclass($cms->cms_type, $this->user_data['auth_user_md5.user_id']);
                        if($ok = $connected_user->deleteUser()){
                            if($connected_user->is_connected){
                                $this->msg .= "info§" . sprintf(_("Der verknüpfte Nutzer %s wurde im System %s gelöscht."), $connected_user->login, $connected_user->cms_type) . "§";
                            }
                        }
                        $cms->USER_AUTO_CREATE = $user_auto_create;
                    }
                }
            }
        }

        // delete deputy entries if necessary
        $query = "DELETE FROM deputies WHERE ? IN (user_id, range_id)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        $deputyEntries = $statement->rowCount();
        if ($deputyEntries) {
            $this->msg .= "info§".sprintf(_("%s Einträge in den Vertretungseinstellungen gelöscht."), $deputyEntries)."§";
        }

        // delete Stud.IP account
        $query = "DELETE FROM auth_user_md5 WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (!$statement->rowCount()) {
            $this->msg .= "error§<em>" . _("Fehler:") . "</em> " . $query . "§";
            return FALSE;
        } else {
            $this->msg .= "msg§" . sprintf(_("Benutzer \"%s\" gel&ouml;scht."), $this->user_data['auth_user_md5.username']) . "§";
        }
        log_event("USER_DEL",$this->user_data['auth_user_md5.user_id'],NULL,sprintf("%s %s (%s)", $this->user_data['auth_user_md5.Vorname'], $this->user_data['auth_user_md5.Nachname'], $this->user_data['auth_user_md5.username'])); //log with Vorname Nachname (username) as info string

        // Can we reach the email?
        if ($this->checkMail($this->user_data['auth_user_md5.Email'])) {
            // include language-specific subject and mailbody
            $Zeit=date("H:i:s, d.m.Y",time());
            include("locale/$user_language/LC_MAILS/delete_mail.inc.php");

            // send mail
            StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);

        }

        unset($this->user_data);
        return TRUE;

    }
    
    private function adminOK()
    {
        static $ok = null;

        if ($ok === null) {
            $query = "SELECT COUNT(a.Institut_id) = COUNT(c.inst_perms)
                      FROM user_inst AS a
                      LEFT JOIN Institute b ON (a.Institut_id = b.Institut_id AND b.Institut_id != b.fakultaets_id)
                      LEFT JOIN user_inst AS c ON (b.fakultaets_id = c.Institut_id AND c.user_id = ?
                                                  AND c.inst_perms = 'admin')
                      WHERE a.user_id = ? AND a.inst_perms = 'admin'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $GLOBALS['auth']->auth['uid'],
                $this->user_data['auth_user_md5.user_id'],
            ));
            $ok = $statement->fetchColumn();
        }

        return $ok;
    }

    function re_sort_position_in_seminar_user()
    {
        $query = "SELECT Seminar_id, position, status
                  FROM seminar_user
                  WHERE user_id = ? AND status IN ('tutor', 'dozent')";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($row['status'] == 'tutor') {
                re_sort_tutoren($row['Seminar_id'], $row['position']);
            } else if ($row['status'] == 'dozent') {
                re_sort_dozenten($row['Seminar_id'], $row['position']);
            }
        }
    }

    /**
    * Change an existing user password
    *
    * @param string $password
    * @return bool change successful?
    */
    function changePassword($password)
    {
        global $perm, $auth;

        $this->user_data['auth_user_md5.password'] = md5($password);
        $this->storeToDatabase();

        $this->msg .= "msg§" . _("Das Passwort wurde neu gesetzt.") . "§";

        // include language-specific subject and mailbody
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']);
        $Zeit=date("H:i:s, d.m.Y",time());
        include("locale/$user_language/LC_MAILS/password_mail.inc.php");

        // send mail
        StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);

        return TRUE;
    }
}
