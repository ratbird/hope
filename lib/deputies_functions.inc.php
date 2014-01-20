<?php
# Lifter010: TODO
/**
* Helper functions for deputy handling
*
*
* @author       Thomas Hackl <thomas.hackl@uni-passau.de>
* @access       public
* @modulegroup  library
* @module       deputies_functions.inc
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// deputies_functions.inc.php
// helper functions for deputy handling
// Copyright (c) 2010 Thomas Hackl <thomas.hackl@uni-passau.de>
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

/**
 * Fetches all deputies of the given course or person.
 * 
 * @param string $range_id ID of a course or person
 * @param string $name_format defines which format the full name of a deputy should have
 * @return array An array containing all deputies.
 */
function getDeputies($range_id, $name_format='full_rev') {
    global $_fullname_sql;
    if ($_fullname_sql[$name_format]) {
        $name_query = $_fullname_sql[$name_format];
    } else {
        $name_query = $_fullname_sql['full_rev'];
    }
    $name_query .= " AS fullname "; 
    $data = DBManager::get()->query(
        "SELECT a.user_id, a.username, a.Vorname, a.Nachname, d.edit_about, ". 
        "a.perms, ".$name_query.
        "FROM deputies d ".
        "LEFT JOIN auth_user_md5 a ON (d.user_id=a.user_id) ".
        "LEFT JOIN user_info ON (a.user_id=user_info.user_id) ".
        "WHERE d.range_id='$range_id' ".
        "ORDER BY a.Nachname ASC, a.Vorname ASC");
    $deputies = array();
    foreach ($data->fetchAll() as $entry) {
        $deputies[$entry['user_id']] = $entry;
    }
    return $deputies;
}

/**
 * Fetches all persons of which the given person is default deputy.
 * 
 * @param string $user_id the user to check
 * @param string $name_format what format should full name entries have?
 * @return array An array of the given person's bosses.
 */
function getDeputyBosses($user_id, $name_format='full_rev') {
    global $_fullname_sql;
    if ($_fullname_sql[$name_format]) {
        $name_query = $_fullname_sql[$name_format];
    } else {
        $name_query = $_fullname_sql['full_rev'];
    }
    $name_query .= " AS fullname "; 
    $data = DBManager::get()->query(
       "SELECT a.user_id, a.username, a.Vorname, a.Nachname, d.edit_about, ".
        $name_query.
       " FROM deputies d LEFT JOIN auth_user_md5 a ON (d.range_id=a.user_id) ".
       "JOIN user_info ui ON (a.user_id=ui.user_id) ".
       "WHERE d.user_id='$user_id' ".
       "ORDER BY a.Nachname ASC, a.Vorname ASC");
    return $data->fetchAll();
}

/**
 * Adds a person as deputy of a course or another person.
 * 
 * @param string $user_id person to add as deputy
 * @param string $range_id ID of a course or a person
 * @return int Number of affected rows in the database (hopefully 1).
 */
function addDeputy($user_id, $range_id) {
    return DBManager::get()->exec(
       "INSERT INTO deputies SET range_id='$range_id', user_id='$user_id'");
}

/**
 * Removes a person as deputy in the given context (course or person).
 * 
 * @param mixed $user_id which person(s) to remove, can be a single ID or 
 * an array of IDs
 * @param string $range_id where to remove as deputy (course or person ID)
 * @return int Number of affected rows in the database ("1" if successful).
 */
function deleteDeputy($user_id, $range_id) {
    if (is_array($user_id)) {
        return DBManager::get()->exec(
               "DELETE FROM deputies ".
               "WHERE range_id='$range_id' AND user_id IN ('".
               implode("', '", $user_id)."')"
            );
    } else {
        return DBManager::get()->exec(
                "DELETE FROM deputies ".
                "WHERE range_id='$range_id' AND user_id='$user_id'"
           );
    }
}

/**
 * Remove all deputies of the given course or person at once.
 * 
 * @param string $range_id course or person ID
 * @return int Number of affected database rows (>0 if successful).
 */
function deleteAllDeputies($range_id) {
    return DBManager::get()->exec(
           "DELETE FROM deputies WHERE range_id='".$range_id."'"
       );
}

/**
 * Checks whether the given person is a deputy in the given context 
 * (course or person).
 * 
 * @param string $user_id person ID to check
 * @param string $range_id course or person ID
 * @param boolean $check_edit_about check if the given person may edit 
 * the other person's profile
 * @return boolean Is the given person deputy in the given context?
 */
function isDeputy($user_id, $range_id, $check_edit_about=false) {
    $query = "SELECT COUNT(user_id) AS deputy FROM deputies ".
        "WHERE range_id='$range_id' AND user_id='$user_id'";
    if ($check_edit_about)
        $query .= " AND edit_about=1";
    $data = DBManager::get()->query($query);
    $current = $data->fetch();
    return $current['deputy'];
}

/**
 * Set whether the given person my edit the bosses profile page.
 * 
 * @param string $user_id person ID to grant or remove rights
 * @param string $range_id which person's profile are we talking about?
 * @param int $rights editing allowed? 0 or 1
 * @return Number of affected database rows ("1" if successful).
 */
function setDeputyHomepageRights($user_id, $range_id, $rights) {
    return DBManager::get()->exec("UPDATE deputies SET edit_about=$rights ".
       "WHERE user_id='$user_id' AND range_id='$range_id'");
}

/**
 * Shows which permission level a person must have in order to be available 
 * as deputy.
 * 
 * @param boolean $min_perm_only whether to give only the minimum permission 
 * or a set of all valid permissions available for being deputy
 * @return mixed The minimum permission needed for being deputy or a set of
 * all permissions between minimum and "admin"
 */
function getValidDeputyPerms($min_perm_only = false) {
    $permission = $min_perm_only ? 'tutor' : array('tutor', 'dozent');
    return $permission;
}

/**
 * Checks whether the given user has the necessary permission in order to be 
 * deputy.
 * 
 * @param string $user_id user ID to check
 * @return boolean My the given user be given as deputy?
 */
function haveDeputyPerm($user_id='') {
    global $perm;
    $minimum_perm = get_config('DEPUTIES_MIN_PERM');
    if (!$user_id) {
        $user_id = $GLOBALS['user']->id;
    }
    if ($perm->have_perm($minimum_perm, $user_id) && !$perm->have_perm('admin', $user_id)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Database query for retrieving all courses where the current user is deputy 
 * in.
 * 
 * @param string $type are we in the "My courses" list (='meine_seminare') or 
 * in grouping or notification view ('gruppe', 'notification') or outside 
 * Stud.IP in the notification cronjob (='notification_cli')?
 * @param string $sem_number_sql SQL for specifying the semester for a course
 * @param string $sem_number_end_sql SQL for specifying the last semester 
 * a course is in
 * @param string $add_fields optionally necessary fields from database
 * @param string $add_query additional joins
 * @return string The SQL query for getting all courses where the current 
 * user is deputy in
 */
function getMyDeputySeminarsQuery($type, $sem_number_sql, $sem_number_end_sql, $add_fields, $add_query) {
    global $user;
    switch ($type) {
        // My courses list
        case 'meine_sem':
            $fields = array(
                "seminare.VeranstaltungsNummer AS sem_nr",
                "CONCAT(seminare.Name, ' ["._("Vertretung")."]') AS Name",
                "seminare.Seminar_id",
                "seminare.status as sem_status",
                "'dozent'",
                "deputies.gruppe",
                "seminare.chdate",
                "seminare.visible",
                "admission_binding",
                "modules",
                "IFNULL(visitdate,0) as visitdate",
                "admission_prelim",
                "$sem_number_sql as sem_number",
                "$sem_number_end_sql as sem_number_end"
            );
            $joins = array(
                    "JOIN seminare ON (deputies.range_id=seminare.Seminar_id)",
                    "LEFT JOIN object_user_visits ouv ON (ouv.object_id=deputies.range_id AND ouv.user_id='$user->id' AND ouv.type='sem')"
                );
            $where = " WHERE deputies.user_id = '$user->id'";
            break;
        // Grouping and notification settings for my courses
        case 'gruppe':
        case 'notification':
            $fields = array(
                     "seminare.VeranstaltungsNummer AS sem_nr",
                     "CONCAT(seminare.Name, ' ["._("Vertretung")."]') AS Name",
                     "seminare.Seminar_id",
                     "seminare.status as sem_status",
                     "deputies.gruppe",
                     "seminare.visible",
                     "$sem_number_sql as sem_number",
                     "$sem_number_end_sql as sem_number_end"
            );
            $joins = array(
                    "JOIN seminare ON (deputies.range_id=seminare.Seminar_id)"
                );
            $where = " WHERE deputies.user_id = '$user->id'";
            break;
        // Notification mail sending from client script
        case 'notification_cli':
            $fields = array(
                "aum.user_id",
                "aum.username",
                $GLOBALS['_fullname_sql']['full']." AS fullname",
                "aum.Email"
            );
            $joins = array(
                "INNER JOIN auth_user_md5 aum ON (deputies.user_id=aum.user_id)",
                "LEFT JOIN user_info ui ON (ui.user_id=deputies.user_id)"
            );
            $where = " WHERE deputies.notification != 0";
            break;
    }
    $query = "SELECT ".implode(", ", $fields)." ".$add_fields.
        " FROM deputies ".
        implode(" ", $joins).
        $add_query.
        $where;
    return $query;
}

/**
 * Checks if persons may be assigned as default deputy of other persons.
 * 
 * @return activation status of the default deputy functionality.
 */
function isDefaultDeputyActivated() {
    return get_config('DEPUTIES_ENABLE') && 
        get_config('DEPUTIES_DEFAULTENTRY_ENABLE');
}

/**
 * Checks if default deputies may get the rights to edit their bosses profile 
 * page.
 * 
 * @return activation status of the deputy boss profile page editing 
 *         functionality.
 */
function isDeputyEditAboutActivated() {
    return get_config('DEPUTIES_ENABLE') && 
        get_config('DEPUTIES_DEFAULTENTRY_ENABLE') && 
        get_config('DEPUTIES_EDIT_ABOUT_ENABLE');
}
?>