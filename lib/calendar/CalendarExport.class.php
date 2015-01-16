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

require_once('lib/calendar/CalendarExportException.class.php');

class CalendarExport
{

    private $_writer;
    private $export;
    private $count;

    function CalendarExport(&$writer)
    {
        $this->_writer = $writer;
    }

    function exportFromDatabase($range_id = null, $start = 0, $end = Calendar::CALENDAR_END, $event_types = null, $except = NULL)
    {
        global $_calendar_error, $user;

        if (!$range_id) {
            $range->id = $user->id;
        }
        $calendar = new SingleCalendar($range_id);
        //$export_driver = CalendarDriver::getInstance($range_id);
        //$export_driver->openDatabase('EVENTS', $event_types, $start, $end, $except, $sem_ids);
        //$user_sems = Calendar::getBindSeminare($range_id, TRUE);

        $this->_export($this->_writer->writeHeader());
        $calendar->getEvents($event_types, $start, $end);
        foreach ($calendar->events as $event) {
            $this->_export($this->_writer->write($event));
        }
        $this->count = sizeof($events);

        if ($this->count == 0) {
            $message =  _('Es wurden keine Termine exportiert.');
        } else {
            $message = sprintf(ngettext('Es wurde 1 Termin exportiert', 'Es wurden %s Termine exportiert', $export_driver->getCount()), $export_driver->getCount());
        }
        
        $this->_export($this->_writer->writeFooter());
    }

    function exportFromObjects($events)
    {
        global $_calendar_error;

        $this->_export($this->_writer->writeHeader());

        $this->count = 0;
        foreach ($events as $event) {
            $this->_export($this->_writer->write($event));
            $this->count++;
        }

        if (!sizeof($events)) {
            $message = _('Es wurden keine Termine exportiert.');
        } else {
            $message = sprintf(ngettext('Es wurde 1 Termin exportiert', 'Es wurden %s Termine exportiert', sizeof($events)), sizeof($events));
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

    /*
    function setClientIdentifier($client_id)
    {
        $this->_writer->client_identifier = $client_id;
    }
     * 
     */
     

}
