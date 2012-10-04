<?php
# Lifter010: TODO

/*
 * This controller displays an institute-calendar for seminars
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/calendar/instschedule.php';
require_once 'lib/calendar/CalendarWeekView.class.php';
require_once 'lib/classes/SemesterData.class.php';

/**
 * Controller of the institutes' schedules.
 * *
 * @since      2.0
 */
class Calendar_InstscheduleController extends AuthenticatedController
{
    /**
     * this action is the main action of the schedule-controller, setting the environment for the timetable,
     * accepting a comma-separated list of days.
     *
     * @param  string  a list of an arbitrary mix of the numbers 0-6, separated with a comma (e.g. 1,2,3,4,5 (for Monday to Friday, the default))
     */
    function index_action($days = false)
    {
        $my_schedule_settings = json_decode(UserConfig::get($user->id)->__get('my_schedule_settings'),true);
        $my_schedule_settings = $this->check_schedule_default($my_schedule_settings);

        if ($GLOBALS['perm']->have_perm('admin')) $inst_mode = true;

        // try to find the correct institute-id
        $institute_id = Request::option('cid', $SessSemName[1]);

        // set the days to be displayed
        if ($days === false) {
            if (Request::getArray('days')) {
                $this->days = array_keys(Request::getArray('days'));
            } else {
                $this->days = array(0,1,2,3,4,5,6);
            }
        } else {
            $this->days = explode(',', $days);
        }
        
        if (!$institute_id) {
            $institute_id = $GLOBALS['_my_admin_inst_id'] 
                          ? $GLOBALS['_my_admin_inst_id'] 
                          : $my_schedule_settings["glb_inst_id"];

            if (!$my_schedule_settings["glb_inst_id"]["glb_inst_id"]) {
               $my_schedule_settings["glb_inst_id"] = $GLOBALS['_my_admin_inst_id'];
            }

            $myschedule = true;
        }

        if (!$institute_id || (in_array(get_object_type($institute_id), words('inst fak')) === false)) {
            throw new Exception(sprintf_('Kann Einrichtungskalendar nicht anzeigen!'
                . 'Es wurde eine ungültige Instituts-Id übergeben (%s)!', $institute_id));
        }

        // load semester-data and current semester
        $semdata = new SemesterData();
        $this->semesters = $semdata->getAllSemesterData();

        if (Request::option('semester_id')) {
            $this->current_semester = $semdata->getSemesterData(Request::option('semester_id'));
        } else {
            $this->current_semester = $semdata->getCurrentSemesterData();
        }

        $this->entries = (array)CalendarInstscheduleModel::getInstituteEntries($GLOBALS['user']->id,
            $this->current_semester, 8, 20, $institute_id, $this->days);

        Navigation::activateItem('/course/main/schedule');
        PageLayout::setHelpKeyword('Basis.TerminkalenderStundenplan');
        PageLayout::setTitle($GLOBALS['SessSemName']['header_line'].' - '._('Veranstaltungs-Stundenplan'));

        // have we chosen an entry to display?
        if ($this->flash['entry']) {
            $this->show_entry = $this->flash['entry'];
        }

        $this->controller = $this;
        $this->calendar_view = new CalendarWeekView($this->entries, 'instschedule');
        $this->calendar_view->setHeight(40 + (20 * Request::int('zoom', 0)));
        $this->calendar_view->setRange($my_schedule_settings['glb_start_time'], $my_schedule_settings['glb_end_time']);
        $this->calendar_view->setReadOnly();
        $this->calendar_view->groupEntries();  // if enabled, group entries with same start- and end-date


        $style_parameters = array(
            'whole_height' => $this->calendar_view->getOverallHeight(),
            'entry_height' => $this->calendar_view->getHeight()
        );

        $factory = new Flexi_TemplateFactory($this->dispatcher->trails_root . '/views');
        PageLayout::addStyle($factory->render('calendar/stylesheet', $style_parameters));

        if (Request::option('printview')) {
            PageLayout::addStylesheet('print.css');
        } else {
            PageLayout::addStylesheet('print.css', array('media' => 'print'));
        }
    }

    /**
     * Returns an HTML fragment of a grouped entry in the schedule of an institute.
     *
     * @param string  the start time of the group, e.g. "1000"
     * @param string  the end time of the group, e.g. "1200"
     * @param string  the IDs of the courses
     * @param string  true if this is an Ajax request
     * @return void
     */
    function groupedentry_action($start, $end, $seminars, $ajax = false)
    {
        // strucutre of an id: seminar_id-cycle_id
        // we do not need the cycle id here, so we trash it. 
        $seminar_list = array();

        foreach (explode(',', $seminars) as $seminar) {
            $zw = explode('-', $seminar);
            $seminar_list[] = $zw[0];
        }

        $this->show_entry = array(
            'type'     => 'inst',
            'seminars' => $seminar_list,
            'start'    => $start,
            'end'      => $end
        );

        if ($ajax) {
            $this->render_template('calendar/instschedule/_entry_details');
        } else {
            if (Request::option('show_hidden')) {
                $this->flash['show_hidden'] = true;
            }

            $this->flash['entry'] = $this->show_entry;
            $this->redirect('calendar/instschedule/');
        }
    }
    function check_schedule_default($my_schedule_settings) {

        if (!$my_schedule_settings ||
            $my_schedule_settings['glb_start_time'] === NULL ||
            $my_schedule_settings['glb_end_time'] === NULL ) {
            $my_schedule_settings=array(
            "glb_start_time"=>8,
            "glb_end_time"=>19,
            "glb_days"=>array(
                "mo"=>"TRUE",
                "di"=>"TRUE",
                "mi"=>"TRUE",
                "do"=>"TRUE",
                "fr"=>"TRUE",
                "sa"=>"",
                "so"=>""
            ),
            "default_setted"=>time()
            );
        }
        return $my_schedule_settings;
   }
}
