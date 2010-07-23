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
require_once 'app/models/calendar/instschedule.php';
require_once 'app/models/calendar/calendar.php';
require_once 'app/models/calendar/view.php';
require_once 'lib/classes/SemesterData.class.php';

class Calendar_InstscheduleController extends AuthenticatedController
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

            $myschedule = true;
        }

        if (!$institute_id || get_object_type($institute_id) != 'inst') {
            throw new Exception('Cannot display institute-calender. No valid ID given!');
        }

        // load semester-data and current semester
		$semdata = new SemesterData();
		$this->semesters = $semdata->getAllSemesterData();

        if (Request::get('semester_id')) {
            $this->current_semester = $semdata->getSemesterData(Request::get('semester_id'));
        } else {
    		$this->current_semester = $semdata->getCurrentSemesterData();
        }

        $this->entries = (array)CalendarInstscheduleModel::getInstituteEntries($GLOBALS['user']->id,
                         $this->current_semester, 8, 20, $institute_id);

        Navigation::activateItem('/course/main/schedule');
        $GLOBALS['HELP_KEYWORD'] = "Basis.TerminkalenderStundenplan";
        $GLOBALS['CURRENT_PAGE'] = $GLOBALS['SessSemName']["header_line"]." - "._("Veranstaltungs-Timetable");

        // have we chosen an entry to display?
        if ($this->flash['entry']) {
            $this->show_entry = $this->flash['entry'];
        }

		if (!$days) {
			if (Request::getArray('days')) {
				$this->days = array_keys(Request::getArray('days'));
			} else {
                $this->days = array(1,2,3,4,5,6,0);
			}
		} else {
			$this->days = explode(',', $days);
		}

		$this->controller = $this;
		$this->calendar_view = new CalendarView($this->entries, 'instschedule');
		$this->calendar_view->setHeight(40 + (20 * Request::get('zoom', 0)));
		$this->calendar_view->setDays($this->days);
        $this->calendar_view->setRange($my_schedule_settings['glb_start_time'], $my_schedule_settings['glb_end_time']);
        $this->calendar_view->setReadOnly();
		$this->calendar_view->groupEntries();  // if enabled, group entries with same start- and end-date

        if (Request::get('printview')) {
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. $this->url_for('calendar/instschedule/cssprint/'. $this->calendar_view->getOverallHeight() 
    			. '/'. $this->calendar_view->getHeight()) .'" type="text/css" media="screen,print" />' . "\n";
    			
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. URLHelper::getLink('assets/stylesheets/style_print.css')
    			.'" type="text/css" media="screen,print" />' . "\n";
        } else {
    		$_include_additional_header = '<link rel="stylesheet" href="'
    			. $this->url_for('calendar/instschedule/css/'. $this->calendar_view->getOverallHeight() 
    			. '/'. $this->calendar_view->getHeight()) .'" type="text/css" media="screen" />' . "\n";
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. $this->url_for('calendar/instschedule/cssprint/'. $this->calendar_view->getOverallHeight() 
    			. '/'. $this->calendar_view->getHeight()) .'" type="text/css" media="print" />' . "\n";
    			
    		$_include_additional_header .= '<link rel="stylesheet" href="'
    			. URLHelper::getLink('assets/stylesheets/style_print.css')
    			.'" type="text/css" media="print" />' . "\n";
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
            $this->render_template('calendar/instschedule/_entry_details');
        } else {
            if (Request::get('show_hidden')) {
                $this->flash['show_hidden'] = true;
            }

            $this->flash['entry'] = $this->show_entry;
            $this->redirect('calendar/instschedule/');
        }
    }
}
