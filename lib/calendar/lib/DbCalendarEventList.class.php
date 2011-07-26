<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * DbCalendarEventList.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/SeminarEvent.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/calendar_misc_func.inc.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/DbCalendarDay.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/calendar_visual.inc.php');

class DbCalendarEventList
{

    var $start;           // Startzeit als Unix-Timestamp (int)
    var $end;             // Endzeit als Unix-Timestamp (int)
    var $ts;              // der "genormte" Timestamp s.o. (int)
    var $events;          // Termine (Object[])
    var $show_private;    // Private Termine anzeigen ? (boolean)
    var $wdays;
    var $calendar;

    // Konstruktor
    // bei Aufruf ohne Parameter: Termine von jetzt bis jetzt + 8 Tage
    function DbCalendarEventList(&$calendar, $start = NULL, $end = NULL, $sort = true, $sem_ids = NULL, $restrictions = NULL)
    {
        global $user;

        if (is_null($start)) {
            $start = time();
        }
        if (is_null($end)) {
            $end = mktime(23, 59, 59, date('n', $start), date('j', $start) + 8, date('Y', $start));
        }

        $this->start = $start;
        $this->end = $end;
        $this->ts = mktime(12, 0, 0, date('n', $this->start), date('j', $this->start), date('Y', $this->start), 0);
        $end_ts = mktime(12, 0, 0, date('n', $this->end), date('j', $this->end), date('Y', $this->end), 0);
        for ($ts = $this->ts; $ts < $end_ts; $ts += 86400) {
            $this->wdays[$ts] = new DbCalendarDay($calendar, $ts, NULL, $restrictions, $sem_ids);
        }

        foreach ((array) $this->wdays as $wday) {
            foreach ($wday->events as $event) {
                if ($event->getStart() <= $this->end && $event->getEnd() >= $this->start
                        && ($calendar->havePermission(CALENDAR_PERMISSION_READABLE) || $event->properties['CLASS'] == 'PUBLIC' || $calendar->getRange() == CALENDAR_RANGE_SEM)) {
                    $event_key = $event->getId() . $event->getStart();
                    $this->events["$event_key"] = $event;
                }
            }
        }

        if ($sort) {
            $this->sort();
        }
        $this->calendar = $calendar;
    }

    // public
    function getStart()
    {
        return $this->start;
    }

    // public
    function getEnd()
    {
        return $this->end;
    }

    // public
    function numberOfEvents()
    {
        return sizeof($this->events);
    }

    function existEvent()
    {
        return sizeof($this->events) > 0 ? true : false;
    }

    // public
    function nextEvent()
    {
        if (list(, $ret) = each($this->events)) {
            return $ret;
        }
        return false;
    }

    function sort()
    {
        if ($this->events) {
            usort($this->events, "cmp_list");
        }
    }

    function &getAllEvents()
    {
        return $this->events;
    }

}
