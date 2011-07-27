<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* CalendarDriver.class.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  calendar_modules
* @module       calendar_sync
* @package  Calendar
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

global $RELATIVE_PATH_CALENDAR;

require_once("$RELATIVE_PATH_CALENDAR/lib/driver/MySQL/MysqlDriver.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/CalendarEvent.class.php");
require_once("$RELATIVE_PATH_CALENDAR/lib/SeminarEvent.class.php");

class CalendarDriver extends MysqlDriver {
    
    var $db_sem;
    var $_sem_events;
    var $_create_sem_object;
    var $mod;
    
    function CalendarDriver () {
        
        parent::MysqlDriver();
        $this->db['db_sem'] = NULL;
        $this->_sem_events = FALSE;
        $this->_create_sem_object = FALSE;
    }
    
    function bindSeminarEvents () {
        
        $this->$_sem_events = TRUE;
    }
    
    function openDatabase ($mod, $event_types = '', $start = 0,
            $end = 2114377200, $except = NULL, $range_id = '', $sem_ids = '') {
        global $user;
        
        if ($event_types == '')
            $event_types = 'CALENDAR_EVENTS';
        
        if ($range_id == '')
            $range_id = $user->id;
        
        $this->mod = $mod;
        switch ($this->mod) {
            case 'EVENTS':
                $select_cal = '*';
                $select_sem = 't.*, s.Name, th.title, th.description as details, su.* ';
                break;
            
            case 'COUNT':
                $select_cal = 'count(event_id) AS cnt';
                $select_sem = 'count(termin_id) AS cnt';
                break;
        }
        
        if ($event_types == 'ALL_EVENTS' || $event_types == 'CALENDAR_EVENTS') {
            $this->initialize('cal');
            
            $query = "SELECT $select_cal FROM calendar_events WHERE range_id = '$range_id' "
                    . "AND start BETWEEN $start AND $end AND expire >= $start";
            if ($exept !== NULL) {
                $except = implode("','", $except);
                $query .= " AND NOT IN '$except'";
            }
            $this->db['cal']->query($query);
        }
        if ($event_types == 'ALL_EVENTS' || $event_types == 'SEMINAR_EVENTS') {
            $this->initialize('sem');
            
            if ($sem_ids == '')
                $query = "SELECT $select_sem "
                             . "FROM termine t LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen as th USING (issue_id)"
                             . "LEFT JOIN seminar_user su ON su.Seminar_id = t.range_id "
                             . "LEFT JOIN seminare s ON su.Seminar_id = s.Seminar_id WHERE "
                             . "user_id = '{$user->id}' AND date BETWEEN $start AND $end ORDER by th.priority DESC";
            else if ($sem_ids != "") {
                if (is_array($sem_ids))
                    $sem_ids = implode("','", $sem_ids);
                $query = "SELECT $select_sem "
                             . "FROM termine t LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen as th USING (issue_id)"
                             . "LEFT JOIN seminar_user su ON su.Seminar_id = t.range_id "
                             . "LEFT JOIN seminare s ON su.Seminar_id = s.Seminar_id WHERE "
                             . "user_id = '{$user->id}' AND range_id IN ('$sem_ids') AND "
                             . "date BETWEEN $start AND $end ORDER BY th.priority DESC";
            }
            $this->db['sem']->query($query);
        }
    }
    
    function nextProperties () {
        
        if ($this->mod != 'EVENTS')
            return FALSE;

        if (is_object($this->db['cal']) && $this->db['cal']->next_record()) {
            $properties = array(
                    'DTSTART'         => $this->db['cal']->f('start'),
                    'DTEND'           => $this->db['cal']->f('end'),
                    'SUMMARY'         => $this->db['cal']->f('summary'),
                    'DESCRIPTION'     => $this->db['cal']->f('description'),
                    'UID'             => $this->db['cal']->f('uid'),
                    'CLASS'           => $this->db['cal']->f('class'),
                    'CATEGORIES'      => $this->db['cal']->f('categories'),
                    'STUDIP_CATEGORY' => $this->db['cal']->f('category_intern'),
                    'PRIORITY'        => $this->db['cal']->f('priority'),
                    'LOCATION'        => $this->db['cal']->f('location'),
                    'RRULE'           => array(
                            'rtype'       => $this->db['cal']->f('rtype'),
                            'linterval'   => $this->db['cal']->f('linterval'),
                            'sinterval'   => $this->db['cal']->f('sinterval'),
                            'wdays'       => $this->db['cal']->f('wdays'),
                            'month'       => $this->db['cal']->f('month'),
                            'day'         => $this->db['cal']->f('day'),
                            'expire'      => $this->db['cal']->f('expire'),
                            'duration'    => $this->db['cal']->f('duration'),
                            'count'       => $this->db['cal']->f('count')),
                    'EXDATE'          => $this->db['cal']->f('exceptions'),
                    'CREATED'         => $this->db['cal']->f('mkdate'),
                    'LAST-MODIFIED'   => $this->db['cal']->f('chdate'),
                    'DTSTAMP'         => time());
            
            $this->count();
            return $properties;
        }
        elseif (is_object($this->db['sem']) && $this->db['sem']->next_record()) {
            if ($this->db['sem']->f('status') === 'dozent') {
                //wenn ich Dozent bin, zeige den Termin nur, wenn ich durchführender Dozent bin:
                $termin = new SingleDate($this->db['sem']->f('termin_id'));
                if (!in_array($this->db['sem']->f('user_id'), $termin->getRelatedPersons())) {
                    return FALSE;
                }
            }
            $this->_create_sem_object = TRUE;
            $properties = array(
                    'DTSTART'         => $this->db['sem']->f('date'),
                    'DTEND'           => $this->db['sem']->f('end_time'),
                    'SUMMARY'         => $this->db['sem']->f('title') ? $this->db['sem']->f('title') : $this->db['sem']->f('Name'),
                    'DESCRIPTION'     => $this->db['sem']->f('details'),
                    'LOCATION'        => $this->db['sem']->f('raum') ? $this->db['sem']->f('raum') : $this->getRoom($this->db['sem']->f('termin_id')),
                    'STUDIP_CATEGORY' => $this->db['sem']->f('date_typ'),
                    'CREATED'         => $this->db['sem']->f('mkdate'),
                    'LAST-MODIFIED'   => $this->db['sem']->f('chdate'),
                    'DTSTAMP'         => time());
            
            $this->count();
            return $properties;
        }
        else
            $this->_create_sem_object = FALSE;
            
        return FALSE;
    }

    function getRoom ($termin_id) {
        $db = new DB_Seminar();
        $db->query("SELECT ro.name FROM resources_assign as ra LEFT JOIN resources_objects as ro USING (resource_id) WHERE ra.assign_user_id = '$termin_id'");

        if ($db->next_record()) {
            return $db->f('name');
        } else {
            return _("Keine Raumangabe");
        }
    }
    
    function &nextObject () {
        
        if ($this->mod != 'EVENTS')
            return FALSE;
        
        if ($properties = $this->nextProperties()) {
            if ($this->_create_sem_object) {
                $event = new SeminarEvent($this->db['sem']->f('termin_id'), $properties, $this->db['sem']->f('range_id'));
            }
            else {
                $event = new CalendarEvent($properties, $this->db['cal']->f('event_id'));
                $event->user_id = $this->db['cal']->f('range_id');
            }
            
            $this->count();
            return $event;
        }
            
        return FALSE;
    }
    
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
            
            if ($mult)
                $query .= ",\n";
            else
                $mult = TRUE;
            $query .= sprintf("('%s','%s','%s','%s',%s,%s,'%s','%s','%s','%s',%s,%s,'%s',%s,%s,%s,
                    '%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s)",
                    $id, $user->id, $user->id,
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
                    $property_set['LAST-MODIFIED']);
            
            $this->count();
        }
    
    //  echo "<br>$query<br>";
        
        $this->db['cal']->query($query);
    }
    
    function writeObjectsIntoDatabase ($objects, $mode = 'REPLACE') {
        global $user;
        
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
            if ($mult)
                $query .= ",\n";
            else
                $mult = TRUE;
            $query .= sprintf("('%s','%s','%s','%s',%s,%s,'%s','%s','%s','%s',%s,%s,'%s',%s,%s,%s,
                    '%s',%s,%s,'%s',%s,%s,%s,'%s',%s,%s)",
                    $object->getId(), $user->id, $user->id,
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
                    $object->properties['LAST-MODIFIED']);
            
            $this->count();
        }
    
//      echo "<br>$query<br>";
        
        $this->db['cal']->query($query);
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
            $end = 2114377200, $range_id = '') {
        global $user;
        
        $this->initialize('cal');
        if ($range_id == '')
            $range_id = $user->id;
        
        $query = "DELETE FROM calendar_events WHERE range_id = '$range_id'";
        switch ($mod) {
            case 'ALL':
                break;
            
            case 'EXPIRED':
                $query .= " AND (expire < $end OR (rtype = 'SINGLE' AND end < $end))";
                $query .= " AND chdate < $end";
                break;
            
            case 'SINGLE':
                $event_ids = implode(',', $event_ids);
                $query .= " AND event_id IN '$event_ids'";
                break;
        }
        
        $this->db['cal']->query($query);
        
        if ($rows = $this->db['cal']->affected_rows())
            return $rows;
        
        return FALSE;
    }
    
}

?>
