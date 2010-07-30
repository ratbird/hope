<?
/**
* calendar_visual_group.inc.php
*
*
*
* @author		Peter Thienel <thienel@data-quest.de>
* 					Suchi & Berg GmbH <info@data-quest.de>
* @version		$Id: calendar_visual_group.inc.php,v 1.6 2010/05/28 13:54:51 thienel Exp $
* @access		public
* @modulegroup	calendar
* @module		calendar
* @package	calendar
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// calendar_visual_group.inc.php
//
// Copyright (c) 2006 Peter Tienel <thienel@data-quest.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+



function calendar_select ($selected_id) {
	global $_fullname_sql, $atime, $cmd;

	if (!get_config('CALENDAR_GROUP_ENABLE')) {
		return '';
	}

	$users = Calendar::getUsers();
	$groups = Calendar::getGroups();
	$sems = Calendar::GetSeminarActivatedCalendar();
	$insts = Calendar::GetInstituteActivatedCalendar();
	if ($GLOBALS['perm']->have_perm('dozent')) {
		$lecturers = Calendar::GetLecturers();
	} else {
		$lecturers = array();
	}

	if (!(sizeof($users) + sizeof($groups) + sizeof($sems) + sizeof($insts))) {
		return '';
	}

	return $GLOBALS['template_factory']->render('calendar/calendar_select', compact('calendar_sess_control_data', 'users', 'groups', 'sems', 'insts', 'lecturers', 'atime', 'cmd', 'selected_id'));
}


function calendar_select_user ($calendar, $selected_users) {
	global $auth;

	if (!get_config('CALENDAR_GROUP_ENABLE')) {
		return '';
	}

	$users = Calendar::getUsers('WRITABLE');

	if (!is_array($selected_users)) {
		$selected_users = array();
		while ($user_calendar = $calendar->nextCalendar()) {
			$selected_users[] = $user_calendar->getUserName();
		}
	}

	return $GLOBALS['template_factory']->render('calendar/calendar_select_user', compact('selected_users', 'users'));
}

function js_hover_group ($events, $start, $end, $user_id) {
	global $forum, $auth;

	if (!($forum['jshover'] == 1 && $auth->auth['jscript'])) {
		return '';
    }

	if ($end) {
		$date_time = strftime('%x, ', $start) . strftime('%H:%M - ', $start) . strftime('%H:%M', $end);
    } else {
		$date_time = strftime('%x, ', $start);
    }
	if ($GLOBALS['user']->id == $user_id) {
		$js_title = sprintf(_("Termine am %s, Eigener Kalender"), $date_time);
	} else {
		$js_title = sprintf(_("Termine am %s, Gruppenmitglied: %s"), $date_time, get_fullname($user_id, 'no_title_short'));
    }

	if (!is_array($events)) {
		$events = array();
    }

	foreach ($events as $event) {
		if (date('j', $event->getStart()) != date('j', $event->getEnd())) {
			$js_text .= '<b>' . $event->toStringDate('SHORT_DAY') . '</b> &nbsp; ';
		} else {
			$js_text .= '<b>' . $event->toStringDate('SHORT') . '</b> &nbsp; ';
        }
		$js_text .= htmlReady($event->getTitle()) . '<br>';
	}

	$js_text = "'" . JSReady($js_text, 'contact')
								. "',CAPTION,'" . JSReady($js_title) . "',NOCLOSE,CSSOFF";

	return " onmouseover=\"return overlib($js_text);\" onmouseout=\"return nd();\"";
}

?>