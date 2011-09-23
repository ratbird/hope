<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarWriterICalendar.class.php
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

global $RELATIVE_PATH_CALENDAR;

define('CALENDAR_WEEKSTART', 'MO');

require_once($RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarWriter.class.php');

class CalendarWriteriCalendar extends CalendarWriter
{

    var $newline = "\r\n";

    function CalendarWriteriCalendar()
    {

        parent::CalendarWriter();
        $this->default_filename_suffix = "ics";
        $this->format = "iCalendar";
    }

    function writeHeader()
    {

        // Default values
        $header = "BEGIN:VCALENDAR" . $this->newline;
        $header .= "VERSION:2.0" . $this->newline;
        if ($this->client_identifier) {
            $header .= "PRODID:" . $this->client_identifier . $this->newline;
        } else {
            $header .= "PRODID:-//Stud.IP@{$_SERVER['SERVER_NAME']}//Stud.IP_iCalendar Library";
            $header .= " //EN" . $this->newline;
        }
        $header .= "METHOD:PUBLISH" . $this->newline;

        return $header;
    }

    function writeFooter()
    {

        return "END:VCALENDAR" . $this->newline;
    }

    /**
     * Export this component as iCalendar format
     *
     * @param object $event The event to export.
     * @return String iCalendar formatted data
     */
    function write(&$event)
    {

        $match_pattern_1 = array('\\', '\n', ';', ',');
        $replace_pattern_1 = array('\\\\', '\\n', '\;', '\,');
        $match_pattern_2 = array('\\', '\n', ';');
        $replace_pattern_2 = array('\\\\', '\\n', '\;');

        $result = "BEGIN:VEVENT" . $this->newline;

        foreach ($event->properties as $name => $value) {
            $params = array();
            $params_str = '';
            
            if ($name === 'SUMMARY') {
                $value = $event->getTitle();
            }
            if ($value === '') {
                continue;
            }

            switch ($name) {
                // not supported event properties
                case 'SEMNAME':
                case 'EXPIRE':
                case 'STUDIP_CATEGORY':
                case 'STUDIP_AUTHOR_ID':
                case 'STUDIP_ID':
                case 'BEGIN':
                case 'END':
                case 'EVENT_TYPE':
                case 'SEM_ID':
                    continue 2;

                // text fields
                case 'SUMMARY':
                    $value = str_replace($match_pattern_1, $replace_pattern_1, $value);
                    break;
                case 'DESCRIPTION':
                    $value = str_replace($match_pattern_1, $replace_pattern_1, $event->getDescription());
                    break;
                case 'LOCATION':
                    $value = str_replace($match_pattern_1, $replace_pattern_1, $event->getLocation());
                    break;

                case 'CATEGORIES':
                    $value = str_replace($match_pattern_1, $replace_pattern_1, $event->getCategory());
                    break;

                // Date fields
                case 'LAST-MODIFIED':
                case 'CREATED':
                case 'COMPLETED':
                    $value = $this->_exportDateTime($value);
                    break;

                case 'DTSTAMP':
                    $value = $this->_exportDateTime(time());
                    break;

                case 'DTEND':
                case 'DTSTART':
                    if ($event->isDayEvent()) {
                        $params['VALUE'] = 'DATE';
                        $params_str = ';VALUE=DATE';
                        $value++;
                    }
                case 'DUE':
                case 'RECURRENCE-ID':
                    if (array_key_exists('VALUE', $params)) {
                        if ($params['VALUE'] == 'DATE') {
                            $value = $this->_exportDate($value);
                        } else {
                            $value = $this->_exportDateTime($value);
                        }
                    } else {
                        $value = $this->_exportDateTime($value);
                    }
                    break;

                case 'EXDATE':
                    if (array_key_exists('VALUE', $params))
                        $value = $this->_exportExdate($value, $params['VALUE']);
                    else
                        $value = $this->_exportExdate($value, 'DATE-TIME');
                    break;

                case 'RDATE':
                    if (array_key_exists('VALUE', $params)) {
                        if ($params['VALUE'] == 'DATE') {
                            $value = $this->_exportDate($value);
                        } else if ($params['VALUE'] == 'PERIOD') {
                            $value = $this->_exportPeriod($value);
                        } else {
                            $value = $this->_exportDateTime($value);
                        }
                    } else {
                        $value = $this->_exportDateTime($value);
                    }
                    break;

                case 'TRIGGER':
                    if (array_key_exists('VALUE', $params)) {
                        if ($params['VALUE'] == 'DATE-TIME') {
                            $value = $this->_exportDateTime($value);
                        } else if ($params['VALUE'] == 'DURATION') {
                            $value = $this->_exportDuration($value);
                        }
                    } else {
                        $value = $this->_exportDuration($value);
                    }
                    break;

                // Duration fields
                case 'DURATION':
                    $value = $this->_exportDuration($value);
                    break;

                // Period of time fields
                case 'FREEBUSY':
                    $value_str = '';
                    foreach ($value as $period) {
                        $value_str .= empty($value_str) ? '' : ',';
                        $value_str .= $this->_exportPeriod($period);
                    }
                    $value = $value_str;
                    break;


                // UTC offset fields
                case 'TZOFFSETFROM':
                case 'TZOFFSETTO':
                    $value = $this->_exportUtcOffset($value);
                    break;

                // Integer fields
                case 'PERCENT-COMPLETE':
                    if ($event->getPermission() == Event::PERMISSION_CONFIDENTIAL)
                        $value = '';
                case 'REPEAT':
                case 'SEQUENCE':
                    $value = "$value";
                    break;

                case 'PRIORITY':
                    if ($event->getPermission() == Event::PERMISSION_CONFIDENTIAL)
                        $value = '0';
                    else {
                        switch ($value) {
                            case 1:
                                $value = '1';
                                break;
                            case 2:
                                $value = '5';
                                break;
                            case 3:
                                $value = '9';
                                break;
                            default:
                                $value = '0';
                        }
                    }
                    break;

                // Geo fields
                case 'GEO':
                    if ($event->getPermission() == Event::PERMISSION_CONFIDENTIAL)
                        $value = '';
                    else
                        $value = $value['latitude'] . ',' . $value['longitude'];
                    break;

                // Recursion fields
                case 'EXRULE':
                case 'RRULE':
                    if ($event->properties["RRULE"]["rtype"] != "SINGLE")
                        $value = $this->_exportRecurrence($value);
                    else
                        continue 2;
                    break;

                case "UID":
                    $value = "$value";
            }

            $attr_string = "$name$params_str:$value";
            $result .= $this->_foldLine($attr_string) . $this->newline;
        }
        //  $result .= 'DTSTAMP:' . $this->_exportDateTime(time()) . $this->newline;
        $result .= "END:VEVENT" . $this->newline;

        return utf8_encode($result);
    }

    /**
     * Export a UTC Offset field
     *
     * @param array $value
     * @return String UTC offset field iCalendar formatted
     */
    function _exportUtcOffset($value)
    {
        $offset = $value['ahead'] ? '+' : '-';
        $offset .= sprintf('%02d%02d', $value['hour'], $value['minute']);
        if (array_key_exists('second', $value)) {
            $offset .= sprintf('%02d', $value['second']);
        }

        return $offset;
    }

    /**
     * Export a Time Period field
     *
     * @param array $value
     * @return String Period field iCalendar formatted
     */
    function _exportPeriod($value)
    {
        $period = $this->_exportDateTime($value['start']);
        $period .= '/';
        if (array_key_exists('duration', $value)) {
            $period .= $this->_exportDuration($value['duration']);
        } else {
            $period .= $this->_exportDateTime($value['end']);
        }
        return $period;
    }

    /**
     * Export a DateTime field
     *
     * @param int $value Unix timestamp
     * @return String Date and time (UTC) iCalendar formatted
     */
    function _exportDateTime($value)
    {

//      $TZOffset  = 3600 * substr(date('O', $value), 0, 3);
//      $TZOffset += 60 * substr(date('O', $value), 3, 2);
        //transform local time in UTC
        $value -= date('Z', $value);

        return $this->_exportDate($value) . 'T' . $this->_exportTime($value);
    }

    /**
     * Export a Time field
     *
     * @param int $value Unix timestamp
     * @return String Time (UTC) iCalendar formatted
     */
    function _exportTime($value)
    {
        $time = date("His", $value);
        $time .= 'Z';

        return $time;
    }

    /**
     * Export a Date field
     */
    function _exportDate($value)
    {
        return date("Ymd", $value);
    }

    /**
     * Export a duration value
     */
    function _exportDuration($value)
    {
        $duration = '';
        if ($value < 0) {
            $value *= - 1;
            $duration .= '-';
        }
        $duration .= 'P';

        $weeks = floor($value / (7 * 86400));
        $value = $value % (7 * 86400);
        if ($weeks) {
            $duration .= $weeks . 'W';
        }

        $days = floor($value / (86400));
        $value = $value % (86400);
        if ($days) {
            $duration .= $days . 'D';
        }

        if ($value) {
            $duration .= 'T';

            $hours = floor($value / 3600);
            $value = $value % 3600;
            if ($hours) {
                $duration .= $hours . 'H';
            }

            $mins = floor($value / 60);
            $value = $value % 60;
            if ($mins) {
                $duration .= $mins . 'M';
            }

            if ($value) {
                $duration .= $value . 'S';
            }
        }

        return $duration;
    }

    /**
     * Export a recurrence rule
     */
    function _exportRecurrence($value)
    {
        $rrule = array();
        // the last day of week in a MONTHLY or YEARLY recurrence in the
        // Stud.IP calendar is 5, in iCalendar it is -1
        if ($value['sinterval'] == '5')
            $value['sinterval'] = '-1';

        if ($value['count'])
            unset($value['expire']);

        foreach ($value as $r_param => $r_value) {
            if ($r_value) {
                switch ($r_param) {
                    case 'rtype':
                        $rrule[] = 'FREQ=' . $r_value;
                        break;
                    case 'expire':
                        // end of unix epoche (this is also the end of Stud.IP epoche ;-) )
                        if ($r_value < Calendar::CALENDAR_END)
                            $rrule[] = 'UNTIL=' . $this->_exportDateTime($r_value);
                        break;
                    case 'linterval':
                        $rrule[] = 'INTERVAL=' . $r_value;
                        break;
                    case 'wdays':
                        switch ($value['rtype']) {
                            case 'WEEKLY':
                                $rrule[] = 'BYDAY=' . $this->_exportWdays($r_value);
                                break;
                            // Some CUAs (e.g. Outlook) don't understand the nWDAY syntax
                            // (where n is the nth ocurrence of the day in a given period of
                            // time and WDAY is the day of week) the RRULE uses the BYSETPOS
                            // rule.
                            case 'MONTHLY':
                            case 'YEARLY':
                                $rrule[] = 'BYDAY=' . $value['sinterval'] . $this->_exportWdays($r_value);
                                $rrule[] = 'BYDAY=' . $this->_exportWdays($r_value);
                                // The Stud.IP calendar don't support multiple values in a
                                // comma separated list.

                                if ($value['sinterval'])
                                    $rrule[] = 'BYSETPOS=' . $value['sinterval'];

                                break;
                        }
                        break;
                    case 'day':
                        $rrule[] = 'BYMONTHDAY=' . $r_value;
                        break;
                    case 'month':
                        $rrule[] = 'BYMONTH=' . $r_value;
                        break;
                    case 'count':
                        $rrule[] = 'COUNT=' . $r_value;
                        break;
                }
            }
        }

        if ($value['rtype'] == 'WEEKLY' && CALENDAR_WEEKSTART != 'MO') {
            $rrule[] = 'WKST=' . CALENDAR_WEEKSTART;
        }

        return implode(';', $rrule);
    }

    /**
     * Return the Stud.IP calendar wdays attribute of a event recurrence
     */
    function _exportWdays($value)
    {
        $wdays_map = array('1' => 'MO', '2' => 'TU', '3' => 'WE', '4' => 'TH', '5' => 'FR',
            '6' => 'SA', '7' => 'SU');
        $wdays = array();
        preg_match_all('/(\d)/', $value, $matches);
        foreach ($matches[1] as $match) {
            $wdays[] = $wdays_map[$match];
        }

        return implode(',', $wdays);
    }

    function _exportExdate($value, $param)
    {
        $exdates = array();
        $date_times = explode(',', $value);
        foreach ($date_times as $date_time) {
            if ($param == 'DATE-TIME')
                $exdates[] = $this->_exportDateTime($date_time);
            else
                $exdates[] = $this->_exportDate($date_time);
        }

        return implode(',', $exdates);
    }

    /**
     * Return the folded version of a line
     */
    function _foldLine($line)
    {
        $line = preg_replace('/(\r\n|\n|\r)/', '\n', $line);
        if (strlen($line) > 75) {
            $foldedline = '';
            while (!empty($line)) {
                $maxLine = substr($line, 0, 75);
                $cutPoint = max(60, max(strrpos($maxLine, ';'), strrpos($maxLine, ':')) + 1);

                $foldedline .= ( empty($foldedline)) ?
                        substr($line, 0, $cutPoint) :
                        $this->newline . ' ' . substr($line, 0, $cutPoint);

                $line = (strlen($line) <= $cutPoint) ? '' : substr($line, $cutPoint);
            }
            return $foldedline;
        }
        return $line;
    }

}
