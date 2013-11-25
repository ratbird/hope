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
 * @author      Arne Schr�der <schroeder@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     news
 */

require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/language.inc.php';
require_once 'lib/object.inc.php';
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
    $comsubmit = Request::option('comsubmit');
    if (!empty($comsubmit)) {
        $cmd_data["comsubmit"]=$comsubmit;
        Request::set('comopen',$comsubmit);
    }
    $comopen = Request::quoted('comopen');
    if (Request::quoted('comopen')) {
        $cmd_data["comopen"] = $comopen;
        Request::set('nopen',$comopen);
    }

    if (Request::option('nopen')) $cmd_data["nopen"]=Request::option('nopen');
    if (Request::quoted('nclose')) $cmd_data["nopen"]='';
    if (Request::quoted('comnew')) $cmd_data["comnew"]=Request::quoted('comnew');
}

/**
 * generates proper text for confirmation question and deletes comments
 *
 *
 * @param mixed $comment_id (single or array)
 * @return string text for confirmation question or empty string after deletion
 */
function delete_comments($delete_comments_array = '') 
{
    $text = '';
    if (! is_array($delete_comments_array))
        $delete_comments_array = array($delete_comments_array);
    if (Request::submitted('yes'))
        $confirmed = true;
    if ($confirmed) {
        foreach ($delete_comments_array as $comment_id) {
            $delete_comment = new StudipComments($comment_id);
            if (!$delete_comment->isNew()) {
                if (!is_object($news[$delete_comment->getValue("object_id")]))
                    $news[$delete_comment->getValue("object_id")] = new StudipNews($delete_comment->getValue("object_id"));
                // user has to have delete permission for news
                if ($news[$delete_comment->getValue("object_id")]->havePermission('delete')) {
                    $delete_comment->delete();
                    $delete_counter++;
                }
                else
                    PageLayout::postMessage(MessageBox::error(_('Keine Berechtigung zum L�schen des Kommentars.')));
            }
        }
        if ($delete_counter > 1)
            PageLayout::postMessage(MessageBox::success(sprintf(_('%s Kommentare wurden gel�scht.'), $delete_counter)));
        elseif ($delete_counter == 1)
            PageLayout::postMessage(MessageBox::success(_('Kommentar wurde gel�scht.')));
    }
    else {
        if (count($delete_comments_array) > 1)
            $text = sprintf(_('Wollen Sie die %s Komentare jetzt l�schen?'), count($delete_comments_array));
        elseif (count($delete_comments_array) == 1)
            $text = _('Wollen Sie den Kommentar jetzt l�schen?');
    }
    return $text;
}

/**
 * generates proper text for confirmation question and deletes news
 *
 *
 * @param mixed $delete_news_array (single id or array)
 * @return string text for confirmation question or empty string after deletion
 */
function delete_news($delete_news_array) 
{
    $text = '';
    if (! is_array($delete_news_array))
        $delete_news_array = array($delete_news_array);
    if (Request::submitted('yes'))
        $confirmed = true;
    foreach ($delete_news_array as $news_id) {
        if ($news_id) {
            $delete_news = new StudipNews($news_id);
            $delete_news_titles[] = $delete_news->getValue('topic');
            if ($confirmed) {
                $msg_object = new messaging();
                if ($delete_news->havePermission('delete')) {
                    PageLayout::postMessage(MessageBox::success(sprintf(_('Ank�ndigung "%s" wurde gel�scht.'), $delete_news->getValue('topic'))));
                    if ($delete_news->getValue('user_id') != $GLOBALS['auth']->auth['uid']) {
                        setTempLanguage($delete_news->getValue('user_id'));
                        $msg = sprintf(_('Ihre Ank�ndigung \"%s\" wurde von einer Administratorin oder einem Administrator gel�scht!.'), $delete_news->getValue('topic'), get_fullname() . ' ('.get_username().')'). "\n";
                        $msg_object->insert_message($msg, get_username($delete_news->getValue('user_id')) , "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Ank�ndigung ge�ndert"));
                        restoreLanguage();
                    }
                    $delete_news->delete();
                }
                else
                    PageLayout::postMessage(MessageBox::error(sprintf(_('Keine Berechtigung zum L�schen der Ank�ndigung "%s".'), $delete_news->getValue('topic'))));
            }
        }
    }
    if (! $confirmed) {
        if (count($delete_news_titles) == 1)
            $text = sprintf(_('- Die Ank�ndigung "%s" wird unwiderruflich gel�scht.'), $delete_news_titles[0])."\n";
        elseif (count($delete_news_titles) > 1)
            $text = sprintf(_('- Die %s Ank�ndigungen "%s" werden unwiderruflich gel�scht.'), count($delete_news_titles), implode('", "', $delete_news_titles))."\n";
    }
    return $text;
}

/**
 * generates proper text for confirmation question and removes range_id from news
 *
 *
 * @param $remove_array array with $news_id as key and array of range_ids as value
 * @param string $range_id
 * @return string text for confirmation question or empty string after removal
 */
function remove_news($remove_array) 
{
    $question_text = array();
    if (! is_array($remove_array))
        return false;
    if (Request::submitted('yes'))
        $confirmed = true;
    foreach ($remove_array as $news_id => $ranges) {
        $remove_news = new StudipNews($news_id);
        $remove_news_title = $remove_news->getValue('topic');
        if (! is_array($ranges))
            $ranges = array($ranges);
        // should we delete news completely
        if (count($ranges) == count($remove_news->getRanges())) {                
            $text = delete_news($news_id);
            if ($text)
                $question_text[] = $text;
        // or just remove range_id(s)?
        } else {
            $text = '';
            if ($confirmed AND ! $remove_news->isNew() AND count($ranges)) {
                if ($remove_news->havePermission('unassign')) {
                    foreach ($ranges as $range_id) {
                        $remove_news->deleteRange($range_id);
                    }
                    if (count($ranges) == 1)
                        PageLayout::postMessage(MessageBox::success(sprintf(_('Ank�ndigung "%s" wurde aus dem Bereich entfernt.'), $remove_news->getValue('topic'))));
                    else
                        PageLayout::postMessage(MessageBox::success(sprintf(_('Ank�ndigung "%s" wurde aus %s Bereichen entfernt.'), $remove_news->getValue('topic'), count($ranges))));
                    $remove_news->store();
                } else
                    PageLayout::postMessage(MessageBox::error(sprintf(_('Keine Berechtigung zum Entfernen der Ank�ndigung "%s".'), $remove_news->getValue('topic'))));
            } elseif (! $confirmed) {
                if (count($ranges) == 1)
                    $text = sprintf(_('- Die Ank�ndigung "%s" wird aus dem aktiven Bereich entfernt. '
                                      .'Sie wird dadurch nicht endg�ltig gel�scht, sondern ausgeh�ngt.'), $remove_news_title)."\n";
                elseif (count($ranges) > 1)
                    $text = sprintf(_('- Die Ank�ndigung "%s" wird aus den %s gew�hlten Bereichen entfernt. '
                                      .'Sie wird dadurch nicht endg�ltig gel�scht, sondern ausgeh�ngt.'), $remove_news_title, count($ranges))."\n";
            }
            if ($text)
               $question_text[] = $text;
        }
    }
    if (count($question_text) > 1)
        return _('Wollen Sie die folgenden Aktionen jetzt ausf�hren?') . "\n" . implode($question_text);
    elseif (count($question_text) == 1)
        return _('Wollen Sie diese Aktion jetzt ausf�hren?') . "\n" . implode($question_text);
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

    // delete order?
    if (is_array($news[Request::option('ndelete')])) {
        $question_text = delete_news(Request::option('ndelete'));
        $question_param = array('ndelete' => Request::option('ndelete'), 'yes' => 1);      
        // reload news items
        $news = StudipNews::GetNewsByRange($range_id, true);
        if ($question_text)
            $question_text = _('Wollen Sie die folgende Aktion jetzt ausf�hren?') . "\n" . $question_text;
    }
    // remove order?
    elseif ($news[Request::option('nremove')]) {
        $question_text = remove_news(array(Request::option('nremove') => $range_id));
        $question_param = array('nremove' => Request::option('nremove'), 'yes' => 1);      
        // reload news items
        $news = StudipNews::GetNewsByRange($range_id, true);
    }
    
    // Adjust news' open state
    foreach ($news as $id => &$news_item) {
        $news_item['open'] = ($id == $open);
    }

    // Leave if there are no news and we are not an admin
    if (!count($news) && !$show_admin) {
        return false;
    }

    SkipLinks::addIndex(_('Ank�ndigungen'), 'news_box');

    if (!count($news)) {
        $template = $GLOBALS['template_factory']->open('news/list-empty');
        $template->width      = $width;
        $template->range_id   = $range_id;
    } else {
        $rss_id = get_config('NEWS_RSS_EXPORT_ENABLE')
                ? StudipNews::GetRssIdFromRangeId($range_id)
                : false;

        $template = $GLOBALS['template_factory']->open('news/list');
        $template->question_text = $question_text;
        $template->question_param = $question_param;
        $template->width      = $width;
        $template->range_id   = $range_id;
        $template->rss_id     = $rss_id;
        $template->show_admin = $show_admin;
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
            $studip_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/profile?again=yes&username=' . get_username($range_id);
            $title = get_fullname($range_id) . ' (Stud.IP - ' . $GLOBALS['UNI_NAME_CLEAN'] . ')';
            $description = _('Pers�nliche Neuigkeiten') . ' ' . $title;
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
    $news_object = new StudipNews($news_item['news_id']);
    $template = $GLOBALS['template_factory']->open('news/news-content');
    $template->news          = $news_item;
    $template->may_edit      = $news_object->havePermission('edit');
    $template->may_unassign  = $news_object->havePermission('unassign');
    $template->may_delete    = $news_object->havePermission('delete');
    $template->content       = $content;
    $template->show_comments = $showcomments;
    $template->show_admin    = $show_admin;
    $template->admin_msg     = $admin_msg;

    return $template->render();
}
