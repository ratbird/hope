<?php
/*
 * This class displays a seminar-schedule for
 * users on a seminar-based view and for admins on an institute based view
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/classes/WidgetHelper.php';

/**
 * Personal schedule controller.
 *
 * @since      2.0
 */
class ScheduleWidget extends StudIPPlugin implements PortalPlugin
{
    public function getPortalTemplate() {
        $c = new AuthenticatedController(new StudipDispatcher());
        $response = $c->relay('calendar/schedule/index');

        return preg_replace('/<a.*>(.*)<\/a>/msU', '$1', $response->body);
    }

  /**
     * Callback function being called before an action is executed. If this
     * function does not return FALSE, the action will be called, otherwise
     * an error will be generated and processing will be aborted. If this function
     * already #rendered or #redirected, further processing of the action is
     * withheld.
     *
     * @param string  Name of the action to perform.
     * @param array   An array of arguments to the action.
     *
     * @return bool
     */
    function  __construct() {
        parent::__construct();
    }


    function getPluginName(){
        return _("Mein Stundenplan");
    }

}
