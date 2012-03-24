<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CheckMultipleOverlaps.class.php
*
* checks overlaps for multiple resources, seminars and assign objects
* via the a special table
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, data-quest GmbH <info@data-quest.de>
* @access       public
* @package      resources
* @modulegroup      resources_modules
* @module       CheckMultipleOverlaps.class.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CheckMultipleOverlaps.class.php
// Klasse zum checken von Ueberschneidungen von mehrere Ressourcen, Veranstaltungen und
// Belegungen
// Copyright (C) 2004 Cornelis Kater <ckater@gwdg.de>, data-quest GmbH <info@data-quest.de>
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

require_once $RELATIVE_PATH_RESOURCES."/lib/AssignEventList.class.php";
require_once $RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php";

class CheckMultipleOverlaps {
    var $begin;
    var $end;
    var $db;            //db object
    var $db1;           //db2 object
    var $resource_ids;      //all the resources in the actual check-set

    //Kontruktor
    function CheckMultipleOverlaps () {
        $this->db = new DB_Seminar;
    }

    function setTimeRange($begin, $end) {
        $this->begin = $begin;
        $this->end = $end;
    }

    function setAutoTimeRange($assObjs) {
        $end = 0;
        foreach ($assObjs as $obj) {
            if (!$begin)
                $begin = $obj->getBegin();
            if ($obj->getBegin() < $begin)
                $begin = $obj->getBegin();
            if ($obj->getRepeatEnd() > $end)
                $end = $obj->getRepeatEnd();
        }
        $this->setTimeRange($begin, $end);
    }

    function deleteIndexes() {
        //$query = sprintf ("ALTER IGNORE TABLE `resources_temporary_events` DROP PRIMARY KEY, DROP INDEX `resource_id` ");
        //$this->db->query($query);
    }

    function setIndexes() {
        //$query = sprintf ("ALTER TABLE `resources_temporary_events` ADD PRIMARY KEY ( `event_id` ), ADD INDEX ( `resource_id` )");
        //$this->db->query($query);
    }

    function addResource($resource_id, $day_of_week = false) {
        global $RESOURCES_ASSIGN_LOCKING_ACTIVE, $user;

        $this->resource_ids[] = $resource_id;
        $query = sprintf ("DELETE FROM resources_temporary_events WHERE resource_id = '%s'", $resource_id);
        $this->db->query($query);
        $resObject = new ResourceObject($resource_id);

        //when multiple assigns are allowed, we need no check for other assigns...
        if (!$resObject->getMultipleAssign()) {
            $assEvt = new AssignEventList($this->begin, $this->end, $resource_id, FALSE, FALSE, FALSE, false, $day_of_week);

            $now = time();
            if ($assEvt->existEvent()){
                while ($event = $assEvt->nextEvent()) {
                    $sql[] = "('" . md5(uniqid("tempo",1)) ."','$resource_id', '".$event->getAssignId()."', ".$event->getBegin().", ".$event->getEnd().", 'assign', $now)";
                }
            }
        }

        //...but we always need the check for the locks, so insert them
        if (($RESOURCES_ASSIGN_LOCKING_ACTIVE) && ($resObject->isLockable()) && ($resObject->isRoom()) && (getGlobalPerms($user->id) != "admin")) {
            $query = "SELECT lock_id, lock_begin, lock_end FROM resources_locks WHERE type = 'assign'";
            $this->db->query($query);
            while ($this->db->next_record()) {
                $sql[] = "('" . md5(uniqid("tempo",1)) ."','$resource_id', '".$this->db->f("lock_id")."', ".$this->db->f("lock_begin").", ".$this->db->f("lock_end").", 'lock', $now)";
            }
        }

        //insert data
        if ($sql) {
            $query = "INSERT INTO resources_temporary_events (event_id ,resource_id, assign_id,begin,end,type,mkdate) VALUES " . join(",",$sql);
            $this->db->query($query);
        }
    }

    function checkOverlap ($events, &$result, $index_mode = "assign_id") {
        if (sizeof($events) == 0) return false;
        if ($this->resource_ids) {
            foreach ($events as $obj) {
                $clause = sprintf ("((begin <= %s AND end > %s) OR (begin >=%s AND end <= %s) OR (begin <= %s AND end >= %s) OR (begin < %s AND end >= %s))", $obj->getBegin(), $obj->getBegin(), $obj->getBegin(), $obj->getEnd(),$obj->getBegin(), $obj->getEnd(), $obj->getEnd(), $obj->getEnd());
                $cases.= sprintf(" WHEN %s THEN '%s'", $clause, $obj->getId());
                $clauses[] = $clause;
            }

            $clause = join(" OR ",$clauses);
            $in = "('".join("','",$this->resource_ids)."')";

            $query = sprintf ("SELECT *, CASE %s END AS event_id FROM resources_temporary_events WHERE 1 AND (%s) AND resource_id IN %s ORDER BY begin", $cases, $clause, $in);
            $this->db->query($query);
            while ($this->db->next_record()) {
                $result[$this->db->f("resource_id")][($index_mode == "assign_id") ? $events[$this->db->f("event_id")]->getAssignId() : $events[$this->db->f("event_id")]->getAssignUserId()][] =
                    array("begin"=>$this->db->f("begin"), "end"=>$this->db->f("end"), "assign_id" => $this->db->f('assign_id'), "event_id"=>$this->db->f("event_id"),
                          "own_begin" =>$events[$this->db->f("event_id")]->getBegin(), "own_end" =>$events[$this->db->f("event_id")]->getEnd(),
                          "lock" =>($this->db->f("type") == "lock") ? TRUE : FALSE);
            }
            return;
        }
        $result = array();
    }
}
?>
