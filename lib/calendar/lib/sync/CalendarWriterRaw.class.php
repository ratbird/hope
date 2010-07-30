<?
/**
* CalendarWriterRaw.class.php
*
*
* @author		Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: CalendarWriterRaw.class.php,v 1.1 2008/12/23 09:50:34 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar_export
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarWriterRaw.class.php
// 
// Copyright (C) 2006 Peter Thienel <thienel@data-quest.de>,
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

require_once $RELATIVE_PATH_CALENDAR . '/lib/sync/CalendarWriter.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/ErrorHandler.class.php';

class CalendarWriterRaw extends CalendarWriter {
	
	function CalendarWriterRaw () {
		
		parent::CalendarWriter();
	}
	
	function write (&$event) {
	//	print_r($event);
		
		$properties = $event->properties;
		$properties['DTSTART']       = $this->writeDateTime($properties['DTSTART']);
		$properties['DTEND']         = $this->writeDateTime($properties['DTEND']);
		$properties['PRIORITY']      = (int) $properties['PRIORITY'];
		$properties['EXDATE']        = $this->writeExceptions($event->getExceptions());
		$properties['CREATED']       = $this->writeDateTime($properties['CREATED']);
		$properties['LASTMODIFIED'] = $this->writeDateTime($properties['LAST-MODIFIED']);
		$properties['DTSTAMP']       = $this->writeDateTime($properties['DTSTAMP']);
		$properties['CATEGORIES']    = explode(', ', $event->toStringCategories());
		
		$properties['RRULE']['linterval'] = (int) $properties['RRULE']['linterval'];
		$properties['RRULE']['sinterval'] = (int) $properties['RRULE']['sinterval'];
		$properties['RRULE']['month']     = (int) $properties['RRULE']['month'];
		$properties['RRULE']['day']       = (int) $properties['RRULE']['day'];
		$properties['RRULE']['expire']    = $this->writeDateTime($properties['RRULE']['expire']);
		$properties['RRULE']['count']     = (int) $properties['RRULE']['count'];		
		
		unset($properties['RRULE']['ts']);
		unset($properties['RRULE']['duration']);
		unset($properties['STUDIP_CATEGORY']);
		unset($properties['STUDIP_AUTHOR_ID']);
		
		return $properties;
	}
	
	function writeDateTime ($timestamp) {
		return array(
				'year' => intval(date('Y', $timestamp)),
				'month' => intval(date('n', $timestamp)),
				'day' => intval(date('j', $timestamp)),
				'hour' => intval(date('G', $timestamp)),
				'minute' => intval(date('i', $timestamp)),
				'second' => intval(date('s', $timestamp)));
	}
	
	function writeExceptions ($exceptions) {
		$ex = array();
		foreach ($exceptions as $exception) {
			$ex[] = $this->writeDateTime($exception);
		}
		return $ex;
	}
	
}
?>