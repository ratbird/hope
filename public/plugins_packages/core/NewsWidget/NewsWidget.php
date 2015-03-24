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
    public function getPluginName()
    {
        return _('Ankündigungen');
    }

    function getPortalTemplate()
    {
        $dispatcher = new StudipDispatcher();
        $controller = new NewsController($dispatcher);
        $response = $controller->relay('news/display/studip');
        $template = $GLOBALS['template_factory']->open('shared/string');
        $template->content = $response->body;

        if (get_config('NEWS_RSS_EXPORT_ENABLE')) {
            if ($rss_id = StudipNews::GetRssIdFromRangeId('studip')) {
                $navigation = new Navigation('', 'rss.php', array('id' => $rss_id));
                $navigation->setImage('icons/16/blue/rss.png', array('title' => _('RSS-Feed')));
                $icons[] = $navigation;
            }
        }

        if ($GLOBALS['perm']->have_perm('root')) {
            $navigation = new Navigation('', 'dispatch.php/news/edit_news/new/studip');
            $navigation->setImage('icons/16/blue/add.png', array('rel' => 'get_dialog', 'title' =>_('Ankündigungen bearbeiten')));
            $icons[] = $navigation;
            if (get_config('NEWS_RSS_EXPORT_ENABLE')) {
                $navigation = new Navigation('', 'dispatch.php/news/rss_config/studip');
                $navigation->setImage('icons/16/blue/add/rss.png', array('data-dialog' => 'size=auto', 'title' =>_('RSS-Feed konfigurieren')));
                $icons[] = $navigation;
            }
        }

        $template->icons = $icons;

        return $template;
    }
}
