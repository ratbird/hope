<?
# Lifter002: TODO
# Lifter007: TODO

/**
 * SingleCalendar.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

require_once($RELATIVE_PATH_CALENDAR . '/lib/Calendar.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/SeminarCalendarEvent.class.php');

class SingleCalendar extends Calendar
{

    var $view = NULL;
    var $perm_string = '';

    function SingleCalendar($range_id, $permission = NULL)
    {
        Calendar::Calendar($range_id);
        if (is_null($permission)) {
            $permission = $this->checkUserPermissions($this->user_id);
        }
        // switch back to the users own calendar, if the user has no permission
        if ($permission == Calendar::PERMISSION_FORBIDDEN) {
            $this->user_id = $GLOBALS['user']->id;
            $this->permissions = Calendar::PERMISSION_OWN;
            $this->user_name = $GLOBALS['auth']->auth['uname'];
            $this->perm_string = _("(schreibberechtigt)");
        } else {
            $this->permission = $permission;
            if ($permission == Calendar::PERMISSION_OWN)
                $this->perm_string = _("(schreibberechtigt)");
            elseif ($permission == Calendar::PERMISSION_WRITABLE)
                $this->perm_string = _("(schreibberechtigt)");
            elseif ($permission == Calendar::PERMISSION_READABLE)
                $this->perm_string = _("(leseberechtigt)");
        }
        CalendarDriver::getInstance($this->user_id, $this->permission);
    }

    function getId()
    {
        return $this->getUserId();
    }

    function checkUserPermissions($user_id)
    {
        return Calendar::GetPermissionByUserRange($GLOBALS['user']->id, $this->user_id);
    }

    function showEdit()
    {

    }

    function toStringDay($day_time, $start_time, $end_time, $restrictions = NULL, $sem_ids = NULL)
    {

        $this->view = new DbCalendarDay($this, $day_time, NULL, $restrictions, $sem_ids);

        if ($this->havePermission(Calendar::PERMISSION_WRITABLE)) {
            $params = array('precol' => true,
                'compact' => true,
                'link_edit' => false,
                'title_length' => 70,
                'height' => 20,
                'padding' => 3,
                'spacing' => 1,
                'bg_image' => 'big',
                'link_precol' => true);
        } else {
            $params = array('precol' => true,
                'compact' => true,
                'link_edit' => false,
                'title_length' => 70,
                'height' => 20,
                'padding' => 3,
                'spacing' => 1,
                'bg_image' => 'big',
                'link_precol' => false);
        }

        $tmpl = $GLOBALS['template_factory']->open('calendar/day');
        $tmpl->writable = $this->havePermission(Calendar::PERMISSION_WRITABLE);
        $tmpl->calendar = $this;
        $tmpl->start = $start_time * 3600;
        $tmpl->end = $end_time * 3600;
        $tmpl->step = $this->getUserSettings('step_day');
        $tmpl->params = $params;
        $tmpl->atime = $day_time;
        return $tmpl->render();
    }

    function toStringWeek($week_time, $start_time, $end_time, $restrictions = NULL, $sem_ids = NULL)
    {

        $this->view = new DbCalendarWeek($this, $week_time,
                        $this->getUserSettings('type_week'), $restrictions, $sem_ids);

        $tmpl = $GLOBALS['template_factory']->open('calendar/week_table');
        $tmpl->calendar = $this;
        $tmpl->start = $start_time;
        $tmpl->end = $end_time;
        $tmpl->step = $this->getUserSettings('step_week');

        return $tmpl->render();
    }

    function toStringMonth($month_time, $step = NULL, $restrictions = NULL, $sem_ids = NULL)
    {

        $this->view = new DbCalendarMonth($this, $month_time, $restrictions, $sem_ids);
        $this->view->sort();

        return create_month_view($this, $month_time, $step);
    }

    function toStringYear($year_time, $restrictions = NULL, $sem_ids = NULL)
    {

        $this->view = new DbCalendarYear($this, $year_time, $restrictions, $sem_ids);

        return create_year_view($this);
    }

    function restoreEvent($event_id)
    {
        global $_calendar_error;

        /* if ($_REQUEST['evtype'] == 'semcal') {
          $this->event = new DbSeminarCalendarEvent($this, $event_id);
          } else { */
        $this->event = new DbCalendarEvent($this, $event_id);
        // }
        if ($this->getRange() == Calendar::RANGE_SEM || $this->getRange() == Calendar::RANGE_INST) {
            $this->headline = getHeaderLine($this->user_id) . ' - ' . _("Terminkalender - Termin bearbeiten");
        } else if ($this->checkPermission(Calendar::PERMISSION_OWN)) {
            $this->headline = _("Mein pers&ouml;nlicher Terminkalender - Termin bearbeiten");
        } else {
            if ($this->event->havePermission(Event::PERMISSION_WRITABLE)) {
                $this->headline = sprintf(_("Terminkalender von %s %s - Termin bearbeiten"), get_fullname($this->getUserId()), $this->perm_string);
            } elseif ($this->event->havePermission(Event::PERMISSION_READABLE)) {
                $this->headline = sprintf(_("Terminkalender von %s %s - Termin-Details"), get_fullname($this->getUserId()), $this->perm_string);
            } else {
                $_calendar_error->throwError(ErrorHandler::ERROR_CRITICAL, _("Sie haben keine Berechtigung, diesen Termin einzusehen."));
            }
        }
    }

    function restoreSeminarEvent($event_id)
    {
        $this->event = new SeminarEvent($event_id);
        $this->headline = _("Mein pers&ouml;nlicher Terminkalender - Veranstaltungstermin");
    }

    function createSeminarEvent($type)
    {
        if ($type == 'sem') {
            $this->event = new SeminarEvent();
        } elseif ($type == 'semcal') {
            $this->event = new SeminarCalendarEvent();
        }
    }

    function addEventObj(&$event, $updated, $selected_users = NULL)
    {
        global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CHAT;

        if ($this->havePermission(Calendar::PERMISSION_WRITABLE)) {
            $this->event = $event;
            // send a message if it is not the users own calendar
            if (!$this->checkPermission(Calendar::PERMISSION_OWN) && $this->getRange() == Calendar::RANGE_USER) {
                include_once('lib/messaging.inc.php');
                $message = new messaging();
                $event_data = '';

                if ($updated) {
                    $msg_text = sprintf(_("%s hat einen Termin in Ihrem Kalender geändert."), get_fullname());
                    $subject = sprintf(_("Termin am %s geändert"), $this->event->toStringDate('SHORT_DAY'));
                    $msg_text .= '\n\n**';
                } else {
                    $msg_text = sprintf(_("%s hat einen neuen Termin in Ihren Kalender eingetragen."), get_fullname());
                    $subject = sprintf(_("Neuer Termin am %s"), $this->event->toStringDate('SHORT_DAY'));
                    $msg_text .= '\n\n**';
                }
                $msg_text .= _("Zeit:") . '** ' . $this->event->toStringDate('LONG') . '\n**';
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

                $message->insert_message($msg_text, $this->user_name, '____%system%____', '', '', '', '', $subject);
            }

            $this->event->save();
        }
    }

    function deleteEvent($event_id)
    {
        global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CHAT;

        if ($this->havePermission(Calendar::PERMISSION_WRITABLE)) {

            $this->event = new DbCalendarEvent($this, $event_id);

            if (!$this->event->havePermission(Event::PERMISSION_WRITABLE)) {
                $this->event = NULL;

                return false;
            }

            if (!$this->checkPermission(Calendar::PERMISSION_OWN) && $this->getRange() == Calendar::RANGE_USER) {
                include_once('lib/messaging.inc.php');
                $message = new messaging();
                $event_data = '';

                $subject = sprintf(_("Termin am %s gelöscht"), $this->event->toStringDate('SHORT_DAY'));
                $msg_text = sprintf(_("%s hat folgenden Termin in Ihrem Kalender gelöscht:"), get_fullname());
                $msg_text .= '\n\n**';

                $msg_text .= _("Zeit:") . '** ' . $this->event->toStringDate('LONG') . '\n**';
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

                $message->insert_message($msg_text, $this->user_name, '____%system%____', '', '', '', '', $subject);
            }

            $this->event->delete();

            return true;
        }
        $this->event = NULL;

        return false;
    }

}
