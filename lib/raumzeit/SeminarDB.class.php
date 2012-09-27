<?php
# Lifter002: DONE
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// SeminarDB.class.php
//
// Datenbank-Abfragen für Seminar.class.php
//
// +--------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +--------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +--------------------------------------------------------------------------+


/**
 * SeminarDB.class.php
 *
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @version     19. Oktober 2005
 * @access      protected
 * @package     raumzeit
 */

class SeminarDB
{
    function getIssues($seminar_id)
    {
        $query = "SELECT themen.*, folder.range_id, folder.folder_id, px_topics.topic_id
                  FROM themen
                  LEFT JOIN folder ON (range_id = issue_id)
                  LEFT JOIN px_topics ON (px_topics.topic_id = issue_id)
                  WHERE themen.seminar_id = ?
                  ORDER BY priority";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id));
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    function getSingleDates($seminar_id, $start = 0, $end = 0)
    {
        $query = "SELECT termine.*, resources_assign.resource_id, GROUP_CONCAT(trp.user_id) AS related_persons
                  FROM termine
                  LEFT JOIN termin_related_persons AS trp ON (termin_id = trp.range_id)
                  LEFT JOIN resources_assign ON (assign_user_id = termin_id)
                  WHERE termine.range_id = ?
                    AND (metadate_id IS NULL OR metadate_id = '')";
        $parameters = array($seminar_id);

        if ($start != 0 || $end != 0) {
            $query .= " AND termine.date BETWEEN ? AND ?";
            array_push($parameters, $start, $end);
        }

        $query .= " GROUP BY termin_id ORDER BY date";

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);

        $ret = array();
        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($data['related_persons']) {
                $data['related_persons'] = explode(',', $data['related_persons']);
            }
            
            $ret[] = $data;
        }

        return $ret;
    }

    function getStatOfNotBookedRooms($cycle_id, $seminar_id, $filterStart = 0, $filterEnd = 0)
    {
        $stat = array(
            'booked'         => 0,
            'open'           => 0,
            'open_rooms'     => array(),
            'declined'       => 0,
            'declined_dates' => array(),
        );

        $query = "SELECT termine.*, resources_assign.resource_id
                  FROM termine
                  LEFT JOIN resources_assign ON (assign_user_id = termin_id)
                  WHERE range_id = ? AND metadate_id = ?";
        $parameters = array($seminar_id, $cycle_id);

        if ($filterStart != 0 || $filterEnd != 0) {
            $query .= " AND date >= ? AND end_time <= ?";
            array_push($parameters, $filterStart, $filterEnd);
        }
        $query .= " ORDER BY date";

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $stat['all'] += 1;
            if ($row['resource_id']) {
                $stat['booked'] += 1;
            } else {
                $stat['open'] += 1;
                $stat['open_rooms'][] = $row;
            }
        }

        // count how many singledates have a declined room-request
        $query = "SELECT *
                  FROM termine t
                  LEFT JOIN resources_requests AS rr ON (t.termin_id = rr.termin_id)
                  WHERE range_id = ? AND t.metadate_id = ? AND closed = 3";
        $parameters = array($seminar_id, $cycle_id);

        if ($filterStart != 0 && $filterEnd != 0) {
            $query .= " AND date >= ? AND end_time <= ?";
            array_push($parameters, $filterStart, $filterEnd);
        }
        $query .= " ORDER BY date";

        $stmt = DBManager::get()->prepare($query);
        $stmt->execute($parameters);

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stat['declined'] += 1;
            $stat['declined_dates'][] = $data;
        }

        return $stat;
    }

    function countRequestsForSingleDates($cycle_id, $seminar_id, $filterStart = 0, $filterEnd = 0)
    {
        $query = "SELECT COUNT(*)
                  FROM termine AS t
                  LEFT JOIN resources_requests AS rr ON (t.termin_id = rr.termin_id)
                  WHERE seminar_id = ? AND t.metadate_id = ? AND closed = 0";
        $parameters = array($seminar_id, $cycle_id);

        if ($filterStart > 0 || $filterEnd > 0) {
            $query .= " AND `date` >= ? AND end_time <= ?";
            array_push($parameters, $filterStart, $filterEnd);
        }

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        return $statement->fetchColumn();
    }

    // removes all singleDates which are NOT between $start and $end
    function removeOutRangedSingleDates($start, $end, $seminar_id)
    {
        $query = "SELECT termin_id
                  FROM termine
                  WHERE range_id = ? AND (`date` NOT BETWEEN ? AND ?)
                    AND NOT (metadate_id IS NULL OR metadate_id = '')";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $start, $end));
        $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $termin = new SingleDate($id);
            $termin->delete();
            unset($termin);
        }

        if (count($ids) > 0) {
            // remove all assigns for the dates in question
            $query = "SELECT assign_id FROM resources_assign WHERE assign_user_id IN (?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($ids));

            while ($id = $statement->fetchColumn()) {
                AssignObject::Factory($assign_id)->delete();
            }
        }

        // $query = "DELETE FROM termine
        //           WHERE range_id = ? AND (`date` NOT BETWEEN ? AND ?)
        //             AND NOT (metadate_id IS NULL OR metadate_id = '')";
        // $statement = DBManager::get()->prepare($query);
        // $statement->execute(array($seminar_id, $start, $end));

        $query = "DELETE FROM ex_termine
                  WHERE range_id = ? AND (`date` NOT BETWEEN ? AND ?)
                    AND NOT (metadate_id IS NULL OR metadate_id = '')";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $start, $end));
    }

    function hasDatesOutOfDuration($start, $end, $seminar_id)
    {
        $query = "SELECT COUNT(*)
                  FROM termine
                  WHERE range_id = ? AND `date` NOT BETWEEN ? AND ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($seminar_id, $start, $end));
        return $statement->fetchColumn();
    }

    function getFirstDate($seminar_id)
    {
        $termine = array();

        $presence_types = getPresenceTypes();
        if (count($presence_types) > 0) {
            $query = "SELECT termin_id, date, end_time
                      FROM termine
                      WHERE range_id = ? AND date_typ IN (?)
                      ORDER BY date";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($seminar_id, $presence_types));

            $start = 0;
            $end = 0;

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (($start == 0 && $end == 0) || ($start == $row['date'] && $end == $row['end_time'])) {
                    $termine[] = $row['termin_id'];
                    $start     = $row['date'];
                    $end       = $row['end_time'];
                }
            }
        }

        return $termine ?: false;
    }

    function getNextDate($seminar_id)
    {
        $termin = array();

        $query = "SELECT termin_id, date, end_time
                  FROM termine 
                  WHERE range_id = ? AND date > UNIX_TIMESTAMP(NOW() - INTERVAL 1 HOUR)
                  ORDER BY date, end_time";
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($seminar_id));

        $start = 0;
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($start == 0 || $start == $data['date']) {
                $termin[] = $data['termin_id'];
                $start = $data['date'];
            }
        }

        $ex_termin = array();

        $query = "SELECT termin_id
                  FROM ex_termine 
                  WHERE range_id = ? AND date > UNIX_TIMESTAMP(NOW() - INTERVAL 1 HOUR)
                    AND content != '' AND content IS NOT NULL
                  ORDER BY date
                  LIMIT 1";
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($seminar_id));

        while ($termin_id = $stmt->fetchColumn()) {
            $ex_termin[] = $termin_id;
        }

        return compact('termin', 'ex_termin');
    }

    /**
     * vergisst die Einträge in resources_requests_properties
     * @deprecated
     * @param unknown_type $id
     * @return boolean
     */
    function deleteRequest($id)
    {
        $query = "DELETE FROM resources_requests
                  WHERE seminar_id = ?
                    AND (termin_id = '' OR termin_id IS NULL)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));

        return true;
    }

}