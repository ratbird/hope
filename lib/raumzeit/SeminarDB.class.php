<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

class SeminarDB {
    function getIssues($seminar_id) {
        $db = new DB_Seminar();

        $ret = Array();

        $db->query("SELECT themen.*, folder.range_id, folder.folder_id, px_topics.topic_id FROM themen LEFT JOIN folder ON (range_id = issue_id) LEFT JOIN px_topics ON (px_topics.topic_id = issue_id) WHERE themen.seminar_id = '$seminar_id' ORDER BY priority");
        while ($db->next_record()) {
            $ret[] = $db->Record;
        }

        return $ret;
    }

    function getSingleDates($seminar_id, $start = 0, $end = 0) {
        $db = new DB_Seminar();

        $ret = Array();

        if (($start != 0) || ($end != 0)) {
            $db->query("SELECT termine.*, resources_assign.resource_id,GROUP_CONCAT(trp.user_id) as related_persons FROM termine LEFT JOIN termin_related_persons trp ON termin_id=trp.range_id LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE termine.range_id = '$seminar_id' AND (metadate_id IS NULL OR metadate_id = '') AND termine.date >= $start AND termine.date <= $end GROUP BY termin_id ORDER BY date");
        } else {
            $db->query("SELECT termine.*, resources_assign.resource_id,GROUP_CONCAT(trp.user_id) as related_persons FROM termine LEFT JOIN termin_related_persons trp ON termin_id=trp.range_id LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE termine.range_id = '$seminar_id' AND (metadate_id IS NULL OR metadate_id = '') GROUP BY termin_id ORDER BY date");
        }

        while($db->next_record()) {
            $data = $db->Record;
            $data['related_persons'] = $data['related_persons'] ? explode(',', $data['related_persons']) : array();
            $ret[] = $data;
        }

        return $ret;
    }

    function getStatOfNotBookedRooms($cycle_id, $seminar_id, $filterStart = 0, $filterEnd = 0) {
        $db = new DB_Seminar();
        if (($filterStart == 0) && ($filterEnd == 0)) {
            $query = "SELECT termine.*, resources_assign.resource_id FROM termine LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE range_id = '$seminar_id' AND metadate_id = '$cycle_id' ORDER BY date";
        } else {
            $query = "SELECT termine.*, resources_assign.resource_id FROM termine LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE range_id = '$seminar_id' AND metadate_id = '$cycle_id' AND date >= $filterStart AND end_time <= $filterEnd ORDER BY date";
        }
        $db->query($query);
        while ($db->next_record()) {
            $stat['all']++;
            if ($db->f('resource_id')) {
                $stat['booked']++;
            } else {
                $stat['open']++;
                $stat2[] = $db->Record;
            }
        }
        $stat['open_rooms'] = $stat2;


        // count how many singledates have a declined room-request
        $stmt = DBManager::get()->query("SELECT * FROM termine t
            LEFT JOIN resources_requests rr ON (t.termin_id = rr.termin_id)
            WHERE range_id = '$seminar_id' AND t.metadate_id = '$cycle_id' AND closed = 3"
            . (($filterStart != 0 && $filterEnd != 0) ? " AND date >= $filterStart AND end_time <= $filterEnd " : '') .
            " ORDER BY date");

        $tmp = array();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stat['declined_dates'][] = $data;
            $stat['declined']++;
        }

        return $stat;
    }

    function countRequestsForSingleDates($cycle_id, $seminar_id, $filterStart = 0, $filterEnd = 0) {
        $db = new DB_Seminar();
        if (($filterStart == 0) && ($filterEnd == 0)) {
            $query = "SELECT COUNT(*) AS anzahl FROM termine t LEFT JOIN resources_requests rr ON (t.termin_id = rr.termin_id) WHERE seminar_id = '$seminar_id' AND t.metadate_id = '$cycle_id' AND closed = 0";
        } else {
            $query = "SELECT COUNT(*) AS anzahl FROM termine t LEFT JOIN resources_requests rr ON (t.termin_id = rr.termin_id) WHERE seminar_id = '$seminar_id' AND t.metadate_id = '$cycle_id' AND closed = 0 AND date >= $filterStart AND end_time <= $filterEnd";
        }

        $db->query($query);
        $db->next_record();
        return $db->f('anzahl');
    }

    // removes all singleDates which are NOT between $start and $end
    function removeOutRangedSingleDates($start, $end, $seminar_id) {
        $db = new DB_Seminar();
        $db->query("SELECT * FROM termine WHERE (date < $start OR date > $end) AND range_id = '$seminar_id' AND NOT (metadate_id IS NULL OR metadate_id = '')");
        $in_list = '(';
        $i = 0;
        while ($db->next_record()) {
            if ($i == 1) {
                $in_list .= ',';
            }
            $in_list .= "'".$db->f('termin_id')."'";
            $i = 1;
            $termin = new SingleDate($db->f('termin_id'));
            $termin->delete();
            unset($termin);
        }
        $in_list .= ')';

        if ($in_list != '()') {
            $db->query($query = "DELETE FROM resources_assign WHERE assign_user_id IN $in_list");
        }

        //$db->query($query = "DELETE FROM termine WHERE (date < $start OR date > $end) AND range_id = '$seminar_id' AND NOT (metadate_id IS NULL OR metadate_id = '')");
        $db->query($query = "DELETE FROM ex_termine WHERE (date < $start OR date > $end) AND range_id = '$seminar_id' AND NOT (metadate_id IS NULL OR metadate_id = '')");
    }

    function hasDatesOutOfDuration($start, $end, $seminar_id) {
        $db = new DB_Seminar();
        $db->query("SELECT COUNT(*) as c FROM termine WHERE (date < $start OR date > $end) AND range_id = '$seminar_id'");
        $db->next_record();
        return $db->f('c');
    }

    function getFirstDate($seminar_id)
    {
        $termine = array();
        $db = new DB_Seminar("SELECT termin_id, date, end_time FROM termine
            WHERE range_id = '$seminar_id' AND date_typ IN ".getPresenceTypeClause()."
            ORDER BY date");
        $start = 0;
        $end = 0;
        while ($db->next_record()) {
            if (($start == 0 && $end == 0) || ($start == $db->f('date') && $end == $db->f('end_time'))) {
                $termine[] = $db->f('termin_id');
                $start = $db->f('date');
                $end = $db->f('end_time');
            }
        }

        return sizeof($termine) ? $termine : false;
    }

    function getNextDate($seminar_id)
    {
        $termin = array();
        $ex_termin = 0;
        $db = new DB_Seminar("SELECT termin_id, date, end_time FROM termine WHERE range_id = '$seminar_id' AND date > ".(time() - 3600)." ORDER BY date");
        $start = 0;
        $end = 0;
        while ($db->next_record()) {
            if (($start == 0 && $end == 0) || ($start == $db->f('date') && $end == $db->f('end_time'))) {
                $termin[] = $db->f('termin_id');
                $start = $db->f('date');
                $end = $db->f('end_time');
            }
        }

        $db = new DB_Seminar("SELECT termin_id FROM ex_termine WHERE range_id = '$seminar_id' AND date > ".(time() - 3600)." AND content != '' AND content IS NOT NULL ORDER BY date LIMIT 1");
        if ($db->next_record()) {
            $ex_termin = $db->f('termin_id');
        }

        return array('termin' => $termin, 'ex_termin' => $ex_termin);
    }

    /**
     * vergisst die Einträge in resources_requests_properties
     * @deprecated
     * @param unknown_type $id
     * @return boolean
     */
    function deleteRequest($id) {
        $db = new DB_Seminar();
        $db->query("DELETE FROM resources_requests WHERE seminar_id = '$id' AND (termin_id = '' OR termin_id IS NULL)");
        return TRUE;
    }

}
