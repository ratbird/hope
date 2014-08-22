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
        if ($GLOBALS['perm']->have_perm('admin')) $inst_mode = true;
        $my_schedule_settings = $GLOBALS['user']->cfg->SCHEDULE_SETTINGS;
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

        // try to find the correct institute-id
        $institute_id = Request::option('institute_id',
            $SessSemName[1] ? $SessSemName[1] :
            Request::option('cid', false));

        if (!$institute_id) {
            $institute_id = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;
        }

        if (!$institute_id || (in_array(get_object_type($institute_id), words('inst fak')) === false)) {
            throw new Exception(sprintf(_('Kann Einrichtungskalendar nicht anzeigen!'
                . 'Es wurde eine ungültige Instituts-Id übergeben (%s)!', $institute_id)));
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

        $this->controller = $this;
        $this->calendar_view = new CalendarWeekView($this->entries, 'instschedule');
        $this->calendar_view->setHeight(40 + (20 * Request::int('zoom', 0)));
        $this->calendar_view->setRange($my_schedule_settings['glb_start_time'], $my_schedule_settings['glb_end_time']);
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
     * @param string $start the start time of the group, e.g. "1000"
     * @param string $end   the end time of the group, e.g. "1200"
     * @param string $seminars  the IDs of the courses
     * @param string $day  numeric day to show
     *
     * @return void
     */
    function groupedentry_action($start, $end, $seminars, $day)
    {
        $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');

        // strucutre of an id: seminar_id-cycle_id
        // we do not need the cycle id here, so we trash it.
        $seminar_list = array();

        foreach (explode(',', $seminars) as $seminar) {
            $zw = explode('-', $seminar);
            $this->seminars[$zw[0]] = Seminar::getInstance($zw[0]);
        }

        $this->start = substr($start, 0, 2) .':'. substr($start, 2, 2);
        $this->end   = substr($end, 0, 2) .':'. substr($end, 2, 2);

        $day_names  = array(_("Montag"),_("Dienstag"),_("Mittwoch"),
            _("Donnerstag"),_("Freitag"),_("Samstag"),_("Sonntag"));

        $this->day   = $day_names[(int)$day];

        $this->render_template('calendar/instschedule/_entry_details');
    }
}
