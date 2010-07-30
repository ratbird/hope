<?
/**
* CalendarWriter.class.php
*
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: CalendarWriter.class.php,v 1.2 2008/12/23 09:50:34 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar_export
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarWriter.class.php
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

require_once $RELATIVE_PATH_CALENDAR . '/lib/ErrorHandler.class.php';

class CalendarWriter {

	var $default_filename_suffix;
	var $format;
	var $client_identifier;
	
	function CalendarWriter () {
		
		// initialize error handler
		init_error_handler('_calendar_error');
	}
	
	function write (&$event) {
		
		return $event->properties;
	}
	
	function writeHeader () {
	
	}
	
	function writeFooter () {
	
	}
	
	function getDefaultFilenameSuffix () {
	
		return $this->default_filename_suffix;
	}
	
	function getFormat () {
	
		return $this->format;
	}
	
}
?>