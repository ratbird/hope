<?

/*
calendar_settings.inc
Persoenlicher Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthienel@data-quest.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

// Imports
require_once 'lib/visual.inc.php';

if ($i_page == "calendar.php") {
	include 'lib/include/html_head.inc.php';
	include 'lib/include/header.php';
}

// store user-settings
if ($cmd_cal == 'chng_cal_settings') {
    $calendar_user_control_data = array(
        'view'             => $cal_view,
        'start'            => $cal_start,
        'end'              => $cal_end,
        'step_day'         => $cal_step_day,
        'step_week'        => $cal_step_week,
        'type_week'        => $cal_type_week,
        'holidays'         => $cal_holidays,
        'sem_data'         => $cal_sem_data,
        'link_edit'        => $cal_link_edit,
        'delete'           => $cal_delete,
        'step_week_group'  => $cal_step_week_group,
        'step_day_group'   => $cal_step_day_group
    );
}

// print out form
echo $GLOBALS['template_factory']->render('calendar/settings', compact('calendar_user_control_data', 'calendar_sess_control_data', 'atime'));