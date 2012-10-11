<?php
# Lifter002: DONE - not applicable
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - not applicable
/**
 * functions.php
 *
 * The Stud.IP-Core functions. Look to the descriptions to get further details
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @author      André Noack <andre.noack@gmx.net>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @access      public
 * @package     studip_core
 * @modulegroup library
 * @module      functions.php
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// functions.php
// Stud.IP Kernfunktionen
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>,
// Ralf Stockmann <rstockm@gwdg.de>, André Noack André Noack <andre.noack@gmx.net>
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

require_once ('lib/classes/StudipSemTree.class.php');
require_once ('lib/classes/StudipRangeTree.class.php');
require_once ('lib/classes/Modules.class.php');
require_once ('lib/classes/SemesterData.class.php');
require_once ('lib/classes/HolidayData.class.php');
require_once ('lib/visual.inc.php');
require_once ('lib/object.inc.php');
require_once ('lib/user_visible.inc.php');
require_once ('lib/exceptions/AccessDeniedException.php');
require_once ('lib/exceptions/CheckObjectException.php');

/**
 * This function creates the header line for studip-objects
 *
 * you will get a line like this "Veranstaltung: Name..."
 *
 * @param string $id          the id of the Veranstaltung
 * @param string $object_name the name of the object (optional)
 *
 * @return string  the header-line
 *
 */
function getHeaderLine($id, $object_name = null)
{
    if(!$object_name){
        $object_name = get_object_name($id, get_object_type($id));
    }
    $header_line = $object_name['type'];
    if ($object_name['name']) $header_line.=": ";
    if (studip_strlen($object_name['name']) > 60){
            $header_line .= studip_substr($object_name['name'], 0, 60);
            $header_line .= "... ";
    } else {
        $header_line .= $object_name['name'];
    }
    return $header_line;
}

/**
 * returns an array containing name and type of the passed objeact
 * denoted by $range_id
 *
 * @global array $SEM_TYPE
 * @global array $INST_TYPE
 * @global array $SEM_TYPE_MISC_NAME
 *
 * @param string $range_id    the id of the object
 * @param string $object_type the type of the object
 * 
 * @return array  an array containing name and type of the object
 */
function get_object_name($range_id, $object_type)
{
    global $SEM_TYPE,$INST_TYPE, $SEM_TYPE_MISC_NAME;

    if ($object_type == "sem") {
        $query = "SELECT status, Name FROM seminare WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($SEM_TYPE[$row['status']]['name'] == $SEM_TYPE_MISC_NAME) {
            $type = _('Veranstaltung');
        } else {
            $type = $SEM_TYPE[$row['status']]['name'];
        }
        if (!$type) {
            $type = _('Veranstaltung');
        }
        $name = $row['Name'];
    } else if ($object_type == 'inst' || $object_type == 'fak') {
        $query = "SELECT type, Name FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $type = $INST_TYPE[$row['type']]['name'];
        if (!$type) {
            $type = _('Einrichtung');
        }
        $name = $row['Name'];
    }

    return compact('name', 'type');
}

/**
 * This function "selects" a Veranstaltung to work with it
 *
 * The following variables will bet set:
 *   $SessionSeminar                 Veranstaltung id<br>
 *   $SessSemName[0]                 Veranstaltung name<br>
 *   $SessSemName[1]                 Veranstaltung id<br>
 *   $SessSemName[2]                 Veranstaltung ort (room)<br>
 *   $SessSemName[3]                 Veranstaltung Untertitel (subtitle)<br>
 *   $SessSemName[4]                 Veranstaltung start_time (the Semester start_time)<br>
 *   $SessSemName[5]                 Veranstaltung institut_id (the home-intitute)<br>
 *   $SessSemName["art"]             Veranstaltung type in alphanumeric form<br>
 *   $SessSemName["art_num"]         Veranstaltung type in numeric form<br>
 *   $SessSemName["art_generic"]     Veranstaltung generic type in alhanumeric form (self description)<br>
 *   $SessSemName["class"]               Veranstaltung class (sem or inst, in this function always sem)<br>
 *   $SessSemName["header_line"]     the header-line to use on every page of the Veranstaltung<br>
 *
 * @param string $sem_id the id of the Veranstaltung
 *
 * @return boolean  true if successful
 *
 */
function selectSem ($sem_id)
{
    global $perm, $SEM_TYPE, $SEM_TYPE_MISC_NAME, $SessionSeminar, $SessSemName, $SemSecLevelRead, $SemSecLevelWrite, $SemUserStatus, $rechte;

    closeObject();

    $query = "SELECT Institut_id, Name, Seminar_id, Untertitel, start_time, 
                     status, Lesezugriff, Schreibzugriff, Passwort
              FROM seminare
              WHERE Seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($sem_id));
    if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $SemSecLevelRead = $row["Lesezugriff"];
        $SemSecLevelWrite = $row["Schreibzugriff"];
        $rechte = $perm->have_studip_perm("tutor", $row["Seminar_id"]);
        if( !($SemUserStatus = $perm->get_studip_perm($row["Seminar_id"])) ){
            $SemUserStatus = "nobody";
            if ($SemSecLevelRead > 0 || !get_config('ENABLE_FREE_ACCESS')) {
                throw new AccessDeniedException(_("Keine Berechtigung."));
            }
        }
        $SessionSeminar = $row["Seminar_id"];
        $SessSemName[0] = $row["Name"];
        $SessSemName[1] = $row["Seminar_id"];
        $SessSemName[3] = $row["Untertitel"];
        $SessSemName[4] = $row["start_time"];
        $SessSemName[5] = $row["Institut_id"];
        $SessSemName["art_generic"] = _("Veranstaltung");
        $SessSemName["class"] = "sem";
        $SessSemName["art_num"] = $row["status"];
        if ($SEM_TYPE[$row["status"]]["name"] == $SEM_TYPE_MISC_NAME) {
            $SessSemName["art"] = _("Veranstaltung");
        } else {
            $SessSemName["art"] = $SEM_TYPE[$row["status"]]["name"];
        }
        $SessSemName["header_line"] = getHeaderLine ($sem_id, array('name' => $row["Name"], 'type' => $SessSemName["art"]));

        $_SESSION['SessionSeminar'] =& $SessionSeminar;
        $_SESSION['SessSemName'] =& $SessSemName;

        URLHelper::addLinkParam('cid', $SessionSeminar);
        return true;
    } else {
        return false;
    }
}

/**
 * This function "selects" an Einrichtung to work with it
 *
 * Note: Stud.IP treats Einrichtungen like Veranstaltungen, yu can see this
 * especially if you look at the variable names....
 *
 * The following variables will bet set:
 *   $SessionSeminar                 Einrichtung id<br>
 *   $SessSemName[0]                 Einrichtung name<br>
 *   $SessSemName[1]                 Einrichtung id<br>
 *   $SessSemName["art"]             Einrichtung type in alphanumeric form<br>
 *   $SessSemName["art_num"]         Einrichtung type in numeric form<br>
 *   $SessSemName["art_generic"]     Einrichtung generic type in alhanumeric form (self description)<br>
 *   $SessSemName["class"]               Einrichtung class (sem or inst, in this function always inst)<br>
 *   $SessSemName["header_line"]     the header-line to use on every page of the Einrichtung<br>
 *
 * @param string $inst_id the id of the Veranstaltung
 *
 * @return boolean  true if successful
 *
 */
function selectInst ($inst_id)
{
    global $SessionSeminar, $SessSemName, $INST_TYPE, $SemUserStatus, $rechte, $perm;

    closeObject();

    if (!get_config('ENABLE_FREE_ACCESS') && !$perm->have_perm('user')) {
        throw new AccessDeniedException(_("Keine Berechtigung."));
    }

    $query = "SELECT Name, Institut_id, type,fakultaets_id,
                     Institut_id = fakultaets_id AS is_fak
              FROM Institute
              WHERE Institut_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($inst_id));
    if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if ( !($SemUserStatus = $perm->get_studip_perm($row["Institut_id"])) ) {
            $SemUserStatus = 'nobody';
        }
        $rechte = $perm->have_studip_perm("tutor", $row["Institut_id"]);
        $SessionSeminar = $row["Institut_id"];
        $SessSemName[0] = $row["Name"];
        $SessSemName[1] = $row["Institut_id"];
        $SessSemName["art_generic"] = _("Einrichtung");
        $SessSemName["art"] = $INST_TYPE[$row["type"]]["name"];
        if (!$SessSemName["art"]) {
            $SessSemName["art"] = $SessSemName["art_generic"];
        }
        $SessSemName["class"] = "inst";
        $SessSemName["is_fak"] = $row["is_fak"];
        $SessSemName["art_num"] = $row["type"];
        $SessSemName["fak"] = $row["fakultaets_id"];
        $SessSemName["header_line"] = getHeaderLine ($inst_id, array('name' => $row["Name"], 'type' => $SessSemName["art"]));

        $_SESSION['SessionSeminar'] =& $SessionSeminar;
        $_SESSION['SessSemName'] =& $SessSemName;

        URLHelper::addLinkParam('cid', $SessionSeminar);
        return true;
    } else {
        return false;
    }
}

/**
 * This function "opens" a course to work with it. Does the same
 * as selectSem() but also sets the visit date.
 *
 * @param string $sem_id the id of the course
 * 
 * @return boolean  true if successful
 */
function openSem ($sem_id)
{
    if (($result = selectSem($sem_id))) {
        object_set_visit($sem_id, "sem");
    }

    return $result;
}

/**
 * This function "opens" an institute to work with it. Does the same
 * as selectInst() but also sets the visit date.
 *
 * @param string $inst_id the id of the institute
 * 
 * @return boolean  true if successful
 */
function openInst ($inst_id)
{
    if (($result = selectInst($inst_id))) {
        object_set_visit($inst_id, "inst");
    }

    return $result;
}

/**
 * This function checks, if there is an open Veranstaltung or Einrichtung
 *
 * @global array $SessSemName
 *
 * @throws CheckObjectException
 *
 * @return void
 */
function checkObject()
{
    global $SessSemName;

    if ($SessSemName[1] == "") {
        throw new CheckObjectException(_('Sie haben kein Objekt gewählt.'));
    }
}


/**
 * This function checks, if given module is allowed in this stud-ip object
 *
 * @global array $SessSemName
 *
 * @param string $module the module to check for
 *
 * @return void
 */
function checkObjectModule($module)
{
    global $SessSemName;

    if ($SessSemName[1]) {
        $modules = new Modules();

        if (!$modules->checkLocal($module, $SessSemName[1])) {
            throw new CheckObjectException(sprintf(_('Das Inhaltselement "%s" ist für dieses Objekt leider nicht verfügbar.'), ucfirst($module)));
        }
    }
}

/**
 * This function closes a opened Veranstaltung or Einrichtung
 *
 * @global string  $SessionSeminar
 * @global array   $SessSemName
 * @global string  $SemSecLevelRead
 * @global string  $SemSecLevelWrite
 * @global string  $SemUserStatus
 * @global boolean $rechte
 * @global object  $sess
 *
 * @return void
 */
function closeObject()
{
    global $SessionSeminar, $SessSemName, $SemSecLevelRead, $SemSecLevelWrite, $SemUserStatus, $rechte, $sess;

    $SessionSeminar = null;
    $SessSemName = array();
    $SemSecLevelRead = null;
    $SemSecLevelWrite = null;
    $SemUserStatus = null;
    $rechte = false;

    URLHelper::removeLinkParam('cid');
    //$sess->unregister('raumzeitFilter');
    $_SESSION['raumzeitFilter'] = '';
}

/**
 * This function returns the last activity in the Veranstaltung
 *
 * @param string $sem_id the id of the Veranstaltung
 * @return integer  unix timestamp
 */
function lastActivity ($sem_id)
{
    $queries = array(
        // Veranstaltungs-data
        "SELECT chdate FROM seminare WHERE Seminar_id = ?",
        // Postings
        "SELECT MAX(chdate) FROM px_topics WHERE Seminar_id = ?",
        // Folder
        "SELECT MAX(chdate) FROM folder WHERE range_id = ?",
        // Dokuments
        "SELECT MAX(chdate) FROM dokumente WHERE seminar_id = ?",
        // SCM
        "SELECT MAX(chdate) FROM scm WHERE range_id = ?",
        // Dates
        "SELECT MAX(chdate) FROM termine WHERE range_id = ?",
        // News
        "SELECT MAX(date) FROM news_range LEFT JOIN news USING (news_id) WHERE range_id = ?",
        // Literature
        "SELECT MAX(chdate) FROM lit_list WHERE range_id = ?",
    );

    // Votes
    if (get_config('VOTE_ENABLE')) {
        $queries[] = "SELECT MAX(chdate) FROM vote WHERE range_id = ?";
    }

    // Wiki
    if (get_config('WIKI_ENABLE')) {
        $queries[] = "SELECT MAX(chdate) FROM wiki WHERE range_id = ?";
    }

    $timestamp = false;
    foreach ($queries as $query) {
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($sem_id));
        $temp = $statement->fetchColumn();

        if (!$timestamp || $temp > $timestamp) {
            $timestamp = $temp;
        }
    }

    //correct the timestamp, if date in the future (news can be in the future!)
    if ($timestamp > time()) {
        $timestamp = time();
    }

    return $timestamp;
}


/**
 * This function determines the type of the passed id
 *
 * The function recognizes the following types at the moment:
 * Einrichtungen, Veranstaltungen, Statusgruppen and Fakultaeten
 *
 * @staticvar array $object_type_cache
 *
 * @param string $id         the id of the object
 * @param array  $check_only an array to narrow the search, may contain
 *                            'sem', 'fak', 'group' or 'dokument' (optional)
 *
 * @return string  return "inst" (Einrichtung), "sem" (Veranstaltung),
 *                 "fak" (Fakultaeten), "group" (Statusgruppe), "dokument" (Dateien)
 *
 */
function get_object_type($id, $check_only = array())
{
    static $object_type_cache;

    // Nothing to check
    if (!$id) {
        return false;
    }

    // Id is global
    if ($id == 'studip') {
        return 'global';
    }

    // Read from cache if available
    if ($object_type_cache[$id]) {
        return $object_type_cache[$id];
    }

    // Tests for specific types
    $tests = array(
        'sem'        => "SELECT 1 FROM seminare WHERE Seminar_id = ?",
        'date'       => "SELECT 1 FROM termine WHERE termin_id = ?",
        'user'       => "SELECT 1 FROM auth_user_md5 WHERE user_id = ?",
        'group'      => "SELECT 1 FROM statusgruppen WHERE statusgruppe_id = ?",
        'dokument'   => "SELECT 1 FROM dokumente WHERE dokument_id = ?",
        'range_tree' => "SELECT 1 FROM range_tree WHERE item_id = ?",
    );

    // Test for every type if no specific types are provided
    $check_all = !count($check_only);

    // Loop through tests
    foreach ($tests as $key => $query) {
        if ($check_all || in_array($key, $check_only)) {
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($id));

            if ($statement->fetchColumn()) {
                return $object_type_cache[$id] = $key;
            }
        }
    }

    // Institute or faculty?
    if ($check_all || in_array('inst', $check_only)) {
        $query = "SELECT Institut_id = fakultaets_id FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));

        $is_fak = $statement->fetchColumn();
        if ($is_fak !== false) {
            return $object_type_cache[$id] = ($is_fak ? 'fak' : 'inst');
        }
    }

    // None of the above
    return false;
}

/**
 * The function calculate one of the group colors unique for the Semester of the Veranstaltung
 *
 * It calculate a unique color number to create the initial entry for a new user in a Veranstaltung.
 * It will create a unique number for every Semester and will start over, if the max. number
 * (7) is reached.
 *
 * @param integer $sem_start_time the timestamp of the start time from the Semester
 * @param string  $user_id        this field is not necessary anymore and remains
 *                                for compatibilty reasons only
 * 
 * @return integer  the color number
 *
 */
function select_group($sem_start_time, $user_id='')
{
    //Farben Algorhytmus, erzeugt eindeutige Farbe fuer jedes Semester. Funktioniert ab 2001 die naechsten 1000 Jahre.....
    $year_of_millenium=date ("Y", $sem_start_time) % 1000;
    $index=$year_of_millenium * 2;
    if (date ("n", $sem_start_time) > 6)
        $index++;
    $group=($index % 7) + 1;

    return $group;
}

/**
 * The function shortens a string, but it uses the first 2/3 and the last 1/3
 *
 * The parts will be divided by a "[...]". The functions is to use like php's
 * substr function.
 *
 * @param string  $what  the original string
 * @param integer $start start pos, 0 is the first pos
 * @param integer $end   end pos
 *
 * @return string
 *
 *
 */
function my_substr($what, $start, $end)
{
    $length=$end-$start;
    $what_length = studip_strlen($what);
    // adding 5 because: strlen("[...]") == 5
    if ($what_length > $length + 5) {
        $what=studip_substr($what, $start, round(($length / 3) * 2))."[...]".studip_substr($what, $what_length - round($length / 3), $what_length);
    }
    return $what;
}


/**
 * The function determines, if the current user have write perm in a Veranstaltung or Einrichtung
 *
 * It uses the Variables $SemSecLevelWrite, $SemUserStatus and $rechte, which are created in the
 * modul check_sem_entry.inc.php and $perm from PHP-lib
 *
 * @global string  $SemSecLevelWrite
 * @global string  $SemUserStatus
 * @global array   $perm
 * @global boolean $rechte
 *
 * @return string  the error msg. If no msg is returned, the user has write permission
 *
 */
function have_sem_write_perm ()
{
    global $SemSecLevelWrite, $SemUserStatus, $perm, $rechte;

    $error_msg="";
    if (!($perm->have_perm("root"))) {
        if (!($rechte || ($SemUserStatus=="autor") || ($SemUserStatus=="tutor") || ($SemUserStatus=="dozent"))) {
            //Auch eigentlich uberfluessig...
            //$error_msg = "<br><b>Sie haben nicht die Berechtigung in dieser Veranstaltung zu schreiben!</b><br><br>";
            switch ($SemSecLevelWrite) {
                case 2 :
                    $error_msg=$error_msg."error§" . _("In dieser Veranstaltung ist ein Passwort f&uuml;r den Schreibzugriff n&ouml;tig.") . "<br>" . sprintf(_("Zur %sPassworteingabe%s"), "<a href=\"sem_verify.php\">", "</a>") . "§";
                    break;
                case 1 :
                    if ($perm->have_perm("autor"))
                        $error_msg=$error_msg."info§" . _("Sie müssen sich erneut für diese Veranstaltung anmelden, um Dateien hochzuladen und Beitr&auml;ge im Forum schreiben zu können!") . "<br>" . sprintf(_("Hier kommen Sie zur %sFreischaltung%s der Veranstaltung."), "<a href=\"sem_verify.php\">", "</a>") . "§";
                    elseif ($perm->have_perm("user"))
                        $error_msg=$error_msg."info§" . _("Bitte folgen Sie den Anweisungen in der Registrierungsmail.") . "§";
                    else
                        $error_msg=$error_msg."info§" . _("Bitte melden Sie sich an.") . "<br>" . sprintf(_("Hier geht es zur %sRegistrierung%s wenn Sie noch keinen Account im System haben."), "<a href=\"register1.php\">", "</a>") . "§";
                    break;
                default :
                    //Wenn Schreiben fuer Nobody jemals wieder komplett verboten werden soll, diesen Teil bitte wieder einkommentieren (man wei&szlig; ja nie...)
                    //$error_msg=$error_msg."Bitte melden Sie sich an.<br><br><a href=\"register1.php\"><b>Registrierung</b></a> wenn Sie noch keinen Account im System haben.<br><a href=\"index.php?again=yes\"><b>Login</b></a> f&uuml;r registrierte Benutzer.<br><br>";
                    break;
                }
            $error_msg=$error_msg."info§" . _("Dieser Fehler kann auch auftreten, wenn Sie zu lange inaktiv gewesen sind.") . "§";
            }
        }
    return $error_msg;
}

/**
 * The function gives the global perm of an user
 *
 * It ist recommended to use $auth->auth["perm"] for this query,
 * but the function is useful, if you want to query an user_id from another user
 * (which ist not the current user)
 *
 * @deprecated   use $GLOBALS['perm']->get_perm($user_id)
 *
 * @param string $user_id if omitted, current user_id is used
 *
 * @return string  the perm level or an error msg
 *
 */
function get_global_perm($user_id = "")
{
    global $perm;
    $status = $perm->get_perm($user_id);
    return (!$status) ? _("Fehler!") : $status;
}

/**
 * Returns permission for given range_id and user_id
 *
 * Function works for Veranstaltungen, Einrichtungen, Fakultaeten.
 * admins get status 'admin' if range_id is a seminar
 *
 * @deprecated  use $GLOBALS['perm']->get_studip_perm($range_id, $user_id)
 *
 * @param string $range_id an id a Veranstaltung, Einrichtung or Fakultaet
 * @param string $user_id  if omitted,current user_id is used
 * 
 * @return string  the perm level
 */
function get_perm($range_id, $user_id = "")
{
    global $perm;
    $status = $perm->get_studip_perm($range_id,$user_id);
    return (!$status) ? _("Fehler!") : $status;
}


/**
 * Retrieves the fullname for a given user_id
 *
 * @param string $user_id   if omitted, current user_id is used
 * @param string $format    output format
 * @param bool   $htmlready if true, htmlReady is applied to all output-strings
 *
 * @return string
 */
function get_fullname($user_id = "", $format = "full" , $htmlready = false)
{
    static $cache;
    global $user, $_fullname_sql;

    if (!$user_id) {
        $user_id = $user->id;
    }

    $hash = md5($user_id . $format);
    if (!isset($cache[$hash])) {
        $query = "SELECT {$_fullname_sql[$format]}
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $cache[$hash] = $statement->fetchColumn() ?: _('unbekannt');
    }

    return $htmlready ? htmlReady($cache[$hash]) : $cache[$hash];
}

/**
 * Retrieves the fullname for a given username
 *
 * @param string $uname     if omitted, current user_id is used
 * @param string $format    output format
 * @param bool   $htmlready if true, htmlReady is applied to all output-strings
 *
 * @return       string
 */
function get_fullname_from_uname($uname = "", $format = "full", $htmlready = false)
{
    static $cache;
    global $auth, $_fullname_sql;

    if (!$uname) {
        $uname = $auth->auth['uname'];
    }

    $hash = md5($uname . $format);
    if (!isset($cache[$hash])) {
        $query = "SELECT {$_fullname_sql[$format]}
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  WHERE username = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($uname));
        $cache[$hash] = $statement->fetchColumn() ?: _('unbekannt');
    }

    return $htmlready ? htmlReady($cache[$hash]) : $cache[$hash];
}

/**
 * Retrieves the Vorname for a given user_id
 *
 * @param string $user_id if omitted, current user_id is used
 *
 * @return string
 */
function get_vorname($user_id = "")
{
    global $user;

    if (!$user_id) {
        $user_id = $user->id;
    }

    $query = "SELECT Vorname FROM auth_user_md5 WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $author = $statement->fetchColumn() ?: _('unbekannt');

    return $author;
}

/**
 * Retrieves the Nachname for a given user_id
 *
 * @param string $user_id if omitted, current user_id is used
 *
 * @return string
 */
function get_nachname($user_id = "")
{
    global $user;

    if (!$user_id) {
        $user_id = $user->id;
    }

    $query = "SELECT Nachname FROM auth_user_md5 WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $author = $statement->fetchColumn() ?: _('unbekannt');

    return $author;
}

/**
 * Retrieves the username for a given user_id
 *
 * @global object $auth
 * @staticvar array $cache
 *
 * @param string $user_id if omitted, current username will be returned
 * 
 * @return string
 *
 */
function get_username($user_id = "")
{
    static $cache = array();
    global $auth;

    if (!$user_id || $user_id == $auth->auth['uid']) {
        return $auth->auth['uname'];
    }

    if (!isset($cache[$user_id])) {
        $query = "SELECT username FROM auth_user_md5 WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $cache[$user_id] = $statement->fetchColumn();
    }

    return $cache[$user_id];
}

/**
 * Retrieves the userid for a given username
 *
 * uses global $online array if user is online
 *
 * @global object $auth
 * @staticvar array $cache
 *
 * @param string $username if omitted, current user_id will be returned
 *
 * @return string
 */
function get_userid($username = "")
{
    static $cache = array();
    global $auth;

    if (!$username || $username == $auth->auth['uname']) {
        return $auth->auth['uid'];
    }

    // Read id from database if no cached version is available
    if (!isset($cache[$username])) {
        $query = "SELECT user_id FROM auth_user_md5 WHERE username = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($username));
        $cache[$username] = $statement->fetchColumn();
    }

    return $cache[$username];
}


/**
 * This function tracks user acces to several Data (only dokuments by now, to be extended)
 *
 * @param string $id          the id of the object to track
 * @param string $object_type the object type (optional)
 *
 * @return void
 */
function TrackAccess ($id, $object_type = null)
{
    if (!$object_type){
        $object_type = get_object_type($id, array('dokument'));
    }
    switch ($object_type) {         // what kind ob object shall we track
        case "dokument":                // the object is a dokument, so downloads will be increased
            $query = "UPDATE dokumente SET downloads = downloads + 1 WHERE dokument_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($id));
            break;
    }
}

/**
 * Return an array containing the nodes of the sem-tree-path
 *
 * @param string $seminar_id the seminar to get the path for
 * @param int    $depth      the depth
 * @param string $delimeter  a string to separate the path parts
 *
 * @return array
 */
function get_sem_tree_path($seminar_id, $depth = false, $delimeter = ">")
{
    $the_tree = TreeAbstract::GetInstance("StudipSemTree");
    $view = new DbView();
    $ret = null;
    $view->params[0] = $seminar_id;
    $rs = $view->get_query("view:SEMINAR_SEM_TREE_GET_IDS");
    while ($rs->next_record()){
        $ret[$rs->f('sem_tree_id')] = $the_tree->getShortPath($rs->f('sem_tree_id'),$depth,$delimeter);
    }
    return $ret;
}

/**
 * Return an array containing the nodes of the range-tree-path
 *
 * @param string $institut_id the institute to get the path for
 * @param int    $depth       the depth
 * @param string $delimeter   a string to separate the path parts
 *
 * @return array
 */
function get_range_tree_path($institut_id, $depth = false, $delimeter = ">")
{
    $the_tree = TreeAbstract::GetInstance("StudipRangeTree");
    $view = new DbView();
    $ret = null;
    $view->params[0] = $institut_id;
    $rs = $view->get_query("view:TREE_ITEMS_OBJECT");
    while ($rs->next_record()){
        $ret[$rs->f('item_id')] = $the_tree->getShortPath($rs->f('item_id'),$depth,$delimeter);
    }
    return $ret;
}


/**
 * check_and_set_date
 *
 * Checks if given date is valid and sets field in array accordingly.
 * (E.g. $admin_admission_data['admission_enddate'])
 *
 * @param mixed $tag    day or placeholder for day
 * @param mixed $monat  month or placeholder for month
 * @param mixed $jahr   year or placeholder for year
 * @param mixed $stunde hours or placeholder for hours
 * @param mixed $minute minutes or placeholder for minutes
 * @param array &$arr   Reference to array to update. If NULL, only check is performed
 * @param mixed $field  Name of field in array to be set
 *
 * @return bool  true if date was valid, false else
 */
function check_and_set_date($tag, $monat, $jahr, $stunde, $minute, &$arr, $field)
{

    $check=TRUE; // everything ok?
    if (($jahr>0) && ($jahr<100))
        $jahr=$jahr+2000;

    if ($monat == _("mm")) $monat=0;
    if ($tag == _("tt")) $tag=0;
    if ($jahr == _("jjjj")) $jahr=0;
    //if ($stunde == _("hh")) $stunde=0;
    if ($minute == _("mm")) $minute=0;

    if (($monat) && ($tag) && ($jahr)) {
        if ($stunde==_("hh")) {
            $check=FALSE;
        }

        if ((!checkdate((int)$monat, (int)$tag, (int)$jahr) && ((int)$monat) && ((int)$tag) && ((int)$jahr))) {
            $check=FALSE;
        }

        if (($stunde > 24) || ($minute > 59)
            || ($stunde == 24 && $minute > 0) ) {
            $check=FALSE;
        }

        if ($stunde == 24) {
            $stunde = 23;
            $minute = 59;
        }

        if ($arr) {
            if ($check) {
                $arr[$field] = mktime((int)$stunde,(int)$minute, 0,$monat,$tag,$jahr);
            } else {
                $arr[$field] = -1;
            }
        }
    }
    return $check;
}

/**
 * writes an entry into the studip configuration table
 *
 * @deprecated
 * @param string $key the key for the config entry
 * @param string $val the value that should be set
 * @param array  $arr an array with key=>value to write into config
 *
 * @return bool  true if date was valid, else false
 */
function write_config ($key, $val, $arr = null)
{
    if (is_null($arr)) {
        $arr[$key] = $val;
    }
    $config = Config::get();
    if (is_array($arr)) {
        foreach ($arr as $key => $val) {
            if (isset($config->$key)) {
                $config->store($key, $val);
            } else {
                $config->create($key, array('value' => $val, 'type' => 'string'));
            }
            $GLOBALS[$key] = $config->$key;
        }
    }

}

/**
 * gets an entry from the studip configuration table
 *
 * @param string $key the key for the config entry
 *
 * @return string  the value
 *
 */
function get_config($key)
{
    return Config::get()->$key;
}

/**
 * get the lecturers and their order-positions in the passed seminar
 *
 * folgende Funktion ist nur notwendig, wenn die zu kopierende Veranstaltung nicht
 * vom Dozenten selbst, sondern vom Admin oder vom root kopiert wird (sonst wird
 * das Dozentenfeld leer gelassen, was ja keiner will...)
 *
 * @param string $seminar_id the seminar to get the lecturers from
 *
 * @return array  an array containing user_ids as key and positions as value
 */
function get_seminar_dozent($seminar_id)
{
    $query = "SELECT user_id, position
              FROM seminar_user
              WHERE Seminar_id = ? AND status = 'dozent'
              ORDER BY position";
    $statement = DBManager::get()->prepare($query);
    $result = $statement->execute(array($seminar_id));

    if (!$result) {
        echo 'Fehler bei DB-Abfrage in get_seminar_user!';
        return 0;
    }

    $dozenten = $statement->fetchGrouped(PDO::FETCH_COLUMN);

    if (empty($dozenten)) {
        echo 'Fehler in get_seminar_dozent: Kein Dozent gefunden';
        return 0;
    }

    return $dozenten;
}

/**
 * reset the order-positions for the lecturers in the passed seminar,
 * starting at the passed position
 *
 * @param string $s_id     the seminar to work on
 * @param int    $position the position to start with
 *
 * @return void
 */
function re_sort_dozenten($s_id, $position)
{
    $query = "UPDATE seminar_user
              SET position = position - 1
              WHERE Seminar_id = ? AND status = 'dozent' AND position > ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($s_id, $position));
}

/**
 * reset the order-positions for the tutors in the passed seminar,
 * starting at the passed position
 *
 * @param string $s_id     the seminar to work on
 * @param int    $position the position to start with
 *
 * @return void
 */
function re_sort_tutoren($s_id, $position)
{
    $query = "UPDATE seminar_user
              SET position = position - 1
              WHERE Seminar_id = ? AND status = 'tutor' AND position > ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($s_id, $position));
}

/**
 * return the highest position-number increased by one for the
 * passed user-group in the passed seminar
 *
 * @param string $status     can be on of 'tutor', 'dozent', ...
 * @param string $seminar_id the seminar to work on
 *
 * @return int  the next available position
 */
function get_next_position($status, $seminar_id)
{
    $query = "SELECT MAX(position) + 1
              FROM seminar_user
              WHERE Seminar_id = ? AND status = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id, $status));

   return $statement->fetchColumn() ?: 0;
}

/**
 * get the tutors and their order-positions in the passed seminar
 *
 * @param string $seminar_id the seminar to get the tutors from
 *
 * @return array  an array containing user_ids as key and positions as value
 */
function get_seminar_tutor($seminar_id)
{
    $query = "SELECT user_id, position
              FROM seminar_user
              WHERE Seminar_id = ? AND status = 'tutor'
              ORDER BY position";
    $statement = DBManager::get()->prepare($query);
    $result = $statement->execute(array($seminar_id));
    
    if (!$result) {
        echo 'Fehler bei DB-Abfrage in get_seminar_user!';
        return 0;
    }

    $tutoren = $statement->fetchGrouped(PDO::FETCH_COLUMN);

    return empty($tutoren) ? null : $tutoren;
}

/**
 * return all sem_tree-entries for the passed seminar
 *
 * @param string $seminar_id the seminar
 * 
 * @return array  a list of sem_tree_id's
 */
function get_seminar_sem_tree_entries($seminar_id)
{
    $view = new DbView();
    $ret = null;
    $view->params[0] = $seminar_id;
    $rs = $view->get_query("view:SEMINAR_SEM_TREE_GET_IDS");
    while ($rs->next_record()){
        $ret[] = $rs->f('sem_tree_id');
    }
    return $ret;
}

/**
 * return an array of all seminars for the passed user, containing
 * the name, id, makedate and sem-number.
 *
 * @param string $user_id the user's id
 *
 * @return array the user seminars as an array of four fields
 */
function get_seminars_user($user_id)
{
    $query = "SELECT Seminar_id, Name, sem.mkdate, VeranstaltungsNummer AS va_nummer
              FROM seminare AS sem
              JOIN seminar_user USING (Seminar_id)
              WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $seminars = $statement->fetchAll(PDO::FETCH_ASSOC);

    return $seminars;
}

/**
 * converts a string to a float, depending on the locale
 *
 * @param string $str the string to convert to float
 *
 * @return float the string casted to float
 */
function StringToFloat($str)
{
    $str = substr((string)$str,0,13);
    $locale = localeconv();
    $from = ($locale["thousands_sep"] ? $locale["thousands_sep"] : ',');
    $to = ($locale["decimal_point"] ? $locale["decimal_point"] : '.');
    if(strstr($str, $from)){
        $conv_str = str_replace($from, $to, $str);
        $my_float = (float)$conv_str;
        if ($conv_str === (string)$my_float) return $my_float;
    }
    return (float)$str;
}

/**
 * check which perms the currently logged in user had in the
 * passed archived seminar
 *
 * @global array $perm
 * @global object $auth
 * @staticvar array $archiv_perms
 *
 * @param string $seminar_id the seminar in the archive
 *
 * @return string the perm the user had
 */
function archiv_check_perm($seminar_id)
{
    static $archiv_perms;
    global $perm, $user;

    $u_id = $user->id;

    // root darf sowieso ueberall dran
    if ($perm->have_perm('root')) {
        return 'admin';
    }

    if (!is_array($archiv_perms)){
        $query = "SELECT seminar_id, status FROM archiv_user WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($u_id));
        $archiv_perms = $statement->fetchGrouped(PDO::FETCH_COLUMN);

        if ($perm->have_perm("admin")){
            $query = "SELECT archiv.seminar_id, 'admin'
                      FROM user_inst
                      INNER JOIN archiv ON (heimat_inst_id = institut_id)
                      WHERE user_inst.user_id = ? AND user_inst.inst_perms = 'admin'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($u_id));
            $temp_perms = $statement->fetchGrouped(PDO::FETCH_COLUMN);

            $archiv_perms = array_merge($archiv_perms, $temp_perms);
        }
        if ($perm->is_fak_admin()){
            $query = "SELECT archiv.seminar_id, 'admin'
                      FROM user_inst
                      INNER JOIN Institute ON (user_inst.institut_id = Institute.fakultaets_id)
                      INNER JOIN archiv ON (archiv.heimat_inst_id = Institute.institut_id)
                      WHERE user_inst.user_id = ? AND user_inst.inst_perms = 'admin'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($u_id));
            $temp_perms = $statement->fetchGrouped(PDO::FETCH_COLUMN);

            $archiv_perms = array_merge($archiv_perms, $temp_perms);
        }
    }
    return $archiv_perms[$seminar_id];
}

/**
 * retrieve a list of all online users
 *
 * @global object $user
 * @global array  $_fullname_sql
 *
 * @param int    $active_time filter: the time in minutes until last life-sign
 * @param string $name_format format the fullname shall have
 *
 * @return array
 */
function get_users_online($active_time = 5, $name_format = 'full_rev')
{
    global $user, $_fullname_sql;

    if (!isset($_fullname_sql[$name_format])) {
        reset($_fullname_sql);
        $name_format = key($_fullname_sql);
    }

    $query = "SELECT a.username, {$_fullname_sql[$name_format]} AS name,
                     UNIX_TIMESTAMP() - UNIX_TIMESTAMP(changed) AS last_action,
                     a.user_id, contact_id AS is_buddy, " . get_vis_query('a', 'online') . " AS is_visible
              FROM " . PHPLIB_USERDATA_TABLE . "
              LEFT JOIN auth_user_md5 a ON (a.user_id = sid)
              LEFT JOIN user_info USING (user_id)
              LEFT JOIN user_visibility USING (user_id)
              LEFT JOIN contact ON (owner_id = ? AND contact.user_id = a.user_id AND buddy = 1)
              WHERE changed > ? AND sid NOT IN ('nobody', ?)
              ORDER BY a.Nachname ASC, a.Vorname ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $user->id,
        date('YmdHis', time() - $active_time * 60),
        $user->id,
    ));
    $online = $statement->fetchGrouped();

    return $online;
}

/**
 * get the number of currently online users
 *
 * @param int $active_time filter: the time in minutes until last life-sign
 * 
 * @return int
 */
function get_users_online_count($active_time = 5)
{
    $query = "SELECT COUNT(*) FROM " . PHPLIB_USERDATA_TABLE . "
              WHERE changed > ? AND sid NOT IN ('nobody', ?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        date('YmdHis', time() - $active_time * 60),
        $GLOBALS['user']->id,
    ));
    return $statement->fetchColumn();
}

/**
 * return a studip-ticket
 *
 * @return string a unique id referring to a newly created ticket
 */
function get_ticket()
{
    return Seminar_Session::get_ticket();
}

/**
 * check if the passed ticket is valid
 *
 * @param string $studipticket the ticket-id to check
 * 
 * @return bool
 */
function check_ticket($studipticket)
{
    return Seminar_Session::check_ticket($studipticket);
}

/**
 * searches
 *
 * @global array $perm
 * @global object $user
 * @global array $_fullname_sql
 *
 * @param string $search_str  optional search-string
 * @param string $search_user optional user to search for
 * @param bool   $show_sem    if true, the seminar is added to the result
 *
 * @return array
 */
function search_range($search_str = false, $search_user = false, $show_sem = true)
{
    global $perm, $user, $_fullname_sql;

    // Helper function that obtains the correct name for an entity taking
    // in account whether the semesters should be displayed or not
    $formatName = function ($row) use ($show_sem) {
        $name = $row['Name'];
        if ($show_sem) {
            $name = sprintf('%s (%s%s)',
                            $name,
                            $row['startsem'],
                            $row['startsem'] != $row['endsem'] ? ' - ' . $row['endsem'] : '');
        }
        return $name;
    };

    $search_result = array();
    $show_sem_sql1 = ",s.start_time,sd1.name AS startsem,IF(s.duration_time=-1, '"._("unbegrenzt")."', sd2.name) AS endsem ";
    $show_sem_sql2 = "LEFT JOIN semester_data sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
                      LEFT JOIN semester_data sd2 ON (start_time + duration_time BETWEEN sd2.beginn AND sd2.ende)";


    if ($search_str && $perm->have_perm('root')) {
        if ($search_user) {
            $query = "SELECT user_id, CONCAT({$_fullname_sql['full']}, ' (', username, ')') AS name
                      FROM auth_user_md5 AS a
                      LEFT JOIN user_info USING (user_id)
                      WHERE CONCAT(Vorname, ' ', Nachname, ' ', username) LIKE CONCAT('%', ?, '%')
                      ORDER BY Nachname, Vorname";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $search_result[$row['user_id']] = array(
                    'type' => 'user',
                    'name' => $row['name'],
                );
            }
        }

        $_hidden = _('(versteckt)');
        $query = "SELECT Seminar_id, IF(s.visible = 0, CONCAT(s.Name, ' {$_hidden}'), s.Name) AS Name %s
                  FROM seminare AS s %s
                  WHERE s.Name LIKE CONCAT('%%', ?, '%%')
                  ORDER BY start_time DESC, Name";
        $query = $show_sem
               ? sprintf($query, $show_sem_sql1, $show_sem_sql2)
               : sprintf($query, '', '');
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($search_str));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Seminar_id']] = array(
                'type'      => 'sem',
                'name'      => $formatName($row),
                'starttime' => $row['start_time'],
                'startsem'  => $row['startsem'],
            );
        }

        $query = "SELECT Institut_id, Name, IF(Institut_id = fakultaets_id, 'fak', 'inst') AS type
                  FROM Institute
                  WHERE Name LIKE CONCAT('%', ?, '%')
                  ORDER BY Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($search_str));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Institut_id']] = array(
                'type' => $row['type'],
                'name' => $row['Name'],
            );
        }
    } elseif ($search_str && $perm->have_perm('admin')) {
        $_hidden = _('(versteckt)');
        $query = "SELECT s.Seminar_id, IF(s.visible = 0, CONCAT(s.Name, ' {$_hidden}), s.Name) AS Name %s
                  FROM user_inst AS a
                  LEFT JOIN seminare AS s USING (Institut_id) %s
                  WHERE a.user_id = ? AND a.inst_perms = 'admin' AND s.Name LIKE CONCAT('%%', ?, '%%')
                  ORDER BY start_time";
        $query = $show_sem
               ? sprintf($query, $show_sem_sql1, $show_sem_sql2)
               : sprintf($query, '', '');
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id, $search_str));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Seminar_id']] = array(
                'type'      => 'sem',
                'name'      => $formatName($row),
                'starttime' => $row['start_time'],
                'startsem'  => $row['startsem'],
            );
        }

        $query = "SELECT b.Institut_id, b.Name
                  FROM user_inst AS a
                  LEFT JOIN Institute AS b USING (Institut_id)
                  WHERE a.user_id = ? AND a.inst_perms = 'admin'
                    AND a.institut_id != b.fakultaets_id AND b.Name LIKE CONCAT('%', ?, '%')
                  ORDER BY Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id, $search_str));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Institut_id']] = array(
                'type' => 'inst',
                'name' => $row['Name'],
            );
        }
        if ($perm->is_fak_admin()) {
            $_hidden = _('(versteckt)');
            $query = "SELECT s.Seminar_id, IF(s.visible = 0, CONCAT(s.Name, ' {$_hidden}), s.Name) AS Name %s
                      FROM user_inst AS a
                      LEFT JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                      LEFT JOIN Institute AS c ON (c.fakultaets_id = b.Institut_id AND c.fakultaets_id = c.Institut_id)
                      LEFT JOIN seminare AS s ON (s.Institut_id = c.Institut_id) %s
                      WHERE a.user_id = ? AND a.inst_perms = 'admin'
                        AND NOT ISNULL(b.Institut_id) AND s.Name LIKE CONCAT('%%', ?, '%%')
                      ORDER BY start_time DESC, Name";
            $query = $show_sem
                   ? sprintf($query, $show_sem_sql1, $show_sem_sql2)
                   : sprintf($query, '', '');
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $search_result[$row['Seminar_id']] = array(
                    'type'      => 'sem',
                    'name'      => $formatName($row),
                    'starttime' => $row['start_time'],
                    'startsem'  => $row['startsem'],
                );
            }

            $query = "SELECT c.Institut_id, c.Name
                      FROM user_inst AS a
                      LEFT JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                      LEFT JOIN Institute AS c ON (c.fakultaets_id = b.institut_id AND c.fakultaets_id != c.institut_id)
                      WHERE a.user_id = ? AND a.inst_perms = 'admin'
                        AND NOT ISNULL(b.Institut_id) AND c.Name LIKE CONCAT('%', ?, '%')
                      ORDER BY Name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $search_result[$row['Institut_id']] = array(
                    'type' => 'inst',
                    'name' => $row['Name'],
                );
            }

            $query = "SELECT b.Institut_id, b.Name
                      FROM user_inst AS a
                      LEFT JOIN Institute AS b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id)
                      WHERE a.user_id = = AND a.inst_perms = 'admin'
                        AND NOT ISNULL(b.Institut_id) AND b.Name LIKE CONCAT('%', ?, '%')
                      ORDER BY Name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $search_str));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $search_result[$row['Institut_id']] = array(
                    'type' => 'inst',
                    'name' => $row['Name'],
                );
            }
        }
    } elseif ($perm->have_perm('tutor') || $perm->have_perm('autor')) {
        // autors my also have evaluations and news in studygroups with proper rights
        $_hidden = _('(versteckt)');
        $query = "SELECT s.Seminar_id, IF(s.visible = 0, CONCAT(s.Name, ' {$_hidden}'), s.Name) AS Name %s
                  FROM seminar_user AS a
                  LEFT JOIN seminare AS s USING (Seminar_id) %s
                  WHERE a.user_id = ? AND a.status IN ('tutor', 'dozent')
                  ORDER BY start_time DESC, Name";
        $query = $show_sem
               ? sprintf($query, $show_sem_sql1, $show_sem_sql2)
               : sprintf($query, '', '');
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Seminar_id']] = array(
                'type'      => 'sem',
                'name'      => $formatName($row),
                'starttime' => $row['start_time'],
                'startsem'  => $row['startsem'],
            );
        }

        $query = "SELECT Institut_id, b.Name AS name,
                         IF (Institut_id = fakultaets_id, 'fak', 'inst') AS type
                  FROM user_inst AS a
                  LEFT JOIN Institute AS b USING (Institut_id)
                  WHERE a.user_id = ? AND a.inst_perms IN ('dozent','tutor')
                  ORDER BY Name";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user->id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Institut_id']] = array(
                'name' => $row['Name'],
                'type' => $row['type'],
            );
        }
    }

    if (get_config('DEPUTIES_ENABLE')) {
        $_hidden = _('(versteckt)');
        $_deputy = _('Vertretung');
        $query = "SELECT s.Seminar_id,
                         CONCAT(IF(s.visible = 0, CONCAT(s.Name, ' {$_hidden}'), s.Name), ' [{$_deputy}]') AS Name %s
                  FROM seminare AS s
                  JOIN deputies AS d ON (s.Seminar_id = d.range_id) %s
                  WHERE s.Name LIKE CONCAT('%%', ?, '%%') AND d.user_id = ?
                  ORDER BY s.start_time DESC, Name";
        $query = $show_sem
               ? sprintf($query, $show_sem_sql1, $show_sem_sql2)
               : sprintf($query, '', '');
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($search_str, $user->id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $search_result[$row['Seminar_id']] = array(
                'type'      => 'sem',
                'name'      => $formatName($row),
                'starttime' => $row['start_time'],
                'startsem'  => $row['startsem'],
            );
        }
        if (isDeputyEditAboutActivated()) {
            $query = "SELECT a.user_id, a.username, 'user' AS type, 
                             CONCAT({$_fullname_sql['full']}, ' (', username, ')') AS name
                      FROM auth_user_md5 AS a
                      JOIN user_info USING (user_id)
                      JOIN deputies AS d ON (a.user_id = d.range_id)
                      WHERE d.user_id = ?
                      ORDER BY name ASC";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $user->id
            ));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $search_result[$row['user_id']] = $row;
            }
        }
    }

    return $search_result ?: null;
}

/**
 * format_help_url($keyword)
 * returns URL for given help keyword
 *
 * @param string $keyword the help-keyword
 *
 * @return string the help-url
 */
function format_help_url($keyword)
{
    global $auth, $_language;

    $helppage=$keyword;

    // $loc is only set if special help view for installation is known
    //
    $loc="";
    $locationid=get_config("EXTERNAL_HELP_LOCATIONID");
    if ($locationid && $locationid!="default") {
    $loc = $locationid."/";
    }

    // all help urls need short language tag (de, en)
    //
    $lang="de";
    if ($_language) {
        list($lang) = explode('_', $_language);
    }

    // determine Stud.IP version as of MAJOR.MINOR
    // from SOFTWARE_VERSION. That variable MUST match pattern MAJOR.MINOR.*
    //
    $v=array();
    preg_match("/^([0-9]+\.[0-9]+)/", $GLOBALS['SOFTWARE_VERSION'], $v);
    $version=$v[0];

    $help_query="http://docs.studip.de/help/".$version."/".$lang."/".$loc.$helppage;
    return $help_query;
}

/**
 * Remove slashes if magic quotes are enabled
 *
 * @param mixed $mixed string or array to strip slashes from
 *
 * @return mixed cleaned string or array
 */
function remove_magic_quotes($mixed)
{
    if (get_magic_quotes_gpc()) {
        if (is_array($mixed)) {
            foreach ($mixed as $k => $v) {
                $mixed[$k] = remove_magic_quotes($v);
            }
        }
        else {
            $mixed = stripslashes($mixed);
        }
    }
    return $mixed;
}

/**
 * Unset all variables set by register_globals (if enabled).
 * Note: The session variables 'auth' and 'SessSemName' are preserved.
 *
 * @return void
 */
function unregister_globals ()
{
    if (!ini_get('register_globals')) {
        return;
    }

    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
        die('GLOBALS overwrite attempt detected');
    }

    $noUnset = array('GLOBALS', '_GET', '_POST', '_COOKIE',
                     '_REQUEST', '_SERVER', '_ENV', '_FILES',
                     'auth', 'SessionSeminar', 'SessSemName');
    $vars = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES);

    if (isset($_SESSION)) {
        $vars = array_merge($vars, $_SESSION);
    }

    foreach ($vars as $var => $value) {
        if (!in_array($var, $noUnset) && isset($GLOBALS[$var])) {
            unset($GLOBALS[$var]);
        }
    }
}

/**
  * Extracts an excerpt from the 'text' surrounding the 'phrase' with a number
  * of characters on each side determined by 'radius'. If the phrase isn't
  * found, null is returned.
  * Ex: text_excerpt("hello my world", "my", 3) => "...lo my wo..."
  *
  * @param string  $text           the text to excerpt
  * @param string  $phrase         the search phrase
  * @param integer $radius         the radius around the phrase
  * @param integer $length         the maximum length of the excerpt string
  * @param string  $excerpt_string the excerpt string
  *
  * @return string
*/
function text_excerpt($text, $phrase, $radius = 100, $length = 200,
                      $excerpt_string = '...')
{
  if ($text == '' || $phrase == '') {
    return '';
  }

  $found_pos = strpos(strtolower($text), strtolower($phrase));

  if ($found_pos === FALSE) {
    $start_pos = 0;
  }
  else {
    $start_pos = max($found_pos - $radius, 0);
  }

  $end_pos = $start_pos + $length - strlen($excerpt_string);
  if ($start_pos !== 0) {
    $end_pos -= strlen($excerpt_string);
  }

  $end_pos = min($end_pos, strlen($text));

  $prefix = $start_pos > 0 ? $excerpt_string : '';
  $postfix = $end_pos < strlen($text) ? $excerpt_string : '';

  return $prefix.substr($text, $start_pos, $end_pos - $start_pos).$postfix;
}

/**
 * Splits a string by space characters and returns these words as an array.
 *
 * @param string $string the string to split
 *
 * @return array  the words of the string as array
 */
function words($string)
{
  return preg_split('/ /', $string, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Encodes a string or array from Stud.IP encoding (WINDOWS-1252/ISO-8859-1 with numeric HTML-ENTITIES) to UTF-8
 *
 * @param mixed $data a string or an array with strings to encode in WINDOWS-1252/HTML-ENTITIES
 *
 * @return string  the string in UTF-8
 */
function studip_utf8encode($data)
{
    if (is_array($data)) {
        $new_data = array();
        foreach ($data as $key => $value) {
            $key = studip_utf8encode($key);
            $new_data[$key] = $value = studip_utf8encode($value);
        }
        return $new_data;
    } elseif(is_string($data)) {
        if(!preg_match('/[\200-\377]/', $data) && !preg_match("'&#[0-9]+;'", $data)){
            return $data;
        } else {
            return mb_decode_numericentity(
                mb_convert_encoding($data,'UTF-8', 'WINDOWS-1252'),
                array(0x100, 0xffff, 0, 0xffff), 
                'UTF-8'
            );
        }
    } else {
        return $data;
    }
}

/**
 * Encodes a string or array from UTF-8 to Stud.IP encoding (WINDOWS-1252/ISO-8859-1 with numeric HTML-ENTITIES)
 *
 * @param mixed $data a string in UTF-8 or an array with all strings encoded in utf-8
 *
 * @return string  the string in WINDOWS-1252/HTML-ENTITIES
 */
function studip_utf8decode($data)
{
    if (is_array($data)) {
        $new_data = array();
        foreach ($data as $key => $value) {
            $key = studip_utf8decode($key);
            $new_data[$key] = $value = studip_utf8decode($value);
        }
        return $new_data;
    } elseif (is_string($data)) {
        if(!preg_match('/[\200-\377]/', $data)){
            return $data;
        } else {
            $windows1252 = array(
                "\x80" => '&#8364;',
                "\x81" => '&#65533;',
                "\x82" => '&#8218;',
                "\x83" => '&#402;',
                "\x84" => '&#8222;',
                "\x85" => '&#8230;',
                "\x86" => '&#8224;',
                "\x87" => '&#8225;',
                "\x88" => '&#710;',
                "\x89" => '&#8240;',
                "\x8A" => '&#352;',
                "\x8B" => '&#8249;',
                "\x8C" => '&#338;',
                "\x8D" => '&#65533;',
                "\x8E" => '&#381;',
                "\x8F" => '&#65533;',
                "\x90" => '&#65533;',
                "\x91" => '&#8216;',
                "\x92" => '&#8217;',
                "\x93" => '&#8220;',
                "\x94" => '&#8221;',
                "\x95" => '&#8226;',
                "\x96" => '&#8211;',
                "\x97" => '&#8212;',
                "\x98" => '&#732;',
                "\x99" => '&#8482;',
                "\x9A" => '&#353;',
                "\x9B" => '&#8250;',
                "\x9C" => '&#339;',
                "\x9D" => '&#65533;',
                "\x9E" => '&#382;',
                "\x9F" => '&#376;');
            return str_replace(
                array_values($windows1252),
                array_keys($windows1252),
                utf8_decode(mb_encode_numericentity(
                    $data,
                    array(0x100, 0xffff, 0, 0xffff),
                    'UTF-8'
                ))
            );
        }
    } else {
        return $data;
    }
}

/**
 * Get the title used for the given status ('dozent', 'tutor' etc.) for the
 * specified SEM_TYPE. Alternative titles can be defined in the config.inc.php.
 *
 * @global array $SEM_TYPE
 * @global array $SessSemName
 * @global array $DEFAULT_TITLE_FOR_STATUS
 *
 * @param string $type     status ('dozent', 'tutor', 'autor', 'user' or 'accepted')
 * @param int    $count    count, this determines singular or plural form of title
 * @param int    $sem_type sem_type of course (defaults to type of current course)
 *
 * @return string  translated title for status
 */
function get_title_for_status($type, $count, $sem_type = NULL)
{
    global $SEM_TYPE, $SessSemName, $DEFAULT_TITLE_FOR_STATUS;

    if (is_null($sem_type)) {
        $sem_type = $SessSemName['art_num'];
    }

    $atype = 'title_'.$type;

    if (isset($SEM_TYPE[$sem_type][$atype])) {
        $title = $SEM_TYPE[$sem_type][$atype];
    } else if (isset($DEFAULT_TITLE_FOR_STATUS[$type])) {
        $title = $DEFAULT_TITLE_FOR_STATUS[$type];
    } else {
        throw new Exception('unkown status in get_title_for_status()');
    }

    return ngettext($title[0], $title[1], $count);
}

/**
 * Stud.IP encoding aware version of good ol' substr(), treats numeric HTML-ENTITIES as one character
 * use only if really necessary
 *
 * @param string  $string string to shorten
 * @param integer $offset position to start with
 * @param integer $length maximum length
 *
 * @return string  the part of the string
 */
function studip_substr($string, $offset, $length = false)
{
    if(!preg_match("'&#[0-9]+;'", $string)){
        return substr($string, $offset, $length);
    }
    $utf8string = studip_utf8encode($string);
    if ($length === false) {
        return studip_utf8decode(mb_substr($utf8string, $offset, mb_strlen($utf8string, 'UTF-8'), 'UTF-8'));
    } else {
        return studip_utf8decode(mb_substr($utf8string, $offset, $length, 'UTF-8'));
    }
}

/**
 * Stud.IP encoding aware version of good ol' strlen(), treats numeric HTML-ENTITIES as one character
 * use only if really necessary
 *
 * @param string $string the string to measure
 * 
 * @return integer  the number of characters in string
 */
function studip_strlen($string)
{
    if(!preg_match("'&#[0-9]+;'", $string)){
        return strlen($string);
    }
    return mb_strlen(studip_utf8encode($string), 'UTF-8');
}

/**
 * Test whether the given URL refers to some page or resource of
 * this Stud.IP installation.
 *
 * @param string $url url to check
 *
 * @return mixed
 */
function is_internal_url($url)
{
    if (preg_match('%^[a-z]+:%', $url)) {
        return strpos($url, $GLOBALS['ABSOLUTE_URI_STUDIP']) === 0;
    }

    if ($url[0] === '/') {
        return strpos($url, $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']) === 0;
    }

    return true;
}

/**
 * Return the list of SEM_TYPES that represent study groups in this
 * Stud.IP installation.
 *
 * @return array  list of SEM_TYPES used for study groups
 */
function studygroup_sem_types()
{
    $result = array();

    foreach ($GLOBALS['SEM_TYPE'] as $id => $sem_type) {
        if ($GLOBALS['SEM_CLASS'][$sem_type['class']]['studygroup_mode']) {
            $result[] = $id;
        }
    }

    return $result;
}

/**
 * generates form fields for the submitted multidimensional array
 *
 * @param string $variable the name of the array, which is filled with the data
 * @param mixed  $data     the data-array
 * @param mixed  $parent   leave this entry as is
 *
 * @return string the inputs of type hidden as html
 */
function addHiddenFields($variable, $data, $parent = array())
{
    if (is_array($data)) {
        foreach($data as $key => $value) {
            if (is_array($value)) {
                $ret .= addHiddenFields($variable, $value, array_merge($parent, array($key)));
            } else {
                $ret.= '<input type="hidden" name="'. $variable .'['. implode('][', array_merge($parent, array($key))) .']" value="'. $value .'">' ."\n";
            }
        }
    }

    return $ret;
}

/**
 * Returns a new array that is a one-dimensional flattening of this
 * array (recursively). That is, for every element that is an array,
 * extract its elements into the new array.
 *
 * @param array $ary the array to be flattened
 * @return array the flattened array
 */
function array_flatten($ary)
{
    $i = 0;
    while ($i < sizeof($ary)) {
        if (is_array($ary[$i])) {
            array_splice($ary, $i, 1, $ary[$i]);
        } else {
            $i++;
        }
    }
    return $ary;
}
