<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarParserICalendar.class.php
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

require_once("$RELATIVE_PATH_CALENDAR/lib/sync/CalendarParser.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/CalendarEvent.class.php");

class CalendarParserICalendar extends CalendarParser
{

    var $count = NULL;

    function CalendarParserICalendar()
    {

        parent::CalendarParser();
        $this->type = "iCalendar";
    }

    function getCount($data)
    {
        global $_calendar_error;

        $matches = array();
        if (is_null($this->count)) {
            // Unfold any folded lines
            $data = preg_replace('/\x0D?\x0A[\x20\x09]/', '', $data);
            preg_match_all('/(BEGIN:VEVENT(\r\n|\r|\n)[\W\w]*?END:VEVENT\r?\n?)/', $data, $matches);
            $this->count = sizeof($matches[1]);
        }

        return $this->count;
    }

    /**
     * Parse a string containing vCalendar data.
     *
     * @access private
     * @param String $data  The data to parse
     *
     */
    function parse($data, $ignore)
    {
        global $_calendar_error, $PERS_TERMIN_KAT;

        // match categories
        $studip_categories = array();
        $i = 1;
        foreach ($PERS_TERMIN_KAT as $cat) {
            $studip_categories[$cat['name']] = $i++;
        }

        // Unfold any folded lines
        // the CR is optional for files imported from Korganizer (non-standard)
        $data = preg_replace('/\x0D?\x0A[\x20\x09]/', '', $data);

        // UTF-8 decoding
        $v_calendar = utf8_decode($data);
        if (!preg_match('/BEGIN:VCALENDAR(\r\n|\r|\n)([\W\w]*)END:VCALENDAR\r?\n?/', $v_calendar, $matches)) {
            $_calendar_error->throwError(ERROR_CRITICAL, _("Die Import-Datei ist keine g&uuml;ltige iCalendar-Datei!"));
            return FALSE;
        }

        // client identifier
        if (!$this->_parseClientIdentifier($matches[2])) {
            return FALSE;
        }

        // All sub components
        if (!preg_match_all('/BEGIN:VEVENT(\r\n|\r|\n)([\w\W]*?)END:VEVENT(\r\n|\r|\n)/', $matches[2], $v_events)) {
            $_calendar_error->throwError(ERROR_MESSAGE, _("Die importierte Datei enthält keine Termine."));
            return TRUE;
        }

        if ($this->count) {
            $this->count = 0;
        }
        foreach ($v_events[2] as $v_event) {
            $properties['CLASS'] = 'PRIVATE';
            // Parse the remain attributes

            if (preg_match_all('/(.*):(.*)(\r|\n)+/', $v_event, $matches)) {
                $properties = array();
                $check = array();
                foreach ($matches[0] as $property) {
                    preg_match('/([^;^:]*)((;[^:]*)?):(.*)/', $property, $parts);
                    $tag = $parts[1];
                    $value = $parts[4];
                    $params = array();

                    // skip seminar events
                    if ((!$this->import_sem) && $tag == 'UID') {
                        if (strpos($value, 'Stud.IP-SEM') === 0) {
                            continue 2;
                        }
                    }

                    if (!empty($parts[2])) {
                        preg_match_all('/;(([^;=]*)(=([^;]*))?)/', $parts[2], $param_parts);
                        foreach ($param_parts[2] as $key => $param_name)
                            $params[strtoupper($param_name)] = strtoupper($param_parts[4][$key]);

                        if ($params['ENCODING']) {
                            switch ($params['ENCODING']) {
                                case 'QUOTED-PRINTABLE':
                                    $value = $this->_qp_decode($value);
                                    break;

                                case 'BASE64':
                                    $value = base64_decode($value);
                                    break;
                            }
                        }
                    }

                    switch ($tag) {
                        // text fields
                        case 'DESCRIPTION':
                        case 'SUMMARY':
                        case 'LOCATION':
                        case 'CATEGORIES';
                            $value = preg_replace('/\\\\,/', ',', $value);
                            $value = preg_replace('/\\\\n/', "\n", $value);
                            $properties[$tag] = trim($value);
                            break;

                        // Date fields
                        case 'DCREATED': // vCalendar property name for "CREATED"
                            $tag = "CREATED";
                        case 'DTSTAMP':
                        case 'COMPLETED':
                        case 'CREATED':
                        case 'LAST-MODIFIED':
                            $properties[$tag] = $this->_parseDateTime($value);
                            break;

                        case 'DTSTART':
                        case 'DTEND':
                            // checking for day events
                            if ($params['VALUE'] == 'DATE')
                                $check['DAY_EVENT'] = TRUE;
                        case 'DUE':
                        case 'RECURRENCE-ID':
                            $properties[$tag] = $this->_parseDateTime($value);
                            break;

                        case 'RDATE':
                            if (array_key_exists('VALUE', $params)) {
                                if ($params['VALUE'] == 'PERIOD') {
                                    $properties[$tag] = $this->_parsePeriod($value);
                                } else {
                                    $properties[$tag] = $this->_parseDateTime($value);
                                }
                            } else {
                                $properties[$tag] = $this->_parseDateTime($value);
                            }
                            break;

                        case 'TRIGGER':
                            if (array_key_exists('VALUE', $params)) {
                                if ($params['VALUE'] == 'DATE-TIME') {
                                    $properties[$tag] = $this->_parseDateTime($value);
                                } else {
                                    $properties[$tag] = $this->_parseDuration($value);
                                }
                            } else {
                                $properties[$tag] = $this->_parseDuration($value);
                            }
                            break;

                        case 'EXDATE':
                            $properties[$tag] = array();
                            // comma seperated dates
                            $values = array();
                            $dates = array();
                            preg_match_all('/,([^,]*)/', ',' . $value, $values);

                            foreach ($values as $value) {
                                if (array_key_exists('VALUE', $params)) {
                                    if ($params['VALUE'] == 'DATE-TIME') {
                                        $dates[] = $this->_parseDateTime($value);
                                    } else if ($params['VALUE'] == 'DATE') {
                                        $dates[] = $this->_parseDate($value);
                                    }
                                } else {
                                    $dates[] = $this->_parseDateTime($value);
                                }
                            }
                            // some iCalendar exports (e.g. KOrganizer) use an EXDATE-entry for every
                            // exception, so we have to merge them
                            array_merge($properties[$tag], $dates);
                            break;

                        // Duration fields
                        case 'DURATION':
                            $attibutes[$tag] = $this->_parseDuration($value);
                            break;

                        // Period of time fields
                        case 'FREEBUSY':
                            $values = array();
                            $periods = array();
                            preg_match_all('/,([^,]*)/', ',' . $value, $values);
                            foreach ($values[1] as $value) {
                                $periods[] = $this->_parsePeriod($value);
                            }

                            $properties[$tag] = $periods;
                            break;

                        // UTC offset fields
                        case 'TZOFFSETFROM':
                        case 'TZOFFSETTO':
                            $properties[$tag] = $this->_parseUtcOffset($value);
                            break;

                        case 'PRIORITY':
                            $properties[$tag] = $this->_parsePriority($value);
                            break;

                        case 'CLASS':
                            switch (trim($value)) {
                                case 'PUBLIC':
                                    $properties[$tag] = 'PUBLIC';
                                    break;
                                case 'CONFIDENTIAL':
                                    $properties[$tag] = 'CONFIDENTIAL';
                                    break;
                                default:
                                    $properties[$tag] = 'PRIVATE';
                            }
                            break;

                        // Integer fields
                        case 'PERCENT-COMPLETE':
                        case 'REPEAT':
                        case 'SEQUENCE':
                            $properties[$tag] = intval($value);
                            break;

                        // Geo fields
                        case 'GEO':
                            $floats = split(';', $value);
                            $value['latitude'] = floatval($floats[0]);
                            $value['longitude'] = floatval($floats[1]);
                            $properties[$tag] = $value;
                            break;

                        // Recursion fields
                        case 'EXRULE':
                        case 'RRULE':
                            $properties[$tag] = $this->_parseRecurrence($value);
                            break;

                        default:
                            // string fields
                            $properties[$tag] = trim($value);
                            break;
                    }
                }

                if (!$properties['RRULE']['rtype'])
                    $properties['RRULE'] = array('rtype' => 'SINGLE');

                $properties['RRULE'] = CalendarEvent::createRepeat($properties['RRULE'], $properties['DTSTART'], $properties['DTEND']);

                if (!$properties['LAST-MODIFIED'])
                    $properties['LAST-MODIFIED'] = $properties['CREATED'];

                if (!$properties['DTSTART'] || ($properties['EXDATE'] && !$properties['RRULE'])) {
                    $_calendar_error->throwError(ERROR_CRITICAL, _("Die Datei ist keine g&uuml;ltige iCalendar-Datei!"));
                    $this->count = 0;
                    return FALSE;
                }

                if (!$properties['DTEND'])
                    $properties['DTEND'] = $properties['DTSTART'];

                // day events starts at 00:00:00 and ends at 23:59:59
                if ($check['DAY_EVENT'])
                    $properties['DTEND']--;

                // default: all imported events are set to private
                if (!$properties['CLASS']
                        || ($this->public_to_private && $properties['CLASS'] == 'PUBLIC')) {
                    $properties['CLASS'] = 'PRIVATE';
                }

                if (isset($studip_categories[$properties['CATEGORIES']])) {
                    $properties['STUDIP_CATEGORY'] = $studip_categories[$properties['CATEGORIES']];
                    $properties['CATEGORIES'] = '';
                }

                $this->components[] = $properties;
            } else {
                $_calendar_error->throwError(ERROR_CRITICAL, _("Die Datei ist keine g&uuml;ltige iCalendar-Datei!"));
                $this->count = 0;
                return FALSE;
            }
            $this->count++;
        }

        return TRUE;
    }

    /**
     * Parse a UTC Offset field
     */
    function _parseUtcOffset($text)
    {
        $offset = 0;
        if (preg_match('/(\+|-)([0-9]{2})([0-9]{2})([0-9]{2})?/', $text, $matches)) {
            $offset += 3600 * intval($matches[2]);
            $offset += 60 * intval($matches[3]);
            $offset *= ( $matches[1] == '+' ? 1 : -1);
            if (array_key_exists(4, $matches)) {
                $offset += intval($matches[4]);
            }
            return $offset;
        } else {
            return FALSE;
        }
    }

    /**
     * Parse a Time Period field
     */
    function _parsePeriod($text)
    {
        $matches = split('/', $text);

        $start = $this->_parseDateTime($matches[0]);

        if ($duration = $this->_parseDuration($matches[1])) {
            return array('start' => $start, 'duration' => $duration);
        } else if ($end = $this->_parseDateTime($matches[1])) {
            return array('start' => $start, 'end' => $end);
        }
    }

    /**
     * Parse a DateTime field
     */
    function _parseDateTime($text)
    {
        $dateParts = split('T', $text);
        if (count($dateParts) != 2 && !empty($text)) {
            // not a date time field but may be just a date field
            if (!$date = $this->_parseDate($text)) {
                return $date;
            }
            $date = $this->_parseDate($text);
            return mktime(0, 0, 0, $date['month'], $date['mday'], $date['year']);
        }

        if (!$date = $this->_parseDate($dateParts[0])) {
            return $date;
        }
        if (!$time = $this->_parseTime($dateParts[1])) {
            return $time;
        }

        if ($time['zone'] == 'UTC') {
            return gmmktime($time['hour'], $time['minute'], $time['second'], $date['month'], $date['mday'], $date['year']);
        } else {
            return mktime($time['hour'], $time['minute'], $time['second'], $date['month'], $date['mday'], $date['year']);
        }
    }

    /**
     * Parse a Time field
     */
    function _parseTime($text)
    {
        if (preg_match('/([0-9]{2})([0-9]{2})([0-9]{2})(Z)?/', $text, $matches)) {
            $time['hour'] = intval($matches[1]);
            $time['minute'] = intval($matches[2]);
            $time['second'] = intval($matches[3]);
            if (array_key_exists(4, $matches)) {
                $time['zone'] = 'UTC';
            } else {
                $time['zone'] = 'LOCAL';
            }
            return $time;
        } else {
            return FALSE;
        }
    }

    /**
     * Parse a Date field
     */
    function _parseDate($text)
    {
        if (strlen(trim($text)) != 8) {
            return FALSE;
        }

        $date['year'] = intval(substr($text, 0, 4));
        $date['month'] = intval(substr($text, 4, 2));
        $date['mday'] = intval(substr($text, 6, 2));

        return $date;
    }

    /**
     * Parse a Duration Value field
     */
    function _parseDuration($text)
    {
        if (preg_match('/([+]?|[-])P(([0-9]+W)|([0-9]+D)|)(T(([0-9]+H)|([0-9]+M)|([0-9]+S))+)?/', trim($text), $matches)) {
            // weeks
            $duration = 7 * 86400 * intval($matches[3]);
            if (count($matches) > 4) {
                // days
                $duration += 86400 * intval($matches[4]);
            }
            if (count($matches) > 5) {
                // hours
                $duration += 3600 * intval($matches[7]);
                // mins
                if (array_key_exists(8, $matches)) {
                    $duration += 60 * intval($matches[8]);
                }
                // secs
                if (array_key_exists(9, $matches)) {
                    $duration += intval($matches[9]);
                }
            }
            // sign
            if ($matches[1] == "-") {
                $duration *= - 1;
            }

            return $duration;
        } else {
            return FALSE;
        }
    }

    function _parsePriority($value)
    {
        $value = intval($value);
        if ($value > 0 && $value < 5)
            return 1;

        if ($value == 5)
            return 2;

        if ($value > 5 && $value < 10)
            return 3;

        return 0;
    }

    /**
     * Parse a Recurrence field
     */
    function _parseRecurrence($text)
    {
        global $_calendar_error;

        if (preg_match_all('/([A-Za-z]*?)=([^;]*);?/', $text, $matches, PREG_SET_ORDER)) {
            $r_rule = array();

            foreach ($matches as $match) {
                switch ($match[1]) {
                    case 'FREQ' :
                        switch (trim($match[2])) {
                            case 'DAILY' :
                            case 'WEEKLY' :
                            case 'MONTHLY' :
                            case 'YEARLY' :
                                $r_rule['rtype'] = trim($match[2]);
                                break;
                            default:
                                $_calendar_error->throwSingleError('parse', ERROR_WARNING, _("Der Import enth&auml;lt Kalenderdaten, die Stud.IP nicht korrekt darstellen kann."));
                                break;
                        }
                        break;

                    case 'UNTIL' :
                        $r_rule['expire'] = $this->_parseDateTime($match[2]);
                        break;

                    case 'COUNT' :
                        $r_rule['count'] = intval($match[2]);
                        break;

                    case 'INTERVAL' :
                        $r_rule['linterval'] = intval($match[2]);
                        break;

                    case 'BYSECOND' :
                    case 'BYMINUTE' :
                    case 'BYHOUR' :
                    case 'BYWEEKNO' :
                    case 'BYYEARDAY' :
                        $_calendar_error->throwSingleError('parse', ERROR_WARNING, _("Der Import enth&auml;lt Kalenderdaten, die Stud.IP nicht korrekt darstellen kann."));
                        break;

                    case 'BYDAY' :
                        $byday = $this->_parseByDay($match[2]);
                        $r_rule['wdays'] = $byday['wdays'];
                        if ($byday['sinterval'])
                            $r_rule['sinterval'] = $byday['sinterval'];
                        break;

                    case 'BYMONTH' :
                        $r_rule['month'] = $this->_parseByMonth($match[2]);
                        break;

                    case 'BYMONTHDAY' :
                        $r_rule['day'] = $this->_parseByMonthDay($match[2]);
                        break;

                    case 'BYSETPOS':
                        $r_rule['sinterval'] = intval($match[2]);
                        break;

                    case 'WKST' :
                        break;
                }
            }
        }

        return $r_rule;
    }

    function _parseByDay($text)
    {
        global $_calendar_error;

        preg_match_all('/(-?\d{1,2})?(MO|TU|WE|TH|FR|SA|SU),?/', $text, $matches, PREG_SET_ORDER);
        $wdays_map = array('MO' => '1', 'TU' => '2', 'WE' => '3', 'TH' => '4', 'FR' => '5',
            'SA' => '6', 'SU' => '7');
        $wdays = "";
        $sinterval = NULL;
        foreach ($matches as $match) {
            $wdays .= $wdays_map[$match[2]];
            if ($match[1]) {
                if (!$sinterval && ((int) $match[1]) > 0 || $match[1] == '-1') {
                    if ($match[1] == '-1')
                        $sinterval = '5';
                    else
                        $sinterval = $match[1];
                } else {
                    $_calendar_error->throwSingleError('parse', ERROR_WARNING, _("Der Import enth&auml;lt Kalenderdaten, die Stud.IP nicht korrekt darstellen kann."));
                }
            }
        }

        return $wdays ? array('wdays' => $wdays, 'sinterval' => $sinterval) : FALSE;
    }

    function _parseByMonthDay($text)
    {
        $days = explode(',', $text);
        if (sizeof($days) > 1 || ((int) $days[0]) < 0)
            return FALSE;

        return $days[0];
    }

    function _parseByMonth($text)
    {
        $months = explode(',', $text);
        if (sizeof($months) > 1)
            return FALSE;

        return $months[0];
    }

    function _qp_decode($value)
    {

        return preg_replace("/=([0-9A-F]{2})/e", "chr(hexdec('\\1'))", $value);
    }

    function _parseClientIdentifier(&$data)
    {
        global $_calendar_error;

        if ($this->client_identifier == '') {
            if (!preg_match('/PRODID((;[\W\w]*)*):([\W\w]+?)(\r\n|\r|\n)/', $data, $matches)) {
                $_calendar_error->throwError(ERROR_CRITICAL, _("Die Datei ist keine g&uuml;ltige iCalendar-Datei!"));
                return FALSE;
            } elseif (!trim($matches[3])) {
                $_calendar_error->throwError(ERROR_CRITICAL, _("Die Datei ist keine g&uuml;ltige iCalendar-Datei!"));
                return FALSE;
            } else {
                $this->client_identifier = trim($matches[3]);
            }
        }
        return TRUE;
    }

    function getClientIdentifier($data = NULL)
    {
        if (!is_null($data)) {
            $this->_parseClientIdentifier($data);
        }

        return $this->client_identifier;
    }

}

