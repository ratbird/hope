<?
/**
* CalendarDriver.class.php
* 
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: CalendarDriver.class.php,v 1.4 2009/09/06 01:33:37 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar_sync
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarDriver.class.php
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

global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR;

require_once $RELATIVE_PATH_CALENDAR . '/lib/driver/MySQL/MysqlDriver.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php';

class CalendarDriver extends MysqlDriver {
	
	var $db_sem;
	var $_sem_events;
	var $object_type;
	var $mod;
	var $range_id;
	var $user_id;
	var $permission;
	
//i	function CalendarDriver ($user_id, $permission = NULL) {
	function CalendarDriver ($range_id, $permission = NULL) {
		
		parent::MysqlDriver();
		$this->db['db_sem'] = NULL;
		$this->_sem_events = FALSE;
		$this->object_type = 'cal';
		$this->user_id = $GLOBALS['auth']->auth['uid'];
		$this->range_id = $range_id;
		if (is_null($permission)) {
			$permission = CALENDAR_PERMISSION_OWN;
		}
		$this->permission = $permission;
	}
	
	//i function &getInstance ($user_id = NULL, $permission = NULL) {
	function &getInstance ($range_id = NULL, $permission = NULL) {
		global $user;
		static $instance = array();
		
		//i if (is_null($user_id)) {
		if (is_null($range_id)) {
			$range_id = $user->id;
		}
		if (!isset($instance[$range_id])) {
			$instance[$range_id] =& new CalendarDriver($range_id, $permission);
		}
		
		return $instance[$range_id];
	}
	
	function bindSeminarEvents () {
		
		$this->_sem_events = TRUE;
	}
	
	function openDatabase ($mod, $event_type = '', $start = 0,
			$end = CALENDAR_END, $except = NULL, $sem_ids = NULL) {
		
		if ($event_type == '')
			$event_type = 'CALENDAR_EVENTS';
		
		$this->mod = $mod;
		
		
		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// to-do: Prüfen ob wir uns in Projektumgebung befinden verbessern!
		if (!isset($GLOBALS['SessSemName'][1])) {
			if (!$GLOBALS['calendar_sess_control_data']['show_project_events']) {
				$event_type = 'CALENDAR_EVENTS';
			}
		}
		
		switch ($this->mod) {
			case 'EVENTS':
				$select_cal = '*';
				$select_semcal = 'ce.*, s.Name';
				$select_sem = 't.*, s.Name, su.status';
				break;
			
			case 'COUNT':
				$select_cal = 'count(event_id) AS cnt';
				$select_semcal = 'count(event_id) AS cnt';
				$select_sem = 'count(termin_id) AS cnt';
				break;
		}
		
		if ($event_type == 'ALL_EVENTS' || $event_type == 'CALENDAR_EVENTS') {
			$this->initialize('cal');
			
			$query = "SELECT $select_cal FROM calendar_events WHERE range_id = '{$this->range_id}' "
					. "AND (start BETWEEN $start AND $end "
					. "OR (start <= $end AND (expire + end - start) >= $start AND rtype != 'SINGLE') "
					. "OR ($start BETWEEN start AND end))";
			if ($exept !== NULL) {
				$except = implode("','", $except);
				$query .= " AND NOT IN '$except'";
			}
			$this->db['cal']->query($query);
		}
		
		if ($event_type == 'ALL_EVENTS' || $event_type == 'SEMINAR_EVENTS') {
			$this->initialize('semcal');
			$this->initialize('sem');
			$this->initialize('semperms');
			/*
			if (is_null($sem_ids)) {
				$query_semcal = "SELECT $select_semcal FROM calendar_events ce LEFT JOIN seminar_user su ON su.Seminar_id = ce.range_id
						LEFT JOIN seminare s USING(Seminar_id) WHERE user_id = '{$this->range_id}' "
					. "AND (start BETWEEN $start AND $end "
					. "OR (start <= $end AND (expire + end - start) >= $start AND rtype != 'SINGLE') "
					. "OR ($start BETWEEN start AND end))";
				
				$query_sem = "SELECT $select_sem "
							 . "FROM termine t LEFT JOIN seminar_user su ON su.Seminar_id=t.range_id "
							 . "LEFT JOIN seminare s USING(Seminar_id) WHERE "
		      		 . "user_id = '{$this->range_id}' AND date BETWEEN $start AND $end";
				echo "<br>$query_semcal";
			} else {*/
				if (is_array($sem_ids) && count($sem_ids)) {
					$sem_ids = implode("','", $sem_ids);
				} else {
					$sem_ids = '';
				}
				
				$query_semcal = "SELECT $select_semcal FROM calendar_events ce LEFT JOIN seminar_user su ON su.Seminar_id = ce.range_id
						LEFT JOIN seminare s USING(Seminar_id) WHERE su.user_id = '{$this->range_id}' 
						AND range_id IN ('$sem_ids') AND su.bind_calendar = 1 "
					. "AND (start BETWEEN $start AND $end "
					. "OR (start <= $end AND (expire + end - start) >= $start AND rtype != 'SINGLE') "
					. "OR ($start BETWEEN start AND end))";
				
				
				
				/////////////////////////////////////////////////////////////////////////
				// to-do: Unterscheidung zwischen range_id, user_id des aktuellen Benutzers und der range_id (user_id) des Kalenders
				// eines anderen Benutzers verbessern! Argh!!!
				if ($this->range_id == $sem_ids) {
					$range_id = $GLOBALS['user']->id;
				} else {
					$range_id = $this->range_id;
				}
				
				
				
				$query_sem = "SELECT $select_sem "
							 . "FROM termine t LEFT JOIN seminar_user su ON su.Seminar_id=t.range_id "
							 . "LEFT JOIN seminare s USING(Seminar_id) WHERE "
		       		 . "user_id = '$range_id' AND range_id IN ('$sem_ids') AND "
							 . "date BETWEEN $start AND $end";
		//	}
			
			$this->db['semcal']->query($query_semcal);
			$this->db['sem']->query($query_sem);
		}
	}
	
	function nextProperties () {
	
		if ($this->mod != 'EVENTS')
			return FALSE;

		if (is_object($this->db['cal']) && $this->db['cal']->next_record()) {
			$this->object_type = 'cal';
			$properties = array(
					'DTSTART'           => $this->db['cal']->f('start'),
					'DTEND'             => $this->db['cal']->f('end'),
					'SUMMARY'           => stripslashes($this->db['cal']->f('summary')),
					'DESCRIPTION'       => stripslashes($this->db['cal']->f('description')),
					'UID'               => $this->db['cal']->f('uid'),
					'CLASS'             => $this->db['cal']->f('class'),
					'CATEGORIES'        => stripslashes($this->db['cal']->f('categories')),
					'STUDIP_CATEGORY'   => $this->db['cal']->f('category_intern'),
					'PRIORITY'          => $this->db['cal']->f('priority'),
					'LOCATION'          => stripslashes($this->db['cal']->f('location')),
					'RRULE'             => array(
							'rtype'         => $this->db['cal']->f('rtype'),
							'linterval'     => $this->db['cal']->f('linterval'),
							'sinterval'     => $this->db['cal']->f('sinterval'),
							'wdays'         => $this->db['cal']->f('wdays'),
							'month'         => $this->db['cal']->f('month'),
							'day'           => $this->db['cal']->f('day'),
							'expire'        => $this->db['cal']->f('expire'),
							'duration'      => $this->db['cal']->f('duration'),
							'count'         => $this->db['cal']->f('count'),
							'ts'            => $this->db['cal']->f('ts')),
					'EXDATE'            => $this->db['cal']->f('exceptions'),
					'CREATED'           => $this->db['cal']->f('mkdate'),
					'LAST-MODIFIED'     => $this->db['cal']->f('chdate'),
					'STUDIP_ID'         => $this->db['cal']->f('event_id'),
					'DTSTAMP'           => time(),
					'EVENT_TYPE'        => 'cal',
					'STUDIP_AUTHOR_ID'  => $this->db['cal']->f('autor_id'));
			
			$this->count();
			
			return $properties;
		} elseif (is_object($this->db['semcal']) && $this->db['semcal']->next_record()) {
			$this->object_type = 'semcal';
			$properties = array(
					'DTSTART'         => $this->db['semcal']->f('start'),
					'DTEND'           => $this->db['semcal']->f('end'),
					'SUMMARY'         => stripslashes($this->db['semcal']->f('summary')),
					'DESCRIPTION'     => stripslashes($this->db['semcal']->f('description')),
					'LOCATION'        => stripslashes($this->db['semcal']->f('location')),
					'STUDIP_CATEGORY' => $this->db['semcal']->f('category_intern'),
					'CREATED'         => $this->db['semcal']->f('mkdate'),
					'LAST-MODIFIED'   => $this->db['semcal']->f('chdate'),
					'STUDIP_ID'       => $this->db['semcal']->f('event_id'),
					'SEMNAME'         => stripslashes($this->db['semcal']->f('Name')),
					'SEM_ID'          => $this->db['semcal']->f('range_id'),
					'CLASS'           => 'PRIVATE',
					'CATEGORIES'      => stripslashes($this->db['semcal']->f('categories')),
					'UID'             => SeminarEvent::createUid($this->db['semcal']->f('event_id')),
					'RRULE'           => array(
							'rtype'         => $this->db['semcal']->f('rtype'),
							'linterval'     => $this->db['semcal']->f('linterval'),
							'sinterval'     => $this->db['semcal']->f('sinterval'),
							'wdays'         => $this->db['semcal']->f('wdays'),
							'month'         => $this->db['semcal']->f('month'),
							'day'           => $this->db['semcal']->f('day'),
							'expire'        => $this->db['semcal']->f('expire'),
							'duration'      => $this->db['semcal']->f('duration'),
							'count'         => $this->db['semcal']->f('count'),
							'ts'            => $this->db['semcal']->f('ts')),
					'EXDATE'            => $this->db['semcal']->f('exceptions'),
					'CREATED'           => $this->db['semcal']->f('mkdate'),
					'LAST-MODIFIED'     => $this->db['semcal']->f('chdate'),
					'STUDIP_ID'         => $this->db['semcal']->f('event_id'),
					'DTSTAMP'           => time(),
					'EVENT_TYPE'        => 'semcal',
					'STUDIP_AUTHOR_ID'  => $this->db['semcal']->f('autor_id'));
			
			$query_semperms = "SELECT COUNT(*) AS count FROM seminar_user WHERE Seminar_id = '{$properties['SEM_ID']}' AND user_id = '{$GLOBALS['user']->id}'";
			$this->db['semperms']->query($query_semperms);
			if ($this->db['semperms']->next_record() && $this->db['semperms']->f('count') > 0) {
				$properties['CLASS'] = 'PRIVATE';
			} else {
				$properties['CLASS'] = 'CONFIDENTIAL';
			}
			
			$this->count();
			return $properties;
		} elseif (is_object($this->db['sem']) && $this->db['sem']->next_record()) {
			$this->object_type = 'sem';
			$properties = array(
					'DTSTART'         => $this->db['sem']->f('date'),
					'DTEND'           => $this->db['sem']->f('end_time'),
					'SUMMARY'         => stripslashes($this->db['sem']->f('content')),
					'DESCRIPTION'     => stripslashes($this->db['sem']->f('description')),
					'LOCATION'        => stripslashes($this->db['sem']->f('raum')),
					'STUDIP_CATEGORY' => $this->db['sem']->f('date_typ') + 1,
					'CREATED'         => $this->db['sem']->f('mkdate'),
					'LAST-MODIFIED'   => $this->db['sem']->f('chdate'),
					'STUDIP_ID'       => $this->db['sem']->f('termin_id'),
					'SEMNAME'         => stripslashes($this->db['sem']->f('Name')),
					'CLASS'           => 'PRIVATE',
					'UID'             => SeminarEvent::createUid($this->db['sem']->f('termin_id')),
					'RRULE'           => SeminarEvent::createRepeat(),
					'EVENT_TYPE'      => 'sem',
					'DTSTAMP'         => time());
			
			$this->count();
			return $properties;
		} else {
			$this->object_type = 'cal';
		}
		
		return FALSE;
	}
	
	function &nextObject () {
		if ($this->mod != 'EVENTS') {
			return FALSE;
		}
		if ($properties = $this->nextProperties()) {
			if ($this->object_type == 'semcal') {
				$event =& new SeminarCalendarEvent($properties, $this->db['semcal']->f('event_id'), $this->db['semcal']->f('range_id'));
			//	$event->sem_id = $this->db['semcal']->f('range_id');
			//	if ($this->db['semcal']->f('status') == 'tutor' || $this->db['semcal']->f('status') == 'dozent') {
			//		$event->setWritePermission(TRUE);
			//	}
			} elseif ($this->object_type == 'sem') {
				$event =& new SeminarEvent($this->db['sem']->f('termin_id'), $properties, $this->db['sem']->f('range_id'));
				if ($this->db['sem']->f('status') == 'tutor' || $this->db['sem']->f('status') == 'dozent') {
					$event->setWritePermission(TRUE);
				}
			} else {
				$event =& new CalendarEvent($properties, $this->db['cal']->f('event_id'),
						$this->db['cal']->f('range_id'), $this->permission);
				$event->setImportDate($this->db['cal']->f('importdate'));
				$event->editor = $this->db['cal']->f('editor_id');
			}
			
			return $event;
		}
			
		return FALSE;
	}
	
	// this method is optimized for getting a single event
	function openDatabaseGetSingleObject ($event_id, $event_type = 'CALENDAR_EVENTS') {
		
		$this->mod = 'EVENTS';
		
		if ($event_type == 'CALENDAR_EVENTS') {
			$this->initialize('cal');
			
			$query = "SELECT * FROM calendar_events WHERE range_id = '{$this->range_id}' "
					. "AND event_id = '$event_id'";
			$this->db['cal']->query($query);
		} elseif ($event_type == 'SEMINAR_CALENDAR_EVENTS') {
			$this->initialize('semcal');
			$this->initialize('semperms');
			$query = "SELECT ce.*, s.Name "
							. "FROM calendar_events ce LEFT JOIN seminar_user su ON (su.Seminar_id=ce.range_id) "
							. "LEFT JOIN seminare s USING(Seminar_id) WHERE "
		      		. "event_id = '$event_id' AND user_id = '{$this->range_id}'";
			$this->db['semcal']->query($query);
		} elseif ($event_type == 'SEMINAR_EVENTS') {
			$this->initialize('sem');
			$query = "SELECT t.*, s.Name "
							. "FROM termine t LEFT JOIN seminar_user su ON (su.Seminar_id=t.range_id) "
							. "LEFT JOIN seminare s USING(Seminar_id) WHERE "
		      		. "termin_id = '$event_id' AND user_id = '{$this->user_id}'";
			$this->db['sem']->query($query);
		} else {
			$this->mod = '';
		}
	}
	
	// depricated
	function openDatabaseGetView (&$view, $event_type = 'CALENDAR_EVENTS') {
		$calendar_view = strtolower(get_class($view));
		switch ($calendar_view) {
			case 'dbcalendarday' :
				$this->mod = 'EVENTS';
				$this->initialize('cal');
				$query = "SELECT * FROM calendar_events WHERE range_id='" . $this->range_id
						. "' AND ((start BETWEEN " . $view->getStart() . " AND " . $view->getEnd()
						. " OR end BETWEEN " . $view->getStart() . " AND " . $view->getEnd()
						. ") OR (" . $view->getStart() . " BETWEEN start AND end) OR "
						. "(start <= " . $view->getEnd() . " AND expire > " . $view->getStart()
						. " AND (rtype = 'DAILY' OR (rtype = 'WEEKLY' AND wdays LIKE '%"
						. $view->getDayOfWeek() . "%') OR (rtype = 'MONTHLY' AND (wdays LIKE '%"
						. $view->getDayOfWeek() . "%' OR day = " . $view->getValue()
						. ")) OR (rtype = 'YEARLY' AND (month = " . $view->getMonth()
						. " AND (day = " . $view->getValue() . " OR wdays LIKE '%"
						. $view->getDayOfWeek() . "%'))) OR duration > 0)))";
				$this->db['cal']->query($query);
				break;
			
			case 'dbcalendarmonth' :
				$this->mod = 'EVENTS';
				$this->initialize('cal');
				$query = sprintf("SELECT * FROM calendar_events "
						. "WHERE range_id='%s' AND (start BETWEEN %s AND %s OR "
						. "(start <= %s AND expire > %s AND rtype != 'SINGLE') OR (%s BETWEEN start AND end))"
						. " ORDER BY start ASC",
						$this->range_id, $view->getStart(), $view->getEnd(), $view->getEnd(),
						$view->getStart(), $view->getStart());
				$this->db['cal']->query($query);
				break;
			
			default:
				$this->openDatabase('EVENTS', $event_type, $view->getStart(), $view->getEnd());
		}
	}
	
	// sets the import date to the current date
	function writeIntoDatabase ($properties, $mode = 'REPLACE') {
		global $user;
		
		if (!sizeof($properties))
			return FALSE;
			
		if ($mode == 'INSERT_IGNORE')
			$query = "INSERT IGNORE INTO";
		elseif ($mode ==  'INSERT')
			$query = "INSERT INTO";
		elseif ($mode == 'REPLACE')
			$query = "REPLACE";
		
		$query .= " calendar_events VALUES ";
		
		$this->initialize('cal');
		
		$mult = FALSE;
		foreach ($properties as $property_set) {
		
			if ($property_set['ID'] == '')
				$id = CalendarEvent::createUniqueId();
			else
				$id = $property_set['ID'];
			
			if (!$property_set['STUDIP_AUTHOR_ID']) {
				$property_set['STUDIP_AUTHOR_ID'] = $user->id;
			}
			
			if ($mult)
				$query .= ",\n";
			else
				$mult = TRUE;
			$query .= sprintf("('%s','%s','%s','%s','%s',%s,%s,'%s','%s','%s','%s',%s,%s,'%s',%s,%s,%s,
					'%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s,%s)",
					$id, $this->range_id, $property_set['STUDIP_AUTHOR_ID'], $user->id,
					$property_set['UID'],
					$property_set['DTSTART'],
					$property_set['DTEND'],
					addslashes($property_set['SUMMARY']),
					addslashes($property_set['DESCRIPTION']),
					$property_set['CLASS'],
					addslashes($property_set['CATEGORIES']),
					(int) $property_set['STUDIP_CATEGORY'],
					(int) $property_set['PRIORITY'],
					addslashes($property_set['LOCATION']),
					$property_set['RRULE']['ts'],
					(int) $property_set['RRULE']['linterval'],
					(int) $property_set['RRULE']['sinterval'],
					$property_set['RRULE']['wdays'],
					(int) $property_set['RRULE']['month'],
					(int) $property_set['RRULE']['day'],
					$property_set['RRULE']['rtype'],
					$property_set['RRULE']['duration'],
					$property_set['RRULE']['count'],
					$property_set['RRULE']['expire'],
					$property_set['EXDATE'],
					$property_set['CREATED'],
					$property_set['LAST-MODIFIED'],
					time());
			
			$this->count();
		}
		
		$this->db['cal']->query($query);
	}
	
	function writeObjectsIntoDatabase ($objects, $mode = 'REPLACE') {
		global $user;
		
		if (is_object($objects))
			$objects = array($objects);
		
		if (!sizeof($objects))
			return FALSE;
		
		if ($mode == 'INSERT_IGNORE')
			$query = "INSERT IGNORE INTO";
		elseif ($mode ==  'INSERT')
			$query = "INSERT INTO";
		elseif ($mode == 'REPLACE')
			$query = "REPLACE";
		
		$query .= " calendar_events VALUES ";
		
		$this->initialize('cal');
		
		$mult = FALSE;
		foreach ($objects as $object) {
			if (strtolower(get_class($object)) == 'seminarevent') {
				continue;
			}
			
			if ($object->properties['STUDIP_AUTHOR_ID']) {
				$author_id = $object->properties['STUDIP_AUTHOR_ID'];
			} else {
				$author_id = $user->id;
			}
			
			if ($mult)
				$query .= ",\n";
			else
				$mult = TRUE;
			$query .= sprintf("('%s','%s','%s','%s','%s',%s,%s,'%s','%s','%s','%s',%s,%s,'%s',%s,%s,%s,
					'%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s,%s)",
					$object->getId(), $this->range_id, $author_id, $user->id,
					$object->properties['UID'],
					$object->properties['DTSTART'],
					$object->properties['DTEND'],
					addslashes($object->properties['SUMMARY']),
					addslashes($object->properties['DESCRIPTION']),
					$object->properties['CLASS'],
					addslashes($object->properties['CATEGORIES']),
					(int) $object->properties['STUDIP_CATEGORY'],
					(int) $object->properties['PRIORITY'],
					addslashes($object->properties['LOCATION']),
					$object->properties['RRULE']['ts'],
					(int) $object->properties['RRULE']['linterval'],
					(int) $object->properties['RRULE']['sinterval'],
					$object->properties['RRULE']['wdays'],
					(int) $object->properties['RRULE']['month'],
					(int) $object->properties['RRULE']['day'],
					$object->properties['RRULE']['rtype'],
					$object->properties['RRULE']['duration'],
					$object->properties['RRULE']['count'],
					$object->properties['RRULE']['expire'],
					$object->properties['EXDATE'],
					$object->properties['CREATED'],
					$object->properties['LAST-MODIFIED'],
					$object->getImportDate());
			
			$this->count();
		}
			
		$this->db['cal']->query($query);
		
		return TRUE;
	}
	
	function getCountEvents () {
		
		if ($this->mod != 'COUNT')
			return FALSE;
		
		$count = 0;
		if (is_object($this->db['cal']) && $this->db['cal']->next_record())
			$count = $this->db['cal']->f('cnt');
		if (is_object($this->db['sem']) && $this->db['sem']->next_record())
			$count += $this->db['sem']->f('cnt');
		
		return $count;
	}
	
	function deleteFromDatabase ($mod, $event_ids = NULL, $start = 0,
			$end = CALENDAR_END, $range_id = '') {
		
		$this->initialize('cal');
		if ($range_id == '')
			$range_id = $this->range_id;
		
		$query = "DELETE FROM calendar_events WHERE range_id = '$range_id'";
		switch ($mod) {
			case 'ALL':
				break;
			
			case 'EXPIRED':
				$query .= " AND (expire < $end OR (rtype = 'SINGLE' AND end < $end))";
				$query .= " AND chdate < $end";
				break;
			
			case 'SINGLE':
				if (is_array($event_ids)) {
					$event_ids = implode("','", $event_ids);
					$query .= " AND event_id IN ('$event_ids')";
				}
				else
					$query .= " AND event_id = '$event_ids'";
				break;
				
			case 'KILL':
				$query_kill_sync_entries = "DELETE FROM calendar_sync WHERE range_id = '$range_id'";
				$this->db['cal']->query($query_kill_sync_entries);
				break;
		}
		
		$this->db['cal']->query($query);
		
		if ($rows = $this->db['cal']->affected_rows())
			return $rows;
		
		return FALSE;
	}
	
	function deleteObjectsFromDatabase ($objects) {
		if (is_object($objects))
			$objects = array($objects);
		
		$event_ids = array();
		foreach ($objects as $object) {
			if (strtolower(get_class($object)) != 'seminarevent') {
				$event_ids[] = $object->getId();
			}
		}
		
		return $this->deleteFromDatabase('SINGLE', $event_ids);
	}
		
}

?>
