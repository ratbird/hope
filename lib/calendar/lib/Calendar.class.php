<?
/**
* SingleCalendar.class.php
*
*
*
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: Calendar.class.php,v 1.8 2010/05/28 13:54:51 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// GroupCalendar.class.php
//
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
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


define('CALENDAR_END', 0x7FFFFFFF);
define('CALENDAR_PERMISSION_OWN', 16);
define('CALENDAR_PERMISSION_ADMIN', 8);
define('CALENDAR_PERMISSION_WRITABLE', 4);
define('CALENDAR_PERMISSION_READABLE', 2);
define('CALENDAR_PERMISSION_FORBIDDEN', 1);
define('CALENDAR_RANGE_USER', 1);
define('CALENDAR_RANGE_GROUP', 2);
define('CALENDAR_RANGE_SEM', 3);
define('CALENDAR_RANGE_INST', 4);

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once $RELATIVE_PATH_CALENDAR . '/lib/ErrorHandler.class.php';
require_once 'lib/functions.php';
require_once $RELATIVE_PATH_CALENDAR . "/lib/driver/{$CALENDAR_DRIVER}/CalendarDriver.class.php";
require_once $RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SingleCalendar.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/GroupCalendar.class.php';
require_once 'lib/classes/Modules.class.php';


class Calendar {

	var $user_name = '';
	var $user_id = '';
	var $permission = CALENDAR_PERMISSION_WRITABLE;//CALENDAR_PERMISSION_FORBIDDEN;
	var $headline = '';
	var $user_settings;
	var $event = NULL;
	var $range;


	function Calendar ($range_id) {

		init_error_handler('_calendar_error');
		/*
		if ($this->getRange() == CALENDAR_RANGE_USER) {
			$this->user_name = get_username($range_id);
		}*/
//		$this->getUserId();
		$this->user_id = $range_id;
		$this->setUserSettings();
	}

	function &getInstance ($range_id = NULL) {
		global $user;

		static $instance = array();
		/*
		if (!get_config('CALENDAR_GROUP_ENABLE') || is_null($selection)) {
			$range_id = $user->id;
		}
		*/
		if (is_null($range_id)) {
			$range_id = $user->id;
		}

		if (!is_object($instance[$selection])) {
			global $perm;

			switch (get_object_type($range_id)) {
				case 'user' :
					closeObject();
					$instance[$range_id] =& new SingleCalendar($range_id);
					$instance[$range_id]->range = CALENDAR_RANGE_USER;
					$instance[$range_id]->user_name = get_username($range_id);
					break;
				case 'group' :
					closeObject();
					if (get_config('CALENDAR_GROUP_ENABLE')) {
						$instance[$range_id] =& new GroupCalendar($range_id, $user->id);
						$instance[$range_id]->range = CALENDAR_RANGE_GROUP;
						$instance[$range_id]->user_name = get_username();
					} else {
						$instance[$range_id] =& new SingleCalendar($user->id);
						$instance[$range_id]->range = CALENDAR_RANGE_USER;
						$instance[$range_id]->user_name = get_username();
					}
					break;
				case 'sem' :
					if ($perm->have_studip_perm('user', $range_id)) {
						$instance[$range_id] =& new SingleCalendar($range_id);
						$instance[$range_id]->range = CALENDAR_RANGE_SEM;
						$instance[$range_id]->user_name = get_username();
					} else {
						$range_id = $user->id;
						$instance[$range_id] =& new SingleCalendar($user->id);
						$instance[$range_id]->range = CALENDAR_RANGE_USER;
						$instance[$range_id]->user_name = get_username();
					}
					break;
				case 'inst' :
				case 'fak' :
					$instance[$range_id] =& new SingleCalendar($range_id);
					$instance[$range_id]->range = CALENDAR_RANGE_INST;
					$instance[$range_id]->user_name = get_username();
					break;
				default :
					$range_id = $user->id;
					$instance[$range_id] =& new SingleCalendar($user->id);
					$instance[$range_id]->range = CALENDAR_RANGE_USER;
					$instance[$range_id]->user_name = get_username();
			}
		}
		$instance[$range_id]->setPermission(Calendar::GetPermissionByUserRange($GLOBALS['auth']->auth['uid'], $range_id));
		return $instance[$range_id];
	}

	function havePermission ($permission) {

		return $permission <= $this->permission;
	}

	function getPermission () {

		return $this->permission;
	}

	function setPermission ($permission) {

		$this->permission = $permission;
	}

	function checkPermission ($permission) {

		return $this->permission == $permission;
	}

	function GetPermissionByUserRange ($user_id, $range_id) {
		if (get_object_type($range_id) == 'user' && $user_id == $range_id) {
			return CALENDAR_PERMISSION_OWN;
		}

		switch (get_object_type($range_id)) {
			case 'user' :

				// alle Dozenten haben gegenseitig schreibenden Zugriff, ab dozent immer schreibenden Zugriff
				if ($GLOBALS['perm']->have_perm('dozent') && $GLOBALS['perm']->get_perm($range_id) == 'dozent') {
					return CALENDAR_PERMISSION_WRITABLE;
				}

				$db =& new DB_Seminar();
				$query = "SELECT calpermission FROM contact WHERE owner_id = '$range_id' AND user_id = '$user_id'";
				$db->query($query);
				if ($db->next_record()) {
					switch ($db->f('calpermission')) {
						case 1 :
							return CALENDAR_PERMISSION_FORBIDDEN;
						case 2 :
							return CALENDAR_PERMISSION_READABLE;
						case 4 :
							return CALENDAR_PERMISSION_WRITABLE;
						default :
							return CALENDAR_PERMISSION_FORBIDDEN;
					}
				}
				return CALENDAR_PERMISSION_FORBIDDEN;
			case 'group' :
				$db =& new DB_Seminar();
				$query = "SELECT range_id FROM statusgruppen WHERE statusgruppe_id = '$range_id'";
				$db->query($query);
				if ($db->next_record()) {
					if ($db->f('range_id') == $user_id) {
						return CALENDAR_PERMISSION_OWN;
					}
				}
				return CALENDAR_PERMISSION_FORBIDDEN;
			case 'sem' :
				switch ($GLOBALS['perm']->get_studip_perm($range_id, $user_id)) {
					case 'user' :
					case 'autor' :
						return CALENDAR_PERMISSION_READABLE;
					case 'tutor' :
					case 'dozent' :
					case 'admin' :
					case 'root' :
						return CALENDAR_PERMISSION_WRITABLE;
					default :
						return CALENDAR_PERMISSION_FORBIDDEN;
				}
			case 'inst' :
			case 'fak' :
				switch ($GLOBALS['perm']->get_studip_perm($range_id, $user_id)) {
					case 'user' :
						return CALENDAR_PERMISSION_READABLE;
					case 'autor' :
						return CALENDAR_PERMISSION_READABLE;
					case 'tutor' :
					case 'dozent' :
					case 'admin' :
					case 'root' :
						return CALENDAR_PERMISSION_WRITABLE;
					default :
						// readable for all
						return CALENDAR_PERMISSION_READABLE;
				}
			default :
				return CALENDAR_PERMISSION_FORBIDDEN;
		}
		return CALENDAR_PERMISSION_FORBIDDEN;
	}

	function getRange () {
		return $this->range;
	}

	function getUserId () {
		global $_calendar_error;

		if ($this->user_id != '') {
			return $this->user_id;
		}

	//	$user_id = $GLOBALS['user']->id;
		$user_id = get_userid($this->user_name);
		if (!$user_id) {
			$_calendar_error->throwError(ERROR_FATAL,
				_("Der Benutzername existiert nicht."), __FILE__, __LINE__);
			return FALSE;
		}
		$this->user_id = $user_id;

		return $this->user_id;
	}

	function getUserName () {

		return $this->user_name;
	}

	function getGroupName () {

		$db =& new DB_Seminar();

		$query = "SELECT name FROM statusgruppen WHERE statusgruppe_id = '{$this->group_id}'";
		$query .= " AND range_id = '{$this->user_id}'";

		$db->query($query);

		if ($db->next_record())
			return $db->f('name');

		return '';
	}

	function getGroups () {
		global $auth;

		$db =& new DB_Seminar();
		$query = "SELECT DISTINCT sg.statusgruppe_id, sg.name FROM statusgruppen sg
				LEFT JOIN statusgruppe_user su USING(statusgruppe_id)
				LEFT JOIN contact c ON(su.user_id = c.owner_id)
				WHERE sg.range_id = '" . $auth->auth['uid'] . "' AND c.calpermission > 1
				ORDER BY sg.name";
		$db->query($query);

		$groups = array();
		while ($db->next_record())
			$groups[] = array('name' => $db->f('name'), 'id' => $db->f('statusgruppe_id'));

		return $groups;
	}

	function getUsers ($mode = 'ALL') {
		global $auth;

		$db =& new DB_Seminar();

		if ($mode == 'ALL')
			$perm = 'su.calpermission > 1';
		else
			$perm = 'su.calpermission > 2';

		$query = "SELECT DISTINCT aum.username, CONCAT(aum.Nachname,', ',aum.vorname) as fullname, aum.user_id
				FROM contact c LEFT JOIN auth_user_md5 aum ON(c.owner_id = aum.user_id)
				WHERE c.user_id = '{$auth->auth['uid']}' AND c.calpermission > 1
				ORDER BY fullname";

		$db->query($query);
		$users = array();
		while ($db->next_record()) {
			$users[] = array('name' => $db->f('fullname'), 'username' => $db->f('username'),
					'id' => $db->f('user_id'));
		}

		return $users;
	}

	function getAllContactGroups () {
		global $auth;

		$db =& new DB_Seminar();

		$query = "SELECT aum.user_id, aum.username,  s.statusgruppe_id, s.name ";
		$query .= "FROM statusgruppe_user su ";
		$query .= "LEFT JOIN statusgruppen s USING ( statusgruppe_id ) ";
		$query .= "LEFT JOIN auth_user_md5 aum ON ( range_id = aum2.user_id ) ";
		$query .= "WHERE su.user_id = '{$auth->auth['uid']}' AND s.range_id != aum.user_id ";
		$query .= "AND s.range_id = aum.user_id AND s.cal_enable = 1";

		$contact_groups = array();
		$db->query($query);
		while ($db->next_record()) {
			$contact_groups[] = $db->Record;
		}

		return $contact_groups;
	}

	function getHeadline () {
		return html_entity_decode($this->headline);
	}

	function createEvent ($properties = NULL) {
		if (is_null($properties))
			$properties = array();
		$this->event =& new CalendarEvent($properties);
	}

	function addEvent ($event_id = '', $selected_users = NULL) {
		global $calendar_sess_forms_data;

		$this->event =& new DbCalendarEvent($this, $event_id);
		if ($this->havePermission(CALENDAR_PERMISSION_WRITEABLE)) {
			$this->setEventProperties($calendar_sess_forms_data, $calendar_sess_forms_data['mod_prv']);

			$this->addEventObj($this->event, ($event_id == '' ? FALSE : TRUE), $selected_users);
		}
	}

	// abstract
	function addEventObj (&$event, $updated, $selected_users = NULL) {

	}

	function getDefaultUserSettings ($index = NULL) {
		$default = array(
			'view'             => 'showweek',
			'start'            => 9,
			'end'              => 20,
			'step_day'         => 900,
			'step_week'        => 1800,
			'type_week'        => 'LONG',
			'holidays'         => TRUE,
			'sem_data'         => TRUE,
			'link_edit'        => TRUE,
			'delete'           => 0,
			'step_week_group'  => 7200,
			'step_day_group'  => 3600
		);
		return (is_null($index) ? $default : $default[$index]);
	}

	function updateBindSeminare () {
		global $user;

		$db1 =& new DB_Seminar();
		$db2 =& new DB_Seminar();
		if (is_array($_POST['sem'])) {
			$query = "SELECT Seminar_id FROM seminar_user WHERE user_id = '{$user->id}'";
			$db1->query($query);
			while ($db1->next_record()) {
				if ($_POST['sem'][$db1->f('Seminar_id')]) {
					$query = "UPDATE seminar_user SET bind_calendar = 1 WHERE Seminar_id = '"
							. $db1->f('Seminar_id') . "' AND user_id = '{$user->id}'";
				}
				else {
					$query = "UPDATE seminar_user SET bind_calendar = 0 WHERE Seminar_id = '"
							. $db1->f('Seminar_id') . "' AND user_id = '{$user->id}'";
				}
				$db2->query($query);
			}
		}
		else {
			return FALSE;
		}
		return TRUE;
	}

	function getBindSeminare ($user_id = NULL, $all = NULL, $names = FALSE) {
		global $perm;

		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		$bind_seminare = array();

		$db =& new DB_Seminar();
		if ($names) {
			$query = "SELECT su.Seminar_id, s.Name FROM seminar_user su LEFT JOIN seminare s USING(Seminar_id) WHERE user_id = '$user_id'";
		} else {
			$query = "SELECT Seminar_id FROM seminar_user WHERE user_id = '$user_id'";
		}
		if (is_null($all) || $all === FALSE) {
			$query .= " AND bind_calendar = 1";
		}
		if ($names) {
			$query .= ' ORDER BY Name';
			$db->query($query);
			while ($db->next_record()) {
				$bind_seminare[$db->f('Seminar_id')] = $db->f('Name');
			}
		} else {
			if (isset($GLOBALS['SessSemName'][1])) {
				if($perm->have_studip_perm('user', $GLOBALS['SessSemName'][1])) {
					array_push($bind_seminare, $GLOBALS['SessSemName'][1]);
					return $bind_seminare;
				}
				return NULL;
			} else {
				$db->query($query);
				while ($db->next_record()) {
					$bind_seminare[] = $db->f('Seminar_id');
				}
			}
		}
		if (count($bind_seminare)) {
			return $bind_seminare;
		}

		return NULL;
	}

	function getBindInstitute ($user_id = NULL, $all = NULL, $names = FALSE) {

	}

	function setUserSettings ($user_settings = NULL) {
		$default_user_settings = $this->getDefaultUserSettings();
		$this->user_settings = $default_user_settings;
		if (!is_null($user_settings)) {
			foreach ($default_user_settings as $key => $foo) {
				$this->user_settings[$key] = $user_settings[$key];
			}
		}
	}

	function getUserSettings ($index = NULL) {
		if (is_null($index))
			return $this->user_settings;

		return (isset($this->user_settings[$index]) ? $this->user_settings[$index] : $this->getDefaultUserSettings($index));
	}

	function setEventProperties (&$calendar_form_data, $mod) {

		if ($calendar_form_data['wholeday']) {
			$this->event->properties['DTSTART'] = mktime(0, 0, 0, $calendar_form_data['start_month'],
					$calendar_form_data['start_day'], $calendar_form_data['start_year']);
			$this->event->properties['DTEND'] = mktime(23, 59, 59, $calendar_form_data['end_month'],
					$calendar_form_data['end_day'], $calendar_form_data['end_year']);
			$this->event->setDayEvent();
		}
		else {
			$this->event->properties['DTSTART'] = mktime($calendar_form_data['start_h'],
					$calendar_form_data['start_m'], 0, $calendar_form_data['start_month'],
					$calendar_form_data['start_day'], $calendar_form_data['start_year']);
			$this->event->properties['DTEND'] = mktime($calendar_form_data['end_h'],
					$calendar_form_data['end_m'], 0, $calendar_form_data['end_month'],
					$calendar_form_data['end_day'], $calendar_form_data['end_year']);
		}
		$this->event->properties['SUMMARY']         = $calendar_form_data['txt'];
		$this->event->properties['CATEGORIES']      = $calendar_form_data['cat_text'];
		$this->event->properties['STUDIP_CATEGORY'] = $calendar_form_data['cat'];
		$this->event->properties['PRIORITY']        = $calendar_form_data['priority'];
		$this->event->properties['LOCATION']        = $calendar_form_data['loc'];
		$this->event->properties['DESCRIPTION']     = $calendar_form_data['content'];

		switch ($calendar_form_data['via']) {
			case 'PUBLIC':
				$this->event->setType('PUBLIC');
				break;
			case 'CONFIDENTIAL':
				$this->event->setType('CONFIDENTIAL');
				break;
			default:
				$this->event->setType('PRIVATE');
		}

		if ($mod != 'SINGLE' && $calendar_form_data['exp_c'] == 'date') {
			$expire = mktime(23, 59, 59, $calendar_form_data['exp_month'],
					$calendar_form_data['exp_day'], $calendar_form_data['exp_year']);
			$calendar_form_data['exp_count'] = 0;
		}
		elseif ($calendar_form_data['exp_c'] == 'never') {
			$expire = CALENDAR_END;
			$calendar_form_data['exp_count'] = 0;
		}

		switch ($mod) {
			case 'DAILY':
				if ($calendar_form_data['type_d'] == 'daily') {
					$this->event->setRepeat(array('rtype' => 'DAILY',
							'linterval' => $calendar_form_data['linterval_d'],
							'expire' => $expire, 'count' => $calendar_form_data['exp_count']));
				}
				elseif ($calendar_form_data['type_d'] == 'wdaily') {
					$this->event->setRepeat(array('rtype' => 'WEEKLY', 'linterval' => '1',
							'wdays' => '12345', 'expire' => $expire,
							'count' => $calendar_form_data['exp_count']));
				}
				break;

			case 'WEEKLY':
				if (empty($calendar_form_data['wdays'])) {
					$this->event->setRepeat(array('rtype' => 'WEEKLY',
							'linterval' => $calendar_form_data['linterval_w'],
							'expire' => $expire, 'count' => $calendar_form_data['exp_count']));
				}
				else {
					$weekdays = implode('', $calendar_form_data['wdays']);
					$this->event->setRepeat(array('rtype' => 'WEEKLY',
							'linterval' => $calendar_form_data['linterval_w'], 'wdays' => $weekdays,
							'expire' => $expire, 'count' => $calendar_form_data['exp_count']));
				}
				break;

			case 'MONTHLY':
				if ($calendar_form_data['type_m'] == 'day') {
					$this->event->setRepeat(array('rtype' => 'MONTHLY',
							'linterval' => $calendar_form_data['linterval_m1'],
							'day' => $calendar_form_data['day_m'], 'expire' => $expire,
							'count' => $calendar_form_data['exp_count']));
				}
				else {
					$this->event->setRepeat(array('rtype' => 'MONTHLY',
							'linterval' => $calendar_form_data['linterval_m2'],
							'sinterval' => $calendar_form_data['sinterval_m'],
							'wdays' => $calendar_form_data['wday_m'],
							'expire' => $expire, 'count' => $calendar_form_data['exp_count']));
				}
				break;

			case 'YEARLY':
				if ($calendar_form_data['type_y'] == 'day') {
					$this->event->setRepeat(array('rtype' => 'YEARLY',
							'month' => $calendar_form_data['month_y1'],
							'day' => $calendar_form_data['day_y'], 'expire' => $expire,
							'count' => $calendar_form_data['exp_count']));
				}
				else {
					$this->event->setRepeat(array('rtype' => 'YEARLY',
							'sinterval' => $calendar_form_data['sinterval_y'],
							'wdays' => $calendar_form_data['wday_y'],
							'month' => $calendar_form_data['month_y2'], 'expire' => $expire,
							'count' => $calendar_form_data['exp_count']));
				}
				break;

			default:
				$this->event->setRepeat(array('rtype' => 'SINGLE', 'expire' => $expire));
		}

		// exceptions
		$this->event->setExceptions($calendar_form_data['exceptions']);
		// add exception
		if ($calendar_form_data['add_exc_x']
				&& check_date($calendar_form_data['exc_month'], $calendar_form_data['exc_day'],
				$calendar_form_data['exc_year'])) {
			$exception = array(mktime(12, 0, 0, $calendar_form_data['exc_month'],
					$calendar_form_data['exc_day'], $calendar_form_data['exc_year'], 0));
			$this->event->setExceptions(array_merge($this->event->getExceptions(), $exception));
			unset($calendar_form_data['add_exc_x']);
		}
		// delete exceptions
		if ($calendar_form_data['del_exc_x']
				&& is_array($calendar_form_data['exc_delete'])) {
			$this->event->setExceptions(array_diff($this->event->getExceptions(),
					$calendar_form_data['exc_delete']));
			unset($calendar_form_data['del_exc_x']);
			unset($calendar_form_data['exc_delete']);
		}

		$calendar_form_data['exceptions'] = $this->event->getExceptions();

	}

	function getEventProperties (&$calendar_form_data) {

		$calendar_form_data['start_h'] = date('G', $this->event->getStart());
		$calendar_form_data['start_m'] = date('i', $this->event->getStart());
		$calendar_form_data['start_day'] = date('j', $this->event->getStart());
		$calendar_form_data['start_month'] = date('n', $this->event->getStart());
		$calendar_form_data['start_year'] = date('Y', $this->event->getStart());
		$calendar_form_data['end_h'] = date('G', $this->event->getEnd());
		$calendar_form_data['end_m'] = date('i', $this->event->getEnd());
		$calendar_form_data['end_day'] = date('j', $this->event->getEnd());
		$calendar_form_data['end_month'] = date('n', $this->event->getEnd());
		$calendar_form_data['end_year'] = date('Y', $this->event->getEnd());

		$calendar_form_data['wholeday'] = $this->event->isDayEvent();

		$calendar_form_data['cat'] = $this->event->properties['STUDIP_CATEGORY'];
		$calendar_form_data['txt'] = htmlReady($this->event->getTitle());
		$calendar_form_data['content'] = htmlReady($this->event->properties['DESCRIPTION']);
		$calendar_form_data['loc'] = htmlReady($this->event->getLocation());

		if (strtolower(get_class($this->event)) != 'seminarevent') {

			// exceptions
			$calendar_form_data['exceptions'] = $this->event->getExceptions();

			$calendar_form_data['cat_text'] = htmlReady($this->event->properties['CATEGORIES']);

			switch ($this->event->getType()) {
				case 'PUBLIC':
					$calendar_form_data['via'] = 'PUBLIC';
					break;
				case 'CONFIDENTIAL':
					$calendar_form_data['via'] = 'CONFIDENTIAL';
					break;
				default:
					$calendar_form_data['via'] = 'PRIVATE';
			}

			$calendar_form_data['priority'] = $this->event->getPriority();
			$repeat = $this->event->getRepeat();
			if ($repeat['count']) {
				$calendar_form_data['exp_count'] = $repeat['count'];
				$calendar_form_data['exp_c'] = 'count';
			}
			else {
				$expire = $this->event->getExpire();
				if (!$expire || $expire == CALENDAR_END)
					$calendar_form_data['exp_c'] = 'never';
				else
					$calendar_form_data['exp_c'] = 'date';
				$calendar_form_data['exp_day'] = date('j', $expire);
				$calendar_form_data['exp_month'] = date('n', $expire);
				$calendar_form_data['exp_year'] = date('Y', $expire);
			}

			switch ($repeat['rtype']) {
				case 'SINGLE':
					break;
				case 'DAILY':
					$calendar_form_data['linterval_d'] = $repeat['linterval'];
					$calendar_form_data['type_d'] = 'daily';
					break;
				case 'WEEKLY':
					$calendar_form_data['linterval_w'] = $repeat['linterval'];
					for ($i = 0; $i < strlen($repeat['wdays']); $i++)
						$calendar_form_data['wdays'][$repeat['wdays']{$i}] = $repeat['wdays']{$i};
					break;
				case 'MONTHLY':
					if ($repeat['wdays']) {
						$calendar_form_data['type_m'] = 'wday';
						$calendar_form_data['linterval_m2'] = $repeat['linterval'];
						$calendar_form_data['sinterval_m'] = $repeat['sinterval'];
						$calendar_form_data['wday_m'] = $repeat['wdays'];
					}
					else {
						$calendar_form_data['type_m'] = 'day';
						$calendar_form_data['linterval_m1'] = $repeat['linterval'];
						$calendar_form_data['day_m'] = $repeat['day'];
					}
					break;
				case 'YEARLY':
					if ($repeat['wdays']) {
						$calendar_form_data['type_y'] = 'wday';
						$calendar_form_data['sinterval_y'] = $repeat['sinterval'];
						$calendar_form_data['wday_y'] = $repeat['wdays'];
						$calendar_form_data['month_y2'] = $repeat['month'];
					}
					else {
						$calendar_form_data['type_y'] = 'day';
						$calendar_form_data['day_y'] = $repeat['day'];
						$calendar_form_data['month_y1'] = $repeat['month'];
					}
			}
		}
	}

	function checkFormData (&$calendar_form_data) {

		$err = array();
		if (!check_date($calendar_form_data['start_month'],
				$calendar_form_data['start_day'], $calendar_form_data['start_year']))
			$err['start_time'] = TRUE;
		if (!check_date($calendar_form_data['end_month'],
				$calendar_form_data['end_day'], $calendar_form_data['end_year']))
			$err['end_time'] = TRUE;

		if (!$err['start_time'] && !$err['end_time']){
			$start = mktime($calendar_form_data['start_h'],
					$calendar_form_data['start_m'], 0,
					$calendar_form_data['start_month'], $calendar_form_data['start_day'],
					$calendar_form_data['start_year']);
			$end = mktime($calendar_form_data['end_h'], $calendar_form_data['end_m'],
					0, $calendar_form_data['end_month'], $calendar_form_data['end_day'],
					$calendar_form_data['end_year']);
			if ($start > $end)
				$err['end_time'] = TRUE;
		}

		if (!preg_match('/^.*\S+.*$/', $calendar_form_data['txt']))
			$err['titel'] = TRUE;
		switch ($calendar_form_data['mod_prv']) {
			case 'DAILY':
				if (!preg_match("/^\d{1,3}$/", $calendar_form_data['linterval_d'])) {
					$err['linterval_d'] = TRUE;
					$err['set_recur'] = TRUE;
				}
				break;
			case 'WEEKLY':
				if (!preg_match("/^\d{1,3}$/", $calendar_form_data['linterval_w'])) {
					$err['linterval_w'] = TRUE;
					$err['set_recur'] = TRUE;
				}
				break;
			case 'MONTHLY':
				if ($calendar_form_data['type_m'] == 'day') {
					if (!preg_match("/^\d{1,2}$/", $calendar_form_data['day_m'])
							|| $calendar_form_data['day_m'] > 31
							|| $calendar_form_data['day_m'] < 1) {
						$err['day_m'] = TRUE;
						$err['set_recur'] = TRUE;
					}
					if (!preg_match("/^\d{1,3}$/", $calendar_form_data['linterval_m1'])) {
						$err['linterval_m1'] = TRUE;
						$err['set_recur'] = TRUE;
					}
				}
				else {
					if (!preg_match("/^\d{1,3}$/", $calendar_form_data['linterval_m2'])) {
						$err['linterval_m2'] = TRUE;
						$err['set_recur'] = TRUE;
					}
				}
				break;
			case 'YEARLY':
				// Jahr 2000 als Schaltjahr
				if (!check_date($calendar_form_data['month_y1'],
						$calendar_form_data['day_y'], 2000)
						&& $calendar_form_data['type_y'] == 'day') {
					$err['day_y'] = TRUE;
					$err['set_recur'] = TRUE;
				}
		}

		if ($calendar_form_data['mod_prv'] != 'SINGLE'
				&& $calendar_form_data['exp_c'] == 'date') {
			if (!check_date($calendar_form_data['exp_month'],
					$calendar_form_data['exp_day'], $calendar_form_data['exp_year'])) {
				$err['exp_time'] = TRUE;
				$err['set_recur'] = TRUE;
			}
			else {
				$exp = mktime(23, 59, 59, $calendar_form_data['exp_month'],
						$calendar_form_data['exp_day'], $calendar_form_data['exp_year']);
				if (!$err['end_time'] && $exp < $end) {
					$err['exp_time'] = TRUE;
					$err['set_recur'] = TRUE;
				}
			}
		}
		elseif ($calendar_form_data['mod_prv'] != 'SINGLE'
				&& $calendar_form_data['exp_c'] == 'count') {
			if (!(preg_match("/^\d{1,3}$/", $calendar_form_data['exp_count'])
					&& $calendar_form_data['exp_count'] > 0)) {
				$err['exp_count'] = TRUE;
				$err['set_recur'] = TRUE;
			}
		}

		// category 255 is reserved for busy times
		if ($calendar_form_data['cat'] == 255)
			$calendar_form_data['cat'] = 1;

		return $err;
	}

	function toStringEdit ($termin_id = NULL, $atime, $type = 'pers', $devent) {
		$write_permission = TRUE;
		if (is_null($termin_id)) {
			if ($type != 'pers') {
				$this->createSeminarEvent($type);
				if (!$this->event->restore($termin_id)) {
					// throw error
					// its something wrong... better to go back to the last view
				/*	page_close();
					header("Location: " . $PHP_SELF	. "?cmd="
							. $calendar_sess_control_data['view_prv'] . "&atime=$atime");
					exit;*/
				}
			} else {
				// get event from database
				$this->restoreEvent($termin_id);
				if (!$mod)
					$mod = $this->event->getRepeat('rtype');
			}
		}
		elseif (isset($cal_group) || $this->havePermission(CALENDAR_PERMISSION_WRITABLE)) {
			if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
				$this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Termin anlegen/bearbeiten");
			} else if (strtolower(get_class($_calendar)) == 'groupcalendar') {
				$this->headline = sprintf(_("Terminkalender der Gruppe %s - Termin anlegen/bearbeiten"),
						$this->getGroupName());
			} else if ($this->checkPermission(CALENDAR_PERMISSION_OWN)) {
				$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Termin anlegen/bearbeiten");
			} else {
				$this->headline = sprintf(_("Terminkalender von %s %s - Termin anlegen/bearbeiten"),
						get_fullname($this->getUserId()), $this->perm_string);
			}
			// call from dayview for new event -> set default values
			if ($atime && empty($_POST)) {
				if ($devent) {
					$properties = array(
							'DTSTART' => mktime(0, 0, 0, date('n', $atime), date('j', $atime),
									date('Y', $atime)),
							'DTEND'   => mktime(23, 59, 59, date('n', $atime),
									date('j', $atime), date('Y', $atime)),
							'SUMMARY' => _("Kein Titel"),
							'STUDIP_CATEGORY' => 1,
							'CATEGORIES' => '',
							'CLASS' => 'PRIVATE');
					$this->createEvent($properties);
					$this->event->setDayEvent();
				}
				else {
					$properties = array(
							'DTSTART' => $atime,
							'DTEND'   => mktime(date('G', $atime) + 1, date('i', $atime), 0,
									date('n', $atime), date('j', $atime), date('Y', $atime)),
							'SUMMARY' => _("Kein Titel"),
							'STUDIP_CATEGORY' => 1,
							'CATEGORIES' => '',
							'CLASS' => 'PRIVATE');
					$this->createEvent($properties);
				}

				$this->event->setRepeat(array('rtype' => 'SINGLE'));
			}
			else {
				$this->createEvent();
			}
		}
		else {
			$write_permission = FALSE;
			$this->headline = sprintf(_("Terminkalender von %s %s - Zugriff verweigert"),
					get_fullname($this->getUserId()), $this->perm_string);
		}

		return $write_permission;

	}

	// static
	function checkRestriction ($properties, $restrictions) {
		if (!is_null($restrictions)) {
				foreach ($restrictions as $property_name => $restriction) {
					if ($restriction != '') {
						if ($properties[strtoupper($property_name)] != $restriction)
							return FALSE;
					}
				}
			return TRUE;
		}

		return TRUE;
	}

	function countEvents () {
		return 0;
	}

	function GetSeminarActivatedCalendar () {
		global $user;

		$db = new DB_Seminar();
		$modules = new Modules();
		$query = "SELECT seminar_id, Name, modules FROM seminar_user LEFT JOIN seminare USING(seminar_id) WHERE user_id = '{$user->id}' ORDER BY Name ASC";
		$db->query($query);
		$active_calendar = array();
		while ($db->next_record()) {
			if ($modules->isBit($db->f('modules'), $modules->registered_modules['calendar']['id'])) {
				$active_calendar[$db->f('seminar_id')] = $db->f('Name');
			}
		}
		return $active_calendar;
	}

	function GetInstituteActivatedCalendar () {
		global $user;

		$db = new DB_Seminar();
		$modules = new Modules();
		$query = "SELECT ui.Institut_id, Name, modules FROM user_inst ui LEFT JOIN Institute i USING(Institut_id)WHERE user_id = '{$user->id}' AND inst_perms IN ('admin','dozent','tutor','autor') ORDER BY Name ASC";
		$db->query($query);
		$active_calendar = array();
		while ($db->next_record()) {
			if ($modules->isBit($db->f('modules'), $modules->registered_modules['calendar']['id'])) {
				$active_calendar[$db->f('Institut_id')] = $db->f('Name');
			}
		}
		return $active_calendar;
	}

	function GetLecturers () {
		$db = new DB_Seminar();

		$query = "SELECT aum.username, CONCAT(aum.Nachname,', ',aum.vorname) as fullname, aum.user_id FROM auth_user_md5 aum WHERE perms = 'dozent' ORDER BY fullname";
		$db->query($query);
		$lecturers = array();
		while ($db->next_record()) {
			if ($db->f('user_id') != $GLOBALS['user']->id) {
				$lecturers[] = array('name' => $db->f('fullname'), 'username' => $db->f('username'), 'id' => $db->f('user_id'));
			}
		}
		return $lecturers;
	}

}
?>
