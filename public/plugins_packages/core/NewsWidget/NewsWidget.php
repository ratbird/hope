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

        return $template;
    }

    function getPluginName()
    {
        return _('Ankündigungen');
    }

    function getHeaderOptions()
    {
        $options = array();
        $show_admin = $GLOBALS['perm']->have_perm('root');
        $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                   ? StudipNews::GetRssIdFromRangeId('studip')
                   : false;

        if ($rss_id) {
           $options[] = array('url' => URLHelper::getLink('rss.php?id='. $rss_id),
                              'img' => 'icons/16/blue/rss.png',
                              'tooltip' => _('RSS-Feed'));
        }
        if ($show_admin) {
            $options[] = array('url' => URLHelper::getLink('dispatch.php/news/admin_news'),
                               'img' => 'icons/16/blue/admin.png',
                               'tooltip' =>_('Ankündigungen bearbeiten'));
        }
        return $options;
    }
}
