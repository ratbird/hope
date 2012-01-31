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
        $this->set_layout(NULL);
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
        foreach(Request::optionArray('selected_dates') as $one) {
            $date = new SingleDate($one);
            if ($date->getStartTime()) {
                $timestamps[] = $date->getStartTime();
                $timestamps[] = $date->getEndTime();
                $event = new AssignEvent($date->getTerminID(), $date->getStartTime(), $date->getEndTime(), $room, null, '');
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
                foreach(array_keys($details) as $termin_id) {
                    if ($dates[$termin_id]->getResourceId() == $room_id) {
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
    
    function render_json($data){
        $this->set_content_type('application/json;charset=utf-8');
        return $this->render_text(json_encode($data));
    }
}
