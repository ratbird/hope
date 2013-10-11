<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * CalendarDriver.class.php
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


global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR;

require_once $RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php';

class CalendarDriver
{

    private $sem_events;
    private $object_type;
    private $mod;
    private $range_id;
    private $user_id;
    private $permission;
    private $result;
    private $perm;

    public function __construct($range_id, $permission = NULL)
    {
        global $user;

        $this->sem_events = false;
        $this->object_type = 'cal';
        $this->user_id = $user->id;
        $this->range_id = $range_id;
        if (is_null($permission)) {
            $permission = Calendar::PERMISSION_OWN;
        }
        $this->permission = $permission;
        if (is_object($GLOBALS['perm'])) {
            $this->perm =& $GLOBALS['perm'];
        } else {
            $this->perm = new Seminar_Perm();
        }
    }

    function &getInstance($range_id = NULL, $permission = NULL)
    {
        global $user;
        static $instance = array();

        if (is_null($range_id)) {
            $range_id = $user->id;
        }
        if (!isset($instance[$range_id])) {
            $instance[$range_id] = new CalendarDriver($range_id, $permission);
        }

        return $instance[$range_id];
    }

    function bindSeminarEvents()
    {

        $this->sem_events = true;
    }

    function openDatabase($mod, $event_type = '', $start = 0, $end = Calendar::CALENDAR_END, $except = NULL, $sem_ids = NULL)
    {
        if ($event_type == '')
            $event_type = 'CALENDAR_EVENTS';

        $this->mod = $mod;
        $this->result = array();

        switch ($this->mod) {
            case 'EVENTS':
                $select_cal = '*';
                $select_semcal = 'ce.*, s.Name';
                $select_sem = "t.*, s.Name, su.status, resource_id, GROUP_CONCAT(th.title SEPARATOR '; ') as title, GROUP_CONCAT(th.description SEPARATOR '\n\n') as description";
                break;

            case 'COUNT':
                $select_cal = 'count(event_id) AS cnt';
                $select_semcal = 'count(event_id) AS cnt';
                $select_sem = 'count(distinct termin_id) AS cnt';
                break;
        }

        if ($event_type == 'ALL_EVENTS' || $event_type == 'CALENDAR_EVENTS') {
            $db_cal = DBManager::get();

            $query = "SELECT {$select_cal}
                      FROM calendar_events
                      WHERE range_id = :range_id
                        AND (start BETWEEN :start AND :end OR
                             (start <= :end AND (expire + end - start) >= :start AND rtype != 'SINGLE')  OR
                             (:start BETWEEN start AND end))";
            $parameters = array(
                ':range_id' => $this->range_id,
                ':start'    => $start,
                ':end'      => $end
            );
            if ($except !== NULL) {
                $query .= " AND NOT IN (:except)";
                $parameters[':except'] = $except;
            }
            $statement = DBManager::get()->prepare($query);
            $statement->execute($parameters);
            $this->result['cal'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($event_type == 'ALL_EVENTS' || $event_type == 'SEMINAR_EVENTS') {

            $query = "SELECT $select_semcal FROM calendar_events ce
                LEFT JOIN seminar_user su ON su.Seminar_id = ce.range_id
                LEFT JOIN seminare s USING(Seminar_id) WHERE su.user_id = ?
                AND range_id IN (?) AND su.bind_calendar = 1
                AND (start BETWEEN ? AND ?
                OR (start <= ? AND (expire + end - start) >= ?
                AND rtype != 'SINGLE') OR (? BETWEEN start AND end))";
            $db_semcal = DBManager::get()->prepare($query);
            $db_semcal->execute(array(
                $this->range_id,
                $sem_ids ?: '',
                $start, $end,
                $end, $start,
                $start
            ));

            if ($this->range_id == $sem_ids) {
                $range_id = $GLOBALS['user']->id;
            } else {
                $range_id = $this->range_id;
            }

            $query = "SELECT $select_sem "
                    . "FROM (SELECT termin_id,range_id,date,end_time,mkdate,chdate,date_typ,content,metadate_id, 0 as ex_termin
                        FROM termine WHERE range_id IN (?) AND date BETWEEN ? AND ?
                        UNION SELECT termin_id,range_id,date,end_time,mkdate,chdate,date_typ,content,metadate_id, 1 as ex_termin
                        FROM ex_termine WHERE content <> '' AND range_id IN (?) AND date BETWEEN ? AND ?) as t
                        LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen as th USING (issue_id)
                        LEFT JOIN seminar_user su ON su.Seminar_id=t.range_id "
                    . "LEFT JOIN seminare s ON s.Seminar_id=t.range_id "
                    . "LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE "
                    . "(IFNULL(t.metadate_id,'') = '' OR t.metadate_id NOT IN (SELECT metadate_id FROM schedule_seminare WHERE user_id = ? AND visible = 0)) AND "
                    . "user_id = ? GROUP BY termin_id ORDER BY NULL";
            $db_sem = DBManager::get()->prepare($query);
            $db_sem->execute(array(
                    $sem_ids ?: '',
                    $start, $end,
                    $sem_ids ?: '',
                    $start, $end,
                    $range_id,
                    $range_id
            ));

            $this->result['semcal'] = $db_semcal->fetchAll(PDO::FETCH_ASSOC);
            $this->result['sem'] = $db_sem->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    function nextProperties()
    {
        if ($this->mod != 'EVENTS')
            return FALSE;

        if (is_array($this->result['cal']) && (list(, $result) = each($this->result['cal']))) {
            $this->object_type = 'cal';
            $properties = array(
                'DTSTART' => $result['start'],
                'DTEND' => $result['end'],
                'SUMMARY' => stripslashes($result['summary']),
                'DESCRIPTION' => stripslashes($result['description']),
                'UID' => $result['uid'],
                'CLASS' => $result['class'],
                'CATEGORIES' => stripslashes($result['categories']),
                'STUDIP_CATEGORY' => $result['category_intern'],
                'PRIORITY' => $result['priority'],
                'LOCATION' => stripslashes($result['location']),
                'RRULE' => array(
                    'rtype' => $result['rtype'],
                    'linterval' => $result['linterval'],
                    'sinterval' => $result['sinterval'],
                    'wdays' => $result['wdays'],
                    'month' => $result['month'],
                    'day' => $result['day'],
                    'expire' => $result['expire'],
                    'duration' => $result['duration'],
                    'count' => $result['count'],
                    'ts' => $result['ts']),
                'EXDATE' => $result['exceptions'],
                'CREATED' => $result['mkdate'],
                'LAST-MODIFIED' => $result['chdate'],
                'STUDIP_ID' => $result['event_id'],
                'DTSTAMP' => time(),
                'EVENT_TYPE' => 'cal',
                'STUDIP_AUTHOR_ID' => $result['autor_id'],
                'STUDIP_EDITOR_ID' => $result['editor_id']);

            $this->count();

            return $properties;
        } elseif (is_array($this->result['semcal']) && (list(, $result) = each($this->result['semcal']))) {
            $this->object_type = 'semcal';
            $properties = array(
                'DTSTART' => $result['start'],
                'DTEND' => $result['end'],
                'SUMMARY' => stripslashes($result['summary']),
                'DESCRIPTION' => stripslashes($result['description']),
                'LOCATION' => stripslashes($result['location']),
                'STUDIP_CATEGORY' => $result['category_intern'],
                'CREATED' => $result['mkdate'],
                'LAST-MODIFIED' => $result['chdate'],
                'STUDIP_ID' => $result['event_id'],
                'SEMNAME' => stripslashes($result['Name']),
                'SEM_ID' => $result['range_id'],
                'CLASS' => 'CONFIDENTIAL',
                'CATEGORIES' => stripslashes($result['categories']),
                'UID' => SeminarEvent::createUid($result['event_id']),
                'RRULE' => array(
                    'rtype' => $result['rtype'],
                    'linterval' => $result['linterval'],
                    'sinterval' => $result['sinterval'],
                    'wdays' => $result['wdays'],
                    'month' => $result['month'],
                    'day' => $result['day'],
                    'expire' => $result['expire'],
                    'duration' => $result['duration'],
                    'count' => $result['count'],
                    'ts' => $result['ts']),
                'EXDATE' => $result['exceptions'],
                'CREATED' => $result['mkdate'],
                'LAST-MODIFIED' => $result['chdate'],
                'DTSTAMP' => time(),
                'EVENT_TYPE' => 'semcal',
                'STUDIP_AUTHOR_ID' => $result['autor_id'],
                'STUDIP_EDITOR_ID' => $result['editor_id']);

            if ($this->perm->have_studip_perm('autor', $properties['SEM_ID'], $this->user_id)) {
                $properties['CLASS'] = 'PRIVATE';
            }

            $this->count();
            return $properties;
        } elseif (is_array($this->result['sem']) && (list(, $result) = each($this->result['sem']))) {
            while (true) {
                if ($result['status'] === 'dozent') {
                    //wenn ich Dozent bin, zeige den Termin nur, wenn ich durchf�hrender Dozent bin:
                    $termin = new SingleDate($result['termin_id']);
                    if (!in_array($this->user_id, $termin->getRelatedPersons())) {
                        if ((list(, $result) = each($this->result['sem']))) {
                            continue;
                        } else {
                            $this->object_type = 'cal';
                            return false;
                        }
                    }
                }
                break;
            }
            $this->object_type = 'sem';
            $properties = array(
                'DTSTART' => $result['date'],
                'DTEND' => $result['end_time'],
                'SUMMARY' => $result['ex_termin'] ? _("f�llt aus") : $result['title'],
                'DESCRIPTION' => $result['ex_termin'] ? $result['content'] : $result['description'],
                'LOCATION' => $result['raum'],
                'STUDIP_CATEGORY' => $result['date_typ'],
                'CREATED' => $result['mkdate'],
                'LAST-MODIFIED' => $result['chdate'],
                'STUDIP_ID' => $result['termin_id'],
                'SEM_ID' => $result['range_id'],
                'SEMNAME' => $result['Name'],
                'CLASS' => 'CONFIDENTIAL',
                'UID' => SeminarEvent::createUid($result['termin_id']),
                'RRULE' => SeminarEvent::createRepeat(),
                'EVENT_TYPE' => 'sem',
                'STATUS' => $result['ex_termin'] ? 'CANCELLED' : 'CONFIRMED',
                'DTSTAMP' => time());

            if ($result['status']) {
                $properties['CLASS'] = 'PRIVATE';
            }

            if (Config::get()->RESOURCES_ENABLE && $result['resource_id']) {
                $resObj = ResourceObject::Factory($result['resource_id']);
                $properties['LOCATION'] = $resObj->getName();
            }
            $this->count();
            return $properties;
        } else {
            $this->object_type = 'cal';
        }

        return FALSE;
    }

    function &nextObject()
    {
        if ($this->mod != 'EVENTS') {
            return FALSE;
        }
        if ($properties = $this->nextProperties()) {
            if ($this->object_type == 'semcal') {
                $event = new SeminarCalendarEvent($properties, $properties['STUDIP_ID'], $properties['SEM_ID']);
            } elseif ($this->object_type == 'sem') {
                $event = new SeminarEvent($properties['STUDIP_ID'], $properties, $this->result['sem']['range_id']);
                if ($this->result['sem']['status'] == 'tutor' || $this->result['sem']['status'] == 'dozent') {
                    $event->setWritePermission(TRUE);
                }
            } else {
                $event = new CalendarEvent($properties, $this->result['cal']['event_id'],
                                $this->result['cal']['range_id'], $this->permission);
                $event->setImportDate($this->result['cal']['importdate']);
                $event->editor = $this->result['cal']['editor_id'];
            }

            return $event;
        }

        return FALSE;
    }

    // this method is optimized for getting a single event
    function openDatabaseGetSingleObject($event_id, $event_type = 'CALENDAR_EVENTS')
    {
        $this->mod = 'EVENTS';
        $this->result = array();
        if ($event_type == 'CALENDAR_EVENTS') {
            $db_cal = DBManager::get()->prepare("SELECT * FROM calendar_events WHERE range_id = ? AND event_id = ?");
            $db_cal->execute(array($this->range_id, $event_id));
            $this->result['cal'] = $db_cal->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($event_type == 'SEMINAR_CALENDAR_EVENTS') {
            $db_semcal = DBManager::get()->prepare("SELECT ce.*, s.Name "
                    . "FROM calendar_events ce LEFT JOIN seminar_user su ON (su.Seminar_id=ce.range_id) "
                    . "LEFT JOIN seminare s USING(Seminar_id) WHERE "
                    . "event_id = ? AND user_id = ?");
            $db_semcal->execute(array($event_id, $this->range_id));
            $this->result['semcal'] = $db_semcal->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($event_type == 'SEMINAR_EVENTS') {
            $db_sem = DBManager::get()->prepare("SELECT t.*, s.Name, su.status, resource_id, GROUP_CONCAT(th.title SEPARATOR '; ') as title, GROUP_CONCAT(th.description SEPARATOR '\n\n') as description "
                    . "FROM (SELECT termin_id,range_id,date,end_time,mkdate,chdate,date_typ,content, 0 as ex_termin
                        FROM termine WHERE termin_id = ?
                        UNION SELECT termin_id,range_id,date,end_time,mkdate,chdate,date_typ,content, 1 as ex_termin
                        FROM ex_termine WHERE content <> '' AND termin_id = ?) as t
                       LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen as th USING (issue_id)
                       LEFT JOIN seminar_user su ON (su.Seminar_id=t.range_id) "
                    . "LEFT JOIN seminare s ON (s.Seminar_id=t.range_id) "
                    . "LEFT JOIN resources_assign ON (assign_user_id = termin_id) WHERE "
                    . "user_id = ? GROUP BY termin_id ORDER BY NULL");
            $db_sem->execute(array($event_id,$event_id, $this->user_id));
            $this->result['sem'] = $db_sem->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $this->mod = '';
        }
    }

    // depricated
    function openDatabaseGetView(&$view, $event_type = 'CALENDAR_EVENTS')
    {
        $this->result = array();
        $calendar_view = strtolower(get_class($view));
        switch ($calendar_view) {
            case 'dbcalendarday' :
                $this->mod = 'EVENTS';
                $db_cal = DBManager::get()->prepare("SELECT * FROM calendar_events WHERE range_id='" . $this->range_id
                        . "' AND ((start BETWEEN " . $view->getStart() . " AND " . $view->getEnd()
                        . " OR end BETWEEN " . $view->getStart() . " AND " . $view->getEnd()
                        . ") OR (" . $view->getStart() . " BETWEEN start AND end) OR "
                        . "(start <= " . $view->getEnd() . " AND expire > " . $view->getStart()
                        . " AND (rtype = 'DAILY' OR (rtype = 'WEEKLY' AND wdays LIKE '%"
                        . $view->getDayOfWeek() . "%') OR (rtype = 'MONTHLY' AND (wdays LIKE '%"
                        . $view->getDayOfWeek() . "%' OR day = " . $view->getValue()
                        . ")) OR (rtype = 'YEARLY' AND (month = " . $view->getMonth()
                        . " AND (day = " . $view->getValue() . " OR wdays LIKE '%"
                        . $view->getDayOfWeek() . "%'))) OR duration > 0)))");
                $db_cal->execute(array($this->range_id, $view->getStart(), $view->getEnd(), $view->getStart(), $view->getEnd(), $view->getStart(), $view->getEnd(), $view->getStart(), $view->getDayOfWeek(), $view->getDayOfWeek(), $view->getValue(), $view->getMonth(), $view->getValue(), $view->getDayOfWeek()));
                $this->result['cal'] = $db_cal->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'dbcalendarmonth' :
                $this->mod = 'EVENTS';
                $db_cal = DBManager::get()->prepare("SELECT * FROM calendar_events "
                        . "WHERE range_id = ? AND (start BETWEEN ? AND ? OR "
                        . "(start <= ? AND expire > ? AND rtype != 'SINGLE') OR (? BETWEEN start AND end))"
                        . " ORDER BY start ASC");
                $db_cal->execute(array($this->range_id, $view->getStart(), $view->getEnd(), $view->getEnd(), $view->getStart(), $view->getStart()));
                $this->result['cal'] = $db_cal->fetchAll(PDO::FETCH_ASSOC);
                break;

            default:
                $this->openDatabase('EVENTS', $event_type, $view->getStart(), $view->getEnd());
        }
    }

    // sets the import date to the current date
    function writeIntoDatabase($properties, $mode = 'REPLACE')
    {
        global $user;

        if (!sizeof($properties))
            return FALSE;

        if ($mode == 'INSERT_IGNORE')
            $query = "INSERT IGNORE INTO";
        elseif ($mode == 'INSERT')
            $query = "INSERT INTO";
        elseif ($mode == 'REPLACE')
            $query = "REPLACE";

        $query = 'INSERT INTO calendar_events VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $query .= ' ON DUPLICATE KEY UPDATE range_id = VALUES(range_id), autor_id = VALUES(autor_id), editor_id = VALUES(editor_id), uid = VALUES(uid), start = VALUES(start), end = VALUES(end), summary = VALUES(summary), description = VALUES(description), class = VALUES(class) , categories = VALUES(categories), category_intern = VALUES(category_intern), priority = VALUES(priority), location = VALUES(location), ts = VALUES(ts), linterval = VALUES(linterval), sinterval = VALUES(sinterval), wdays = VALUES(wdays), month = VALUES(month), day = VALUES(day), rtype = VALUES(rtype), duration = VALUES(duration), count = VALUES(count), expire = VALUES(expire), exceptions = VALUES(exceptions), mkdate=VALUES(mkdate), chdate = VALUES(chdate), importdate = VALUES(importdate)';

        $db_cal = DBManager::get()->prepare($query);

        foreach ($properties as $property_set) {

            if ($property_set['ID'] == '')
                $id = CalendarEvent::createUniqueId();
            else
                $id = $property_set['ID'];

            if (!$property_set['STUDIP_AUTHOR_ID']) {
                $property_set['STUDIP_AUTHOR_ID'] = $user->id;
            }

            $db_cal->execute(array(
                $id,
                $this->range_id,
                $property_set['STUDIP_AUTHOR_ID'],
                $user->id,
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
                time()));

            $this->count();
        }
    }

    function writeObjectsIntoDatabase($objects, $mode = 'REPLACE')
    {
        global $user;

        if (is_object($objects))
            $objects = array($objects);

        if (!sizeof($objects))
            return FALSE;

        if ($mode == 'INSERT_IGNORE')
            $query = "INSERT IGNORE INTO";
        elseif ($mode == 'INSERT')
            $query = "INSERT INTO";
        elseif ($mode == 'REPLACE')
            $query = "REPLACE";

        $query = 'INSERT INTO calendar_events VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $query .= ' ON DUPLICATE KEY UPDATE range_id = VALUES(range_id), autor_id = VALUES(autor_id), editor_id = VALUES(editor_id), uid = VALUES(uid), start = VALUES(start), end = VALUES(end), summary = VALUES(summary), description = VALUES(description), class = VALUES(class) , categories = VALUES(categories), category_intern = VALUES(category_intern), priority = VALUES(priority), location = VALUES(location), ts = VALUES(ts), linterval = VALUES(linterval), sinterval = VALUES(sinterval), wdays = VALUES(wdays), month = VALUES(month), day = VALUES(day), rtype = VALUES(rtype), duration = VALUES(duration), count = VALUES(count), expire = VALUES(expire), exceptions = VALUES(exceptions), mkdate=VALUES(mkdate), chdate = VALUES(chdate), importdate = VALUES(importdate)';

        $db_cal = DBManager::get()->prepare($query);

        foreach ($objects as $object) {
            if (strtolower(get_class($object)) == 'seminarevent') {
                continue;
            }

            if ($object->properties['STUDIP_AUTHOR_ID']) {
                $author_id = $object->properties['STUDIP_AUTHOR_ID'];
            } else {
                $author_id = $user->id;
            }

            $db_cal->execute(array(
                $object->getId(),
                $this->range_id,
                $author_id,
                $user->id,
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
                $object->getImportDate()));

            $this->count();
        }
        return TRUE;
    }

    function getCountEvents()
    {

        if ($this->mod != 'COUNT') {
            return FALSE;
        }

        $count = 0;
        if (is_array($this->result['cal'])) {
            $count = $this->result['cal']['cnt'];
        }
        if (is_array($this->result['sem'])) {
            $count += $this->result['sem']['cnt'];
        }
        return $count;
    }

    function deleteFromDatabase($mod, $event_ids = NULL, $start = 0, $end = Calendar::CALENDAR_END, $range_id = '')
    {

        if ($range_id == '') {
            $range_id = $this->range_id;
        }

        $db = DBManager::get();
        $query = "DELETE FROM calendar_events WHERE range_id = ?";
        switch ($mod) {
            case 'ALL':
                $stmt = $db->prepare($query);
                $stmt->execute(array($range_id));
                break;

            case 'EXPIRED':
                $query .= " AND (expire < $end OR (rtype = 'SINGLE' AND end < $end))";
                $query .= " AND chdate < $end";
                $stmt = $db->prepare($query);
                $stmt->execute(array($range_id, $end, $end, $end));
                break;

            case 'SINGLE':
                if (is_array($event_ids)) {
                    $event_ids = implode("','", $event_ids);
                    $query .= " AND event_id IN ('$event_ids')";
                    $stmt = $db->prepare($query);
                    $stmt->execute(array($range_id));
                } else {
                    $query .= " AND event_id = '$event_ids'";
                    $stmt = $db->prepare($query);
                $stmt->execute(array($range_id, $event_ids));
                }
                break;

            case 'KILL':
                $stmt = $db->prepare('DELETE FROM calendar_sync WHERE range_id = ?');
                $stmt->execute(array($range_id));
                break;
        }

        if ($rows = $stmt->rowCount()) {
            return $rows;
        }

        return FALSE;
    }

    function deleteObjectsFromDatabase($objects)
    {
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

    private function count()
    {

        $this->count++;
    }

    public function getCount()
    {

        return $this->count;
    }
}
