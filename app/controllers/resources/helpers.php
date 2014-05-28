<?php
/**
 * helpers.php - ajax helpers for room/resources
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */
//uarg
global $RELATIVE_PATH_RESOURCES;

require_once 'lib/resources/lib/ResourcesUserRoomsList.class.php';
require_once 'lib/resources/lib/CheckMultipleOverlaps.class.php';
require_once 'app/controllers/authenticated_controller.php';

class Resources_HelpersController extends AuthenticatedController
{
/**
     * common tasks for all actions
     */
    function before_filter(&$action, &$args)
    {
        $this->current_action = $action;
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        $this->set_content_type('text/html;charset=windows-1252');
    }

    function bookable_rooms_action()
    {
        if (!getGlobalPerms($GLOBALS['user']->id) == 'admin') {
            $resList = new ResourcesUserRoomsList($GLOBALS['user']->id, false, false, false);
            if (!$resList->roomsExist()) {
                throw new AccessDeniedException('');
            }
        }
        $select_options = Request::optionArray('rooms');
        $rooms = array_filter($select_options, function($v) {return strlen($v) === 32;});
        $events = array();
        $dates = array();
        $timestamps = array();
        if (count(Request::getArray('new_date'))) {
            $new_date = array();
            foreach (Request::getArray('new_date') as $one) {
                if ($one['name'] == 'startDate') {
                    $dmy = explode('.', $one['value']);
                    $new_date['day'] = (int)$dmy[0];
                    $new_date['month'] = (int)$dmy[1];
                    $new_date['year'] = (int)$dmy[2];
                }
                $new_date[$one['name']] = (int)$one['value'];
            }

            if (check_singledate($new_date['day'], $new_date['month'], $new_date['year'], $new_date['start_stunde'],
            $new_date['start_minute'], $new_date['end_stunde'], $new_date['end_minute'])) {
                $start = mktime($new_date['start_stunde'], $new_date['start_minute'], 0, $new_date['month'], $new_date['day'], $new_date['year']);
                $ende = mktime($new_date['end_stunde'], $new_date['end_minute'], 0, $new_date['month'], $new_date['day'], $new_date['year']);
                $timestamps[] = $start;
                $timestamps[] = $ende;
                $event = new AssignEvent('new_date', $start, $ende, null, null, '');
                $events[$event->getId()] = $event;
            }
        }
        foreach(Request::optionArray('selected_dates') as $one) {
            $date = new SingleDate($one);
            if ($date->getStartTime()) {
                $timestamps[] = $date->getStartTime();
                $timestamps[] = $date->getEndTime();
                $event = new AssignEvent($date->getTerminID(), $date->getStartTime(), $date->getEndTime(), null, null, '');
                $events[$event->getId()] = $event;
                $dates[$date->getTerminID()] = $date;
            }
        }
        if (count($events)) {
            $result = array();
            $checker = new CheckMultipleOverlaps();
            $checker->setTimeRange(min($timestamps), max($timestamps));
            foreach($rooms as $room) $checker->addResource($room);
            $checker->checkOverlap($events, $result, "assign_id");
            foreach((array)$result as $room_id => $details) {
                foreach($details as $termin_id => $conflicts) {
                    if ($termin_id == 'new_date' && Request::option('singleDateID')) {
                        $assign_id = SingleDateDB::getAssignID(Request::option('singleDateID'));
                    } else {
                        $assign_id = SingleDateDB::getAssignID($termin_id);
                    }
                    $filter = function($a) use ($assign_id)
                        {
                            if ($a['assign_id'] && $a['assign_id'] == $assign_id) {
                                return false;
                            }
                            return true;
                        };
                    if (!count(array_filter($conflicts, $filter))) {
                        unset($result[$room_id][$termin_id]);
                    }
                }
            }
            $result = array_filter($result);
            $this->render_json(array_keys($result));
            return;
        }

        $this->render_nothing();
    }

    function resource_message_action($resource_id)
    {
        $r_perms = new ResourceObjectPerms($resource_id, $GLOBALS['user']->id);
        if (!$r_perms->havePerm('admin')) {
            throw new AccessDeniedException('');
        }
            $this->resource = new ResourceObject($resource_id);
            $title = sprintf(_("Nutzer von %s benachrichtigen"),htmlReady($this->resource->getName()));
            $form_fields['start_day'] = array('type' => 'text', 'size' => '10', 'required' => true, 'caption' => _("Belegungen berücksichtigen von"));
            $form_fields['start_day']['attributes'] = array('onMouseOver' => 'jQuery(this).datepicker();this.blur();', 'onChange' => '$(this).closest("form").submit();');
            $form_fields['start_day']['default_value'] = strftime('%x');
            $form_fields['end_day'] = array('type' => 'text', 'size' => '10', 'required' => true, 'caption' => _("Belegungen berücksichtigen bis"));
            $form_fields['end_day']['attributes'] = array('onMouseOver' => 'jQuery(this).datepicker();this.blur();', 'onChange' => '$(this).closest("form").submit();');
            $form_fields['end_day']['default_value'] = strftime('%x', strtotime('+6 months'));
            $form_fields['subject'] = array('type' => 'text', 'size' => '200','attributes' => array('style' => 'width:100%'), 'required' => true, 'caption' => _("Betreff"));
            $form_fields['subject']['default_value'] = $this->resource->getName();
            $form_fields['message'] = array('caption' => _("Nachricht"), 'type' => 'textarea', 'required' => true, 'attributes' => array('rows' => 4, 'style' => 'width:100%'));

            $form_buttons['save_close'] = array('caption' => _('OK'), 'info' => _("Benachrichtigung verschicken und Dialog schließen"));

            $form = new StudipForm($form_fields, $form_buttons, 'resource_message', false);

            $start_time = strtotime($form->getFormFieldValue('start_day'));
            $end_time = strtotime($form->getFormFieldValue('end_day'));

            $assign_events = new AssignEventList($start_time, $end_time, $resource_id, '', '', TRUE, 'all');
            $rec = array();
            while ($event = $assign_events->nextEvent()) {
                if ($owner_type = $event->getOwnerType()) {
                    if ($owner_type == 'date') {
                        $seminar = new Seminar(Seminar::GetSemIdByDateId($event->getAssignUserId()));
                        foreach($seminar->getMembers('dozent') as $dozent) {
                            $rec[$dozent['username']][] = strftime('%x %R', $event->begin) . ' - ' . strftime('%R', $event->end) . ' ' . $seminar->getName();
                        }
                    } else {
                        $rec[get_username($event->getAssignUserId())][] = strftime('%x %R', $event->begin) . ' - ' . strftime('%R', $event->end);
                    }
                }
            }

            if ($form->isSended() && count($rec) && $form->getFormFieldValue('message')) {
                $messaging = new Messaging();
                $ok = $messaging->insert_message($form->getFormFieldValue('message'),
                                           array_keys($rec),
                                           $GLOBALS['user']->id,
                                           null,
                                           null,
                                           null,
                                           '',
                                           $form->getFormFieldValue('subject'),
                                           true);
                PageLayout::postMessage(MessageBox::success(sprintf(_("Die Nachricht wurde an %s Nutzer verschickt"), $ok)));
                return $this->redirect(URLHelper::getUrl('resources.php?view=resources'));
            }

            if (!count($rec)) {
                PageLayout::postMessage(MessageBox::error(sprintf(_("Im Zeitraum %s - %s wurden keine Belegungen gefunden!"), strftime('%x', $start_time), strftime('%x', $end_time))));
                $this->no_receiver = true;
            } else {
                $submessage = array();
                foreach ($rec as $username => $slots) {
                    $submessage[] = get_fullname_from_uname($username, 'full_rev_username', true) . ' '. sprintf(_('(%s Belegungen)'), count($slots));
                }
                PageLayout::postMessage(MessageBox::info(sprintf(_("Benachrichtigung an %s Nutzer verschicken"), count($rec)), $submessage, true));
            }
            $this->form = $form;
            $this->response->add_header('X-Title', $title);
    }

    function after_filter($action, $args)
    {
        if (Request::isXhr()) {
            foreach ($this->response->headers as $k => $v) {
                if ($k === 'Location') {
                    $this->response->headers['X-Location'] = $v;
                    unset($this->response->headers['Location']);
                    $this->response->set_status(200);
                    $this->response->body = '';
                }
            }
        }
        parent::after_filter($action, $args);
    }
}
