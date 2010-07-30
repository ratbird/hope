<?
/**
* SingleCalendar.class.php
* 
* 
* 
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: SingleCalendar.class.php,v 1.2 2009/10/07 20:10:42 thienel Exp $
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

require_once $RELATIVE_PATH_CALENDAR . '/lib/Calendar.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php';


class SingleCalendar extends Calendar {
	
	var $view = NULL;
	var $perm_string = '';
	
	
	function SingleCalendar ($range_id, $permission = NULL) {
		Calendar::Calendar($range_id);
		if (is_null($permission)) {
			$permission = $this->checkUserPermissions($this->user_id);
		}
		// switch back to the users own calendar, if the user has no permission
		if ($permission == CALENDAR_PERMISSION_FORBIDDEN) {
			$this->user_id = $GLOBALS['user']->id;
			$this->permissions = CALENDAR_PERMISSION_OWN;
			$this->user_name = $GLOBALS['auth']->auth['uname'];
			$this->perm_string = _("(schreibberechtigt)");
 		}
		else {
			$this->permission = $permission;
			if ($permission == CALENDAR_PERMISSION_OWN)
				$this->perm_string = _("(schreibberechtigt)");
			elseif ($permission == CALENDAR_PERMISSION_WRITABLE)
				$this->perm_string = _("(schreibberechtigt)");
			elseif ($permission == CALENDAR_PERMISSION_READABLE)
				$this->perm_string = _("(leseberechtigt)");
		}
		CalendarDriver::getInstance($this->user_id, $this->permission);
	}
	
	function getId () {
	
		return $this->getUserId();
	}
	
	function checkUserPermissions ($user_id) {
		//global $auth, $_calendar_error;
		
//		if ($this->permissions == CALENDAR_PERMISSION_FORBIDDEN) {
			// the calendar object is an instance of the users own calendar
		/*
		if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
			return CALENDAR_PERMISSION_WRITABLE;
		} else if ($auth->auth['uid'] == $user_id) {
			$permission = CALENDAR_PERMISSION_OWN;
		} else {
			$db = new DB_Seminar();
		*/
		//	$query = "SELECT s.statusgruppe_id, su.calpermission FROM statusgruppe_user su LEFT JOIN ";
		//	$query .= "statusgruppen s USING (statusgruppe_id) WHERE ";
		//	$query .= "su.user_id = '{$auth->auth['uid']}' AND s.range_id = '$user_id'";
		/*
			$query = "SELECT calpermission FROM contact WHERE owner_id = '$user_id'";
			$query .= " AND user_id = '" . $auth->auth['uid'] . "'";
			$db->query($query);
			if ($db->next_record()) {
				if ($db->f('calpermission') == 2) {
					$permission = CALENDAR_PERMISSION_READABLE;
				} elseif ($db->f('calpermission') == 4) {
					$permission = CALENDAR_PERMISSION_WRITABLE;
				} else {
					$permission = CALENDAR_PERMISSION_FORBIDDEN;
					$_calendar_error->throwError(ERROR_CRITICAL,
							_("Sie haben keine Berechigung, diesen Kalender einzusehen."));
				}
			} else {
				$permission = CALENDAR_PERMISSION_WRITABLE;
			}
		}
		*/
		return Calendar::GetPermissionByUserRange($GLOBALS['user']->id, $this->user_id);
	}
	
	function showEdit () {
	
	}
	
	function toStringDay ($day_time, $start_time, $end_time, $restrictions = NULL, $sem_ids = NULL) {
		
		$this->view =& new DbCalendarDay($this, $day_time, NULL, $restrictions, $sem_ids);
		if ($this->checkPermission(CALENDAR_PERMISSION_OWN)) {
			$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Tagesansicht");
		} else if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
			$this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Tagesansicht");
		} else {
			$this->headline = sprintf(_("Terminkalender von %s %s - Tagesansicht"),
					get_fullname($this->getUserId()), $this->perm_string);
		}
		
		if ($this->havePermission(CALENDAR_PERMISSION_WRITABLE)) {
			$params = array('precol' => TRUE,
											'compact' => TRUE,
											'link_edit' => FALSE,
											'title_length' => 70,
											'height' => 20,
											'padding' => 3,
											'spacing' => 1,
											'bg_image' => 'big',
											'link_precol' => TRUE);
		} else {
			$params = array('precol' => TRUE,
											'compact' => TRUE,
											'link_edit' => FALSE,
											'title_length' => 70,
											'height' => 20,
											'padding' => 3,
											'spacing' => 1,
											'bg_image' => 'big',
											'link_precol' => FALSE);
		}
		
		return create_day_view($this, $start_time, $end_time,
				$this->getUserSettings('step_day'), $day_time, $params);
	}
	
	function toStringWeek ($week_time, $start_time, $end_time, $restrictions = NULL, $sem_ids = NULL) {
		
		$this->view =& new DbCalendarWeek($this, $week_time,
				$this->getUserSettings('type_week'), $restrictions, $sem_ids);
		if ($this->checkPermission(CALENDAR_PERMISSION_OWN)) {
			$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Wochenansicht");
		} else if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
			$this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Wochenansicht");
		} else {
			$this->headline = sprintf(_("Terminkalender von %s %s - Wochenansicht"),
					get_fullname($this->getUserId()), $this->perm_string);
		}
		if ($this->havePermission(CALENDAR_PERMISSION_WRITABLE)) {
			$string = create_week_view($this->view, $start_time, $end_time,
					$this->getUserSettings('step_week'),
					FALSE, $this->getUserSettings('link_edit'));
		} else {
			$string = create_week_view($this->view, $start_time, $end_time,
					$this->getUserSettings('step_week'),
					FALSE, FALSE);
		}
		
		return $string;
	}
	
	function toStringMonth ($month_time, $step = NULL, $restrictions = NULL, $sem_ids = NULL) {
		
		$this->view =& new DbCalendarMonth($this, $month_time, $restrictions, $sem_ids);
		if ($this->checkPermission(CALENDAR_PERMISSION_OWN)) {
			$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Monatsansicht");
		} else if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
			$this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Monatsansicht");
		} else {
			$this->headline = sprintf(_("Terminkalender von %s %s - Monatsansicht"),
					get_fullname($this->getUserId()), $this->perm_string);
		}
		$this->view->sort();
		
		return create_month_view($this, $month_time, $step);
	}
	
	function toStringYear ($year_time, $restrictions = NULL, $sem_ids = NULL) {
		
		$this->view =& new DbCalendarYear($this, $year_time, $restrictions, $sem_ids);
		if ($this->checkPermission(CALENDAR_PERMISSION_OWN)) {
			$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Jahresansicht");
		} else if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
			$this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Jahresansicht");
		} else {
			$this->headline = sprintf(_("Terminkalender von %s %s - Jahresansicht"),
					get_fullname($this->getUserId()), $this->perm_string);
		}
		
		return create_year_view($this);
	}
		
	function restoreEvent ($event_id) {
		global $_calendar_error;
		
		/*if ($_REQUEST['evtype'] == 'semcal') {
			$this->event =& new DbSeminarCalendarEvent($this, $event_id);
		} else {*/
			$this->event =& new DbCalendarEvent($this, $event_id);
	//	}
		if ($this->getRange() == CALENDAR_RANGE_SEM || $this->getRange() == CALENDAR_RANGE_INST) {
			$this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Termin bearbeiten");
		} else if ($this->checkPermission(CALENDAR_PERMISSION_OWN)) {
			$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Termin bearbeiten");
		} else {
			if ($this->event->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
				$this->headline = sprintf(_("Terminkalender von %s %s - Termin bearbeiten"),
						get_fullname($this->getUserId()), $this->perm_string);
			} elseif ($this->event->havePermission(CALENDAR_EVENT_PERM_READABLE)) {
				$this->headline = sprintf(_("Terminkalender von %s %s - Termin-Details"),
						get_fullname($this->getUserId()), $this->perm_string);
			} else {
				$_calendar_error->throwError(ERROR_CRITICAL,
						_("Sie haben keine Berechtigung, diesen Termin einzusehen."));
			}
		}
		/*
		elseif ($this->havePermission(CALENDAR_PERMISSION_WRITABLE)) {
			if ($this->event->getPermission() == CALENDAR_EVENT_PERM_WRITABLE) {
				$this->headline = sprintf(_("Terminkalender von %s %s - Termin bearbeiten"),
						get_fullname($this->getUserId()), $this->perm_string);
			} else {
				$this->headline = sprintf(_("Terminkalender von %s %s - Termin-Details"),
						get_fullname($this->getUserId()), $this->perm_string);
			}
		} elseif ($this->havePermission(CALENDAR_PERMISSION_READABLE)) {
			$this->headline = sprintf(_("Terminkalender von %s %s - Termin-Details"),
					get_fullname($this->getUserId()), $this->perm_string);
		} else {
			$_calendar_error->throwError(ERROR_CRITICAL,
					_("Sie haben keine Berechtigung, diesen Termin einzusehen."));
		}
		*/
	}
	
	function restoreSeminarEvent ($event_id) {
		$this->event =& new SeminarEvent($event_id);
		
		$this->headline = _("Mein pers&ouml;nlicher Terminkalender - Veranstaltungstermin");
	}
	
	function createSeminarEvent ($type) {
		if ($type == 'sem') {
			$this->event =& new SeminarEvent();
		} elseif ($type == 'semcal') {
			$this->event =& new SeminarCalendarEvent();
		}
	}
	
	function addEventObj (&$event, $updated, $selected_users = NULL) {
		global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CHAT;
		
		if ($this->havePermission(CALENDAR_PERMISSION_WRITABLE)) {
			$this->event = $event;
			// send a message if it is not the users own calendar
			if (!$this->checkPermission(CALENDAR_PERMISSION_OWN) && $this->getRange() == CALENDAR_RANGE_USER) {
				include_once('lib/messaging.inc.php');
				$message = new messaging();
				$event_data = '';
				
				if ($updated) {
					$msg_text = sprintf(_("%s hat einen Termin in Ihrem Kalender geändert."),
							get_fullname());
					$subject = sprintf(_("Termin am %s geändert"), 
							$this->event->toStringDate('SHORT_DAY'));
					$msg_text .= '\n\n**';
				}
				else	{
					$msg_text = sprintf(_("%s hat einen neuen Termin in Ihren Kalender eingetragen."),
							get_fullname());
					$subject = sprintf(_("Neuer Termin am %s"),
							$this->event->toStringDate('SHORT_DAY'));
					$msg_text .= '\n\n**';
				}
				$msg_text .=  _("Zeit:") . '** ' . $this->event->toStringDate('LONG') . '\n**';
				$msg_text .= _("Zusammenfassung:") . '** ' . $this->event->getTitle() . '\n';
				if ($event_data = $this->event->getDescription())
					$msg_text .= '**' . _("Beschreibung:") . "** $event_data\n";
				if ($event_data = $this->event->toStringCategories())
					$msg_text .= '**' . _("Kategorie:") . "** $event_data\n";
				if ($event_data = $this->event->toStringPriority())
					$msg_text .= '**' . _("Priorität:") . "** $event_data\n";
				if ($event_data = $this->event->toStringAccessibility())
					$msg_text .= '**' . _("Zugriff:") . "** $event_data\n";
				if ($event_data = $this->event->toStringRecurrence())
					$msg_text .= '**' . _("Wiederholung:") . "** $event_data\n";
				
				$message->insert_message($msg_text, $this->user_name,
						'____%system%____', '', '', '', '', $subject);
			}
		
			$this->event->save();
		}
	
	}
	
	function deleteEvent ($event_id) {
		global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CHAT;
		
		if ($this->havePermission(CALENDAR_PERMISSION_WRITABLE)) {
			
			$this->event =& new DbCalendarEvent($this, $event_id);
			
			if (!$this->event->havePermission(CALENDAR_EVENT_PERM_WRITABLE)) {
				$this->event = NULL;
				
				return FALSE;
			}
			
			if (!$this->checkPermission(CALENDAR_PERMISSION_OWN) && $this->getRange() == CALENDAR_RANGE_USER) {
				include_once('lib/messaging.inc.php');
				$message = new messaging();
				$event_data = '';
				
				$subject = sprintf(_("Termin am %s gelöscht"),
						$this->event->toStringDate('SHORT_DAY'));
				$msg_text = sprintf(_("%s hat folgenden Termin in Ihrem Kalender gelöscht:"),
						get_fullname());
				$msg_text .= '\n\n**';
				
				$msg_text .=  _("Zeit:") . '** ' . $this->event->toStringDate('LONG') . '\n**';
				$msg_text .= _("Zusammenfassung:") . '** ' . $this->event->getTitle() . '\n';
				if ($event_data = $this->event->getDescription())
					$msg_text .= '**' . _("Beschreibung:") . "** $event_data\n";
				if ($event_data = $this->event->toStringCategories())
					$msg_text .= '**' . _("Kategorie:") . "** $event_data\n";
				if ($event_data = $this->event->toStringPriority())
					$msg_text .= '**' . _("Priorität:") . "** $event_data\n";
				if ($event_data = $this->event->toStringAccessibility())
					$msg_text .= '**' . _("Zugriff:") . "** $event_data\n";
				if ($event_data = $this->event->toStringRecurrence())
					$msg_text .= '**' . _("Wiederholung:") . "** $event_data\n";
			
				$message->insert_message($msg_text, $this->user_name,
						'____%system%____', '', '', '', '', $subject);
			}
			
			$this->event->delete();
			
			return TRUE;
		}
		$this->event = NULL;
		
		return FALSE;
	}

}
		
?>
