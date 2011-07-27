<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * DbCalendarYear.class.php
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

require_once($RELATIVE_PATH_CALENDAR . '/lib/CalendarYear.class.php');

class DbCalendarYear extends CalendarYear
{

    var $appdays;          // timestamps der Tage, die Termine enthalten (int[])
    var $user_id;         // User-ID aus PhpLib (String)
    var $driver;
    var $permission;

    // Konstruktor
    function DbCalendarYear(&$calendar, $tmstamp, $restrictions = NULL, $sem_ids = NULL)
    {
        global $user;

        $this->user_id = $calendar->getUserId();
        $this->permission = $calendar->getPermission();
        CalendarYear::CalendarYear($tmstamp);
        $this->driver = CalendarDriver::getInstance($this->user_id);
        $this->restore($restrictions, $sem_ids);
    }

    // public
    function restore($restrictions = NULL, $sem_ids = NULL)
    {

        $this->driver->openDatabase('EVENTS', 'ALL_EVENTS', $this->getStart(), $this->getEnd(), NULL, $sem_ids);

        $end = $this->getEnd();
        $start = $this->getStart();
        $year = $this->year;
        $end_ts = mktime(12, 0, 0, 12, 31, $year, 0);
        $start_ts = mktime(12, 0, 0, 1, 1, $year, 0);
        $month = 1;

        while ($properties = $this->driver->nextProperties()) {

            if (!Calendar::checkRestriction($properties, $restrictions))
                continue;

            $rep = $properties['RRULE'];
            $duration = (int) ((mktime(12, 0, 0, date('n', $properties['DTEND']), date('j', $properties['DTEND']), date('Y', $properties['DTEND']), 0)
                    - mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0))
                    / 86400);

            // single event or first event
            $lwst = mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0);
            if ($start_ts > $lwst) {
                $adate = $start_ts;
            } else {
                $adate = $lwst;
            }
            $hgst = $lwst + $duration * 86400;
            while ($adate >= $start_ts && $adate <= $end_ts && $adate <= $hgst) {
                $this->createEvent($properties, $adate, $properties['DTSTART'], $properties['DTEND']);
                $adate += 86400;
            }

            switch ($rep['rtype']) {

                // tägliche Wiederholung
                case 'DAILY' :
                    if ($rep['ts'] < $start) {
                        // brauche den ersten Tag nach $start an dem dieser Termin wiederholt wird
                        if ($rep['linterval'] == 1) {
                            $adate = $this->ts;
                        } else {
                            $adate = $this->ts + ($rep['linterval'] - (($this->ts - $rep['ts']) / 86400)
                                    % $rep['linterval']) * 86400;
                        }

                        while ($adate <= $end_ts && $adate >= $this->ts && $adate <= $rep['expire']) {
                            $hgst = $adate + $duration * 86400;
                            $md_date = $adate;
                            while ($md_date <= $end_ts && $md_date >= $this->ts && $md_date <= $hgst) {
                                $this->createEvent($properties, $md_date, $adate, $hgst);
                                $md_date += 86400;
                            }
                            $adate += $rep['linterval'] * 86400;
                        }
                    } else {
                        $adate = $rep['ts'];
                    }

                    while ($adate <= $end_ts && $adate >= $this->ts && $adate <= $rep['expire']) {
                        $hgst = $adate + $duration * 86400;
                        $md_date = $adate;
                        while ($md_date <= $end_ts && $md_date >= $this->ts && $md_date <= $hgst) {
                            $this->createEvent($properties, $md_date, $adate, $hgst);
                            $md_date += 86400;
                        }
                        $adate += $rep['linterval'] * 86400;
                    }
                    break;

                // wöchentliche Wiederholung
                case 'WEEKLY' :

                    if ($properties['DTSTART'] >= $start && $properties['DTSTART'] <= $end) {
                        $lwst = mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']), 0);
                        $hgst = $lwst + $duration * 86400;
                        if ($rep['ts'] != $adate) {
                            $md_date = $lwst;
                            while ($md_date <= $end_ts && $md_date >= $start_ts && $md_date <= $hgst) {
                                $this->createEvent($properties, $md_date, $lwst, $hgst);
                                $md_date += 86400;
                            }
                        }
                        $aday = strftime('%u', $lwst) - 1;
                        for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                            $awday = (int) substr($rep['wdays'], $i, 1) - 1;
                            if ($awday > $aday) {
                                $lwst = $lwst + ($awday - $aday) * 86400;
                                $hgst = $lwst + $duration * 86400;
                                $wdate = $lwst;
                                while ($wdate >= $start_ts && $wdate <= $end_ts && $wdate <= $hgst) {
                                    $this->createEvent($properties, $wdate, $lwst, $hgst);
                                    $wdate += 86400;
                                }
                            }
                        }
                    }
                    if ($rep['ts'] < $start) {
                        // Brauche den Montag der angefangenen Woche
                        $adate = $start_ts - (strftime('%u', $start_ts) - 1) * 86400;
                        $adate += ( $rep['linterval'] - (($adate - $rep['ts']) / 604800)
                                % $rep['linterval']) * 604800;
                        $adate -= $rep['linterval'] * 604800;
                    } else {
                        $adate = $rep['ts'];
                    }

                    while ($adate >= $properties['DTSTART'] && $adate <= $rep['expire'] && $adate <= $end) {
                        // Termin kann innerhalb der Woche an verschiedenen Wochentagen wiederholt werden
                        for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                            $awday = (int) $rep['wdays']{$i};
                            $lwst = $adate + ($awday - 1) * 86400;
                            $hgst = $lwst + $duration * 86400;
                            if ($lwst < $start_ts)
                                $lwst = $start_ts;
                            $wdate = $lwst;

                            while ($wdate >= $start_ts && $wdate <= $end_ts && $wdate <= $hgst) {
                                $this->createEvent($properties, $wdate, $lwst, $hgst);
                                $wdate += 86400;
                            }
                        }
                        $adate += 604800 * $rep['linterval'];
                    }
                    break;

                // monatliche Wiederholung
                case 'MONTHLY' :
                    $bmonth = ($rep['linterval'] - ((($year - date('Y', $rep['ts'])) * 12)
                            - date('n', $rep['ts'])) % $rep['linterval']) % $rep['linterval'];
                    for ($amonth = $bmonth - $rep['linterval']; $amonth <= $bmonth; $amonth += $rep['linterval']) {
                        if ($rep['ts'] < $start) {
                            // ist Wiederholung am X. Wochentag des X. Monats...
                            if (!$rep['day']) {
                                $lwst = mktime(12, 0, 0, $amonth
                                                - ((($year - date('Y', $rep['ts'])) * 12
                                                + ($amonth - date('n', $rep['ts']))) % $rep['linterval']), 1, $year, 0)
                                        + ($rep['sinterval'] - 1) * 604800;
                                $aday = strftime('%u', $lwst);
                                $lwst -= ( $aday - $rep['wdays']) * 86400;
                                if ($rep['sinterval'] == 5) {
                                    if (date('j', $lwst) < 10) {
                                        $lwst -= 604800;
                                    }
                                    if (date('n', $lwst) == date('n', $lwst + 604800)) {
                                        $lwst += 604800;
                                    }
                                } else {
                                    if ($aday > $rep['wdays']) {
                                        $lwst += 604800;
                                    }
                                }
                            } else {
                                // oder am X. Tag des Monats ?
                                $lwst = mktime(12, 0, 0, $amonth
                                        - ((($year - date('Y', $rep['ts'])) * 12
                                        + ($amonth - date('n', $rep['ts']))) % $rep['linterval']), $rep['day'], $year, 0);
                            }
                        } else {
                            // first recurrence
                            $lwst = $rep['ts'];
                        }
                        $hgst = $lwst + $duration * 86400;
                        $md_date = $lwst;
                        // Termine, die sich ueber mehrere Tage erstrecken
                        while ($hgst >= $start_ts && $md_date <= $hgst && $md_date <= $end_ts) {
                            $this->createEvent($properties, $md_date, $lwst, $hgst);
                            $md_date += 86400;
                        }
                    }
                    break;

                // jährliche Wiederholung
                case 'YEARLY' :


                    for ($ayear = $this->year - 1; $ayear <= $this->year; $ayear++) {
                        if ($rep['day']) {
                            $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'], $ayear, 0);
                            $hgst = $lwst + $duration * 86400;
                            $wdate = $lwst;
                            while ($hgst >= $start_ts && $wdate <= $hgst && $wdate <= $end_ts) {
                                $this->createEvent($properties, $wdate, $lwst, $hgst);
                                $wdate += 86400;
                            }
                        } else {
                            if ($rep['ts'] < $start) {
                                $adate = mktime(12, 0, 0, $rep['month'], 1, $ayear, 0)
                                        + ($rep['sinterval'] - 1) * 604800;
                                $aday = strftime('%u', $adate);
                                $adate -= ( $aday - $rep['wdays']) * 86400;
                                if ($rep['sinterval'] == 5) {
                                    if (date('j', $adate) < 10) {
                                        $adate -= 604800;
                                    }
                                } elseif ($aday > $rep['wdays']) {
                                    $adate += 604800;
                                }
                            } else {
                                $adate = $rep['ts'];
                            }
                            $lwst = $adate;
                            $hgst = $lwst + $duration * 86400;
                            while ($hgst >= $start_ts && $adate <= $hgst && $adate <= $end_ts) {
                                $this->createEvent($properties, $adate, $lwst, $hgst);
                                $adate += 86400;
                            }
                        }
                    }


                /*
                  if ($rep['day']) {
                  $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'],
                  $this->year + (($this->year - date('Y', $rep['ts'])) % $rep['linterval']), 0);
                  $hgst = mktime(12, 0, 0, date('n', $properties['DTEND']),
                  date('j', $properties['DTEND']),
                  date('Y', $lwst) + date('Y', $properties['DTEND'])
                  - date('Y', $properties['DTSTART']), 0);

                  $wdate = mktime(12, 0, 0, $rep['month'], $rep['day'], $this->year, 0);
                  while ($wdate >= $lwst && $wdate <= $hgst && $wdate <= $end && $wdate >= $start) {
                  $this->createEvent($properties, $wdate, 1, $lwst, $hgst);
                  $wdate += 86400;
                  }
                  } else {

                  if ($rep['sinterval'] == 5)
                  $cor = 0;
                  else
                  $cor = 1;

                  if ($rep['ts'] < $start) {
                  if (!$rep['day']) {
                  $adate = mktime(12, 0, 0, $rep['month'], 1, $this->year, 0)
                  + ($rep['sinterval'] - $cor) * 604800;
                  $aday = strftime('%u',$adate);
                  $adate -= ($aday - $rep['wdays']) * 86400;
                  if ($rep['sinterval'] == 5) {
                  if (date('j',$adate) < 10) {
                  $adate -= 604800;
                  }
                  } elseif ($aday > $rep['wdays']) {
                  $adate += 604800;
                  }
                  }
                  } else {
                  $adate = $rep['ts'];
                  }
                  $lwst = $adate;
                  $hgst = mktime(12, 0, 0, date('n', $lwst),
                  date('j', $lwst) + $duration,
                  date('Y', $lwst) + date('Y', $properties['DTEND'])
                  - date('Y', $properties['DTSTART']), 0);
                  while ($adate >= $lwst && $adate <= $hgst && $adate <= $end_ts) {
                  $this->createEvent($properties, $adate, 1, $lwst, $hgst);
                  $adate += 86400;
                  }
                  }
                 */
            }
        }
    }

    // public
    function existEvent($tmstamp)
    {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp), 0);
        if (empty($this->appdays["$adate"]))
            return false;
        return true;
    }

    // Anzahl von Terminen an einem bestimmten Tag
    // public
    function numberOfEvents($tmstamp)
    {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp), 0);
        return sizeof($this->appdays[$adate]);
    }

    // public
    function serialisiere()
    {
        return serialize($this);
    }

    function createEvent($properties, $date, $lwst, $hgst)
    {
        if ($date < $this->getStart() || $date > $this->getEnd()) {
            return false;
        }

        // if this date is in the exceptions return false
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
        $this->appdays["$date"]["{$properties['STUDIP_ID']}"] = 1;

        return true;
    }

}
