<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * DbCalendarDay.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarDay.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/calendar_misc_func.inc.php');

class DbCalendarDay extends CalendarDay
{

    var $events = array();          // Termine (Object[])
    var $events_delete;   // Termine, die geloescht werden (Object[])
    var $events_created;
    var $arr_pntr;       // "private" function nextEvent()
    var $user_id;         // User-ID (String)
    var $driver;
    var $permission;

    // Konstruktor
    function DbCalendarDay(&$calendar, $tmstamp, $events = NULL, $restrictions = NULL, $sem_ids = NULL)
    {

        $this->user_id = $calendar->getUserId();
        $this->permission = $calendar->getPermission();
        CalendarDay::CalendarDay($tmstamp);
        $this->driver = CalendarDriver::getInstance($calendar->getUserId());
        if (is_null($events))
            $this->restore($restrictions, $sem_ids);
        $this->sort();
        $this->arr_pntr = 0;
    }

    // Anzahl von Terminen innerhalb eines bestimmten Zeitabschnitts
    // default one day
    // public
    function numberOfEvents($start = 0, $end = 86400)
    {
        $i = 0;
        $count = 0;
        while ($aterm = $this->events[$i]) {
            if ($aterm->getStart() >= $this->getStart() + $start && $aterm->getStart() <= $this->getStart() + $end) {
                $count++;
            }
            $i++;
        }
        return $count - 1;
    }

    // public
    function numberOfSimultaneousApps($term)
    {
        $i = 0;
        $count = 0;
        while ($aterm = $this->events[$i]) {
            if ($aterm->getStart() >= $term->getStart() && $aterm->getStart() < $term->getEnd()) {
                $count++;
            }
            $i++;
        }
        return ($count);
    }

    // Termin hinzufuegen
    // Der Termin wird gleich richtig einsortiert
    // public
    function addEvent($term)
    {
        $this->events[] = $term;
        $this->sort();
        //	return true;
    }

    // Termin loeschen
    // public
    function delEvent($id)
    {
        for ($i = 0; $i < sizeof($this->events); $i++) {
            if ($id != $this->events[$i]->getId()) {
                $app_bck[] = $this->events[$i];
            } else {
                $this->events_delete[] = $this->events[$i];
            }
        }

        if (sizeof($app_bck) == sizeof($this->events)) {
            return false;
        }

        $this->events = $app_bck;
        return true;
    }

    // ersetzt vorhandenen Termin mit uebergebenen Termin, wenn ID gleich
    // public
    function replaceEvent($term)
    {
        for ($i = 0; $i < sizeof($this->events); $i++) {
            if ($this->events[$i]->getId() == $term->getId()) {
                $this->events[$i] = $term;
                $this->sort();
                return true;
            }
        }

        return false;
    }

    // Abrufen der Termine innerhalb eines best. Zeitraums
    // default 1 hour
    // public
    function nextEvent($start = -1, $step = 3600)
    {
        if ($start < 0)
            $start = $this->start;
        while ($this->arr_pntr < sizeof($this->events)) {
            if ($this->events[$this->arr_pntr]->getStart() >= $start
                    && $this->events[$this->arr_pntr]->getStart() < $start + $step) {
                return $this->events[$this->arr_pntr++];
            }

            $this->arr_pntr++;
        }
        $this->arr_pntr = 0;

        return false;
    }

    // Termine in Datenbank speichern.
    // public
    function save()
    {
        day_save($this);
    }

    // public
    function existEvent()
    {
        if (sizeof($this->events) > 0) {
            return true;
        }
        return false;
    }

    // Wiederholungstermine, die in der Vergangenheit angelegt wurden belegen in
    // events[] die ersten Positionen und werden hier in den "Tagesablauf" einsortiert
    // Termine, die sich ueber die Tagesgrenzen erstrecken, muessen anhand ihrer
    // "absoluten" Anfangszeit einsortiert werden.
    // private
    function sort()
    {
        if (sizeof($this->events)) {
            usort($this->events, "cmp_list");
        }
    }

    // Termine aus Datenbank holen
    // private
    function restore($restrictions = NULL, $sem_ids = NULL)
    {

        //	$this->driver->openDatabaseGetView($this);
        $this->driver->openDatabase('EVENTS', 'ALL_EVENTS', $this->getStart(), $this->getEnd(), NULL, $sem_ids);

        while ($properties = $this->driver->nextProperties()) {

            if (!Calendar::checkRestriction($properties, $restrictions))
                continue;

            $rep = $properties['RRULE'];
            $duration = (int) ((mktime(12, 0, 0, date('n', $properties['DTEND']), date('j', $properties['DTEND']), date('Y', $properties['DTEND']), 0)
                    - mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0))
                    / 86400);

            // single events or first event
            if ($properties['DTSTART'] >= $this->getStart() && $properties['DTEND'] <= $this->getEnd()) {
                $this->createEvent($properties, 0, $properties['DTSTART'], $properties['DTEND']);
            } elseif ($properties['DTSTART'] >= $this->getStart() && $properties['DTSTART'] <= $this->getEnd()) {
                $this->createEvent($properties, 1, $properties['DTSTART'], $properties['DTEND']);
            } elseif ($properties['DTSTART'] < $this->getStart() && $properties['DTEND'] > $this->getEnd()) {
                $this->createEvent($properties, 2, $properties['DTSTART'], $properties['DTEND']);
            } elseif ($properties['DTEND'] > $this->getStart() && $properties['DTEND'] <= $this->getEnd()) {
                $this->createEvent($properties, 3, $properties['DTSTART'], $properties['DTEND']);
            }

            switch ($rep['rtype']) {

                case 'DAILY':

                    if ($this->getEnd() > $rep['expire'] + $duration * 86400) {
                        continue;
                    }
                    $pos = (($this->ts - $rep['ts']) / 86400) % $rep['linterval'];
                    $start = $this->ts - $pos * 86400;
                    $end = $start + $duration * 86400;
                    $this->createEvent($properties, 1, $start, $end);
                    break;

                case 'WEEKLY':

                    for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                        $pos = ((($this->ts - ($this->dow - 1) * 86400) - $rep['ts']) / 86400
                                - $rep['wdays']{$i} + $this->dow)
                                % ($rep['linterval'] * 7);
                        $start = $this->ts - $pos * 86400;
                        $end = $start + $duration * 86400;

                        if ($start >= $properties['DTSTART'] && $start <= $this->ts && $end >= $this->ts) {
                            $this->createEvent($properties, 3, $start, $end);
                        }
                    }
                    break;

                case 'MONTHLY':
                    if ($rep['day']) {
                        $lwst = mktime(12, 0, 0, $this->mon
                                - ((($this->year - date('Y', $rep['ts'])) * 12
                                + ($this->mon - date('n', $rep['ts']))) % $rep['linterval']), $rep['day'], $this->year, 0);
                        $hgst = $lwst + $duration * 86400;
                        $this->createEvent($properties, 1, $lwst, $hgst);
                        break;
                    }
                    if ($rep['sinterval']) {
                        $mon = $this->mon - $rep['linterval'];
                        do {
                            $lwst = mktime(12, 0, 0, $mon
                                            - ((($this->year - date('Y', $rep['ts'])) * 12
                                            + ($mon - date('n', $rep['ts']))) % $rep['linterval']), 1, $this->year, 0)
                                    + ($rep['sinterval'] - 1) * 604800;
                            $aday = strftime('%u', $lwst);
                            $lwst -= ( $aday - $rep['wdays']) * 86400;
                            if ($rep['sinterval'] == 5) {
                                if (date('j', $lwst) < 10)
                                    $lwst -= 604800;
                                if (date('n', $lwst) == date('n', $lwst + 604800))
                                    $lwst += 604800;
                            }
                            else {
                                if ($aday > $rep['wdays'])
                                    $lwst += 604800;
                            }
                            $hgst = $lwst + $duration * 86400;
                            if ($this->ts >= $lwst && $this->ts <= $hgst) {
                                $this->createEvent($properties, 1, $lwst, $hgst);
                            }
                            $mon += $rep['linterval'];
                        } while ($lwst < $this->ts);
                    }
                    break;

                case 'YEARLY':

                    if ($this->ts < $rep['ts']) {
                        break;
                    }
                    if ($rep['day']) {
                        if (date('Y', $properties['DTEND']) - date('Y', $properties['DTSTART'])) {
                            $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'], $this->year - (($this->year - date('Y', $rep['ts'])) % $rep['linterval'])
                                    - $rep['linterval'], 0);
                            $hgst = $lwst + 86400 * $duration;

                            if ($this->ts >= $lwst && $this->ts <= $hgst) {
                                $this->createEvent($properties, 2, $lwst, $hgst);
                                break;
                            }
                        }
                        $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'], $this->year - (($this->year - date('Y', $rep['ts'])) % $rep['linterval'])
                                , 0);
                        $hgst = $lwst + 86400 * $duration;
                        $this->createEvent($properties, 1, $lwst, $hgst);
                        break;
                    }
                    $ayear = $this->year - 1;
                    do {
                        if ($rep['sinterval']) {
                            $lwst = mktime(12, 0, 0, $rep['month'], 1 + ($rep['sinterval'] - 1) * 7, $ayear, 0);
                            $aday = strftime('%u', $lwst);
                            $lwst -= ( $aday - $rep['wdays']) * 86400;
                            if ($rep['sinterval'] == 5) {
                                if (date('j', $lwst) < 10)
                                    $lwst -= 604800;
                                if (date('n', $lwst) == date('n', $lwst + 604800))
                                    $lwst += 604800;
                            } elseif ($aday > $rep['wdays']) {
                                $lwst += 604800;
                            }
                            $ayear++;
                            $hgst = $lwst + $duration * 86400;
                            if ($this->ts >= $lwst && $this->ts <= $hgst) {
                                $this->createEvent($properties, 1, $lwst, $hgst);
                            }
                        }
                    } while ($lwst < $this->ts);
            }
        }
    }

    function bindSeminarEvents($sem_ids = '', $restrictions = NULL)
    {
        global $TERMIN_TYP, $user;

        $user_sems = Calendar::getBindSeminare($user->id, true);
        $this->driver->openDatabase('EVENTS', 'SEMINAR_EVENTS', $this->getStart(), $this->getEnd(), NULL, $sem_ids);

        $count_events = sizeof($this->events);
        while ($event = & $this->driver->nextObject()) {
            if (!Calendar::checkRestriction($event->getProperty(), $restrictions)) {
                continue;
            }
            if (!in_array($event->getSeminarId(), $user_sems)) {
                $event->setPermission(CALENDAR_EVENT_PERM_CONFIDENTIAL);
            }
            $this->events[] = $event;
        }

        if ($count_events < sizeof($this->events)) {
            $this->sort();

            return true;
        }

        return false;
    }

    function getUserId()
    {

        return $this->user_id;
    }

    function createEvent($properties, $time_range, $lwst, $hgst)
    {

        // if this date is in the exceptions return false
        $exdates = explode(',', $properties['EXDATE']);
        foreach ($exdates as $exdate) {
            if ($exdate > 0 && $exdate >= $lwst && $exdate <= $hgst) {
                return false;
            }
        }
        // is event expired?
        if ($properties['RRULE']['expire'] > 0 && $properties['RRULE']['expire'] <= $hgst) {
            return false;
        }
        $start = mktime(date('G', $properties['DTSTART']), date('i', $properties['DTSTART']), date('s', $properties['DTSTART']), date('n', $lwst), date('j', $lwst), date('Y', $lwst));
        $end = mktime(date('G', $properties['DTEND']), date('i', $properties['DTEND']), date('s', $properties['DTEND']), date('n', $hgst), date('j', $hgst), date('Y', $hgst));
        $properties['DTSTART'] = $start;
        $properties['DTEND'] = $end;

        if (($start <= $this->getStart() && $end >= $this->getStart())
                || ($start >= $this->getStart() && $start < $this->getEnd())
                || ($end > $this->getStart() && $end <= $this->getEnd())) {

            if (!$this->events_created["{$properties['STUDIP_ID']}.$start"]) {
                if ($properties['EVENT_TYPE'] == 'semcal') {
                    $event = new SeminarCalendarEvent($properties, $properties['STUDIP_ID'], $properties['SEM_ID'], $this->permission);
                    $event->sem_id = $properties['SEM_ID'];
                } else if ($properties['EVENT_TYPE'] == 'cal') {
                    $event = new CalendarEvent($properties, $properties['STUDIP_ID'], $this->user_id, $this->permission);
                } else {
                    $event = new SeminarEvent($properties['STUDIP_ID'], $properties, $properties['SEM_ID'], $this->permission);
                }
                $this->events_created["{$properties['STUDIP_ID']}.$start"] = 1;
                $this->events[] = $event;
                return true;
            }
        }
        return false;
    }

}
