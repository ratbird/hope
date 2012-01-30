<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* resourcesFunc.php
*
* functions for resources
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       resourcesFunc.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// resourcesFunc.php
// Funktionen der Ressourcenverwaltung
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/ResourceObjectPerms.class.php";


/*
* allowCreateRooms
*
* gets the status, if an user is allowed to create new rooms(objects)
*
* @param    string  the user_id, if not set, the actual user's id is used
* @return   boolean
*
**/
function allowCreateRooms($user_id='') {
    global $user, $perm;

    if (!$user_id)
        $user_id = $user->id;

    switch ($GLOBALS["RESOURCES_ALLOW_CREATE_ROOMS"]) {
        case 1:
            if ($perm->have_perm("tutor"))
                return TRUE;
            else
                return FALSE;
        break;
        case 2:
            if ($perm->have_perm("admin"))
                return TRUE;
            else
                return FALSE;
        break;
        case 3:
            if (getGlobalPerms($user_id) == "admin")
                return TRUE;
            else
                return FALSE;
        break;

    }
}

/*
* getLockPeriod
*
* gets a lock-period, if one is active (only the first lock period that matches will be returned)
*
* @param    int the timestamp, if left, the actual time
* @return   array   the start- and end-timestamp
*
**/
function getLockPeriod($type, $timestamp1='', $timestamp2='') {
    static $cache;

    if ($cache[$type][$timestamp1 / 60][$timestamp2 / 60]) {
        return $cache[$type][$timestamp1 / 60][$timestamp2 / 60];
    }

    $db = new DB_Seminar;

    if (!$timestamp1)
        $timestamp1 = time();
    if (!$timestamp2)
        $timestamp2 = time();

    if (((!$GLOBALS['RESOURCES_LOCKING_ACTIVE']) && ($type == "edit")) || ((!$GLOBALS['RESOURCES_ASSIGN_LOCKING_ACTIVE']) && ($type == "assign"))) {
        $cache[$type][$timestamp1 / 60][$timestamp2 / 60] = FALSE;
        return FALSE;
    } else {
        if (($timestamp1) && ($timestamp2))
            $query = sprintf ("SELECT lock_id, lock_begin, lock_end FROM resources_locks WHERE type = '%s' AND  lock_begin <= '%s' AND lock_end >= '%s' ", $type, $timestamp1, $timestamp1);
        else
            $query = sprintf ("SELECT lock_id, lock_begin, lock_end FROM resources_locks WHERE type = '%s' AND "
                     ."((lock_begin <= %s AND lock_end > %s) OR (lock_begin >=%s AND lock_end <= %s) OR (lock_begin <= %s AND lock_end >= %s) OR (lock_begin < %s AND lock_end >= %s)) ",
                     $type, $timestamp1, $timestamp1, $timestamp1, $timestamp2, $timestamp1, $timestamp2, $timestamp2, $timestamp2);
        $db->query($query);
        $db->next_record();
        if ($db->nf()) {
            $arr[0] = $db->f("lock_begin");
            $arr[1] = $db->f("lock_end");
            $arr[2] = $db->f("lock_id");
            $cache[$type][timestamp1 / 60][$timestamp2 / 60] = $arr;
            return $arr;
        } else {
            $cache[$type][timestamp1 / 60][$timestamp2 / 60] = FALSE;
            return FALSE;
        }
    }
}

/**
* isLockPeriod
*
* determines, if a lock period could be found in resources_locks and locking is active
*
* @param    int the timestamp, if left, the actual time
* @return   boolean true or false
*
**/
function isLockPeriod($type, $timestamp='') {
    static $cache;

    if ($cache[$type][$timestamp / 60]) {
        return $cache[$type][$timestamp / 60];
    }

    $db = new DB_Seminar;

    if (!$timestamp)
        $timestamp = time();

    if (((!$GLOBALS['RESOURCES_LOCKING_ACTIVE']) && ($type == "edit")) || ((!$GLOBALS['RESOURCES_ASSIGN_LOCKING_ACTIVE']) && ($type == "assign"))) {
        $cache[$type][$timestamp % 60] = FALSE;
        return FALSE;
    } else {
        $query = sprintf ("SELECT * FROM resources_locks WHERE lock_begin <= '%s' AND lock_end >= '%s' AND type = '%s'", $timestamp, $timestamp, $type);
        $db->query($query);
        if ($db->nf()) {
            $cache[$type][$timestamp % 60] = TRUE;
            return TRUE;
        } else {
            $cache[$type][$timestamp % 60] = FALSE;
            return FALSE;
        }
    }
}

/**
* changeLockableRecursiv
*
* sets the lockale option for all childs to state
*
* @param    string  the key for the resource object
* @param    boolean if set, all childs will be lockable
*
**/
function changeLockableRecursiv ($resource_id, $state) {
    global $resources_data;
    $db = new DB_Seminar;

    $query = sprintf ("UPDATE resources_objects SET lockable = '%s' WHERE resource_id = '%s' ", $state, $resource_id);
    $db->query($query);

    $query = sprintf ("SELECT resource_id FROM resources_objects WHERE parent_id = '%s' ", $resource_id);
    $db->query($query);
    while ($db->next_record()) {
        changeLockableRecursiv ($db->f("resource_id"), $state);
    }
}


/*
* getGlobalPerms
*
* this Funktion get the globals perms, the given user has in the
* resources-management
*
* @param    string  the user_id
* @return   string  the perms-string
*
**/
function getGlobalPerms($user_id) {
    static $cache;
    global $perm;


    if (!$user_id){
        $user_id = $GLOBALS['user']->id;
    }

    if ($cache[$user_id])
        return $cache[$user_id];

    $db = new DB_Seminar;

    if (!$perm->have_perm("root")) {
        $db->query("SELECT user_id, perms FROM resources_user_resources WHERE user_id='$user_id' AND resource_id = 'all' ");
        if ($db->next_record() && $db->f("perms"))
            $res_perm = $db->f("perms");
        else
            $res_perm = "autor";
    } else
        $res_perm = "admin";

    $cache[$user_id] = $res_perm;
    return $res_perm;
}

/*
* getGlobalPerms
*
* this Funktion creates an fully formatted output after changing/updating assigns
*
* @param    array   the result array, contains all informations about the last operation(s)
* @param    string  the mode of returning: "good" (all booked resources), "bad" (all not booked resources) or "both"
* @return   string  the formatted message, ready for using it in msg.inc.php
*
**/
function getFormattedResult($result, $mode="bad", $bad_message_text = '', $good_message_text = '') {
    //extract the overlaps (bad results) and locks
    if (is_array($result)) {
        $overlaps=FALSE;
        foreach ($result as $key=>$val)
            if ($val["overlap_assigns"] == TRUE) {
                $overlaps[] = array("resource_id"=>$val["resource_id"], "overlap_assigns"=>$val["overlap_assigns"]);
                foreach ($val["overlap_assigns"] as $val2)
                    if ($val2["lock_id"])
                        $locks[$val2["lock_id"]] = array ("begin" => $val2["lock_begin"], "end" => $val2["lock_end"]);
            }
        if ($locks)
            sort ($locks);
    } else
        return FALSE;

    //extract the succesfully booked roomes
    foreach ($result as $key=>$val)
        if (!is_array($val["overlap_assigns"]))
            $rooms_id[$val["resource_id"]]=TRUE;

    //create bad message
    if ((is_array($overlaps)) && (($mode == "bad") || ($mode == "booth"))) {
        $i=0;
        $bad_message = "error§"._("Folgende gew&uuml;nschte Raumbelegungen &uuml;berschneiden sich mit bereits vorhandenen Belegungen. Bitte &auml;ndern Sie die R&auml;ume oder Zeiten!");
        //the overlaps (show only the earliest here, plus a message when more)
        foreach ($overlaps as $val) {
            $resObj = ResourceObject::Factory($val["resource_id"]);
            $bad_message.="<br><font size=\"-1\" color=\"black\">".htmlReady($resObj->getName()).": ";
            //show the first overlap
            list(, $val2) = each($val["overlap_assigns"]);
            $bad_message.=date("d.m, H:i",$val2["begin"])." - ".date("H:i",$val2["end"]);
            if (sizeof($val["overlap_assigns"]) >1)
                $bad_message.=", ... (".sprintf (_("und %s weitere &Uuml;berschneidungen"), (sizeof($val["overlap_assigns"])-1)).")";
            $bad_message.= ", ".$resObj->getFormattedLink($val2["begin"], _("Raumplan anzeigen"));
            $i++;
        }
        $bad_message.="</font>";
        if ($locks) {
            $bad_message.="<br><font size=\"+0\" color=\"red\">"._("Die gew&uuml;nschten Belegungen kollidieren mit folgenden Sperrzeiten:")."</font>";
            $bad_message.="<br><font size=\"-1\" color=\"black\">";
            foreach ($locks as $val) {
                $bad_message.=date("d.m.Y, H:i",$val["begin"])." - ".date("d.m.Y, H:i",$val["end"])."<br>";
            }
            $bad_message.="</font>";
        }
        $bad_message.="§";
    }


    //create good message
    if ((is_array($rooms_id)) && (($mode == "good") || ($mode == "booth"))) {
        $i=0;
        foreach ($rooms_id as $key=>$val) {
            if ($key) {
                $resObj = ResourceObject::Factory($key);
                if ($i)
                    $rooms_booked.=", ";
                $rooms_booked.= $resObj->getFormattedLink();
                $i++;
            }
        }

    if ($rooms_booked)
        if ($i == 1)
            $good_message.= sprintf ("msg§"._("Die Belegung des Raumes %s wurde in die Ressourcenverwaltung &uuml;bernommen.")."§", $rooms_booked);
        elseif ($i)
            $good_message.= sprintf ("msg§"._("Die Belegung der R&auml;ume %s wurden in die Ressourcenverwaltung &uuml;bernommen.")."§", $rooms_booked);
    }

    if ($mode == "bad")
        return $bad_message;
    if ($mode == "good")
        return $good_message;
    if ($mode == "booth")
        return $bad_message.$good_message;
}

/*****************************************************************************
a quick function to get the resource_id (only rooms!) for a assigned date
/*****************************************************************************/

function getDateAssigenedRoom($date_id){
    $db=new DB_Seminar;
    $query = sprintf ("SELECT resources_assign.resource_id FROM resources_assign LEFT JOIN resources_objects USING (resource_id) LEFT JOIN resources_categories USING (category_id) WHERE assign_user_id = '%s' AND resources_categories.is_room = 1 ", $date_id);
    $db->query($query);
    if ($db->next_record())
        return $db->f("resource_id");
    else
        return FALSE;
}

/*****************************************************************************
a quick function to get a name from a resources object
/*****************************************************************************/

function getResourceObjectName($id){
    $db=new DB_Seminar;
    $query = sprintf ("SELECT name FROM resources_objects WHERE resource_id = '%s'", $id);
    $db->query($query);
    if ($db->next_record())
        return $db->f("name");
    else
        return FALSE;
}

/*****************************************************************************
a quick function to get a category from a resources object
/*****************************************************************************/

function getResourceObjectCategory($id){
    $db=new DB_Seminar;
    $query = sprintf ("SELECT category_id FROM resources_objects WHERE resource_id = '%s'", $id);
    $db->query($query);
    if ($db->next_record())
        return $db->f("category_id");
    else
        return FALSE;
}

function getDateRoomRequest($termin_id) {
    return RoomRequest::existsByDate($termin_id);
}

function getSeminarRoomRequest($seminar_id) {
    return RoomRequest::existsByCourse($seminar_id);
}


function getMyRoomRequests($user_id = '', $semester_id = null, $only_not_closed = true, $single_request = null) {
    global $user, $perm, $RELATIVE_PATH_RESOURCES;

    require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");


    $db = DBManager::get();

    $requests = array();

    if (!$user_id)
        $user_id = $user->id;
    if ($only_not_closed) {
        $criteria = ' closed = 0 ';
    } else {
        $criteria = ' 1 ';
    }
    if($single_request){
        $criteria .= " AND rr.request_id= " . $db->quote($single_request);
    } elseif ($semester_id){
        $semester = Semester::find($semester_id);
        $sem_criteria = ' BETWEEN ' . (int)$semester['beginn'] . ' AND ' . (int)$semester['ende'];
    }
    $query  = "SELECT request_id, closed, rr.resource_id "
            . "FROM resources_requests rr "
            . "WHERE %s ";
    $query1 = "SELECT request_id FROM resources_requests rr "
            . "INNER JOIN termine tt ON (tt.termin_id = rr.termin_id "
            . "AND tt.date > UNIX_TIMESTAMP() "
            . ($sem_criteria ? ' AND tt.date ' . $sem_criteria : '').") "
            . "WHERE rr.termin_id <> ''  AND %s";
    $query2 = "SELECT DISTINCT request_id FROM resources_requests rr "
            . "INNER JOIN termine t ON(rr.seminar_id = t.range_id "
            . "AND t.date_typ IN ".getPresenceTypeClause(). " AND t.date > UNIX_TIMESTAMP() "
            . ($sem_criteria ? ' AND t.date ' . $sem_criteria : '').") "
            . "WHERE  rr.termin_id = '' AND rr.metadate_id = '' AND %s ";
    $query3 = "SELECT DISTINCT request_id FROM resources_requests rr "
            . "INNER JOIN termine ttt ON (ttt.metadate_id = rr.metadate_id  "
            . "AND ttt.date > UNIX_TIMESTAMP() "
            . ($sem_criteria ? ' AND ttt.date ' . $sem_criteria : '').") "
            . "WHERE  rr.metadate_id <> '' AND %s ";

    if ((getGlobalPerms($user_id) == "admin")) {
        if ($rs = $db->query(sprintf($query, $criteria))) {
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                $requests[$row["request_id"]] = array("my_sem"=>TRUE, "my_res"=> strlen($row["resource_id"]) > 0, "closed"=>$row["closed"]);
                $requests[$row["request_id"]]["resource_id"] = $row['resource_id'];
            }
        }
        if ($rs = $db->query(sprintf($query1, $criteria))) {
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                $requests[$row["request_id"]]["have_times"] = 1;
            }
        }
        if ($rs = $db->query(sprintf($query2, $criteria))) {
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                $requests[$row["request_id"]]["have_times"] = 1;
            }
        }
        if ($rs = $db->query(sprintf($query3, $criteria))) {
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                $requests[$row["request_id"]]["have_times"] = 1;
            }
        }
    } else {
        //load all my resources
        $resList = new ResourcesUserRoomsList($user_id, FALSE, FALSE);
        $my_res = $resList->getRooms();

        if (sizeof($my_res)) {
            foreach ($my_res as $res_id => $dummy){
                $object_perms = ResourceObjectPerms::Factory($res_id, $user_id);
                if (!$object_perms->havePerm('tutor')){
                    unset($my_res[$res_id]);
                }
            }
        }

        if (sizeof($my_res)) {
            $res_criteria = $criteria . " AND rr.resource_id IN ('".join("','",array_keys($my_res))."')";
            if ($rs = $db->query(sprintf($query, $res_criteria))) {
                while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                    $requests[$row["request_id"]]["resource_id"] = $row['resource_id'];
                    $requests[$row["request_id"]]["my_res"] = TRUE;
                    $requests[$row["request_id"]]["closed"] = $row['closed'];
                }
            }
            if ($rs = $db->query(sprintf($query1, $res_criteria))) {
                while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                    $requests[$row["request_id"]]["have_times"] = 1;
                }
            }
            if ($rs = $db->query(sprintf($query2, $res_criteria))) {
                while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                    $requests[$row["request_id"]]["have_times"] = 1;
                }
            }
            if ($rs = $db->query(sprintf($query3, $res_criteria))) {
                while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                    $requests[$row["request_id"]]["have_times"] = 1;
                }
            }
            //load all my seminars
            $my_sems = search_administrable_seminars();
            if (sizeof($my_sems)) {
                $sem_criteria = $criteria . " AND rr.seminar_id IN " . "('".join("','",array_keys($my_sems))."')";
                if ($rs = $db->query(sprintf($query, $sem_criteria))) {
                    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                        $requests[$row["request_id"]]["resource_id"] = $row['resource_id'];
                        $requests[$row["request_id"]]["my_sem"] = TRUE;
                        $requests[$row["request_id"]]["closed"] = $row['closed'];
                    }
                }
                if ($rs = $db->query(sprintf($query1, $sem_criteria))) {
                    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                        $requests[$row["request_id"]]["have_times"] = 1;
                    }
                }
                if ($rs = $db->query(sprintf($query2, $sem_criteria))) {
                    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                        $requests[$row["request_id"]]["have_times"] = 1;
                    }
                }
                if ($rs = $db->query(sprintf($query3, $sem_criteria))) {
                    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                        $requests[$row["request_id"]]["have_times"] = 1;
                    }
                }
            }
        }
    }

    return $requests;
}


/*****************************************************************************
sort function to sort the AssignEvents by date
/*****************************************************************************/

function cmp_assign_events($a, $b){
    $start_a = $a->getBegin();
    $start_b = $b->getBegin();
    if($start_a == $start_b)
        return 0;
    if($start_a < $start_b)
        return -1;
    return 1;
}

/*****************************************************************************
sort function to sort the ResourceObject by name
/*****************************************************************************/
function cmp_resources($a, $b){
    $name_a = $a->getName();
    $name_b = $b->getName();
    if($name_a == $name_b)
        return 0;
    if($name_a < $name_b)
        return -1;
    return 1;
}

/*
* checkAvailableResources
*
* This Funktion searches for available resources for studip-objects (and users, too),
* but it only work's properly with studip-objects, because it didn't pay attention for
* inheritance of perms for a studip-user.
*
* @param    string  the obejct id
* @return   boolean true, if resources are found, otherwise false
*
**/
function checkAvailableResources($id) {
    $db = new DB_Seminar;

    //check if owner
    $db->query("SELECT resource_id FROM resources_objects WHERE owner_id='$id' LIMIT 1");
    if ($db->next_record()) return TRUE;

    //or additional perms avaiable
    $db->query("SELECT perms FROM resources_user_resources  WHERE user_id='$id' ");
    if ($db->next_record()) return TRUE;

    return FALSE;
}

/*****************************************************************************
checkObjektAdminstrablePerms checks, if I have the chance to change
the owner of the given object
/*****************************************************************************/

function checkObjektAdministrablePerms ($resource_object_owner_id, $user_id='') {
    global $user, $perm, $my_perms;

    if (!$user_id)
        $user_id = $user->id;

    //for root, it's quick!
    if ($perm->have_perm("root"))
        return TRUE;

    //for the resources admin too
    if (getGlobalPerms($user_id) == "admin")
        return TRUE;

    //load all my administrable objects
    $my_objects=search_administrable_objects ();

    //ok, we as a user aren't interesting...
    unset ($my_objects[$user_id]);
    if (sizeof ($my_objects)) {
        if (($my_objects[$resource_object_owner_id]["perms"] == "admin") || ($resource_object_owner_id == $user_id)) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else
        return FALSE;
}
/*
* search_administrable_seminars
*
* this Funktion searches all my aministrable seminars
*
* @param    string  a search string, that could be used
* @param    string  the user_id
* @return   array   result
*
**/
function search_administrable_seminars ($search_string='', $user_id='') {
    global $user, $perm, $auth;

    $db = new DB_Seminar;
    $db2 = new DB_Seminar;
    $db3 = new DB_Seminar;

    if (!$user_id)
        $user_id = $user->id;

    if (!$search_string)
        $search_sql = "1";
    else
        $search_sql = " Name LIKE '%$search_string%' OR Untertitel = '%$search_string%' OR Seminar_id = '$search_string' ";

    $user_global_perm = $perm->get_perm($user_id);
    switch ($user_global_perm) {
        case "root":
            //Alle Seminare...
            $db->query("SELECT Seminar_id, Name FROM seminare WHERE $search_sql ORDER BY Name");
            while ($db->next_record())
                $my_objects[$db->f("Seminar_id")]=array("name"=>$db->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "admin");
        break;
        case "admin":
            //Alle meine Institute (unabhaengig von Suche fuer Rechte)...
            if ($perm->is_fak_admin($user_id)){
                $db->query("SELECT DISTINCT (ifnull( b.Institut_id, a.Institut_id )) AS Institut_id
                            FROM user_inst a
                            LEFT JOIN Institute b ON ( a.Institut_id = b.fakultaets_id )
                            WHERE a.inst_perms = 'admin' AND a.user_id = '$user_id'");
            } else {
                $db->query("SELECT Institut_id FROM user_inst WHERE inst_perms = 'admin' AND user_inst.user_id='$user_id' ");
            }
            while ($db->next_record()) {
                //...alle Seminare meiner Institute, in denen ich Admin bin....
                $db2->query("SELECT seminare.Seminar_id, Name FROM seminar_inst LEFT JOIN seminare USING (seminar_id)
                    WHERE ($search_sql) AND seminar_inst.institut_id = '".$db->f("Institut_id")."' ORDER BY Name");
                while ($db2->next_record()) {
                    $my_objects[$db2->f("Seminar_id")]=array("name"=>$db2->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "admin");
                }
            }
        break;
        case "dozent":
        case "tutor":
            //Alle meine Seminare
            $db->query("SELECT seminare.Seminar_id, Name FROM seminar_user LEFT JOIN seminare USING (seminar_id) WHERE ($search_sql) AND seminar_user.status IN ('tutor', 'dozent')  AND seminar_user.user_id='$user_id' ORDER BY Name");
            while ($db->next_record())
                $my_objects[$db->f("Seminar_id")]=array("name"=>$db->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "admin");
        break;
    }
    return $my_objects;
}


/*
* search_administrable_objects
*
* this Funktion searches all my aministrable objects (the object i've got tutor
* or better perms, so I'am able to administrate most of the things).
*
* @param    string  a search string, that could be used
* @param    string  the user_id
* @param    boolean should seminars searched`?
* @return   array
*
**/
function search_administrable_objects($search_string='', $user_id='', $sem=TRUE) {
    static $my_object_cache;
    global $user, $perm, $auth, $_fullname_sql;

    $db = new DB_Seminar;
    $db2 = new DB_Seminar;
    $db3 = new DB_Seminar;

    if (!$user_id)
        $user_id = $user->id;

    $user_global_perm = $perm->get_perm($user_id);

    if (!$search_string){
        $caching = true;
        $search_sql['user'] = '1';
        $search_sql['institut'] = '1';
        $search_sql['seminar'] = '1';
    } else {
        $search_sql['user'] = "username LIKE '%$search_string%' OR Vorname LIKE '%$search_string%' OR Nachname LIKE '%$search_string%' OR auth_user_md5.user_id = '$search_string'";
        $search_sql['institut'] = "Name LIKE '%$search_string%' OR Institute.Institut_id = '$search_string'";
        $search_sql['seminar'] = "Name LIKE '%$search_string%' OR Untertitel = '%$search_string%' OR seminare.Seminar_id = '$search_string'";
        if ($user_global_perm == 'admin'){
            $tmp_objects = search_administrable_objects(false,$user_id,false);
            if (is_array($tmp_objects)){
                foreach ($tmp_objects as $id => $detail){
                    if ($detail['inst_perms']){
                        $my_inst_ids[$id] = $detail['inst_perms'];
                    }
                }
            }
        }
    }

    if ($caching && isset($my_object_cache[$user_id][$sem])){
        return $my_object_cache[$user_id][$sem];
    }

    if (getGlobalPerms($user_id) == "admin")
        $my_objects["global"]=array("name"=>_("Global"), "perms" => "admin");

    $username = get_username($user_id);

    switch ($user_global_perm) {
        case "root":
            //Alle Personen...
            $db->query("SELECT auth_user_md5.user_id,". $_fullname_sql['full_rev'] ." AS fullname , username FROM auth_user_md5  LEFT JOIN user_info USING (user_id) WHERE {$search_sql['user']} ORDER BY Nachname");
            while ($db->next_record())
                    $my_objects[$db->f("user_id")]=array("name"=>$db->f("fullname")." (".$db->f("username").")", "art"=>_("Personen"), "perms" => "admin");
            //Alle Seminare...
            if ($sem) {
                $db->query("SELECT Seminar_id, Name FROM seminare WHERE {$search_sql['seminar']} ORDER BY Name");
                while ($db->next_record())
                    $my_objects[$db->f("Seminar_id")]=array("name"=>$db->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "admin");
            }
            //Alle Institute...
            $db->query("SELECT Institut_id, Name FROM Institute WHERE {$search_sql['institut']} ORDER BY Name");
            while ($db->next_record())
                $my_objects[$db->f("Institut_id")]=array("name"=>$db->f("Name"), "art"=>_("Einrichtungen"), "perms" => "admin");
        break;
        case "admin":
            //Alle meine Institute (Suche)...
            $db->query("SELECT Institute.Institut_id, Name, inst_perms FROM user_inst LEFT JOIN Institute USING (institut_id) WHERE ({$search_sql['institut']}) AND inst_perms = 'admin' AND user_inst.user_id='$user_id' ORDER BY Name");
            while ($db->next_record()) {
                $my_objects[$db->f("Institut_id")]=array("name"=>$db->f("Name"), "art"=>_("Einrichtungen"), "perms" => "admin", 'inst_perms'=> 'admin');
                $my_inst_ids[$db->f("Institut_id")] = 'admin';
            }
            $allowed_inst_perms = array('autor','tutor','dozent');
            if ($perm->is_fak_admin($user_id)){
                $db->query("SELECT Institut_id,Name FROM Institute WHERE ({$search_sql['institut']}) AND Institut_id!=fakultaets_id AND fakultaets_id IN('" . join("','" , array_keys($my_inst_ids)) ."')");
                while($db->next_record()){
                    $my_objects[$db->f("Institut_id")]=array("name"=>$db->f("Name"), "art"=>_("Einrichtungen"), "perms" => "admin",'inst_perms' => 'fak_admin');
                    $my_inst_ids[$db->f("Institut_id")] = 'fak_admin';
                }
                $allowed_inst_perms[] = 'admin';
            }
            if (is_array($my_inst_ids)){
                $inst_in = "('" . join("','" , array_keys($my_inst_ids)) ."')";
                if ($sem) {
                    $db2->query("SELECT a.seminar_id, Name FROM  seminar_inst a
                                LEFT JOIN seminare USING (seminar_id)
                                WHERE ({$search_sql['seminar']})
                                AND  a.Institut_id IN $inst_in  GROUP BY a.seminar_id ORDER BY Name");
                    while ($db2->next_record()) {
                        $my_objects[$db2->f("seminar_id")]=array("name"=>$db2->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "admin");
                    }
                }
            }

        break;
        case "dozent":
        case "tutor":
            $user_status = ($user_global_perm == 'tutor' ? "'tutor'" : "'tutor','dozent'");
            //Alle meine Seminare
            if ($sem) {
                $db->query("SELECT seminare.Seminar_id, Name FROM seminar_user LEFT JOIN seminare USING (seminar_id) WHERE ({$search_sql['seminar']}) AND seminar_user.status IN ($user_status)  AND seminar_user.user_id='$user_id' ORDER BY Name");
                while ($db->next_record())
                    $my_objects[$db->f("Seminar_id")]=array("name"=>$db->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "admin");
            }
            //Alle meine Institute...
            $db->query("SELECT Institute.Institut_id, Name, inst_perms FROM user_inst LEFT JOIN Institute USING (institut_id) WHERE ({$search_sql['institut']}) AND inst_perms IN ($user_status)  AND user_inst.user_id='$user_id'  ORDER BY Name");
            while ($db->next_record())
                $my_objects[$db->f("Institut_id")]=array("name"=>$db->f("Name"), "art"=>_("Einrichtungen"), "perms" => $db->f("inst_perms"));
        break;
        case "autor":
    }
    $my_objects[$user_id]=array("name"=>"aktueller Account"." (".$username.")", "art"=>_("Personen"),  "perms" => "admin");

    if ($caching){
        $my_object_cache[$user_id][$sem] = $my_objects;
    }
    return $my_objects;
}

/*
* search_my_objects
*
* this Funktion searches all my objects (only them with autor perms).
* the function works as an addition to the search administrable objects
* function above
*
* @param    string  a search string, that could be used
* @param    string  the user_id
* @param    boolean should seminars searched`?
* @return   array
*
**/
function search_my_objects ($search_string='', $user_id='', $sem=TRUE) {
    global $user, $perm, $auth, $_fullname_sql;

    $db = new DB_Seminar;

    if (!$user_id)
        $user_id = $user->id;

    if (!$search_string)
        $search_string = "_";

    if ($perm->have_perm('admin')){
        return array();
    }

    //Alle meine Seminare
    if ($sem) {
        $db->query("SELECT seminare.Seminar_id, Name FROM seminar_user LEFT JOIN seminare USING (seminar_id) WHERE (Name LIKE '%$search_string%' OR Untertitel LIKE '%$search_string%' OR seminare.Seminar_id = '$search_string') AND seminar_user.status = 'autor'  AND seminar_user.user_id='$user_id' ORDER BY Name");
        while ($db->next_record())
            $my_objects[$db->f("Seminar_id")]=array("name"=>$db->f("Name"), "art"=>_("Veranstaltungen"), "perms" => "autor");
    }

    //Alle meine Institute...
    $db->query("SELECT Institute.Institut_id, Name FROM user_inst LEFT JOIN Institute USING (institut_id) WHERE (Name LIKE '%$search_string%' OR Institute.Institut_id = '$search_string') AND inst_perms = 'autor' AND user_inst.user_id='$user_id' ORDER BY Name");
    while ($db->next_record())
        $my_objects[$db->f("Institut_id")]=array("name"=>$db->f("Name"), "art"=>_("Einrichtungen"), "perms" => "autor");

    return $my_objects;
}


/*****************************************************************************
search_admin_user searches in all the admins
/*****************************************************************************/

function search_admin_user ($search_string='') {
    global $_fullname_sql;
    $db=new DB_Seminar;

    //In allen Admins suchen...
    $db->query("SELECT a.user_id, ". $_fullname_sql['full_rev'] ." AS fullname, username FROM auth_user_md5  a LEFT JOIN user_info USING (user_id) WHERE username LIKE '%$search_string%' OR Vorname LIKE '%$search_string%' OR Nachname LIKE '%$search_string%' OR a.user_id = '$search_string' ORDER BY Nachname");
    while ($db->next_record())
            $my_objects[$db->f("user_id")]=array("name"=>$db->f("fullname")." (".$db->f("username").")", "art"=>_("Personen"));

    return $my_objects;
}


/*****************************************************************************
search_objects searches in all objects
/*****************************************************************************/

function search_objects ($search_string='', $user_id='', $sem=TRUE) {
    global $user, $perm, $auth, $_fullname_sql;

    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db3=new DB_Seminar;

    if (!$user_id)
        $user_id=$user->id;

    //Alle Personen...
    $db->query("SELECT a.user_id, ". $_fullname_sql['full_rev'] ." AS fullname, username FROM auth_user_md5 a LEFT JOIN user_info USING (user_id) WHERE username LIKE '%$search_string%' OR Vorname LIKE '%$search_string%' OR Nachname LIKE '%$search_string%' OR a.user_id = '$search_string' ORDER BY Nachname");
    while ($db->next_record())
        $my_objects[$db->f("user_id")]=array("name"=>$db->f("fullname")." (".$db->f("username").")", "art"=>_("Personen"));
    //Alle Seminare...
    if ($sem) {
        $db->query("SELECT Seminar_id, Name FROM seminare WHERE Name LIKE '%$search_string%' OR Untertitel = '%$search_string%' OR Seminar_id = '$search_string' ORDER BY Name");
        while ($db->next_record())
            $my_objects[$db->f("Seminar_id")]=array("name"=>$db->f("Name"), "art"=>_("Veranstaltungen"));
    }
    //Alle Institute...
    $db->query("SELECT Institut_id, Name FROM Institute WHERE Name LIKE '%$search_string%' OR Institut_id = '$search_string' ORDER BY Name");
    while ($db->next_record())
        $my_objects[$db->f("Institut_id")]=array("name"=>$db->f("Name"), "art"=>_("Einrichtungen"));

    return $my_objects;
}


/*****************************************************************************
Searchform, zur Erzeugung der oft gebrauchten Personen-Auswahl
u.a. Felder
/*****************************************************************************/

function showSearchForm($name, $search_string='', $user_only=FALSE, $administrable_objects_only=FALSE, $admins=FALSE, $allow_all=FALSE, $sem=TRUE, $img_dir="left") {

    if ($search_string) {
        if ($user_only) //Nur in Personen suchen
            if ($admins) //nur admins anzeigen
                $my_objects=search_admin_user($search_string);
            else //auch andere...
                ;
        elseif ($administrable_objects_only)
            $my_objects=search_administrable_objects($search_string, FALSE, $sem);
        else //komplett in allen Objekten suchen
            $my_objects=search_objects($search_string, FALSE, $sem);

        ?>
        <input type="hidden" name="<? echo "search_string_".$name ?>" value="<? echo $search_string ?>">
        <input type="image" name="<? echo "send_".$name ?>" src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/yellow/arr_2<?=$img_dir.".png\" ".tooltip (_("diesen Eintrag übernehmen")) ?> value="<?=_("&uuml;bernehmen")?>" >
        <select align="absmiddle" name="<? echo "submit_".$name ?>">
        <?
        if ($allow_all)
            print "<option style=\"vertical-align: middle;\" value=\"all\">"._("jedeR")."</option>";

        if ( is_array($my_objects) )
        foreach ($my_objects as $key=>$val) {
            if ($val["art"] != $old_art) {
                ?>
            <font size=-1><option value="FALSE"><? echo "-- ".$val["art"]." --"; ?></option></font>
                <?
            }
            ?>
            <font size=-1><option value="<? echo $key ?>"><? echo my_substr($val["name"],0,30); ?></option></font>
            <?

            $old_art=$val["art"];
        }
        ?></select>
        <font size=-1><input type="image" align="absmiddle" name="<? echo "reset_".$name ?>" src="<?= Assets::image_path('icons/16/blue/refresh.png') ?>"  <?=tooltip (_("Suche zurücksetzen")) ?> border="0" value="<?=_("neue Suche")?>"></font>
        <?
    } else {
        ?>
        <font size=-1><input type="text" align="absmiddle" name=" <? echo "search_string_".$name ?>" size=30 maxlength=255></font>
        <font size=-1><input type="image" align="absmiddle" name=" <? echo "do_".$name ?>" src="<?= Assets::image_path('icons/16/blue/search.png') ?>"  <?=tooltip (_("Starten Sie hier Ihre Suche")) ?> border=0 value="<?=_("suchen")?>"></font>
        <?
    }
}

function getResourcesCategories()
{
    $query = "SELECT * FROM resources_categories ORDER BY name";
    return DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

?>
