<?
/**
* GroupCalendar.class.php
*
*
*
*
* @author        Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version    $Id: GroupCalendar.class.php,v 1.2 2009/10/07 20:10:42 thienel Exp $
* @access        public
* @modulegroup    calendar_modules
* @module        calendar
* @package    Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// GroupCalendar.class.php
//
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
// Suchi & Berg GmbH <info@data-quest.de>
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


require_once $RELATIVE_PATH_CALENDAR . '/lib/ErrorHandler.class.php';
require_once 'lib/functions.php';
//require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/Calendar.class.php");
require_once $RELATIVE_PATH_CALENDAR . '/lib/calendar_misc_func.inc.php';


class GroupCalendar extends Calendar {

    var $group_id;
    var $calendars;
    var $view;

    function GroupCalendar ($group_id, $user_id) {

    //    Calendar::Calendar($user_name);
        $this->group_id = $group_id;
        $this->permission = CALENDAR_PERMISSION_FORBIDDEN;
        $this->user_id = $user_id;
        $this->getCalendars();

    }

    function getCalendars () {

        // own calendar
        $this->calendars[] = Calendar::getInstance(get_userid());

        $db =& new DB_Seminar();
        $db_permission =& new DB_Seminar();

        $query = "SELECT s.statusgruppe_id, ";
        $query .= "aum.username, aum.user_id FROM statusgruppen s LEFT JOIN ";
        $query .= "statusgruppe_user su USING(statusgruppe_id) LEFT JOIN ";
        $query .= "auth_user_md5 aum USING(user_id) WHERE ";
        $query .= "s.statusgruppe_id = '{$this->group_id}' AND s.range_id = '{$this->user_id}' AND ";
        $query .= "su.user_id <> '{$this->user_id}' AND aum.perms NOT IN ('root', 'admin') ORDER BY aum.nachname";

        $db->query($query);

        while ($db->next_record()) {
            /*
            $query = "SELECT su.calpermission FROM statusgruppen s LEFT JOIN statusgruppe_user su USING ";
            $query .= "(statusgruppe_id) WHERE range_id = '" . $db->f('user_id');
            $query .= "' AND user_id = '{$this->user_id}' AND su.calpermission > 1";
            */
            $query = "SELECT calpermission FROM contact WHERE owner_id = '" . $db->f('user_id');
            $query .= "' AND user_id = '{$this->user_id}' AND calpermission > 1";
            $db_permission->query($query);

            if (!$db_permission->num_rows()) {
                continue;
            }

            $this->calendars[] = Calendar::getInstance($db->f('user_id'));
        }

    }

    function &nextCalendar () {
        static $pointer = 0;

        if ($pointer < sizeof($this->calendars))
            return $this->calendars[$pointer++];

        $pointer = 0;
        return FALSE;
    }

    function getId () {

        return $this->group_id;
    }

    // private
    function addEventObj ($event, $updated, $selected_users = NULL) {
        if (is_null($selected_users) || !is_array($selected_users)) {
            while ($calendar = $this->nextCalendar()) {
                $event2 = new DbCalendarEvent($calendar, '', $event->properties);
                $calendar->addEventObj($event2, $updated);
            }
        }
        else {
            while ($calendar = $this->nextCalendar()) {
                if (in_array($calendar->getUserName(), $selected_users)) {
                    $event2 = new DbCalendarEvent($calendar, '', $event->properties);
                    $calendar->addEventObj($event2, $updated);
                }
            }
        }
    }

    function mergeEvents () {
        $this->merged_events = array();
        foreach ($this->calendars as $calendar) {
            if (is_array($calendar->view->events))
                $this->view->events += $calendar->view->events;
        }

        if (sizeof($this->view->events))
            usort($this->view->events, 'cmp_list');
    }

    function createEvent ($properties = NULL) {
        parent::createEvent($properties);

        foreach ($this->calendars as $calendar)
            $calendar->createEvent($properties);
    }

    function toStringDay ($day_time, $start_time, $end_time, $restrictions = NULL) {
        $this->headline = sprintf(_("Terminkalender der Gruppe %s - Tagesansicht"), $this->getGroupName());

        // get the events of all group members
        foreach ($this->calendars as $calendar) {
            $calendar->view = new DbCalendarDay($calendar, $day_time, NULL, $restrictions, Calendar::getBindSeminare($calendar->getUserId()));
        }

        $this->view = $this->calendars[0]->view;

        return $GLOBALS['template_factory']->render('calendar/day_view_group', array('group_calendar' => $this, 'atime' => $day_time, 'start_time' => $start_time, 'end_time' => $end_time, 'group_id' => $this->group_id));
    }

    function toStringWeek ($week_time, $start_time, $end_time, $restrictions = NULL) {
        $this->headline = sprintf(_("Terminkalender der Gruppe %s - Wochenansicht"),
                $this->getGroupName());

        // get the events of all group members
        foreach ($this->calendars as $calendar) {
            $calendar->view = new DbCalendarWeek($calendar, $week_time, $this->getUserSettings('type_week'), $restrictions, Calendar::getBindSeminare($calendar->getUserId()));
        }

        $this->view = $this->calendars[0]->view;

        return $GLOBALS['template_factory']->render('calendar/week_view_group', array('group_calendar' => $this, 'atime' => $week_time, 'start_time' => $start_time, 'end_time' => $end_time, 'group_id' => $this->group_id));

        //return create_week_view_group($this, $week_time, $start_time, $end_time, $this->group_id);
    }

    function toStringMonth ($month_time, $step = NULL, $restrictions = NULL) {
        $this->headline = sprintf(_("Terminkalender der Gruppe %s - Monatsansicht"),
                $this->getGroupName());

        // get the events of all group members
        foreach ($this->calendars as $calendar) {
            $calendar->view = new DbCalendarMonth($calendar, $month_time, $restrictions, Calendar::getBindSeminare($calendar->getUserId()));
        }

        $this->view = $this->calendars[0]->view;

        return create_month_view($this, $month_time);
    }

    function toStringYear ($year_time, $restrictions = NULL) {
        $this->headline = sprintf(_("Terminkalender der Gruppe %s - Jahresansicht"),
            $this->getGroupName());

        // get the events of all group members
        foreach ($this->calendars as $calendar) {
            $calendar->view = new DbCalendarYear($calendar, $year_time, $restrictions, Calendar::getBindSeminare($calendar->getUserId()));
        //    $this->calendars[$i]->view->bindSeminarEvents(
            //    Calendar::getBindSeminare($this->calendars[$i]->getUserId()), $restrictions);
        }

        $this->view = $this->calendars[0]->view;

        return create_year_view($this);
    }

}

?>