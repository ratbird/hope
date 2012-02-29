<?php
/*
 * ical.php - iCalendar export controller
 *
 * Copyright (C) 2011 - Peter Thienel <thienel@data-quest.de>, Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once($GLOBALS['RELATIVE_PATH_CALENDAR'].'/lib/sync/CalendarExport.class.php');
require_once($GLOBALS['RELATIVE_PATH_CALENDAR'].'/lib/sync/CalendarWriterICalendar.class.php');
require_once 'studip_controller.php';
require_once('app/models/ical_export.php');

class iCalController extends StudipController
{

    /**
     * Handels the download the calendar data as iCalendar for the
     * user identified by $key.
     *
     * 
     * @global Seminar_User $user
     * @global Seminar_Perm $perm
     * @param string $key
     * @param string $type type of export
     */
    function index_action($key)
    {
        global $user, $perm;
        
        if (isset($key) && trim($key)) {
            $user_id = IcalExport::getUserIdByKey($key);
        } else {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            if (isset($username) && isset($password)) {
                $result = StudipAuthAbstract::CheckAuthentication($username, $password);
            }

            if (isset($result) && $result['uid'] !== false) {
                $user_id = get_userid($username);
            } else {
                header('WWW-Authenticate: Basic realm="Stud.IP Login"');
                header('HTTP/1.1 401 Unauthorized');

                $exception = new AccessDeniedException('invalid password');
                $this->render_text($template_factory->render('access_denied_exception',
                        compact('exception')));
            }
        }

        if ($user_id) {
            if (!is_object($user) || $user->id == 'nobody') {
                $user = new Seminar_User($user_id);
                $perm = new Seminar_Perm();
            }

            $extype = 'ALL_EVENTS';
            $export = new CalendarExport(new CalendarWriterICalendar());
            $export->exportFromDatabase($user_id, 0, 2114377200, 'ALL_EVENTS',
                    Calendar::getBindSeminare($user_id));

            if ($GLOBALS['_calendar_error']->getMaxStatus(ERROR_CRITICAL)) {
                header('HTTP/1.1 404 Not Found');
                exit;
            }
            $content = join($export->getExport());
            header('Content-Type: text/calendar');
            header('Content-Disposition: attachment; filename="studip.ics"');
            header('Content-Transfer-Encoding: binary' );
            header('Pragma: public');
            header('Cache-Control: private');
            header('Content-Length:' . strlen($content));
            $this->render_text($content);
        } else {
            // delayed response to prevent brute force attacks
            
            header('HTTP/1.1 404 Not Found');
            exit;
        }
    }

}
