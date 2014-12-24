<?php
/*
 * This is the controller for the single calendar view
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/calendar/Calendar.php';
require_once 'app/models/calendar/SingleCalendar.php';

class Calendar_SingleController extends AuthenticatedController
{

    private $calendar_settings = array();

    function __construct($dispatcher) {
        parent::__construct($dispatcher);


    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->calendar_settings = (array) UserConfig::get($GLOBALS['user']->id)->getValue('CALENDAR_SETTINGS');
        URLHelper::bindLinkParam('atime', $this->atime);
        $this->atime = Request::int('atime', time());
        $this->category = Request::int('category');
        $this->action = $action;
        if ($this->category) {
            URLHelper::bindLinkParam('category', $this->category);
        }
    }

    private function createSidebar($active = 'week')
    {
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Kalender'));
        $sidebar->setImage('sidebar/schedule-sidebar.png');
        $views = new ViewsWidget();
        $views->addLink(_('Tagesansicht'), $this->url_for('calendar/single/day'))->setActive($active == 'day');
        $views->addLink(_('Wochenansicht'), $this->url_for('calendar/single/week'))->setActive($active == 'week');
        $views->addLink(_('Monatsansicht'), $this->url_for('calendar/single/month'))->setActive($active == 'month');
        $views->addLink(_('Jahresansicht'), $this->url_for('calendar/single/year'))->setActive($active == 'year');
        $views->addLink(_('Exportieren'), $this->url_for('calendar/single/export'))->setActive($active == 'export');
        $sidebar->addWidget($views);
        $actions = new ActionsWidget();
        $actions->addLink(_('Termin anlegen'), $this->url_for('calendar/single/edit'), 'icons/16/blue/add.png');
        $sidebar->addWidget($actions);
    }

    private function createSidebarFilter($calendar)
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

        if (Config::get()->getValue('CALENDAR_GROUP_ENABLE')) {
            $tmpl = $tmpl_factory->open('calendar/single/_select_calendar');
            $tmpl->calendar_id = $calendar->getId();
            $tmpl->action_url = $this->url_for('calendar/single/switch');
            $tmpl->view = $this->action;
            $filters->addElement(new WidgetElement($tmpl->render()));
        }

        Sidebar::get()->addWidget($filters);
    }

    public function index_action()
    {
        // switch to the view the user has selected in his personal settings
        $default_view = $this->calendar_settings['view'] ?: 'week';
        $this->redirect('calendar/single/'
                . $default_view);
    }

    public function day_action($range_id = null)
    {

        $range_id = $range_id ?: $GLOBALS['user']->id;

        $this->atime = Request::int('atime', time());

        $calendar = SingleCalendar::getDayCalendar($range_id, $this->atime);

        PageLayout::setTitle(
                _("Mein persönlicher Terminkalender - Tagesansicht"));
        Navigation::activateItem("/calendar/calendar");

        $at = date('G', $this->atime);
        if ($at >= $this->calendar_settings['start']
                && $at <= $this->calendar_settings['end'] || !$this->atime) {
            $st = $this->calendar_settings['start'];
            $et = $this->calendar_settings['end'];
        } elseif ($at < $this->calendar_settings['start']) {
            $st = 0;
            $et = $this->calendar_settings['start'] + 2;
        } else {
            $st = $this->calendar_settings['end'] - 2;
            $et = 23;
        }

        $this->calendar = $calendar;
        $this->start = $st * 3600;
        $this->end = $et * 3600;
        $this->step = $this->calendar_settings['step_day'];

        $this->createSidebar('day');
        $this->createSidebarFilter($this->calendar);
    }


    /**
    * @todo der include muss weg
    */
    public function week_action($range_id = null)
    {
        $range_id = $range_id ?: $GLOBALS['user']->id;

        $this->atime = Request::int('atime', time());

        $timestamp = mktime(12, 0, 0, date('n', $this->atime),
                date('j', $this->atime), date('Y', $this->atime));
        $monday = $timestamp - 86400 * (strftime("%u", $timestamp) - 1);
        $day_count = $this->calendar_settings['type_week'] == 'SHORT' ? 5 : 7;

        for ($i = 0; $i < $day_count; $i++) {
            $this->calendars[$i] =
                    SingleCalendar::getDayCalendar($range_id, $monday + $i * 86400);
        }

        PageLayout::setTitle(
                _("Mein persönlicher Terminkalender - Wochenansicht"));
        Navigation::activateItem("/calendar/calendar");


        $at = date('G', $this->atime);
        if ($at >= $this->calendar_settings['start']
                && $at <= $this->calendar_settings['end'] || !$this->atime) {
            $st = $this->calendar_settings['start'];
            $et = $this->calendar_settings['end'];
        } elseif ($at < $this->calendar_settings['start']) {
            $st = 0;
            $et = $this->calendar_settings['start'] + 2;
        } else {
            $st = $this->calendar_settings['end'] - 2;
            $et = 23;
        }

        $this->start = $st;
        $this->end = $et;
        $this->step = $this->calendar_settings['step_week'];

        $this->createSidebar('week');
        $this->createSidebarFilter($this->calendars[0]);
    }

    public function month_action($range_id = null)
    {
        $range_id = $range_id ?: $GLOBALS['user']->id;

        $this->atime = Request::int('atime', time());

        $month_start = mktime(12, 0, 0, date('n', $this->atime), 1, date('Y', $this->atime));
        $month_end = mktime(12, 0, 0, date('n', $this->atime), date('t', $this->atime), date('Y', $this->atime));
        $adow = strftime('%u', $month_start) - 1;
        $cor = date('n', $this->atime) == 3 ? 1 : 0;
        $this->first_day = $month_start - $adow * 86400;
        $this->last_day = ((42 - ($adow + date('t', $this->atime))) % 7 + $cor) * 86400 + $month_end;
        
        for ($start_day = $this->first_day; $start_day <= $this->last_day; $start_day += 86400) {
            $this->calendars[] = SingleCalendar::getDayCalendar($range_id, $start_day);
        }

        PageLayout::setTitle(
                _("Mein persönlicher Terminkalender - Monatsansicht"));
        Navigation::activateItem("/calendar/calendar");

        $this->createSidebar('month');
        $this->createSidebarFilter($this->calendars[0]);
    }

    public function year_action($range_id = null)
    {
        $range_id = $range_id ?: $GLOBALS['user']->id;

        $this->atime = Request::int('atime', time());

        $start = mktime(0, 0, 0, 1, 1, date('Y', $this->atime));
        $end = mktime(23, 59, 59, 12, 31, date('Y', $this->atime));
        $this->calendar = new SingleCalendar($range_id, $start, $end);
        $this->count_list = $this->calendar->getListCountEvents();
        

        PageLayout::setTitle(
                _("Mein persönlicher Terminkalender - Jahresansicht"));
        Navigation::activateItem("/calendar/calendar");

        $this->createSidebar('year');
        $this->createSidebarFilter($this->calendar);
    }

    public function switch_action()
    {
        $view = Request::option('view', 'week');
        $range_id = Request::option('range', $GLOBALS['user']->id);
        $object_type = get_object_type($range_id);
        switch ($object_type) {
            case 'user':
                $this->redirect('calendar/single/' . $view . '/' . $range_id);
                break;

            case 'sem':
                break;

            case 'inst':
            case 'fak':
                break;
        }
    }

    public function jump_to_action()
    {
        $atime = strtotime(Request::get('jmp_date', 'now'));
        $action = Request::option('action', 'week');
        $this->redirect('calendar/single/' . $action . '?atime=' . $atime);
    }
    
    public function event_action($event_id = null)
    {

    }

    public function seminar_events_action()
    {

    }

    public function showexport_action()
    {

    }

    public function export_action()
    {

    }
    
}