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
        $config['has_one']['event'] = array(
            'class_name' => 'EventData',
            'foreign_key' => 'event_id',
            'assoc_foreign_key' => 'event_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['additional_fields']['type'] = true;
        $config['additional_fields']['name'] = true;
        $config['additional_fields']['author_id'] = true;
        $config['additional_fields']['editor_id'] = true;
        $config['additional_fields']['title'] = true;
        $config['additional_fields']['start'] = true;
        $config['additional_fields']['end'] = true;

        parent::configure($config);
    }

    public static function deleteBySQL($where, $params = array())
    {
        $ret = parent::deleteBySQL($where, $params);
        EventData::garbageCollect();
        return $ret;
    }

    /**
     * Finds calendar events by the uid of the event data.
     *
     * @param string $uid The global unique id of this event.
     * @return null|CalendarEvent The calendar event, an array of calendar events or null.
     */
    public static function findByUid($uid, $range_id = null)
    {
        $event_data = EventData::findOneByuid($uid);
        if ($event_data) {
            if ($range_id) {
                return self::find(array($range_id, $event_data->getId()));
            }
            return self::findByevent_id($event_data->getId());
        }
        return null;
    }

    /**
     * Keeps the event data
     */
    public function __clone()
    {
        $event = clone $this->event;
        parent::__clone();
        $this->event = $event;
    }

    /**
     * Returns a list of all categories the event belongs to.
     * Returns an empty string if no permission.
     *
     * @return string All categories as list.
     */
    public function toStringCategories($as_array = false)
    {
        global $PERS_TERMIN_KAT;

        $categories = array();
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            if ($this->event->categories) {
                $categories = array_map('trim', explode(',', $this->event->categories));
            }
            if ($this->event->category_intern) {
                array_unshift($categories,
                        $PERS_TERMIN_KAT[$this->event->category_intern]['name']);
            }
        }
        return $as_array ? $categories : implode(', ', $categories);
    }

    /**
     * Returns all values that defines a recurrence rule or a single value
     * named by $index.
     *
     * @param string $index Name of the value to retrieve (optional).
     * @return string|array The value(s) of the recurrence rule.
     * @throws InvalidArgumentException
     */
    public function getRecurrence($index = null)
    {
        $recurrence = array(
            'ts' => $this->event->ts ?: mktime(12, 0, 0, date('n', $this->getStart()), date('j',
                $this->getStart()), date('Y', $this->getStart())),
            'linterval' => $this->event->linterval,
            'sinterval' => $this->event->sinterval,
            'wdays' => $this->event->wdays,
            'month' => $this->event->month,
            'day' => $this->event->day,
            'rtype' => $this->event->rtype ?: 'SINGLE',
            'duration' => $this->event->duration,
            'count' => $this->event->count,
            'expire' => $this->event->expire ?: Calendar::CALENDAR_END
        );
        if ($index) {
            if (in_array($index, array_keys($recurrence))) {
                return $recurrence[$index];
            } else {
                throw new InvalidArgumentException('CalendarEvent::getRecurrence '
                        . $index . ' is not a field in the recurrence rule.');
            }
        }
        return $recurrence;
    }

    /**
     *
     * TODO should throw an exception if input values are wrong
     *
     * @param array $r_rule
     * @return array The values of the recurrence rule.
     */
    function setRecurrence($r_rule)
    {
        $start = $this->getStart();
        $end = $this->getEnd();
        $duration = (int) ((mktime(12, 0, 0, date('n', $end),
                date('j', $end), date('Y', $end))
                - mktime(12, 0, 0, date('n', $start),
                        date('j', $start), date('Y', $start))) / 86400);
        if (!isset($r_rule['count'])) {
            $r_rule['count'] = 0;
        }

        switch ($r_rule['rtype']) {
            case 'SINGLE':
                $ts = mktime(12, 0, 0, date('n', $start),
                        date('j', $start), date('Y', $start));
                $rrule = array($ts, 0, 0, '', 0, 0, 'SINGLE', $duration);
                break;
            case 'DAILY':
                $r_rule['linterval'] = $r_rule['linterval'] ? intval($r_rule['linterval']) : 1;
                $ts = mktime(12, 0, 0, date('n', $start),
                        date('j', $start) + $r_rule['linterval'], date('Y', $start));
                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $start), date('j', $start)
                            + ($r_rule['count'] - 1) * $r_rule['linterval'], date('Y', $start));
                }
                $rrule = array($ts, $r_rule['linterval'], 0, '', 0, 0, 'DAILY', $duration);
                break;
            case 'WEEKLY':
                $r_rule['linterval'] = $r_rule['linterval'] ? intval($r_rule['linterval']) : 1;
                if (!$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start), date('j', $start) +
                            ($r_rule['linterval'] * 7 - (strftime('%u', $start) - 1)),
                            date('Y', $start));
                    if ($r_rule['count']) {
                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $start),
                                date('j', $start) + ($r_rule['linterval'] * 7 * ($r_rule['count'] - 1)),
                                date('Y', $start));
                    }
                    $rrule = array($ts, $r_rule['linterval'], 0, strftime('%u', $start),
                        0, 0, 'WEEKLY', $duration);
                } else {
                    $ts = mktime(12, 0, 0, date('n', $start),
                            date('j', $start) + (7 - (strftime('%u', $start) - 1))
                            - ((strftime('%u', $start) <= substr($r_rule['wdays'], -1)) ? 7 : 0),
                            date('Y', $start));

                    if ($r_rule['count']) {
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

                        $r_rule['expire'] = mktime(23, 59, 59, date('n', $expire_ts),
                                date('j', $expire_ts), date('Y', $expire_ts));
                    }
                    $rrule = array($ts, $r_rule['linterval'], 0, $r_rule['wdays'],
                        0, 0, 'WEEKLY', $duration);
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
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0,
                        date('j', $start), 'MONTHLY', $duration);
                } else if (!$r_rule['sinterval'] && !$r_rule['wdays']) {
                    if ($r_rule['day'] < date('j', $start)) {
                        $amonth = date('n', $start) + $r_rule['linterval'];
                    } else {
                        $amonth = date('n', $start);
                    }
                    $ts = mktime(12, 0, 0, $amonth, $r_rule['day'], date('Y', $start));
                    $rrule = array($ts, $r_rule['linterval'], 0, '', 0,
                        $r_rule['day'], 'MONTHLY', $duration);
                } else if (!$r_rule['day']) {
                    $amonth = date('n', $start);
                    $adate = mktime(12, 0, 0, $amonth, 1,
                            date('Y', $start)) + ($r_rule['sinterval'] - 1) * 604800;
                    $awday = strftime('%u', $adate);
                    $adate -= ( $awday - $r_rule['wdays']) * 86400;
                    if ($r_rule['sinterval'] == 5) {
                        if (date('j', $adate) < 10) {
                            $adate -= 604800;
                        }
                        if (date('n', $adate) == date('n', $adate + 604800)) {
                            $adate += 604800;
                        }
                    } else if ($awday > $r_rule['wdays']) {
                        $adate += 604800;
                    }
                    if (date('Ymd', $adate) < date('Ymd', $start)) {
                        $amonth = date('n', $start) + $r_rule['linterval'];
                        $adate = mktime(12, 0, 0, $amonth, 1,
                                date('Y', $start)) + ($r_rule['sinterval'] - 1) * 604800;
                        $awday = strftime('%u', $adate);
                        $adate -= ( $awday - $r_rule['wdays']) * 86400;
                        if ($r_rule['sinterval'] == 5) {
                            if (date('j', $adate) < 10) {
                                $adate -= 604800;
                            }
                            if (date('n', $adate) == date('n', $adate + 604800)) {
                                $adate += 604800;
                            }
                        } else if ($awday > $r_rule['wdays']) {
                            $adate += 604800;
                        }
                    }
                    $ts = $adate;
                    $rrule = array($ts, $r_rule['linterval'], $r_rule['sinterval'],
                        $r_rule['wdays'], 0, 0, 'MONTHLY', $duration);
                }

                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $ts) + $r_rule['linterval']
                            * ($r_rule['count'] - 1), date('j', $ts), date('Y', $ts));
                }
                break;
            case 'YEARLY':
                if (!$r_rule['month'] && !$r_rule['day'] && !$r_rule['sinterval'] && !$r_rule['wdays']) {
                    $ts = mktime(12, 0, 0, date('n', $start),
                            date('j', $start), date('Y', $start) + 1);
                    $rrule = array($ts, 1, 0, '', date('n', $start),
                        date('j', $start), 'YEARLY', $duration);
                } else if (!$r_rule['sinterval'] && !$r_rule['wdays']) {
                    if (!$r_rule['day']) {
                        $r_rule['day'] = date('j', $start);
                    }
                    $ts = mktime(12, 0, 0, $r_rule['month'], $r_rule['day'],
                            date('Y', $start));
                    if ($ts <= mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start))) {
                        $ts = mktime(12, 0, 0, $r_rule['month'], $r_rule['day'],
                                date('Y', $start) + 1);
                    }
                    $rrule = array($ts, 1, 0, '', $r_rule['month'],
                        $r_rule['day'], 'YEARLY', $duration);
                } else if (!$r_rule['day']) {
                    $ayear = date('Y', $start);
                    do {
                        $adate = mktime(12, 0, 0, $r_rule['month'],
                                1 + ($r_rule['sinterval'] - 1) * 7, $ayear);
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
                    $rrule = array($ts, 1, $r_rule['sinterval'], $r_rule['wdays'],
                        $r_rule['month'], 0, 'YEARLY', $duration);
                }

                if ($r_rule['count']) {
                    $r_rule['expire'] = mktime(23, 59, 59, date('n', $ts),
                            date('j', $ts), date('Y', $ts) + $r_rule['count'] - 1);
                }
                break;
            default :
                $ts = mktime(12, 0, 0, date('n', $start),
                        date('j', $start), date('Y', $start));
                $rrule = array($ts, 0, 0, '', 0, 0, 'SINGLE', $duration);
                $r_rule['count'] = 0;
        }

        if (!$r_rule['expire'] || $r_rule['expire'] > Calendar::CALENDAR_END) {
            $r_rule['expire'] = Calendar::CALENDAR_END;
        }
        $this->event->ts = $rrule[0];
        $this->event->linterval = $rrule[1];
        $this->event->sinterval = $rrule[2];
        $this->event->wdays = $rrule[3];
        $this->event->month = $rrule[4];
        $this->event->day = $rrule[5];
        $this->event->rtype = $rrule[6];
        $this->event->duration = $rrule[7];
        $this->event->count = $r_rule['count'];
        $this->event->expire = $r_rule['expire'];
    }

    /**
     * Returns a string representation of the recurrence rule.
     * If $only_type is true returns only the type of the recurrence.
     *
     * @param bool $only_type If true returns only the type of recurrence.
     * @return string The recurrence rule - human readable
     */
    public function toStringRecurrence($only_type = false)
    {
        $rrule = $this->getRecurrence();
        $replace = array(_('Montag') . ', ', _('Dienstag') . ', ', _('Mittwoch') . ', ',
            _('Donnerstag') . ', ', _('Freitag') . ', ', _('Samstag') . ', ', _('Sonntag') . ', ');
        $search = array('1', '2', '3', '4', '5', '6', '7');
        $wdays = str_replace($search, $replace, $rrule['wdays']);
        $wdays = substr($wdays, 0, -2);

        switch ($rrule['rtype']) {
            case 'DAILY':
                if ($rrule['linterval'] > 1) {
                        $type = 'xdaily';
                    $text = sprintf(_('Der Termin wird alle %s Tage wiederholt.'),
                            $rrule['linterval']);
                } else {
                    $type = 'daily';
                    $text = _('Der Termin wird t�glich wiederholt');
                }
                break;
            case 'WEEKLY':
                if ($rrule['linterval'] > 1) {
                    $type = 'xweek_wdaily';
                    $text = sprintf(_('Der Termin wird alle %s Wochen am %s wiederholt.'),
                            $rrule['linterval'], $wdays);
                } else {
                    if ($rrule['wdays'] = '12345') {
                        $type = 'workdaily';
                    } else {
                        $type = 'wdaily';
                    }
                    $text = sprintf(_('Der Termin wird jeden %s wiederholt.'), $wdays);
                }
                break;
            case 'MONTHLY':
                if ($rrule['linterval'] > 1) {
                    if ($rrule['day']) {
                        $type = 'mday_xmonthly';
                        $text = sprintf(_('Der Termin wird am %s. alle %s Monate wiederholt.'),
                                $rrule['day'], $rrule['linterval']);
                    } else {
                        if ($rrule['sinterval'] != '5') {
                            $type = 'xwday_xmonthly';
                            $text = sprintf(_('Der Termin wird jeden %s. %s alle %s Monate wiederholt.'),
                                    $rrule['sinterval'], $wdays, $rrule['linterval']);
                        } else {
                            $type = 'lastwday_xmonthly';
                            $text = sprintf(_('Der Termin wird jeden letzten %s alle %s Monate wiederholt.'),
                                    $wdays, $rrule['linterval']);
                        }
                    }
                } else {
                    if ($rrule['day']) {
                        $type = 'mday_monthly';
                        $text = sprintf(_('Der Termin wird am %s. jeden Monat wiederholt.'),
                                $rrule['day'], $rrule['linterval']);
                    } else {
                        if ($rrule['sinterval'] != '5') {
                            $type = 'xwday_monthly';
                            $text = sprintf(_('Der Termin wird am %s. %s jeden Monat wiederholt.'),
                                    $rrule['sinterval'], $wdays, $rrule['linterval']);
                        } else {
                            $type = 'lastwday_monthly';
                            $text = sprintf(_('Der Termin wird jeden letzten %s jeden Monat wiederholt.'),
                                    $wdays, $rrule['linterval']);
                        }
                    }
                }
                break;
            case 'YEARLY':
                $month_names = array(_('Januar'), _('Februar'), _('M�rz'), _('April'), _('Mai'),
                    _('Juni'), _('Juli'), _('August'), _('September'), _('Oktober'),
                    _('November'), _('Dezember'));
                if ($rrule['day']) {
                    $type = 'mday_month_yearly';
                    $text = sprintf(_('Der Termin wird jeden %s. %s wiederholt.'),
                            $rrule['day'], $month_names[$rrule['month'] - 1]);
                } else {
                    if ($rrule['sinterval'] != '5') {
                        $type = 'xwday_month_yearly';
                        $text = sprintf(_('Der Termin wird jeden %s. %s im %s wiederholt.'),
                                $rrule['sinterval'], $wdays, $month_names[$rrule['month'] - 1]);
                    } else {
                        $type = 'lastwday_month_yearly';
                        $text = sprintf(_('Der Termin wird jeden letzten %s im %s wiederholt.'),
                                $wdays, $month_names[$rrule['month'] - 1]);
                    }
                }
                break;
            default:
                $type = 'single';
                $text = _("Der Termin wird nicht wiederholt.");
        }
        return $only_type ? $type : $text;
    }

    /**
     * Returns the priority in a human readable form.
     * If the user has no permission an epmty string will be returned.
     *
     * @return string The priority as a string.
     */
    public function toStringPriority()
    {
        if (!$this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            return '';
        }
        switch ($this->event->priority) {
            case 1:
                return _("hoch");
            case 2:
                return _("mittel");
            case 3:
                return _("niedrig");
            default:
                return _("keine Angabe");
        }
    }

    /**
     * Returns the accessibilty in a human readable form.
     * If the user has no permission an epmty string will be returned.
     *
     * @return string The accessibility as string.
     */
    public function toStringAccessibility()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            switch ($this->event->class) {
                case 'PUBLIC':
                    return _('�ffentlich');
                case 'CONFIDENTIAL':
                    return _('vertraulich');
                default:
                    return _('privat');
            }
        }
        return '';
    }

    /**
     * Returns the exceptions as array of unix timestamps.
     *
     * @return array Array of unix timestamps.
     */
    public function getExceptions()
    {
        $exceptions = array();
        if (trim($this->event->exceptions)) {
            $exceptions = explode(',', $this->event->exceptions);
        }
        return $exceptions;
    }

    /**
     * Sets proper timestamps as exceptions for given unix timestamps.
     *
     * @param array $exceptions Array of exceptions as unix timestamps.
     */
    public function setExceptions($exceptions)
    {
        $exc = array();
        if (is_array($exceptions)) {
            $exc = array_map(function ($exception) {
                $exception = intval($exception);
                return mktime(12, 0, 0, date('n', $exception),
                        date('j', $exception), date('Y', $exception));
            }, $exceptions);
        }
        $this->event->exceptions = implode(',', $exc);
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
     * Sets the title of this event.
     *
     * @param type $title The title of this event.
     */
    public function setTitle($title)
    {
        $this->event->summary = $title;
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

    /**
     * Sets the start date time with given unix timestamp.
     *
     * @param string $timestamp Unix timestamp.
     */
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

    /**
     * Sets the end date time by given unix timestamp.
     *
     * @param string $timestamp Unix timestamp.
     */
    public function setEnd($timestamp)
    {
        $this->event->end = $timestamp;
    }

    /**
     * Returns the user id of the author.
     *
     * @return string User id of the author.
     */
    public function getAuthorId()
    {
        return $this->event->author_id;
    }

    /**
     * Sets the author by given user id.
     *
     * @param string $author_id User id of the author.
     */
    public function setAuthorId($author_id)
    {
        $this->event->author_id = $author_id;
    }

    /**
     * Sets the editor id by given user id.
     *
     * @param string $editor_id User id of the editor.
     */
    public function setEditorId($editor_id)
    {
        $this->event->editor_id = $editor_id;
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

            if ($category == 0 && trim($this->event->categories)) {
                $categories = array();
                $i = 1;
                foreach ($PERS_TERMIN_KAT as $pers_cat) {
                    $categories[strtolower($pers_cat['name'])] = $i++;
                }
                $cat_event = explode(',', $this->event->categories);
                foreach ($cat_event as $cat) {
                    $index = strtolower(trim($cat));
                    if ($categories[$index]) {
                        $category = $categories[$index];
                        break;
                    }
                }
            }
        } else {
            $category = 255;
        }
        return $category;
    }

    /**
     * Returns a csv list of categories. If no categories are stated or the user
     * has no permission an empty string will be returned.
     *
     * @return string csv list of categories or empty string
     */
    public function getUserDefinedCategories()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            return trim((string) $this->event->categories);
        }
        return '';
    }

    /**
     * Stores user defined categories as a csv list.
     *
     * @param array|string $categories An array or csv list of user defined categories.
     */
    public function setUserDefinedCategories($categories)
    {
        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }
        $cat_list = implode(',', array_map('trim', $categories));
        $this->event->categories = $cat_list;
    }

    /**
     * Sets the accessibility (class). Possible classes are 'PUBLIC', 'PRIVATE'
     * and 'CONFIDENTIAL'.
     * If the given class is unknown, the event gets the class 'PRIVATE'.
     *
     * @param string $class The name of the class.
     */
    public function setAccessibility($class)
    {
        $class = strtoupper($class);
        if (in_array($class, array('PUBLIC', 'PRIVATE', 'CONFIDENTIAL'))) {
            $this->event->class = $class;
        } else {
            $this->event->class = 'PRIVATE';
        }
    }

    /**
     * Sets the priority. Possible values are
     * 0: not specified
     * 1: high
     * 2: middle
     * 3: low
     * Default is 0.
     *
     * @param int $priority The priority between 0 and 3.
     */
    public function setPriority($priority)
    {
        if ($priority >= 0 && $priority < 4)
        {
            $this->event->priority = $priority;
        } else {
            $this->event->priority = 0;
        }
    }

    /**
     * Returns the user id of the editor.
     *
     * @return string User id of the editor
     */
    public function getEditorId()
    {
        return $this->event->editor_id;
    }

    /**
     * Returns whether this event is an all day event.
     *
     * @return boolean true if all day event
     */
    public function isDayEvent()
    {
        return (date('His', $this->getStart()) == '000000' &&
        (date('His', $this->getEnd()) == '235959'
        || date('His', $this->getEnd() - 1) == '235959'));
    }

    /**
     * Returns the state of accessibility as string.
     * Possible values:
     * PUBLIC, PRIVATE, CONFIDENTIAL
     * The default is CONFIDENTIAL.
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
     * Returns an array with options for accessibility depending on the permission
     * of the given calendar permission.
     *
     * @param int $permission The calendar permission
     * @return array The accessibility options.
     */
    public function getAccessibilityOptions($permission)
    {
        if ($this->getType() != 'user') {
            $options = array('PRIVATE' => _('privat'));
        } else {
            switch ($permission) {
                case Calendar::PERMISSION_OWN :
                case Calendar::PERMISSION_ADMIN :
                    $options = array(
                        'PUBLIC' => _('�ffentlich'),
                        'PRIVATE' => _('privat'),
                        'CONFIDENTIAL' => _('vertraulich')
                    );
                    break;
                case Calendar::PERMISSION_WRITABLE :
                    $options = array(
                        'PRIVATE' => _('privat'),
                        'CONFIDENTIAL' => _('vertraulich')
                    );
                    break;
                default :
                    $options = array();
            }
        }
        return $options;
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
     * Returns the object type this event belongs to.
     * Possible values are 'user', 'sem', 'inst', 'fak'.
     *
     * @return string The object type.
     */
    public function getType()
    {
        return get_object_type($this->range_id, array('user', 'sem', 'inst', 'fak'));
    }

    /**
     * Returns the priority:
     * 0 means priority is not stated
     * 1 means "high"
     * 2 means "middle"
     * 3 means "low"
     * If the user has no permission it returns 0.
     *
     * @return int The priority.
     */
    public function getPriority()
    {
        if ($this->havePermission(Event::PERMISSION_READABLE,
                $this->permission_user_id)) {
            return $this->event->priority ?: 0;
        }
        return 0;
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
                'CATEGORIES' => $this->getUserDefinedCategories(),
                'STUDIP_CATEGORY' => $this->getCategory(),
                'PRIORITY' => $this->getPriority(),
                'LOCATION' => stripslashes($this->getLocation()),
                'RRULE' => $this->getRecurrence(),
                'EXDATE' => (string) $this->event->exceptions,
                'CREATED' => $this->event->mkdate,
                'LAST-MODIFIED' => $this->event->chdate,
                'STUDIP_ID' => $this->event->getId(),
                'DTSTAMP' => time(),
                'EVENT_TYPE' => 'cal',
                'STUDIP_AUTHOR_ID' => $this->event->author_id,
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
        $event_collection = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $event_collection[$i] = new CalendarEvent();
            $event_collection[$i]->setData($row);
            $event_collection[$i]->setNew(false);
            $event = new EventData();
            $event->setData($row);
            $event->setNew(false);
            $event_collection[$i]->event = $event;
            $i++;
        }
        $event_collection = SimpleORMapCollection::createFromArray($event_collection, false);
        $event_collection->setClassName('Event');
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
            if ($user_id == $this->event->author_id) {
                $permissions[$user_id][$this->event_id] = Event::PERMISSION_WRITABLE;
            } else if ($user_id == $this->range_id) {
                $permissions[$user_id][$this->event_id] = Event::PERMISSION_WRITABLE;
            } else {
                switch ($this->getType()) {
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
        $accessibility = $this->getAccessibility();
        if ($this->user->id) {
            if ($user_id != $this->user->id) {
                if ($accessibility == 'PUBLIC') {
                    $permission = Event::PERMISSION_READABLE;
                }
                $calendar_user = CalendarUser::find(
                        array($this->user->getId(), $user_id));
                if ($calendar_user) {
                    if ($accessibility == 'CONFIDENTIAL') {
                        if ($this->event->calendars->findOneBy('range_id', $user_id)) {
                            if ($calendar_user->permission == Calendar::PERMISSION_WRITABLE) {
                                $permission = Event::PERMISSION_WRITABLE;
                            } else {
                                $permission = Event::PERMISSION_READABLE;
                            }
                        } else {
                            $permission = Event::PERMISSION_CONFIDENTIAL;
                        }
                    } else {
                        if ($calendar_user->permission == Calendar::PERMISSION_WRITABLE) {
                            $permission = Event::PERMISSION_WRITABLE;
                        } else {
                            $permission = Event::PERMISSION_READABLE;
                        }
                    }
                }
            } else {
                $permission = Event::PERMISSION_WRITABLE;
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

    public function getAuthor()
    {
        return $this->event->author;
    }

    public function getEditor()
    {
        return $this->event->editor;
    }

    public function getAttendees()
    {
        return self::findByEvent_id($this->event_id);
    }
}
