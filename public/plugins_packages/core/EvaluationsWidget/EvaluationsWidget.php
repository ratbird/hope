<?php
/*
 * EvaluationsWidget.php - widget plugin for start page
 *
 * Copyright (C) 2014 - Nadine Werner <nadwerner@uos.de>
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/vote.php';

class EvaluationsWidget extends StudIPPlugin implements PortalPlugin
{
    public function getPortalTemplate()
    {
        // include and show votes and tests
        if (get_config('VOTE_ENABLE')) {
            $dispatcher = new StudipDispatcher();
            $controller = new VoteController($dispatcher);
            $response = $controller->relay('vote/display/studip');
            $template = $GLOBALS['template_factory']->open('shared/string');
            $template->content = $response->body;

            return $template;
        }
    }

    public function getPluginName()
    {
        return _('Umfragen');
    }

    public function getHeaderOptions()
    {
        $options = array();
        $show_admin = $GLOBALS['perm']->have_perm('root');

        if ($show_admin) {
            $options[] = array('url' => URLHelper::getLink('admin_vote.php?page=overview&showrangeID=studip'),
                               'img' => 'icons/16/blue/admin.png',
                               'tooltip' =>_('Umfragen bearbeiten'));
        }
        return $options;
    }
}
