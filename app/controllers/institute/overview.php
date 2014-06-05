<?php
# Lifter010: TODO

/*
 * Copyright (C) 2014 - Arne Schröder <schroeder@data-quest.de>
 *
 * formerly institut_main.php - Die Eingangsseite fuer ein Institut
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/showNews.inc.php';
require_once 'lib/show_dates.inc.php';
require_once 'lib/vote/vote_show.inc.php';


class Institute_OverviewController extends AuthenticatedController
{
    protected $allow_nobody = true;

    function before_filter(&$action, &$args) {
        global $SEM_TYPE, $SEM_CLASS;

        if (Request::option('auswahl')) {
            Request::set('cid', Request::option('auswahl'));
        }

        parent::before_filter($action, $args);

        checkObject();
        $this->institute = Institute::findCurrent();
        if (!$this->institute) {
            throw new CheckObjectException(_('Sie haben kein Objekt gewählt.'));
        }
        $this->institute_id = $this->institute->id;

        //set visitdate for institute, when coming from meine_seminare
        if (Request::option('auswahl')) {
            object_set_visit($this->institute_id, "inst");
        }

        //gibt es eine Anweisung zur Umleitung?
        if (Request::get('redirect_to')) {
            $query_parts = explode('&', stristr(urldecode($_SERVER['QUERY_STRING']), 'redirect_to'));
            list( , $where_to) = explode('=', array_shift($query_parts));
            $new_query = $where_to . '?' . join('&', $query_parts);
            page_close();
            $new_query = preg_replace('/[^0-9a-z+_#?&=.-\/]/i', '', $new_query);
            header('Location: '.URLHelper::getURL($new_query, array('cid' => $this->institute_id)));
            die;
        }

        PageLayout::setHelpKeyword("Basis.Einrichtungen");
        PageLayout::setTitle($_SESSION['SessSemName']["header_line"]. " - " ._("Kurzinfo"));
        Navigation::activateItem('/course/main/info');

    }

    /**
     * This method is called to show the form to upload a new avatar for a
     * course.
     *
     * @return void
     */
    function index_action()
    {
        $this->sidebar = Sidebar::get();
        $this->sidebar->setImage(Assets::image_path("sidebar/institute-sidebar.png"));

        if (get_config('NEWS_RSS_EXPORT_ENABLE') && $this->institute_id){
            $rss_id = StudipNews::GetRssIdFromRangeId($this->institute_id);
            if ($rss_id) {
                PageLayout::addHeadElement('link', array('rel'   => 'alternate',
                                                         'type'  => 'application/rss+xml',
                                                         'title' => 'RSS',
                                                         'href'  => 'rss.php?id='.$rss_id));
            }
        }
        // list of used modules
        $Modules = new Modules;
        $modules = $Modules->getLocalModules($this->institute_id);

        URLHelper::bindLinkParam("inst_data", $this->institut_main_data);

        // (un)subscribe to institute
        if ($GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] AND ($GLOBALS['user']->id != 'nobody') AND ! $GLOBALS['perm']->have_perm('admin')) {
            $widget = new ActionsWidget();
            if (! $GLOBALS['perm']->have_studip_perm('user', $this->institute_id)) {
                $url = URLHelper::getLink('dispatch.php/institute/overview', array(
                    'follow_inst' => 'on'
                ));
                $widget->addLink(_('Einrichtung abonnieren'), $url);
            } elseif (! $GLOBALS['perm']->have_studip_perm('autor', $this->institute_id)) {
                $url = URLHelper::getLink('dispatch.php/institute/overview', array(
                    'follow_inst' => 'off'
                ));
                $widget->addLink(_('Austragen aus der Einrichtung'), $url);
            }
            $this->sidebar->addWidget($widget);

            if (! $GLOBALS['perm']->have_studip_perm('user', $this->institute_id) AND (Request::option('follow_inst') == 'on')) {
                $query = "INSERT IGNORE INTO user_inst
                          (user_id, Institut_id, inst_perms)
                          VALUES (?, ?, 'user')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $GLOBALS['user']->user_id,
                    $this->institute_id
                ));
                if ($statement->rowCount() > 0) {
                    log_event('INST_USER_ADD', $this->institute_id, $GLOBALS['user']->user_id, 'user');
                    PageLayout::postMessage(MessageBox::success(_("Sie haben die Einrichtung abonniert.")));
                    header('Location: '.URLHelper::getURL('', array('cid' => $this->institute_id)));
                    die;
                }
            } elseif (! $GLOBALS['perm']->have_studip_perm('autor', $this->institute_id) AND (Request::option('follow_inst') == 'off')) {
                $query = "DELETE FROM user_inst
                          WHERE user_id = ?  AND Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $GLOBALS['user']->user_id,
                    $this->institute_id
                ));
                if ($statement->rowCount() > 0) {
                    log_event('INST_USER_DEL', $this->institute_id, $GLOBALS['user']->user_id, 'user');
                    PageLayout::postMessage(MessageBox::success(_("Sie haben sich aus der Einrichtung ausgetragen.")));
                    header('Location: '.URLHelper::getURL('', array('cid' => $this->institute_id)));
                    die;
                }
            }
        }

        //Auf und Zuklappen News
        process_news_commands($this->institut_main_data);
    }
}
