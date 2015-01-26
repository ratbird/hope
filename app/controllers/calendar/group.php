<?php

require_once 'app/controllers/calendar/calendar.php';
require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/calendar/Calendar.php';
require_once 'app/models/calendar/SingleCalendar.php';

class Calendar_GroupController extends Calendar_CalendarController {

    function __construct($dispatcher) {
        parent::__construct($dispatcher);
    }

    public function before_filter(&$action, &$args)
    {
        $this->base = 'calendar/group/';
        parent::before_filter($action, $args);
    }
    
    protected function createSidebar($active = 'week', $calendar = null)
    {
        parent::createSidebar($active, $calendar);
        $sidebar = Sidebar::Get();
        $actions->addLink(_('Termin anlegen'),
            $this->url_for('calendar/group/edit'), 'icons/16/blue/add.png',
            array('data-dialog' => 'size=auto'));
        $actions->addLink(_('Kalender freigeben'),
                $this->url_for('calendar/single/manage_access'), 'icons/16/blue/community.png',
                array('data-dialog' => '', 'data-dialogname' => 'manageaccess'));
        $sidebar->addWidget($actions);
    }

    protected function getTitle($group)
    {
        $title = sprintf(_('Terminkalender der Gruppe "%s"'), $group->name);
        return $title;
    }
    
    public function index_action()
    {
        // switch to the view the user has selected in his personal settings
        $default_view = $this->settings['view'] ?: 'week';
        $this->redirect($this->url_for('calendar/group/' . $default_view));
    }
    
    public function day_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        // get group and the calendars of the members
        // the first calendar is the calendar of the actual user
        $this->calendars[0] = SingleCalendar::getDayCalendar(
                $GLOBALS['user']->id, $this->atime);
        $group = $this->getGroup($this->calendars[0]);
        foreach ($group->members as $member) {
            $calendar = new SingleCalendar($member->user_id);
            if ($calendar->havePermission(Calendar::PERMISSION_READABLE)) {
                $this->calendars[] = SingleCalendar::getDayCalendar($calendar, $this->atime);
            }
        }
        
        PageLayout::setTitle($this->getTitle($group)
                . ' - ' . _('Tagesansicht'));
        Navigation::activateItem('/calendar/calendar');

        $this->last_view = 'day';
        
        $this->createSidebar('day');
        $this->createSidebarFilter();
    }
    
    private function getGroup($calendar)
    {
        $group = Statusgruppen::find($this->range_id);
        if ($group->isNew()) {
            throw new AccessDeniedException();
        }
        // is the user the owner of this group
        if ($group->range_id != $calendar->getRangeId()) {
            // not the owner...
            throw new AccessDeniedException();
        }
        return $group;
    }
    
    public function week_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $timestamp = mktime(12, 0, 0, date('n', $this->atime),
                date('j', $this->atime), date('Y', $this->atime));
        $monday = $timestamp - 86400 * (strftime('%u', $timestamp) - 1);
        $day_count = $this->settings['type_week'] == 'SHORT' ? 5 : 7;
        // one calendar for each day for the actual user
        for ($i = 0; $i < $day_count; $i++) {
            // one calendar holds the events of one day
            $this->calendars[0][$i] =
                    SingleCalendar::getDayCalendar(
                            $GLOBALS['user']->id, $monday + $i * 86400);
        }
        // check and get the group
        $group = $this->getGroup($this->calendars[0][0]);
        $n = 1;
        foreach ($group->members as $member) {
            $calendar = new SingleCalendar($member->user_id);
            if ($calendar->havePermission(Calendar::PERMISSION_READABLE)) {
                for ($i = 0; $i < $day_count; $i++) {
                    $this->calendars[$n][$i] =
                            SingleCalendar::getDayCalendar($calendar, $monday + $i * 86400);
                }
                $n++;
            }
        }
        
        PageLayout::setTitle($this->getTitle($group)
                . ' - ' . _('Wochenansicht'));
        Navigation::activateItem('/calendar/calendar');

        $this->last_view = 'week';
        
        $this->createSidebar('week');
        $this->createSidebarFilter();
    }
    
    public function month_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $month_start = mktime(12, 0, 0, date('n', $this->atime), 1, date('Y', $this->atime));
        $month_end = mktime(12, 0, 0, date('n', $this->atime), date('t', $this->atime), date('Y', $this->atime));
        $adow = strftime('%u', $month_start) - 1;
        $cor = date('n', $this->atime) == 3 ? 1 : 0;
        $this->first_day = $month_start - $adow * 86400;
        $this->last_day = ((42 - ($adow + date('t', $this->atime))) % 7 + $cor) * 86400 + $month_end;
        // one calendar each day for the actual user
        for ($start_day = $this->first_day; $start_day <= $this->last_day; $start_day += 86400) {
            $this->calendars[0][] = SingleCalendar::getDayCalendar(
                    $GLOBALS['user']->id, $start_day);
        }
        // check and get the group
        $group = $this->getGroup($this->calendars[0][0]);
        $n = 1;
        // get the calendars of the group members
        foreach ($group->members as $member) {
            $calendar = new SingleCalendar($member->user_id);
            if ($calendar->havePermission(Calendar::PERMISSION_READABLE)) {
                for ($start_day = $this->first_day; $start_day <= $this->last_day; $start_day += 86400) {
                    $this->calendars[$n][] =
                            SingleCalendar::getDayCalendar($calendar, $start_day);
                }
                $n++;
            }
        }
        PageLayout::setTitle($this->getTitle($group)
                . ' - ' . _('Monatssicht'));
        Navigation::activateItem('/calendar/calendar');

        $this->last_view = 'month';
        
        $this->createSidebar('month');
        $this->createSidebarFilter();
    }
    
    public function year_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $start = mktime(0, 0, 0, 1, 1, date('Y', $this->atime));
        $end = mktime(23, 59, 59, 12, 31, date('Y', $this->atime));
        $this->calendars[0] = new SingleCalendar(
                $GLOBALS['user']->id, $start, $end);
        $this->count_lists[0] = $this->calendars[0]->getListCountEvents();
        
        // check and get the group
        $group = $this->getGroup($this->calendars[0]);
        $n = 1;
        // get the calendars of the group members
        foreach ($group->members as $member) {
            $calendar = new SingleCalendar($member->user_id);
            if ($calendar->havePermission(Calendar::PERMISSION_READABLE)) {
                $this->calendars[$n] = $calendar->setStart($start)->setEnd($end);
                $this->count_lists[$n] = $this->calendars[$n]->getListCountEvents();
                $n++;
            }
        }
        
        PageLayout::setTitle($this->getTitle($group)
                . ' - ' . _('Jahresansicht'));
        Navigation::activateItem("/calendar/calendar");

        $this->last_view = 'year';
        $this->createSidebar('year');
        $this->createSidebarFilter();
    }
}