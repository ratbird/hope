<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CalendarParserICalendar.class.php
* 
* Based on the iCalendar parser from The Horde Project
* www.horde.org
* horde/lib/iCalendar.php,v 1.19
* Copyright 2003 Mike Cochrane <mike@graftonhall.co.nz>
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
// CalendarParserICalender.class.php
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

global $RELATIVE_PATH_CALENDAR;

require_once("$RELATIVE_PATH_CALENDAR/lib/ErrorHandler.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/driver/$CALENDAR_DRIVER/CalendarDriver.class.php");

class CalendarSynchronizer {
    
    var $_import;
    var $_export;
    var $count;
    var $max_events;
    
    function CalendarSynchronizer (&$import, &$export, $max_events = 0) {
        global $CALENDAR_MAX_EVENTS;
        
        // initialize error handling
        init_error_handler('_calendar_error');
        
        $this->_import =& $import;
        $this->_export =& $export;
        
        if ($max_events == 0)
            $this->max_events = $CALENDAR_MAX_EVENTS;
        else
            $this->max_events = $max_events;
    }
    
    function synchronize ($compare_fields = NULL) {
        global $_calendar_error;
        
        // export to extern CUA
        $ext = array();
        // events to replace in Stud.IP
        $int = array();
        
        $this->_import->importIntoObjects();
        $events = $this->_import->getObjects();
        $this->count = sizeof($events);
        
        // get events from database
        $db = new CalendarDriver();
        $db->openDatabase('EVENTS', 'CALENDAR_EVENTS');
        
        $sentinel = '#';
        $in_to_ext = TRUE;
        array_unshift($events, $sentinel);
        
        // synchronize!
        while ($in = $db->nextObject()) {
        
            while ($ex = array_pop($events)) {
                
                // end of queue, return to start
                if ($ex == $sentinel) {
                    if ($in_to_ext)
                        $ext[] = $in;
                    array_unshift($events, $sentinel);
                    continue 2;
                }
                
                // no chance to do the job because there's no LAST-MODIFIED...
                if (!$ex->properties['LAST-MODIFIED']) {
                    $_calendar_error->throwError(ERROR_CRITICAL,
                            _("Die Datei kann nicht mit dem Stud.IP-Terminkalender synchronisiert werden."));
                    return FALSE;
                }
                
                // we are lucky, because we have the same UID and LAST-MODIFIED, easy...
                if ($ex->properties['UID'] == $in->properties['UID']) {
                    if ($ex->properties['LAST-MODIFIED'] < $in->properties['LAST-MODIFIED']) {
                        $ext[] = $in;
                    }
                    if ($ex->properties['LAST-MODIFIED'] > $in->properties['LAST-MODIFIED']) {
                        $ex->id = $in->id;
                        $int[] = $ex;
                    }
                    $in_to_ext = FALSE;
                }
                // difficult and unsave, if we have no UID...
                elseif ($ex->properties['CREATED'] == $in->properties['CREATED']) {
                    if (trim($ex->properties['SUMMARY']) == trim($in->properties['SUMMARY'])) {
                        if ($ex->properties['LAST-MODIFIED'] < $in->properties['LAST-MODIFIED']) {
                            $ext[] = $in;
                        }
                        if ($ex->properties['LAST-MODIFIED'] > $in->properties['LAST-MODIFIED']) {
                            $ex->id = $in->id;
                            $int[] = $ex;
                        }
                    }
                    $in_to_ext = FALSE;
                }
                else {
                    array_unshift($events, $ex);
                }
                
            }
                $in_to_ext = TRUE;
        }
        
        // delete sentinel
        array_shift($events);
        // every event left over in $events is not in Stud.IP, so import the rest
        $int = array_merge((array)$int, (array)$events);
        
        if (sizeof($int) > $this->max_events) {
            $_calendar_error->throwError(ERROR_CRITICAL,
                    _("Die zu synchronisierende Datei enth&auml;lt zu viele Termine."));
            return FALSE;
        }
        
        $this->count += $db->getCount();
        
        // OK, work is done, import and export the events
        $db->writeObjectsIntoDatabase($int, 'REPLACE');
        $this->_export->exportFromObjects($ext);
    }
    
    function getCount () {
    
        return $this->count;
    }
}

?>
