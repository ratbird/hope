<?php
# Lifter010: TODO

/*
 * This class displays a seminar-schedule for
 * users on a seminar-based view and for admins on an institute based view
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'app/models/calendar/schedule.php';

/**
 * Personal schedule controller.
 *
 * @since      2.0
 */
class Calendar_ScheduleController extends AuthenticatedController
{

    /**
     * Callback function being called before an action is executed. If this
     * function does not return FALSE, the action will be called, otherwise
     * an error will be generated and processing will be aborted. If this function
     * already #rendered or #redirected, further processing of the action is
     * withheld.
     *
     * @param string  Name of the action to perform.
     * @param array   An array of arguments to the action.
     *
     * @return bool
     */
    function before_filter(&$action, &$args) {
        global $user;

        parent::before_filter($action, $args);
        $zoom = Request::int('zoom');
        $this->my_schedule_settings = UserConfig::get($user->id)->SCHEDULE_SETTINGS;
        // bind zoom, show_hidden and semester_id for all actions, even preserving them after redirect
        if (isset($zoom)) {
            URLHelper::addLinkParam('zoom', Request::int('zoom'));
            $this->my_schedule_settings['zoom'] = Request::int('zoom');
            UserConfig::get($user->id)->store('SCHEDULE_SETTINGS', $this->my_schedule_settings);
        }

        URLHelper::bindLinkParam('semester_id', $this->current_semester['semester_id']);
        URLHelper::bindLinkParam('show_hidden', $this->show_hidden);

        PageLayout::setHelpKeyword('Basis.MyStudIPStundenplan');
        PageLayout::setTitle(_('Mein Stundenplan'));
    }

    /**
     * this action is the main action of the schedule-controller, setting the environment
     * for the timetable, accepting a comma-separated list of days.
     *
     * @param  string  $days  a list of an arbitrary mix of the numbers 0-6, separated
     *                        with a comma (e.g. 1,2,3,4,5 (for Monday to Friday, the default))
     * @return void
     */
    function index_action($days = false)
    {
        global $user;

        $schedule_settings = CalendarScheduleModel::getScheduleSettings();

        if ($GLOBALS['perm']->have_perm('admin')) $inst_mode = true;

        if ($inst_mode) {

            // try to find the correct institute-id
            $institute_id = Request::option('institute_id',
                            $SessSemName[1] ? $SessSemName[1] :
                            Request::option('cid', false));


            if (!$institute_id) {
                $institute_id = UserConfig::get($user->id)->MY_INSTITUTES_DEFAULT;
            }

            if (!$institute_id || !in_array(get_object_type($institute_id), words('fak inst'))) {
                throw new Exception('Cannot display institute-calender. No valid ID given!');
            }

            Navigation::activateItem('/browse/my_courses/schedule');
        } else {
            Navigation::activateItem('/calendar/schedule');
        }

        // check, if the hidden seminar-entries shall be shown
        $show_hidden = Request::int('show_hidden', 0);

        // load semester-data and current semester
        $semdata = new SemesterData();
        $this->semesters = array_reverse($semdata->getAllSemesterData());

        if (Request::option('semester_id')) {
            $this->current_semester = $semdata->getSemesterData(Request::option('semester_id'));
        } else {
            $this->current_semester = $semdata->getCurrentSemesterData();
        }

        // check type-safe if days is false otherwise sunday (0) cannot be chosen
        if ($days === false) {
            if (Request::getArray('days')) {
                $this->days = array_keys(Request::getArray('days'));
            } else {
                $this->days = $schedule_settings['glb_days'];
                foreach ($this->days as $key => $day_number) {
                    $this->days[$key] = ($day_number + 6) % 7;
                }
            }
        } else {
            $this->days = explode(',', $days);
        }

        $this->controller = $this;

        $this->calendar_view = $inst_mode
            ? CalendarScheduleModel::getInstCalendarView($institute_id, $show_hidden, $this->current_semester, $this->days)
            : CalendarScheduleModel::getUserCalendarView($GLOBALS['user']->id, $show_hidden, $this->current_semester, $this->days);;

        // have we chosen an entry to display?
        if ($this->flash['entry']) {
            if ($inst_mode) {
                $this->show_entry = $this->flash['entry'];
            } else if ($this->flash['entry']['id'] == null) {
                $this->show_entry = $this->flash['entry'];
            } else {
                foreach ($this->calendar_view->getColumns() as $entry_days) {
                    foreach ($entry_days->getEntries() as $entry) {
                        if ($this->flash['entry']['cycle_id']) {
                            if ($this->flash['entry']['id'] .'-'. $this->flash['entry']['cycle_id'] == $entry['id']) {
                                $this->show_entry = $entry;
                                $this->show_entry['id'] = reset(explode('-', $this->show_entry['id']));
                            }
                        } else {
                            if ($entry['id'] == $this->flash['entry']['id']) {
                                $this->show_entry = $entry;
                            }
                        }
                    }
                }
            }
        }

        $style_parameters = array(
            'whole_height' => $this->calendar_view->getOverallHeight(),
            'entry_height' => $this->calendar_view->getHeight()
        );

        $factory = new Flexi_TemplateFactory($this->dispatcher->trails_root . '/views');
        PageLayout::addStyle($factory->render('calendar/stylesheet', $style_parameters), 'screen, print');

        if (Request::option('printview')) {
            $this->calendar_view->setReadOnly();
            PageLayout::addStylesheet('print.css');
        } else {
            PageLayout::addStylesheet('print.css', array('media' => 'print'));
        }

        $this->show_hidden    = $show_hidden;

        $inst = get_object_name($institute_id, 'inst');
        $this->inst_mode      = $inst_mode;
        $this->institute_name = $inst['name'];
        $this->institute_id   = $institute_id;

        if (Request::get('show_settings')) {
            $this->show_settings = true;
        }
    }

    function new_entry_action()
    {
        $this->layout = null;

        if (!Request::isXhr()) {
            $this->render_nothing();
        }
    }

    /**
     * this action is called whenever a new entry shall be modified or added to the schedule
     *
     * @param  string  $id  optional, if id given, the entry with this id is updated
     * @return void
     */
    function addEntry_action( $id = false )
    {
        if ($id) {
            $data['id'] = $id;
        }

        $error = false;
        if (Request::int('start_hour') !== null && Request::int('day') !== null && Request::int('end_hour') !== null) {
            $data['start']   = Request::int('start_hour') * 100;
            $data['end']     = Request::int('end_hour')   * 100;
            $data['day']     = Request::int('day') + 1;

            // validate the submitted data
            if ($data['start'] >= $data['end'] || Request::int('start_hour') < 0 || Request::int('start_hour') > 23
                || Request::int('end_hour') < 0 || Request::int('end_hour') > 24) {
                $error = true;
            }
        } else {
            $data['start'] = (Request::int('entry_start_hour') * 100) + Request::int('entry_start_minute');
            $data['end']   = (Request::int('entry_end_hour')   * 100) + Request::int('entry_end_minute');
            $data['day']   = Request::int('entry_day');

            if ($data['start'] >= $data['end']
                || Request::int('entry_start_hour')   < 0 || Request::int('entry_start_hour')   > 23
                || Request::int('entry_end_hour')     < 0 || Request::int('entry_end_hour')     > 23
                || Request::int('entry_start_minute') < 0 || Request::int('entry_start_minute') > 59
                || Request::int('entry_end_minute')   < 0 || Request::int('entry_end_minute')   > 59
            ) {
                $error = true;
            }
        }

        if ($error) {
            $this->flash['messages'] = array('error' =>
                array(_("Eintrag konnte nicht gespeichert werden, da die Start- und/oder Endzeit ung�ltigt ist!"))
             );
        } else {
            $data['title']   = Request::get('entry_title');
            $data['content'] = Request::get('entry_content');
            $data['user_id'] = $GLOBALS['user']->id;
            if (Request::get('entry_color')) {
                $data['color'] = Request::get('entry_color');
            } else {
                $data['color'] = DEFAULT_COLOR_NEW;
            }

            CalendarScheduleModel::storeEntry($data);
        }

        $this->redirect('calendar/schedule');
    }


    /**
     * this action keeps the entry of the submitted_id and enables displaying of the entry-dialog.
     * If no id is submitted, an empty entry_dialog is displayed.
     *
     * @param  string  $id  the id of the entry to edit (if any), false otherwise.
     * @return void
     */
    function entry_action($id = false, $cycle_id = false)
    {
        if (Request::isXhr()) {
            $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
            $this->layout = null;

            $this->entry = array(
                'id' => $id,
                'cycle_id' => $cycle_id
            );

            if ($cycle_id) {
                $this->show_entry = array_pop(CalendarScheduleModel::getSeminarEntry($id, $GLOBALS['user']->id, $cycle_id));
                $this->show_entry['id'] = $id;
                $this->render_template('calendar/schedule/_entry_course');
            } else if ($id) {
                $entry_columns = CalendarScheduleModel::getScheduleEntries($GLOBALS['user']->id, 0, 0, $id);
                $entries = array_pop($entry_columns)->getEntries();
                $this->show_entry = array_pop($entries);
                $this->render_template('calendar/schedule/_entry_schedule');
            }
        } else {
            $this->flash['entry'] = array(
                'id' => $id,
                'cycle_id' => $cycle_id
            );

            $this->redirect('calendar/schedule/');
        }
    }

    /**
     * Return an HTML fragment containing a form to edit an entry
     *
     * @param  string  the ID of a course
     * @param  string  an optional cycle's ID
     * @return void
     */
    function entryajax_action($id, $cycle_id = false)
    {
        $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
        if ($cycle_id) {
            $this->show_entry = array_pop(CalendarScheduleModel::getSeminarEntry($id, $GLOBALS['user']->id, $cycle_id));
            $this->show_entry['id'] = $id;
            $this->render_template('calendar/schedule/_entry_course');
        } else {
            $entry_columns = CalendarScheduleModel::getScheduleEntries($GLOBALS['user']->id, 0, 0, $id);
            $entries = array_pop($entry_columns)->getEntries();
            $this->show_entry = array_pop($entries);
            $this->render_template('calendar/schedule/_entry_schedule');
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

        $this->timespan = substr($start, 0, 2) .':'. substr($start, 2, 2)
                        . ' - '. substr($end, 0, 2) .':'. substr($end, 2, 2);
        $this->start    = $start;
        $this->end      = $end;

        $day_names  = array(_("Montag"),_("Dienstag"),_("Mittwoch"),
            _("Donnerstag"),_("Freitag"),_("Samstag"),_("Sonntag"));

        $this->day        = (int)$day;
        $this->day_name   = $day_names[$this->day];


        $this->render_template('calendar/schedule/_entry_inst');
    }

    /**
     * delete the entry of the submitted id (only entry belonging to the current
     * use can be deleted)
     *
     * @param  string  $id  the id of the entry to delete
     * @return void
     */
    function delete_action($id)
    {
        CalendarScheduleModel::deleteEntry($id);
        $this->redirect('calendar/schedule');
    }

    /**
     * store the color-settings for the seminar
     *
     * @param  string  $seminar_id
     * @return void
     */
    function editseminar_action($seminar_id, $cycle_id)
    {
        $data = array(
            'id'       => $seminar_id,
            'cycle_id' => $cycle_id,
            'color'    => Request::get('entry_color')
        );

        CalendarScheduleModel::storeSeminarEntry($data);

        $this->redirect('calendar/schedule');
    }

    /**
     * Adds the appointments of a course to your schedule.
     *
     * @param  string  the ID of the course
     * @return void
     */
    function addvirtual_action($seminar_id)
    {
        $sem = Seminar::getInstance($seminar_id);
        foreach ($sem->getCycles() as $cycle) {
            $data = array(
                'id'       => $seminar_id,
                'cycle_id' => $cycle->getMetaDateId(),
                'color'    => false
            );

            CalendarScheduleModel::storeSeminarEntry($data);
        }

        $this->redirect('calendar/schedule');
    }


    /**
     * Set the visibility of the course.
     *
     * @param  string  the ID of the course
     * @param  string  the ID of the cycle
     * @param  string  visibility; either '1' or '0'
     * @param  string  if you give this optional param, it signals an Ajax request
     * @return void
     */
    function adminbind_action($seminar_id, $cycle_id, $visible, $ajax = false)
    {
        CalendarScheduleModel::adminBind($seminar_id, $cycle_id, $visible);

        if (!$ajax) {
            $this->redirect('calendar/schedule');
        } else {
            $this->render_nothing();
        }
    }

    /**
     * Hide the give appointment.
     *
     * @param  string  the ID of the course
     * @param  string  the ID of the cycle
     * @param  string  if you give this optional param, it signals an Ajax request
     * @return void
     */
    function unbind_action($seminar_id, $cycle_id = false, $ajax = false)
    {
        CalendarScheduleModel::unbind($seminar_id, $cycle_id);

        if (!$ajax) {
            $this->redirect('calendar/schedule');
        } else {
            $this->render_nothing();
        }
    }

    /**
     * Show the given appointment.
     *
     * @param  string  the ID of the course
     * @param  string  the ID of the cycle
     * @param  string  if you give this optional param, it signals an Ajax request
     * @return void
     */
    function bind_action($seminar_id, $cycle_id, $ajax = false)
    {
        CalendarScheduleModel::bind($seminar_id, $cycle_id);

        if (!$ajax) {
            $this->redirect('calendar/schedule');
        } else {
            $this->render_nothing();
        }
    }

    /**
     * Show the settings' form.
     *
     * @return void
     */
    function settings_action()
    {
        if (Request::isXhr()) {
            $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
            $this->layout = null;
        } else {
            $this->redirect('calendar/schedule/index?show_settings=true');
        }

        $this->settings = UserConfig::get($GLOBALS['user']->id)->SCHEDULE_SETTINGS;
    }

    /**
     * Store the settings
     *
     * @param string  the start time of the calendar to show, e.g. "1000"
     * @param string  the end time of the calendar to show, e.g. "1200"
     * @param string  the days to show
     * @param string  the ID of the semester
     * @return void
     */
    function storesettings_action($start_hour = false, $end_hour = false, $days = false, $semester_id = false)
    {
        global $user;

        if ($start_hour === false) {
            $start_hour  = Request::int('start_hour');
            $end_hour    = Request::int('end_hour');
            $days        = Request::getArray('days');
        }
        $this->my_schedule_settings = array(
            'glb_start_time' => $start_hour,
            'glb_end_time'   => $end_hour,
            'glb_days'       => $days,
            'converted'      => true
        );

        UserConfig::get($user->id)->store('SCHEDULE_SETTINGS', $this->my_schedule_settings);

        $this->redirect('calendar/schedule');
    }

}
