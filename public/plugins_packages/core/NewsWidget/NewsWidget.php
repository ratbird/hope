<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * news.php - News controller
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>, Rasmus Fuhse <fuhse@data-quest.de>,
 * Nadine Werner <nadwerner@uos.de>
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/functions.php';
require_once 'lib/user_visible.inc.php';
require_once 'lib/classes/WidgetHelper.php';

class NewsWidget extends StudIPPlugin implements PortalPlugin
{

    function __construct()
    {

        parent::__construct();
        PageLayout::addScript($this->getPluginUrl() . '/js/news.js');


    }

    function getPortalTemplate()
    {

        global $perm;
        $range_id = 'studip';
        $show_admin = $perm->have_perm('root');
        $limit = 0;
        $index_data = Request::getArray('index_data');
        $open = $index_data['nopen'] ;
        $width = "";
        $last_visited = 0;
        $cmd_data = $index_data;
        $plugin = $this;
        $widgetId = $this->widget_id;
        ///?bind Link param?? ::bindLinkParam('index_data', $index_data);
        if ($show_admin && $touch_id = Request::option('touch_news'))
        {
             StudipNews::TouchNews($touch_id);
        }
        $news = StudipNews::GetNewsByRange($range_id, true);

         // Adjust news' open state
        foreach ($news as $id => &$news_item) {
            $news_item['open'] = ($id == $open);
        }
         if ($SessSemName[1] == $range_id) {
            $admin_link = sprintf('new_%1$s=TRUE&view=news_%1$s', $SessSemName['class'] == 'sem' ? 'sem' : 'inst');
        } else if ($range_id == $auth->auth['uid']) {
            $admin_link = 'range_id=self';
        } else if ($range_id == 'studip') {
            $admin_link = 'range_id=studip';
        } else if (isDeputyEditAboutActivated() && isDeputy($auth->auth['uid'], $range_id, true)) {
            $admin_link = 'range_id=' . $range_id;
        }
         // Leave if there are no news and we are not an admin
         $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
         SkipLinks::addIndex(_('Ankündigungen'), 'news_box');
         if (!count($news) && !$show_admin) {
            $template = $this->factory->open('list-empty');
            $template->width      = $width;
            $template->admin_link = $admin_link;
         } else {
            $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                   ? StudipNews::GetRssIdFromRangeId($range_id)
                   : false;
            $template = $this->factory->open('list');
            $template->width      = $width;
            $template->rss_id     = $rss_id;
            $template->show_admin = $show_admin;
            $template->admin_link = $admin_link;
            $template->news       = $news;
            $template->cmd_data   = $cmd_data;
        }

        $template->plugin   = $plugin;
        $template->widgetId   = $widgetId;

        $template->title = _('Ankündigungen');
        $template->icon_url = 'icons/16/white/news.png';

        // TODO: Frameworkmethode dafür finden
        $template->admin_url = 'dispatch.php/news/edit_news/';
        $template->admin_title = _('Ankündigung erstellen');

        return $template;
    }

    function getPluginName(){
        return _("Ankündigungen");
    }

    function getContent()
    {
                global $perm;
        $range_id = 'studip';
        $show_admin = $perm->have_perm('root');
        $limit = 0;
        $open = $index_data['nopen'] ;
        $width = "";
        $last_visited = 0;
        $cmd_data = $index_data ;
        $plugin = $this;
        $widgetId = $this->widget_id;

        ///?bind Link param?? ::bindLinkParam('index_data', $index_data);
        if ($show_admin && $touch_id = Request::option('touch_news'))
        {
             StudipNews::TouchNews($touch_id);
        }
        $news = StudipNews::GetNewsByRange($range_id, true);

         // Adjust news' open state
        foreach ($news as $id => &$news_item) {
            $news_item['open'] = ($id == $open);
        }
         if ($SessSemName[1] == $range_id) {
            $admin_link = sprintf('new_%1$s=TRUE&view=news_%1$s', $SessSemName['class'] == 'sem' ? 'sem' : 'inst');
        } else if ($range_id == $auth->auth['uid']) {
            $admin_link = 'range_id=self';
        } else if ($range_id == 'studip') {
            $admin_link = 'range_id=studip';
        } else if (isDeputyEditAboutActivated() && isDeputy($auth->auth['uid'], $range_id, true)) {
            $admin_link = 'range_id=' . $range_id;
        }
         // Leave if there are no news and we are not an admin
        if (!count($news) && !$show_admin) {
            return NULL;
        }
         $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
         SkipLinks::addIndex(_('Ankündigungen'), 'news_box');
         if (!count($news)) {
            $template = $this->factory->open('list-empty');
            $template->width      = $width;
            $template->admin_link = $admin_link;
         } else {
            $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                   ? StudipNews::GetRssIdFromRangeId($range_id)
                   : false;
            $template = $this->factory->open('list');
            $template->width      = $width;
            $template->rss_id     = $rss_id;
            $template->show_admin = $show_admin;
            $template->admin_link = $admin_link;
            $template->news       = $news;
            $template->cmd_data   = $cmd_data;
            $template->plugin   = $plugin;
            $template->widgetId   = $widgetId;
        }

        return $template;
    }

    function getHeaderOptions()
    {

        global $perm;
        $options = array();
        $show_admin = $perm->have_perm('root');
        $range_id = 'studip';
        $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                   ? StudipNews::GetRssIdFromRangeId($range_id)
                   : false;
        if ($rss_id) {
           $options[] = array('url' => URLHelper::getLink('rss.php?id='. $rss_id),
                              'img' => 'icons/16/blue/rss.png',
                              'tooltip' => _('RSS-Feed'));
        }
        if ($show_admin) {
            $options[] = array('url' => URLHelper::getLink("dispatch.php/news/admin_news"),
                               'img' => 'icons/16/blue/admin.png',
                               'tooltip' =>_('Ankündigungen bearbeiten'));
        }
        return $options;
    }

    function getURL()
    {

    }

    function getRange(){
        global $user;
        return $user->id;
    }

    function get_news_action()
    {
        if ($_POST)
        {
            $id = $_POST['id'];
            $widgetId = $_POST['widgetId'];

        }
        if (is_null($id)) {
            $this->set_status(400);
            return $this->render_nothing();
        }

        $news = new StudipNews($id);

        if ($news->isNew()) {
            $this->set_status(404);
            return $this->render_nothing();
        }


        // check for permission for at least one of those ranges
        list($permitted, $show_admin) = $this->ajaxified_news_has_permission($news);
        if (!$permitted) {
            $this->set_status(401);
            return $this->render_nothing();
        }
        $newscontent = $news->toArray();

        // use the same logic here as in show_news_item()
        if ($newscontent['user_id'] != $GLOBALS['auth']->auth['uid']) {
            object_add_view($id);
        }

        object_set_visit($id, "news");
        if ($GLOBALS['user']->id == 'nobody') {
            $newscontent['allow_comments'] = 0;
        }
        $this->news = $newscontent;
        $plugin = $this;
        $this->content = $this->show_news_item_content_action($newscontent,
                                                array(),
                                                $show_admin,
                                                Request::get('admin_link'),
                                                $plugin,
                                                $widgetId
        );
    }
    function show_news_action($range_id, $show_admin = FALSE, $limit = "", $open, $width = "100%", $last_visited = 0, $cmd_data)
    {
    global $auth, $SessSemName;

    if ($show_admin && $touch_id = Request::option('touch_news')) {
        StudipNews::TouchNews($touch_id);
    }

    $news = StudipNews::GetNewsByRange($range_id, true);

    // Adjust news' open state
    foreach ($news as $id => &$news_item) {
        $news_item['open'] = ($id == $open);
    }

    if ($SessSemName[1] == $range_id) {
        $admin_link = sprintf('new_%1$s=TRUE&view=news_%1$s', $SessSemName['class'] == 'sem' ? 'sem' : 'inst');
    } else if ($range_id == $auth->auth['uid']) {
        $admin_link = 'range_id=self';
    } else if ($range_id == 'studip') {
        $admin_link = 'range_id=studip';
    } else if (isDeputyEditAboutActivated() && isDeputy($auth->auth['uid'], $range_id, true)) {
        $admin_link = 'range_id=' . $range_id;
    }

    // Leave if there are no news and we are not an admin
    if (!count($news) && !$show_admin) {
        return false;
    }

    SkipLinks::addIndex(_('Ankündigungen'), 'news_box');
    $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
    if (!count($news)) {
        $template = $this->factory->open('list-empty');
        $template->width      = $width;
        $template->admin_link = $admin_link;
    } else {
        $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                ? StudipNews::GetRssIdFromRangeId($range_id)
                : false;


        $template = $this->factory->open('list');
        $template->width      = $width;
        $template->rss_id     = $rss_id;
        $template->show_admin = $show_admin;
        $template->admin_link = $admin_link;
        $template->news       = $news;
        $template->cmd_data   = $cmd_data;
    }
    return $template->render();

}
    /**
     * Checks for permission of the user to view the given news
     *
     * @param  StudipNews  the news that the user wants to view
     *
     * @return array       an array of booleans, the first value is the TRUE, if the
     *                     user is permitted, the second is TRUE, if that user
     *                     may administer the news
     */
    function ajaxified_news_has_permission($news)
    {

        $permitted = FALSE;
        $show_admin = FALSE;

        foreach ($news->getRanges() as $range) {

            $object_type = 'studip' === $range
            ? 'studip'
            : get_object_type($range, words('sem inst fak user'));

            if ('studip' === $object_type) {
                $permitted = TRUE;
                $show_admin = $GLOBALS['perm']->have_perm('root');
            }

            else if (in_array($object_type, words('sem inst fak'))) {

                if ($_SESSION['SessionSeminar'] === (string)$range) {
                    $permitted = TRUE;
                    $show_admin = $GLOBALS['perm']->have_studip_perm('tutor', $range);
                }
            }

            else if ('user' === $object_type) {
                if ($range === $GLOBALS['auth']->auth['uid']
                || get_visibility_by_id($range)) {
                    $permitted = TRUE;
                    $show_admin = $GLOBALS['perm']->have_perm('autor')
                    && $GLOBALS['auth']->auth['uid'] === $range;
                }
            }

            if ($show_admin) {
                break;
            }
        }

        return array($permitted, $show_admin);
    }
    function show_news_item_action($news_item, $cmd_data, $show_admin, $admin_link,$plugin, $widgetId)
    {
        global $auth;
        $id = $news_item['news_id'];
        $tempnew = (($news_item['chdate'] >= object_get_visit($id, 'news', false, false))
                 && ($news_item['user_id'] != $auth->auth['uid']));

        if ($tempnew && Request::get("new_news")) {
            $news_item['open'] = $tempnew;
        }
        $titel = htmlReady(mila($news_item['topic']));

        if ($news_item['open']) {
            $link = '?nclose=true';

            if ($cmd_data['comopen'] != $id) {
                $titel .= '<a name="anker"></a>';
            }

            if ($news_item['user_id'] != $auth->auth['uid']) {
                object_add_view($id);  //Counter for news - not my own
            }

            object_set_visit($id, 'news'); //and, set a visittime
        } else {
            $link = '?nopen=' . $id;
        }

        $user = User::find($news_item['user_id']);

        $link .= '&username=' . $user->username . '#anker';
        $titel = sprintf('<a href="#" onclick="NEWSWIDGET.openclose(\'%s\', \'%s\',\'%s\'); return false;" class="tree">%s</a>',
                      $id, $admin_link, $widgetId, $titel);
        $onclick = sprintf('onclick="NEWSWIDGET.openclose(\'%s\', \'%s\',\'%s\'); return false;" ',
                      $id, $admin_link, $widgetId,  $titel);
        $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');

        $template = $this->factory->open('news');
        $template->id         = $id;
        $template->link       = $link;
        $template->onclick    = $onclick;
        $template->news_item  = $news_item;
        $template->icon       = Assets::img('icons/16/grey/news.png', array('class' => 'text-bottom'));
        $template->titel      = $titel;
        $template->zusatz     = $this->factory->render('zusatz', compact('user', 'news_item'));
        $template->cmd_data   = $cmd_data;
        $template->show_admin = $show_admin;
        $template->admin_link = $admin_link;
        $template->tempnew    = $tempnew;
        $template->plugin     = $plugin;
        $template->widgetId   = $widgetId;

        echo $template->render();

    }
    function comsubmit_action()
    {
        global $auth, $_fullname_sql;
        if(isset($_POST['id'])) {
             $id = $_POST['id'];
             $widgetId = $_POST['widgetId'];
             $comment_content = trim($_POST['comment_content']);
             if ($comment_content) {
                $comment = new StudipComments();
                $index = StudipComments::NumCommentsForObject($id);
                $comment->setValue('object_id', $id);
                $comment->setValue('user_id', $auth->auth['uid']);
                $comment->setValue('content', stripslashes($comment_content));
                $comment->store();
            }
        }
        $news = new StudipNews($id);
        list($permitted, $show_admin) = $this->ajaxified_news_has_permission($news);
        $all_comments = StudipComments::GetCommentsForObject($id);
        $new_comment = end($all_comments);
        $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
        $template = $this->factory->open('comment-box');
        $template->index = $index;
        $template->comment = $new_comment;
        $template->show_admin = $show_admin;
        echo $template->render();


    }

    function comdel_action()
    {
     global $auth, $perm;
        if ($_POST)
        {
            $newsId = $_POST['newsId'];
            $comId = $_POST['comId'];

        }
        $ok = 0;
        $comment = new StudipComments($comId);
        if (!$comment->isNew()) {
            if ($perm->have_perm("root")) {
                $ok = 1;
            } else {
                $news = new StudipNews($comment->getValue("object_id"));
                if (!$news->isNew() && $news->getValue("user_id") == $auth->auth["uid"]) {
                    $ok = 1;
                }
            }
            if ($ok) {
                $ok = $comment->delete();
            }
        }
        echo $ok;


    }
/**
 *
 * @param unknown_type $news_item
 * @param unknown_type $cmd_data
 * @param unknown_type $show_admin
 * @param unknown_type $admin_link
 */
function show_news_item_content_action($news_item, $cmd_data, $show_admin, $admin_link,$plugin, $widgetId)
{
    global $auth;

    $id = $news_item['news_id'];

    $user = User::find($news_item['user_id']);

    $unamelink = '&username='.$user->username;
    $uname = $user->username;

    list($content, $admin_msg) = explode('<admin_msg>', $news_item['body']);

    if (!$content) {
        $content = _('Keine Beschreibung vorhanden.');
    }

    if ($news_item['chdate_uid']) {
        $admin_msg = StudipNews::GetAdminMsg($news_item['chdate_uid'], $news_item['chdate']);
    }

    //
    // Kommentare
    //
    if ($news_item['allow_comments']) {
        $showcomments = $cmd_data['comopen'] == $id;

        if ($cmd_data['comsubmit'] == $id) {
            $comment_content = trim(Request::get('comment_content'));
            if ($comment_content) {
                $comment = new StudipComments();
                $comment->setValue('object_id', $id);
                $comment->setValue('user_id', $auth->auth['uid']);
                $comment->setValue('content', stripslashes($comment_content));
                $comment->store();
            }
            $showcomments = 1;
        } else if ($cmd_data['comdelnews'] == $id) {
            delete_comment($cmd_data['comdel']);
            $showcomments = 1;
        }
    }
    $this->factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');

    $template = $this->factory->open('news-content');

    $template->news_item     = $news_item;
    $template->admin_link    = $admin_link;
    $template->may_edit      = ($auth->auth['uid'] == $news_item['user_id'] || $show_admin);
    $template->content       = $content;
    $template->show_comments = $showcomments;
    $template->show_admin    = $show_admin;
    $template->admin_msg     = $admin_msg;
    $template->plugin        = $plugin;
    $template->widgetId      = $widgetId;

    header('Content-Type: text/html; charset=windows-1252');
    echo $template->render();

  }

}

