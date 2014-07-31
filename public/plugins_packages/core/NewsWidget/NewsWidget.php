<?php
/*
 * news.php - News controller
 *
 * Copyright (C) 2014 - Nadine Werner <nadwerner@uos.de>
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/news.php';

class NewsWidget extends StudIPPlugin implements PortalPlugin
{
    function getPortalTemplate()
    {
        $dispatcher = new StudipDispatcher();
        $controller = new NewsController($dispatcher);
        $response = $controller->relay('news/display/studip');
        $template = $GLOBALS['template_factory']->open('shared/string');
        $template->content = $response->body;

        if (get_config('NEWS_RSS_EXPORT_ENABLE')) {
            $navigation = new Navigation('', 'rss.php', array('id' => StudipNews::GetRssIdFromRangeId('studip')));
            $navigation->setImage('icons/16/blue/rss.png', array('title' => _('RSS-Feed')));
            $icons[] = $navigation;
        }

        if ($GLOBALS['perm']->have_perm('root')) {
            $navigation = new Navigation('', 'dispatch.php/news/edit_news/new');
            $navigation->setImage('icons/16/blue/add.png', array('rel' => 'get_dialog', 'title' =>_('Ankündigungen bearbeiten')));
            $icons[] = $navigation;
        }

        $template->title = _('Ankündigungen');
        $template->icons = $icons;

        return $template;
    }
}
