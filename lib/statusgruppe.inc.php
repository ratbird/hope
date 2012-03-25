<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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
function MakeUniqueStatusgruppeID () {
    // baut eine ID die es noch nicht gibt

    $hash_secret = "kertoiisdfgz";
    $db=new DB_Seminar;
    $tmp_id=md5(uniqid($hash_secret));

    $db->query ("SELECT statusgruppe_id FROM statusgruppen WHERE statusgruppe_id = '$tmp_id'");
    if ($db->next_record())
        $tmp_id = MakeUniqueStatusgruppeID(); //ID gibt es schon, also noch mal
    return $tmp_id;
}


// Funktionen zum veraendern der Gruppen

function AddNewStatusgruppe ($new_statusgruppe_name, $range_id, $new_statusgruppe_size, $new_selfassign = 0, $new_doc_folder = false, $statusgruppe_id = false) {

    if (!$statusgruppe_id) {
        $statusgruppe_id = MakeUniqueStatusgruppeID();
    }

    $mkdate = time();
    $chdate = time();
    $db=new DB_Seminar;
    $db->query ("SELECT position FROM statusgruppen WHERE range_id = '$range_id' ORDER BY position DESC");
    if ($db->next_record()) {
        $position = $db->f("position")+1;
    } else {
        $position = "1";
    }
    $calendar_group = Request::get('is_cal_group') ? 1 : 0;
    $db->query("INSERT INTO statusgruppen SET statusgruppe_id = '$statusgruppe_id', name = '$new_statusgruppe_name', range_id= '$range_id', position='$position', size = '$new_statusgruppe_size', selfassign = '$new_selfassign', mkdate = '$mkdate', chdate = '$chdate', calendar_group = $calendar_group");
    if($db->affected_rows() && $new_doc_folder){
        create_folder(mysql_escape_string(_("Dateiordner der Gruppe:") . ' ' . $new_statusgruppe_name), mysql_escape_string(_("Ablage für Ordner und Dokumente dieser Gruppe")), $statusgruppe_id, 15);
    }
    return $statusgruppe_id;
}

function CheckSelfAssign($statusgruppe_id) {
    $db=new DB_Seminar;
    $db->query ("SELECT selfassign FROM statusgruppen WHERE statusgruppe_id = '$statusgruppe_id'");
    if ($db->next_record()) {
        $tmp = $db->f(0);
    } else {
        $tmp = FALSE;
    }
    return $tmp;
}

function CheckSelfAssignAll($seminar_id) {
    $db = new DB_Seminar("SELECT SUM(selfassign), COUNT( IF( selfassign > 0, 1, NULL ) ) , MIN(selfassign) FROM statusgruppen WHERE range_id='$seminar_id' ");
    $db->next_record();
    $ret = array();
    if($db->f(2)) $ret[0] = true;
    if($db->f(1) && $db->f(0) == $db->f(1) * 2) $ret[1] = true;
    return $ret;
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
        if ($result = $db->query("SELECT 1 " .
                "FROM statusgruppen " .
                "WHERE range_id = (SELECT range_id " .
                                                    "FROM statusgruppen " .
                                                    "WHERE statusgruppe_id = ".$db->quote($statusgruppe_id).") " .
                        "AND selfassign = 2")->fetch()) {
            $flag = 2;
        } elseif ($result = $db->query("SELECT 1 " .
                "FROM statusgruppen " .
                "WHERE range_id = (SELECT range_id " .
                                                    "FROM statusgruppen " .
                                                    "WHERE statusgruppe_id = ".$db->quote($statusgruppe_id).") " .
                        "AND selfassign = 1")->fetch()) {
            $flag = 1;
        }
    }
    $db->exec("UPDATE statusgruppen " .
            "SET selfassign = ".$db->quote($flag)." " .
            "WHERE statusgruppe_id = ".$db->quote($statusgruppe_id));
    return $flag;
}

function SetSelfAssignAll ($seminar_id, $flag = false) {
    $db = DBManager::get();
    return $db->exec("UPDATE statusgruppen SET selfassign = '".(int)$flag."' WHERE range_id = ".$db->quote($seminar_id));
}

function SetSelfAssignExclusive ($seminar_id, $flag = false) {
    $db=new DB_Seminar;
    $db->query("UPDATE statusgruppen SET selfassign = '".($flag ? 2 : 1)."' WHERE  range_id = '$seminar_id' AND selfassign > 0");
    return $db->affected_rows();
}

function GetAllSelected ($range_id, $level = 0) {
    $db3=new DB_Seminar;
    $db3->query ("SELECT user_id, sg.statusgruppe_id FROM statusgruppen as sg LEFT JOIN statusgruppe_user USING(statusgruppe_id) WHERE range_id = '$range_id'");

    // WTF???
    if ($level == 0) {
        if ($db3->num_rows() == 0) return array();
    } else {
        if ($db3->num_rows() == 0) return FALSE;
    }

    $selected = array();
    $role_ids = array();

    while ($db3->next_record()) {
        $user_id = $db3->f('user_id');
        $statusgruppe = $db3->f('statusgruppe_id');

        if ($user_id != NULL) {
            $selected[$user_id] = true;
        }

        if (!$role_ids[$statusgruppe]) {
            $zw = GetAllSelected($statusgruppe, $level+1);
            if ($zw) {
                $selected += array_fill_keys($zw, true);
            }
            $role_ids[$statusgruppe] = true;
        }
    }

    return array_keys($selected);
}

function EditStatusgruppe ($new_statusgruppe_name, $new_statusgruppe_size, $edit_id, $new_selfassign="0", $new_doc_folder = false) {

    $chdate = time();
    $db=new DB_Seminar;
    $calendar_group = Request::get('is_cal_group') ? 1 : 0;
    $db->query("UPDATE statusgruppen SET name = '$new_statusgruppe_name', size = '$new_statusgruppe_size', chdate = '$chdate', selfassign = '$new_selfassign', calendar_group = $calendar_group WHERE statusgruppe_id = '$edit_id'");
    if($new_doc_folder){
        create_folder(mysql_escape_string(_("Dateiordner der Gruppe:") . ' '. $new_statusgruppe_name), mysql_escape_string(_("Ablage für Ordner und Dokumente dieser Gruppe")), $edit_id, 15);
    }
}

function InsertPersonStatusgruppe ($user_id, $statusgruppe_id) {
    $position = CountMembersPerStatusgruppe($statusgruppe_id)+1;
    $db=new DB_Seminar; 
    $db->query("SELECT * FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$user_id'");
    if (!$db->next_record()) {
        $db->query("INSERT INTO statusgruppe_user SET statusgruppe_id = '$statusgruppe_id', user_id = '$user_id', position = '$position'");
        MakeDatafieldsDefault($user_id, $statusgruppe_id);      
        $writedone = TRUE;
    } else {
        $writedone = FALSE;
    }
    return $writedone;
}

function MakeDatafieldsDefault($user_id, $statusgruppe_id, $default = 'default_value') {
    global $auth;
    $fields = DataFieldStructure::getDataFieldStructures('userinstrole');

    $db = new DB_Seminar("SELECT * FROM datafields WHERE object_type = 'userinstrole'");
    $db2 = new DB_Seminar();
    $cur = time();
    while ($db->next_record()) {
        if ($fields[$db->f('datafield_id')]->editAllowed($auth->auth['perm'])) {
            $db2->query("REPLACE INTO datafields_entries (datafield_id, range_id, content, mkdate, chdate, sec_range_id) VALUES ('".$db->f('datafield_id')."', '$user_id', '$default', '$cur', '$cur', '$statusgruppe_id')");
        }
    }
}

// find all "statusgruppen_ids" which are connected to a certain range_id
function getStatusgruppenIDS($range_id)
{

    $db=new DB_Seminar;
    $db->query("SELECT * FROM statusgruppen WHERE range_id = '$range_id'");
    while ($db->next_record())
    {
        $ids[] = $db->f("statusgruppe_id");
    }
    return $ids;
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

function RemovePersonStatusgruppe ($username, $statusgruppe_id) {

    $user_id = get_userid($username);
    $db=new DB_Seminar;
    $db->query("SELECT position FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$user_id'");
    if ($db->next_record())
        $position = $db->f("position");
    $db->query("DELETE FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$user_id'");

    // Neusortierung
    $db->query("SELECT * FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id' AND position > '$position'");
    while ($db->next_record()) {
        $new_position = $db->f("position")-1;
        $alt_user_id = $db->f("user_id");
        $db2=new DB_Seminar;
        $db2->query("UPDATE statusgruppe_user SET position =  '$new_position' WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$alt_user_id'");
    }
}

function RemovePersonFromAllStatusgruppen ($username) {

    $user_id = get_userid($username);
    $db=new DB_Seminar;
    $db->query("DELETE FROM statusgruppe_user WHERE user_id = '$user_id'");
    $result = $db->affected_rows();
    return $result;
}

function RemovePersonStatusgruppeComplete ($username, $range_id) {

    $result = getAllStatusgruppenIDS($range_id);
    $user_id = get_userid($username);

    if (is_array($result))
    foreach($result as $range_id)
    {
        $db=new DB_Seminar;
        $db->query("SELECT DISTINCT statusgruppe_user.statusgruppe_id FROM statusgruppe_user LEFT JOIN statusgruppen USING(statusgruppe_id) WHERE range_id = '$range_id' AND user_id = '$user_id'");
        while ($db->next_record()) {
            RemovePersonStatusgruppe($username, $db->f("statusgruppe_id"));
        }
    }
}

function DeleteStatusgruppe ($statusgruppe_id) {

    $db=new DB_Seminar;
    $db->query("SELECT position, range_id FROM statusgruppen WHERE statusgruppe_id = '$statusgruppe_id'");
    if ($db->next_record()) {
        $position = $db->f("position");
        $range_id = $db->f("range_id");
    }

    // get all child-statusgroups and put them as a child of the vather, so they don't hang around without a parent
    $childs = getAllChildIDs($statusgruppe_id);
    if (is_array($childs)) {
        foreach ($childs as $id) {
            $db->query("UPDATE statusgruppen SET range_id = '".$range_id."' WHERE statusgruppe_id = '$id'");
        }
    }

    $db=new DB_Seminar;
    $db->query("DELETE FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id'");
    $db->query("DELETE FROM statusgruppen WHERE statusgruppe_id = '$statusgruppe_id'");

    // Eingetragene Datenfelder löschen
    $db->query("DELETE FROM datafields_entries WHERE range_id = '$statusgruppe_id'");

    // Neusortierung

    $db->query("SELECT * FROM statusgruppen WHERE range_id = '$range_id' AND position > '$position'");
    while ($db->next_record()) {
        $new_position = $db->f("position")-1;
        $statusgruppe_id = $db->f("statusgruppe_id");
        $db2=new DB_Seminar;
        $db2->query("UPDATE statusgruppen SET position =  '$new_position' WHERE statusgruppe_id = '$statusgruppe_id'");
    }
}

function MovePersonPosition ($username, $statusgruppe_id, $direction) {
    $user_id = get_userid($username);
    $db=new DB_Seminar;
    $db->query("SELECT position FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$user_id'");
    if ($db->next_record()) {
        if ($direction == "up")
            $position = $db->f("position")-1;
        if ($direction == "down")
            $position = $db->f("position")+1;
        $position_alt = $db->f("position");
        $db->query("UPDATE statusgruppe_user SET position =  '$position_alt' WHERE statusgruppe_id = '$statusgruppe_id' AND position = '$position'");
        $db->query("UPDATE statusgruppe_user SET position =  '$position' WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$user_id'");
    }
}

/**
 * Sorts a person in statusgroup after the an other person
 * @param $user_id the user to be sorted
 * @param $after to user after which is sorted in
 * @param $role_id the id of the statusgroup the sorting is taking place
 */
function SortPersonInAfter($user_id, $after, $role_id) {
    $db = new DB_Seminar($query = "SELECT user_id, position FROM statusgruppe_user WHERE (user_id = '$user_id' OR user_id = '$after') AND statusgruppe_id = '$role_id'");

    while ($db->next_record()) {
        $pos[$db->f('user_id')] = $db->f('position');
    }

    $query = "UPDATE statusgruppe_user SET position = position + 1 WHERE statusgruppe_id = '$role_id' AND position > ".$pos[$after];
    $db->query($query);

    $query = "UPDATE statusgruppe_user SET position = ".($pos[$after]+1)." WHERE statusgruppe_id = '$role_id' AND user_id = '$user_id'";
    $db->query($query);
    $query = "UPDATE statusgruppe_user SET position = position - 1 WHERE statusgruppe_id = '$role_id' AND position > ".$pos[$user_id];
    $db->query($query);
}


function DeleteAllStatusgruppen ($range_id) {

    $db=new DB_Seminar;
    $i = 0;
    $db->query("SELECT statusgruppe_id FROM statusgruppen WHERE range_id = '$range_id'");
    while ($db->next_record()) {
        $statusgruppe_id = $db->f("statusgruppe_id");
        DeleteStatusgruppe($statusgruppe_id);
        $i++;
    }
    return $i;
}


function moveStatusgruppe($role_id, $up_down = 'up') {
    $db = new DB_Seminar();

    $db->query("SELECT range_id, position FROM statusgruppen WHERE statusgruppe_id = '$role_id'");

    if ($db->next_record()) {
        $pos = $db->f('position');
        $range_id = $db->f('range_id');

        if ($up_down == 'up') {
            $pos_a = $pos;
            $pos_b = $pos-1;
        } else {
            $pos_a = $pos;
            $pos_b = $pos+1;
        }

        $db->query("UPDATE statusgruppen SET position = $pos_a WHERE range_id = '$range_id' AND position = $pos_b");
        $db->query("UPDATE statusgruppen SET position = $pos_b WHERE statusgruppe_id = '$role_id'");
    }
}


function SortStatusgruppe($insert_after, $insert_id) {
    $db = new DB_Seminar("SELECT range_id, position FROM statusgruppen WHERE statusgruppe_id = '$insert_after'");
    $db->next_record();
    $range_id = $db->f('range_id');
    $pos = $db->f('position');

    $db->query("UPDATE statusgruppen SET position = position + 1 WHERE range_id = '$range_id' AND position > $pos");
    $db->query("UPDATE statusgruppen SET position = ".($pos + 1)." WHERE statusgruppe_id = '$insert_id'");

    resortStatusgruppeByRangeId($range_id);
}

function SubSortStatusgruppe($insert_vather, $insert_daughter) {
    if ($insert_vather == '' || $insert_daughter == '') return FALSE;
    if (isVatherDaughterRelation($insert_vather, $insert_daughter)) return FALSE;

    $db = new DB_Seminar();

    $pos = -1;
    $db->query("SELECT position FROM statusgruppen WHERE range_id = '$insert_daughter' ORDER BY position ASC");

    while ($db->next_record()) {
        $pos = $db->f('position');
    }
    $pos++;

    $db->query("UPDATE statusgruppen SET position = $pos, range_id = '$insert_daughter' WHERE statusgruppe_id = '$insert_vather'");

    return TRUE;
}


function resortStatusgruppeByRangeId($range_id) {
    $db = new DB_Seminar("SELECT statusgruppe_id FROM statusgruppen WHERE range_id = '$range_id' ORDER BY position ASC");
    while ($db->next_record()) {
        $zw[] = $db->f('statusgruppe_id');
    }

    if (is_array($zw))
    foreach ($zw as $pos => $id) {
        $db->query("UPDATE statusgruppen SET position = $pos WHERE statusgruppe_id = '$id'");
    }
}

function SwapStatusgruppe ($statusgruppe_id) {

    $db=new DB_Seminar;
    $db->query("SELECT * FROM statusgruppen WHERE statusgruppe_id = '$statusgruppe_id'");
    if ($db->next_record()) {
        $current_position = $db->f("position");
        $range_id = $db->f("range_id");
        $next_position = $current_position + 1;
        $db2=new DB_Seminar;
        $db2->query("UPDATE statusgruppen SET position =  '$next_position' WHERE statusgruppe_id = '$statusgruppe_id'");
        $db2->query("UPDATE statusgruppen SET position =  '$current_position' WHERE range_id = '$range_id' AND position = '$next_position' AND statusgruppe_id != '$statusgruppe_id'");
    }
}

function CheckStatusgruppe ($range_id, $name) {

    $db=new DB_Seminar;
    $db->query("SELECT * FROM statusgruppen WHERE range_id = '$range_id' AND name = '$name'");
    if ($db->next_record()) {
        $exists = $db->f("statusgruppe_id");
    } else {
        $exists = FALSE;
    }
    return $exists;
}

function CheckUserStatusgruppe ($group_id, $object_id) {
    $db=new DB_Seminar;
    $db->query("SELECT * FROM statusgruppe_user WHERE statusgruppe_id = '$group_id' AND user_id = '$object_id'");
    if ($db->next_record()) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function GetRangeOfStatusgruppe ($statusgruppe_id) {
    $db=new DB_Seminar;
    $has_parent = true;
    $group = $statusgruppe_id;
    while ($has_parent) {
        $db->query("SELECT range_id FROM statusgruppen WHERE statusgruppe_id='$group'");
        if ($db->next_record()) {
            $group = $db->f('range_id');
        } else {
            $has_parent = false;
        }
    }

    return $group;
}


/**
* get all statusgruppen for one user and one range
*
* get all statusgruppen for one user and one range
*
* @access   public
* @param    string  $course_id
* @param    string  $user_id
* @return   array   ( statusgruppe_id => name)
*/
function GetGroupsByCourseAndUser($course_id, $user_id) {
    $ret = array();
    $st = DbManager::get()->prepare("SELECT a.statusgruppe_id, a.name
                                     FROM statusgruppen a
                                     INNER JOIN statusgruppe_user b USING(statusgruppe_id)
                                     WHERE user_id = ? AND a.range_id = ? ORDER BY a.position ASC");
    if ($st->execute(array($user_id,$course_id))) {
        $ret = array_map('array_shift', $st->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP));
    }
    return $ret;
}

function getOptionsOfStGroups ($userID) {
    $db = new DB_Seminar();
    $db->query("SELECT statusgruppe_id,visible,inherit FROM statusgruppe_user WHERE user_id='$userID'");
    while ($db->next_record())
        $ret[$db->f('statusgruppe_id')] = array('visible' => $db->f('visible') == 1, 'inherit' => $db->f('inherit') == 1);
    return $ret;
}


// visible and inherit must be '0' or '1'
function setOptionsOfStGroup ($groupID, $userID, $visible, $inherit='') {
    $db = new DB_Seminar();
    $db->query("SELECT inherit, visible FROM statusgruppe_user WHERE statusgruppe_id='$groupID' AND user_id='$userID'");
    if ($db->next_record()) {
        $query = "REPLACE INTO statusgruppe_user SET statusgruppe_id='$groupID', user_id='$userID'";
        $query .= ", visible='" . ($visible === '' ? $db->f('visible') : ($visible == '1' ? 1 : 0)) . "'";
        $query .= ", inherit='" . ($inherit === '' ? $db->f('inherit') : ($inherit == '1' ? 1 : 0)) . "'";
        $db->query($query);
    }
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
function CountMembersStatusgruppen ($range_id) {

    $db = new DB_Seminar();
    $ids = getAllStatusgruppenIDS($range_id);
    $db->query($query = "SELECT COUNT(DISTINCT user_id) AS count FROM statusgruppen
            LEFT JOIN statusgruppe_user USING(statusgruppe_id)
            WHERE statusgruppen.statusgruppe_id IN ('". implode("', '", $ids) ."')");

    $db->next_record();
    return $db->f("count");
}

function CountMembersPerStatusgruppe ($group_id) {
    $db = new DB_Seminar();
    $db->query("SELECT COUNT(user_id) AS count FROM statusgruppen
                            LEFT JOIN statusgruppe_user USING(statusgruppe_id)
                            WHERE statusgruppen.statusgruppe_id = '$group_id'");
    $db->next_record();
    return $db->f("count");
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
    $db = new DB_Seminar();

    $db->query("SELECT s.name, s.statusgruppe_id
            FROM statusgruppe_user su
            LEFT JOIN statusgruppen s
            ON (su.statusgruppe_id = s.statusgruppe_id)
            WHERE s.statusgruppe_id IN ('".join("','", $group_list)."') AND su.user_id='{$user_id}'");

    $user_groups = array();

    while ($db->next_record()) {
        $user_groups[] = $db->f('statusgruppe_id');
    }

    return (sizeof($user_groups)) ? $user_groups : false;
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
    $db = new DB_Seminar();
    $db->query("SELECT * FROM statusgruppen WHERE range_id = '$parent' ORDER BY position ASC");
    
    if ($db->num_rows() == 0) return false; 
    
    $childs = array();

    while ($db->next_record()) {
        $user_there = false;
        if ($check_user) {
            $db2 = new DB_Seminar("SELECT * FROM statusgruppe_user WHERE user_id = '$check_user' AND statusgruppe_id = '".$db->f('statusgruppe_id')."'");
            if ($db2->num_rows() > 0) {
                $user_there = true;
                $db2->next_record();
                $visible = $db2->f('visible');
            }
        }

        $kids = GetAllStatusgruppen($db->f('statusgruppe_id'), $check_user, $exclude);

        $user_in_child = false;
        if ($check_user && is_array($kids)) {
            foreach ($kids as $kid) {
                if ($kid['user_there'] || $kid['user_in_child']) {
                    $user_in_child = true;
                }
            }
        }

        if (($check_user && $exclude && ($user_in_child || $user_there)) || !$check_user || (!$exclude && $check_user)) {           
            $childs[$db->f('statusgruppe_id')] = array (
                    /*
                    'name' => $db->f('name'),
                    'size' => $db->f('size'),                   
                    'selfassign' => $db->f('selfassign'),
                    'position' => $db->f('position'),
                    */
                    'role' => Statusgruppe::getFromArray($db->Record),
                    'visible' => $visible,
                    'user_there' => $user_there,
                    'user_in_child' => $user_in_child,
                    'child' => $kids
                    );
        }
    }   

    return (is_array($childs)) ? $childs : FALSE;
}


function GetStatusgruppeName ($group_id) {
    $db = new DB_Seminar();
    $db->query("SELECT name FROM statusgruppen WHERE statusgruppe_id='$group_id' ");

    if ($db->next_record())
        return $db->f("name");
    else
        return FALSE;
}

function GetStatusgruppeLimit ($group_id) {
    $db = new DB_Seminar();
    $db->query("SELECT size FROM statusgruppen WHERE statusgruppe_id='$group_id' ");

    if ($db->next_record())
        return $db->f("size");
    else
        return FALSE;
}

function CheckStatusgruppeFolder($group_id){
    $db = new DB_Seminar("SELECT folder_id FROM folder WHERE range_id='$group_id'");
    $db->next_record();
    return $db->f(0);
}

function CheckStatusgruppeMultipleAssigns($range_id){
    $ret = array();
    $db = new DB_Seminar("
    SELECT count( statusgruppen.statusgruppe_id ) as count , user_id, group_concat( name ) as gruppen
    FROM statusgruppen
    INNER JOIN statusgruppe_user
    USING ( statusgruppe_id )
    WHERE range_id = '$range_id'
    AND selfassign = 2
    GROUP BY user_id HAVING count > 1");
    while($db->next_record()){
        $ret[] = $db->Record;
    }
    return $ret;
}

function isVatherDaughterRelation($vather, $daughter) {
    $db = new DB_Seminar();
    $childs = getAllChildIDs($vather);
    if (in_array($daughter, array_keys($childs)) === TRUE) {
        return TRUE;
    }
    return FALSE;
}

function getAllChildIDs($range_id) {
    $db = new DB_Seminar();
    $db->query("SELECT name, statusgruppe_id FROM statusgruppen WHERE range_id = '$range_id'");

    $zw = array();
    if ($db->num_rows() == 0) {
        return $zw;
    }

    while ($db->next_record()) {
        $zw[$db->f('statusgruppe_id')] = $db->f('name');
        $zw =  array_merge((array)$zw, (array)getAllChildIDs($db->f('statusgruppe_id')));
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

function getPersonsForRole($role_id) {
    global $_fullname_sql;

    $persons = array();
    
    $db = new DB_Seminar();
    $db->query ("SELECT statusgruppe_user.user_id, " . $_fullname_sql['full_rev'] . " AS fullname , username, position FROM statusgruppe_user LEFT JOIN auth_user_md5 USING(user_id) LEFT JOIN user_info USING (user_id) WHERE statusgruppe_id = '$role_id' ORDER BY position ASC");
    while ($db->next_record()) {
        $persons[$db->f('user_id')] = array (
            'position' => $db->f('position'),
            'username' => $db->f('username'),
            'fullname' => $db->f('fullname')
        );
    }

    return $persons;
}

function sortStatusgruppeByName($statusgruppe_id) {
    $position = 1;
    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    // Zuerst Mitglieder der Gruppe nach Nachnamen sortiert aus DB holen
    $sql =      "SELECT * FROM statusgruppe_user su                
        LEFT JOIN auth_user_md5 a ON a.user_id=su.user_id 
        WHERE statusgruppe_id = '".$statusgruppe_id."' 
        ORDER BY a.Nachname";
    $db->query($sql);
    while ($db->next_record()) {
        // Positionierung neu vergeben
        $sql =  "UPDATE statusgruppe_user 
            SET position=$position 
            WHERE user_id = '".$db->f("user_id")."' 
            AND statusgruppe_id = '".$statusgruppe_id."' ";
        $position++;
        $db2->query($sql);
    }
}

function getPersons($range_id, $type = false) {
    global $_fullname_sql, $_range_type;

    $bereitszugeordnet = GetAllSelected($range_id);
    
    if ($type == 'sem') {
        $query = "SELECT seminar_user.user_id, username, " . $_fullname_sql['full_rev'] .
                " AS fullname, perms FROM seminar_user " .
                " LEFT JOIN auth_user_md5 USING(user_id) " .
                " LEFT JOIN user_info USING (user_id) " .
                " WHERE Seminar_id = '$range_id' ORDER BY Nachname ASC";
    } else if ($type == 'inst') {
        $query = "SELECT a.user_id, username, " . $_fullname_sql['full_rev'] .
            " AS fullname, inst_perms, perms FROM seminar_inst d LEFT JOIN user_inst a USING(Institut_id) ".
            " LEFT JOIN auth_user_md5  b USING(user_id) " .
            " LEFT JOIN user_info USING (user_id) ".
            " LEFT JOIN seminar_user c ON (c.user_id=a.user_id AND c.seminar_id='$range_id')  ".
            " WHERE d.seminar_id = '$range_id' AND a.inst_perms IN ('tutor','dozent') AND ISNULL(c.seminar_id) " .
            " GROUP BY a.user_id ORDER BY Nachname";        
    } else {
        $query = "SELECT user_inst.user_id, username, " . $_fullname_sql['full_rev'] .  
            " AS fullname, inst_perms AS perms FROM user_inst ".
            " LEFT JOIN auth_user_md5 USING(user_id) ".
            " LEFT JOIN user_info USING (user_id) ".
            " WHERE Institut_id = '$range_id' AND inst_perms != 'user' AND inst_perms != 'admin' ORDER BY Nachname ASC";
    }

    $db = new DB_Seminar();
    $db->query($query);
    while ($db->next_record()) {
        if (in_array($db->f("user_id"), $bereitszugeordnet)) { 
            $hasgroup = true;
        } else {
            $hasgroup = false;
        }

        $all_persons[$db->f('user_id')] = array (
            'fullname' => $db->f('fullname'),
            'username' => $db->f('username'),
            'perms' => $db->f('perms'),
            'hasgroup' => $hasgroup
        );
    }

    return $all_persons;
}

function getSearchResults ($search_exp, $range_id, $type = 'inst') { 
    global $SessSemName, $_fullname_sql;

    $ret = '';
    $db=new DB_Seminar;
    if ($type == "sem") {
        $query = "SELECT a.user_id, username, " . $_fullname_sql['full_rev'] ." AS fullname, perms FROM auth_user_md5 a ".      
        "LEFT JOIN user_info USING (user_id) LEFT JOIN seminar_user b ON (b.user_id=a.user_id AND b.seminar_id='$range_id')  ".
        "WHERE perms IN ('autor','tutor','dozent') AND ISNULL(b.seminar_id) AND ".
        "(username LIKE '%$search_exp%' OR Vorname LIKE '%$search_exp%' OR Nachname LIKE '%$search_exp%') ".
        "ORDER BY Nachname";
    } else {
        $query = "SELECT DISTINCT auth_user_md5.user_id, " . $_fullname_sql['full_rev'] ." AS fullname, username, perms ".
        "FROM auth_user_md5 LEFT JOIN user_info USING (user_id) LEFT JOIN user_inst ON user_inst.user_id=auth_user_md5.user_id AND Institut_id = '$inst_id' ".
        "WHERE perms !='root' AND perms !='admin' AND perms !='user' AND (user_inst.inst_perms = 'user' OR user_inst.inst_perms IS NULL) ".
        "AND (Vorname LIKE '%$search_exp%' OR Nachname LIKE '%$search_exp%' OR username LIKE '%$search_exp%') ORDER BY Nachname ";
    }

    $db->query($query); // results all users which are not in the seminar
    if (!$db->num_rows()) {     
        return false;
    } else {        
        while ($db->next_record()) {
            $ret[] = array(
                'username' => $db->f('username'),
                'fullname' => $db->f('fullname'),
                'perms' => $db->f('perms')
            );          
        }       
        return $ret;
    }   
}

function checkExternDefaultForUser($user_id) {
    $stmt = DBManager::get()->prepare("SELECT COUNT(*) as c FROM user_inst WHERE user_id = ?");
    $stmt->execute(array($user_id));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['c'] == 1) {
        $stmt = DBManager::get()->prepare("UPDATE user_inst SET externdefault = 1 WHERE user_id = ?");
        $stmt->execute(array($user_id));
    }
}
