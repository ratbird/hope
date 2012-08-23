<?php
# Lifter001: DONE
# Lifter002: TEST - the small chunks of html in show_news_item() are hard to get rid of [tlx]
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 * showNews.inc.php - Anzeigefunktion fuer News
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     news
 */

require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/language.inc.php';
require_once 'lib/object.inc.php';
require_once 'lib/classes/StudipNews.class.php';
require_once 'lib/classes/StudipComments.class.php';
require_once 'lib/classes/Seminar.class.php';

/**
 *
 * @param unknown_type $cmd_data
 */
function process_news_commands(&$cmd_data)
{
    //Auf und Zuklappen News

    $cmd_data["comopen"]='';
    $cmd_data["comnew"]='';
    $cmd_data["comsubmit"]='';
    $cmd_data["comdel"]='';
    $cmd_data["comdelnews"]='';
    $comsubmit = Request::option('comsubmit');
    if (!empty($comsubmit)) {
        $cmd_data["comsubmit"]=$comsubmit;
        Request::set('comopen',$comsubmit);
    }
    $comdelnews = Request::quoted('comdelnews');
    if (Request::quoted('comdelnews')){
        $cmd_data["comdelnews"] = $comdelnews;
        Request::set('comopen',$comdelnews);
    }
    $comopen = Request::quoted('comopen');
    if (Request::quoted('comopen')) {
        $cmd_data["comopen"] = $comopen;
        Request::set('nopen',$comopen);
    }

    if (Request::option('nopen')) $cmd_data["nopen"]=Request::option('nopen');
    if (Request::quoted('nclose'))  $cmd_data["nopen"]='';
    if (Request::quoted('comnew')) $cmd_data["comnew"]=Request::quoted('comnew');
    if (Request::quoted('comdel')) $cmd_data["comdel"]=Request::quoted('comdel');
}

/**
 *
 * @param unknown_type $comment_id
 */
function delete_comment($comment_id)
{
    global $auth, $perm;

    $ok = 0;
    $comment = new StudipComments($comment_id);
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
    return $ok;
}

/**
 *
 * @param unknown_type $range_id
 * @param unknown_type $show_admin
 * @param unknown_type $limit
 * @param unknown_type $open
 * @param unknown_type $width
 * @param unknown_type $last_visited
 * @param unknown_type $cmd_data
 */
function show_news($range_id, $show_admin = FALSE, $limit = "", $open, $width = "100%", $last_visited = 0, $cmd_data)
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

    if (!count($news)) {
        $template = $GLOBALS['template_factory']->open('news/list-empty');
        $template->width      = $width;
        $template->admin_link = $admin_link;
    } else {
        $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                ? StudipNews::GetRssIdFromRangeId($range_id)
                : false;

        $template = $GLOBALS['template_factory']->open('news/list');
        $template->width      = $width;
        $template->rss_id     = $rss_id;
        $template->show_admin = $show_admin;
        $template->admin_link = $admin_link;
        $template->news       = $news;
        $template->cmd_data   = $cmd_data;
    }
    echo $template->render();

    return true;
}

/**
 *
 * @param unknown_type $range_id
 * @param unknown_type $type
 */
function show_rss_news($range_id, $type)
{
    $item_url_fmt = '%s&nopen=%s';

    switch ($type){
        case 'user':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'about.php?again=yes&username=' . get_username($range_id);
            $title = get_fullname($range_id) . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $description = _('Persönliche Neuigkeiten') . ' ' . $title;
        break;
        case 'sem':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'seminar_main.php?auswahl=' . $range_id;
            $sem_obj = Seminar::GetInstance($range_id);
            if ($sem_obj->read_level > 0) $studip_url .= '&again=yes';
            $title = $sem_obj->getName() . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $description = _('Neuigkeiten der Veranstaltung') . ' ' . $title;

        break;
        case 'inst':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'institut_main.php?auswahl=' . $range_id;
            $object_name = get_object_name($range_id, $type);
            $title = $object_name['name'] . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $description = _('Neuigkeiten der Einrichtung') . ' ' . $title;
        break;
        case 'global':
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'index.php?again=yes';
            $item_url_fmt = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/news/get_news/%2$s';
            $title = 'Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'];
            $description = _('Allgemeine Neuigkeiten') . ' ' . $title;
        break;
    }

    $items = StudipNews::GetNewsByRange($range_id, true);
    $last_changed = 0;

    foreach ($items as &$item) {
        if ($last_changed < $item['chdate']) {
            $last_changed = $item['chdate'];
        }

        if ($item['date'] < $item['chdate']) {
            $item['date'] = $item['chdate'];
        }
        list($body, $admin_msg) = explode('<admin_msg>', $item['body']);
        $item['body'] = $body;
    }

    header('Content-type: text/xml; charset=utf-8');

    $template = $GLOBALS['template_factory']->open('news/rss-feed');
    $template->items        = $items;
    $template->title        = $title;
    $template->studip_url   = $studip_url;
    $template->description  = $description;
    $template->last_changed = $last_changed;
    $template->item_url_fmt = $item_url_fmt;
    echo $template->render();

    return true;
}

/**
 *
 * @param unknown_type $news_item
 * @param unknown_type $cmd_data
 * @param unknown_type $show_admin
 * @param unknown_type $admin_link
 */
function show_news_item($news_item, $cmd_data, $show_admin, $admin_link)
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
    $titel = sprintf('<a href="%s" onclick="STUDIP.News.openclose(\'%s\', \'%s\'); return false;" class="tree">%s</a>',
                     URLHelper::getLink($link), $id, $admin_link, $titel);

    $template = $GLOBALS['template_factory']->open('news/news');
    $template->link       = $link;
    $template->news_item  = $news_item;
    $template->icon       = Assets::img('icons/16/grey/news.png', array('class' => 'text-bottom'));
    $template->titel      = $titel;
    $template->zusatz     = $GLOBALS['template_factory']->render('news/zusatz', compact('user', 'news_item'));
    $template->cmd_data   = $cmd_data;
    $template->show_admin = $show_admin;
    $template->admin_link = $admin_link;
    $template->tempnew    = $tempnew;

    return $template->render();
}

/**
 *
 * @param unknown_type $news_item
 * @param unknown_type $cmd_data
 * @param unknown_type $show_admin
 * @param unknown_type $admin_link
 */
function show_news_item_content($news_item, $cmd_data, $show_admin, $admin_link)
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

    $template = $GLOBALS['template_factory']->open('news/news-content');
    $template->news          = $news_item;
    $template->admin_link    = $admin_link;
    $template->may_edit      = ($auth->auth['uid'] == $news_item['user_id'] || $show_admin);
    $template->content       = $content;
    $template->show_comments = $showcomments;
    $template->show_admin    = $show_admin;
    $template->admin_msg     = $admin_msg;

    return $template->render();
}
