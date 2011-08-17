<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarExport.class.php
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
require_once("$RELATIVE_PATH_CALENDAR/lib/driver/$CALENDAR_DRIVER/CalendarDriver.class.php");

class CalendarExport
{

    var $_writer;
    var $export;
    var $count;

    function CalendarExport(&$writer)
    {

        // initialize error handling
        init_error_handler('_calendar_error');
        $this->_writer = $writer;
    }

    function exportFromDatabase($range_id = '', $start = 0, $end = Calendar::CALENDAR_END, $event_types = 'ALL_EVENTS', $sem_ids = NULL, $except = NULL)
    {
        global $_calendar_error, $user;

        if (!$range_id) {
            $range->id = $user->id;
        }
        $export_driver = CalendarDriver::getInstance($range_id);
        $export_driver->openDatabase('EVENTS', $event_types, $start, $end, $except, $sem_ids);
        $user_sems = Calendar::getBindSeminare($range_id, TRUE);

        $this->_export($this->_writer->writeHeader());

        while ($event = $export_driver->nextObject()) {
            if (strtolower(get_class($event)) == 'seminarevent'
                    && !in_array($event->getSeminarId(), $user_sems)) {
                $event->setPermission(Event::PERMISSION_CONFIDENTIAL);
            }
            $this->_export($this->_writer->write($event));
        }
        $this->count = $export_driver->getCount();

        if ($this->count == 0) {
            $_calendar_error->throwError(ErrorHandler::ERROR_MESSAGE, _("Es wurden keine Termine exportiert."));
        } else {
            $_calendar_error->throwError(ErrorHandler::ERROR_MESSAGE, sprintf(_("Es wurden %s Termine exportiert"), $export_driver->getCount()));
        }

        $this->_export($this->_writer->writeFooter());
    }

    function exportFromObjects(&$events)
    {
        global $_calendar_error;

        $this->_export($this->_writer->writeHeader());

        $this->count = 0;
        foreach ($events as $event) {
            $this->_export($this->_writer->write($event));
            $this->count++;
        }

        if (!sizeof($events)) {
            $_calendar_error->throwError(ErrorHandler::ERROR_MESSAGE, _("Es wurden keine Termine exportiert."));
        } else {
            $_calendar_error->throwError(ErrorHandler::ERROR_MESSAGE, sprintf(_("Es wurden %s Termine exportiert"), sizeof($events)));
        }

        $this->_export($this->_writer->writeFooter());
    }

    function _export($exp)
    {
        if (sizeof($exp)) {
            $this->export[] = $exp;
        }
    }

    function getExport()
    {

        return $this->export;
    }

    function getCount()
    {

        return $this->count;
    }

    function setClientIdentifier($client_id)
    {
        $this->_writer->client_identifier = $client_id;
    }

}

///////////////////////////////////////////////////////////////////////////////////////
// debugging
///////////////////////////////////////////////////////////////////////////////////////
/*
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
require_once("$ABSOLUTE_PATH_STUDIP$RELATIVE_PATH_CALENDAR/lib/sync/CalendarWriterICalendar.class.php");
global $user;
$export = new CalendarExport(new CalendarWriterICalendar());
$export->exportFromDatabase($user->id);
echo "<pre>" . $export->export . "</pre>";
page_close();
*/
