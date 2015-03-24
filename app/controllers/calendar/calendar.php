<?php
/*
 * The controller for the personal calendar.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       
 */

require_once 'app/controllers/authenticated_controller.php';

class Calendar_CalendarController extends AuthenticatedController
{
    
    public function __construct($dispatcher) {
        parent::__construct($dispatcher);
    }
    
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        PageLayout::setHelpKeyword('Basis.Terminkalender');
        $this->settings = UserConfig::get($GLOBALS['user']->id)->getValue('CALENDAR_SETTINGS');
        if (!is_array($this->settings)) {
            $this->settings = Calendar::getDefaultUserSettings();
        }
        URLHelper::bindLinkParam('atime', $this->atime);
        $this->atime = Request::int('atime', time());
        $this->category = Request::int('category');
        $this->range_id = Request::option('range_id', $GLOBALS['user']->id);
        $this->last_view = Request::option('last_view',
                $this->settings['view']);
        $this->action = $action;
        $this->restrictions =
                $this->category ? array('STUDIP_CATEGORY' => $this->category) : null;
        if ($this->category) {
            URLHelper::bindLinkParam('category', $this->category);
        }
        if ($this->range_id) {
            URLHelper::bindLinkParam('range_id', $this->range_id);
        }
        URLHelper::bindLinkParam('last_view', $this->last_view);
        Navigation::activateItem('/calendar/calendar');
    }
    
    protected function createSidebar($active = null, $calendar = null)
    {
        $active = $active ?: $this->last_view;
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Kalender'));
        $sidebar->setImage('sidebar/schedule-sidebar.png');
        $views = new ViewsWidget();
        $views->addLink(_('Tag'), $this->url_for($this->base . 'day'))
                ->setActive($active == 'day');
        $views->addLink(_('Woche'), $this->url_for($this->base . 'week'))
                ->setActive($active == 'week');
        $views->addLink(_('Monat'), $this->url_for($this->base . 'month'))
                ->setActive($active == 'month');
        $views->addLink(_('Jahr'), $this->url_for($this->base . 'year'))
                ->setActive($active == 'year');
        $sidebar->addWidget($views);
    }
    
    protected function createSidebarFilter()
    {
        $tmpl_factory = $this->get_template_factory();

        $filters = new SidebarWidget();
        $filters->setTitle('Auswahl');

        $tmpl = $tmpl_factory->open('calendar/single/_jump_to');
        $tmpl->atime = $this->atime;
        $tmpl->action = $this->action;
        $tmpl->action_url = $this->url_for('calendar/single/jump_to');
        $filters->addElement(new WidgetElement($tmpl->render()));

        $tmpl = $tmpl_factory->open('calendar/single/_select_category');
        $tmpl->action_url = $this->url_for();
        $tmpl->category = $this->category;
        $filters->addElement(new WidgetElement($tmpl->render()));

        if (get_config('CALENDAR_GROUP_ENABLE')) {
            $tmpl = $tmpl_factory->open('calendar/single/_select_calendar');
            $tmpl->range_id = $this->range_id;
            $tmpl->action_url = $this->url_for('calendar/group/switch');
            $tmpl->view = $this->action;
            $filters->addElement(new WidgetElement($tmpl->render()));
        }
        Sidebar::get()->addWidget($filters);
    }
    
    public function index_action()
    {
        // switch to the view the user has selected in his personal settings
        $default_view = $this->settings['view'] ?: 'week';
        $this->redirect($this->url_for($this->base . $default_view));
    }
    
    public function edit_action($range_id = null, $event_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = new SingleCalendar($this->range_id);
        $this->event = $this->calendar->getEvent($event_id);
        
        if ($this->event->isNew()) {
            $this->event = $this->calendar->getNewEvent();
            if (Request::get('isdayevent')) {
                $this->event->setStart(mktime(0, 0, 0, date('n', $this->atime),
                        date('j', $this->atime), date('Y', $this->atime)));
                $this->event->setEnd(mktime(23, 59, 59, date('n', $this->atime),
                        date('j', $this->atime), date('Y', $this->atime)));
            } else {
                $this->event->setStart($this->atime);
                $this->event->setEnd($this->atime + 3600);
            }
            $this->event->setAuthorId($GLOBALS['user']->id);
            $this->event->setEditorId($GLOBALS['user']->id);
            $this->event->setAccessibility('PRIVATE');
            $this->attendees = array($this->event);
            if (!Request::isXhr()) {
                PageLayout::setTitle($this->getTitle($this->calendar, _('Neuer Termin')));
            }
        } else {
            // open read only events and course events not as form
            // show information in dialog instead
            if (!$this->event->havePermission(Event::PERMISSION_WRITABLE)
                    || $this->event instanceof CourseEvent) {
                $this->redirect($this->url_for('calendar/single/event/' . implode('/',
                        array($this->range_id, $this->event->event_id))));
                return null;
            }
            $this->attendees = $this->event->getAttendees();
            if (!Request::isXhr()) {
                PageLayout::setTitle($this->getTitle($this->calendar, _('Termin bearbeiten')));
            }
        }
        
        if (get_config('CALENDAR_GROUP_ENABLE')
                && $this->calendar->getRange() == Calendar::RANGE_USER) {
            $search_obj = new SQLSearch("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
                . "FROM calendar_user "
                . "LEFT JOIN auth_user_md5 ON calendar_user.owner_id = auth_user_md5.user_id "
                . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
                . 'WHERE calendar_user.user_id = '
                . DBManager::get()->quote($GLOBALS['user']->id)
                . ' AND calendar_user.permission > ' . Event::PERMISSION_READABLE
                . ' AND (username LIKE :input OR Vorname LIKE :input '
                . "OR CONCAT(Vorname,' ',Nachname) LIKE :input "
                . "OR CONCAT(Nachname,' ',Vorname) LIKE :input "
                . "OR Nachname LIKE :input OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input "
                . ") ORDER BY fullname ASC",
                _('Nutzer suchen'), 'user_id');
            $this->quick_search = QuickSearch::get('user_id', $search_obj)
                    ->fireJSFunctionOnSelect('STUDIP.Messages.add_adressee');
            
      //      $default_selected_user = array($this->calendar->getRangeId());
            $this->mps = MultiPersonSearch::get('add_adressees')
                ->setLinkText(_('Mehrere Teilnehmer hinzufügen'))
       //         ->setDefaultSelectedUser($default_selected_user)
                ->setTitle(_('Mehrere Teilnehmer hinzufügen'))
                ->setExecuteURL($this->url_for($this->base . 'edit'))
                ->setJSFunctionOnSubmit('STUDIP.Messages.add_adressees')
                ->setSearchObject($search_obj);
            $owners = SimpleORMapCollection::createFromArray(
                    CalendarUser::findByUser_id($this->calendar->getRangeId()))
                    ->pluck('owner_id');
            foreach (Calendar::getGroups($GLOBALS['user']->id) as $group) {
                $this->mps->addQuickfilter(
                    $group->name,
                    $group->members->filter(
                        function ($member) use ($owners) {
                            if (in_array($member->user_id, $owners)) {
                                return $member;
                            }
                        })->pluck('user_id')
                );
            }
        }
        
        $stored = false;
        if (Request::submitted('store')) {
            $stored = $this->storeEventData($this->event, $this->calendar);
        }

        if ($stored !== false) {
            if ($stored === 0) {
                if (Request::isXhr()) {
                    header('X-Dialog-Close: 1');
                    exit;
                } else {
                    PageLayout::postMessage(MessageBox::success(_('Der Termin wurde nicht geändert.')));
                    $this->relocate('calendar/single/' . $this->last_view, array('atime' => $this->atime));
                }
            } else {
                PageLayout::postMessage(MessageBox::success(_('Der Termin wurde gespeichert.')));
                $this->relocate('calendar/single/' . $this->last_view, array('atime' => $this->atime));
            }
        }
        
        $this->createSidebar('edit', $this->calendar);
        $this->createSidebarFilter();
    }
    
    public function switch_action()
    {
        $view = Request::option('last_view', 'week');
        $this->range_id = Request::option('range_id', $GLOBALS['user']->id);
        $object_type = get_object_type($this->range_id);
        switch ($object_type) {
            case 'user':
            case 'sem':
            case 'inst':
            case 'fak':
                $this->redirect($this->url_for('calendar/single/'
                        . $view . '/' . $this->range_id));
                break;
            case 'group':
                $this->redirect($this->url_for('calendar/group/'
                        . $view . '/' . $this->range_id));
                break;
        }
    }
    
    public function jump_to_action()
    {
        $date = Request::get('jmp_date');
        if ($date) {
            $atime = strtotime($date . strftime(' %T', $this->atime));
        } else {
            $atime = 'now';
        }
        $action = Request::option('action', 'week');
        $this->range_id = $this->range_id ?: $GLOBALS['user']->id;
        $this->redirect($this->url_for($this->base . $action,
                array('atime' => $atime, 'range_id' => $this->range_id)));
    }
    
    protected function storeEventData(CalendarEvent $event, SingleCalendar $calendar)
    {
        if (Request::int('isdayevent')) {
            $dt_string = Request::get('start_date') . ' 00:00:00';
        } else {
            $dt_string = Request::get('start_date') . ' ' . Request::int('start_hour')
                    . ':' . Request::int('start_minute');
        }
        $event->setStart($this->parseDateTime($dt_string));
        if (Request::int('isdayevent')) {
            $dt_string = Request::get('end_date') . ' 23:59:59';
        } else {
            $dt_string = Request::get('end_date') . ' ' . Request::int('end_hour')
                    . ':' . Request::int('end_minute');
        }
        $event->setEnd($this->parseDateTime($dt_string));
        if ($event->getStart() > $event->getEnd()) {
            $messages[] = _('Die Startzeit muss vor der Endzeit liegen.');
        }
        
        if (Request::isXhr()) {
            $event->setTitle(studip_utf8decode(Request::get('summary', '')));
            $event->event->description = studip_utf8decode(Request::get('description', ''));
            $event->setUserDefinedCategories(studip_utf8decode(Request::get('categories', '')));
            $event->event->location = studip_utf8decode(Request::get('location', ''));
        } else {
            $event->setTitle(Request::get('summary'));
            $event->event->description = Request::get('description', '');
            $event->setUserDefinedCategories(Request::get('categories', ''));
            $event->event->location = Request::get('location', '');
        }
        $event->event->category_intern = Request::int('category_intern', 1);
        $event->setAccessibility(Request::option('accessibility', 'PRIVATE'));
        $event->setPriority(Request::int('priority', 0));
        
        if (!$event->getTitle()) {
            $messages[] = _('Es muss eine Zusammenfassung angegeben werden.');
        }
        
        $rec_type = Request::option('recurrence', 'single');
        $expire = Request::option('exp_c', 'never');
        $rrule = array(
            'linterval' => null,
            'sinterval' => null,
            'wdays' => null,
            'month' => null,
            'day' => null,
            'rtype' => 'SINGLE',
            'count' => null,
            'expire' => null
        );
        if ($expire == 'count') {
            $rrule['count'] = Request::int('exp_count', 10);
        } else if ($expire == 'date') {
            if (Request::isXhr()) {
                $exp_date = studip_utf8decode(Request::get('exp_date'));
            } else {
                $exp_date = Request::get('exp_date');
            }
            $exp_date = $exp_date ?: strftime('%x', time());
            $rrule['expire'] = $this->parseDateTime($exp_date . ' 12:00');
        }
        switch ($rec_type) {
            case 'daily':
                if (Request::option('type_daily', 'day') == 'day') {
                    $rrule['linterval'] = Request::int('linterval_d', 1);
                    $rrule['rtype'] = 'DAILY';
                } else {
                    $rrule['linterval'] = 1;
                    $rrule['wdays'] = '12345';
                    $rrule['rtype'] = 'WEEKLY';
                }
                break;
            case 'weekly':
                $rrule['linterval'] = Request::int('linterval_w', 1);
                $rrule['wdays'] = implode('', Request::intArray('wdays',
                        array(strftime('%u', $event->getStart()))));
                $rrule['rtype'] = 'WEEKLY';
                break;
            case 'monthly':
                if (Request::option('type_m', 'day') == 'day') {
                    $rrule['linterval'] = Request::int('linterval_m1', 1);
                    $rrule['day'] = Request::int('day_m',
                            strftime('%e', $event->getStart()));
                    $rrule['rtype'] = 'MONTHLY';
                } else {
                    $rrule['linterval'] = Request::int('linterval_m2', 1);
                    $rrule['sinterval'] = Request::int('sinterval_m', 1);
                    $rrule['wdays'] = Request::int('wday_m',
                            strftime('%u', $event->getStart()));
                    $rrule['rtype'] = 'MONTHLY';
                }
                break;
            case 'yearly':
                if (Request::option('type_y', 'day') == 'day') {
                    $rrule['linterval'] = 1;
                    $rrule['day'] = Request::int('day_y',
                            strftime('%e', $event->getStart()));
                    $rrule['month'] = Request::int('month_y1',
                            date('n', $event->getStart()));
                    $rrule['rtype'] = 'YEARLY';
                } else {
                    $rrule['linterval'] = 1;
                    $rrule['sinterval'] = Request::int('sinterval_y', 1);
                    $rrule['wdays'] = Request::int('wday_y',
                            strftime('%u', $event->getStart()));
                    $rrule['month'] = Request::int('month_y2',
                            date('n', $event->getStart()));
                    $rrule['rtype'] = 'YEARLY';
                }
                break;
        }
        if (sizeof($messages)) {
            PageLayout::postMessage(MessageBox::error(_('Bitte Eingaben korrigieren'), $messages));
            return false;
        } else {
            $event->setRecurrence($rrule);
            $exceptions = array_diff(Request::getArray('exc_dates'),
                    Request::getArray('del_exc_dates'));
            $event->setExceptions($this->parseExceptions($exceptions));
            // if this is a group event, store event in the calendars of each attendee
            if (get_config('CALENDAR_GROUP_ENABLE')) {
                $attendee_ids = Request::optionArray('attendees');
                return $calendar->storeEvent($event, $attendee_ids);
            } else {
                return $calendar->storeEvent($event);
            }
        }
    }
    
    protected function parseExceptions($exc_dates) {
        $matches = array();
        $dates = array();
        preg_match_all('%(\d{1,2})\h*([/.])\h*(\d{1,2})\h*([/.])\h*(\d{4})\s*%',
                implode(' ', $exc_dates), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ($match[2] == '/') {
                $dates[] = strtotime($match[1].'/'.$match[3].'/'.$match[5]);
            } else {
                $dates[] = strtotime($match[1].$match[2].$match[3].$match[4].$match[5]);
            }
        }
        return $dates;
    }
    
    protected function parseDateTime($dt_string)
    {
        $dt_array = date_parse_from_format('j.n.Y H:i:s', $dt_string);
        return mktime($dt_array['hour'], $dt_array['minute'], $dt_array['second'],
                $dt_array['month'], $dt_array['day'], $dt_array['year']);
    }
    
}