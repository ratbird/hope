<?php

/*
 * Copyright (C) 2009 - Till Glöggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/calendar/organizer.php';
require_once 'app/models/calendar/calendar.php';
require_once 'app/models/calendar/view.php';

class Calendar_OrganizerController extends AuthenticatedController
{
	
	function index_action($days = false)
	{
		Navigation::activateItem('/messaging/calendar');

		$this->entries = CalendarOrganizerModel::getEntries( $GLOBALS['user']->id );

		if (!$days) {
			//var_dump(Request::get('days'));
			if (Request::getArray('days')) {
				$this->days = array_keys(Request::getArray('days'));
			} else {
				$this->days = array(1, 2, 3, 4, 5);
			}
		} else {
			$this->days = explode(',', $days);
		}

		$this->controller = $this;
		$this->calendar_view = new CalendarView( $this->entries, 'organizer' );
		$this->calendar_view->setDays( $this->days );
		//$this->calendar_view->groupEntries();
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

		if (Request::get('hour') || Request::get('day')) {
			$data['start']   = Request::int('hour') * 100;
			$data['end']     = (Request::int('hour')  + 1) * 100;
			$data['day']     = Request::int('day');
		} else {
			$data['start'] = (Request::int('entry_start_hour') * 100) + Request::int('entry_start_minute');
			$data['end']   = (Request::int('entry_end_hour') * 100) + Request::int('entry_end_minute');
			$data['day']   = Request::int('entry_day');
		}

		$data['title']   = Request::get('entry_title');
		$data['content'] = Request::get('entry_content');
		$data['user_id'] = $GLOBALS['user']->id;
		if (Request::get('entry_color')) {
			$data['color'] = Request::get('entry_color');
		} else {
			$data['color'] = dechex(120) . dechex(120) . dechex(250);
		}

		CalendarOrganizerModel::storeEntry($data);

		$this->redirect('calendar/schedule');
	}


	function entry_action($id = false)
	{
		$this->flash['show_entry'] = true;

		if ($id) {
			$this->flash['entry'] = CalendarOrganizerModel::getEntry( $id );
		}

		$this->redirect('calendar/schedule');
	}

	function delete_action($id) 
	{
		CalendarOrganizerModel::deleteEntry($id);
		$this->redirect('calendar/schedule');
	}

	function entrydetails_action($day, $hour)
	{
		$this->flash['show_entry'] = true;

		$this->flash['entry'] = array(
			'title'        => htmlReady(Request::get('title')),
			'content'      => htmlReady(Request::get('content')),
			'day'          => $day,
			'start'        => $hour * 100,
			'end'          => ($hour+1) * 100,
			'start_hour'   => $hour,
			'start_minute' => '00',
			'end_hour'     => ($hour+1),
			'end_minute'   => '00'
		);

		$this->redirect('calendar/schedule');
	}
}
