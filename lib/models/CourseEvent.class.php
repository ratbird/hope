<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * 
 */

class CourseEvent extends CourseDate implements Event
{
    
    protected static function configure($config)
    {
        $config['alias_fields']['termin_id'] = 'event_id';
        $config['alias_fields']['date'] = 'start';
        $config['alias_fields']['end_time'] = 'end';
        $config['alias_fields']['raum'] = 'location';
        $config['alias_fields']['date_typ'] = 'category_intern';
        
        $config['additional_fields']['editor_id']['get'] = function ($date){
            return null;
        };
        $config['additional_fields']['uid']['get'] = function ($date){
            return 'Stud.IP-SEM-' . $date->getId()
                    . '@' . $_SERVER['SERVER_NAME'];
        };
        $config['additional_fields']['summary']['get'] = function ($date){
            return $date->course->name;
        };
        $config['additional_fields']['description']['get'] = function ($date){
            return '';
        };
        $config['additional_fields']['location']['get'] = function ($date){
            $date->getRoomName();
        };
        parent::configure($config);
    }
    
    /**
     * Returns all CalendarEvents in the given time range for the given range_id.
     * 
     * @param string $user_id Id of Stud.IP object from type user, course, inst
     * @param DateTime $start The start date time.
     * @param DateTime $end The end date time.
     * @return SimpleORMapCollection Collection of found CalendarEvents.
     */
    public static function getEventsByInterval($user_id, DateTime $start, dateTime $end)
    {
        $stmt = DBManager::get()->prepare('SELECT * FROM seminar_user '
                . 'INNER JOIN termine ON seminar_id = range_id '
                . 'WHERE user_id = :user_id '
                . 'AND bind_calendar = 1 '
                . 'AND date BETWEEN :start AND :end '
                . 'ORDER BY date ASC');
        $stmt->execute(array(
            ':user_id' => $user_id,
            ':start'   => $start->getTimestamp(),
            ':end'     => $end->getTimestamp()
        ));
        $i = 0;
        $event_collection = new SimpleORMapCollection();
        $event_collection->setClassName('Event');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $event_collection[$i] = new CourseEvent();
            $event_collection[$i]->setData($row);
            $event_collection[$i]->setNew(false);
        }
        return $event_collection;
    }
    
    /**
     * Returns the name of the category.
     *
     * @return string the name of the category
     */
    public function toStringCategories()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $GLOBALS['TERMIN_TYP'][$this->date_type]['name'];
        }
        return '';
    }
    
    /**
     * Returns the id of the related course
     * 
     * @return string The id of the related course.
     */
    public function getSeminarId()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->sem_id;
        }
        return null;
    }

    /**
     * Returns an array that represents the recurrence rule for this event.
     * If an index is given, returns only this field of the rule.
     * 
     * @return array|string The array with th recurrence rule or only one field.
     */
    public function getRepeat($index = null)
    {
        $rep = array('ts' => 0, 'linterval' => 0, 'sinterval' => 0, 'wdays' => '',
            'month' => 0, 'day' => 0, 'rtype' => 'SINGLE', 'duration' => 1);
        return $index ? $rep[$index] : $rep;
    }
    
    /**
     * Returns the name of the related course.
     * 
     * @return string The name of the related course.
     */
    public function getSemName()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->course->name;
        }
        return '';
    }
    
    /**
     * TODO Wird das noch benötigt?
     */
    public function getType()
    {
        return 1;
    }
    
    /**
     * Returns the title of this event.
     * The title of a course event is the name of the course or if a topic is
     * assigned, the title of this topic. If the user has not the permission
     * Event::PERMISSION_READABLE, the title is "Keine Berechtigung.".
     * 
     * @return string 
     */
    public function getTitle()
    {
        if (!$this->havePermission(Event::PERMISSION_READABLE)) {
            $title = _('Keine Berechtigung.');
        }
        if (sizeof($this->topics)) {
            $title = implode(', ', $this->topics->pluck('title'));
        } else {
            $title = $this->course->name;
        }
        return $title;
    }
    
    /**
     * Returns the starttime as unix timestamp of this event.
     *
     * @return int The starttime of this event as a unix timestamp.
     */
    public function getStart()
    {
        return $this->start;
    }
    
    /**
     * Returns the endtime of this event.
     *
     * @return int The endtime of this event as a unix timestamp.
     */
    public function getEnd()
    {
        return $this->end;
    }
    
    /**
     * Returns the duration of this event in seconds.
     *
     * @return int the duration of this event in seconds
     */
    function getDuration()
    {
        return $this->end - $this->start;
    }
    
    /**
     * Returns the location.
     * Without permission or the location is not set an empty string is returned.
     * 
     * @see ClendarDate::getRoomName()
     * @return string The location
     */
    function getLocation()
    {
        $location = '';
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            $location = $this->location;
        }
        return $location;
    }
    
    /**
     * Returns the global unique id of this event.
     * 
     * @return string The global unique id.
     */
    public function getUid()
    {
        return $this->uid;
    }
    
    /**
     * Returns the description of the topic.
     * If the user has no permission or the event has no topic
     * or the topics have no descritopn an empty string is returned.
     *
     * @return String the description
     */
    function getDescription()
    {
        $description = '';
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            if (sizof($this->topics)) {
                $description = implode(', ', $this->topics->pluck('description'));
            }
        }
        return $description;
    }
    
    /**
     * Returns the Stud.IP build in category as integer value.
     * If the user has no permission, 255 is returned.
     *
     * @See config.inc.php $PERS_TERMIN
     * @return int the categories
     */
    public function getStudipCategory()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->date_typ;
        }
        return 255;
    }
    
    /**
     * Returns the index of the category.
     * If the user has no permission, 255 is returned.
     * 
     * TODO remove? use getStudipCategory instead?
     * 
     * @see config/config.inc.php $TERMIN_TYP
     * @return int The index of the category
     */
    public function getCategory()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE)) {
            return $this->date_typ;
        }
        return 255;
    }
    
    /**
     * TODO remove, do this in template!
     */
    public function getCategoryStyle($image_size = 'small')
    {
        global $TERMIN_TYP, $PERS_TERMIN_KAT;

        $index = $this->getCategory();
        if ($index == 255) {
            return array('image' => $image_size == 'small' ?
                Assets::image_path('calendar/category' . $index . '_small.jpg') :
                Assets::image_path('calendar/category' . $index . '.jpg'),
                'color' => $PERS_TERMIN_KAT[$index]['color']);
        }

        return array('image' => $image_size == 'small' ?
            Assets::image_path('calendar/category_sem' . ($index) . '_small.jpg') :
            Assets::image_path('calendar/category_sem' . ($index) . '.jpg'),
            'color' => $TERMIN_TYP[$index]['color']);
    }
    
    /**
     * Returns the user id of the last editor.
     * Since course events have no editor null is returned.
     * 
     * @return null|int Returns always null.
     */
    public function getEditorId()
    {
        return null;
    }
    
    /**
     * Returns whether the event is a all day event.
     * 
     * @return 
     */
    public function isDayEvent()
    {
        return (($this->end - $this->start) / 60 / 60) > 23;
    }
    
    /**
     * Returns the accessibility of this event. The value is not influenced by
     * the permission of the actual user.
     * 
     * According to RFC5545 the accessibility (property CLASS) is represented
     * by the 3 state PUBLIC, PRIVATE and CONFIDENTIAL
     * 
     * TODO check this statement:
     * An course event is always CONFIDENTIAL 
     * 
     * @return string The accessibility as string.
     */
    public function getAccessibility()
    {
        return 'CONFIDENTIAL';
    }
    
    /**
     * Returns the unix timestamp of the last change.
     *
     * @access public
     */
    public function getChangeDate()
    {
        return $this->chdate;
    }
    
    /**
     * Returns the date time the event was imported.
     * Since course events are not imported normaly, returns the date time
     * of creation.
     * 
     * @return int Date time of import as unix timestamp:
     */
    public function getImportDate()
    {
        return $this->mkdate;
    }
    
    /**
     * Returns all related groups.
     * 
     * TODO remove, use direct access to field CourseDate::statusgruppen.
     * 
     * @return SimpleORMapCollection The collection of statusgruppen. 
     */
    public function getRelatedGroups()
    {
        return $this->statusgruppen;
    }
    
    public function getProperties()
    {
        
        $properties = array(
                'DTSTART' => $result['date'],
                'DTEND' => $result['end_time'],
                'SUMMARY' => $result['ex_termin'] ? _("fällt aus") : $result['title'],
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
        
        if ($this->properties === null) {
            $this->properties = array(
                'DTSTART' => $this->getStart(),
                'DTEND' => $this->getEnd(),
                'SUMMARY' => stripslashes($this->getTitle()),
                'DESCRIPTION' => stripslashes($this->getDescription()),
                'UID' => $this->getUid(),
                'CLASS' => $this->getAccessibility(),
                'CATEGORIES' => stripslashes($this->getCategory()),
                'STUDIP_CATEGORY' => $this->event->category_intern,
                'PRIORITY' => $this->event->priority,
                'LOCATION' => stripslashes($this->location),
                'RRULE' => array(
                    'rtype' => $this->rtype,
                    'linterval' => $this->linterval,
                    'sinterval' => $this->sinterval,
                    'wdays' => $this->wdays,
                    'month' => $this->month,
                    'day' => $this->day,
                    'expire' => $this->expire,
                    'duration' => $this->duration,
                    'count' => $this->count,
                    'ts' => $this->ts),
                'EXDATE' => (string) $this->exceptions,
                'CREATED' => $this->mkdate,
                'LAST-MODIFIED' => $this->chdate,
                'STUDIP_ID' => $this->event_id,
                'DTSTAMP' => time(),
                'EVENT_TYPE' => 'cal',
                'STUDIP_AUTHOR_ID' => $this->autor_id,
                'STUDIP_EDITOR_ID' => $this->editor_id);
        }
        return $this->properties;
    }
    
}