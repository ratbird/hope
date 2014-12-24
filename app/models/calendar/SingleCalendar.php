<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'app/models/calendar/Calendar.php';

class SingleCalendar
{
    public $events;
    
    public $range_object;
    
    public $type;
    
    public $range;
    
    public $start;
    
    public $end;
    
    public function __construct($range_id, $start = null, $end = null)
    {
        $this->range_object = get_object_by_range_id($range_id);
        $range_map = array(
            'User' => Calendar::RANGE_USER,
            'Course' => Calendar::RANGE_INST,
            'Institute' => Calendar::RANGE_SEM
        );
        $this->range = $range_map[get_class($this->range_object)];
        $this->start = $start ?: 0;
        $this->end = $end ?: Calendar::CALENDAR_END;
        $this->events = new SimpleORMapCollection();
        $this->events->setClassName('Event');
        $this->type = get_class($this->range_object);
    }
    
    
    public function getCalendarEvents($start = null, $end = null)
    {
        $start = !is_null($start) ?: $this->start;
        $end = !is_null($end) ?: $this->end;
        $this->events->merge(CalendarEvent::getEventsByInterval(
                $this->range_object->getId(), new DateTime('@' . $start),
                new DateTime('@' . $end)));
        return $this;
    }
    
    public function getCourseEvents($start = null, $end = null)
    {
        $start = is_null($start) ?: $this->start;
        $end = is_null($end) ?: $this->end;
        if ($this->range == Calendar::RANGE_USER) {
            $this->events->merge(CourseEvent::getEventsByInterval(
                    $this->range_object->getId(), new DateTime('@' . $start),
                    new DateTime('@' . $end)));
        }
        return $this;
    }
    
    public function getStart()
    {
        return $this->start;
    }
    
    public function getEnd()
    {
        return $this->end;
    }
    
    /**
     * Sorts all events by start time.
     */
    public function sortEvents()
    {
        $this->events->uasort(function ($a, $b){
            if ($a->start == $b->start) {
                return 0;
            }
            return ($a->start < $b->start) ? -1 : 1;
        });
    }
    
    public function getId()
    {
        return $this->getRangeId();
    }
    
    public function getRangeId()
    {
        $this->range_object->getId();
    }
    
    public function getPermissionByUser($user_id = null)
    {
        static $user_permission = array();
        
        $user_id = $user_id ?: $GLOBALS['user']->id;
        
        if ($user_permission[$user_id]) {
            return $user_permission[$user_id];
        }
        if ($this->range == Calendar::RANGE_USER
                && $this->range_object->getId() == $user_id) {
            $user_permission[$user_id] = Calendar::PERMISSION_OWN;
            return $user_permission[$user_id];
        }

        switch ($this->type) {
            case 'User' :
                // alle Dozenten haben gegenseitig schreibenden Zugriff, ab dozent immer schreibenden Zugriff
                /*
                if ($GLOBALS['perm']->have_perm('dozent') && $GLOBALS['perm']->get_perm($this->range_object->getId()) == 'dozent') {
                    return Calendar::PERMISSION_WRITABLE;
                }
                 * 
                 */

                $stmt = DBManager::get()->prepare('SELECT calpermission FROM contact WHERE owner_id = ? AND user_id = ?');
                $stmt->execute(array($this->range_object->getId(), $user_id));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    switch ($result['calpermission']) {
                        case 1 :
                            $user_permission[$user_id] = Calendar::PERMISSION_FORBIDDEN;
                            break;
                        case 2 :
                            $user_permission[$user_id] = Calendar::PERMISSION_READABLE;
                            break; 
                        case 4 :
                            $user_permission[$user_id] = Calendar::PERMISSION_WRITABLE;
                            break; 
                        default :
                            $user_permission[$user_id] = Calendar::PERMISSION_FORBIDDEN;
                    }
                } else {
                    $user_permission[$user_id] = Calendar::PERMISSION_FORBIDDEN;
                }
                break;
                /*
            case 'group' :
                $stmt = DBManager::get()->prepare('SELECT range_id FROM statusgruppen WHERE statusgruppe_id = ?');
                $stmt->execute(array($range_id));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    if ($result['range_id'] == $user_id) {
                        return Calendar::PERMISSION_OWN;
                    }
                }
                return Calendar::PERMISSION_FORBIDDEN;
                 * 
                 */
            case 'Course' :
                switch ($GLOBALS['perm']->get_studip_perm($this->range_object->getId(), $user_id)) {
                    case 'user' :
                    case 'autor' :
                        $user_permission[$user_id] = Calendar::PERMISSION_READABLE;
                        break;
                    case 'tutor' :
                    case 'dozent' :
                    case 'admin' :
                    case 'root' :
                        $user_permission[$user_id] = Calendar::PERMISSION_WRITABLE;
                        break;
                    default :
                        $user_permission[$user_id] = Calendar::PERMISSION_FORBIDDEN;
                }
                break;
            case 'Institute' :
                switch ($GLOBALS['perm']->get_studip_perm($this->range_object->getId(), $user_id)) {
                    case 'user' :
                        $user_permission[$user_id] = Calendar::PERMISSION_READABLE;
                        break;
                    case 'autor' :
                        $user_permission[$user_id] = Calendar::PERMISSION_READABLE;
                        break;
                    case 'tutor' :
                    case 'dozent' :
                    case 'admin' :
                    case 'root' :
                        $user_permission[$user_id] = Calendar::PERMISSION_WRITABLE;
                        break;
                    default :
                        // readable for all
                        $user_permission[$user_id] = Calendar::PERMISSION_READABLE;
                }
                break;
            default :
                $user_permission[$user_id] = Calendar::PERMISSION_FORBIDDEN;
        }
        return $user_permission[$user_id];
    }
    
    public function havePermission($permission)
    {
        return $permission <= $this->getPermissionByUser($GLOBALS['user']->id);
    }

    public function checkPermission($permission)
    {
        return $permission == $this->getPermissionByUser($GLOBALS['user']->id);
    }
    
    public function addEventObj(&$event, $updated, $selected_users = NULL)
    {
        if ($this->havePermission(Calendar::PERMISSION_WRITABLE)) {
            $this->event = $event;
            if ($this->range == Calendar::RANGE_USER) {
                // send a message if it is not the users own calendar
                $this->sendStoreMessage($event, $updated);
            }

            $this->event->save();
        }
    }
    
    protected function sendStoreMessage($event, $updated)
    {
        if (!$this->checkPermission(Calendar::PERMISSION_OWN)
                && $this->getRange() == Calendar::RANGE_USER) {
            include_once('lib/messaging.inc.php');
            $message = new messaging();
            $event_data = '';

            if ($updated) {
                $msg_text = sprintf(_("%s hat einen Termin in Ihrem Kalender geändert."), get_fullname());
                $subject = sprintf(_("Termin am %s geändert"), $event->toStringDate('SHORT_DAY'));
                $msg_text .= "\n\n**";
            } else {
                $msg_text = sprintf(_("%s hat einen neuen Termin in Ihren Kalender eingetragen."), get_fullname());
                $subject = sprintf(_("Neuer Termin am %s"), $event->toStringDate('SHORT_DAY'));
                $msg_text .= "\n\n**";
            }
            $msg_text .= _("Zeit:") . '** ' . $event->toStringDate('LONG') . "\n**";
            $msg_text .= _("Zusammenfassung:") . '** ' . $event->getTitle() . "\n";
            if ($event_data = $event->getDescription()) {
                $msg_text .= '**' . _("Beschreibung:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringCategories()) {
                $msg_text .= '**' . _("Kategorie:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringPriority()) {
                $msg_text .= '**' . _("Priorität:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringAccessibility()) {
                $msg_text .= '**' . _("Zugriff:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringRecurrence()) {
                $msg_text .= '**' . _("Wiederholung:") . "** $event_data\n";
            }

            $message->insert_message($msg_text, $this->range_object->username,
                    '____%system%____', '', '', '', '', $subject);
        }
    }

    function deleteEvent($event_id)
    {
        if ($this->havePermission(Calendar::PERMISSION_WRITABLE)) {
            $this->event = new DbCalendarEvent($this, $event_id);

            if (!$this->event->havePermission(Event::PERMISSION_WRITABLE)) {
                $this->event = NULL;

                return false;
            }

            if ($this->range == Calendar::RANGE_USER) {
                $this->sendDeleteMessage($this->event);

                $this->event->delete();

                return true;
            }
            $this->event = NULL;
        }
        return false;
    }
    
    protected function sendDeleteMessage($event)
    {
        if (!$this->checkPermission(Calendar::PERMISSION_OWN)
                && $this->getRange() == Calendar::RANGE_USER) {
            include_once('lib/messaging.inc.php');
            $message = new messaging();
            $event_data = '';

            $subject = sprintf(_("Termin am %s gelöscht"), $event->toStringDate('SHORT_DAY'));
            $msg_text = sprintf(_("%s hat folgenden Termin in Ihrem Kalender gelöscht:"), get_fullname());
            $msg_text .= "\n\n**";

            $msg_text .= _("Zeit:") . '** ' . $event->toStringDate('LONG') . "\n**";
            $msg_text .= _("Zusammenfassung:") . '** ' . $event->getTitle() . "\n";
            if ($event_data = $event->getDescription()) {
                $msg_text .= '**' . _("Beschreibung:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringCategories()) {
                $msg_text .= '**' . _("Kategorie:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringPriority()) {
                $msg_text .= '**' . _("Priorität:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringAccessibility()) {
                $msg_text .= '**' . _("Zugriff:") . "** $event_data\n";
            }
            if ($event_data = $event->toStringRecurrence()) {
                $msg_text .= '**' . _("Wiederholung:") . "** $event_data\n";
            }

            $message->insert_message($msg_text, $this->range_object->username,
                    '____%system%____', '', '', '', '', $subject);
        }
    }
    
    public static function getEventList($owner_id, $start, $end, $user_id = null,
            $restrictions = null)
    {
        $user_id = !is_null($user_id) ?: $GLOBALS['user']->id;
        $end_time = mktime(12, 0, 0, date('n', $end), date('j', $end), date('Y', $end));
        $start_day = date('j', $start);
        $events = array();
        do {
            $time = mktime(12, 0, 0, date('n', $start), $start_day, date('Y', $start));
            $start_day++;
            $day = self::getDayCalendar($owner_id, $time, $user_id, $restrictions);
            foreach ($day->events as $event) {
                $event_key = $event->getId() . $event->getStart();
                $events["$event_key"] = $event;
            }
        } while ($time <= $end_time);
        return $events;
    }
    
    /**
     * Returns a SingleCalendar object with all events of the given owner for
     * one day set by timestamp.
     * 
     * @param type $owner_id
     * @param type $time
     * @param type $user_id
     * @param type $restrictions
     * @return \SingleCalendar
     */
    public static function getDayCalendar($owner_id, $time, $user_id = null,
            $restrictions = null)
    {
        $user_id = !is_null($user_id) ?: $GLOBALS['user']->id;
        
        $day = date('Y-m-d-', $time);
        $start = DateTime::createFromFormat('Y-m-d-H:i:s', $day . '00:00:00');
        $end = DateTime::createFromFormat('Y-m-d-H:i:s', $day . '23:59:59');
        $calendar = new SingleCalendar($owner_id, $start->format('U'), $end->format('U'));
        $calendar->getCalendarEvents()->sortEvents();//->getCourseEvents();
      //  $permission = $calendar->getPermissionByUser($user_id);
        $dow = date('w', $calendar->getStart());
        $month = date('n', $calendar->getStart());
        $year = date('Y', $calendar->getStart());
        $events_created = array();
        
        foreach ($calendar->events as $event) {
            if (!$event->havePermission(Event::PERMISSION_CONFIDENTIAL, $user_id)
                    && !SingleCalendar::checkRestriction($event, $restrictions)) {
                continue;
            }

            $properties = $event->getProperties();
            $ts = mktime(12, 0, 0, date('n', $calendar->start), date('j', $calendar->start), date('Y', $calendar->start));
            $rep = $properties['RRULE'];
            $duration = (int) ((mktime(12, 0, 0, date('n', $properties['DTEND']), date('j', $properties['DTEND']), date('Y', $properties['DTEND']))
                    - mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART'])))
                    / 86400);

            // single events or first event
            if ($properties['DTSTART'] >= $calendar->getStart()
                    && $properties['DTEND'] <= $calendar->getEnd()) {
                self::createDayViewEvent($event, $properties['DTSTART'], $properties['DTEND'],
                        $calendar->getStart(), $calendar->getEnd(), $events_created);
            } elseif ($properties['DTSTART'] >= $calendar->getStart()
                    && $properties['DTSTART'] <= $calendar->getEnd()) {
                self::createDayViewEvent($event, $properties['DTSTART'], $properties['DTEND'],
                        $calendar->getStart(), $calendar->getEnd(), $events_created);
            } elseif ($properties['DTSTART'] < $calendar->getStart()
                    && $properties['DTEND'] > $calendar->getEnd()) {
                self::createDayViewEvent($event, $properties['DTSTART'], $properties['DTEND'],
                        $calendar->getStart(), $calendar->getEnd(), $events_created);
            } elseif ($properties['DTEND'] > $calendar->getStart()
                    && $properties['DTEND'] <= $calendar->getEnd()) {
                self::createDayViewEvent($event, $properties['DTSTART'], $properties['DTEND'],
                        $calendar->getStart(), $calendar->getEnd(), $events_created);
            }

            switch ($rep['rtype']) {

                case 'DAILY':
                    if ($calendar->getEnd() > $rep['expire'] + $duration * 86400) {
                        continue;
                    }
                    $pos = (($ts - $rep['ts']) / 86400) % $rep['linterval'];
                    $start = $ts - $pos * 86400;
                    $end = $start + $duration * 86400;
                    self::createDayViewEvent($event, $start, $end, $calendar->getStart(),
                            $calendar->getEnd(), $events_created);
                    break;

                case 'WEEKLY':
                    for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                        $pos = ((($ts - $dow * 86400) - $rep['ts']) / 86400
                                - $rep['wdays']{$i} + $dow)
                                % ($rep['linterval'] * 7);
                        $start = $ts - $pos * 86400;
                        $end = $start + $duration * 86400;

                        if ($start >= $properties['DTSTART'] && $start <= $ts && $end >= $ts) {
                            self::createDayViewEvent($event, $start, $end,
                                    $calendar->getStart(), $calendar->getEnd(), $events_created);
                        }
                    }
                    break;

                case 'MONTHLY':
                    if ($rep['day']) {
                        $lwst = mktime(12, 0, 0, $month
                                - ((($year - date('Y', $rep['ts'])) * 12
                                + ($month - date('n', $rep['ts']))) % $rep['linterval']),
                                $rep['day'], $year);
                        $hgst = $lwst + $duration * 86400;
                        self::createDayViewEvent($event, $lwst, $hgst, $calendar->getStart(),
                                $calendar->getEnd(), $events_created);
                        break;
                    }
                    if ($rep['sinterval']) {
                        $mon = $month - $rep['linterval'];
                        do {
                            $lwst = mktime(12, 0, 0, $mon
                                    - ((($year - date('Y', $rep['ts'])) * 12
                                    + ($mon - date('n', $rep['ts']))) % $rep['linterval']),
                                    1, $year) + ($rep['sinterval'] - 1) * 604800;
                            $aday = strftime('%u', $lwst);
                            $lwst -= ( $aday - $rep['wdays']) * 86400;
                            if ($rep['sinterval'] == 5) {
                                if (date('j', $lwst) < 10) {
                                    $lwst -= 604800;
                                }
                                if (date('n', $lwst) == date('n', $lwst + 604800)) {
                                    $lwst += 604800;
                                }
                            } else {
                                if ($aday > $rep['wdays']) {
                                    $lwst += 604800;
                                }
                            }
                            $hgst = $lwst + $duration * 86400;
                            if ($ts >= $lwst && $ts <= $hgst) {
                                self::createDayViewEvent($event, $lwst, $hgst,
                                        $calendar->getStart(), $calendar->getEnd(), $events_created);
                            }
                            $mon += $rep['linterval'];
                        } while ($lwst < $ts);
                    }
                    break;

                case 'YEARLY':
                    if ($ts < $rep['ts']) {
                        break;
                    }
                    if ($rep['day']) {
                        if (date('Y', $properties['DTEND']) - date('Y', $properties['DTSTART'])) {
                            $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'],
                                    $year - (($year - date('Y', $rep['ts'])) % $rep['linterval'])
                                    - $rep['linterval']);
                            $hgst = $lwst + 86400 * $duration;

                            if ($ts >= $lwst && $ts <= $hgst) {
                                self::createDayViewEvent($event, $lwst, $hgst,
                                        $calendar->getStart(), $calendar->getEnd(), $events_created);
                                break;
                            }
                        }
                        $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'],
                                $year - (($year - date('Y', $rep['ts'])) % $rep['linterval']));
                        $hgst = $lwst + 86400 * $duration;
                        self::createDayViewEvent($event, $lwst, $hgst, $calendar->getStart(),
                                $calendar->getEnd(), $events_created);
                        break;
                    }
                    $ayear = $year - 1;
                    do {
                        if ($rep['sinterval']) {
                            $lwst = mktime(12, 0, 0, $rep['month'],
                                    1 + ($rep['sinterval'] - 1) * 7, $ayear);
                            $aday = strftime('%u', $lwst);
                            $lwst -= ( $aday - $rep['wdays']) * 86400;
                            if ($rep['sinterval'] == 5) {
                                if (date('j', $lwst) < 10) {
                                    $lwst -= 604800;
                                }
                                if (date('n', $lwst) == date('n', $lwst + 604800)) {
                                    $lwst += 604800;
                                }
                            } elseif ($aday > $rep['wdays']) {
                                $lwst += 604800;
                            }
                            $ayear++;
                            $hgst = $lwst + $duration * 86400;
                            if ($ts >= $lwst && $ts <= $hgst) {
                                self::createDayViewEvent($event, $lwst, $hgst,
                                        $calendar->getStart(), $calendar->getEnd(), $events_created);
                            }
                        }
                    } while ($lwst < $ts);
            }
        }
        $calendar->events = SimpleORMapCollection::createFromArray(
                array_values($events_created));
        return $calendar;
    }
    
    private static function createDayViewEvent($event, $lwst, $hgst,
            $cl_start, $cl_end, &$events_created)
    {
        // if this date is in the exceptions return false
        $exdates = explode(',', $event->getProperty('EXDATE'));
        foreach ($exdates as $exdate) {
            if ($exdate > 0 && $exdate >= $lwst && $exdate <= $hgst) {
                return false;
            }
        }
        // is event expired?
        $rrule = $event->getProperty('RRULE');
        if ($rrule['expire'] > 0 && $rrule['expire'] <= $hgst) {
            return false;
        }
        $start = mktime(date('G', $event->getStart()), date('i', $event->getStart()),
                date('s', $event->getStart()), date('n', $lwst), date('j', $lwst), date('Y', $lwst));
        $end = mktime(date('G', $event->getEnd()), date('i', $event->getEnd()),
                date('s', $event->getEnd()), date('n', $hgst), date('j', $hgst), date('Y', $hgst));

        if (($start <= $cl_start && $end >= $cl_end)
                || ($start >= $cl_start && $start < $cl_end)
                || ($end > $cl_start && $end <= $cl_end)) {

            if (!$events_created[$event->getId() . $start]) {
                $new_event = clone $event;
                $new_event->setStart($start);
                $new_event->setEnd($end);
                /*
                if ($properties['EVENT_TYPE'] == 'semcal') {
                    $event = new SeminarCalendarEvent($properties, $properties['STUDIP_ID'], $properties['SEM_ID'], $this->permission);
                    $event->sem_id = $properties['SEM_ID'];
                } else if ($properties['EVENT_TYPE'] == 'cal') {
                    $event = new CalendarEvent($properties, $properties['STUDIP_ID'], $this->user_id, $this->permission);
                } else {
                    $event = new SeminarEvent($properties['STUDIP_ID'], $properties, $properties['SEM_ID'], $this->permission);
                }
                 * 
                 */
                $events_created[$event->getId() . $start] = $event;
            }
        }
    }
    
    /**
     * Returns an array with all days between start and end of this SingleCalendar.
     * The keys are the timestamps of the days (12:00) and the values are number
     * of events for a day.
     * 
     * @param string $user_id Use the permissions of this user.
     * @param array $restrictions
     */
    public function getListCountEvents($user_id = null, $restrictions = null)
    {

        $end = $this->getEnd();
        $start = $this->getStart();
        $year = $this->year;
        $end_ts = mktime(12, 0, 0, date('n', $end), date('j', $end), date('Y', $end));
        $start_ts = mktime(12, 0, 0, date('n', $start), date('j', $start), date('Y', $start));
        $this->getCalendarEvents()->sortEvents();
        $daylist = array();
        
        foreach ($this->events as $event) {
            if (!$event->havePermission(Event::PERMISSION_CONFIDENTIAL, $user_id)
                    && !SingleCalendar::checkRestriction($event, $restrictions)) {
                continue;
            }
            $properties = $event->getProperties();

            $rep = $properties['RRULE'];
            $duration = (int) ((mktime(12, 0, 0, date('n', $properties['DTEND']), date('j', $properties['DTEND']), date('Y', $properties['DTEND']))
                    - mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART'])))
                    / 86400);

            // single event or first event
            $lwst = mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']));
            if ($start_ts > $lwst) {
                $adate = $start_ts;
            } else {
                $adate = $lwst;
            }
            $hgst = $lwst + $duration * 86400;
            while ($adate >= $start_ts && $adate <= $end_ts && $adate <= $hgst) {
                $this->countListEvent($properties, $adate, $properties['DTSTART'], $properties['DTEND'], $daylist);
                $adate += 86400;
            }

            switch ($rep['rtype']) {
                case 'DAILY' :
                    if ($rep['ts'] < $start) {
                        // brauche den ersten Tag nach $start an dem dieser Termin wiederholt wird
                        if ($rep['linterval'] == 1) {
                            $adate = $this->ts;
                        } else {
                            $adate = $this->ts + ($rep['linterval'] - (($this->ts - $rep['ts']) / 86400)
                                    % $rep['linterval']) * 86400;
                        }
                        while ($adate <= $end_ts && $adate >= $this->ts && $adate <= $rep['expire']) {
                            $hgst = $adate + $duration * 86400;
                            $md_date = $adate;
                            while ($md_date <= $end_ts && $md_date >= $this->ts && $md_date <= $hgst) {
                                $this->countListEvent($properties, $md_date, $adate, $hgst, $daylist);
                                $md_date += 86400;
                            }
                            $adate += $rep['linterval'] * 86400;
                        }
                    } else {
                        $adate = $rep['ts'];
                    }
                    while ($adate <= $end_ts && $adate >= $this->ts && $adate <= $rep['expire']) {
                        $hgst = $adate + $duration * 86400;
                        $md_date = $adate;
                        while ($md_date <= $end_ts && $md_date >= $this->ts && $md_date <= $hgst) {
                            $this->countListEvent($properties, $md_date, $adate, $hgst, $daylist);
                            $md_date += 86400;
                        }
                        $adate += $rep['linterval'] * 86400;
                    }
                    break;

                case 'WEEKLY' :
                    if ($properties['DTSTART'] >= $start && $properties['DTSTART'] <= $end) {
                        $lwst = mktime(12, 0, 0, date('n', $properties['DTSTART']), date('j', $properties['DTSTART']), date('Y', $properties['DTSTART']));
                        $hgst = $lwst + $duration * 86400;
                        if ($rep['ts'] != $adate) {
                            $md_date = $lwst;
                            while ($md_date <= $end_ts && $md_date >= $start_ts && $md_date <= $hgst) {
                                $this->countListEvent($properties, $md_date, $lwst, $hgst, $daylist);
                                $md_date += 86400;
                            }
                        }
                        $aday = strftime('%u', $lwst) - 1;
                        for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                            $awday = (int) substr($rep['wdays'], $i, 1) - 1;
                            if ($awday > $aday) {
                                $lwst = $lwst + ($awday - $aday) * 86400;
                                $hgst = $lwst + $duration * 86400;
                                $wdate = $lwst;
                                while ($wdate >= $start_ts && $wdate <= $end_ts && $wdate <= $hgst) {
                                    $this->countListEvent($properties, $wdate, $lwst, $hgst, $daylist);
                                    $wdate += 86400;
                                }
                            }
                        }
                    }
                    if ($rep['ts'] < $start) {
                        $adate = $start_ts - (strftime('%u', $start_ts) - 1) * 86400;
                        $adate += ( $rep['linterval'] - (($adate - $rep['ts']) / 604800)
                                % $rep['linterval']) * 604800;
                        $adate -= $rep['linterval'] * 604800;
                    } else {
                        $adate = $rep['ts'];
                    }

                    while ($adate >= $properties['DTSTART'] && $adate <= $rep['expire'] && $adate <= $end) {
                        // event is repeated on different week days
                        for ($i = 0; $i < strlen($rep['wdays']); $i++) {
                            $awday = (int) $rep['wdays']{$i};
                            $lwst = $adate + ($awday - 1) * 86400;
                            $hgst = $lwst + $duration * 86400;
                            if ($lwst < $start_ts) {
                                $lwst = $start_ts;
                            }
                            $wdate = $lwst;

                            while ($wdate >= $start_ts && $wdate <= $end_ts && $wdate <= $hgst) {
                                $this->countListEvent($properties, $wdate, $lwst, $hgst, $daylist);
                                $wdate += 86400;
                            }
                        }
                        $adate += 604800 * $rep['linterval'];
                    }
                    break;

                case 'MONTHLY' :
                    $bmonth = ($rep['linterval'] - ((($year - date('Y', $rep['ts'])) * 12)
                            - date('n', $rep['ts'])) % $rep['linterval']) % $rep['linterval'];
                    for ($amonth = $bmonth - $rep['linterval']; $amonth <= $bmonth; $amonth += $rep['linterval']) {
                        if ($rep['ts'] < $start) {
                            // is repeated at X. week day of X. month...
                            if (!$rep['day']) {
                                $lwst = mktime(12, 0, 0, $amonth
                                                - ((($year - date('Y', $rep['ts'])) * 12
                                                + ($amonth - date('n', $rep['ts']))) % $rep['linterval']), 1, $year)
                                        + ($rep['sinterval'] - 1) * 604800;
                                $aday = strftime('%u', $lwst);
                                $lwst -= ( $aday - $rep['wdays']) * 86400;
                                if ($rep['sinterval'] == 5) {
                                    if (date('j', $lwst) < 10) {
                                        $lwst -= 604800;
                                    }
                                    if (date('n', $lwst) == date('n', $lwst + 604800)) {
                                        $lwst += 604800;
                                    }
                                } else {
                                    if ($aday > $rep['wdays']) {
                                        $lwst += 604800;
                                    }
                                }
                            } else {
                                // or at X. day of month ?
                                $lwst = mktime(12, 0, 0, $amonth
                                        - ((($year - date('Y', $rep['ts'])) * 12
                                        + ($amonth - date('n', $rep['ts']))) % $rep['linterval']), $rep['day'], $year);
                            }
                        } else {
                            // first recurrence
                            $lwst = $rep['ts'];
                        }
                        $hgst = $lwst + $duration * 86400;
                        $md_date = $lwst;
                        // events last longer than one day
                        while ($hgst >= $start_ts && $md_date <= $hgst && $md_date <= $end_ts) {
                            $this->countListEvent($properties, $md_date, $lwst, $hgst, $daylist);
                            $md_date += 86400;
                        }
                    }
                    break;

                case 'YEARLY' :
                    for ($ayear = $this->year - 1; $ayear <= $this->year; $ayear++) {
                        if ($rep['day']) {
                            $lwst = mktime(12, 0, 0, $rep['month'], $rep['day'], $ayear);
                            $hgst = $lwst + $duration * 86400;
                            $wdate = $lwst;
                            while ($hgst >= $start_ts && $wdate <= $hgst && $wdate <= $end_ts) {
                                $this->countListEvent($properties, $wdate, $lwst, $hgst, $daylist);
                                $wdate += 86400;
                            }
                        } else {
                            if ($rep['ts'] < $start) {
                                $adate = mktime(12, 0, 0, $rep['month'], 1, $ayear)
                                        + ($rep['sinterval'] - 1) * 604800;
                                $aday = strftime('%u', $adate);
                                $adate -= ( $aday - $rep['wdays']) * 86400;
                                if ($rep['sinterval'] == 5) {
                                    if (date('j', $adate) < 10) {
                                        $adate -= 604800;
                                    }
                                } elseif ($aday > $rep['wdays']) {
                                    $adate += 604800;
                                }
                            } else {
                                $adate = $rep['ts'];
                            }
                            $lwst = $adate;
                            $hgst = $lwst + $duration * 86400;
                            while ($hgst >= $start_ts && $adate <= $hgst && $adate <= $end_ts) {
                                $this->countListEvent($properties, $adate, $lwst, $hgst, $daylist);
                                $adate += 86400;
                            }
                        }
                    }
            }
        }
        return $daylist;
    }
    
    private function countListEvent($properties, $date, $lwst, $hgst, &$daylist)
    {
        if ($date < $this->getStart() || $date > $this->getEnd()) {
            return false;
        }

        // if this date is in the exceptions return false
        $exdates = explode(',', $properties['EXDATE']);
        foreach ($exdates as $exdate) {
            if ($exdate > 0 && $exdate >= $lwst && $exdate <= $hgst) {
                return false;
            }
        }
        // is event expired?
        if ($properties['RRULE']['expire'] > 0
                && $properties['RRULE']['expire'] <= $hgst) {
            return false;
        }
        $daylist["$date"]["{$properties['STUDIP_ID']}"] = 1;

        return true;
    }
    
    /**
     * 
     * TODO use filter instead
     * 
     * @param Event $event
     * @param array $restrictions
     * @return boolean
     */
    public static function checkRestriction(Event $event, $restrictions)
    {
        $properties = $event->getProperties();
        if (is_array($restrictions)) {
            foreach ($restrictions as $property_name => $restriction) {
                if ($restriction != '') {
                    if ($properties[strtoupper($property_name)] != $restriction) {
                        return false;
                    }
                }
            }
            return true;
        }

        return true;
    }
    
    /**
     * Returns an array with all necessary informations to build the day view.
     * 
     * @param type $start
     * @param type $end
     * @param type $step
     * @param type $params
     * @return type
     */
    public function createEventMatrix($start, $end, $step, $params = NULL)
    {
        $term = array();
        $em = $this->adapt_events($start, $end, $step);
        $max_cols = 0;

        // calculate maximum number of columns
        $w = 0;
        for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
            $col = 0;
            $row = $i - $start / $step;
            while ($w < sizeof($em['events']) && $em['events'][$w]->getStart() >= $this->getStart() + $i * $step
            && $em['events'][$w]->getStart() < $this->getStart() + ($i + 1) * $step) {
                $rows = ceil($em['events'][$w]->getDuration() / $step);
                if ($rows < 1) {
                    $rows = 1;
                }

                while ($term[$row][$col] != '' && $term[$row][$col] != '#') {
                    $col++;
                }

                $term[$row][$col] = $em['events'][$w];
                $mapping[$row][$col] = $em['map'][$w];

                $count = $rows - 1;
                for ($x = $row + 1; $x < $row + $rows; $x++) {
                    for ($y = 0; $y <= $col; $y++) {
                        if ($y == $col) {
                            $term[$x][$y] = $count--;
                        } elseif ($term[$x][$y] == '') {
                            $term[$x][$y] = '#';
                        }
                    }
                }
                if ($max_cols < sizeof($term[$row])) {
                    $max_cols = sizeof($term[$row]);
                }
                $w++;
            }
        }

        $row_min = 0;
        for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
            $row = $i - $start / $step;
            $row_min = $row;

            while ($this->maxValue($term[$row], $step) > 1) {
                $row += $this->maxValue($term[$row], $step) - 1;
            }

            $size = 0;
            for ($j = $row_min; $j <= $row; $j++) {
                if (sizeof($term[$j]) > $size) {
                    $size = sizeof($term[$j]);
                }
            }

            for ($j = $row_min; $j <= $row; $j++) {
                $colsp[$j] = $size;
            }

            $i = $row + $start / $step;
        }

        $rows = array();
        for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
            $row = $i - $start / $step;
            $cspan_0 = 0;
            if ($term[$row]) {
                if ($colsp[$row] > 0) {
                    $cspan_0 = (int) ($max_cols / $colsp[$row]);
                }

                for ($j = 0; $j < $colsp[$row]; $j++) {
                    $sp = 0;
                    $n = 0;
                    if ($j + 1 == $colsp[$row]) {
                        $cspan[$row][$j] = $cspan_0 + $max_cols % $colsp[$row];
                    }

                    if (is_object($term[$row][$j])) {
                        // Wieviele Termine sind zum aktuellen Termin zeitgleich?
                        $p = 0;
                        $count = 0;
                        while ($aterm = $em['events'][$p]) {
                            if ($aterm->getStart() >= $term[$row][$j]->getStart()
                                    && $aterm->getStart() <= $term[$row][$j]->getEnd()) {
                                $count++;
                            }
                            $p++;
                        }

                        if ($count == 0) {
                            for ($n = $j + 1; $n < $colsp[$row]; $n++) {
                                if (!is_int($term[$row][$n])) {
                                    $sp++;
                                } else {
                                    break;
                                }
                            }
                            $cspan[$row][$j] += $sp;
                        }
                        $rows[$row][$j] = ceil($term[$row][$j]->getDuration() / $step);
                        if ($rows[$row][$j] < 1) {
                            $rows[$row][$j] = 1;
                        }
                        if ($sp > 0) {
                            for ($m = $row; $m < $rows + $row; $m++) {
                                $colsp[$m] = $colsp[$m] - $sp + 1;
                                $v = $j;
                                while ($term[$m][$v] == '#') {
                                    $term[$m][$v] = 1;
                                }
                            }
                            $j = $n;
                        }
                    } elseif ($term[$row][$j] == '#') {
                        $csp = 1;
                        while ($term[$row][$j] == '#') {
                            $csp += $cspan[$row][$j];
                            $j++;
                        }
                        $cspan[$row][$j] = $csp;
                    } elseif ($term[$row][$j] == '') {
                        $cspan[$row][$j] = $max_cols - $j + 1;
                    }
                }
            }
        }
        $em['cspan'] = $cspan;
        $em['rows'] = $rows;
        $em['colsp'] = $colsp;
        $em['term'] = $term;
        $em['max_cols'] = $max_cols;
        $em['mapping'] = $mapping;

        return $em;
    }

    private function maxValue($term, $st)
    {
        $max_value = 0;
        for ($i = 0; $i < sizeof($term); $i++) {
            if (is_object($term[$i]))
                $max = ceil($term[$i]->getDuration() / $st);
            elseif ($term[$i] == '#')
                continue;
            elseif ($term[$i] > $max_value)
                $max = $term[$i];
            if ($max > $max_value)
                $max_value = $max;
        }

        return $max_value;
    }

    private function adapt_events($start, $end, $step = 900)
    {
        for ($i = 0; $i < sizeof($this->events); $i++) {
            if (($this->events[$i]->getEnd() >= $this->getStart() + $start)
                    && ($this->events[$i]->getStart() < $this->getStart() + $end + 3600)) {

                if ($this->events[$i]->isDayEvent()
                        || ($this->events[$i]->getStart() <= $this->getStart()
                        && $this->events[$i]->getEnd() >= $this->getEnd())) {
                    $cloned_day_event = clone $this->events[$i];
                    $cloned_day_event->setStart($this->getStart());
                    $cloned_day_event->setEnd($this->getEnd());
                    $tmp_day_event[] = $cloned_day_event;
                    $map_day_events[] = $i;
                } else {
                    $cloned_event = clone $this->events[$i];
                    $end_corr = $cloned_event->getEnd() % $step;
                    if ($end_corr > 0) {
                        $end_corr = $cloned_event->getEnd() + ($step - $end_corr);
                        $cloned_event->setEnd($end_corr);
                    }
                    if ($cloned_event->getStart() < ($this->getStart() + $start)) {
                        $cloned_event->setStart($this->getStart() + $start);
                    }
                    if ($cloned_event->getEnd() > ($this->getStart() + $end + 3600)) {
                        $cloned_event->setEnd($this->getStart() + $end + 3600);
                    }

                    $tmp_event[] = $cloned_event;
                    $map_events[] = $i;
                }
            }
        }
        return array('events' => $tmp_event, 'map' => $map_events, 'day_events' => $tmp_day_event,
            'day_map' => $map_day_events);
    }
    
}