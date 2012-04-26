<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* helper functions for handling statusgruppen
*
* helper functions for handling statusgruppen
*
* @author               Ralf Stockmann <rstockm@gwdg.de>
* @access               public
* @package          studip_core
* @modulegroup  library
* @module               statusgruppe.inc.php
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// statusgruppe.inc.php
// Copyright (c) 2002 Ralf Stockmann <rstockm@gwdg.de>
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

require_once('lib/classes/DataFieldEntry.class.php');
require_once('lib/classes/Statusgruppe.class.php');

/**
* built a not existing ID
*
* @access private
* @return   string
*/
function MakeUniqueStatusgruppeID ()
{
    $query = "SELECT 1 FROM statusgruppen WHERE statusgruppe_id = ?";
    $presence = DBManager::get()->prepare($query);

    do {
        $tmp_id = md5(uniqid('status_gruppe', true));

        $presence->execute(array($tmp_id));
        $present = $presence->fetchColumn();
        $presence->closeCursor();
    } while ($present);

    return $tmp_id;
}


// Funktionen zum veraendern der Gruppen

function AddNewStatusgruppe ($new_statusgruppe_name, $range_id, $new_statusgruppe_size, $new_selfassign = 0, $new_doc_folder = false, $statusgruppe_id = false)
{
    $query = "SELECT position FROM statusgruppen WHERE range_id = ? ORDER BY position DESC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    $position = 1 + $statement->fetchColumn();

    $query = "INSERT INTO statusgruppen (statusgruppe_id, name, range_id, position, size,
                                         selfassign, calendar_group, mkdate, chdate)
              VALUES (?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $statusgruppe_id ?: MakeUniqueStatusgruppeID(),
        $new_statusgruppe_name,
        $range_id,
        $position,
        $new_statusgruppe_size ?: 0,
        $new_selfassign,
        Request::get('is_cal_group') ? 1 : 0,
    ));
    if ($statement->rowCount() && $new_doc_folder) {
        create_folder(mysql_escape_string(_("Dateiordner der Gruppe:") . ' ' . $new_statusgruppe_name), mysql_escape_string(_("Ablage für Ordner und Dokumente dieser Gruppe")), $statusgruppe_id, 15);
    }
    return $statusgruppe_id;
}

function CheckSelfAssign($statusgruppe_id)
{
    $query = "SELECT selfassign FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id));
    return $statement->fetchColumn();
}

function CheckSelfAssignAll($seminar_id)
{
    $query = "SELECT SUM(selfassign), COUNT(IF(selfassign > 0, 1, NULL)), MIN(selfassign)
              FROM statusgruppen
              WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($seminar_id));
    $temp = $statement->fetch(PDO::FETCH_NUM);

    // TODO What do these flags represent? [tlx]
    return array(
        (bool)$temp[2],
        $temp[1] && $temp[0] == $temp[1] * 2,
    );
}

function CheckAssignRights($statusgruppe_id, $user_id, $seminar_id) {
    global $perm;
    list($self_assign_all, $self_assign_exclusive) = CheckSelfAssignAll($seminar_id);
    if (CheckSelfAssign($statusgruppe_id)
    && !CheckUserStatusgruppe($statusgruppe_id, $user_id)
    && !$perm->have_perm("admin")
    && $perm->have_perm("autor")
    && ((GetStatusgruppeLimit($statusgruppe_id)==FALSE) || (GetStatusgruppeLimit($statusgruppe_id) > CountMembersPerStatusgruppe($statusgruppe_id)))
    && !($self_assign_exclusive && in_array($user_id, GetAllSelected($seminar_id)))
    )
        $assign = TRUE;
    else
        $assign = FALSE;
    return $assign;
}

/**
 * sets selfassign of a group to 0 or 1/2 dependend on the status of the other groups
 * @param statusgruppe_id:  id of statusgruppe in database
 * @param flag: 0 for users are not allowed to assign themselves to this group
 *                          or 1 / 2 to set selfassign to the value of the other statusgroups
 *                          of the same seminar for which selfassign is allowed. If no such
 *                          group exists, selfassign is set to the value of flag, 1 means
 *                          selfassigning is allowed and 2 it's only allowed for a maximum
 *                          of one group.
 */
function SetSelfAssign ($statusgruppe_id, $flag="0") {
    $db = DBManager::get();
    if ($flag != 0) {
        $query = "SELECT selfassign FROM statusgruppen WHERE selfassign = ? AND range_id = (
                      SELECT range_id
                      FROM statusgruppen
                      WHERE statusgruppe_id = ?
                  )";
        $statement = DBManager::get()->prepare($query);

        $statement->execute(array(2, $statusgruppe_id));
        if ($temp = $statement->fetchColumn()) {
            $flag = $temp;
        } else {
            $statement->execute(array(1, $statusgruppe_id));

            if ($temp = $statement->fetchColumn()) {
                $flag = $temp;
            }
        }
    }

    $query = "UPDATE statusgruppen SET selfassign = ? WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($flag, $statusgruppe_id));

    return $flag;
}

function SetSelfAssignAll ($seminar_id, $flag = false)
{
    $query = "UPDATE statusgruppen SET selfassign = ? WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array((int)$flag, $seminar_id));
    return $statement->rowCount();
}

function SetSelfAssignExclusive ($seminar_id, $flag = false)
{
    $query = "UPDATE statusgruppen SET selfassign = ? WHERE range_id = ? AND selfassign > 0";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $flag ? 2 : 1,
        $seminar_id,
    ));
    return $statement->rowCount();
}

function GetAllSelected ($range_id, $level = 0)
{
    $query = "SELECT user_id, statusgruppe_id
              FROM statusgruppen
              LEFT JOIN statusgruppe_user USING (statusgruppe_id)
              WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

    // WTF???
    if (empty($temp)) {
        return $level == 0 ? array() : false;
    }

    $selected = array();
    $role_ids = array();

    foreach ($temp as $row) {
        if ($row['user_id'] != null) {
            $selected[$row['user_id']] = true;
        }

        if (!$role_ids[$row['statusgruppe_id']]) {
            $zw = GetAllSelected($row['statusgruppe_id'], $level + 1);
            if ($zw) {
                $selected += array_fill_keys($zw, true);
            }
            $role_ids[$row['statusgruppe_id']] = true;
        }
    }

    return array_keys($selected);
}

function EditStatusgruppe ($new_statusgruppe_name, $new_statusgruppe_size, $edit_id, $new_selfassign="0", $new_doc_folder = false)
{
    $query = "UPDATE statusgruppen
              SET name = ?, size = ?, selfassign = ?, calendar_group = ?, chdate = UNIX_TIMESTAMP()
              WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $new_statusgruppe_name,
        $new_statusgruppe_size,
        $new_selfassign,
        Request::get('is_cal_group') ? 1 : 0,
        $edit_id,
    ));

    if ($new_doc_folder) {
        create_folder(mysql_escape_string(_("Dateiordner der Gruppe:") . ' '. $new_statusgruppe_name), mysql_escape_string(_("Ablage für Ordner und Dokumente dieser Gruppe")), $edit_id, 15);
    }
}

function InsertPersonStatusgruppe ($user_id, $statusgruppe_id)
{
    $query = "SELECT 1 FROM statusgruppe_user WHERE user_id = ? AND statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $statusgruppe_id));
    $present = $statement->fetchColumn();

    if ($present) {
        return false;
    }

    $position = CountMembersPerStatusgruppe($statusgruppe_id) + 1;

    $query = "INSERT INTO statusgruppe_user (statusgruppe_id, user_id, position)
              VALUES (?, ?, ?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id, $user_id, $position));

    MakeDatafieldsDefault($user_id, $statusgruppe_id);

    return true;
}

function MakeDatafieldsDefault($user_id, $statusgruppe_id, $default = 'default_value')
{
    global $auth;
    $fields = DataFieldStructure::getDataFieldStructures('userinstrole');

    $query = "SELECT datafield_id FROM datafields WHERE object_type = 'userinstrole'";
    $ids = DBManager::get()->query($query)->fetchAll(PDO::FETCH_COLUMN);

    $query = "REPLACE INTO datafields_entries (datafield_id, range_id, content, sec_range_id, mkdate, chdate)
              VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
    $insert = DBManager::get()->prepare($query);

    foreach ($ids as $id) {
        if ($fields[$id]->editAllowed($auth->auth['perm'])) {
            $insert->execute(array($id, $user_id, $default, $statusgruppe_id));
        }
    }
}

// find all "statusgruppen_ids" which are connected to a certain range_id
function getStatusgruppenIDS($range_id)
{
    $query = "SELECT statusgruppe_id FROM statusgruppen WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

// find the complete "statusgruppen_id-hierarchy" associated with an range_id
function getAllStatusgruppenIDS($range_id)
{
    $agenda =array($range_id);
    $result = array();
    while(sizeof($agenda)>0)
    {
        $current = array_pop($agenda);
        $result[] =  $current;
        $agenda = array_merge((array)getStatusgruppenIDS($current), (array)$agenda);
    }
    return $result;
}

function RemovePersonStatusgruppe ($username, $statusgruppe_id)
{
    $user = User::findByUsername($username);

    // Get user's position for later resorting
    $query = "SELECT position FROM statusgruppe_user WHERE statusgruppe_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id, $user->user_id));
    $position = $statement->fetchColumn() ?: 0;

    // Delete user from statusgruppe
    $query = "DELETE FROM statusgruppe_user WHERE statusgruppe_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id, $user->user_id));

    // Resort members
    $query = "UPDATE statusgruppe_user SET position = position - 1 WHERE statusgruppe_id = ? AND position > ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id, $position));
}

function RemovePersonFromAllStatusgruppen ($username)
{
    $user = User::findByUsername($username);

    $query = "DELETE FROM statusgruppe_user WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->user_id));
    return $statement->rowCount();
}

function RemovePersonStatusgruppeComplete ($username, $range_id) {

    $result = getAllStatusgruppenIDS($range_id);
    $user = User::findByUsername($username);

    $query = "SELECT DISTINCT statusgruppe_id
              FROM statusgruppe_user
              LEFT JOIN statusgruppen USING (statusgruppe_id)
              WHERE range_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);

    foreach ($result as $range_id) {
        $statement->execute(array($range_id, $user->user_id));
        $statusgruppen = $statement->fetchAll(PDO::FETCH_COLUMN);
        $statement->closeCursor();

        foreach ($statusgruppen as $id) {
            RemovePersonStatusgruppe($username, $id);
        }
    }
}

function DeleteStatusgruppe ($statusgruppe_id)
{
    $query = "SELECT position, range_id FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$temp) {
        return;
    }

    // get all child-statusgroups and put them as a child of the father, so they don't hang around without a parent
    $childs = getAllChildIDs($statusgruppe_id);
    if (!empty($childs)) {
        $query = "UPDATE statusgruppen SET range_id = ? WHERE statusgruppe_id IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($temp['range_id'], $childs));
    }

    // Remove statusgruppe, assigned users and assigned datafields
    $query = "DELETE s, su, de
              FROM statusgruppen AS s
                LEFT JOIN statusgruppe_user AS su USING(statusgruppe_id)
                LEFT JOIN datafields_entries AS de ON (s.statusgruppe_id = de.range_id)
              WHERE s.statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id));

    // Resort
    $query = "UPDATE statusgruppen SET position = position - 1 WHERE range_id = ? AND position > ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($temp['range_id'], $temp['position']));
}

function MovePersonPosition ($username, $statusgruppe_id, $direction)
{
    $user = User::findByUsername($username);

    $query = "SELECT position FROM statusgruppe_user WHERE statusgruppe_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id, $user->user_id));
    $position = $statement->fetchColumn();

    if ($position !== false) {
        $old_position = $position;

        if ($direction == 'up') {
            $position -= 1;
        } else if ($direction == 'down') {
            $position += 1;
        }

        $query = "UPDATE statusgruppe_user SET position = ? WHERE statusgruppe_id = ? AND position = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($old_position, $statusgruppe_id, $position));

        $query = "UPDATE statusgruppe_user SET position = ? WHERE statusgruppe_id = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($position, $statusgruppe_id, $user->user_id));
    }
}

/**
 * Sorts a person in statusgroup after the an other person
 * @param $user_id the user to be sorted
 * @param $after to user after which is sorted in
 * @param $role_id the id of the statusgroup the sorting is taking place
 */
function SortPersonInAfter($user_id, $after, $role_id)
{
    $query = "SELECT SUM(position)
              FROM statusgruppe_user
              WHERE user_id IN (?, ?) AND statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $after, $role_id));
    $position_sum = $statement->fetchColumn();

    $query = "UPDATE statusgruppe_user SET position = ? - position WHERE statusgruppe_id = ? AND user_id IN (?, ?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($position_sum, $user_id, $after));
}

function DeleteAllStatusgruppen ($range_id)
{
    $query = "SELECT statusgruppe_id FROM statusgruppen WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    foreach ($ids as $id) {
        DeleteStatusgruppe($id);
    }

    return count($ids);
}

function moveStatusgruppe($role_id, $up_down = 'up')
{
    $query = "SELECT range_id, position FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($role_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$temp) {
        return;
    }

    $position = $temp['position'];
    if ($up_down == 'up') {
        $other_position = $position - 1;
    } else {
        $other_position = $position + 1;
    }

    $query = "UPDATE statusgruppen SET position = ? WHERE range_id = ? AND position = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($position, $temp['range_id'], $other_position));

    $query = "UPDATE statusgruppen SET position = ? WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($other_position, $role_id));
}


function SortStatusgruppe($insert_after, $insert_id)
{
    $query = "SELECT range_id, position FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($insert_after));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    $query = "UPDATE statusgruppen SET position = position + 1 WHERE range_id = ? AND position > ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($temp['range_id'], $temp['position']));

    $query = "UPDATE statusgruppen SET position = ? WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($temp['position'] + 1, $insert_id));

    resortStatusgruppeByRangeId($range_id);
}

function SubSortStatusgruppe($insert_father, $insert_daughter) {
    if ($insert_father == '' || $insert_daughter == '') return FALSE;
    if (isVatherDaughterRelation($insert_father, $insert_daughter)) return FALSE;

    $query = "SELECT MAX(position) FROM statusgruppen WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement-execute(array($insert_daughter));
    $position = $statement->fetchColumn() + 1;

    $query = "UPDATE statusgruppen SET position = ?, range_id = ? WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($position, $insert_daughter, $insert_father));

    return TRUE;
}


function resortStatusgruppeByRangeId($range_id)
{
    $query = "SELECT statusgruppe_id FROM statusgruppen WHERE range_id = ? ORDER BY position";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    $query = "UPDATE statusgruppen SET position = ? WHERE statusgruppe_id = ?";
    $update = DBManager::get()->prepare($query);

    foreach ($ids as $index => $id) {
        $update->execute(array($index, $id));
    }
}

function SwapStatusgruppe ($statusgruppe_id)
{
    moveStatusgruppe($statusgruppe_id, 'down');
}

function CheckStatusgruppe ($range_id, $name)
{
    $query = "SELECT statusgruppe_id FROM statusgruppen WHERE range_id = ? AND name = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id, $name));
    return $statement->fetchColumn();
}

function CheckUserStatusgruppe ($group_id, $object_id)
{
    $query = "SELECT 1 FROM statusgruppe_user WHERE statusgruppe_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($group_id, $object_id));
    return $statement->fetchColumn();
}

function GetRangeOfStatusgruppe ($statusgruppe_id)
{
    $has_parent = true;

    $query = "SELECT range_id FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);

    $group = $statusgruppe_id;
    while ($has_parent) {
        $statement->execute(array($group));
        $range_id = $statement->fetchColumn();
        $statement->closeCursor();

        if ($range_id) {
            $group = $range_id;
        } else {
            $has_parent = false;
        }
    }

    return $group;
}


/**
* get all statusgruppen for one user and one range
*
* @access   public
* @param    string  $course_id
* @param    string  $user_id
* @return   array   ( statusgruppe_id => name)
*/
function GetGroupsByCourseAndUser($course_id, $user_id)
{
    $st = DbManager::get()->prepare("SELECT statusgruppe_id, a.name
                                     FROM statusgruppen a
                                     INNER JOIN statusgruppe_user b USING (statusgruppe_id)
                                     WHERE user_id = ? AND a.range_id = ?
                                     ORDER BY a.position");
    $st->execute(array($user_id, $course_id));
    return $st->fetchGrouped(PDO::FETCH_COLUMN);
}

function getOptionsOfStGroups ($userID)
{
    $query = "SELECT statusgruppe_id, visible, inherit FROM statusgruppe_user WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($userID));
    return $statement->fetchGrouped(PDO::FETCH_ASSOC);
}


// visible and inherit must be '0' or '1'
function setOptionsOfStGroup ($groupID, $userID, $visible, $inherit='')
{
    $query = "REPLACE INTO statusgruppe_user (statusgruppe_id, user_id, visible, inherit)
                SELECT statusgruppe_id, user_id, IFNULL(?, visible), IFNULL(?, inherit)
                FROM statusgruppe_user
                WHERE statusgruppe_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $visible !== '' ? ($visible == '1' ? 1 : 0) : null,
        $inherit !== '' ? ($inherit == '1' ? 1 : 0) : null,
        $groupID,
        $user_id,
    ));
}


/**
* Returns the number of persons who are grouped in Statusgruppen for one range.
*
* Persons who are members in more than one Statusgruppe will be count only once
*
* @access public
* @param string $range_id The ID of a range with Statusgruppen
* @return int The number of members
*/
function CountMembersStatusgruppen ($range_id)
{
    $ids = getAllStatusgruppenIDS($range_id);
    if (empty($ids)) {
        return 0;
    }

    $query = "SELECT COUNT(DISTINCT user_id)
              FROM statusgruppen
              JOIN statusgruppe_user USING (statusgruppe_id)
              WHERE statusgruppe_id IN (?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($ids));
    return $statement->fetchColumn();
}

function CountMembersPerStatusgruppe ($group_id)
{
    $query = "SELECT COUNT(user_id)
              FROM statusgruppen
              JOIN statusgruppe_user USING (statusgruppe_id)
              WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($group_id));
    return $statement->fetchColumn();
}


/**
* Returns all statusgruppen for the given range.
*
* If there is no statusgruppe for the given range, it returns FALSE.
*
* @access public
* @param  string  $range_id
* @param  string  $user_id (optional)
* @return array (tree)
*/

function GetStatusgruppenForUser($user_id, $group_list)
{
    if (empty($group_list)) {
        return false;
    }

    $query = "SELECT statusgruppe_id
              FROM statusgruppe_user
              LEFT JOIN statusgruppen USING (statusgruppe_id)
              WHERE user_id = ? AND statusgruppe_id IN (?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id, $group_list));
    return $statement->fetchAll(PDO::FETCH_COLUMN) ?: false;
}


/**
* Returns all statusgruppen for the given range.
*
* If there is no statusgruppe for the given range, it returns FALSE.
*
* @access   public
* @param    string  $range_id
* @param    string  $user_id
* @return   array   (structure statusgruppe_id => name)
*/
function GetAllStatusgruppen($parent, $check_user = null, $exclude = false)
{
    $query = "SELECT * FROM statusgruppen WHERE range_id = ? ORDER BY position";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($parent));
    $groups = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (empty($groups)) {
        return false;
    }

    $query = "SELECT visible FROM statusgruppe_user WHERE user_id = ? AND statusgruppe_id = ?";
    $presence = DBManager::get()->prepare($query);

    $childs = array();
    foreach ($groups as $group) {
        $user_there = $visible = $user_in_child = false;

        $kids = getAllStatusgruppen($group['statusgruppe_id'], $check_user, $exclude);

        if ($check_user) {
            $presence->execute(array($check_user, $group['statusgruppe_id']));
            $present = $presence->fetchColumn();
            $presence->closeCursor();

            if ($user_there = ($present !== false)) {
                $visible = $present;
            }

            if (is_array($kids)) {
                foreach ($kids as $kid) {
                    if ($kid['user_there'] || $kid['user_in_child']) {
                        $user_in_child = true;
                    }
                }
            }
        }

        if (!$check_user || !$exclude || $user_in_child || $user_there) {
            $childs[$group['statusgruppe_id']] = array(
                'role'          => Statusgruppe::getFromArray($group),
                'visible'       => $visible,
                'user_there'    => $user_there,
                'user_in_child' => $user_in_child,
                'child'         => $kids
            );
        }
    }

    return is_array($childs) ? $childs : false;
}


function GetStatusgruppeName ($group_id)
{
    $query = "SELECT name FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($group_id));
    return $statement->fetchColumn();
}

function GetStatusgruppeLimit ($group_id)
{
    $query = "SELECT size FROM statusgruppen WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($group_id));
    return $statement->fetchColumn();
}

function CheckStatusgruppeFolder($group_id)
{
    $query = "SELECT folder_id FROM folder WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($group_id));
    return $statement->fetchColumn();
}

function CheckStatusgruppeMultipleAssigns($range_id)
{
    $query = "SELECT COUNT(statusgruppe_id) AS count, user_id, GROUP_CONCAT(name) AS gruppen
              FROM statusgruppen
              INNER JOIN statusgruppe_user USING (statusgruppe_id)
              WHERE range_id = ? AND selfassign = 2
              GROUP BY user_id
              HAVING count > 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function isVatherDaughterRelation($vather, $daughter) {
    $children = getAllChildIDs($vather);
    return array_key_exists($daughter, $children);
}

function getAllChildIDs($range_id)
{
    $query = "SELECT statusgruppe_id, name FROM statusgruppen WHERE range_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id));
    $zw = $statement->fetchGrouped(PDO::FETCH_COLUMN);

    $ids = array_keys($zw);
    foreach (array_keys($zw) as $id) {
        $zw = array_merge($zw, getAllChildIDs($id));
    }

    return $zw;
}

function display_roles_recursive($roles, $level = 0, $pred = '') {
    if (is_array($roles))
    foreach ($roles as $role_id => $data) {
        $css_rec = new cssClassSwitcher();
        if ($level > 0) {
            $title = $pred.' > '. $data['name'];
        } else {
            $title = $data['name'];
        }
        echo '<tr><td colspan="2" class="steelkante"><b>'.$title.'</b></td></tr>';
        if ($persons = getPersonsForRole($role_id)) {
            $z = 1;
            if (is_array($persons))
            foreach ($persons as $p) {
                $css_rec->switchClass();
                $class = 'class="'.$css_rec->getClass().'"';
                //echo '<tr><td '.$class.' width="20" align="center">'.$p['position'].'</td>';
                echo '<tr><td '.$class.' width="20" align="center">'.$z.'&nbsp;</td>';
                echo '<td '.$class.'><a href="'.URLHelper::getLink('about.php?username='.$p['username']).'">'.$p['fullname'].'</a></td>';
                $z++;
            }
        }
        echo '<tr><td colspan="2" class="blank">&nbsp;</td></tr>';
        echo '</tr>';
        if ($data['child']) {
            if ($level > 0) {
                $zw = $pred . ' > '.$data['name'];
            } else {
                $pred = $data['name'];
                $zw = $pred;
            }
            display_roles_recursive($data['child'], $level+1, $zw);
        }
    }
}

function GetRoleNames($roles, $level = 0, $pred = '', $all = false) {
    $out = array();

    if (is_array($roles))
    foreach ($roles as $role_id => $role) {
        if ($level == 0) $inst_id = $role_id;
        if (!$role['name']) $role['name'] = $role['role']->getName();

        if ($pred != '') {
            $new_pred = $pred.' > '.$role['name'];
        } else {
            $new_pred = $role['name'];
        }

        if ($role['user_there'] || $all) {
            $out[$role_id] = $new_pred;
        }

        if ($role['child']) {
            $out = array_merge((array)$out, (array)GetRoleNames($role['child'], $level+1, $new_pred, $all));
        }
    }

    return (sizeof($out) > 0 ? $out : null);
}

function get_role_data_recursive($roles, $user_id, &$default_entries, $filter = null, $level = 0, $pred = '') {
    global $auth, $user, $has_denoted_fields;

    $out = '';
    $out_table = array();

    if (is_array($roles))
    foreach ($roles as $role_id => $role) {

        $role['name'] = $role['role']->getName();
        $out_zw = '';

        if ($pred != '') {
            $new_pred = $pred.' > '.$role['name'];
        } else {
            $new_pred = $role['name'];
        }

      $entries = DataFieldEntry::getDataFieldEntries(array($user_id, $role_id));

        if ($role['user_there']) {
            $out_zw .= '<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                    .  '<img src="'.$GLOBALS['ASSETS_URL'].'/images/forumgrau2.png">'
                    .  '&nbsp;</td><td colspan="2"><b>'. htmlReady($new_pred) .'</b></td></tr>';
            $zw = '<td %class%></td><td %class%><font size="-1">'. htmlReady($new_pred) .'</font></td>';
        }

        $zw2 = '';
        $has_value = false;

        if (is_array($entries))
        foreach ($entries as $id => $entry) {
            $default = false;
            if ($filter == null || in_array($id, $filter) === TRUE) {
                if ($entry->getValue() == 'default_value') {
                    $value = $default_entries[$id]->getDisplayValue();
                    $default = true;
                } else {
                    $value = $entry->getDisplayValue();
                }

                $name = $entry->structure->getName();
                if ($role['user_there']) {
                    $view = (DataFieldStructure::permMask($auth->auth['perm']) >= DataFieldStructure::permMask($entry->structure->getViewPerms()));
                    $show_star = false;
                    if (!$view && ($user_id == $user->id)) {
                        $view = true;
                        $show_star = true;
                        $has_denoted_fields = true;
                    }

                    if ($view) { // Sichtbarkeitsberechtigung
                        $zw2 .= '<td %class%><font size="-1">'. trim($value);
                        if ($show_star) $zw2 .= ' *';
                        $zw2 .= '</font></td>';

                        if (trim($value)) {
                            $has_value = true;
                            if (!$default) {
                                $out_zw .= '<tr><td></td><td>'. htmlReady($name) .':&nbsp;&nbsp;</td><td>'.trim($value);
                                if ($show_star) $out_zw .= ' *';
                                $out_zw .= '</td></tr>';
                            }
                        }
                    }   // Ende Sichtbarkeitsberechtigung

                }
            }

        }

        if ($role['user_there'] && $role['visible']) {
            $out_table[] = $zw.$zw2;
            $out .= $out_zw;
        }

        if ($role['child']) {
            $back = get_role_data_recursive($role['child'], $user_id, $default_entries, $filter, $level+1, $new_pred);
            $out .= $back['standard'];
            $out_table = array_merge((array)$out_table, (array)$back['table']);
        }
    }

    return array('standard' => $out, 'table' => $out_table);
}

function getPersonsForRole($role_id)
{
    global $_fullname_sql;

    $query = "SELECT user_id, {$_fullname_sql['full_rev']} AS fullname, username, position
              FROM statusgruppe_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE statusgruppe_id = ?
              ORDER BY position";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($role_id));
    return $statement->fetchGrouped(PDO::FETCH_ASSOC);
}

function sortStatusgruppeByName($statusgruppe_id)
{
    $query = "SELECT user_id
              FROM statusgruppe_user
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE statusgruppe_id = ?
              ORDER BY Nachname";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($statusgruppe_id));
    $users = $statement->fetchAll(PDO::FETCH_COLUMN);

    $query = "UPDATE statusgruppe_user
              SET position = ?
              WHERE user_id = ? AND statusgruppe_id = ?";
    $update = DBManager::get()->prepare($query);

    foreach ($users as $index => $user_id) {
        $update->execute(array($index + 1, $user_id, $statusgruppe_id));
    }
}

function getPersons($range_id, $type = false)
{
    global $_fullname_sql;

    $bereitszugeordnet = GetAllSelected($range_id);

    if ($type == 'sem') {
        $query = "SELECT user_id, username, {$_fullname_sql['full_rev']} AS fullname, perms
                  FROM seminar_user
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE Seminar_id = :range_id
                  ORDER BY Nachname";
    } else if ($type == 'inst') {
        $query = "SELECT a.user_id, username, {$_fullname_sql['full_rev']} AS fullname, inst_perms, perms
                  FROM seminar_inst d
                  LEFT JOIN user_inst a USING (Institut_id)
                  LEFT JOIN auth_user_md5 b USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  LEFT JOIN seminar_user c ON (c.user_id=a.user_id AND c.seminar_id = :range_id)
                  WHERE d.seminar_id = :range_id AND a.inst_perms IN ('tutor','dozent') AND ISNULL(c.seminar_id)
                  GROUP BY a.user_id
                  ORDER BY Nachname";
    } else {
        $query = "SELECT user_inst.user_id, username, {$_fullname_sql['full_rev']} AS fullname, inst_perms AS perms
                  FROM user_inst
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE Institut_id = :range_id AND inst_perms NOT IN ('user', 'admin')
                  ORDER BY Nachname";
    }

    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':range_id', $range_id);
    $statement->execute();
    $all_persons = $statement->fetchGrouped(PDO::FETCH_ASSOC);

    foreach ($all_persons as $user_id => &$person) {
        $person['hasgroup'] = in_array($user_id, $bereitszugeordnet);
    }

    return $all_persons;
}

function getSearchResults ($search_exp, $range_id, $type = 'inst')
{
    global $_fullname_sql;

    if ($type == 'sem') {
        $query = "SELECT username, {$_fullname_sql['full_rev']} AS fullname, perms
                  FROM auth_user_md5 a
                  LEFT JOIN user_info USING (user_id)
                  LEFT JOIN seminar_user b ON (b.user_id = a.user_id AND b.seminar_id = :range_id)
                  WHERE perms IN ('autor', 'tutor', 'dozent') AND ISNULL(b.seminar_id)
                    AND (username LIKE CONCAT('%', :needle, '%')
                         OR Vorname LIKE CONCAT('%', :needle, '%')
                         OR Nachname LIKE CONCAT('%', :needle, '%'))
                  ORDER BY Nachname";
    } else {
        $query = "SELECT DISTINCT {$_fullname_sql['full_rev']} AS fullname, username, perms
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  LEFT JOIN user_inst ON (user_inst.user_id=auth_user_md5.user_id AND Institut_id = :range_id)
                  WHERE perms NOT IN ('user', 'admin', 'root') AND (inst_perms = 'user' OR inst_perms IS NULL)
                    AND (Vorname LIKE CONCAT('%', :needle, '%')
                         OR Nachname LIKE CONCAT('%', :needle, '%')
                         OR username LIKE CONCAT('%', :needle, '%'))
                  ORDER BY Nachname ";
    }

    $statement = DBManager::get()->prepare($query);
    $statement->bindParam(':range_id', $range_id);
    $statement->bindParam(':needle', $search_exp);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: false;
}

function checkExternDefaultForUser($user_id)
{
    $stmt = DBManager::get()->prepare("SELECT COUNT(*) as c FROM user_inst WHERE user_id = ?");
    $stmt->execute(array($user_id));
    $result = $stmt->fetchColumn();
    if ($result == 1) {
        $stmt = DBManager::get()->prepare("UPDATE user_inst SET externdefault = 1 WHERE user_id = ?");
        $stmt->execute(array($user_id));
    }
}
