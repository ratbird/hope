<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * DbCalendarMonth.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/DbCalendarYear.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarMonth.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarDay.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/calendar_misc_func.inc.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php');

class DbCalendarMonth extends DbCalendarYear
{

    var $month;        // Monat (Object)
    var $events;       // Object[][]
    var $arr_pntr;     // Array-Pointer (int)

    // Konstruktor

    function DbCalendarMonth(&$calendar, $tmstamp, $restrictions = NULL, $sem_ids = NULL)
    {
        $this->month = new CalendarMonth($tmstamp);
        $this->events = array();
        parent::DbCalendarYear($calendar, $tmstamp, $restrictions, $sem_ids);
    }

    // public
    function getMonth()
    {
        return $this->month->getValue();
    }

    // public
    function getNameOfMonth()
    {
        return $this->month->toString();
    }

    // public
    function getStart()
    {
        return $this->month->getStart();
    }

    // public
    function getEnd()
    {
        return $this->month->getEnd();
    }

    // public
    function getTs()
    {
        return $this->month->getTs();
    }

    // public
    function sort()
    {
        foreach ($this->events as $key => $val) {
            usort($val, 'cmp');
            $this->events[$key] = $val;
        }
    }

    // public
    function restore($restrictions = NULL, $sem_ids = NULL)
    {

        // 12 Tage zusätzlich (angezeigte Tage des vorigen und des nächsten Monats)
        $end_month = $this->getEnd() + 518400;
        $start_month = $this->getStart() - 518400;

        for ($start_day = $start_month; $start_day < $end_month; $start_day += 86400) {
            $day = new CalendarDay($start_day);
            $end = $start_day + 86400;
            $this->driver->openDatabase('EVENTS', 'ALL_EVENTS', $day->getStart(), $day->getEnd(), NULL, $sem_ids);

            while ($properties = $this->driver->nextProperties()) {

                if (!Calendar::checkRestriction($properties, $restrictions))
                    continue;

                $rep = $properties['RRULE'];
                $duration = (int) ((mktime(12, 0, 0, date('n', $properties['DTEND']), date('j', $properties['DTEND']), date('Y', $properties['DTEND']), 0)
                        - mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0))
                        / 86400);

                // single events or first event
                if ($properties['DTSTART'] >= $day->getStart() && $properties['DTEND'] <= $day->getEnd()) {
                    $this->createEvent($properties, $day, $properties['DTSTART'], $properties['DTEND']);
                } elseif ($properties['DTSTART'] >= $day->getStart() && $properties['DTSTART'] <= $day->getEnd()) {
                    $this->createEvent($properties, $day, $properties['DTSTART'], $properties['DTEND']);
                } elseif ($properties['DTSTART'] < $day->getStart() && $properties['DTEND'] > $day->getEnd()) {
                    $this->createEvent($properties, $day, $properties['DTSTART'], $properties['DTEND']);
                } elseif ($properties['DTEND'] > $day->getStart() && $properties['DTEND'] <= $day->getEnd()) {
                    $this->createEvent($properties, $day, $properties['DTSTART'], $properties['DTEND']);
                }

                switch ($rep['rtype']) {

                    case 'DAILY':

                        if ($day->getEnd() > $rep['expire'] + $duration * 86400) {
                            continue;
                        }
                        $pos = (($day->ts - $rep['ts']) / 86400) % $rep['linterval'];
                        $start = $day->ts - $pos * 86400;
                        $end = $start + $duration * 86400;
                        $this->createEvent($properties, $day, $start, $end);
                        break;

                    case 'WEEKLY':

                        for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                            $pos = ((($day->ts - ($day->dow - 1) * 86400) - $rep['ts']) / 86400
                                    - $rep['wdays']{$i} + $day->dow)
                                    % ($rep['linterval'] * 7);
                            $start = $day->ts - $pos * 86400;
                            $end = $start + $duration * 86400;

                            if ($start >= $properties['DTSTART'] && $start <= $day->ts && $end >= $day->ts) {
                                $this->createEvent($properties, $day, $start, $end);
                            }
                        }
                        break;

                    case 'MONTHLY':
                        if ($rep['day']) {
                            $lwst = mktime(12, 0, 0, $day->mon
                                    - ((($day->year - date('Y', $rep['ts'])) * 12
                                    + ($day->mon - date('n', $rep['ts']))) % $rep['linterval']), $rep['day'], $day->year, 0);
                            $hgst = $lwst + $duration * 86400;
                            $this->createEvent($properties, $day, $lwst, $hgst, $day);
                            break;
                        }
                        if ($rep['sinterval']) {
                            $mon = $day->mon - $rep['linterval'];
                            do {
                                $lwst = mktime(12, 0, 0, $mon
                                                - ((($day->year - date('Y', $rep['ts'])) * 12
                                                + ($mon - date('n', $rep['ts']))) % $rep['linterval']), 1, $day->year, 0)
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
                                if ($day->ts >= $lwst && $day->ts <= $hgst) {
                                    $this->createEvent($properties, $day, $lwst, $hgst, $day);
                                }
                                $mon += $rep['linterval'];
                            } while ($lwst < $day->ts);
                        }
                        break;

                    case 'YEARLY':
                        if ($day->ts < $rep['ts']) {
                            break;
                        }
                        if ($rep['day']) {
                            if (date('Y', $properties['DTEND']) - date('Y', $properties['DTSTART'])) {
                                $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'], $day->year - (($day->year - date('Y', $rep['ts'])) % $rep['linterval'])
                                        - $rep['linterval'], 0);
                                $hgst = $lwst + 86400 * $duration;
                                ;

                                if ($day->ts >= $lwst && $day->ts <= $hgst) {
                                    $this->createEvent($properties, $day, $lwst, $hgst);
                                    break;
                                }
                            }
                            $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'], $day->year - (($day->year - date('Y', $rep['ts'])) % $rep['linterval'])
                                    , 0);
                            $hgst = $lwst + 86400 * $duration;
                            $this->createEvent($properties, $day, $lwst, $hgst);
                            break;
                        }
                        $ayear = $day->year - 1;
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
                                if ($day->ts >= $lwst && $day->ts <= $hgst) {
                                    $this->createEvent($properties, $day, $lwst, $hgst);
                                }
                            }
                        } while ($lwst < $day->ts);
                }
            }
        }
    }

    // public
    function nextEvent($tmstamp)
    {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp), 0);
        if ($this->events["$adate"]) {
            if (!isset($this->arr_pntr["$adate"]))
                $this->arr_pntr["$adate"] = 0;
            if ($this->arr_pntr["$adate"] < $this->appdays["$adate"])
                return $this->events["$adate"][$this->arr_pntr["$adate"]++];

            $this->arr_pntr["$adate"] = 0;
        }

        return false;
    }

    function getEventsOfDay($tmstamp)
    {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp), 0);

        return $this->events["$adate"];
    }

    // public
    function setPointer($tmstamp, $pos)
    {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp), 0);
        $this->arr_pntr["$adate"] = $pos;
    }

    function createEvent($properties, &$day, $lwst = NULL, $hgst = NULL)
    {
        //  if ($date < $this->getStart() - 518400 || $date > $this->getEnd() + 518400) {
        //      return false;
        //  }

        $exdates = explode(',', $properties['EXDATE']);
        foreach ($exdates as $exdate) {
            if ($exdate > 0 && $exdate >= $lwst && $exdate <= $hgst) {
                return false;
            }
        }
        // is event expired?
        if ($properties['RRULE']['expire'] > 0
                && $properties['RRULE']['expire'] <= $hgst) {
            return false;
        }
        if (!is_null($lwst) && !is_null($hgst)) {
            $start = mktime(date('G', $properties['DTSTART']), date('i', $properties['DTSTART']), date('s', $properties['DTSTART']), date('n', $lwst), date('j', $lwst), date('Y', $lwst));
            $end = mktime(date('G', $properties['DTEND']), date('i', $properties['DTEND']), date('s', $properties['DTEND']), date('n', $hgst), date('j', $hgst), date('Y', $hgst));
        }

        $properties['DTSTART'] = $start;
        $properties['DTEND'] = $end;

        if (($start <= $day->getStart() && $end >= $day->getStart())
                || ($start >= $day->getStart() && $start < $day->getEnd())
                || ($end > $day->getStart() && $end <= $day->getEnd())) {

            switch ($properties['EVENT_TYPE']) {
                case 'cal' :
                    $event = new CalendarEvent($properties, $properties['STUDIP_ID'], $this->user_id, $this->permission);
                    break;
                case 'semcal' :
                    $event = new SeminarCalendarEvent($properties, $properties['STUDIP_ID'], $properties['SEM_ID'], $this->permission);
                    $event->sem_id = $properties['SEM_ID'];
                    break;
                case 'sem' :
                    $event = new SeminarEvent($properties['STUDIP_ID'], $properties, $this->user_id);
                    break;
            }
            /*  if (isset($properties['SEM_ID'])) {
              $event = new SeminarCalendarEvent($properties, $properties['STUDIP_ID'], $properties['SEM_ID'], $this->permission);
              $event->sem_id = $properties['SEM_ID'];
              } else {
              $event = new CalendarEvent($properties, $properties['STUDIP_ID'], $this->user_id, $this->permission);
              } */
            $this->events["{$day->ts}"][$properties['STUDIP_ID']] = & $event;
            $this->appdays["{$day->ts}"]++;

            return true;
        }
        return false;
    }

}
