<?php

/*
 * Copyright (C) 2009-2010 - Till Glöggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/trails/AuthenticatedController.php';
require_once 'app/models/calendar/schedule.php';
require_once 'app/models/calendar/calendar.php';
require_once 'app/models/calendar/view.php';
require_once 'lib/classes/SemesterData.class.php';

class Calendar_ScheduleController extends AuthenticatedController
{
	
	/**
	 * delivers the style-sheet used for printing.
	 *
	 * @param  int  $whole_height  the overall-height of the timetable
	 * @param  int  $entry_height  the height of one hour in the timetable
	 */
	function cssprint_action($whole_height, $entry_height) {
		header('Content-Type: text/css');
		$this->whole_height = $whole_height;
		$this->entry_height = $entry_height;
		$this->render_template('calendar/print_style');
		page_close();
	}

	/**
	 * delivers the style-sheet used for displaying the timetable.
	 *
	 * @param  int  $whole_height  the overall-height of the timetable
	 * @param  int  $entry_height  the height of one hour in the timetable
	 */
	function css_action($whole_height, $entry_height) {
		header('Content-Type: text/css');
		$this->whole_height = $whole_height;
		$this->entry_height = $entry_height;
		$this->render_template('calendar/stylesheet');
		page_close();
	}

	/**
	 * this action is the main action of the schedule-controller, setting the environment for the timetable,
	 * accepting a comma-separated list of days.
	 *
	 * @param  string  a list of an arbitrary mix of the numbers 0-6, separated with a comma (e.g. 1,2,3,4,5 (for Monday to Friday, the default))
	 */
	function index_action($days = false)
	{
		global $_include_additional_header, $my_schedule_settings;

        if ($GLOBALS['perm']->have_perm('admin')) $inst_mode = true;

        if ($inst_mode) {

            // try to find the correct institute-id
            $institute_id = Request::get('institute_id', 
                            $SessSemName[1] ? $SessSemName[1] :
                            Request::get('cid', false));

            
            if (!$institute_id) {
                $institute_id = $GLOBALS['_my_admin_inst_id'] 
                              ? $GLOBALS['_my_admin_inst_id'] 
                              : $GLOBALS['my_schedule_settings']["glb_inst_id"];

                if (!$GLOBALS['my_schedule_settings']["glb_inst_id"]) {
                    $GLOBALS['my_schedule_settings']["glb_inst_id"] = $GLOBALS['_my_admin_inst_id'];
                }
            }

            if (!$institute_id || get_object_type($institute_id) != 'inst') {
                throw new Exception('Cannot display institute-calender. No valid ID given!');
            }
        }

        // check, if the hidden seminar-entries shall be shown
        $show_hidden = Request::get('show_hidden', false);
        if ($this->flash['show_hidden']) $show_hidden = true;

        // load semester-data and current semester
		$semdata = new SemesterData();
		$this->semesters = $semdata->getAllSemesterData();
		$this->current_semester = $semdata->getCurrentSemesterData();

        // convert old settings, if necessary (mein_stundenplan.php)
        if (!$my_schedule_settings['converted']) {
            $my_schedule_settings['glb_sem'] = $this->current_semester['semester_id'];
            $c = 1;
            foreach ($my_schedule_settings['glb_days'] as $show) {
                if ($c == 7) $c = 0;
                $new_days[] = $c;
                $c++;
            }

            sort($new_days);
            $my_schedule_settings['glb_days'] = $new_days;
            $my_schedule_settings['converted'] = true;
        }

        // set the user-defined semester and start/end-times
        if ($my_schedule_settings['glb_sem']) {
            foreach ($this->semesters as $semester) {
                if ($semester['semester_id'] == $my_schedule_settings['glb_sem'] && $semester['ende'] > time()) {
                    $this->current_semester = $semester;
                    break;
                }
            }
        }

        if ($inst_mode) {
    		$this->entries = CalendarScheduleModel::getInstituteEntries($GLOBALS['user']->id, $this->current_semester,
                $my_schedule_settings['glb_start_time'], $my_schedule_settings['glb_end_time'], $institute_id, $show_hidden);

            $GLOBALS['HELP_KEYWORD'] = "Basis.MyStudIPStundenplan";
            $GLOBALS['CURRENT_PAGE'] = _("Mein Stundenplan");
            Navigation::activateItem('/browse/my_courses/schedule');
        } else {
            $GLOBALS['HELP_KEYWORD'] = "Basis.MyStudIPStundenplan";
            $GLOBALS['CURRENT_PAGE'] = _("Mein Stundenplan");
		    Navigation::activateItem('/calendar/schedule');

            // get the entries to be displayed in the schedule
    		$this->entries = CalendarScheduleModel::getEntries($GLOBALS['user']->id, $this->current_semester,
                $my_schedule_settings['glb_start_time'], $my_schedule_settings['glb_end_time'], $show_hidden);
        }

        // have we chosen an entry to display?
        if ($this->flash['entry']) {
            if ($inst_mode) {
                $this->show_entry = $this->flash['entry'];
            } else if ($this->flash['entry']['id'] == null) {
                $this->show_entry = $this->flash['entry'];
            } else {
                foreach ($this->entries as $entry_days) {
                    foreach ($entry_days as $entry) {
                        if ($entry['id'] == $this->flash['entry']['id']) {
                            if ($this->flash['entry']['cycle_id']) {
                                if ($this->flash['entry']['cycle_id'] == $entry['cycle_id']) {
                                    $this->show_entry = $entry;
                                }
                            } else {
                                $this->show_entry = $entry;
                            }
                        }
                    }
                }
            }

        }

		if (!$days) {
			if (Request::getArray('days')) {
				$this->days = array_keys(Request::getArray('days'));
			} else {
                $this->days = $my_schedule_settings['glb_days'];
			}
		} else {
			$this->days = explode(',', $days);
		}

		$this->controller = $this;
		$this->calendar_view = new CalendarView($this->entries, 'schedule');
		$this->calendar_view->setHeight(40 + (20 * Request::get('zoom', 0)));
		$this->calendar_view->setDays($this->days);
        $this->calendar_view->setRange($my_schedule_settings['glb_start_time'], $my_schedule_settings['glb_end_time']);

        if ($inst_mode) {
		    $this->calendar_view->groupEntries();  // if enabled, group entries with same start- and end-date
        }

        if (Request::get('printview')) {
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. $this->url_for('calendar/schedule/cssprint/'. $this->calendar_view->getOverallHeight() 
    			. '/'. $this->calendar_view->getHeight()) .'" type="text/css" media="screen,print" />' . "\n";
    			
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. URLHelper::getLink('assets/stylesheets/style_print.css')
    			.'" type="text/css" media="screen,print" />' . "\n";
        } else {
    		$_include_additional_header = '<link rel="stylesheet" href="'
    			. $this->url_for('calendar/schedule/css/'. $this->calendar_view->getOverallHeight() 
    			. '/'. $this->calendar_view->getHeight()) .'" type="text/css" media="screen" />' . "\n";
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. $this->url_for('calendar/schedule/cssprint/'. $this->calendar_view->getOverallHeight() 
    			. '/'. $this->calendar_view->getHeight()) .'" type="text/css" media="print" />' . "\n";
    			
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. URLHelper::getLink('assets/stylesheets/style_print.css')
    			.'" type="text/css" media="print" />' . "\n";
        }

        $this->show_hidden    = $show_hidden;
        $this->inst_mode      = $inst_mode;
	}


	/**
	 * this action is called whenever a new entry shall be modified or added to the schedule
	 *
	 * @param  string  $id  optional, if id given, the entry with this id is updated
	 */
	function addEntry_action( $id = false )
	{
		if ($id) {
			$data['id'] = $id;
		}

        $error = false;

		if (Request::get('hour') || Request::get('day')) {
			$data['start']   = Request::int('hour') * 100;
			$data['end']     = (Request::int('hour')  + 1) * 100;
			$data['day']     = Request::int('day');

            // validate the submitted data
            if ($data['start'] >= $data['end'] || Request::int('hour') < 0 || Request::int('hour') > 23) {
                $error = true;
            }
		} else {
			$data['start'] = (Request::int('entry_start_hour') * 100) + Request::int('entry_start_minute');
			$data['end']   = (Request::int('entry_end_hour') * 100) + Request::int('entry_end_minute');
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
            $this->flash['messages'] = array('error'. chr(167) ._("Eintrag konnte nicht gespeichert werden, da die Start- und/oder Endzeit ungültigt war!"));
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
	 */
	function entry_action($id, $cycle_id = false)
	{
		$this->flash['entry'] = array(
            'id' => $id,
            'cycle_id' => $cycle_id
        );

        if (Request::get('show_hidden')) {
            $this->flash['show_hidden'] = true;
        }

        $this->redirect('calendar/schedule/');
	}


    function entryajax_action($id, $cycle_id = false)
    {
        if ($cycle_id) {
            $this->show_entry = array_pop(CalendarScheduleModel::getSeminarEntry($id, $GLOBALS['user']->id, $cycle_id));
            $this->render_template('calendar/schedule/_entry_course');
        } else {
            $this->show_entry = array_pop(array_pop(CalendarScheduleModel::getScheduleEntries($GLOBALS['user']->id, 0, 0, $id)));
            $this->render_template('calendar/schedule/_entry_schedule');
        }
    }

    function groupedentry_action($start, $end, $seminars, $ajax = false) {
        $this->show_entry = array(
            'type'     => 'inst',
            'seminars' => (array)explode(',', $seminars),
            'start'    => $start,
            'end'      => $end
        );

        if ($ajax) {
            $this->render_template('calendar/schedule/_entry_inst');
        } else {
            if (Request::get('show_hidden')) {
                $this->flash['show_hidden'] = true;
            }

            $this->flash['entry'] = $this->show_entry;
            $this->redirect('calendar/schedule/');
        }
    }

	/**
	 * delete the entry of the submitted id (only entry belonging to the current
	 * use can be deleted)
	 *
	 * @param  string  $id  the id of the entry to delete
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

    function addvirtual_action($seminar_id) {
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


    function adminbind_action($seminar_id, $cycle_id, $visible, $ajax = false) {
		CalendarScheduleModel::adminBind($seminar_id, $cycle_id, $visible);

        if (!$ajax) {
            $this->redirect('calendar/schedule');
        } else {
            $this->render_nothing();
        }
    }

    function unbind_action($seminar_id, $cycle_id = false, $ajax = false) {
		CalendarScheduleModel::unbind($seminar_id, $cycle_id);

        if (!$ajax) {
            $this->redirect('calendar/schedule');
        } else {
            $this->render_nothing();
        }
    }

    function bind_action($seminar_id, $cycle_id, $ajax = false) {
		CalendarScheduleModel::bind($seminar_id, $cycle_id);

        if (Request::get('show_hidden')) {
            $this->flash['show_hidden'] = true;
        }

        if (!$ajax) {
            $this->redirect('calendar/schedule');
        } else {
            $this->render_nothing();
        }
    }

    function settings_action()
    {
    }

    function storesettings_action($start_hour = false, $end_hour = false, $days = false, $semester_id = false)
    {
        global $my_schedule_settings;

        if ($start_hour === false) {
            $start_hour  = Request::get('start_hour');
            $end_hour  = Request::get('end_hour');
            $days        = Request::getArray('days');
            $semester_id = Request::get('semester_id');
        }

        $my_schedule_settings = array(
            'glb_start_time' => $start_hour,
            'glb_end_time'   => $end_hour,
            'glb_days'       => $days,
            'glb_sem'        => $semester_id,
            'glb_inst_id'    => $GLOBALS['my_schedule_settings']["glb_inst_id"],
            'converted'      => true
        );

        $this->redirect('calendar/schedule');
    }
}
