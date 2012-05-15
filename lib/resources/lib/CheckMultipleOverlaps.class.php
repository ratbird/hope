<?php
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

    function addResource($resource_id, $day_of_week = false) {
        // check, if the added resources needs to be checked
        $resObj = ResourceObject::Factory($resource_id);
        
        if (!$resObj->getMultipleAssign()) {
            $this->resource_ids[] = $resource_id;
            return true;
        }
        
        return false;
    }

    function checkOverlap ($events, &$result, $index_mode = "assign_id") {
        if (sizeof($events) == 0) return false;
        if ($this->resource_ids) {
            foreach ($events as $obj) {
                $clause = sprintf ("((begin <= %s AND end > %s) OR (begin >=%s AND end <= %s) OR (begin <= %s AND end >= %s) OR (begin < %s AND end >= %s))", $obj->getBegin(), $obj->getBegin(), $obj->getBegin(), $obj->getEnd(),$obj->getBegin(), $obj->getEnd(), $obj->getEnd(), $obj->getEnd());
                $cases .= sprintf(" WHEN %s THEN '%s'", $clause, $obj->getId());
                $assign_ids[] = $obj->assign_id;
                $clauses[]    = $clause;
            }

            $clause = join(" OR ",$clauses);
            $in = "('".join("','",$this->resource_ids)."')";

            $query = sprintf ("SELECT *, CASE %s END AS event_id FROM resources_temporary_events WHERE 1 AND (%s) AND resource_id IN %s
                AND assign_id NOT IN('%s')
                ORDER BY begin", $cases, $clause, $in, implode("', '", $assign_ids));
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