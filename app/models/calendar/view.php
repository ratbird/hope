<?php

/*
 * This class takes and checks all necessary parameters to display a calendar/schedule/time-table.
 *
 * Copyright (C) 2009-2010 - Till Glöggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('app/models/calendar/calendar.php');

class CalendarView
{

	private $entries;
	private $height;
	private $days       = array(1,2,3,4,5);
	private $grouped    = false;
    private $start_hour = 0;
    private $end_hour   = 24;
    private $read_only  = false;


	/**
	 * You need to pass an instance of this class to the template. The constructor
	 * expects an array of entries of the following type:
	 *
	 *  array (
	 *   'color' => the color in hex (css-like, without the #)
	 *   'start' => the (start hour * 100) + (start minute)
	 *   'end'   => the (end hour * 100) + (end minute)
	 *   'day'   => day of week (0 = Sunday, ... , 6 = Saturday)
	 *   'title' => the entry's title
	 *   'content' => whatever shall be the content of the entry as a string
	 *  )
	 *
	 * @param  mixed  $entries     an array of entries (see above)
	 * @param  string $controller  the name of the controller. Used to create links.
	 */
	public function __construct($entries, $controller)
	{
		if (!is_array($entries)) {
			throw new Exception('You need to pass some entries to the CalendarView!');
		}

		$this->checkEntries($entries);
		$this->entries = $entries;
		$this->context = $controller;
	}


	/**
	 * set the visible days for the calendar. Pass an array with at least one of the numbers 0-6,
	 * which represent the numerical phplike-days respectively.
	 *
	 * @param  mixed  $days an array of at least one of the numbers 0-6
	 */
	public function setDays($days)
	{
        // set the correct order for the days
        $new_days = array();

        for ($i = 1; $i <= 6; $i++) {
            if (in_array($i, $days) !== false) {
                $new_days[] = $i;
            }
        }

        if (in_array(0, $days) === true) {
            $new_days[] = 0;
        }

		$this->days = $new_days;
	}

	/**
	 * set the height for one hour. This value is used to calculate the whole height of the schedule.
	 *
	 * @param  int  $entry_height  the height of one hour in the schedule
	 */
	public function setHeight($height) {
		$this->height = $height;
	}

    /**
     * set the range of hours to be displayed. the start_hour has to be smaller than the end_hour
     *
     * @param  int  $start_hour  the hour to start displaying at
     * @param  int  $end_hour    the hour to stop displaying at
     */
    public function setRange($start_hour, $end_hour) {
        $this->start_hour = $start_hour;
        $this->end_hour = $end_hour;
    }

	/**
   * does some plausability checks on an array of calendar-entries
	 *
	 * @param  mixed  an array of calendar-entries
	 *
	 * @return  bool  false if check failed, true otherwise
	 */
	private function checkEntries($entries)
	{
		foreach ($entries as $day => $entry_list) {
			foreach ($entry_list as $entry) {
				if (!isset($entry['color']) || !isset($entry['start']) || !isset($entry['end']) 
					|| !isset($entry['title']) || !isset($entry['content']) ) {
					throw new Exception('The entry '. print_r($entry, true) .' does not follow the specifications!');
				}
			}
		}

		return true;
	}
	

	/**
	 * Call this function th enable/disable the grouping of entries with the same start and end.
	 *
	 * @param  bool  $group optional, defaults to true
	 */
	public function groupEntries($grouped = true)
	{
		$this->grouped = $grouped;
	}


	/**
	 * Returns an array of calendar-entries, grouped by day and additionally grouped by same start and end
	 * if groupEntries(true) has been called.
	 *
	 * @return  mixed  the (double-)grouped entries
	 */
	public function getEntries()
	{
		if (!$this->sorted_entries) {
			if ($this->isGrouped()) {
				$this->sorted_entries = CalendarModel::sortAndGroupEntries($this->entries);
			} else {
				$this->sorted_entries = CalendarModel::sortEntries($this->entries);
			}
		}

		return $this->sorted_entries;
	}

	/**
	 * Returns an array where for each hour the number of concurrent entries is denoted.
	 * Used by the calendar to display the entries in parallel.
	 *
	 * @return  mixed  concurrent entries at each hour
	 */
	public function getMatrix()
	{
		return CalendarModel::generateMatrix($this->getEntries());
	}


    /* * * * * * * * * * * * * * *
     * * *   G E T T E R S   * * * 
     * * * * * * * * * * * * * * */
	public function getContext()
	{
		return $this->context;
	}

	public function getDays()
	{
		return $this->days;
	}

    public function getRange() {
        return array($this->start_hour, $this->end_hour);
    }

	public function isGrouped()
	{
		return $this->grouped;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getOverallHeight() {
		return $this->height * ($this->end_hour - $this->start_hour) + 60;
	}

    public function setReadOnly($readonly = true) {
        $this->read_only = $readonly;
    }

    public function isReadOnly() {
        return $this->read_only;
    }
}
