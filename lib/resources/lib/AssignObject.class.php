<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* AssignObject.class.php
*
* class for an assign-object
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup      resources
* @module       AssignObject.class.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// AssignObject.class.php
// zentrale Klasse fuer ein Belegungsobjekt
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

require_once ("lib/classes/SemesterData.class.php");
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/lib/list_assign.inc.php";
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . "/resourcesFunc.inc.php";
require_once('lib/log_events.inc.php');
require_once('lib/resources/lib/CheckMultipleOverlaps.class.php');


/*****************************************************************************
AssignObject, zentrale Klasse der Objekte der Belegung
/*****************************************************************************/
class AssignObject {
    var $id;                    //Id des Belegungs-Objects
    var $resource_id;           //resource_id des verknuepten Objects;
    var $assign_user_id;        //id des verknuepten Benutzers der Ressource
    var $user_free_name;        //freier Name fuer Belegung
    var $begin;             //Timestamp der Startzeit
    var $end;                   //Timestamp der Endzeit
    var $repeat_end;            //Timestamp der Endzeit der Belegung (expire)
    var $repeat_quantity;       //Anzahl der Wiederholungen
    var $repeat_interval;       //Intervall der Wiederholungen
    var $repeat_month_of_year ; //Wiederholungen an bestimmten Monat des Jahres
    var $repeat_day_of_month;   //Wiederholungen an bestimmten Tag des Monats
    var $repeat_week_of_month;  //Wiederholungen immer in dieser Woche des Monats
    var $repeat_day_of_week;    //Wiederholungen immer an diesem Wochentag
    var $comment_internal;      // interner Kommentar (z.B. Schliessdienst selbst, etc.)
    var $isNewObject;
    var $chng_flag;
    var $events;

    function Factory(){
        static $assign_object_pool;

        $argn = func_num_args();

        if ($argn == 1){
            if ( ($id = func_get_arg(0)) ){
                if (is_object($assign_object_pool[$id]) && $assign_object_pool[$id]->getId() == $id){
                    return $assign_object_pool[$id];
                } else {
                    $assign_object_pool[$id] = new AssignObject($id);
                    return $assign_object_pool[$id];
                }
            }
        }
        return new AssignObject(func_get_args());
    }

    function AssignObject($argv) {
        global $user;

        $this->user_id = $user->id;

        if($argv && !is_array($argv)) {
            $id = $argv;
            if (!$this->restore($id)){
                $this->isNewObject = true;
            }
        }
        else {
            $this->id = $argv[0];
            $this->resource_id = $argv[1];
            $this->assign_user_id = $argv[2];
            $this->user_free_name = $argv[3];
            $this->begin = $argv[4];
            $this->end = $argv[5];
            $this->repeat_end = $argv[6];
            $this->repeat_quantity = $argv[7];
            $this->repeat_interval = $argv[8];
            $this->repeat_month_of_year  = $argv[9];
            $this->repeat_day_of_month = $argv[10];
            $this->repeat_week_of_month = $argv[11];
            $this->repeat_day_of_week = $argv[12];

            if ($argv[13]) {
                $this->comment_internal = $argv[13];
            }

            if (!$this->id)
                $this->createId();
            $this->isNewObject =TRUE;
        }
    }

    function createId() {
        $this->id = md5(uniqid("BartSimpson",1));
    }

    function create() {
        $db = DBManager::get();
        $query = sprintf("SELECT assign_id FROM resources_assign WHERE assign_id ='%s' ", $this->id);
        $result = $db->query($query);
        if ($result->fetch()) {
            $this->chng_flag=TRUE;
            return $this->store();
        } else
            return $this->store(TRUE);
    }

    function getId() {
        return $this->id;
    }

    function getAssignUserId() {
        return $this->assign_user_id;
    }

    function GetOwnerName($explain=FALSE, $event_obj = null) {
        global $TERMIN_TYP;
        $db = DBManager::get();
        if (is_null($event_obj)){
            $id = $this->assign_user_id;
            $event_obj =& $this;
        } else {
            $id = $event_obj->assign_user_id;
        }

        switch (get_object_type($id, array('date', 'user'))) {
            case "user":
                if (!$explain)
                    return get_fullname($id,'full');
                else
                    return get_fullname($id,'full')." ("._("NutzerIn").")";
            break;
            case "inst":
            case "fak":
                $query = sprintf("SELECT Name FROM Institute WHERE Institut_id='%s' ",$id);
                $result = $db->query($query);
                if ($res = $result->fetch())
                    if (!$explain)
                        return $res["Name"];
                    else
                        return $res["Name"]." ("._("Einrichtung").")";
            break;
            case "sem":
                $sem_obj = Seminar::GetInstance($id);
                if (!$sem_obj->is_new){
                    if (!$explain){
                        return $sem_obj->getName();
                    } else {
                        $meta_dates = $sem_obj->getMetaDates();
                        $key = $sem_obj->getMetaDatesKey($event_obj->begin, $event_obj->end);
                        if ($meta_dates[$key]['desc']){
                            $name = $sem_obj->getName() . ' ('.$meta_dates[$key]['desc'].')';
                        } else {
                            $name = $sem_obj->getName() . " ("._("Veranstaltung").")";
                        }
                        return $name;
                    }
                } else {
                    return "unbekannt";
                }
            break;
            case "date":
                $query = sprintf("SELECT Name, content, date_typ FROM termine LEFT JOIN seminare ON (seminar_id = range_id) WHERE termin_id='%s' ",$id);
                $result = $db->query($query);
                if ($res = $result->fetch())
                    if (!$explain)
                        return $res["Name"];
                    else
                        return $res["Name"]." (".$TERMIN_TYP[$res["date_typ"]]["name"].")";
            break;
            case "global":
            default:
                return "unbekannt";
            break;
        }
    }

    function getUsername($use_free_name=TRUE, $explain=true) {
        if ($this->assign_user_id)
            return $this->getOwnerName($explain)."\n".$this->getUserFreeName();
        elseif ($use_free_name)
            return $this->getUserFreeName(). ($explain ? " (" . _("direkter Eintrag") . ")" : "");
        else
            return FALSE;
    }

    function getOwnerType() {
        $type = get_object_type($this->getAssignUserId(), array('date','user'));
        return $type;
    }

    function getResourceId() {
        return $this->resource_id;
    }

    function getUserFreeName() {
        return $this->user_free_name;
    }

    function getBegin() {
        if (!$this->begin)
            return time();
        else
            return $this->begin;
    }

    function getEnd() {
        if (!$this->end)
            return time()+3600;
        else
            return $this->end;
    }

    function getRepeatEnd() {
        if (!$this->repeat_end)
            return $this->end;
        else
            return $this->repeat_end;
    }

    function getRepeatQuantity() {
        return $this->repeat_quantity;
    }

    function getRepeatInterval() {
        return $this->repeat_interval;
    }

    function getRepeatMonthOfYear() {
        return $this->repeat_month_of_year ;
    }

    function getRepeatDayOfMonth() {
        return $this->repeat_day_of_month;
    }

    function getRepeatWeekOfMonth() {
        return $this->repeat_week_of_month;
    }

    function getRepeatDayOfWeek() {
        return $this->repeat_day_of_week;
    }

    function getRepeatMode() {
        if ((!$this->repeat_month_of_year) && (!$this->repeat_week_of_month) && (!$this->repeat_day_of_month) && (!$this->repeat_day_of_week) && (!$this->repeat_quantity)) {
            if ( $this->repeat_end && date("z", $this->repeat_end) != date("z", $this->begin) )
                return "sd";
            else
                return "na";
        } elseif ($this->repeat_month_of_year)
            return "y";
        elseif ($this->repeat_week_of_month || $this->repeat_day_of_month)
            return "m";
        elseif ($this->repeat_day_of_week)
            return "w";
        else
            return "d";
    }

    function getRepeatEndByQuantity() {
        create_assigns($this, $this, -1, -1);

        $max_date = 0;
        if(is_array($this->events)){
            foreach ($this->events as $val) {
                if ($val->getEnd() > $max_date)
                    $max_date = $val->getEnd();
            }
        }
        return $max_date;
    }

    function getEvents() {
        $this->events = array();
        create_assigns($this, $this);
        return $this->events;
    }

    function isNew() {
        return $this->isNewObject;
    }

    function isRepeatEndSemEnd() {
        $semester = new SemesterData;
        $all_semester = $semester->getAllSemesterData();

        foreach ($all_semester as $a)
            if (($this->begin >= $a["beginn"]) &&($this->begin <= $a["ende"]))
                if ($this->repeat_end==$a["vorles_ende"])
                    return true;
        return false;
    }

    function checkLock() {
        global $user;

        $resObject = ResourceObject::Factory($this->resource_id);
        //load the events of the actual assign...
        create_assigns($this, $this);

        //check, if an assign_lock for one of the events is active
        if (($GLOBALS["RESOURCES_ASSIGN_LOCKING_ACTIVE"]) && ($resObject->isLockable()) && ($resObject->isRoom()) && (getGlobalPerms($user->id) != "admin")) {
            foreach ($this->events as $obj) {
                $lock = getLockPeriod("assign", $obj->getBegin(), $obj->getEnd());
                if ($lock) {
                    $locks[$lock[2]] = array("lock_begin"=>$lock[0], "lock_end"=>$lock[1]);
                }
            }
            if ($locks) {
                return $locks;
            }
        }
    }

    function checkOverlap($check_locks = TRUE) {
        global $user;
        $resObject = ResourceObject::Factory($this->resource_id);
        //we check overlaps always for a whole day
        $start = mktime (0,0,0, date("n", $this->begin), date("j", $this->begin), date("Y", $this->begin));
        if ($this->repeat_end)
            $end = mktime (23,59,59, date("n", $this->repeat_end), date("j", $this->repeat_end), date("Y", $this->repeat_end));
        else
            $end = mktime (23,59,59, date("n", $this->end), date("j", $this->end), date("Y", $this->end));

        //load the events of the actual assign...
        create_assigns($this, $this);

        //check, if an assign_lock for one of the events is active (results in an "overlap" so assign cant be saved)
        if (($GLOBALS["RESOURCES_ASSIGN_LOCKING_ACTIVE"]) && ($resObject->isLockable()) && ($resObject->isRoom()) && (getGlobalPerms($user->id) != "admin") && ($check_locks)) {
            foreach ($this->events as $obj) {
                $lock = getLockPeriod("assign", $obj->getBegin(), $obj->getEnd());
                if ($lock) {
                    $overlaps[] = array("begin" =>$obj->getBegin(), "end"=>$obj->getEnd(), "lock"=> TRUE, "lock_begin"=>$lock[0], "lock_end"=>$lock[1], "lock_id"=>$lock[2],);
                }
            }
            if ($overlaps) {
                return $overlaps;
            }
        }

        //...and add the events of existing assigns in the given resource...
        list_restore_assign($this, $this->resource_id, $start, $end);
        //..so we have a "virtual" set of assign-events in the given resource. Now we can check overlaps...

        //check for regular overlaps
        if (!$resObject->getMultipleAssign()) { //when multiple assigns are allowed, we need no check...
            $multiChecker = new CheckMultipleOverlaps();
            $multiChecker->setAutoTimeRange(Array($this));
            $multiChecker->addResource($this->resource_id);
            $events = Array();
            
            foreach ($this->getEvents() as $evtObj) {
                $events[$evtObj->getId()] = $evtObj;
            }

            $multiChecker->checkOverlap($events, $result);
            $overlaps = Array();

            if (is_array($result[$this->resource_id][$this->id])) {
                foreach($result[$this->resource_id][$this->id] as $overlapping_event) {
                    $overlaps[$overlapping_event["assign_id"]]["begin"] = $overlapping_event["begin"];
                    $overlaps[$overlapping_event["assign_id"]]["end"]   = $overlapping_event["end"];
                }
            }
            
            return $overlaps;
        } else {
            return false;
        }
    }

    function getFormattedShortInfo() {
        $info = strftime("%A", $this->begin);
        $info.= ", ".date("d.m.Y", $this->begin);
        if ((date("d", $this->begin) != date("d", $this->repeat_end)) &&
            (date("m", $this->begin) != date("m", $this->repeat_end)) &&
            (date("Y", $this->begin) != date("Y", $this->repeat_end)))
            $info.= " - ". date("d.m.Y", $this->repeat_end);
        $info.=", ".date("H:i", $this->begin)." - ".date("H:i", $this->end);
        if (($this->getRepeatMode() != "na") && ($this->getRepeatMode() != "sd"))
            $info.=", ".$this->getFormattedRepeatMode();
        return $info;
    }

    function getFormattedRepeatMode() {
        switch ($this->getRepeatMode()) {
            case "d":
                $str[1]= _("jeden Tag");
                $str[2]= _("jeden zweiten Tag");
                $str[3]= _("jeden dritten Tag");
                $str[4]= _("jeden vierten Tag");
                $str[5]= _("jeden f&uuml;nften Tag");
                $str[6]= _("jeden sechsten Tag");
                $max=6;
            break;
            case "w":
                $str[1]= _("jede Woche");
                $str[2]= _("jede zweite Woche");
                $str[3]= _("jede dritte Woche");
                $max=3;
            break;
            case "m":
                $str[1]= _("jeden Monat");
                $str[2]= _("jeden zweiten Monat");
                $str[3]= _("jeden dritten Monat");
                $str[4]= _("jeden vierten Monat");
                $str[5]= _("jeden f&uuml;nften Monat");
                $str[6]= _("jeden sechsten Monat");
                $str[7]= _("jeden siebten Monat");
                $str[8]= _("jeden achten Monat");
                $str[9]= _("jeden neunten Monat");
                $str[10]= _("jeden zehnten Monat");
                $str[11]= _("jeden elften Monat");
                $max=11;
            break;
            case "y":
                $str[1]= _("jedes Jahr");
                $str[2]= _("jedes zweite Jahr");
                $str[3]= _("jedes dritte Jahr");
                $str[4]= _("jedes vierte Jahr");
                $str[5]= _("jedes f&uuml;nfte Jahr");
                $max=5;
            break;
        }
        return $str[$this->getRepeatInterval()];
    }

    function setResourceId($value) {
        $this->resource_id=$value;
        $this->chng_flag=TRUE;
    }

    function setUserFreeName($value) {
        $this->user_free_name=$value;
        $this->chng_flag=TRUE;
    }

    function setAssignUserId($value) {
        $this->assign_user_id=$value;
        $this->chng_flag=TRUE;
    }

    function setBegin($value) {
        $this->begin=$value;
        $this->chng_flag=TRUE;
    }

    function setEnd($value) {
        $this->end=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatEnd($value) {
        $this->repeat_end=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatQuantity($value) {
        $this->repeat_quantity=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatInterval($value) {
        $this->repeat_interval=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatMonthOfYear($value) {
        $this->repeat_month_of_year=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatDayOfMonth($value) {
        $this->repeat_day_of_month=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatWeekOfMonth($value) {
        $this->repeat_week_of_month=$value;
        $this->chng_flag=TRUE;
    }

    function setRepeatDayOfWeek($value) {
        $this->repeat_day_of_week=$value;
        $this->chng_flag=TRUE;
    }

    function setCommentInternal($value) {
        if ($value != '') {
            $this->comment_internal = $value;
            $this->chng_flag = TRUE;
        }
    }

    function getCommentInternal() {
        return $this->comment_internal;
    }

    function restore($id='') {
        $db = DBManager::get();
        if(func_num_args() == 1){
            if (!$id){
                return false;
            }
        } else {
            if (!$this->id){
                return false;
            }
            $id = $this->id;
        }
        $query = sprintf("SELECT * FROM resources_assign WHERE assign_id='%s' ",$id);
        $result = $db->query($query);

        if($res = $result->fetch()) {
            $this->id = $id;
            $this->resource_id = $res["resource_id"];
            $this->assign_user_id = $res["assign_user_id"];
            $this->user_free_name = $res["user_free_name"];
            $this->begin = (int)$res["begin"];
            $this->end =  (int)$res["end"];
            $this->repeat_end =  (int)$res["repeat_end"];
            $this->repeat_quantity =  (int)$res["repeat_quantity"];
            $this->repeat_interval =  (int)$res["repeat_interval"];
            $this->repeat_month_of_year  = (int)$res["repeat_month_of_year"];
            $this->repeat_day_of_month = (int)$res["repeat_day_of_month"];
            $this->repeat_month = (int)$res["repeat_month"];
            $this->repeat_week_of_month = (int)$res["repeat_week_of_month"];
            $this->repeat_day_of_week = (int)$res["repeat_day_of_week"];
            $this->repeat_week = (int)$res["repeat_week"];
            $this->comment_internal = $res["comment_internal"]; 
            return TRUE;
        }
        return FALSE;
    }

    function store($create=''){
        $db = DBManager::get();
        // save only, if changes were made or the object is new and a assign_user_id or a user_free_name is given
        if ((($this->chng_flag) || ($create)) && (($this->assign_user_id) || ($this->user_free_name))) {
            $chdate = time();
            $mkdate = time();

            //insert NULL instead of nothing
            if (!$this->assign_user_id)
                $tmp_assign_user_id = "NULL";
            else
                $tmp_assign_user_id = "'$this->assign_user_id'";

            if($create) {
                $query = sprintf("INSERT INTO resources_assign SET assign_id='%s', resource_id='%s', "
                    ."assign_user_id=%s, user_free_name='%s', begin='%s', end='%s', repeat_end='%s', "
                    ."repeat_quantity='%s', repeat_interval='%s', repeat_month_of_year='%s', repeat_day_of_month='%s',  "
                    ."repeat_week_of_month='%s', repeat_day_of_week='%s', mkdate='%s', comment_internal ='%s'  "
                             , $this->id, $this->resource_id, $tmp_assign_user_id, $this->user_free_name, $this->begin
                             , $this->end, $this->repeat_end, $this->repeat_quantity, $this->repeat_interval
                             , $this->repeat_month_of_year, $this->repeat_day_of_month, $this->repeat_week_of_month
                             , $this->repeat_day_of_week, $mkdate,$this->comment_internal);
            } else {
                $query = sprintf("UPDATE resources_assign SET resource_id='%s', "
                    ."assign_user_id=%s, user_free_name='%s', begin='%s', end='%s', repeat_end='%s', "
                    ."repeat_quantity='%s', repeat_interval='%s', repeat_month_of_year='%s', repeat_day_of_month='%s',  "
                    ."repeat_week_of_month='%s', repeat_day_of_week='%s', comment_internal = '%s' WHERE assign_id='%s' "
                             , $this->resource_id, $tmp_assign_user_id, $this->user_free_name, $this->begin
                             , $this->end, $this->repeat_end, $this->repeat_quantity, $this->repeat_interval
                             , $this->repeat_month_of_year, $this->repeat_day_of_month, $this->repeat_week_of_month
                             , $this->repeat_day_of_week, $this->comment_internal, $this->id);
            }
            $result = $db->exec($query);
            if ($result > 0 ) {
                // LOGGING
                if ($this->assign_user_id) {
                    $type = $this->getOwnerType();
                    if ($type == 'date') {
                        $semid = Seminar::GetSemIdByDateId($this->assign_user_id);
                    } else if ($type == 'sem') {
                        $semid = $this->assign_user_id;
                    } else {
                        $semid = null;
                        error_log("unknown type of assign_user_id {$this->assign_user_id}");
                    }
                    log_event("RES_ASSIGN_SEM",$this->resource_id,$semid,$this->getFormattedShortInfo(). $create ? " Neue Buchung" : " Buchungsupdate",$query);
                } else {
                    log_event("RES_ASSIGN_SINGLE",$this->resource_id,NULL,$this->getFormattedShortInfo(). $create ? " Neue Buchung" : " Buchungsupdate",$query);
                }
                $query = sprintf("UPDATE resources_assign SET chdate='%s' WHERE assign_id='%s' ", $chdate, $this->id);
                $db->exec($query);
                $this->syncronizeMetaDates();
                
                $this->updateResourcesTemporaryEvents();

                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * update the table resources_temporary_events for this assign
     */
    function updateResourcesTemporaryEvents() {
        // delete old events
        $stmt = DBManager::get()->prepare("DELETE FROM resources_temporary_events 
            WHERE assign_id = ?");
        $stmt->execute(array($this->id));

        // get the events and keep resources_temporary_events up-to-date under all circumstances
        $events = $this->getEvents();
        $sql = Array();
        $now = time();

        foreach($events as $event) {
            $sql[] = "('" . md5(uniqid("tempo",1)) ."','$this->resource_id', '".$this->id."', ".$event->getBegin().", ".$event->getEnd().", 'assign', $now)";
        }

        if (sizeof($sql) > 0) {
            $query = "INSERT INTO resources_temporary_events (event_id ,resource_id, assign_id,begin,end,type,mkdate) VALUES " . join(",",$sql);
            DBManager::get()->query($query);
        }
    }

    function syncronizeMetaDates(){
        $changed = false;
        if ($this->getOwnerType() == "sem") {
            $sem = Seminar::GetInstance($this->getAssignUserId());
            if (!$sem->is_new){
                $key = $sem->getMetaDatesKey($this->begin, $this->end);
                if (!is_null($key)){
                    $sem->setMetaDateValue($key, 'resource_id', $this->resource_id);
                    $sem->setMetaDateValue($key, 'room_description', '');
                    $changed = $sem->store();
                }
            }
        }
        return $changed;
    }

    function delete() {
        $db = DBManager::get();

        // LOGGING
        if ($this->assign_user_id) {
            $type = $this->getOwnerType();
            if ($type == 'date') {
                $semid = Seminar::GetSemIdByDateId($this->assign_user_id);
            } else if ($type == 'sem') {
                $semid = $this->assign_user_id;
            } else {
                $semid = null;
                error_log("unknown type of assign_user_id {$this->assign_user_id}");
            }
            log_event("RES_ASSIGN_DEL_SEM",$this->resource_id,$semid,$this->getFormattedShortInfo(),"",$GLOBALS['user']->id);
        } else {
            log_event("RES_ASSIGN_DEL_SINGLE",$this->resource_id,NULL,$this->getFormattedShortInfo(),NULL,$GLOBALS['user']->id);
        }

        // delete entries from resources_temporary_events to keep it consistent
        $query = sprintf("DELETE FROM resources_temporary_events WHERE assign_id='%s'", $this->id);
        $db->query($query);

        // delete entry from resources_assign
        $query = sprintf("DELETE FROM resources_assign WHERE assign_id='%s'", $this->id);
        if($db->exec($query))
            return TRUE;
        return FALSE;
    }

    function getCopyForResource($resource_id){
        $new_assign = new AssignObject(array(null, $resource_id));
        foreach(array(  'assign_user_id',
                        'user_free_name',
                        'begin',
                        'end',
                        'repeat_end',
                        'repeat_quantity',
                        'repeat_interval',
                        'repeat_month_of_year',
                        'repeat_day_of_month',
                        'repeat_day_of_month',
                        'repeat_week_of_month',
                        'repeat_day_of_week') as $prop){
            $new_assign->$prop = $this->$prop;
        }
        return $new_assign;
    }
}
