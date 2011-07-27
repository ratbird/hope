<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CalendarExport.class.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  calendar_modules
* @module       calendar_import
* @package  Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarExport.class.php
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

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once("$RELATIVE_PATH_CALENDAR/lib/ErrorHandler.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/driver/$CALENDAR_DRIVER/CalendarDriver.class.php");
 
class CalendarExport {
    
    var $_writer;
    var $export;
    var $count;
    
    function CalendarExport (&$writer) {
        
        // initialize error handling
        init_error_handler('_calendar_error');
        $this->_writer =& $writer;
    }
    
    function exportFromDatabase ($range_id = '', $start = 0, $end = 2114377200,
            $event_types = 'CALENDAR_EVENTS', $sem_id, $except = NULL) {
        global $_calendar_error;
        
        $export_driver = new CalendarDriver();
        $export_driver->openDatabase('EVENTS', $event_types, $start,
                $end, $except, $range_id, $sem_id);
        
        $this->_export($this->_writer->writeHeader());
        
        while ($event = $export_driver->nextObject()) {     
            $this->_export($this->_writer->write($event));
        }
        $this->count = $export_driver->getCount();
        
        if ($export_driver->getCount() == 0) {
            $_calendar_error->throwError(ERROR_MESSAGE,
                    _("Es wurden keine Termine exportiert."));
        }
        else {
            $_calendar_error->throwError(ERROR_MESSAGE,
                    sprintf(_("Es wurden %s Termine exportiert"), $export_driver->getCount()));
        }
        
        $this->_export($this->_writer->writeFooter());
    }
    
    function exportFromObjects (&$events) {
        global $_calendar_error;
        
        $this->_export($this->_writer->writeHeader());
        
        $count = 0;
        foreach ($events as $event) {           
            $this->_export($this->_writer->write($event));
            $count++;
        }
        $this->count = $count;
        
        if (!sizeof($events)) {
            $_calendar_error->throwError(ERROR_MESSAGE,
                    _("Es wurden keine Termine exportiert."));
        }
        else {
            $_calendar_error->throwError(ERROR_MESSAGE,
                    sprintf(_("Es wurden %s Termine exportiert"), sizeof($events)));
        }
        
        $this->_export($this->_writer->writeFooter());
    }
    
    function getExport () {
        
        return $this->export;
    }
    
    function _export ($string) {
        
        $this->export .= $string;
    }
    
    function getCount () {
    
        return $this->count;
    }
    
}

///////////////////////////////////////////////////////////////////////////////////////
// debugging
///////////////////////////////////////////////////////////////////////////////////////
/*
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
require_once("$RELATIVE_PATH_CALENDAR/lib/sync/CalendarWriterICalendar.class.php");
global $user;
$export = new CalendarExport(new CalendarWriterICalendar());
$export->exportFromDatabase($user->id);
echo "<pre>" . $export->export . "</pre>";
page_close();
*/
?>
