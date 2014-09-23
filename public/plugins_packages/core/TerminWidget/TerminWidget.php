<?php
/*
 * TerminWidget.php - A portal plugin for dates
 *
 * Copyright (C) 2014 - Andr� Kla�en <klassen@elan-ev.de>
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/calendar/contentbox.php';

class TerminWidget extends StudIPPlugin implements PortalPlugin
{
    public function getPluginName()
    {
        return _('Meine aktuellen Termine');
    }

    public function getPortalTemplate()
    {
        $dispatcher = new StudipDispatcher();
        $controller = new Calendar_ContentboxController($dispatcher);
        $response = $controller->relay('calendar/contentbox/display/'.$GLOBALS['user']->id);
        $template = $GLOBALS['template_factory']->open('shared/string');
        $template->content = $response->body;

        $navigation = new Navigation('', 'calendar.php', array('cmd' => 'edit'));
        $navigation->setImage('icons/16/blue/add.png', array('title' => _('Neuen Termin anlegen')));
        $template->icons = array($navigation);

        return $template;
    }
}
