<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CalendarParser.class.php
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
// CalendarParser.class.php
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
require_once("$RELATIVE_PATH_CALENDAR/lib/CalendarEvent.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/driver/$CALENDAR_DRIVER/CalendarDriver.class.php");

class CalendarParser {

    var $events = array();
    var $components;
    var $type;
    var $number_of_events;
    
    function CalendarParser () {
    
        // initialize error handling
        init_error_handler('_calendar_error');
    }
    
    function getCount ($data) {
    
        return FALSE;
    }
    
    function parseIntoDatabase ($data, $ignore) {
    
        $database = new CalendarDriver();
        if ($this->parseIntoObjects($data, $ignore)) {
            $database->writeObjectsIntoDatabase($this->events, 'INSERT_IGNORE');
            return TRUE;
        }
        
        return FALSE;
    }
    
    function parseIntoObjects ($data, $ignore) {
        global $_calendar_error;
        
        if ($this->parse($data, $ignore)) {
            foreach ($this->components as $properties)
                $this->events[] = new CalendarEvent($properties);
            
            return TRUE;
        }
        
        $_calendar_error->throwError(ERROR_FATAL,
                _("Die Import-Datei konnte nicht verarbeitet werden!"), __FILE__, __LINE__);
        
        return FALSE;
    }
    
    function getType () {
        
        return $this->type;
    }
    
    function &getObjects () {
        
        return $objects =& $this->events;
    }
    
}

?>
