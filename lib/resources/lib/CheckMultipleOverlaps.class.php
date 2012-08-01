<?php
# Lifter002: DONE - not applicable
# Lifter003: TEST
# Lifter007: TODO  methods need documentation
# Lifter010: DONE - not applicable

/**
* CheckMultipleOverlaps.class.php
*
* checks overlaps for multiple resources, seminars and assign objects
* via a special table
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

require_once $RELATIVE_PATH_RESOURCES . '/lib/AssignEventList.class.php';
require_once $RELATIVE_PATH_RESOURCES . '/lib/ResourceObject.class.php';

class CheckMultipleOverlaps
{
    var $begin;
    var $end;
    var $resource_ids;      //all the resources in the actual check-set

    function setTimeRange($begin, $end)
    {
        $this->begin = $begin;
        $this->end   = $end;
    }

    function setAutoTimeRange($assObjs)
    {
        $end = 0;
        foreach ($assObjs as $obj) {
            if (!$begin) {
                $begin = $obj->getBegin();
            }
            if ($obj->getBegin() < $begin) {
                $begin = $obj->getBegin();
            }
            if ($obj->getRepeatEnd() > $end) {
                $end = $obj->getRepeatEnd();
            }
        }
        $this->setTimeRange($begin, $end);
    }

    function addResource($resource_id, $day_of_week = false)
    {
        // check, if the added resources needs to be checked
        $resObj = ResourceObject::Factory($resource_id);

        if (!$resObj->getMultipleAssign()) {
            $this->resource_ids[] = $resource_id;
            return true;
        }

        return false;
    }

    function checkOverlap ($events, &$result, $index_mode = 'assign_id')
    {
        if (count($events) == 0) {
            return false;
        }
        if (count($this->resource_ids) == 0) {
            $result = array();
            return;
        }

        $parameters = $clauses = $assign_ids = array();
        $cases = '';
        foreach (array_values($events) as $i => $obj) {
            $clause = "((begin <= :begin{$i} AND end > :begin{$i}) OR
                        (begin >= :begin{$i} AND end <= :end{$i}) OR
                        (begin <= :begin{$i} AND end >= :end{$i}) OR
                        (begin <  :end{$i} AND end >= :end{$i}))";

            $parameters['begin' . $i] = $obj->getBegin();
            $parameters['end' . $i]   = $obj->getEnd();

            $cases .= sprintf(" WHEN %s THEN :id{$i}", $clause);
            $parameters['id' . $i] = $obj->getId();

            $assign_ids[] = $obj->assign_id;
            $clauses[]    = $clause;
        }

        $clause = join(' OR ', $clauses);

        $query = "SELECT resource_id, `begin`, end, assign_id, type,
                         CASE {$cases} END AS event_id
                  FROM resources_temporary_events
                  WHERE ({$clause}) AND resource_id IN (:resource_ids)
                    AND assign_id NOT IN (:assign_ids)
                  ORDER BY begin";
        $parameters[':resource_ids'] = $this->resource_ids;
        $parameters[':assign_ids']   = $assign_ids;

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $index = ($index_mode == 'assign_id')
                   ? $events[$row['event_id']]->getAssignId()
                   : $events[$row['event_id']]->getAssignUserId();
            $result[$row['resource_id']][$index][] = array(
                'begin'     => $row['begin'],
                'end'       => $row['end'],
                'assign_id' => $row['assign_id'],
                'event_id'  => $row['event_id'],
                'own_begin' => $events[$row['event_id']]->getBegin(),
                'own_end'   => $events[$row['event_id']]->getEnd(),
                'lock'      => ($row['type'] == 'lock')
            );
        }
    }
}