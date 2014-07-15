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


class ScheduleWidget extends StudIPPlugin implements PortalPlugin
{
    public function getPortalTemplate() {
        
        // render schedule-action
        $c = new AuthenticatedController(new StudipDispatcher());
        try {
            $response = $c->relay('calendar/schedule/index');
        } catch (Exception $e) {
            // schedule-controller throws an exception if user has no schedule!
        }

        // remove sidebar widgets
        $sidebar = Sidebar::get();
        try {
            $sidebar->removeWidget('calendar/schedule/semester');
            $sidebar->removeWidget('calendar/schedule/actions');
            $sidebar->removeWidget('calendar/schedule/print');
            $sidebar->removeWidget('calendar/schedule/options');
        } catch (Exception $e) {
            // removeWigdet throws an Exception when trying to remove an unknown widget
        }
        
        // take care of Navigation
        try {
            Navigation::getItem('/calendar/schedule')->setActive(false);
        } catch (Exception $e) {
            // navigation-item may not exists, so catch the potential exception
        }

        
        // remove links and return template-string
        return preg_replace('/<a.*>(.*)<\/a>/msU', '$1', $response->body);
    }

    function getHeaderOptions()
    {
        return array(
            array(
                'url' => URLHelper::getLink('dispatch.php/calendar/schedule?show_settings=true'),
                'img' => 'icons/16/blue/admin.png',
                'tooltip' => _('Einstellungen im Stundenplan bearbeiten')
            )
        );
    }

    function getPluginName(){
        return _("Mein Stundenplan");
    }

}
