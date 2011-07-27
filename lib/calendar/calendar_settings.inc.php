<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * calendar_settings.inc.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */
// Imports
require_once 'lib/visual.inc.php';


if ($i_page == "calendar.php") {
    include('lib/include/html_head.inc.php');
    include('lib/include/header.php');
}


// store user-settings
if ($cmd_cal == 'chng_cal_settings') {
    $calendar_user_control_data = array(
        'view' => $cal_view,
        'start' => $cal_start,
        'end' => $cal_end,
        'step_day' => $cal_step_day,
        'step_week' => $cal_step_week,
        'type_week' => $cal_type_week,
        'holidays' => $cal_holidays,
        'sem_data' => $cal_sem_data,
        'link_edit' => $cal_link_edit,
        'delete' => $cal_delete,
        'step_week_group' => $cal_step_week_group,
        'step_day_group' => $cal_step_day_group
    );
}

echo $GLOBALS['template_factory']->render('calendar/settings', compact('calendar_user_control_data', 'calendar_sess_control_data', 'atime'));