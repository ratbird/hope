<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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

class CycleDataDB {

    function getTermine($metadate_id, $start = 0, $end = 0) {
        $db = new DB_Seminar();

        if (($start != 0) || ($end != 0)) {
            $db->query("SELECT termine.*, r.resource_id FROM termine LEFT JOIN resources_assign as r ON (termin_id = assign_user_id)  WHERE metadate_id = '$metadate_id' AND termine.date >= $start AND termine.date <= $end");
        } else {
            $db->query("SELECT termine.*, r.resource_id FROM termine LEFT JOIN resources_assign as r ON (termin_id = assign_user_id)  WHERE metadate_id = '$metadate_id'");
        }

        $ret = array();

        while ($db->next_record()) {
            $ret[] = $db->Record;
        }

        if (($start != 0) || ($end != 0)) {
            $db->query("SELECT * FROM ex_termine WHERE metadate_id = '$metadate_id' AND date >= $start AND date <= $end ORDER BY date ASC");
        } else {
            $db->query("SELECT * FROM ex_termine WHERE metadate_id = '$metadate_id' ORDER BY date ASC");
        }

        while ($db->next_record()) {
            $zw = $db->Record;
            $zw['ex_termin'] = TRUE;
            $ret[] = $zw;
        }

        if ($ret) {
            usort($ret, array('CycleDataDB', 'sort_dates'));
            return $ret;
        }

        return FALSE;
    }

    function sort_dates($a, $b) {
        if ($a['date'] == $b['date']) return 0;
        return ($a['date'] < $b['date']) ? -1 : 1;
    }

    function deleteNewerSingleDates($metadate_id, $timestamp, $keepIssues = false) {
        $db = new DB_Seminar();

        $c = 0;
        $db->query("SELECT * FROM termine WHERE metadate_id = '$metadate_id' AND date > $timestamp");
        while ($db->next_record()) {
            $termin =& new SingleDate($db->f('termin_id'));
            $termin->delete($keepIssues);
            $c++;
            unset($termin);
        }

        $db->query("DELETE FROM termine WHERE metadate_id = '$metadate_id' AND date > $timestamp");
        $db->query("DELETE FROM ex_termine WHERE metadate_id = '$metadate_id' AND date > $timestamp");
        return $c;
    }

    function getPredominantRoomDB($metadate_id, $filterStart = 0, $filterEnd = 0) {
        $db = new DB_Seminar();
        $rooms = array();

        if (($filterStart == 0) && ($filterEnd == 0)) {
            $query = "SELECT COUNT(resource_id) as c, resource_id FROM termine LEFT JOIN resources_assign ON (termin_id = assign_user_id) WHERE termine.metadate_id = '$metadate_id' GROUP BY resource_id ORDER BY c DESC";
        } else {
            $query = "SELECT COUNT(resource_id) as c, resource_id FROM termine LEFT JOIN resources_assign ON (termin_id = assign_user_id) WHERE termine.metadate_id = '$metadate_id' AND termine.date >= $filterStart AND termine.end_time <= $filterEnd GROUP BY resource_id ORDER BY c DESC";
        }

        $db->query($query);
        if ($db->num_rows() == 0) return FALSE;

        while ($db->next_record()) {
            if ($db->f('resource_id') != '') {
                $rooms[] = $db->f('resource_id');
            }
        }

        return $rooms;
    }

    function getFreeTextPredominantRoomDB($metadate_id, $filterStart = 0, $filterEnd = 0) {
        $db = new DB_Seminar();
        $rooms = array();

        if (($filterStart == 0) && ($filterEnd == 0)) {
            $query = "SELECT COUNT(raum) as c, raum FROM termine WHERE termine.metadate_id = '$metadate_id' GROUP BY raum ORDER BY c DESC";
        } else {
            $query = "SELECT COUNT(raum) as c, raum FROM termine WHERE termine.metadate_id = '$metadate_id' AND termine.date >= $filterStart AND termine.end_time <= $filterEnd GROUP BY raum ORDER BY c DESC";
        }

        $db->query($query);
        if ($db->num_rows() == 0) return FALSE;

        while ($db->next_record()) {
            if ($db->f('raum') != '') {
                return $db->f('raum');
            }
        }
    }
}
?>
