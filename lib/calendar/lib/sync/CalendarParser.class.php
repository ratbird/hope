<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarParser.class.php
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

require_once("$RELATIVE_PATH_CALENDAR/lib/ErrorHandler.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/CalendarEvent.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/driver/$CALENDAR_DRIVER/CalendarDriver.class.php");

class CalendarParser
{

    var $events = array();
    var $components;
    var $type;
    var $number_of_events;
    var $public_to_private = false;
    var $client_identifier;
    var $import_sem = false;

    function CalendarParser()
    {

        // initialize error handling
        init_error_handler('_calendar_error');

        $this->client_identifier = '';
    }

    function parse($data, $ignore = null)
    {

        foreach ($data as $properties) {
            if ($this->public_to_private && $properties['CLASS'] == 'PUBLIC') {
                $properties['CLASS'] = 'PRIVATE';
            }
            $properties['CATEGORIES'] = implode(', ', $properties['CATEGORIES']);
            $this->components[] = $properties;
        }
    }

    function getCount($data)
    {

        return 0;
    }

    function parseIntoDatabase($range_id, $data, $ignore)
    {

        $database = CalendarDriver::getInstance($range_id);
        if ($this->parseIntoObjects($data, $ignore)) {
            $database->writeObjectsIntoDatabase($this->events, 'REPLACE');
            return true;
        }

        return false;
    }

    function parseIntoObjects($data, $ignore)
    {
        global $_calendar_error;

        if ($this->parse($data, $ignore)) {
            if (is_array($this->components)) {
                foreach ($this->components as $properties) {
                    $this->events[] = new CalendarEvent($properties);
                }
            }

            return true;
        }

        $_calendar_error->throwError(ErrorHandler::ERROR_CRITICAL, _("Die Import-Daten konnten nicht verarbeitet werden!"), __FILE__, __LINE__);

        return false;
    }

    function getType()
    {

        return $this->type;
    }

    function &getObjects()
    {

        return $objects =& $this->events;
    }

    function changePublicToPrivate($value = true)
    {
        $this->public_to_private = $value;
    }

    function getClientIdentifier($data = null)
    {
        return $this->client_identifier;
    }

}

