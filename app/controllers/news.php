<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * news.php - News controller
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>, Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/functions.php';
require_once 'lib/showNews.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'studip_controller.php';

class NewsController extends StudipController
{
    /**
     * Callback function being called before an action is executed.
     */
    function before_filter(&$action, &$args)
    {
        // open session
        page_open(array('sess' => 'Seminar_Session',
                        'auth' => 'Seminar_Default_Auth',
                        'perm' => 'Seminar_Perm',
                        'user' => 'Seminar_User'));

        // set up user session
        include 'lib/seminar_open.php';

        // allow only "word" characters in arguments
        $this->validate_args($args);
    }

    /**
     * Callback function being called after an action is executed.
     */
    function after_filter($action, $args)
    {
        page_close();
    }

    function get_news_action($id)
    {

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
        $this->content = show_news_item_content($newscontent,
                                                array(),
                                                $show_admin,
                                                Request::get('admin_link')
        );
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
}

