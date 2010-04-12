<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* VeranstaltungResourcesAssign.class.php
*
* updates the saved settings from dates and metadates from a Veranstaltung
* and the linked resources (rooms)
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @package      resources
* @modulegroup  resources_modules
* @module       VeranstaltungResourcesAssign.class.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// VeranstaltungResourcesAssign.class.php
// Modul zum Verknuepfen von Veranstaltungszeiten mit Resourcenbelegung
// Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

require_once 'lib/dates.inc.php';
require_once 'config.inc.php';
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'].'/lib/AssignObject.class.php';
require_once $GLOBALS['RELATIVE_PATH_RESOURCES'].'/lib/RoomRequest.class.php';
require_once 'lib/classes/SemesterData.class.php';

class VeranstaltungResourcesAssign {
    var $db;
    var $db2;
    var $seminar_id;
    var $assign_id;
    var $dont_check;

    //Konstruktor
    function VeranstaltungResourcesAssign ($seminar_id=FALSE) {
        global $RELATIVE_PATH_RESOURCES;
        //make shure to load all the classes from resources, if this class is extern used °change if the classes are storen in own scripts
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;

        $this->seminar_id = $seminar_id;
        $this->dont_check=FALSE;
    }

    function updateAssign($check_locks = true) {
        global $TERMIN_TYP;
        $db = new DB_Seminar;

        $query = sprintf("SELECT termin_id, date_typ FROM termine WHERE range_id = '%s' ", $this->seminar_id);
        $db->query($query);
        while ($db->next_record()) {
            $result = array_merge((array)$result, (array)$this->changeDateAssign($db->f("termin_id")));
        }
        //kill all assigned rooms (only roomes and only resources assigned directly to the Veranstaltung, not to a termin!) to create new ones
        $this->deleteAssignedRooms();

        //if no schedule-date exits, we take the metadates (only in this case! else we take only the concrete dates from the termin table!)
        if (!isSchedule($this->seminar_id,true,true)){
            $seminar = Seminar::GetInstance($this->seminar_id);
            $result2 = array_merge((array)$result, (array)$this->changeMetaAssigns('', '', '', FALSE, FALSE, $check_locks));
            if (is_array($result2)){
                $clear_turnus = false;
                foreach($result2 as $key => $value){
                    if (is_array($value['overlap_assigns'])){
                        foreach($value['overlap_assigns'] as $overlap){
                            $meta_key = $seminar->getMetaDatesKey($overlap["begin"],$overlap["end"]);
                            if (!is_null($meta_key)){
                                $keys_to_clear[] = $meta_key;
                                $clear_turnus = true;
                            }
                        }
                    }
                }
                if ($clear_turnus){
                    $this->clearTurnusData(array_unique($keys_to_clear)); //die, dreaded resource_id, die!!!
                }
            }
            $result = array_merge((array)$result, (array)$result2);
        }
        //Raumanfrage als bearbeitet markieren, wenn vorhanden
        if(get_config('RESOURCES_ALLOW_ROOM_REQUESTS')){
            $request = new RoomRequest(getSeminarRoomRequest($this->seminar_id));
            if (!$request->isNew()){
                $request->checkOpen(true);
            }
        }
        return $result;
    }

    //kills resource_id in metadata_dates
    function clearTurnusData($keys_to_clear = null){
        $seminar = Seminar::GetInstance($this->seminar_id);
        foreach ($seminar->getMetaDates() as $key => $val){
            if (is_null($keys_to_clear) || in_array($key, $keys_to_clear)){
                $seminar->setMetaDateValue($key,'resource_id','');
                $seminar->setMetaDateValue($key,'room_description','');
            }
        }
        $seminar->store();
        $seminar->restore();
        $this->turnus_cleared = true;
    }

    //this method creates all assign-objects based on the seminar-metadata
    function &getMetaAssignObjects ($term_data='', $veranstaltung_start_time='', $veranstaltung_duration_time='') {
        $semester = new SemesterData;
        $all_semester = $semester->getAllSemesterData();

        //load data of the Veranstaltung
        if (!$term_data) {
            $query = sprintf("SELECT start_time, duration_time, metadata_dates FROM seminare WHERE Seminar_id = '%s' ", $this->seminar_id);
            $this->db->query($query);
            $this->db->next_record();

            $term_data = unserialize ($this->db->f("metadata_dates"));
            $veranstaltung_start_time = $this->db->f("start_time");
            $veranstaltung_duration_time = $this->db->f("duration_time");
        }

        //determine first day of the start-week as sem_begin
        if ($term_data["start_woche"] >= 0) {
            foreach ($all_semester as $val)
                if (($veranstaltung_start_time >= $val["beginn"]) AND ($veranstaltung_start_time <= $val["ende"])) {
                    $sem_begin = mktime(0, 0, 0, date("n",$val["vorles_beginn"]), date("j",$val["vorles_beginn"])+($term_data["start_woche"] * 7),  date("Y",$val["vorles_beginn"]));
                }
        } else
            $sem_begin = $term_data["start_termin"];

        //if there happens a mistake with the $sem_beginn, cancel.
        if ($sem_begin <= 0) {
            return FALSE;
        }

        $dow = date("w", $sem_begin);

        if ($dow <= 5)
            $corr = ($dow -1) * -1;
        elseif ($dow == 6)
            $corr = 2;
        elseif ($dow == 0)
            $corr = 1;
        else
            $corr = 0;

        if ($corr)
            $sem_begin_uncorrected = $sem_begin;

        $sem_begin = mktime(0, 0, 0, date("n",$sem_begin), date("j",$sem_begin)+$corr,  date("Y",$sem_begin));


        //determine the last day as sem_end
        foreach ($all_semester as $val)
            if  ((($veranstaltung_start_time + $veranstaltung_duration_time + 1) >= $val["beginn"]) AND (($veranstaltung_start_time + $veranstaltung_duration_time +1) <= $val["ende"])) {
                $sem_end=$val["vorles_ende"];
            }

        $interval = $term_data["turnus"] + 1;

        //create the assigns
        $i=0;
        if (is_array($term_data["turnus_data"])) {
            foreach ($term_data["turnus_data"] as $val) {
                $start_time = mktime ((int)$val["start_stunde"], (int)$val["start_minute"], 0, date("n", $sem_begin), date("j", $sem_begin) + ($val["day"] -1), date("Y", $sem_begin));
                $end_time = mktime ((int)$val["end_stunde"], (int)$val["end_minute"], 0, date("n", $sem_begin), date("j", $sem_begin) + ($val["day"] -1), date("Y", $sem_begin));

                //check if we have to correct the timestamps for a whole week (in special cases, sem_beginn is not a Monday but the assign is)
                if (($sem_begin_uncorrected) && ($start_time < $sem_begin_uncorrected) && ($term_data["turnus"]))  {
                    $start_time = mktime (date("G", $start_time), date("i", $start_time), 0, date("n", $start_time), date("j", $start_time) +  7, date("Y", $start_time));
                    $end_time = mktime (date("G", $end_time), date("i", $end_time), 0, date("n", $end_time), date("j", $end_time) +  7, date("Y", $end_time));
                }

                $day_of_week = date("w", $start_time);
                if ($day_of_week == 0)
                    $day_of_week = 7;

                $AssignObjects[] = AssignObject::Factory(FALSE, $val["resource_id"], $this->seminar_id, $user_free_name,
                                            $start_time, $end_time, $sem_end,
                                            -1, $interval, 0, 0,
                                            0, $day_of_week);
            }
        }

        if (is_array($AssignObjects))
            return $AssignObjects;
        else
            return FALSE;
    }

    function &getDateAssignObjects($presence_dates_only = FALSE) {
        $sem = Seminar::getInstance($this->seminar_id);

        // get regular metadates
        foreach ($sem->getCycles() as $cycle_id => $cycle) {
            // get the assigned singledates 
            $dates = $sem->getSingleDatesForCycle($cycle_id);
            foreach ($dates as $date) {
                if ($date->isPresence() || !$presence_dates_only) {
                    $assignObjects[$date->getSingleDateId()] =& $this->getDateAssignObject($date->getSingleDateId());
                }
            }
        }

        // get irregular singledates
        foreach ($sem->getSingledates() as $date) {
            if ($date->isPresence() || !$presence_dates_only) {
                $assignObjects[$date->getSingleDateId()] =& $this->getDateAssignObject($date->getSingleDateId());
            }
        }

        return $assignObjects;
    }

    //this method creates an assign-object for a seminar-date
    function &getDateAssignObject($termin_id, $resource_id='', $begin=0, $end=0) {
        if (!$begin) {
            $query = sprintf("SELECT date, content, end_time, assign_id FROM termine LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE termin_id = '%s' ORDER BY date, content", $termin_id);
            $this->db->query($query);
            if ($this->db->next_record()) {
                $assign_id=$this->db->f("assign_id");
                $begin=$this->db->f("date");
                $end=$this->db->f("end_time");
            }
        } else {
            if (!$end)
                $end=$begin;
        }

        $AssignObject = AssignObject::Factory($assign_id);
        if ($resource_id)
            $AssignObject->setResourceId($resource_id);
        if (!$AssignObject->getAssignUserId())
            $AssignObject->setAssignUserId($termin_id);

        $AssignObject->setBegin($begin);
        $AssignObject->setEnd($end);
        $AssignObject->setRepeatEnd($end);
        $AssignObject->setRepeatQuantity(0);
        $AssignObject->setRepeatInterval(0);
        $AssignObject->setRepeatMonthOfYear(0);
        $AssignObject->setRepeatDayOfMonth(0);
        $AssignObject->setRepeatWeekOfMonth(0);
        $AssignObject->setRepeatDayOfWeek(0);
        if (!$AssignObject->getId())
            $AssignObject->createId();

        return $AssignObject;
    }

    //this method saves the assigns and does overlap checks;
    function changeMetaAssigns($term_data='', $veranstaltung_start_time='', $veranstaltung_duration_time='', $check_only = FALSE, $assignObjects = FALSE, $check_locks = TRUE) {
        if (func_num_args() == 1)
            $assignObjects = func_get_arg(0);

        //load the assign-objects, if not given
        if (!$assignObjects) {
            $assignObjects =& $this->getMetaAssignObjects($term_data, $veranstaltung_start_time, $veranstaltung_duration_time);
        }
        //check and save the assigns
        $i=0;
        if (is_array($assignObjects))
            foreach ($assignObjects as $obj) {
                if ($obj->getResourceId()) {
                    //check if there are overlaps (resource isn't free!)
                    if (!$this->dont_check)
                        $overlaps = $obj->checkOverlap($check_locks);

                    if ($overlaps)
                        $result[$obj->getId()]=array("overlap_assigns"=>$overlaps, "resource_id"=>$obj->getResourceId());
                    $i++;

                    if ((!$check_only) && (!$overlaps)) {
                        $obj->create();
                        $result[$obj->getId()]=array("overlap_assigns"=>FALSE, "resource_id"=>$obj->getResourceId());
                    }

                }
            }
        return $result;
    }

    function changeDateAssign($termin_id, $resource_id='', $begin='', $end='', $check_only=FALSE, $check_locks = TRUE) {
        //load data from termin and assign object
        $query = sprintf("SELECT date, content, end_time, assign_id, resources_assign.begin AS assign_begin, resources_assign.end AS assign_end, resources_assign.resource_id AS assign_resource_id FROM termine LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE termin_id = '%s' ORDER BY date, content", $termin_id);
        $this->db->query($query);
        if ($this->db->next_record()) {
            if (!$begin) {
                $assign_id=$this->db->f("assign_id");
                $begin=$this->db->f("date");
                $end=$this->db->f("end_time");
            } else {
                if (!$end)
                    $end=$begin;
            }

            if (!$resource_id)
                $resource_id=$this->db->f("assign_resource_id");

            $assign_begin = $this->db->f("assign_begin");
            $assign_end = $this->db->f("assign_end");
            $assign_resource_id = $this->db->f("assign_resource_id");
        } else
            return FALSE;

        //check the saved assign-object-times against the planned times - if the same, no update is needed.
        if (($assign_begin == $begin) && ($assign_end == $end) && (($assign_resource_id == $resource_id))) {
            return TRUE;
        }
        if ((!$assign_id) && (!$check_only)) {
            $result = $this->insertDateAssign($termin_id, $resource_id);
        } else {
            $changeAssign = AssignObject::Factory($assign_id);
            if ($resource_id)
                $changeAssign->setResourceId($resource_id);
            else
                $resource_id = $changeAssign->getResourceId();

            $changeAssign->setBegin($begin);
            $changeAssign->setEnd($end);
            $changeAssign->setRepeatEnd($end);
            $changeAssign->setRepeatQuantity(0);
            $changeAssign->setRepeatInterval(0);
            $changeAssign->setRepeatMonthOfYear(0);
            $changeAssign->setRepeatDayOfMonth(0);
            $changeAssign->setRepeatWeekOfMonth(0);
            $changeAssign->setRepeatDayOfWeek(0);
            if (!$changeAssign->getId())
                $changeAssign->createId();

            //check if there are overlaps (resource isn't free!)
            if (!$this->dont_check)
                $overlaps = $changeAssign->checkOverlap($check_locks);

            if ($overlaps) {
                $result[$changeAssign->getId()]=array("overlap_assigns"=>$overlaps, "resource_id"=>$resource_id, "termin_id"=>$termin_id);
                $this->killDateAssign($termin_id);
            }

            if ((!$check_only) && (!$overlaps)) {
                $changeAssign->store();
                $result[$changeAssign->getId()]=array("overlap_assigns"=>FALSE, "resource_id"=>$resource_id, "termin_id"=>$termin_id);
                //Raumanfrage als bearbeitet markieren, wenn vorhanden
                if(get_config('RESOURCES_ALLOW_ROOM_REQUESTS')){
                    $request = new RoomRequest(getDateRoomRequest($termin_id));
                    if (!$request->isNew()){
                        $request->checkOpen(true);
                    }
                }
            }
        }
        return $result;
    }

    function insertDateAssign($termin_id, $resource_id, $begin='', $end='', $check_only=FALSE, $check_locks = TRUE) {
        if ($resource_id) {
            if (!$begin) {
                $query = sprintf("SELECT date, content, end_time FROM termine WHERE termin_id = '%s'", $termin_id);
                $this->db->query($query);
                if ($this->db->next_record()) {
                    $begin=$this->db->f("date");
                    $end=$this->db->f("end_time");
                }
            } else {
                if (!$end)
                    $end=$begin;
            }

            if ($begin) {
                $createAssign = AssignObject::Factory(FALSE, $resource_id, $termin_id, '',
                                            $begin, $end, $end,
                                            0, 0, 0, 0, 0, 0);
                //check if there are overlaps (resource isn't free!)
                if (!$this->dont_check)
                    $overlaps = $createAssign->checkOverlap($check_locks);

                if ($overlaps)
                    $result[$createAssign->getId()]=array("overlap_assigns"=>$overlaps, "resource_id"=>$resource_id, "termin_id"=>$termin_id);

                if ((!$check_only) && (!$overlaps)) {
                    $createAssign->create();
                    $result[$createAssign->getId()]=array("overlap_assigns"=>FALSE, "resource_id"=>$resource_id, "termin_id"=>$termin_id);
                }
            }
        }
        return $result;
    }

    function killDateAssign($termin_id) {
        if ($termin_id) {
            $query = sprintf ("SELECT assign_id FROM resources_assign LEFT JOIN resources_objects USING (resource_id) LEFT JOIN resources_categories USING (category_id) WHERE assign_user_id = '%s' AND resources_categories.is_room = 1 ", $termin_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killAssign = AssignObject::Factory($this->db->f("assign_id"));
                $killAssign->delete();
            }
            $query = sprintf("SELECT request_id FROM resources_requests WHERE termin_id = '%s' ", $termin_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killRequest = new RoomRequest ($this->db->f("request_id"));
                $killRequest->delete();
            }
        }
    }

    function deleteAssignedRooms() {
        if ($this->seminar_id) {
            $query = sprintf("SELECT assign_id FROM resources_assign LEFT JOIN resources_objects USING (resource_id) LEFT JOIN resources_categories USING (category_id) WHERE resources_assign.assign_user_id = '%s' AND resources_categories.is_room = 1 ", $this->seminar_id);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $killAssign = AssignObject::Factory($this->db->f("assign_id"));
                $killAssign->delete();
            }
        }
    }
}
?>
