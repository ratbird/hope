<?
/**
* CalendarParserRaw.class.php
* 
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: CalendarParserRaw.class.php,v 1.1 2008/12/23 19:28:19 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar_import
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarParserRaw.class.php
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

require_once $RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarParser.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/Calendar.class.php';

class CalendarParserRaw extends CalendarParser {

	var $events = array();
	var $components;
	var $type;
	var $number_of_events;
	var $public_to_private = FALSE;
	var $client_identifier;
	
	function CalendarParserRaw ($client_identifier) {
	
		parent::CalendarParser();
		
		$this->client_identifier = $client_identifier;
	}
	
	function parse ($data, $ignore = NULL) {
		global $user;
		
		$dtstamp = time();
		foreach ($data as $properties) {
			
			// skip seminar events
			if (!$this->import_sem && strpos($properties['UID'], 'Stud.IP-SEM') === 0) {
				continue;
			}
			if ($this->public_to_private && $properties['CLASS'] == 'PUBLIC') {
				$properties['CLASS'] = 'PRIVATE';
			}
			
			$properties['CATEGORIES'] = implode(', ', $properties['CATEGORIES']);
			$properties['DTSTART'] = $this->parseDateTime($properties['DTSTART']);
			$properties['DTEND'] = $this->parseDateTime($properties['DTEND']);
			$properties['CREATED'] = $this->parseDateTime($properties['CREATED']);
			$properties['LAST-MODIFIED'] = $this->parseDateTime($properties['LASTMODIFIED']);
			$properties['DTSTAMP'] = $this->parseDateTime($properties['DTSTAMP']);
			$properties['EXDATE'] = $this->parseExdates($properties['EXDATE']);
			
			$properties['RRULE']['expire'] = $this->parseDateTime($properties['RRULE']['expire']);
			if ($properties['RRULE']['expire'] >= CALENDAR_END) {
				$properties['RRULE']['count'] = 0;
			}
			$properties['RRULE'] = CalendarEvent::createRepeat($properties['RRULE'],
					$properties['DTSTART'], $properties['DTEND']);
			
			$properties['STUDIP_AUTHOR_ID'] = $user->id;
			$properties['DTSTAMP'] = $dtstamp;
			
			unset($properties['LASTMODIFIED']);
			
			$this->components[] = $properties;
		}
		return TRUE;
	}
	
	function parseDateTime ($dt) {
		return mktime($dt['hour'], $dt['minute'], $dt['second'],
				$dt['month'], $dt['day'], $dt['year']);
	}
	
	function parseExdates ($ed) {
		$exds = array();
		foreach ($ed as $exd) {
			$exds[] = $this->parseDateTime($exd);
		}
		return implode(',', $exds);
	}
			
	
	function getCount ($data) {
		if (is_array($data)) {
			return sizeof($data);
		}
		return 0;
	}
}

?>
