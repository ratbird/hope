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
require_once 'lib/admission.inc.php';   // remove user from waiting lists
require_once 'lib/datei.inc.php';   // remove documents of user
require_once 'lib/statusgruppe.inc.php';    // remove user from statusgroups
require_once 'lib/dates.inc.php';   // remove appointments of user
require_once 'lib/messaging.inc.php';   // remove messages send or recieved by user
require_once 'lib/contact.inc.php'; // remove user from adressbooks
require_once 'lib/classes/DataFieldEntry.class.php';    // remove extra data of user
require_once 'lib/classes/auth_plugins/StudipAuthAbstract.class.php';
require_once 'lib/object.inc.php';
require_once 'lib/log_events.inc.php';  // Event logging
require_once 'app/models/studygroup.php';
require_once 'vendor/phpass/PasswordHash.php';

if ($GLOBALS['RESOURCES_ENABLE']) {
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/DeleteResourcesUser.class.php");
}
if (get_config('ELEARNING_INTERFACE_ENABLE')){
    require_once ($GLOBALS['RELATIVE_PATH_ELEARNING_INTERFACE'] . "/ELearningUtils.class.php");
}

/**
 * Adapter to fake user_data property in UserManagement
 *
 * @author noack
 *
 */
class UserDataAdapter implements ArrayAccess, Countable, IteratorAggregate
{
    private $user;

    function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $offset
     * @return string
     */
    function adaptOffset($offset)
    {
        $adapted = trim(strstr($offset, '.'), '.');
        return $adapted ?: $offset;
    }

    /**
    * ArrayAccess: Check whether the given offset exists.
    */
    function offsetExists($offset)
    {

        return $this->user->offsetExists($this->adaptOffset($offset));
    }

    /**
     * ArrayAccess: Get the value at the given offset.
     */
    function offsetGet($offset)
    {
        return $this->user->offsetGet($this->adaptOffset($offset));
    }

    /**
     * ArrayAccess: Set the value at the given offset.
     */
    function offsetSet($offset, $value)
    {
        return $this->user->offsetSet($this->adaptOffset($offset), $value);
    }

    /**
     * ArrayAccess: unset the value at the given offset.
     */
    function offsetUnset($offset)
    {
        return $this->user->offsetUnset($this->adaptOffset($offset));
    }

    /**
     * @see Countable::count()
     */
    function count()
    {
        return $this->user->count();
    }

    /**
     * @see IteratorAggregate::getIterator()
     */
    function getIterator()
    {
        return $this->user->getIterator();
    }

    /**
      * @param array $data
      * @param bool $reset
      */
    function setData($data, $reset = false)
    {
        $adapted_data = array();
        foreach ($data as $k => $v) {
            $adapted_data[$this->adaptOffset($k)] = $v;
        }
        $this->user->setData($adapted_data, $reset);
    }
}

/**
 * UserManagement.class.php
 *
 * Management for the Stud.IP global users
 *
 */
class UserManagement
{
    private $user;
    private $validator;
    private $user_data;

    public $msg;

    private static $pwd_hasher;

    public static function getPwdHasher()
    {
        if (self::$pwd_hasher === null) {
            self::$pwd_hasher = new PasswordHash(8, $GLOBALS['PHPASS_USE_PORTABLE_HASH']);
        }
        return self::$pwd_hasher;
    }

    /**
    * Constructor
    *
    * Pass nothing to create a new user, or the user_id from an existing user to change or delete
    * @access   public
    * @param    string  $user_id    the user which should be retrieved
    */
    function UserManagement($user_id = FALSE)
    {
        $this->validator = new email_validation_class;
        $this->validator->timeout = 10;                 // How long do we wait for response of mailservers?
        $this->getFromDatabase($user_id);
    }

    function __get($attr)
    {
        if ($attr === 'user_data') {
            return $this->user_data;
        }
    }

    function __set($attr, $value)
    {
        if ($attr === 'user_data') {
            if (!is_array($value)) {
                throw InvalidArgumentException('user_data only accepts array');
            }
            return $this->user_data->setData($value, true);
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
        $this->user = User::toObject($user_id);
        if (!$this->user) {
            $this->user = new User();
        }
        $this->user_data = new UserDataAdapter($this->user);
    }

    /**
    * store user data from internal array into database
    *
    * @access   private
    * @return   bool all data stored?
    */
    function storeToDatabase()
    {
        if ($this->user->isNew()) {
            if ($this->user->store()) {
                log_event("USER_CREATE", $this->user->id, null, join(';', $this->user->toArray('username vorname nachname perms email')));
                return true;
            } else {
                return false;
            }
        }

        $nperms = array(
            'user' => 0,
            'autor' => 1,
            'tutor' => 2,
            'dozent' => 3
        );

        if ($this->user->isDirty('perms')) {
            if ($this->user->perms == 'dozent' && in_array($this->user->getPristineValue('perms'), array('user','autor','tutor'))) {
                $this->logInstUserDel($this->user->id, "inst_perms = 'user'");
                $this->user->institute_memberships->unsetBy('inst_perms', 'user');
                // make user visible globally if dozent may not be invisible (StEP 00158)
                if (get_config('DOZENT_ALWAYS_VISIBLE') && $this->user->visible != 'never') {
                    $this->user->visible = 'yes';
                }
                if ($nperms[$this->user->perms] < $nperms[$this->user->getPristineValue('perms')]) {
                    $downgrade = DBManager::get()->prepare(
                            "UPDATE seminar_user " .
                            "INNER JOIN seminare ON (seminare.Seminar_id = seminar_user.Seminar_id) " .
                            "SET seminar_user.status = :new_max_status " .
                            "WHERE seminar_user.user_id = :user_id " .
                            "AND seminar_user.status IN (:old_status) " .
                            "AND seminare.status NOT IN (:studygroups) " .
                            "");
                    $old_status = array();
                    foreach ($nperms as $status => $n) {
                        if ($n > $nperms[$this->user->perms] && $n <= $nperms[$this->user->getPristineValue('perms')]) {
                            $old_status[] = $status;
                        }
                    }
                    $downgrade->execute(array(
                            'user_id' => $this->user->id,
                            'old_status' => $old_status,
                            'studygroups' => studygroup_sem_types(),
                            'new_max_status' => $this->user->perms
                    ));
                }
            }
        }
        foreach (words('username vorname nachname perms email title_front title_rear password') as $field) {
            // logging
            if ($this->user->isFieldDirty($field)) {
                $old_value = $this->user->getPristineValue($field);
                $value = $this->user->getValue($field);
                switch ($field) {
                    case 'username':
                        log_event("USER_CHANGE_USERNAME",$this->user->id,NULL,$old_value." -> ".$value);
                        break;
                    case 'vorname':
                        log_event("USER_CHANGE_NAME",$this->user->id,NULL,"Vorname: ".$old_value." -> ".$value);
                        break;
                    case 'nachname':
                        log_event("USER_CHANGE_NAME",$this->user->id,NULL,"Nachname: ".$old_value." -> ".$value);
                        break;
                    case 'perms':
                        log_event("USER_CHANGE_PERMS",$this->user->id,NULL,$old_value." -> ".$value);
                        break;
                    case 'email':
                        log_event("USER_CHANGE_EMAIL",$this->user->id,NULL,$old_value." -> ".$value);
                        break;
                    case 'title_front':
                        log_event("USER_CHANGE_TITLE",$this->user->id,NULL,"title_front: ".$old_value." -> ".$value);
                        break;
                    case 'title_rear':
                        log_event("USER_CHANGE_TITLE",$this->user->id,NULL,"title_rear: ".$old_value." -> ".$value);
                    case 'password':
                        log_event("USER_CHANGE_PASSWORD",$this->user->id,NULL,"password: ".$old_value." -> ".$value);
                        break;
                }
            }
        }

        $changed = $this->user->store();
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

        if (!$newuser['auth_user_md5.auth_plugin']) {
            $newuser['auth_user_md5.auth_plugin'] = 'standard';
        }

        // Store new values in internal array
        $this->getFromDatabase(null);
        $this->user_data->setData($newuser);

        if ($this->user_data['auth_user_md5.auth_plugin'] == 'standard') {
            $password = $this->generate_password(6);
            $this->user_data['auth_user_md5.password'] = self::getPwdHasher()->HashPassword($password);
        }

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
        $result = AutoInsert::instance()->saveUser($this->user_data['auth_user_md5.user_id'],$this->user_data['auth_user_md5.perms']);

        foreach ($result['added'] as $item) {
            $this->msg .= "msg§".sprintf(_("Das automatische Eintragen in die Veranstaltung <em>%s</em> wurde durchgeführt."), $item) . "§";
        }
        foreach ($result['removed'] as $item) {
            $this->msg .= "msg§".sprintf(_("Das automatische Austragen aus der Veranstaltung <em>%s</em> wurde durchgeführt."), $item) . "§";
        }

        // include language-specific subject and mailbody
        $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']); // user has been just created, so we will get $DEFAULT_LANGUAGE
        $Zeit=date("H:i:s, d.m.Y",time());
        include("locale/$user_language/LC_MAILS/create_mail.inc.php");

        // send mail
        StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);

        // add default visibility settings
        Visibility::createDefaultCategories($this->user_data['auth_user_md5.user_id']);

        return TRUE;
    }

    /**
     * Create a new preliminary studip user with the given parameters
     *
     * @access   public
     * @param    array   structure: array('string table_name.field_name'=>'string value')
     * @return   bool Creation successful?
     */
    function createPreliminaryUser($newuser) {
        global $perm;

        $this->getFromDatabase(null);
        $this->user_data->setData($newuser);
        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts anzulegen.") . "§";
            return FALSE;
        }
        if (in_array($this->user->perms, words('root admin'))) {
            $this->msg .= "error§" . _("Es können keine vorläufigen Administrationsaccounts angelegt werden.") . "§";
            return FALSE;
        }
        if (!$this->user->id) {
            $this->user->setId($this->user->getNewId());
        }
        if (!$this->user->username) {
            $this->user->username = $this->user->id;
        }
        $this->user->auth_plugin = null;
        $this->user->visible = 'never';

        // Do we have all necessary data?
        if (empty ($this->user->perms) || empty ($this->user->vorname) || empty ($this->user->nachname)) {
            $this->msg .= "error§" . _("Bitte geben Sie <em>Status</em>, <em>Vorname</em> und <em>Nachname</em> an!") . "§";
            return FALSE;
        }

        // Is the username correct?
        if (!$this->validator->ValidateUsername($this->user->username)) {
            $this->msg .= "error§" .  _("Der gewählte Benutzername ist zu kurz oder enthält unzulässige Zeichen!") . "§";
            return FALSE;
        }

        // Does the user already exist?
        // NOTE: This should be a transaction, but it is not...
        $temp = User::findByUsername($this->user->username);
        if ($temp) {
            $this->msg .= "error§" . sprintf(_("BenutzerIn <em>%s</em> ist schon vorhanden!"), $this->user->username) . "§";
            return FALSE;
        }

        if (!$this->storeToDatabase()) {
            $this->msg .= "error§" . sprintf(_("BenutzerIn \"%s\" konnte nicht angelegt werden."), $this->user->username) . "§";
            return FALSE;
        }

        $this->msg .= "msg§" . sprintf(_("BenutzerIn \"%s\" (vorläufig) angelegt."), $this->user->username) . "§";

        // add default visibility settings
        Visibility::createDefaultCategories($this->user->id);

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
        global $perm;

        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts zu verändern.") . "§";
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
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Admin-Accounts</em> zu verändern.") . "§";
                return FALSE;
            }
            if ($this->user_data['auth_user_md5.perms'] == "root") {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Root-Accounts</em> zu verändern.") . "§";
                return FALSE;
            }
            if ($perm->is_fak_admin() && $this->user_data['auth_user_md5.perms'] == "admin") {
                if (!$this->adminOK()) {
                    $this->msg .= "error§" . _("Sie haben keine Berechtigung diesen Admin-Account zu verändern.") . "§";
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
                      WHERE su.user_id = ?
                          AND s.status NOT IN (?)
                          AND su.status = 'dozent'
                          AND (SELECT COUNT(*) FROM seminar_user su2 WHERE Seminar_id = su.Seminar_id AND su2.status = 'dozent') = 1
                      GROUP BY user_id";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $this->user_data['auth_user_md5.user_id'],
                $status,
            ));
            $count = $statement->fetchColumn();
        }
        if ($count && isset($newuser['auth_user_md5.perms']) && $newuser['auth_user_md5.perms'] != "dozent") {
            $this->msg .= sprintf("error§" . _("Der Benutzer <em>%s</em> ist alleiniger Dozent in %s aktiven Veranstaltungen und kann daher nicht in einen anderen Status versetzt werden!") . "§", $this->user_data['auth_user_md5.username'], $count);
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

        $this->msg .= "msg§" . sprintf(_("Benutzer \"%s\" verändert."), $this->user_data['auth_user_md5.username']) . "§";
        if ($auth_plugin !== null) {
            // Automated entering new users, based on their status (perms)
            $result = AutoInsert::instance()->saveUser( $this->user_data['auth_user_md5.user_id'],$newuser['auth_user_md5.perms']);
            foreach ($result['added'] as $item) {
                $this->msg .= "msg§".sprintf(_("Das automatische Eintragen in die Veranstaltung <em>%s</em> wurde durchgeführt."), $item) . "§";
            }
            foreach ($result['removed'] as $item) {
                $this->msg .= "msg§".sprintf(_("Das automatische Austragen aus der Veranstaltung <em>%s</em> wurde durchgeführt."), $item) . "§";
            }
            // include language-specific subject and mailbody
            $user_language = getUserLanguagePath($this->user_data['auth_user_md5.user_id']);
            $Zeit=date("H:i:s, d.m.Y",time());
            include("locale/$user_language/LC_MAILS/change_mail.inc.php");

            // send mail
            StudipMail::sendMessage($this->user_data['auth_user_md5.Email'],$subject, $mailbody);
        }
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
                $this->msg .= "info§" . sprintf(_("%s Einträge aus Veranstaltungen gelöscht."), $db_ar) . "§";
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
                $this->msg .= "info§" . sprintf(_("%s Einträge aus Wartelisten gelöscht."), $db_ar) . "§";
                array_map('update_admission', $seminar_ids);
            }
            // delete 'Studiengaenge'
            $query = "DELETE FROM user_studiengang WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Zuordnungen zu Studiengängen gelöscht."), $db_ar) . "§";
            }
            // delete all private appointments of this user
            if ($db_ar = delete_range_of_dates($this->user_data['auth_user_md5.user_id'], FALSE) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Einträge aus den Terminen gelöscht."), $db_ar) . "§";
            }
        }

        if ($newuser['auth_user_md5.perms'] == "admin") {

            $this->logInstUserDel($this->user_data['auth_user_md5.user_id'], "inst_perms != 'admin'");
            $query = "DELETE FROM user_inst WHERE user_id = ? AND inst_perms != 'admin'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Einträge aus MitarbeiterInnenlisten gelöscht."), $db_ar) . "§";
            }
        }
        if ($newuser['auth_user_md5.perms'] == "root") {
            $this->logInstUserDel($this->user_data['auth_user_md5.user_id']);

            $query = "DELETE FROM user_inst WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            if (($db_ar = $statement->rowCount()) > 0) {
                $this->msg .= "info§" . sprintf(_("%s Einträge aus MitarbeiterInnenlisten gelöscht."), $db_ar) . "§";
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
        global $perm;

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
        $this->user_data['auth_user_md5.password'] = self::getPwdHasher()->HashPassword($password);

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
        global $perm;

        // Do we have permission to do so?
        if (!$perm->have_perm("admin")) {
            $this->msg .= "error§" . _("Sie haben keine Berechtigung Accounts zu löschen.") . "§";
            return FALSE;
        }

        if (!$perm->have_perm("root")) {
            if ($this->user_data['auth_user_md5.perms'] == "root") {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung <em>Root-Accounts</em> zu löschen.") . "§";
                return FALSE;
            }
            if ($this->user_data['auth_user_md5.perms'] == "admin" && !$this->adminOK()) {
                $this->msg .= "error§" . _("Sie haben keine Berechtigung diesen Admin-Account zu löschen.") . "§";
                return FALSE;
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
            $this->msg .= sprintf("error§" . _("Der Benutzer/die Benutzerin <em>%s</em> ist DozentIn in %s aktiven Veranstaltungen und kann daher nicht gelöscht werden.") . "§", $this->user_data['auth_user_md5.username'], $active_count);
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

            // Remove private file space of this user
            $root_dir = new RootDirectory($this->user_data['auth_user_md5.user_id']);
            $root_dir->delete();

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
                $this->msg .= "info§" . sprintf(_("%s leere Ordner gelöscht."), $temp_count) . "§";
            }

            // folder left?
            $query = "SELECT COUNT(*) FROM folder WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_data['auth_user_md5.user_id']));
            $count = $statement->fetchColumn();
            if ($count) {
                $this->msg .= sprintf("info§" . _("%s Ordner konnten nicht gelöscht werden, da sie noch Dokumente anderer BenutzerInnen enthalten.") . "§", $count);
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
            $this->msg .= "info§" . sprintf(_("%s Einträge aus Veranstaltungen gelöscht."), $db_ar) . "§";
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
            $this->msg .= "info§" . sprintf(_("%s Einträge aus Wartelisten gelöscht."), $db_ar) . "§";
            array_map('update_admission', $seminar_ids);
        }

        // delete user from instituts
        $this->logInstUserDel($this->user_data['auth_user_md5.user_id']);

        $query = "DELETE FROM user_inst WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Einträge aus MitarbeiterInnenlisten gelöscht."), $db_ar) . "§";
        }

        // delete user from Statusgruppen
        if ($db_ar = RemovePersonFromAllStatusgruppen(get_username($this->user_data['auth_user_md5.user_id']))  > 0) {
            $this->msg .= "info§" . sprintf(_("%s Einträge aus Funktionen / Gruppen gelöscht."), $db_ar) . "§";
        }

        // delete user from archiv
        $query = "DELETE FROM archiv_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Einträge aus den Zugriffsberechtigungen für das Archiv gelöscht."), $db_ar) . "§";
        }

        // delete all personal news from this user
        if (($db_ar = StudipNews::DeleteNewsByAuthor($this->user_data['auth_user_md5.user_id']))) {
            $this->msg .= "info§" . sprintf(_("%s Einträge aus den Ankündigungen gelöscht."), $db_ar) . "§";
        }
        if (($db_ar = StudipNews::DeleteNewsRanges($this->user_data['auth_user_md5.user_id']))) {
            $this->msg .= "info§" . sprintf(_("%s Verweise auf Ankündigungen gelöscht."), $db_ar) . "§";
        }

        //delete entry in news_rss_range
        StudipNews::UnsetRssId($this->user_data['auth_user_md5.user_id']);

        // delete 'Studiengaenge'
        $query = "DELETE FROM user_studiengang WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Zuordnungen zu Studiengängen gelöscht."), $db_ar) . "§";
        }

        // delete all private appointments of this user
        if (get_config('CALENDAR_ENABLE')) {
            $appkills = CalendarEvent::deleteBySQL('range_id = ?',
                    array($this->user_data['auth_user_md5.user_id']));
            if ($appkills) {
                $this->msg .= "info§" . sprintf(_("%s Einträge aus den Terminen gelöscht."), $appkills) ."§";
            }
        }

        // delete all messages send or received by this user
        $messaging=new messaging;
        $messaging->delete_all_messages($this->user_data['auth_user_md5.user_id']);

        // delete user from all foreign adressbooks and empty own adressbook
        $buddykills = RemoveUserFromBuddys($this->user_data['auth_user_md5.user_id']);
        if ($buddykills > 0) {
            $this->msg .= "info§" . sprintf(_("%s Einträge aus Adressbüchern gelöscht."), $buddykills) . "§";
        }
        $msg = DeleteAdressbook($this->user_data['auth_user_md5.user_id']);
        if ($msg) {
            $this->msg .= "info§" . $msg . "§";
        }

        // delete all blubber entrys
        $query = "DELETE FROM blubber WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_data['auth_user_md5.user_id']));
        if (($db_ar = $statement->rowCount()) > 0) {
            $this->msg .= "info§" . sprintf(_("%s Blubber gelöscht."), $db_ar) . "§";
        }

        // delete the datafields
        $localEntries = DataFieldEntry::removeAll($this->user_data['auth_user_md5.user_id']);

        UserConfigEntry::deleteByUser($this->user_data['auth_user_md5.user_id']);

        // delete all remaining user data
        $queries = array(
            "DELETE FROM kategorien WHERE range_id = ?",
            "DELETE FROM user_info WHERE user_id = ?",
            "DELETE FROM user_visibility WHERE user_id = ?",
            "DELETE FROM user_online WHERE user_id = ?",
            "DELETE FROM auto_insert_user WHERE user_id = ?",
            "DELETE FROM roles_user WHERE userid = ?",
            "DELETE FROM schedule WHERE user_id = ?",
            "DELETE FROM schedule_seminare WHERE user_id = ?",
            "DELETE FROM termin_related_persons WHERE user_id = ?",
            "DELETE FROM user_userdomains WHERE user_id = ?",
        );
        foreach ($queries as $query) {
            DBManager::get()->prepare($query)->execute(array($this->user_data['auth_user_md5.user_id']));
        }

        object_kill_visits($this->user_data['auth_user_md5.user_id']);
        object_kill_views($this->user_data['auth_user_md5.user_id']);

        // delete picture
        $avatar = Avatar::getAvatar($this->user_data["auth_user_md5.user_id"]);
        if ($avatar->is_customized()) {
            $avatar->reset();
            $this->msg .= "info§" . _("Bild gelöscht.") . "§";
        }

        // delete visibility settings
        Visibility::removeUserPrivacySettings($this->user_data['auth_user_md5.user_id']);

        //delete connected users
        if (get_config('ELEARNING_INTERFACE_ENABLE')){
            if(ElearningUtils::initElearningInterfaces()){
                foreach($GLOBALS['connected_cms'] as $cms){
                    if ($cms->auth_necessary && ($cms->user instanceOf ConnectedUser)) {
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
            $this->msg .= "msg§" . sprintf(_("Benutzer \"%s\" gelöscht."), $this->user_data['auth_user_md5.username']) . "§";
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
        global $perm;

        $this->user_data['auth_user_md5.password'] = self::getPwdHasher()->HashPassword($password);
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
