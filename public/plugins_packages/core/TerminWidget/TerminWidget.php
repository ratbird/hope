<?php
/*
 * TerminWidget.php - A portal plugin for dates
 *
 * Copyright (C) 2014 - André Klaßen <klassen@elan-ev.de>
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/termine.php';

class TerminWidget extends StudIPPlugin implements PortalPlugin {
    
    public function getPortalTemplate() {
        $dispatcher = new StudipDispatcher();
        $controller = new TermineController($dispatcher);
        $response = $controller->relay('calendar/contentbox/display/'.$GLOBALS['user']->id);
        $template = $GLOBALS['template_factory']->open('shared/string');
        $template->content = $response->body;

        return $template;
    }

    function getHeaderOptions() {
        global $perm, $user;
        $options = array();
        $options[] = array('url' => URLHelper::getLink('calendar.php', array('cmd' => 'edit', 'source_page' => URLHelper::getURL())),
                'img' => 'icons/16/blue/admin.png',
                'tooltip' =>_('Neuen Termin anlegen'));
        return $options;
    }

    function getPluginName(){
        return _("Meine aktuellen Termine");
    }
}
