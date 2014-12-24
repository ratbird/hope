<?php
/*
 * This is the controller for the personal calendar
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

class Calendar_CalendarController extends AuthenticatedController
{
    
    private $calendar_settings = array();
    
    function __construct($dispatcher) {
        $this->calendar_settings = Config::get()->getValue('CALENDAR_SETTINGS');
   //     var_dump($this->calendar_settings); exit;
        parent::__construct($dispatcher);
    }
    
    public function index_action()
    {
        // switch to the view the user has selected in his personal settings
        $default_view = 
        $this->redirect('calendar/calendar/'
                . $this->calendar_settings['view']);
    }
    
    public function day_action($timestamp = null)
    {
        $this->calendar = Calendar::getInstance(Calendar::RANGE_USER,
                $GLOBALS['user']->id);
        PageLayout::setTitle(
                _("Mein persönlicher Terminkalender - Tagesansicht"));
        $_SESSION['calendar_sess_control_data']['view_prv'] = 'showday';
        Navigation::activateItem("/calendar/calendar/day");
        
        $user_config = (array) UserConfig::get($GLOBALS['user']->id)
                ->getValue('CALENDAR_SETTINGS');
        
        $this->atime = intval($timestamp) ?: time();
        
        $at = date('G', $this->atime);
        if ($at >= $user_config['start']
                && $at <= $user_config['end'] || !$atime) {
            $start_time = $user_config['start'];
            $end_time = $user_config['end'];
        } elseif ($at < $user_config['start']) {
            $start_time = 0;
            $end_time = $user_config['start'] + 2;
        } else {
            $start_time = $user_config['end'] - 2;
            $end_time = 23;
        }
        
        $this->view = new DbCalendarDay($this->calendar, $this->atime, NULL,
                Request::get('cal_restrict'),
                Calendar::getBindSeminare($this->calendar->getUserId()));
        
        $this->cmd = 'showday';
    }
    
    
    /**
    * @todo der include muss weg
    */
    public function week_action($timestamp = null)
    {
        
        
        

        $calendar = Calendar::getInstance(Calendar::RANGE_USER,
                $GLOBALS['user']->id);
        
        
        /*
        $calendar = new SingleCalendar($GLOBALS['user']->id);
        $calendar->range = Calendar::RANGE_USER;
        $calendar->user_name = get_username($GLOBALS['user']->id);
        $calendar->setPermission(Calendar::PERMISSION_OWN);
         * 
         */
        
        
        PageLayout::setTitle(
                _("Mein persönlicher Terminkalender - Wochenansicht"));
        $_SESSION['calendar_sess_control_data']['view_prv'] = 'showweek';
        Navigation::activateItem("/calendar/calendar/week");
        
        $atime = $timestamp ?: time();
        
        $at = date('G', $atime);
        if ($at >= $this->calendar_settings['start']
                && $at <= $this->calendar_settings['end'] || !$atime) {
            $st = $this->calendar_settings['start'];
            $et = $this->calendar_settings['end'];
        } elseif ($at < $this->calendar_settings['start']) {
            $st = 0;
            $et = $this->calendar_settings['start'] + 2;
        } else {
            $st = $this->calendar_settings['end'] - 2;
            $et = 23;
        }
        
        include_once($GLOBALS['RELATIVE_PATH_CALENDAR'] . '/lib/DbCalendarWeek.class.php');
        $this->_calendar = $calendar;
        $this->atime = $atime;
        $this->cmd = 'showweek';
        $this->st = $st;
        $this->et = $et;
    }
    
    function toStringWeek($week_time, $start_time, $end_time, $restrictions = NULL, $sem_ids = NULL)
    {

        $this->view = new DbCalendarWeek($this, $week_time,
                        $this->getUserSettings('type_week'), $restrictions, $sem_ids);

        $tmpl = $GLOBALS['template_factory']->open('calendar/week_table');
        $tmpl->calendar = $this;
        $tmpl->start = $start_time;
        $tmpl->end = $end_time;
        $tmpl->step = $this->getUserSettings('step_week');

        return $tmpl->render();
    }
    
    public function month_action($timestamp = null)
    {
        
    }
    
    public function year_action($timestamp = null)
    {
        
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