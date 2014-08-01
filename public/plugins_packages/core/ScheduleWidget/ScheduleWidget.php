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

require_once('app/models/calendar/schedule.php');

class ScheduleWidget extends StudIPPlugin implements PortalPlugin
{
    public function getPluginName()
    {
        return _('Mein Stundenplan');
    }

    public function getPortalTemplate()
    {
        $view = CalendarScheduleModel::getUserCalendarView($GLOBALS['user']->id, true);
        $view->setReadOnly();

        $template = $GLOBALS['template_factory']->open('shared/string');
        $template->content = $view->render();

        return $template;
    }
}
