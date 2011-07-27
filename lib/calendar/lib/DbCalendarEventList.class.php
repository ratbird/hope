<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
DbCalendarEventList.class.php - 0.8.20020708
Klassen fuer Persoenlichen Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthien@gmx.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

//****************************************************************************

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once("config.inc.php");
require_once($RELATIVE_PATH_CALENDAR
        . "/lib/CalendarEvent.class.php");
require_once($RELATIVE_PATH_CALENDAR
        . "/lib/SeminarEvent.class.php");
require_once($RELATIVE_PATH_CALENDAR
        . "/lib/calendar_misc_func.inc.php");
require_once($RELATIVE_PATH_CALENDAR
        . "/lib/driver/$CALENDAR_DRIVER/list_driver.inc.php");

class DbCalendarEventList {

    var $start;           // Startzeit als Unix-Timestamp (int)
    var $end;             // Endzeit als Unix-Timestamp (int)
    var $ts;              // der "genormte" Timestamp s.o. (int)
    var $events;          // Termine (Object[])
    var $show_private;    // Private Termine anzeigen ? (boolean)
    var $user_id;         // User-ID aus PhpLib (String)
    var $range_id;

    // Konstruktor
    // bei Aufruf ohne Parameter: Termine von jetzt bis jetzt + 14 Tage
    function DbCalendarEventList ($range_id, $start = -1, $end = -1, $sort = TRUE) {
        global $user;

        if ($start == -1)
            $start = time();
        if ($end == -1)
            $end = mktime(23, 59, 59, date("n", $start), date("j", $start) + 7, date("Y", $start));

        $this->start = $start;
        $this->end = $end;
        $this->ts = mktime(12, 0, 0, date("n", $this->start), date("j", $this->start), date("Y", $this->start), 0);
        if ($range_id == $user->id)
            $this->show_private = TRUE;
        else
            $this->show_private = FALSE;
        $this->user_id = $user->id;
        $this->range_id = $range_id;
        $this->restore();
        if ($sort)
            $this->sort();
    }

    // public
    function getStart () {
        return $this->start;
    }

    // public
    function getEnd () {
        return $this->end;
    }

    // Persönliche Termine und Seminartermine werden gemischt. Es muss also
    // nicht mehr nachtraeglich sortiert werden.
    // private
    function restore () {
        list_restore($this);
    }

    // public
    function numberOfEvents () {
        return sizeof($this->events);
    }

    function existEvent () {
        return sizeof($this->events) > 0 ? TRUE : FALSE;
    }

    // public
    function nextEvent () {
        if(list(,$ret) = each($this->events));
            return $ret;
        return FALSE;
    }

    // public
    function bindSeminarEvents ($sem_ids = "") {

        if ($this->range_id != $this->user_id)
            return FALSE;

        if ($sem_ids == "") {
            $query = "SELECT su.status, su.gruppe, s.Name, t.*, th.title, th.description as details FROM seminar_user su "
                         . "LEFT JOIN seminare s USING(Seminar_id) LEFT JOIN termine t ON "
                         . "s.Seminar_id=range_id LEFT JOIN themen_termine tt ON (t.termin_id=tt.termin_id) "
                         . "LEFT JOIN themen th ON (tt.issue_id = th.issue_id) WHERE user_id = '" . $this->user_id
                         . "' AND ((date BETWEEN " . $this->getStart() . " AND " . $this->getEnd()
                         . ") OR (end_time BETWEEN " . $this->getStart() . " AND " . $this->getEnd()
                         . "))";
        } else {
            if (is_array($sem_ids))
                $sem_ids = implode("','", $sem_ids);
            $query = "SELECT su.status, su.gruppe, s.Name, t.*, th.title, th.description as details FROM seminar_user su "
                         . "LEFT JOIN seminare s USING(Seminar_id) LEFT JOIN termine t ON "
                         . "s.Seminar_id=range_id LEFT JOIN themen_termine tt ON (t.termin_id=tt.termin_id) "
                         . "LEFT JOIN themen th ON (tt.issue_id = th.issue_id) WHERE user_id = '" . $this->user_id
                         . "' AND range_id IN ('$sem_ids') AND "
                         . "((date BETWEEN " . $this->getStart() . " AND " . $this->getEnd()
                         . ") OR (end_time BETWEEN " . $this->getStart() . " AND " . $this->getEnd()
                         . "))";
        }
        $db = new DB_Seminar();
        $db->query($query);

        if ($db->num_rows() != 0) {
            while ($db->next_record()) {
                if ($db->f('status') === 'dozent') {
                    //wenn ich Dozent bin, zeige den Termin nur, wenn ich durchführender Dozent bin:
                    $termin = new SingleDate($db->f('termin_id'));
                    if (!in_array($this->user_id, $termin->getRelatedPersons())) {
                        continue;
                    }
                }
                $event = new SeminarEvent($db->f('termin_id'), array(
                        'DTSTART'         => $db->f('date'),
                        'DTEND'           => $db->f('end_time'),
                        'SUMMARY'         => $db->f('title') ? $db->f('title') : $db->f('Name'),
                        'STUDIP_CATEGORY' => $db->f('date_typ'),
                        'LOCATION'        => $db->f('raum'),
                        'DESCRIPTION'     => $db->f('details'),
                        'CLASS'           => 'PRIVATE',
                        'SEMNAME'         => $db->f('Name'),
                        'CREATED'         => $db->f('mkdate'),
                        'LAST-MODIFIED'   => $db->f('chdate')),
                        $db->f('range_id'));

                $event->setWritePermission($db->f('status') == 'tutor' || $db->f('status') == 'dozent');
                $this->events[] = $event;
            }
            $this->sort();
            return TRUE;
        }
        return FALSE;
    }

    function sort () {
        if ($this->events)
            usort($this->events, "cmp_list");
    }

    function &getAllEvents () {
        return $this->events;
    }

} // class DbCalendarEventList

?>
