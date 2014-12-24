<?php
/**
 * EventRange.class.php - model class for table calendar_event
 *
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
 * @property string event_id database column
 * @property string range_id database column
 * @property string group_status database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string type computed column
 * @property string name computed column
 * @property string id computed column read/write
 * @property user user belongs_to User
 * @property course course belongs_to Course
 * @property institute institute belongs_to Institute
 * @property event belongs_to CalendarEvent
 */
class CalendarEvent extends SimpleORMap implements Event
{
    private $properties = null;
    private $permission_user_id = null;
    
    protected static function configure($config = array())
    {
        $config['db_table'] = 'calendar_event';
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'range_id',
        );
        $config['belongs_to']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'range_id',
        );
        $config['belongs_to']['institute'] = array(
            'class_name' => 'Institute',
            'foreign_key' => 'range_id',
        );
        $config['belongs_to']['event'] = array(
            'class_name' => 'EventData',
            'foreign_key' => 'event_id',
            'on_delete' => 'delete'
        );
        $config['additional_fields']['type'] = true;
        $config['additional_fields']['name'] = true;
        
        parent::configure($config);
    }

    /**
     * Returns a list of all categories the event belongs to.
     * Returns an empty string if no permission.
     *
     * @return string All categories as list.
     */
    public function toStringCategories()
    {
        global $PERS_TERMIN_KAT;

        $category_list = '';
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            if ($this->categories) {
                $category_list =
                        implode(', ', array_map('trim', explode(',', $this->categories)));
            }
            if ($this->category_intern) {
                $category_list = $PERS_TERMIN_KAT[$this->category_intern]['name']
                        . ', ' . $category_list;
            }
        }
        return $category_list;
    }
    
    /**
     * 
     * TODO should throw an exception if input values are wrong
     * 
     * @param type $r_rule
     * @param type $start
     * @param type $end
     * @return array The values of the recurrence rule.
     */
    public static function createRepeat($r_rule, $start, $end)
    {
        $duration = (int) ((mktime(12, 0, 0, date('n', $end), date('j', $end), date('Y', $end))
                - mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start)))
                / 86400);
        if (!isset($r_rule['count'])) {
            $r_rule['count'] = 0;
        }
        // Hier wird auch der 'genormte Timestamp' (immer 12.00 Uhr, ohne Sommerzeit) ts berechnet.
        switch ($r_rule['rtype']) {

            // ts ist hier der Tag des Termins 12:00:00 Uhr
            case 'SINGLE':
                $ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start));
                $rrule = array($ts, 0, 0, '', 0, 0, 'SINGLE', $duration);
                break;

            case 'DAILY':
                $r_rule['linterval'] = $r_rule['linterval'] ? intval($r_rule['linterval']) : 1;
                // ts ist hier der Tag des ersten Wiederholungstermins 12:00:00 Uhr
                $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) + $r_rule['linterval'], date('Y', $start));
                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $start), date('j', $start)
                            + ($r_rule['count'] - 1) * $r_rule['linterval'], date('Y', $start));
                }
                $rrule = array($ts, $r_rule['linterval'], 0, '', 0, 0, 'DAILY', $duration);
                break;

            case 'WEEKLY':
                $r_rule['linterval'] = $r_rule['linterval'] ? intval($r_rule['linterval']) : 1;
                // ts ist hier der Montag der ersten Wiederholungswoche 12:00:00 Uhr
                if (!$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) +
                            ($r_rule['linterval'] * 7 - (strftime('%u', $start) - 1)), date('Y', $start));
                    if ($r_rule['count']) {
                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $start), date('j', $start) +
                                ($r_rule['linterval'] * 7 * ($r_rule['count'] - 1)), date('Y', $start));
                    }
                    $rrule = array($ts, $r_rule['linterval'], 0, strftime('%u', $start), 0, 0, 'WEEKLY', $duration);
                } else {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) + (7 - (strftime('%u', $start) - 1))
                            - ((strftime('%u', $start) <= substr($r_rule['wdays'], -1)) ? 7 : 0), date('Y', $start));

                    if ($r_rule['count']) {
                        // last week day of the recurrence set
                        $set_start_wday = false;
                        $wdays = array(0);
                        for ($i = 0; $i < strlen($r_rule['wdays']); $i++) {
                            $wdays[] = $r_rule['wdays']{$i};
                            if (!$set_start_wday && intval($r_rule['wdays']{$i}) >= intval(strftime('%u', $start))) {
                                $start_wday = $r_rule['wdays']{$i};
                                $set_start_wday = true;
                            }
                        }
                        if (intval(strftime('%u', $start)) > intval(substr($r_rule['wdays'], -1))) {
                            $start_wday = $r_rule['wdays']{0};
                        }
                        $expire_ts = $ts + ((($r_rule['count'] % (count($wdays) - 1)) >= 1) ? (($start_wday - 1) * 86400) : 0)
                                + floor($r_rule['count'] / (count($wdays) - 1)) * 604800 * $r_rule['linterval'];

                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $expire_ts), date('j', $expire_ts), date('Y', $expire_ts));
                    }
                    $rrule = array($ts, $r_rule['linterval'], 0, $r_rule['wdays'], 0, 0, 'WEEKLY', $duration);
                }
                break;

            case 'MONTHLY':
                if ($r_rule['month']) {
                    return false;
                }
                $r_rule['linterval'] = $r_rule['linterval'] ? intval($r_rule['linterval']) : 1;
                if (!$r_rule['day'] && !$r_rule['sinterval'] && !$r_rule['wdays']) {
                    $amonth = date('n', $start) + $r_rule['linterval'];
                    $ts = mktime(12, 0, 0, $amonth, date('j', $start), date('Y', $start));
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0, date('j', $start), 'MONTHLY', $duration);
                } else if (!$r_rule['sinterval'] && !$r_rule['wdays']) {
                    // Ist erste Wiederholung schon im gleichen Monat?
                    if ($r_rule['day'] < date('j', $start)) {
                        $amonth = date('n', $start) + $r_rule['linterval'];
                    } else {
                        $amonth = date('n', $start);
                    }
                    $ts = mktime(12, 0, 0, $amonth, $r_rule['day'], date('Y', $start));
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0, $r_rule['day'], 'MONTHLY', $duration);
                } elseif (!$r_rule['day']) {
                    // hier ist ts der erste Wiederholungstermin
                    $amonth = date('n', $start);
                    $adate = mktime(12, 0, 0, $amonth, 1, date('Y', $start)) + ($r_rule['sinterval'] - 1) * 604800;
                    $awday = strftime('%u', $adate);
                    $adate -= ( $awday - $r_rule['wdays']) * 86400;
                    if ($r_rule['sinterval'] == 5) {
                        if (date('j', $adate) < 10) {
                            $adate -= 604800;
                        }
                        if (date('n', $adate) == date('n', $adate + 604800)) {
                            $adate += 604800;
                        }
                    } elseif ($awday > $r_rule['wdays']) {
                        $adate += 604800;
                    }
                    // Ist erste Wiederholung schon im gleichen Monat?
                    if (date('Ymd', $adate) < date('Ymd', $start)) {
                        //Dann muss hier die Berechnung ohne interval wiederholt werden
                        $amonth = date('n', $start) + $r_rule['linterval'];
                        $adate = mktime(12, 0, 0, $amonth, 1, date('Y', $start)) + ($r_rule['sinterval'] - 1) * 604800;
                        $awday = strftime('%u', $adate);
                        $adate -= ( $awday - $r_rule['wdays']) * 86400;
                        if ($r_rule['sinterval'] == 5) {
                            if (date('j', $adate) < 10) {
                                $adate -= 604800;
                            }
                            if (date('n', $adate) == date('n', $adate + 604800)) {
                                $adate += 604800;
                            }
                        }
                        else if ($awday > $r_rule['wdays']) {
                            $adate += 604800;
                        }
                    }
                    $ts = $adate;
                    $rrule = array($ts, $r_rule['linterval'], $r_rule['sinterval'], $r_rule['wdays'], 0, 0, 'MONTHLY', $duration);
                }

                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $ts) + $r_rule['linterval']
                            * ($r_rule['count'] - 1), date('j', $ts), date('Y', $ts));
                }
                break;

            case 'YEARLY':
                // ts ist hier der erste Wiederholungstermin 12:00:00 Uhr
                if (!$r_rule['month'] && !$r_rule['day'] && !$r_rule['sinterval'] && !$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start) + 1);
                    $rrule = array($ts, 1, 0, '', date('n', $start), date('j', $start), 'YEARLY', $duration);
                } else if (!$r_rule['sinterval'] && !$r_rule['wdays']) {
                    if (!$r_rule['day']) {
                        $r_rule['day'] = date('j', $start);
                    }
                    $ts = mktime(12, 0, 0, $r_rule['month'], $r_rule['day'], date('Y', $start));
                    if ($ts <= mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start))) {
                        $ts = mktime(12, 0, 0, $r_rule['month'], $r_rule['day'], date('Y', $start) + 1);
                    }
                    $rrule = array($ts, 1, 0, '', $r_rule['month'], $r_rule['day'], 'YEARLY', $duration);
                } else if (!$r_rule['day']) {
                    $ayear = date('Y', $start);
                    do {
                        $adate = mktime(12, 0, 0, $r_rule['month'], 1 + ($r_rule['sinterval'] - 1) * 7, $ayear);
                        $aday = strftime('%u', $adate);
                        $adate -= ( $aday - $r_rule['wdays']) * 86400;
                        if ($r_rule['sinterval'] == 5) {
                            if (date('j', $adate) < 10) {
                                $adate -= 604800;
                            }
                            if (date('n', $adate) == date('n', $adate + 604800)) {
                                $adate += 604800;
                            }
                        } else if ($aday > $r_rule['wdays']) {
                            $adate += 604800;
                        }
                        $ts = $adate;
                        $ayear++;
                    } while ($ts <= mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start)));
                    $rrule = array($ts, 1, $r_rule['sinterval'], $r_rule['wdays'], $r_rule['month'], 0, 'YEARLY', $duration);
                }

                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $ts), date('j', $ts), date('Y', $ts) + $r_rule['count'] - 1);
                }
                break;

            default :
                $ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start));
                $rrule = array($ts, 0, 0, '', 0, 0, 'SINGLE', $duration);
                $r_rule['count'] = 0;
        }

        if (!$r_rule['expire'] || $r_rule['expire'] > Calendar::CALENDAR_END) {
            $r_rule['expire'] = Calendar::CALENDAR_END;
        }

        return array(
            'ts' => $rrule[0],
            'linterval' => $rrule[1],
            'sinterval' => $rrule[2],
            'wdays' => $rrule[3],
            'month' => $rrule[4],
            'day' => $rrule[5],
            'rtype' => $rrule[6],
            'duration' => $rrule[7],
            'count' => $r_rule['count'],
            'expire' => $r_rule['expire']);
    }
    
    /**
     * 
     * @param string $index
     * @return string|array
     * @throws InvalidArgumentException
     */
    public function getRepeat($index = null)
    {
        if ($index) {
            if (in_array($index, array('ts', 'linterval',  'sinterval', 'wdays',
                'month', 'day', 'rtype', 'duration', 'count', 'expire'))) {
                return $this->event->$index;
            } else {
                throw new InvalidArgumentException('CalendarEvent::getRepeat '
                        . $index . ' is no field in recurrence rule.');
            }
        }
        return array(
            'ts' => $this->event->ts,
            'linterval' => $this->event->linterval,
            'sinterval' => $this->event->sinterval,
            'wdays' => $this->event->wdays,
            'month' => $this->event->month,
            'day' => $this->event->day,
            'rtype' => $this->event->rtype,
            'duration' => $this->event->duration,
            'count' => $this->event->count,
            'expire' => $this->event->expire
        );
    }
    
    /**
     * Returns the title of this event.
     * If the user has not the permission Event::PERMISSION_READABLE,
     * the title is "Keine Berechtigung.".
     * 
     * @return string 
     */
    public function getTitle()
    {
        if (!$this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            return _('Keine Berechtigung.');
        }
        if ($this->event->summary == '') {
            return _('Kein Titel');
        }
        return $this->event->summary;
    }
    
    /**
     * Returns the starttime as unix timestamp of this event.
     *
     * @return int The starttime of this event as a unix timestamp
     */
    public function getStart()
    {
        return $this->event->start;
    }
    
    public function setStart($timestamp)
    {
        $this->event->start = $timestamp;
    }
    
    /**
     * Returns the endtime as unix timestamp of this event.
     *
     * @return int the endtime of this event as a unix timestamp
     */
    public function getEnd()
    {
        return $this->event->end;
    }
    
    public function setEnd($timestamp)
    {
        $this->event->end = $timestamp;
    }
    
    /**
     * Returns the duration of this event in seconds.
     *
     * @return int the duration of this event in seconds
     */
    function getDuration()
    {
        return $this->event->end - $this->event->start;
    }
    
    /**
     * Returns the location.
     * Without permission or the location is not set an empty string is returned.
     * 
     * @return string The location
     */
    public function getLocation()
    {
        $location = '';
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            if (trim($this->event->location) != '') {
                $location = $this->event->location;
            }
        }
        return $location;
    }
    
    /**
     * Returns the global uni id of this event.
     * 
     * @return string The global unique id.
     */
    public function getUid()
    {
        return 'Stud.IP-' . $this->event_id . '@' . $_SERVER['SERVER_NAME'];
    }
    
    /**
     * Returns the description of the topic.
     * If the user has no permission or the event has no topic
     * or the topics have no descritopn an empty string is returned.
     *
     * @return String the description
     */
    public function getDescription()
    {
        $description = '';
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            $description = trim($this->event->description);
        }
        return $description;
    }
    
    /**
     * Returns the index of the category.
     * If the user has no permission, 255 is returned.
     * 
     * @see config/config.inc.php $TERMIN_TYP
     * @return int The index of the category
     */
    public function getCategory()
    {
        global $PERS_TERMIN_KAT;

        $category = 0;
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            if ($this->event->category_intern) {
                $category = $this->event->category_intern;
            }

            $categories = array();
            foreach ($PERS_TERMIN_KAT as $category) {
                $categories[] = strtolower($category['name']);
            }

            $cat_event = explode(',', $this->event->categories);
            foreach ($cat_event as $category) {
                $index = array_search(strtolower(trim($category)), $categories);
                if ($index) {
                    $category = ++$index;
                }
            }
        } else {
            $category = 255;
        }
        return $category;
    }
    
    /**
     * Returns the index of the category.
     * If the user has no permission, 255 is returned.
     * 
     * @see config/config.inc.php $TERMIN_TYP
     * @return int The index of the category
     */
    public function getCategoryStyle($image_size = 'small')
    {
        global $PERS_TERMIN_KAT;

        $index = $this->getCategory();
        if ($index) {
            return array('image' => $image_size == 'small' ?
                        Assets::image_path("calendar/category{$index}_small.jpg") :
                        Assets::image_path("calendar/category{$index}.jpg"),
                'color' => $PERS_TERMIN_KAT[$index]['color']);
        }

        return array('image' => $image_size == 'small' ?
                    Assets::image_path("calendar/category1_small.jpg") :
                    Assets::image_path("calendar/category1.jpg"),
            'color' => $PERS_TERMIN_KAT[1]['color']);
    }
    
    /**
     * 
     * @return type
     */
    public function getEditorId()
    {
        return $this->event->editor_id;
    }
    
    /**
     * 
     * @return type
     */
    public function isDayEvent()
    {
        return (date('His', $this->getStart()) == '000000' &&
        (date('His', $this->getEnd()) == '235959'
        || date('His', $this->getEnd() - 1) == '235959'));
    }
    
    /**
     * 
     * @return string
     */
    public function getAccessibility()
    {
        if ($this->event->class) {
            return $this->event->class;
        }
        return 'CONFIDENTIAL';
    }
    
    /**
     * 
     * @return type
     */
    public function getChangeDate()
    {
        return $this->event->chdate;
    }
    
    /**
     * 
     */
    public function getImportDate()
    {
        return $this->event->importdate;
    }
    
    
    /**
     * 
     * TODO wird das noch benötigt?
     * 
     * @return type
     */
    public function getType()
    {
        return get_object_type($this->range_id, array('user', 'sem', 'inst', 'fak'));
    }

    
    /**
     * 
     * TODO remove! not used?
     * 
     * @return type
     */
    public function getName()
    {
        switch ($this->type) {
            case 'user':
                return $this->user->getFullname();
            case 'sem':
                return $this->course->name;
            case 'inst':
            case 'fak':
                return $this->institute->name;
            }
    }
    
    /**
     * Returns all properties of this event.
     * The name of the properties correspond to the properties of the
     * iCalendar calendar data exchange format. There are a few properties with
     * the suffix STUDIP_ which have no eqivalent in the iCalendar format.
     * 
     * DTSTART: The start date-time as unix timestamp.
     * DTEND: The end date-time as unix timestamp.
     * SUMMARY: The short description (title) that will be displayed in the views.
     * DESCRIPTION: The long description.
     * UID: The global unique id of this event.
     * CLASS:
     * CATEGORIES: A comma separated list of categories.
     * PRIORITY: The priority.
     * LOCATION: The location.
     * EXDATE: A comma separated list of unix timestamps.
     * CREATED: The creation date-time as unix timestamp.
     * LAST-MODIFIED: The date-time of last modification as unix timestamp.
     * DTSTAMP: The cration date-time of this instance of the event as unix
     * timestamp.
     * RRULE: All data for the recurrence rule for this event as array.
     * EVENT_TYPE:
     * 
     * 
     * @return array The properties of this event.
     */
    public function getProperties()
    {
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
                'LOCATION' => stripslashes($this->getLocation()),
                'RRULE' => array(
                    'rtype' => $this->event->rtype,
                    'linterval' => $this->event->linterval,
                    'sinterval' => $this->event->sinterval,
                    'wdays' => $this->event->wdays,
                    'month' => $this->event->month,
                    'day' => $this->event->day,
                    'expire' => $this->event->expire,
                    'duration' => $this->event->duration,
                    'count' => $this->event->count,
                    'ts' => $this->event->ts),
                'EXDATE' => (string) $this->event->exceptions,
                'CREATED' => $this->event->mkdate,
                'LAST-MODIFIED' => $this->event->chdate,
                'STUDIP_ID' => $this->event->getId(),
                'DTSTAMP' => time(),
                'EVENT_TYPE' => 'cal',
                'STUDIP_AUTHOR_ID' => $this->event->autor_id,
                'STUDIP_EDITOR_ID' => $this->event->editor_id);
        }
        return $this->properties;
    }
    
    /**
     * Returns the value of property with given name.
     * 
     * @param type $name See CalendarEvent::getProperties() for accepted values.
     * @return mixed The value of the property.
     * @throws InvalidArgumentException
     */
    public function getProperty($name)
    {
        if ($this->properties === null) {
            $this->getProperties();
        }
        
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }
        throw new InvalidArgumentException(get_class($this)
                . ': Property ' . $name . ' does not exist.');
    }
    
    /**
     * Returns all CalendarEvents in the given time range for the given range_id.
     * 
     * @param string $range_id Id of Stud.IP object from type user, course, inst
     * @param DateTime $start The start date time.
     * @param DateTime $end The end date time.
     * @return SimpleORMapCollection Collection of found CalendarEvents.
     */
    public static function getEventsByInterval($range_id, DateTime $start, DateTime $end)
    {
        $stmt = DBManager::get()->prepare('SELECT * FROM calendar_event '
                . 'INNER JOIN event_data USING(event_id) '
                . 'WHERE range_id = :range_id '
                . 'AND (start BETWEEN :start AND :end OR '
                . "(start <= :end AND (expire + end - start) >= :start AND rtype != 'SINGLE') "
                . 'OR (:start BETWEEN start AND end)) '
                . 'ORDER BY start ASC');
        $stmt->execute(array(
            ':range_id' => $range_id,
            ':start'    => $start->getTimestamp(),
            ':end'      => $end->getTimestamp()
        ));
        $i = 0;
        $event_collection = new SimpleORMapCollection();
        $event_collection->setClassName('Event');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $event_collection[$i] = new CalendarEvent();
            $event_collection[$i]->setData($row);
            $event_collection[$i]->setNew(false);
            $event = new EventData();
            $event->setData($row);
            $event->setNew(false);
            $event_collection[$i]->event = $event;
        }
        return $event_collection;
    }
    
    public function setPermissionUser($user_id)
    {
        $this->permission_user_id = $user_id;
    }
    
    public function havePermission($permission, $user_id = null)
    {
        $perm = $this->getPermission($user_id);
        return $perm >= $permission;
    }
    
    public function getPermission($user_id = null)
    {
        static $permissions = array();
        
        if (is_null($user_id)) {
            $user_id = $this->permission_user_id ?: $GLOBALS['user']->id;
        }
        
        if (!$permissions[$user_id][$this->event_id]) {
            if ($user_id == $this->event->autor_id) {
                $permissions[$user_id][$this->event_id] = Event::PERMISSION_WRITABLE;
            } else if ($user_id == $this->range_id) {
                $permissions[$user_id][$this->event_id] = Event::PERMISSION_READABLE;
            } else {
                switch ($this->type) {
                    case 'user':
                        $permissions[$user_id][$this->event_id] =
                            $this->getUserCalendarPermission($user_id);
                        break;
                    case 'course':
                        $permissions[$user_id][$this->event_id] =
                            $this->getCourseCalendarPermission($user_id);
                    case 'inst':
                    case 'fak':
                        $permissions[$user_id][$this->event_id] =
                            $this->getInstituteCalendarPermission($user_id);
                        break;
                    default:
                        $permissions[$user_id][$this->event_id] =
                            Event::PERMISSION_FORBIDDEN;
                }
            }
        }
        return $permissions[$user_id][$this->event_id];
    }
    
    private function getUserCalendarPermission($user_id)
    {
        $permission = Event::PERMISSION_FORBIDDEN;
        if ($this->user->id) {
            if ($user_id != $this->user->id) {
                $accessibility = $this->getAccessibility();
                if ($accessibility == 'PUBLIC') {
                    $permission = Event::PERMISSION_READABLE;
                }
                $stmt = DBManager::get()->prepare('SELECT calpermission FROM contacts '
                        . 'WHERE owner_id = ? AND user_id = ?');
                $stmt->execute(array($this->user->getId(), $user_id));
                $calperm = $stmt->fetchColumn();
                if ($calperm && $calperm > $permission) {
                    $permission = $calperm;
                }
            }
        }
        return $permission;
    }
    
    private function getCourseCalendarPermission($user_id)
    {
        global $perm;
        
        $permission = Event::PERMISSION_FORBIDDEN;
        if ($this->course->id) {
            $course_perm = $perm->get_studip_perm($this->course->id, $user_id);
            switch ($course_perm) {
                case 'user':
                case 'autor':
                    $permission = Event::PERMISSION_READABLE;
                    break;
                case 'tutor':
                case 'dozent':
                case 'admin':
                    $permission = Event::PERMISSION_WRITABLE;
                    break;
                default:
                    $permission = Event::PERMISSION_FORBIDDEN;
            }
        }
        return $permission;
    }
    
    private function getInstituteCalendarPermission($user_id)
    {
        global $perm;
        $permission = Event::PERMISSION_FORBIDDEN;
        if ($this->institute->id) {
            $institute_perm = $perm->get_studip_perm($this->institute->id, $user_id);
            switch ($institute_perm) {
                case 'user';
                case 'autor':
                    $permission = Event::PERMISSION_READABLE;
                    break;
                case 'tutor':
                case 'dozent':
                case 'admin':
                    $permission = Event::PERMISSION_WRITABLE;
                    break;
                default:
                    $permission = Event::PERMISSION_FORBIDDEN;
            }
        }
        return $permission;
    }
}
