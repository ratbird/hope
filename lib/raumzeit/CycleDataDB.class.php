<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// CycleDataDB.class.php
//
// Datenbank-Abfragen für CycleData.class.php
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
 * CycleDataDB.class.php
 *
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @version     19. Oktober 2005
 * @access      protected
 * @package     raumzeit
 */
class CycleDataDB
{
    function getTermine($metadate_id, $start = 0, $end = 0)
    {
        $db = new DB_Seminar();

        if (($start != 0) || ($end != 0)) {
            $db->query("SELECT termine.*, r.resource_id,GROUP_CONCAT(trp.user_id) as related_persons FROM termine LEFT JOIN termin_related_persons trp ON termin_id=trp.range_id LEFT JOIN resources_assign as r ON (termin_id = assign_user_id)  WHERE metadate_id = '$metadate_id' AND termine.date >= $start AND termine.date <= $end GROUP BY termin_id ORDER BY NULL");
        } else {
            $db->query("SELECT termine.*, r.resource_id,GROUP_CONCAT(trp.user_id) as related_persons FROM termine LEFT JOIN termin_related_persons trp ON termin_id=trp.range_id LEFT JOIN resources_assign as r ON (termin_id = assign_user_id)  WHERE metadate_id = '$metadate_id' GROUP BY termin_id ORDER BY NULL");
        }

        $ret = array();

        while ($db->next_record()) {
            $data = $db->Record;
            $data['related_persons'] = $data['related_persons'] ? explode(',', $data['related_persons']) : array();
            $ret[] = $data;
        }

        if (($start != 0) || ($end != 0)) {
            $db->query("SELECT ex_termine.*, GROUP_CONCAT(trp.user_id) as related_persons FROM ex_termine LEFT JOIN termin_related_persons trp ON termin_id=trp.range_id WHERE metadate_id = '$metadate_id' AND date >= $start AND date <= $end GROUP BY termin_id ORDER BY NULL");
        } else {
            $db->query("SELECT ex_termine.*, GROUP_CONCAT(trp.user_id) as related_persons FROM ex_termine LEFT JOIN termin_related_persons trp ON termin_id=trp.range_id WHERE metadate_id = '$metadate_id' GROUP BY termin_id ORDER BY NULL");
        }

        while ($db->next_record()) {
            $zw = $db->Record;
            $zw['ex_termin'] = TRUE;
            $zw['related_persons'] = $zw['related_persons'] ? explode(',', $zw['related_persons']) : array();
            $ret[] = $zw;
        }

        if ($ret) {
            usort($ret, array('CycleDataDB', 'sort_dates'));
            return $ret;
        }

        return FALSE;
    }

    function sort_dates($a, $b)
    {
        if ($a['date'] == $b['date']) return 0;
        return ($a['date'] < $b['date']) ? -1 : 1;
    }

    function deleteNewerSingleDates($metadate_id, $timestamp, $keepIssues = false)
    {
        $db = new DB_Seminar();

        $c = 0;
        $db->query("SELECT * FROM termine WHERE metadate_id = '$metadate_id' AND date > $timestamp");
        while ($db->next_record()) {
            $termin = new SingleDate($db->f('termin_id'));
            $termin->delete($keepIssues);
            $c++;
            unset($termin);
        }

        $db->query("DELETE FROM termine WHERE metadate_id = '$metadate_id' AND date > $timestamp");
        $db->query("DELETE FROM ex_termine WHERE metadate_id = '$metadate_id' AND date > $timestamp");
        return $c;
    }

    function getPredominantRoomDB($metadate_id, $filterStart = 0, $filterEnd = 0)
    {
        $db = new DB_Seminar();
        $rooms = array();

        if (($filterStart == 0) && ($filterEnd == 0)) {
            $query = "SELECT COUNT(resource_id) as c, resource_id FROM termine INNER JOIN resources_assign ON (termin_id = assign_user_id) WHERE termine.metadate_id = '$metadate_id' GROUP BY resource_id ORDER BY c DESC";
        } else {
            $query = "SELECT COUNT(resource_id) as c, resource_id FROM termine INNER JOIN resources_assign ON (termin_id = assign_user_id) WHERE termine.metadate_id = '$metadate_id' AND termine.date >= $filterStart AND termine.end_time <= $filterEnd GROUP BY resource_id ORDER BY c DESC";
        }

        $db->query($query);
        if ($db->num_rows() == 0) return FALSE;

        while ($db->next_record()) {
            if ($db->f('resource_id') != '') {
                $rooms[$db->f('resource_id')] = $db->f('c');
            }
        }

        return $rooms;
    }

    function getFreeTextPredominantRoomDB($metadate_id, $filterStart = 0, $filterEnd = 0)
    {
        if (($filterStart == 0) && ($filterEnd == 0)) {
            $query = "SELECT COUNT(raum) as c, raum FROM termine LEFT JOIN resources_assign ON (termin_id = assign_user_id) WHERE termine.metadate_id = '$metadate_id' AND assign_user_id IS NULL GROUP BY raum ORDER BY c DESC";
        } else {
            $query = "SELECT COUNT(raum) as c, raum FROM termine LEFT JOIN resources_assign ON (termin_id = assign_user_id) WHERE termine.metadate_id = '$metadate_id' AND assign_user_id IS NULL AND termine.date >= $filterStart AND termine.end_time <= $filterEnd GROUP BY raum ORDER BY c DESC";
        }

        $db = DBManager::get()->query($query);
        if (!$data = $db->fetchAll(PDO::FETCH_ASSOC)) return false;

        foreach ($data as $room) {
            $ret[$room['raum']] = $room['c'];
        }

        return $ret;
    }

    /**
     * returns the first date for a given metadate_id as array
     * @param string $metadate_id
     * @return array
     */
    function getFirstDate($metadate_id)
    {
        $db = DbManager::get();
        $ret = $db->query("SELECT * FROM termine WHERE metadate_id=" . $db->quote($metadate_id) . " ORDER BY date ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $ret;
    }
}
