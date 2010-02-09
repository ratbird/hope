<?php
# Lifter007: TODO
# Lifter003: TODO

/*
 * news.php - News controller
 *
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/functions.php';
require_once 'lib/showNews.inc.php';
require_once 'lib/user_visible.inc.php';


class NewsController extends Trails_Controller {


  function before_filter($action, &$args) {
    # open session
    page_open(array('sess' => 'Seminar_Session',
                    'auth' => 'Seminar_Auth',
                    'perm' => 'Seminar_Perm',
                    'user' => 'Seminar_User'));
    require_once 'lib/seminar_open.php';
    # user must be logged in
    $GLOBALS['auth']->login_if($_REQUEST['again']
                               && ($GLOBALS['auth']->auth['uid'] == 'nobody'));
  }


  function after_filter($action, &$args) {
    page_close();
  }


  function open_action($id = NULL) {
    $this->open_or_close(TRUE, $id);
  }


  function close_action($id = NULL) {
    $this->open_or_close(FALSE, $id);
  }


  function open_or_close($open, $id) {

    if (is_null($id)) {
      $this->set_status(400);
      return $this->render_nothing();
    }

    # get news item
    $this->news = new StudipNews($id);
    if ($this->news->is_new) {
      $this->set_status(404);
      return $this->render_nothing();
    }


    # check for permission for at least one of those ranges
    list($permitted, $show_admin) = ajaxified_news_has_permission($this->news);
    if (!$permitted) {
      $this->set_status(401);
      return $this->render_nothing();
    }

    # show news
    $this->news->content['open'] = $open;
    $this->show_admin = $show_admin;

    $this->render_template('news/open_or_close');
  }
}


################################################################################


/**
 * Checks for permission of the user to view the given news
 *
 * @param  StudipNews  the news that the user wants to view
 *
 * @return array       an array of booleans, the first value is the TRUE, if the
 *                     user is permitted, the second is TRUE, if that user
 *                     may administer the news
 */
function ajaxified_news_has_permission($news) {

  $permitted = FALSE;
  $show_admin = FALSE;

  foreach ($news->getRanges() as $range) {

    $object_type = 'studip' === $range
                     ? 'studip'
                     : get_object_type($range);

    if ('studip' === $object_type) {
      $permitted = TRUE;
      $show_admin = $GLOBALS['perm']->have_perm('root');
    }

    else if (in_array($object_type, words('sem inst fak'))) {

      if ($GLOBALS['SessSemName'][1] === (string)$range) {
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

