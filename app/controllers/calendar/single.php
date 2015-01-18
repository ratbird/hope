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
require_once 'lib/calendar/CalendarImportFile.class.php';
require_once 'lib/calendar/CalendarParserICalendar.class.php';
require_once 'lib/calendar/CalendarExportFile.class.php';
require_once 'lib/calendar/CalendarWriterICalendar.class.php';
require_once 'app/models/ical_export.php';

class Calendar_SingleController extends AuthenticatedController
{

    private $calendar_settings = array();

    function __construct($dispatcher) {
        parent::__construct($dispatcher);
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->calendar_settings = UserConfig::get($GLOBALS['user']->id)->getValue('CALENDAR_SETTINGS');
        if (!is_array($this->calendar_settings)) {
            $this->calendar_settings = Calendar::getDefaultUserSettings();
        }
        URLHelper::bindLinkParam('atime', $this->atime);
        $this->atime = Request::int('atime', time());
        $this->category = Request::int('category');
        $this->range_id = Request::option('range_id', $GLOBALS['user']->id);
        $this->last_view = Request::option('last_view',
                $this->calendar_settings['view']);
        $this->action = $action;
        if ($this->category) {
            URLHelper::bindLinkParam('category', $this->category);
        }
        if ($this->range_id) {
            URLHelper::bindLinkParam('range_id', $this->range_id);
        }
        URLHelper::bindLinkParam('last_view', $this->last_view);
        Navigation::activateItem('/calendar/calendar');
    }

    private function createSidebar($active = 'week')
    {
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Kalender'));
        $sidebar->setImage('sidebar/schedule-sidebar.png');
        $views = new ViewsWidget();
        $views->addLink(_('Tag'), $this->url_for('calendar/single/day'))
                ->setActive($active == 'day');
        $views->addLink(_('Woche'), $this->url_for('calendar/single/week'))
                ->setActive($active == 'week');
        $views->addLink(_('Monat'), $this->url_for('calendar/single/month'))
                ->setActive($active == 'month');
        $views->addLink(_('Jahr'), $this->url_for('calendar/single/year'))
                ->setActive($active == 'year');
        $sidebar->addWidget($views);
        $actions = new ActionsWidget();
        $actions->addLink(_('Termin anlegen'),
                $this->url_for('calendar/single/edit'), 'icons/16/blue/add.png',
                array('data-dialog' => ''));
        $sidebar->addWidget($actions);
        $export = new ExportWidget();
        $export->addLink(_('Termine exportieren'),
                $this->url_for('calendar/single/export_calendar'),
                'icons/16/blue/download.png', array('data-dialog' => ''))
                ->setActive($active == 'export_calendar');
        $export->addLink(_('Termine importieren'),
                $this->url_for('calendar/single/import'),
                'icons/16/blue/upload.png', array('data-dialog' => ''))
                ->setActive($active == 'import');
        $export->addLink(_('Kalender teilen'),
                $this->url_for('calendar/single/share'),
                'icons/16/blue/group2.png', array('data-dialog' => ''))
                ->setActive($active == 'share');
        $sidebar->addWidget($export);
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
            $tmpl->range_id = $calendar->getId();
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
        $this->redirect($this->url_for('calendar/single/' . $default_view));
    }

    public function day_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = SingleCalendar::getDayCalendar($this->range_id, $this->atime);

        PageLayout::setTitle($this->getTitle($this->calendar)
                . ' - ' . _('Tagesansicht'));
        Navigation::activateItem("/calendar/calendar");

        $this->last_view = 'day';
        $this->settings = $this->calendar_settings;
        
        $this->createSidebar('day');
        $this->createSidebarFilter($this->calendar);
    }
    
    public function week_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $timestamp = mktime(12, 0, 0, date('n', $this->atime),
                date('j', $this->atime), date('Y', $this->atime));
        $monday = $timestamp - 86400 * (strftime("%u", $timestamp) - 1);
        $day_count = $this->calendar_settings['type_week'] == 'SHORT' ? 5 : 7;
        for ($i = 0; $i < $day_count; $i++) {
            $this->calendars[$i] =
                    SingleCalendar::getDayCalendar($this->range_id, $monday + $i * 86400);
        }
        
        PageLayout::setTitle($this->getTitle($this->calendars[0])
                . ' - ' . _('Wochenansicht'));
        Navigation::activateItem("/calendar/calendar");

        $this->last_view = 'week';
        $this->settings = $this->calendar_settings;
        
        $this->createSidebar('week');
        $this->createSidebarFilter($this->calendars[0]);
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
        for ($start_day = $this->first_day; $start_day <= $this->last_day; $start_day += 86400) {
            $this->calendars[] = SingleCalendar::getDayCalendar($this->range_id, $start_day);
        }

        PageLayout::setTitle($this->getTitle($this->calendars[0])
                . ' - ' . _('Monatsansicht'));

        $this->last_view = 'month';
        $this->createSidebar('month');
        $this->createSidebarFilter($this->calendars[0]);
    }

    public function year_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $start = mktime(0, 0, 0, 1, 1, date('Y', $this->atime));
        $end = mktime(23, 59, 59, 12, 31, date('Y', $this->atime));
        $this->calendar = new SingleCalendar($this->range_id, $start, $end);
        $this->count_list = $this->calendar->getListCountEvents();
        
        PageLayout::setTitle($this->getTitle($this->calendar)
                . ' - ' . _('Jahresansicht'));
        Navigation::activateItem("/calendar/calendar");

        $this->last_view = 'year';
        $this->createSidebar('year');
        $this->createSidebarFilter($this->calendar);
    }

    public function switch_action()
    {
        $view = Request::option('view', 'week');
        $this->range_id = Request::option('range_id', $GLOBALS['user']->id);
        $object_type = get_object_type($this->range_id);
        switch ($object_type) {
            case 'user':
            case 'sem':
            case 'inst':
            case 'fak':
                $this->redirect($this->url_for('calendar/single/' . $view . '/' . $this->range_id));
                break;
        }
    }

    public function jump_to_action()
    {
        $atime = strtotime(Request::get('jmp_date', 'now'));
        $action = Request::option('action', 'week');
        $this->range_id = $this->range_id ?: $GLOBALS['user']->id;
        $this->redirect($this->url_for('calendar/single/' . $action,
                array('atime' => $atime, 'range_id' => $this->range_id)));
    }
    
    public function edit_action($range_id = null, $event_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = new SingleCalendar($this->range_id);
        $this->event = $this->calendar->getEvent($event_id);
        
        if ($this->event->isNew()) {
            $this->event = $this->calendar->getNewEvent();
            $this->event->setStart($this->atime);
            $this->event->setEnd($this->atime + 3600);
            $this->event->setAuthor_id($GLOBALS['user']->id);
            $this->event->setEditor_id($GLOBALS['user']->id);
            PageLayout::setTitle($this->getTitle($this->calendar)
                    . ' - ' . _('Neuer Termin'));
        } else {
            PageLayout::setTitle($this->getTitle($this->calendar)
                    . ' - ' . _('Termin bearbeiten'));
        }
        
        $stored = false;
        if (Request::submitted('store')) {
            $stored = $this->storeEventData($this->event);
        }

        if ($stored) {
            PageLayout::postMessage(MessageBox::success(_('Der Termin wurde gespeichert.')));
            $this->relocate('calendar/single/' . $this->last_view, array('atime' => $this->atime));
        }
        
        $this->createSidebar('event');
        $this->createSidebarFilter($this->calendar);
    }
    
    public function delete_action($range_id, $event_id)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = new SingleCalendar($this->range_id);
        if ($this->calendar->deleteEvent($event_id)) {
            PageLayout::postMessage(MessageBox::success(_('Der Termin wurde gelöscht.')));
        }
        $this->redirect($this->url_for('calendar/single/' . $this->last_view));
    }
    
    public function export_event_action($event_id, $range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $calendar = new SingleCalendar($this->range_id);
        $event = $calendar->getEvent($event_id);
        if (!$event->isNew()) {
            $export = new CalendarExportFile(new CalendarWriterICalendar());
            $export->exportFromObjects($exp_event);
            $export->sendFile();
        }
        $this->render_nothing();
    }
    
    public function export_calendar_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = new SingleCalendar($this->range_id);
        
        if (Request::submitted('export')) {
            $export = new CalendarExportFile(new CalendarWriterICalendar());
            if (Request::get('event_type') == 'user') {
                $types = array('CalendarEvent');
            } else if (Request::get('event_type') == 'course') {
                $types = array('CourseEvent', 'CourseCancelledEvent');
            } else {
                $types = array('CalendarEvent', 'CourseEvent', 'CourseCancelledEvent');
            }
            if (Request::get('export_time') == 'date') {
                $exstart = $this->parseDateTime(Request::get('export_start'));
                $exend = $this->parseDateTime(Request::get('export_end'));
            } else {
                $exstart = 0;
                $exend = Calendar::CALENDAR_END;
            }
            $export->exportFromDatabase($this->calendar->getRangeId(), $exstart,
                    $exend, $types);
            $export->sendFile();
            $this->render_nothing();
            exit;
        }
        
        PageLayout::setTitle($this->getTitle($this->calendar)
                . ' - ' . _('Kalender exportieren'));

        $this->createSidebar('export_calendar');
        $this->createSidebarFilter($this->calendar);
    }
    
    public function import_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = new SingleCalendar($this->range_id);
        
        if ($this->calendar->havePermission(Calendar::PERMISSION_OWN)) {
            if (Request::submitted('import')) {
                $this->checkTicket();
                $import = new CalendarImportFile(new CalendarParserICalendar(),
                        $_FILES['importfile']);
                if (Request::get('import_as_private_imp')) {
                    $import->changePublicToPrivate();
                }
                $import_count = $import->getCount();
                PageLayout::postMessage(MessageBox::success(
                        sprintf('Es wurden %s Termine importiert.', $import_count)));
                $this->redirect($this->url_for('calendar/single/' . $this->last_view));
            }
        }
        
        $this->createSidebar('import');
        $this->createSidebarFilter($this->calendar);
    }
    
    public function share_action($range_id = null)
    {
        $this->range_id = $range_id ?: $this->range_id;
        $this->calendar = new SingleCalendar($this->range_id);
        
        $this->short_id = null;
        if ($this->calendar->havePermission(Calendar::PERMISSION_OWN)) {
            if (Request::submitted('delete_id')) {
                $this->checkTicket();
                IcalExport::deleteKey($GLOBALS['user']->id);
                PageLayout::postMessage(MessageBox::success(
                        _('Die Adresse, unter der Ihre Termine abrufbar sind, wurde gelöscht')));
            }
            
            if (Request::submitted('new_id')) {
                $this->checkTicket();
                $this->short_id = IcalExport::setKey($GLOBALS['user']->id);
                PageLayout::postMessage(MessageBox::success(
                        _('Eine Adresse, unter der Ihre Termine abrufbar sind, wurde erstellt.')));
            } else {
                $this->short_id = IcalExport::getKeyByUser($GLOBALS['user']->id);
            }

            if (Request::submitted('submit_email')) {
                $email_reg_exp = '/^([-.0-9=?A-Z_a-z{|}~])+@([-.0-9=?A-Z_a-z{|}~])+\.[a-zA-Z]{2,6}$/i';
                if (preg_match($email_reg_exp, Request::get('email')) !== 0) {
                    $subject = '[' . get_config('UNI_NAME_CLEAN') . ']' . _('Exportadresse für Ihre Termine');
                    $text .= _("Diese Email wurde vom Stud.IP-System verschickt. Sie können
            auf diese Nachricht nicht antworten.") . "\n\n";
                    $text .= _('Über diese Adresse erreichen Sie den Export für Ihre Termine:') . "\n\n";
                    $text .= $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/ical/index/'
                            . IcalExport::getKeyByUser($GLOBALS['user']->id);
                    StudipMail::sendMessage(Request::get('email'), $subject, $text);
                    PageLayout::postMessage(MessageBox::success(_('Die Adresse wurde verschickt!')));
                } else {
                    PageLayout::postMessage(MessageBox::error(_('Bitte geben Sie eine gültige Email-Adresse an.')));
                }
                $this->short_id = IcalExport::getKeyByUser($GLOBALS['user']->id);
            }
        }
        PageLayout::setTitle($this->getTitle($this->calendar)
                    . ' - ' . _('Kalender teilen oder einbetten'));
        
        $this->createSidebar('share');
        $this->createSidebarFilter($this->calendar);
    }
    
    /**
     * Validate ticket
     * 
     * @throws InvalidArgumentException if ticket is not valid
     */
    private function checkTicket()
    {
        if (!check_ticket(Request::option('studip_ticket'))) {
            throw new InvalidArgumentException(_('Das Ticket für diese Aktion ist ungültig.'));
        }
    }
    
    private function storeEventData(CalendarEvent $event)
    {
        $dt_string = Request::get('start_date') . ' ' . Request::int('start_hour')
                . ':' . Request::int('start_minute');
        $event->setStart($this->parseDateTime($dt_string));
        $dt_string = Request::get('end_date') . ' ' . Request::int('end_hour')
                . ':' . Request::int('end_minute');
        $event->setEnd($this->parseDateTime($dt_string));
        if ($event->getStart() > $event->getEnd()) {
            $messages[] = _('Die Startzeit muss vor der Endzeit liegen.');
        }
        $event->setTitle(Request::get('summary', _('Kein Titel')));
        if (!$event->getTitle()) {
            $messages[] = _('Es muss eine Zusammenfassung angegeben werden.');
        }
        
        $event->event->description = Request::get('description', '');
        $event->event->category_intern = Request::int('category_intern', 1);
        $event->setUserDefinedCategories(Request::get('categories', ''));
        $event->event->location = Request::get('location', '');
        $event->setAccessibility(Request::option('accessibility', 'PRIVATE'));
        $event->setPriority(Request::int('priority', 0));
        
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
            'expire' => Calendar::CALENDAR_END
        );
        if ($expire == 'count') {
            $rrule['count'] = Request::int('exp_count', 10);
        } else if ($expire == 'date') {
            $exp_date = Request::int('exp_date', strftime('%x', time()));
            $rrule['expire'] = $this->parseDateTime($exp_date . ' 12:00');
        }
        switch ($rec_type) {
            case 'daily':
                if (Request::option('type_daily', 'day') == 'day') {
                    $rrule = array(
                        'linterval' => Request::int('linterval_d', 1),
                        'rtype' => 'DAILY'
                    );
                } else {
                    $rrule = array(
                        'linterval' => 1,
                        'wdays' => '12345',
                        'rtype' => 'WEEKLY'
                    );
                }
                break;
            case 'weekly':
                $rrule = array(
                    'linterval' => Request::int('linterval_w', 1),
                    'wdays' => implode('', Request::intArray('wdays',
                            array(strftime('%u', $event->getStart())))),
                    'rtype' => 'WEEKLY'
                );
                break;
            case 'monthly':
                if (Request::option('type_m', 'day') == 'day') {
                    $rrule = array(
                        'linterval' => Request::int('linterval_m1', 1),
                        'day' => Request::int('day_m',
                                strftime('%e', $event->getStart())),
                        'rtype' => 'MONTHLY'
                    );
                } else {
                    $rrule = array(
                        'linterval' => Request::int('linterval_m2', 1),
                        'sinterval' => Request::int('sinterval_m', 1),
                        'wdays' => Request::int('wday_m',
                                strftime('%u', $event->getStart())),
                        'rtype' => 'MONTHLY'
                    );
                }
                break;
            case 'yearly':
                if (Request::option('type_y', 'day') == 'day') {
                    $rrule = array(
                        'linterval' => 1,
                        'day' => Request::int('day_y',
                                strftime('%e', $event->getStart())),
                        'month' => Request::int('month_y1',
                                date('n', $event->getStart())),
                        'rtype' => 'YEARLY'
                    );
                } else {
                    $rrule = array(
                        'linterval' => 1,
                        'sinterval' => Request::int('sinterval_y', 1),
                        'wdays' => Request::int('wday_y',
                                strftime('%u', $event->getStart())),
                        'month' => Request::int('month_y2',
                                date('n', $event->getStart())),
                        'rtype' => 'YEARLY'
                    );
                }
                break;
        }
        if (sizeof($messages)) {
            PageLayout::postMessage(MessageBox::error(_('Bitte Eingaben korrigieren'), $messages));
            return null;
        } else {
            $event->setRecurrence($rrule);
            $event->setExceptions($this->parseExceptions(Request::get('exc_dates', '')));
            return $event->store();
        }
    }
    
    private function parseExceptions($exc_dates) {
        $matches = array();
        $dates = array();
        preg_match_all('%(\d{1,2})\h*([/.])\h*(\d{1,2})\h*([/.])\h*(\d{4})\s*%',
                $exc_dates, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ($match[2] == '/') {
                $dates[] = DateTime::createFromFormat('n/j/Y H:i',
                        $match[1].'/'.$match[3].'/'.$match[5].' 12:00');
            } else {
                $dates[] = DateTime::createFromFormat('n#j#Y H:i',
                        $match[1].$match[2].$match[3].$match[4].$match[5].' 12:00');
            }
        }
        return implode(',', $dates);
    }
    
    private function parseDateTime($dt_string)
    {
        $dt_array = date_parse_from_format('j.n.Y H:i', $dt_string);
        return mktime($dt_array['hour'], $dt_array['minute'], 0, $dt_array['month'],
                $dt_array['day'], $dt_array['year']);
    }
    
    private function getTitle(SingleCalendar $calendar)
    {
        $title = '';
        if ($calendar->getRangeId() == $GLOBALS['user']->id) {
            $title = _('Mein persönlicher Terminkalender');
        } else if ($calendar->getRange() == Calendar::RANGE_USER) {
            $title = sprintf(_('Terminkalender von %s'),
                    $calendar->range_object->getFullname());
        } else {
            $title = getHeaderLine($calendar->getRangeId());
        }
        return $title;
    }
    
}